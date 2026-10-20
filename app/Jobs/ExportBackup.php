<?php

namespace App\Jobs;

use App\Models\DataExport;
use App\Models\Tenant;
use App\Services\Import\BackupExporter;
use App\Support\Tenancy\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/** Builds one workspace backup off the request, so a big company never waits on a page. */
class ExportBackup implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $timeout = 900;

    public int $tries = 1;

    public function __construct(public int $tenantId, public int $exportId)
    {
    }

    public function handle(TenantContext $context, BackupExporter $exporter): void
    {
        $tenant = Tenant::query()->findOrFail($this->tenantId);

        $context->run($tenant, function () use ($exporter): void {
            $export = DataExport::query()->findOrFail($this->exportId);
            $export->update(['status' => 'running']);

            $relative = "tenants/{$this->tenantId}/exports/".Str::uuid().'.zip';
            $size = $exporter->build(Storage::disk('local')->path($relative));

            $export->update(['status' => 'ready', 'path' => $relative, 'size' => $size]);
        });
    }

    public function failed(Throwable $e): void
    {
        $tenant = Tenant::query()->find($this->tenantId);

        $tenant && app(TenantContext::class)->run($tenant, function () use ($e): void {
            DataExport::query()->whereKey($this->exportId)->update(['status' => 'failed', 'error' => Str::limit($e->getMessage(), 240)]);
        });
    }
}
