<?php

namespace App\Services\Load;

use App\Enums\LoadStatus;
use App\Events\LoadPaid;
use App\Events\LoadStatusChanged;
use App\Models\Load;
use App\Models\LoadStatusHistory;
use App\Models\User;
use App\Services\Driver\DriverService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class LoadStatusService
{
    public function __construct(private readonly DriverService $drivers)
    {
    }

    /**
     * Walks the happy path one step at a time. Cancellation has its own
     * service because it also records who/why/when and fires an event.
     *
     * @throws ValidationException
     */
    public function changeStatus(Load $load, LoadStatus $newStatus, User $actor, ?string $reason = null): Load
    {
        $from = $load->status;

        if (! $from->canTransitionTo($newStatus)) {
            throw ValidationException::withMessages([
                'status' => "A {$from->label()} load cannot be marked {$newStatus->label()}.",
            ]);
        }

        DB::transaction(function () use ($load, $from, $newStatus, $actor, $reason): void {
            $load->update(['status' => $newStatus]);

            $this->record($load, $from, $newStatus, $actor, $reason);

            // Delivered frees the driver again unless another load is still open.
            if ($load->driver !== null) {
                $this->drivers->syncStatus($load->driver);
            }

            LoadStatusChanged::dispatch($load, $actor, $from, $newStatus);

            if ($newStatus === LoadStatus::Paid) {
                LoadPaid::dispatch($load, $actor);
            }
        });

        return $load->refresh();
    }

    /**
     * The single place status history is written (SRD 6.3: every status change
     * is timestamped and logged against the user who made it).
     */
    public function record(Load $load, ?LoadStatus $from, LoadStatus $to, User $actor, ?string $reason = null): LoadStatusHistory
    {
        return LoadStatusHistory::create([
            'load_id' => $load->id,
            'from_status' => $from,
            'to_status' => $to,
            'changed_by' => $actor->id,
            'reason' => $reason,
        ]);
    }
}
