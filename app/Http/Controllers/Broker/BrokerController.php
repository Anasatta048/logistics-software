<?php

namespace App\Http\Controllers\Broker;

use App\Enums\BrokerStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Broker\StoreBrokerRequest;
use App\Http\Requests\Broker\UpdateBrokerRequest;
use App\Http\Resources\Broker\BrokerResource;
use App\Models\Broker;
use App\Services\Broker\BrokerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\View\View;

final class BrokerController extends Controller
{
    public function __construct(private readonly BrokerService $brokerService)
    {
    }

    public function index(Request $request): View
    {
        return view('brokers.index', [
            'brokers' => $this->brokerService->paginate($request->only('q', 'status')),
            'statuses' => BrokerStatus::cases(),
        ]);
    }

    public function create(): View
    {
        return view('brokers.create', ['statuses' => BrokerStatus::cases()]);
    }

    public function store(StoreBrokerRequest $request): RedirectResponse
    {
        $broker = $this->brokerService->create($request->validated());

        return redirect()
            ->route('brokers.index')
            ->with('success', "Broker {$broker->company_name} created.");
    }

    public function edit(Broker $broker): View
    {
        return view('brokers.edit', [
            'broker' => $broker,
            'statuses' => BrokerStatus::cases(),
        ]);
    }

    public function update(UpdateBrokerRequest $request, Broker $broker): RedirectResponse
    {
        $this->brokerService->update($broker, $request->validated());

        return redirect()
            ->route('brokers.index')
            ->with('success', "Broker {$broker->company_name} updated.");
    }

    public function destroy(Broker $broker): RedirectResponse
    {
        $this->brokerService->delete($broker);

        return redirect()
            ->route('brokers.index')
            ->with('success', "Broker {$broker->company_name} removed.");
    }

    public function search(Request $request): AnonymousResourceCollection
    {
        return BrokerResource::collection(
            $this->brokerService->search($request->string('q')->toString())
        );
    }
}
