<?php

namespace App\Support;

use App\Support\Settings\TenantSettings;

/**
 * Pushes the tenant's business profile and finance defaults into
 * config('invoice.*'), so the invoice PDF and the invoice forms read them as
 * they always did. Called whenever the tenant context changes (TenantContext).
 */
final class InvoiceProfile
{
    public static function apply(): void
    {
        $business = TenantSettings::all('business');
        $finance = TenantSettings::all('finance');
        $brand = TenantSettings::all('branding');

        config([
            'invoice.company_name' => $business['name'],
            'invoice.email' => (string) $business['email'],
            'invoice.phone' => (string) $business['phone'],
            'invoice.website' => (string) $business['website'],
            'invoice.address' => (string) $business['address'],
            'invoice.tax_id' => (string) $business['tax_id'],
            'invoice.due_days' => (int) $finance['invoice_due_days'],
            'invoice.start_number' => (int) $finance['invoice_start_number'],
            'invoice.brand_color' => (string) $brand['accent_color'],
            'invoice.navy_color' => (string) $brand['sidebar_color'],
            'invoice.template' => (string) $brand['invoice_template'],
            'invoice.prefix' => (string) TenantSettings::get('numbering.invoice_prefix', ''),
            'invoice.note' => (string) $finance['invoice_note'],
        ]);
    }
}
