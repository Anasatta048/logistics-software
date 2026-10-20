<?php

namespace App\Support;

use App\Models\Setting;
use App\Support\Tenancy\TenantContext;

/**
 * The business details printed on every invoice (name, email, phone, website).
 * Stored in the settings table so an admin can change them from Settings →
 * Invoice; config/invoice.php only supplies the first-run defaults.
 */
final class InvoiceProfile
{
    public const SETTING = 'invoice.profile';

    /** @var list<string> */
    public const FIELDS = ['company_name', 'email', 'phone', 'website'];

    /**
     * First-run values: the tenant's own name and contact, not a platform brand.
     *
     * @return array{company_name: string, email: string, phone: string, website: string}
     */
    public static function defaults(): array
    {
        $tenant = app(TenantContext::class)->tenant();

        return [
            'company_name' => $tenant->name ?? config('app.name'),
            'email' => (string) $tenant?->email,
            'phone' => (string) $tenant?->phone,
            'website' => '',
        ];
    }

    /** @return array{company_name: string, email: string, phone: string, website: string} */
    public static function get(): array
    {
        $saved = Setting::read(self::SETTING);

        return array_merge(self::defaults(), array_filter(is_array($saved) ? $saved : [], fn ($v) => filled($v)));
    }

    /** Push the saved profile into config('invoice.*') so the PDF and views read it as before. */
    public static function apply(): void
    {
        foreach (self::get() as $key => $value) {
            config(["invoice.$key" => $value]);
        }
    }
}
