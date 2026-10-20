<?php

namespace App\Listeners;

use App\Events\LoadCancelled;
use App\Services\Driver\DriverService;

final readonly class ReleaseDriverOnCancellation
{
    public function __construct(private DriverService $driverService)
    {
    }

    /**
     * The load is already cancelled when this runs, so the freed driver must
     * go back to the pool even if the load was dispatched (SRD 6.3).
     */
    public function handle(LoadCancelled $event): void
    {
        $driver = $event->load->driver;

        if ($driver !== null) {
            $this->driverService->syncStatus($driver);
        }
    }
}
