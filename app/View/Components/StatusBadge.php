<?php

namespace App\View\Components;

use BackedEnum;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\View\Component;

/**
 * Renders an enum's value as a coloured pill, so every status column looks the
 * same without repeating colour maps in each index view. Accepts either the
 * enum itself or a raw value.
 */
final class StatusBadge extends Component
{
    public readonly string $status;

    public function __construct(string|BackedEnum $status)
    {
        $this->status = $status instanceof BackedEnum ? $status->value : $status;
    }

    public function label(): string
    {
        return Str::headline($this->status);
    }

    /**
     * Maps enum values to the status tokens in UI_UX_DESIGN_PLAN.md Section
     * 1.2 — the only place these colours are chosen; every badge everywhere
     * in the app goes through this component instead of picking its own.
     */
    public function classes(): string
    {
        return match ($this->status) {
            'booked' => 'bg-status-booked/10 text-status-booked dark:bg-status-booked/30 dark:text-white',
            'pickup', 'loaded' => 'bg-status-loaded/10 text-status-loaded dark:bg-status-loaded/30 dark:text-white',
            'delivered', 'pending', 'accrued', 'partially_settled' => 'bg-status-dispatched/10 text-status-dispatched dark:bg-status-dispatched/30 dark:text-status-dispatched',
            'active', 'available', 'paid', 'approved', 'fully_settled' => 'bg-status-paid/10 text-status-paid dark:bg-status-paid/30 dark:text-status-paid',
            'cancelled', 'voided', 'reversed', 'inactive' => 'bg-status-cancelled/10 text-status-cancelled dark:bg-status-cancelled/30 dark:text-status-cancelled',
            default => 'bg-text-muted/10 text-text-muted',
        };
    }

    public function render(): View
    {
        return view('components.status-badge');
    }
}
