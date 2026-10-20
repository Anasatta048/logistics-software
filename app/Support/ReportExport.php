<?php

namespace App\Support;

use App\Models\Account;
use App\Models\Broker;
use App\Models\Company;
use App\Models\Driver;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * One place that turns a report's table into a download.
 *
 * CSV: UTF-8 with a BOM (so Excel shows $ and accents correctly) and a short
 * header block (company, report, period, filters, who/when) above the table.
 * PDF: A4, branded header with the logo, the same header details, the table
 * with numbers right-aligned, and page numbers.
 */
final class ReportExport
{
    /**
     * @param  list<string>  $headings
     * @param  iterable<int, array<int|string, mixed>>  $rows
     * @param  array<string, mixed>  $filters  the validated filter input
     * @param  array<string, string|int|float>  $summary  label => value, shown under the table
     */
    public static function respond(string $format, string $title, string $filename, array $headings, iterable $rows, array $filters = [], array $summary = []): StreamedResponse|Response
    {
        $rows = collect($rows)->map(fn ($row): array => array_values((array) $row))->all();
        $meta = self::meta($title, $filters);
        $file = $filename.'-'.now()->format('Ymd-His');

        return $format === 'pdf'
            ? self::pdf($title, $file, $headings, $rows, $meta, $summary)
            : self::csv($file, $headings, $rows, $meta, $summary);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{company: string, title: string, period: string, filters: list<string>, generated: string}
     */
    private static function meta(string $title, array $filters): array
    {
        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;
        $period = match (true) {
            $from && $to => self::date($from).' – '.self::date($to),
            (bool) $from => 'From '.self::date($from),
            (bool) $to => 'Up to '.self::date($to),
            default => 'All dates',
        };

        $applied = [];
        $lookup = [
            'company_id' => ['Company', fn ($id) => Company::find($id)?->name],
            'broker_id' => ['Broker', fn ($id) => Broker::find($id)?->company_name],
            'driver_id' => ['Driver', fn ($id) => Driver::find($id)?->name],
            'user_id' => ['Dispatcher', fn ($id) => User::find($id)?->name],
            'account_id' => ['Account', fn ($id) => Account::find($id)?->name],
            'status' => ['Status', fn ($value) => str((string) $value)->replace('_', ' ')->title()->toString()],
        ];

        foreach ($lookup as $key => [$label, $resolve]) {
            if (filled($filters[$key] ?? null) && ($name = $resolve($filters[$key]))) {
                $applied[] = "{$label}: {$name}";
            }
        }

        return [
            'company' => config('invoice.company_name') ?: config('app.name'),
            'title' => $title,
            'period' => $period,
            'filters' => $applied,
            'generated' => Timezones::toUs(now())?->format('M j, Y g:i A').' '.Timezones::usLabel().' · '.(auth()->user()?->name ?? 'system'),
        ];
    }

    private static function date(string $value): string
    {
        return \Illuminate\Support\Carbon::parse($value)->format('M j, Y');
    }

    /**
     * @param  list<string>  $headings
     * @param  list<list<mixed>>  $rows
     * @param  array<string, mixed>  $meta
     * @param  array<string, string|int|float>  $summary
     */
    private static function csv(string $file, array $headings, array $rows, array $meta, array $summary): StreamedResponse
    {
        return response()->streamDownload(function () use ($headings, $rows, $meta, $summary): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [$meta['company'].' — '.$meta['title']]);
            fputcsv($out, ['Period', $meta['period']]);

            if ($meta['filters'] !== []) {
                fputcsv($out, ['Filters', implode('; ', $meta['filters'])]);
            }

            fputcsv($out, ['Generated', $meta['generated']]);
            fputcsv($out, []);
            fputcsv($out, array_values($headings));

            foreach ($rows as $row) {
                fputcsv($out, array_map(fn ($cell) => is_float($cell) ? round($cell, 2) : $cell, $row));
            }

            if ($summary !== []) {
                fputcsv($out, []);

                foreach ($summary as $label => $value) {
                    fputcsv($out, [$label, $value]);
                }
            }

            fclose($out);
        }, $file.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @param  list<string>  $headings
     * @param  list<list<mixed>>  $rows
     * @param  array<string, mixed>  $meta
     * @param  array<string, string|int|float>  $summary
     */
    private static function pdf(string $title, string $file, array $headings, array $rows, array $meta, array $summary): Response
    {
        $pdf = Pdf::loadView('reports.pdf', [
            'meta' => $meta,
            'headings' => $headings,
            'rows' => $rows,
            'summary' => $summary,
            'logo' => \App\Support\BrandTheme::logoDataUri(),
        ])->setPaper('a4', count($headings) > 7 ? 'landscape' : 'portrait');

        return $pdf->download($file.'.pdf');
    }
}
