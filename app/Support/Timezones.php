<?php

namespace App\Support;

use App\Models\Setting;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Dispatch runs on US clocks only. Everything is stored in UTC; people type and
 * read the first configured zone (the entry zone) and every other configured
 * zone is shown next to it automatically, so nobody converts by hand. A load's
 * pickup and dropoff also remember the US zone they were typed in.
 *
 * The zone list is edited by an admin under Settings → Timezones and kept in the
 * settings table. Until one is saved, the zone in config/app.php applies.
 */
final class Timezones
{
    public const SETTING = 'timezones';

    /** @var list<array{label: string, timezone: string}>|null */
    private static ?array $zones = null;

    /**
     * Every configured zone, entry zone first.
     *
     * @return list<array{label: string, timezone: string}>
     */
    public static function zones(): array
    {
        if (self::$zones !== null) {
            return self::$zones;
        }

        $valid = self::usIdentifiers();
        $stored = Setting::read(self::SETTING);

        $zones = collect(is_array($stored) ? $stored : [])
            ->filter(fn ($zone): bool => is_array($zone)
                && filled($zone['label'] ?? null)
                && in_array($zone['timezone'] ?? null, $valid, true))
            ->map(fn (array $zone): array => ['label' => (string) $zone['label'], 'timezone' => (string) $zone['timezone']])
            ->values()
            ->all();

        return self::$zones = $zones !== [] ? $zones : self::defaults();
    }

    /**
     * @return list<array{label: string, timezone: string}>
     */
    public static function defaults(): array
    {
        return [
            ['label' => (string) config('app.us_timezone_label'), 'timezone' => (string) config('app.us_timezone')],
        ];
    }

    /**
     * Zones shown next to the entry zone.
     *
     * @return list<array{label: string, timezone: string}>
     */
    public static function others(): array
    {
        return array_slice(self::zones(), 1);
    }

    /** Forget the per-request cache after the settings change. */
    public static function flush(): void
    {
        self::$zones = null;
    }

    /** The entry zone: what people type and the first one displayed. */
    public static function us(): string
    {
        return self::zones()[0]['timezone'];
    }

    public static function usLabel(): string
    {
        return self::zones()[0]['label'];
    }

    /**
     * The second zone, used where a single extra column is wanted (CSV export).
     */
    public static function pk(): string
    {
        return (self::zones()[1] ?? self::zones()[0])['timezone'];
    }

    public static function pkLabel(): string
    {
        return (self::zones()[1] ?? self::zones()[0])['label'];
    }

    public static function toUs(?CarbonInterface $time): ?Carbon
    {
        return $time === null ? null : Carbon::instance($time)->setTimezone(self::us());
    }

    public static function toPk(?CarbonInterface $time): ?Carbon
    {
        return $time === null ? null : Carbon::instance($time)->setTimezone(self::pk());
    }

    public static function to(?CarbonInterface $time, string $timezone): ?Carbon
    {
        return $time === null ? null : Carbon::instance($time)->setTimezone($timezone);
    }

    /**
     * Friendly names for the US zones people actually dispatch in; any other
     * US identifier is listed under its city.
     */
    private const US_NAMES = [
        'America/New_York' => ['Eastern', 'ET'],
        'America/Chicago' => ['Central', 'CT'],
        'America/Denver' => ['Mountain', 'MT'],
        'America/Phoenix' => ['Arizona (no DST)', 'MST'],
        'America/Los_Angeles' => ['Pacific', 'PT'],
        'America/Anchorage' => ['Alaska', 'AKT'],
        'Pacific/Honolulu' => ['Hawaii', 'HST'],
    ];

    /**
     * Every US timezone identifier (the only zones the app offers).
     *
     * @return list<string>
     */
    public static function usIdentifiers(): array
    {
        return \DateTimeZone::listIdentifiers(\DateTimeZone::PER_COUNTRY, 'US');
    }

    /**
     * US zones for a dropdown, the common ones first: identifier => "Eastern (America/New_York)".
     *
     * @return array<string, string>
     */
    public static function usOptions(): array
    {
        $options = [];

        foreach (array_keys(self::US_NAMES) as $id) {
            $options[$id] = self::US_NAMES[$id][0].' ('.$id.')';
        }

        foreach (self::usIdentifiers() as $id) {
            $options[$id] ??= str_replace('_', ' ', substr($id, (int) strrpos($id, '/') + 1)).' ('.$id.')';
        }

        return $options;
    }

    /** "ET" for America/New_York, or the zone's own abbreviation when it has no short name above. */
    public static function shortLabel(string $timezone): string
    {
        return self::US_NAMES[$timezone][1] ?? Carbon::now($timezone)->format('T');
    }

    /** True for a zone the app offers. */
    public static function isUs(?string $timezone): bool
    {
        return $timezone !== null && in_array($timezone, self::usIdentifiers(), true);
    }

    /**
     * A date-time typed into a form in the given US zone, as the UTC instant to store.
     * Falls back to the entry zone for a missing/unknown zone.
     */
    public static function parseIn(string $value, ?string $timezone): Carbon
    {
        return Carbon::parse($value, self::isUs($timezone) ? $timezone : self::us())->utc();
    }

    /**
     * A date-time typed into a form is entry-zone time; this is the UTC instant to store.
     */
    public static function parseUs(string $value): Carbon
    {
        return Carbon::parse($value, self::us())->utc();
    }

    /**
     * What a datetime-local input should show for a stored instant.
     */
    public static function inputValue(?CarbonInterface $time, ?string $timezone = null): ?string
    {
        return $time === null
            ? null
            : Carbon::instance($time)->setTimezone(self::isUs($timezone) ? $timezone : self::us())->format('Y-m-d\TH:i');
    }

    /**
     * "Today" as the entry zone sees it, for date inputs that default to today.
     */
    public static function today(): string
    {
        return now(self::us())->toDateString();
    }
}
