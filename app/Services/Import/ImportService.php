<?php

namespace App\Services\Import;

use DateTimeInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use OpenSpout\Writer\CSV\Writer as CsvWriter;

/**
 * Reads a CSV / Excel sheet, maps its headers to our columns, checks every
 * row and (on request) saves the good ones. A bad row never blocks a good one;
 * the bad rows come back with the reason so they can be fixed and re-sent.
 */
final class ImportService
{
    public const MAX_ROWS = 5000;

    /**
     * @return array{headers: list<string>, rows: list<list<?string>>}
     */
    public function parse(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $reader = $extension === 'xlsx' ? new XlsxReader() : new CsvReader();
        $reader->open($file->getRealPath());

        $headers = [];
        $rows = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $i => $row) {
                $cells = array_map(fn ($cell) => $this->cell($cell), $row->toArray());

                if ($headers === []) {
                    $headers = array_map(fn ($c) => (string) $c, $cells);

                    continue;
                }

                if (count(array_filter($cells, fn ($c) => $c !== null)) === 0) {
                    continue;
                }

                if (count($rows) >= self::MAX_ROWS) {
                    throw ValidationException::withMessages(['file' => 'That file has more than '.number_format(self::MAX_ROWS).' rows. Split it into smaller files.']);
                }

                $rows[] = $cells;
            }

            break; // the first sheet only
        }

        $reader->close();

        if ($headers === [] || $rows === []) {
            throw ValidationException::withMessages(['file' => 'The file has no data rows. The first row must be the column headings.']);
        }

        return ['headers' => $headers, 'rows' => $rows];
    }

    /**
     * Guess which sheet column feeds each of our columns, from the heading text.
     *
     * @param  list<string>  $headers
     * @return array<string, ?int> our column key => sheet column index
     */
    public function suggest(string $entity, array $headers): array
    {
        $normalised = array_map(fn (string $h): string => $this->norm($h), $headers);
        $map = [];

        foreach (ImportDefinitions::get($entity)['columns'] as $key => [$label, , $aliases]) {
            $map[$key] = null;
            foreach (array_merge([$label, $key], $aliases) as $candidate) {
                $found = array_search($this->norm($candidate), $normalised, true);
                if ($found !== false) {
                    $map[$key] = $found;
                    break;
                }
            }
        }

        return $map;
    }

    /**
     * Check every row; with $commit also save the valid ones.
     *
     * @param  list<list<?string>>  $rows
     * @param  array<string, ?int>  $mapping
     * @return array{valid: int, invalid: int, created: int, errors: list<array{line: int, messages: list<string>, data: array<string, mixed>}>}
     */
    public function run(string $entity, array $rows, array $mapping, bool $commit): array
    {
        $rules = ImportDefinitions::rules($entity);
        $ctx = ImportDefinitions::context();
        $result = ['valid' => 0, 'invalid' => 0, 'created' => 0, 'errors' => []];
        $uniqueKey = ['companies' => 'mc_number', 'brokers' => 'broker_mc_number', 'loads' => 'reference_number'][$entity] ?? null;
        $seen = [];

        foreach ($rows as $index => $cells) {
            $line = $index + 2; // sheet line: heading is line 1
            $row = $this->row($entity, $cells, $mapping);
            $messages = [];

            $validator = Validator::make($row, $rules);
            if ($validator->fails()) {
                $messages = $validator->errors()->all();
            }

            // The same number twice in one file would only fail on the second insert.
            if ($uniqueKey && filled($row[$uniqueKey] ?? null)) {
                $k = mb_strtolower((string) $row[$uniqueKey]);
                if (isset($seen[$k])) {
                    $messages[] = "Duplicate of line {$seen[$k]} in this file.";
                }
                $seen[$k] ??= $line;
            }

            if ($messages === [] && $commit) {
                try {
                    DB::transaction(fn () => ImportDefinitions::persist($entity, $row, $ctx));
                    $result['created']++;
                } catch (\InvalidArgumentException $e) {
                    $messages[] = $e->getMessage();
                } catch (ValidationException $e) {
                    $messages = array_merge($messages, collect($e->errors())->flatten()->all());
                } catch (\Throwable $e) {
                    report($e);
                    $messages[] = 'Could not be saved.';
                }
            }

            if ($messages === []) {
                $result['valid']++;

                continue;
            }

            $result['invalid']++;
            if (count($result['errors']) < 500) {
                $result['errors'][] = ['line' => $line, 'messages' => $messages, 'data' => $row];
            }
        }

        return $result;
    }

    /** A CSV the user can open in Excel: our headings and one sample row. */
    public function template(string $entity): string
    {
        $definition = ImportDefinitions::get($entity);
        $path = tempnam(sys_get_temp_dir(), 'tpl');
        $writer = new CsvWriter();
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues(array_map(fn (array $c): string => $c[0], $definition['columns'])));
        $writer->addRow(Row::fromValues(array_map(fn (array $c): string => (string) $c[3], $definition['columns'])));
        $writer->close();

        $content = (string) file_get_contents($path);
        @unlink($path);

        return $content;
    }

    /** The rejected rows as a CSV (with the reason), ready to fix and upload again. */
    public function errorsCsv(string $entity, array $errors): string
    {
        $columns = array_keys(ImportDefinitions::get($entity)['columns']);
        $path = tempnam(sys_get_temp_dir(), 'err');
        $writer = new CsvWriter();
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues(['line', 'problem', ...$columns]));

        foreach ($errors as $e) {
            $writer->addRow(Row::fromValues([$e['line'], implode(' ', $e['messages']), ...array_map(fn ($c) => $e['data'][$c] ?? '', $columns)]));
        }

        $writer->close();
        $content = (string) file_get_contents($path);
        @unlink($path);

        return $content;
    }

    /**
     * @param  list<?string>  $cells
     * @param  array<string, ?int>  $mapping
     * @return array<string, mixed>
     */
    private function row(string $entity, array $cells, array $mapping): array
    {
        $row = [];

        foreach (array_keys(ImportDefinitions::get($entity)['columns']) as $key) {
            $index = $mapping[$key] ?? null;
            $value = $index === null ? null : ($cells[$index] ?? null);

            if (is_string($value)) {
                $value = trim($value);
                $value = $value === '' ? null : $value;
            }

            // Words typed in a sheet ("Bi-Weekly", "Paid ") meet our lower-case codes.
            if ($value !== null && in_array($key, ['status', 'payment_frequency'], true)) {
                $value = str_replace(['-', ' '], '_', mb_strtolower($value));
            }

            if ($value !== null && is_string($value) && in_array($key, ['rate', 'total_miles', 'weight_lbs', 'default_commission_percentage', 'company_commission_percentage'], true)) {
                $value = str_replace([',', '$', '%', ' '], '', $value);
            }

            $row[$key] = $value;
        }

        return $row;
    }

    private function cell(mixed $cell): ?string
    {
        if ($cell instanceof DateTimeInterface) {
            return $cell->format('Y-m-d H:i:s');
        }

        if ($cell === null) {
            return null;
        }

        $text = trim((string) $cell);

        return $text === '' ? null : $text;
    }

    private function norm(string $text): string
    {
        return trim(preg_replace('/[^a-z0-9#%]+/', ' ', mb_strtolower($text)));
    }
}
