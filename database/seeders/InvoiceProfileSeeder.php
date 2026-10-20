<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Support\InvoiceProfile;
use Illuminate\Database\Seeder;

/**
 * Seeds the invoice business details (Settings → Invoice). Never overwrites
 * what an admin has already saved, so it is safe to run on every deploy.
 */
class InvoiceProfileSeeder extends Seeder
{
    public function run(): void
    {
        $saved = Setting::read(InvoiceProfile::SETTING);

        Setting::write(InvoiceProfile::SETTING, array_merge(
            InvoiceProfile::defaults(),
            array_filter(is_array($saved) ? $saved : [], fn ($v) => filled($v)),
        ));
    }
}
