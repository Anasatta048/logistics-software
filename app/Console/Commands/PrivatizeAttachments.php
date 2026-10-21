<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * One-off, safe to re-run: moves documents uploaded before attachments went private
 * from the public disk (reachable by URL) to the private one (reachable only through
 * an authorized download route).
 */
final class PrivatizeAttachments extends Command
{
    /** table => column holding the path */
    private const TARGETS = [
        'load_attachments' => 'file_path',
        'expenses' => 'attachment_path',
        'journal_vouchers' => 'attachment_path',
        'company_payments' => 'attachment_path',
    ];

    protected $signature = 'attachments:privatize';

    protected $description = 'Move uploaded documents from the public disk to the private disk';

    public function handle(): int
    {
        $public = Storage::disk('public');
        $private = Storage::disk('local');
        $moved = 0;

        foreach (self::TARGETS as $table => $column) {
            DB::table($table)->whereNotNull($column)->orderBy('id')->each(function ($row) use ($column, $public, $private, &$moved): void {
                $path = $row->{$column};

                if (! $public->exists($path)) {
                    return;
                }

                // Copy first, delete the public copy only once the private one is in place.
                if ($private->put($path, $public->readStream($path))) {
                    $public->delete($path);
                    $moved++;
                }
            });
        }

        $this->info("Moved {$moved} file(s) to private storage.");

        return self::SUCCESS;
    }
}
