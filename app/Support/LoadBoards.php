<?php

namespace App\Support;

use App\Support\Settings\TenantSettings;

/**
 * Shortcuts to the load boards a dispatcher keeps open (DAT, Truckstop, ...).
 * The list is a per-tenant setting, one "Name | https://link" per line; every
 * entry is checked here at read time so a typo or a hostile value can never
 * become a clickable `javascript:` link.
 */
final class LoadBoards
{
    public const DEFAULT = <<<'TXT'
DAT One | https://one.dat.com
Truckstop | https://truckstop.com
123Loadboard | https://www.123loadboard.com
Trucker Path | https://loadboard.truckerpath.com
Direct Freight | https://www.directfreight.com
TXT;

    /** @return list<array{name: string, url: string}> */
    public static function links(?string $raw = null): array
    {
        $raw ??= (string) TenantSettings::get('loadboards.links', self::DEFAULT);
        $links = [];

        foreach (preg_split('/\R/', $raw) ?: [] as $line) {
            [$name, $url] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');

            if ($name === '' || ! self::safe($url)) {
                continue;
            }

            $links[] = ['name' => mb_substr($name, 0, 40), 'url' => $url];
        }

        return array_slice($links, 0, 12);
    }

    /** Only absolute http(s) addresses with a host. */
    public static function safe(string $url): bool
    {
        $parts = parse_url($url);

        return $parts !== false
            && in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
            && ! empty($parts['host'])
            && filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}
