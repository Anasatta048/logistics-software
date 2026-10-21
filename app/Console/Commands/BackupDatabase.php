<?php

namespace App\Console\Commands;

use App\Services\Support\DatabaseBackup;
use Illuminate\Console\Command;

/** Nightly backup; `--verify` also restores it into a scratch database to prove it works. */
final class BackupDatabase extends Command
{
    protected $signature = 'backup:run {--verify : Restore the new dump into a scratch database and compare row counts}';

    protected $description = 'Dump the database (and uploaded files) and prune old backups';

    public function handle(DatabaseBackup $backup): int
    {
        $made = $backup->run();
        $this->info('Database: '.basename($made['sql']).' ('.round(filesize($made['sql']) / 1024).' KB)');

        if ($made['files']) {
            $this->info('Files:    '.basename($made['files']).' ('.round(filesize($made['files']) / 1024).' KB)');
        }

        return $this->option('verify') ? $this->verify($backup, $made['sql']) : self::SUCCESS;
    }

    private function verify(DatabaseBackup $backup, string $file): int
    {
        $rows = [];
        $ok = true;

        foreach ($backup->verify($file) as $table => $r) {
            $rows[] = [$table, $r['live'], $r['restored'], $r['ok'] ? 'ok' : 'MISSING'];
            $ok = $ok && $r['ok'];
        }

        $this->table(['Table', 'Live rows', 'Restored rows', 'Result'], $rows);
        $ok ? $this->info('Restore check passed.') : $this->error('Restore check FAILED.');

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
