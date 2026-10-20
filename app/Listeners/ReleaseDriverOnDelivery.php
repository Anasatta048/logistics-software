<?php

namespace App\Listeners;

use App\Events\LoadPaid;
use App\Services\Driver\DriverService;

final readonly class ReleaseDriverOnDelivery
{
    public function __construct(private DriverService $driverService)
    {
    }

    /**
     * Paid is the delivered end of the happy path, so this is where the driver
     * goes back in the pool. They stay On Load from the moment the load is
     * booked through every step in between — an Inactive driver is left alone,
     * exactly as the cancellation release does.
     */
    public function handle(LoadPaid $event): void
    {
        $driver = $event->load->driver;

        if ($driver !== null) {
            $this->driverService->syncStatus($driver);
        }
    }
}
