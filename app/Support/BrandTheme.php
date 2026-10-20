<?php

namespace App\Support;

use App\Support\Settings\TenantSettings;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\Storage;

/**
 * The tenant's colours as CSS custom properties. The stylesheet and Tailwind
 * read `--brand-primary-rgb` / `--brand-navy-rgb`, so one inline <style> in
 * the page head re-themes buttons, links, charts and the sidebar.
 */
final class BrandTheme
{
    public static function css(): string
    {
        if (! app(TenantContext::class)->has()) {
            return '';
        }

        $brand = TenantSettings::all('branding');
        $accent = self::hex($brand['accent_color'], '#E8622C');
        $navy = self::hex($brand['sidebar_color'], '#1B2A4A');

        return sprintf(
            ':root{--brand-primary:%s;--brand-primary-rgb:%s;--brand-primary-dark:%s;--brand-navy-rgb:%s}',
            $accent,
            self::rgb($accent),
            self::shade($accent, 0.88),
            self::rgb($navy),
        );
    }

    /** Absolute filesystem path of the logo, for PDFs (dompdf reads files, not URLs). */
    public static function logoPath(): ?string
    {
        $path = TenantSettings::get('branding.logo');

        return $path && Storage::disk('public')->exists($path) ? Storage::disk('public')->path($path) : null;
    }

    /** The logo inlined as a data URI, for PDFs and emails. */
    public static function logoDataUri(): ?string
    {
        $file = self::logoPath();

        if ($file === null) {
            return null;
        }

        $mime = mime_content_type($file) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($file));
    }

    private static function hex(mixed $value, string $fallback): string
    {
        return is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? strtoupper($value) : $fallback;
    }

    private static function rgb(string $hex): string
    {
        [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x');

        return "{$r} {$g} {$b}";
    }

    private static function shade(string $hex, float $factor): string
    {
        [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x');

        return sprintf('#%02x%02x%02x', (int) ($r * $factor), (int) ($g * $factor), (int) ($b * $factor));
    }
}
