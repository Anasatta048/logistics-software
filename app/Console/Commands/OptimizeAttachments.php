<?php

namespace App\Console\Commands;

use App\Services\Support\AttachmentOptimizer;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * One-off clean-up for files uploaded before optimisation existed:
 * re-encodes images to WebP / compresses PDFs and repoints the record.
 */
class OptimizeAttachments extends Command
{
    protected $signature = 'attachments:optimize {--dry-run : Report the savings without changing anything}';

    protected $description = 'Convert existing image attachments to WebP and compress PDFs';

    /** @var array<string, string> table => column */
    private const TARGETS = [
        'load_attachments' => 'file_path',
        'expenses' => 'attachment_path',
        'journal_vouchers' => 'attachment_path',
        'company_payments' => 'attachment_path',
    ];

    public function handle(AttachmentOptimizer $optimizer): int
    {
        // Run attachments:privatize first; this works on the private disk.
        $disk = Storage::disk('local');
        $before = $after = $changed = 0;

        foreach (self::TARGETS as $table => $column) {
            DB::table($table)->whereNotNull($column)->orderBy('id')->each(function ($row) use ($table, $column, $optimizer, $disk, &$before, &$after, &$changed) {
                $path = $row->{$column};

                if (! $disk->exists($path) || str_ends_with($path, '.webp')) {
                    return;
                }

                $size = $disk->size($path);
                $upload = new UploadedFile($disk->path($path), basename($path), $disk->mimeType($path), null, true);
                $new = $optimizer->store($upload, dirname($path));

                if ($new === $path || $disk->size($new) >= $size) {
                    $new !== $path && $disk->delete($new);

                    return;
                }

                $before += $size;
                $after += $disk->size($new);
                $changed++;

                if ($this->option('dry-run')) {
                    $disk->delete($new);

                    return;
                }

                DB::table($table)->where('id', $row->id)->update([$column => $new]);
                $disk->delete($path);
            });
        }

        $this->info(sprintf('%s%d file(s), %s → %s', $this->option('dry-run') ? '[dry run] ' : '', $changed, number_format($before / 1024).' KB', number_format($after / 1024).' KB'));

        return self::SUCCESS;
    }
}
