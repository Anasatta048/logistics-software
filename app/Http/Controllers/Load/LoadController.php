<?php

namespace App\Http\Controllers\Load;

use App\Enums\LoadStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Load\AttachDocumentRequest;
use App\Http\Requests\Load\CalculateDistanceRequest;
use App\Http\Requests\Load\CancelLoadRequest;
use App\Http\Requests\Load\ChangeLoadStatusRequest;
use App\Http\Requests\Load\StoreLoadRequest;
use App\Http\Requests\Load\UpdateLoadRequest;
use App\Models\EquipmentType;
use App\Models\Load;
use App\Models\LoadAttachment;
use App\Services\Load\LoadCancellationService;
use App\Services\Load\LoadService;
use App\Services\Load\LoadStatusService;
use App\Services\Map\GeoapifyClient;
use App\Services\Map\MapService;
use App\Services\Map\StubMapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class LoadController extends Controller
{
    public function __construct(
        private readonly LoadService $loadService,
        private readonly LoadStatusService $statusService,
        private readonly LoadCancellationService $cancellationService,
    ) {
    }

    public function index(Request $request): View
    {
        return view('loads.index', [
            'loads' => $this->loadService->paginate($request->only('q', 'status'), $request->user()),
            'statuses' => LoadStatus::cases(),
        ]);
    }

    public function create(): View
    {
        return view('loads.create', $this->formData());
    }

    public function store(StoreLoadRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $load = $this->loadService->create(Arr::except($data, ['attachments', 'attachment_types']), $request->user());

        $this->loadService->attachDocuments($load, $request->file('attachments', []), $data['attachment_types'] ?? [], $request->user());

        return redirect()
            ->route('loads.index')
            ->with('success', "Load {$load->reference_number} created.");
    }

    public function show(Load $load): View
    {
        return view('loads.show', ['load' => $this->loadService->withHistory($load)]);
    }

    /**
     * Streams a document attached to the load. The file lives on the public disk
     * under a random name, so it is sent under a readable one instead.
     */
    public function downloadAttachment(Load $load, LoadAttachment $attachment): StreamedResponse
    {
        abort_unless($attachment->load_id === $load->id, 404);
        abort_unless(Storage::disk('public')->exists($attachment->file_path), 404);

        $name = str($load->reference_number.' '.$attachment->type->label())->replaceMatches('/[^A-Za-z0-9 \-]+/', '')->trim()
            .'.'.pathinfo($attachment->file_path, PATHINFO_EXTENSION);

        return Storage::disk('public')->download($attachment->file_path, $name);
    }

    public function edit(Load $load): View
    {
        return view('loads.edit', $this->formData(['load' => $this->loadService->withHistory($load)]));
    }

    public function update(UpdateLoadRequest $request, Load $load): RedirectResponse
    {
        $this->loadService->update($load, $request->validated(), $request->user());

        return redirect()
            ->route('loads.index')
            ->with('success', "Load {$load->reference_number} updated.");
    }

    public function changeStatus(ChangeLoadStatusRequest $request, Load $load): RedirectResponse|JsonResponse
    {
        // Route middleware can't see the target status, so the per-step gate
        // (loads.load / loads.paid) lives here.
        $status = LoadStatus::from($request->validated('status'));
        Gate::authorize('advanceStatus', [$load, $status]);

        $this->statusService->changeStatus(
            $load,
            $status,
            $request->user(),
            $request->validated('reason'),
        );

        $message = "Load {$load->reference_number} is now {$load->status->label()}.";

        // The index changes status in place over AJAX and swaps in the fresh controls.
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'status' => $load->status->value,
                'html' => view('loads.partials.status-controls', ['load' => $load->fresh()])->render(),
            ]);
        }

        return redirect()
            ->route('loads.index')
            ->with('success', $message);
    }

    public function cancel(CancelLoadRequest $request, Load $load): RedirectResponse
    {
        $this->cancellationService->cancel(
            $load,
            $request->user(),
            $request->validated('reason'),
            $request->boolean('adjust_settlement'),
        );

        return redirect()
            ->route('loads.index')
            ->with('success', "Load {$load->reference_number} cancelled.");
    }

    public function calculateDistance(CalculateDistanceRequest $request, MapService $mapService, GeoapifyClient $geoapify): JsonResponse
    {
        $coordinates = [
            (float) $request->validated('origin_lat'),
            (float) $request->validated('origin_lng'),
            (float) $request->validated('destination_lat'),
            (float) $request->validated('destination_lng'),
        ];

        $miles = $mapService->calculateDistance(...$coordinates);

        return response()->json([
            'miles' => $miles,
            // The road itself, for the route preview (empty when no routing key is set).
            'path' => $geoapify->routePath(...$coordinates),
            // Lets the form tell a dispatcher when the number is an offline
            // estimate rather than a real driving route.
            'is_estimate' => $mapService instanceof StubMapService,
        ]);
    }

    public function attachDocument(AttachDocumentRequest $request, Load $load): RedirectResponse
    {
        $files = $request->file('attachments');

        $this->loadService->attachDocuments($load, $files, $request->validated('attachment_types'), $request->user());

        return redirect()
            ->route('loads.edit', $load)
            ->with('success', count($files) === 1 ? 'Document attached.' : count($files).' documents attached.');
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function formData(array $extra = []): array
    {
        return [
            ...$extra,
            'equipmentTypes' => EquipmentType::orderBy('name')->get(['id', 'name']),
            'selected' => $this->loadService->selection($extra['load'] ?? null, session()->getOldInput()),
            'dispatchers' => $this->loadService->dispatchers(),
        ];
    }
}
