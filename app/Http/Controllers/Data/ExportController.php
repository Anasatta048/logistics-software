<?php

namespace App\Http\Controllers\Data;

use App\Http\Controllers\Controller;
use App\Jobs\ExportBackup;
use App\Models\DataExport;
use App\Models\PlatformAuditLog;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExportController extends Controller
{
    public function store(Request $request, TenantContext $context): RedirectResponse
    {
        // Old backups are removed when a new one is asked for: they hold the whole company.
        DataExport::query()->where('created_at', '<', now()->subDays(7))->get()->each(function (DataExport $old): void {
            $old->path && Storage::disk('local')->delete($old->path);
            $old->delete();
        });

        if (DataExport::query()->whereIn('status', ['queued', 'running'])->exists()) {
            return back()->with('success', 'A backup is already being prepared. It will appear below when ready.');
        }

        $export = DataExport::create(['user_id' => $request->user()->id, 'status' => 'queued']);
        ExportBackup::dispatch((int) $context->id(), $export->id);

        return back()->with('success', 'Preparing your backup. Refresh in a minute and download it from the list.');
    }

    public function download(Request $request, DataExport $dataExport): StreamedResponse
    {
        abort_unless($dataExport->status === 'ready' && $dataExport->path && Storage::disk('local')->exists($dataExport->path), 404);

        PlatformAuditLog::record('backup.downloaded', (int) $dataExport->tenant_id, 'Downloaded a full backup', ['by' => $request->user()->email]);

        return Storage::disk('local')->download($dataExport->path, 'backup-'.$dataExport->created_at->format('Y-m-d').'.zip');
    }
}
