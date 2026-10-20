<?php

namespace App\Http\Controllers\Data;

use App\Http\Controllers\Controller;
use App\Services\Import\ImportDefinitions;
use App\Services\Import\ImportService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Settings -> Import: bring a spreadsheet of old carriers, brokers, drivers or loads in. */
final class ImportController extends Controller
{
    public function __construct(private readonly ImportService $imports)
    {
    }

    public function index(): View
    {
        return view('data.import-index', [
            'entities' => ImportDefinitions::all(),
            'exports' => \App\Models\DataExport::query()->latest()->limit(8)->get(),
        ]);
    }

    public function template(string $entity): StreamedResponse
    {
        ImportDefinitions::get($entity);
        $content = $this->imports->template($entity);

        return response()->streamDownload(fn () => print $content, "{$entity}-template.csv", ['Content-Type' => 'text/csv']);
    }

    public function upload(Request $request, string $entity): RedirectResponse
    {
        ImportDefinitions::get($entity);
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:5120']]);

        $parsed = $this->imports->parse($request->file('file'));
        $token = (string) Str::uuid();

        Storage::disk('local')->put($this->path($token), json_encode($parsed + ['name' => $request->file('file')->getClientOriginalName()]));

        return redirect()->route('data.import.preview', [$entity, $token]);
    }

    public function preview(Request $request, string $entity, string $token): View
    {
        $data = $this->load($token);
        $mapping = $request->has('mapping')
            ? array_map(fn ($v) => $v === '' || $v === null ? null : (int) $v, (array) $request->input('mapping'))
            : $this->imports->suggest($entity, $data['headers']);

        return view('data.import-preview', [
            'entity' => $entity,
            'definition' => ImportDefinitions::get($entity),
            'token' => $token,
            'file' => $data,
            'mapping' => $mapping,
            'check' => $this->imports->run($entity, $data['rows'], $mapping, commit: false),
        ]);
    }

    public function commit(Request $request, string $entity, string $token): View
    {
        $data = $this->load($token);
        $mapping = array_map(fn ($v) => $v === '' || $v === null ? null : (int) $v, (array) $request->input('mapping'));

        foreach (ImportDefinitions::get($entity)['columns'] as $key => [, $required]) {
            abort_if($required && ! isset($mapping[$key]), 422, 'Every required column must be matched to a column in your file.');
        }

        $result = $this->imports->run($entity, $data['rows'], $mapping, commit: true);

        $errorsFile = null;
        if ($result['errors'] !== []) {
            $errorsFile = "tenants/{$this->tenantId()}/imports/{$token}-errors.csv";
            Storage::disk('local')->put($errorsFile, $this->imports->errorsCsv($entity, $result['errors']));
        }

        Storage::disk('local')->delete($this->path($token));

        return view('data.import-result', [
            'entity' => $entity,
            'definition' => ImportDefinitions::get($entity),
            'result' => $result,
            'token' => $errorsFile ? $token : null,
        ]);
    }

    public function errors(string $entity, string $token): StreamedResponse
    {
        abort_unless(Str::isUuid($token), 404);
        $file = "tenants/{$this->tenantId()}/imports/{$token}-errors.csv";
        abort_unless(Storage::disk('local')->exists($file), 404);

        return Storage::disk('local')->download($file, "{$entity}-rejected-rows.csv");
    }

    /** @return array{headers: list<string>, rows: list<list<?string>>, name: string} */
    private function load(string $token): array
    {
        abort_unless(Str::isUuid($token), 404);
        $path = $this->path($token);
        abort_unless(Storage::disk('local')->exists($path), 404, 'That upload has expired. Upload the file again.');

        return json_decode((string) Storage::disk('local')->get($path), true);
    }

    private function path(string $token): string
    {
        return "tenants/{$this->tenantId()}/imports/{$token}.json";
    }

    private function tenantId(): int
    {
        return (int) app(TenantContext::class)->id();
    }
}
