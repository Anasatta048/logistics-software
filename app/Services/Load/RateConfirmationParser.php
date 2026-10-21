<?php

namespace App\Services\Load;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser;
use Throwable;

/**
 * Reads the text of a broker's rate confirmation PDF and guesses the load
 * fields from it. Rate confirmations have no standard layout, so this is a set
 * of forgiving heuristics: every value is only a suggestion the dispatcher
 * checks before saving, and anything not found is simply left out. It reads
 * the PDF's own text; a scanned (picture-only) PDF has none and yields nothing.
 */
final class RateConfirmationParser
{
    private const EQUIPMENT = [
        'Dry Van' => ['dry van', 'van'],
        'Reefer' => ['reefer', 'refrigerated'],
        'Flatbed' => ['flatbed', 'flat bed'],
        'Step Deck' => ['step deck', 'stepdeck'],
        'Power Only' => ['power only'],
        'Box Truck' => ['box truck', 'straight truck'],
    ];

    private const PICKUP_WORDS = 'pick[\s-]?up|shipper|origin|pu\b|load at';

    private const DROP_WORDS = 'deliver(?:y)?|consignee|destination|drop[\s-]?off|\bdel\b|unload at';

    /**
     * @return array{text_found: bool, fields: array<string, string>, broker: array{name: ?string, mc: ?string}}
     */
    public function fromFile(UploadedFile $file): array
    {
        try {
            $text = (new Parser())->parseFile($file->getRealPath())->getText();
        } catch (Throwable) {
            $text = '';
        }

        return $this->parse($text);
    }

    /**
     * @return array{text_found: bool, fields: array<string, string>, broker: array{name: ?string, mc: ?string}}
     */
    public function parse(string $text): array
    {
        $text = str_replace(["\r\n", "\r", "\u{00A0}"], ["\n", "\n", ' '], $text);
        $flat = preg_replace('/[ \t]+/', ' ', $text);

        if (mb_strlen(trim($flat)) < 40) {
            return ['text_found' => false, 'fields' => [], 'broker' => ['name' => null, 'mc' => null]];
        }

        $fields = array_filter([
            'reference_number' => $this->reference($flat),
            'rate' => $this->rate($flat),
            'weight_lbs' => $this->weight($flat),
            'total_miles' => $this->miles($flat),
            'broker_email' => $this->email($flat),
            'broker_phone' => $this->phone($flat),
            'broker_contact_person' => $this->contact($flat),
            'equipment_type' => $this->equipment($flat),
            'origin_address' => $this->address($flat, self::PICKUP_WORDS),
            'destination_address' => $this->address($flat, self::DROP_WORDS),
            'pickup_at' => $this->when($flat, self::PICKUP_WORDS),
            'dropoff_at' => $this->when($flat, self::DROP_WORDS),
        ], fn ($value): bool => $value !== null && $value !== '');

        return [
            'text_found' => true,
            'fields' => $fields,
            'broker' => ['name' => $this->brokerName($flat), 'mc' => $this->mc($flat)],
        ];
    }

    private function reference(string $text): ?string
    {
        // Separators stay on one line, and the first candidate holding a digit wins ("Load" alone is not a number).
        if (preg_match_all('/\b(?:load|order|shipment|reference|ref|confirmation|pro|bol)[ ]*(?:#|no\.?|number|num|id)?[ ]*[:#]?[ ]*([A-Z0-9][A-Z0-9-]{3,19})\b/i', $text, $matches)) {
            foreach ($matches[1] as $candidate) {
                if (preg_match('/\d/', $candidate) && ! preg_match('/^\d{1,2}-\d{1,2}-\d{2,4}$/', $candidate)) {
                    return strtoupper($candidate);
                }
            }
        }

        return null;
    }

    private function rate(string $text): ?string
    {
        $labelled = [];

        if (preg_match_all('/(total(?:\s+(?:carrier\s+)?(?:pay|rate|amount|due|charges?))?|carrier\s+(?:pay|rate)|grand\s+total|line\s*haul|flat\s+rate|rate)[^\d$\n]{0,30}\$?\s*([\d]{1,3}(?:,\d{3})*(?:\.\d{2})|\d{3,6}(?:\.\d{2})?)/i', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $labelled[] = ['label' => strtolower($m[1]), 'value' => (float) str_replace(',', '', $m[2])];
            }
        }

        $labelled = array_filter($labelled, fn (array $r): bool => $r['value'] >= 50 && $r['value'] <= 250000);

        if ($labelled === []) {
            return null;
        }

        // A "total" line beats a bare "rate" or a line-haul component.
        $totals = array_filter($labelled, fn (array $r): bool => str_contains($r['label'], 'total') || str_contains($r['label'], 'carrier'));
        $pool = $totals ?: $labelled;

        return number_format(max(array_column($pool, 'value')), 2, '.', '');
    }

    private function weight(string $text): ?string
    {
        if (preg_match('/(\d{1,3}(?:,\d{3})+|\d{3,6})\s*(?:lbs?\.?|pounds)\b/i', $text, $m)) {
            $value = (int) str_replace(',', '', $m[1]);

            return $value >= 100 && $value <= 200000 ? (string) $value : null;
        }

        return null;
    }

    private function miles(string $text): ?string
    {
        if (preg_match('/(\d{1,3}(?:,\d{3})+|\d{2,5})(?:\.\d)?\s*(?:mi\b|miles)/i', $text, $m)) {
            return (string) (int) str_replace(',', '', $m[1]);
        }

        return null;
    }

    private function email(string $text): ?string
    {
        return preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $text, $m) ? strtolower($m[0]) : null;
    }

    private function phone(string $text): ?string
    {
        // Prefer a number next to a "phone / tel / contact" label, otherwise the first one.
        $pattern = '(?:\+?1[\s.-]?)?\(?\d{3}\)?[\s.-]?\d{3}[\s.-]?\d{4}';

        if (preg_match('/(?:phone|tel|ph|contact|dispatch)[^\d\n]{0,12}('.$pattern.')/i', $text, $m) || preg_match('/('.$pattern.')/', $text, $m)) {
            $digits = preg_replace('/\D/', '', $m[1]);
            $digits = strlen($digits) === 11 ? substr($digits, 1) : $digits;

            return strlen($digits) === 10 ? sprintf('(%s) %s-%s', substr($digits, 0, 3), substr($digits, 3, 3), substr($digits, 6)) : null;
        }

        return null;
    }

    private function contact(string $text): ?string
    {
        if (preg_match('/(?i:contact|dispatcher|agent|carrier rep(?:resentative)?|broker rep(?:resentative)?)\s*(?i:name)?\s*[:\-]\s*([A-Z][a-z]+(?:[ ]+[A-Z][a-z.\']+){1,2})/', $text, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    private function equipment(string $text): ?string
    {
        $lower = strtolower($text);

        // Longest keyword first so "step deck" is not read as "deck", "power only" not as "van".
        $best = null;

        foreach (self::EQUIPMENT as $name => $keywords) {
            foreach ($keywords as $keyword) {
                if (preg_match('/\b'.preg_quote($keyword, '/').'\b/', $lower) && ($best === null || strlen($keyword) > $best[1])) {
                    $best = [$name, strlen($keyword)];
                }
            }
        }

        return $best[0] ?? null;
    }

    private function mc(string $text): ?string
    {
        return preg_match('/\bMC\s*(?:#|no\.?|number)?\s*[:#]?\s*(\d{4,8})\b/i', $text, $m) ? $m[1] : null;
    }

    private function brokerName(string $text): ?string
    {
        if (preg_match('/^\s*([A-Z][A-Za-z0-9&.,\' -]{2,50}(?:Logistics|Freight|Transport(?:ation)?|Brokerage|Express|Carriers|Global|Lines))\b/m', $text, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    /** "street, City, ST 12345" from the block that follows a pickup or delivery heading. */
    private function address(string $text, string $words): ?string
    {
        $block = $this->block($text, $words);

        if ($block === null) {
            return null;
        }

        if (! preg_match('/([A-Za-z][A-Za-z .\'-]{1,30}),?\s+([A-Z]{2})\s+(\d{5})(?:-\d{4})?/', $block, $city, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        $cityPart = trim($city[1][0]).', '.$city[2][0].' '.$city[3][0];
        $before = substr($block, 0, $city[0][1]);

        // The street line is the last line before the city that starts with a house number.
        $street = null;

        foreach (array_reverse(preg_split('/\n/', $before)) as $line) {
            if (preg_match('/^\s*(\d{1,6}\s+[A-Za-z0-9 .#\'-]{3,50})\s*,?\s*$/', $line, $m)) {
                $street = trim($m[1]);
                break;
            }
        }

        return $street ? "{$street}, {$cityPart}" : $cityPart;
    }

    /** The first date (and time) after a pickup or delivery heading, as a datetime-local value. */
    private function when(string $text, string $words): ?string
    {
        $block = $this->block($text, $words);

        if ($block === null) {
            return null;
        }

        $date = null;

        if (preg_match('#\b(\d{1,2})/(\d{1,2})/(\d{2,4})\b#', $block, $m)) {
            $year = strlen($m[3]) === 2 ? '20'.$m[3] : $m[3];
            $date = [(int) $year, (int) $m[1], (int) $m[2]];
        } elseif (preg_match('/\b(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*\.?\s+(\d{1,2})(?:st|nd|rd|th)?,?\s+(\d{4})\b/i', $block, $m)) {
            $month = (int) date('n', strtotime("1 {$m[1]} 2000"));
            $date = [(int) $m[3], $month, (int) $m[2]];
        }

        if ($date === null || ! checkdate($date[1], $date[2], $date[0])) {
            return null;
        }

        $time = '09:00';

        if (preg_match('/\b(\d{1,2}):(\d{2})\s*([AP]M)?\b/i', $block, $t)) {
            $hour = (int) $t[1];

            if (isset($t[3]) && $t[3] !== '') {
                $hour = $hour % 12 + (strtoupper($t[3]) === 'PM' ? 12 : 0);
            }

            $time = sprintf('%02d:%s', min($hour, 23), $t[2]);
        }

        return Carbon::create($date[0], $date[1], $date[2])->format('Y-m-d').'T'.$time;
    }

    /** The ~260 characters after the first heading matching $words, or null. */
    private function block(string $text, string $words): ?string
    {
        // Headings sit at the start of a line, optionally numbered ("Stop 1: Pickup").
        if (preg_match('/^\s*(?:stop\s*\d*\s*[:\-]?\s*)?(?:'.$words.')[^\n]{0,30}\n?/imu', $text, $m, PREG_OFFSET_CAPTURE)) {
            return Str::substr($text, mb_strlen(substr($text, 0, $m[0][1])), 260);
        }

        return null;
    }
}
