<?php

namespace App\Services\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\Process;
use ZipArchive;

/**
 * Daily dump of the database (gzip) plus a zip of uploaded files, kept for
 * `backup.keep_days`, optionally copied to an off-site disk, and a restore
 * check that loads the newest dump into a scratch database and compares row
 * counts, because a backup nobody has restored is only a hope.
 */
final class DatabaseBackup
{
    /** Tables whose row counts the restore check compares. */
    private const CHECKED_TABLES = ['tenants', 'users', 'loads', 'invoices', 'subscription_payments'];

    public function directory(): string
    {
        $dir = config('backup.path');

        if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            throw new RuntimeException("Cannot create backup folder {$dir}");
        }

        return $dir;
    }

    /** @return array{sql: string, files: ?string} the files written */
    public function run(): array
    {
        $stamp = now()->format('Y-m-d_His');
        $sql = $this->directory()."/db_{$stamp}.sql.gz";

        $this->dump($sql);

        $files = null;

        if (config('backup.include_files')) {
            $files = $this->directory()."/files_{$stamp}.zip";
            $this->zipUploads($files);
        }

        $this->offsite(array_filter([$sql, $files]));
        $this->prune();

        return ['sql' => $sql, 'files' => $files];
    }

    /** Newest database dump on disk, or null. */
    public function latest(): ?string
    {
        $found = glob($this->directory().'/db_*.sql.gz') ?: [];
        sort($found);

        return $found === [] ? null : end($found);
    }

    public function latestAgeHours(): ?float
    {
        $file = $this->latest();

        return $file === null ? null : round((time() - filemtime($file)) / 3600, 1);
    }

    /**
     * Restore the newest dump into `<db>_restore_check`, compare counts with the
     * live database, drop the scratch database.
     *
     * @return array<string, array{live: int, restored: int, ok: bool}>
     */
    public function verify(?string $file = null): array
    {
        $file ??= $this->latest() ?? throw new RuntimeException('There is no backup to verify.');
        $config = config('database.connections.'.config('database.default'));
        $scratch = $config['database'].'_restore_check';

        DB::statement("DROP DATABASE IF EXISTS `{$scratch}`");
        DB::statement("CREATE DATABASE `{$scratch}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        try {
            $this->restore($file, $scratch, $config);

            $result = [];

            foreach (self::CHECKED_TABLES as $table) {
                $live = (int) DB::table($table)->count();
                $restored = (int) DB::selectOne("SELECT COUNT(*) AS c FROM `{$scratch}`.`{$table}`")->c;
                $result[$table] = ['live' => $live, 'restored' => $restored, 'ok' => $restored > 0 || $live === 0];
            }

            return $result;
        } finally {
            DB::statement("DROP DATABASE IF EXISTS `{$scratch}`");
        }
    }

    private function dump(string $target): void
    {
        $c = config('database.connections.'.config('database.default'));

        $process = new Process(array_filter([
            config('backup.mysqldump'),
            '--host='.$c['host'],
            '--port='.$c['port'],
            '--user='.$c['username'],
            $c['password'] !== '' && $c['password'] !== null ? '--password='.$c['password'] : null,
            '--single-transaction', '--quick', '--routines', '--default-character-set=utf8mb4', '--no-tablespaces',
            $c['database'],
        ]));
        $process->setTimeout(1800);

        $gz = gzopen($target, 'wb9');
        $failed = false;

        $process->run(function (string $type, string $buffer) use ($gz, &$failed): void {
            if ($type === Process::OUT) {
                gzwrite($gz, $buffer);
            } else {
                $failed = $failed || str_contains($buffer, 'ERROR');
            }
        });
        gzclose($gz);

        if (! $process->isSuccessful() || $failed || filesize($target) < 200) {
            @unlink($target);
            throw new RuntimeException('mysqldump failed: '.trim($process->getErrorOutput()));
        }
    }

    private function restore(string $file, string $database, array $c): void
    {
        $process = new Process(array_filter([
            config('backup.mysql'),
            '--host='.$c['host'],
            '--port='.$c['port'],
            '--user='.$c['username'],
            $c['password'] !== '' && $c['password'] !== null ? '--password='.$c['password'] : null,
            '--default-character-set=utf8mb4',
            $database,
        ]));
        $process->setTimeout(1800);

        $in = gzopen($file, 'rb');
        $process->setInput((function () use ($in) {
            while (! gzeof($in)) {
                yield gzread($in, 1 << 20);
            }
            gzclose($in);
        })());
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('Restore failed: '.trim($process->getErrorOutput()));
        }
    }

    private function zipUploads(string $target): void
    {
        $zip = new ZipArchive();

        if ($zip->open($target, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Cannot create {$target}");
        }

        $root = storage_path('app');

        foreach (['public', 'private'] as $folder) {
            $base = "{$root}/{$folder}";

            if (! is_dir($base)) {
                continue;
            }

            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS)) as $file) {
                if ($file->isFile() && ! str_contains($file->getPathname(), DIRECTORY_SEPARATOR.'.gitignore')) {
                    $zip->addFile($file->getPathname(), $folder.'/'.ltrim(str_replace([$base, '\\'], ['', '/'], $file->getPathname()), '/'));
                }
            }
        }

        $zip->close();
    }

    /** @param  list<string>  $files */
    private function offsite(array $files): void
    {
        $disk = config('backup.offsite_disk');

        if (! $disk) {
            return;
        }

        foreach ($files as $file) {
            Storage::disk($disk)->put('backups/'.basename($file), fopen($file, 'rb'));
        }
    }

    private function prune(): void
    {
        $limit = now()->subDays(config('backup.keep_days'))->getTimestamp();

        foreach (glob($this->directory().'/{db_,files_}*', GLOB_BRACE) ?: [] as $file) {
            if (filemtime($file) < $limit) {
                @unlink($file);
            }
        }
    }
}
