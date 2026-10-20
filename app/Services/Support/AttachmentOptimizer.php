<?php

namespace App\Services\Support;

use App\Support\Tenancy\TenantContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Every attachment goes through here before it is stored.
 *
 *  - Images (jpg / png / webp / static gif) become WebP, shrunk to at most
 *    {@see MAX_EDGE}px on the long side. A phone photo of a rate confirmation
 *    drops from several MB to a few hundred KB with no visible loss.
 *  - PDFs are re-written with Ghostscript's "ebook" profile when it is
 *    installed (the Docker image has it); the smaller file is kept, the
 *    original when Ghostscript can't improve on it.
 *  - Anything else is stored untouched.
 *
 * It never fails an upload: if optimising throws, the original is stored.
 */
final class AttachmentOptimizer
{
    private const MAX_EDGE = 2200;

    private const WEBP_QUALITY = 80;

    /** @var list<string> */
    private const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/bmp'];

    /**
     * Optimise and store; returns the path on the disk, like {@see UploadedFile::store()}.
     */
    public function store(UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        // Each tenant's files live under their own folder: easy to export,
        // quota or delete as a unit, and a stray path never points at another tenant.
        $tenantId = app(TenantContext::class)->id();
        $directory = $tenantId ? "tenants/{$tenantId}/".trim($directory, '/') : $directory;

        try {
            $mime = (string) $file->getMimeType();

            if (in_array($mime, self::IMAGE_MIMES, true) && ($webp = $this->toWebp($file->getRealPath(), $mime)) !== null) {
                return $this->put($disk, $directory, 'webp', $webp);
            }

            if ($mime === 'application/pdf' && ($pdf = $this->compressPdf($file->getRealPath())) !== null) {
                return $this->put($disk, $directory, 'pdf', $pdf);
            }
        } catch (Throwable $e) {
            report($e);
        }

        return $file->store($directory, $disk);
    }

    private function put(string $disk, string $directory, string $extension, string $contents): string
    {
        $path = trim($directory, '/').'/'.Str::random(40).'.'.$extension;
        Storage::disk($disk)->put($path, $contents);

        return $path;
    }

    /**
     * @return string|null  WebP bytes, or null when the file should be stored as it came
     */
    private function toWebp(string $path, string $mime): ?string
    {
        if (! function_exists('imagewebp') || ! is_readable($path)) {
            return null;
        }

        $bytes = file_get_contents($path);

        // Animated GIFs would lose their motion.
        // A GIF with more than one graphic-control block is animated.
        if ($mime === 'image/gif' && substr_count($bytes, "\x00\x21\xF9\x04") > 1) {
            return null;
        }

        $image = @imagecreatefromstring($bytes);

        if ($image === false) {
            return null;
        }

        $image = $this->upright($image, $mime === 'image/jpeg' ? $path : null);
        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, self::MAX_EDGE / max($width, $height));

        if ($scale < 1) {
            $resized = imagescale($image, max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale)), IMG_BICUBIC);

            if ($resized !== false) {
                $image = $resized;
            }
        }

        imagepalettetotruecolor($image);
        imagealphablending($image, false);
        imagesavealpha($image, true);

        ob_start();
        imagewebp($image, null, self::WEBP_QUALITY);
        $webp = (string) ob_get_clean();

        return $webp !== '' ? $webp : null;
    }

    /**
     * Phones store photos sideways with an EXIF flag; WebP drops the flag, so bake the rotation in.
     */
    private function upright(\GdImage $image, ?string $jpegPath): \GdImage
    {
        if ($jpegPath === null || ! function_exists('exif_read_data')) {
            return $image;
        }

        $orientation = @exif_read_data($jpegPath)['Orientation'] ?? 1;
        $angle = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;

        if ($angle === 0) {
            return $image;
        }

        return imagerotate($image, $angle, 0) ?: $image;
    }

    /**
     * @return string|null  the smaller PDF's bytes, or null when the original should stay
     */
    private function compressPdf(string $path): ?string
    {
        $binary = $this->ghostscript();

        if ($binary === null || ! is_readable($path)) {
            return null;
        }

        $out = tempnam(sys_get_temp_dir(), 'pdfopt');

        try {
            $command = sprintf(
                '%s -q -dNOPAUSE -dBATCH -dSAFER -sDEVICE=pdfwrite -dCompatibilityLevel=1.5 -dPDFSETTINGS=/ebook -dDetectDuplicateImages=true -dCompressFonts=true -sOutputFile=%s %s 2>&1',
                escapeshellarg($binary),
                escapeshellarg($out),
                escapeshellarg($path),
            );

            exec($command, $output, $code);

            if ($code !== 0 || ! is_file($out) || filesize($out) < 100) {
                return null;
            }

            // Keep it only when it is actually smaller (already-lean PDFs can grow).
            return filesize($out) < filesize($path) ? (string) file_get_contents($out) : null;
        } finally {
            @unlink($out);
        }
    }

    private function ghostscript(): ?string
    {
        static $resolved = false;

        if ($resolved !== false) {
            return $resolved;
        }

        $configured = config('services.ghostscript.binary');
        $candidates = array_filter([$configured, 'gs', 'gswin64c', 'gswin32c']);

        foreach ($candidates as $candidate) {
            $probe = PHP_OS_FAMILY === 'Windows' ? 'where' : 'command -v';

            if (is_file($candidate) || trim((string) @shell_exec($probe.' '.escapeshellarg($candidate).' 2>'.(PHP_OS_FAMILY === 'Windows' ? 'nul' : '/dev/null'))) !== '') {
                return $resolved = $candidate;
            }
        }

        return $resolved = null;
    }
}
