<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Uploaded paperwork (rate confirmations, BOL/POD, receipts, payment slips) lives
 * on the private disk and is only ever handed out through a route that checks
 * who is asking. Files stored before that change sit on the public disk until
 * `php artisan attachments:privatize` moves them; until then they are still found.
 */
final class AttachmentDisk
{
    public const PRIVATE = 'local';

    private const LEGACY = 'public';

    public static function find(string $path): ?string
    {
        foreach ([self::PRIVATE, self::LEGACY] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return $disk;
            }
        }

        return null;
    }

    public static function download(string $path, string $name): StreamedResponse
    {
        $disk = self::find($path);

        abort_if($disk === null, 404);

        return Storage::disk($disk)->download($path, $name);
    }

    public static function delete(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        foreach ([self::PRIVATE, self::LEGACY] as $disk) {
            Storage::disk($disk)->delete($path);
        }
    }
}
