@props(['load'])
@php
    // Booked, Pickup, Loaded and Delivered can be set by hand, in any order;
    // each target still needs the right for that step.
    $options = collect(\App\Enums\LoadStatus::cases())
        ->filter(fn (\App\Enums\LoadStatus $status): bool => $status->isManual()
            && $load->status->canTransitionTo($status)
            && \Illuminate\Support\Facades\Gate::allows('advanceStatus', [$load, $status]));
@endphp
@if ($options->isNotEmpty())
    <form method="POST" action="{{ route('loads.status', $load) }}" class="inline-block">
        @csrf
        <select name="status" aria-label="{{ __('Change status') }}" onchange="if (this.value) this.form.submit()"
            class="rounded-input border-border bg-surface py-1 pl-2 pr-7 text-xs text-text-primary shadow-sm focus:border-brand-primary focus:ring-brand-primary dark:bg-surface-dark dark:border-white/10 dark:text-white">
            <option value="">{{ __('Change status…') }}</option>
            @foreach ($options as $status)
                <option value="{{ $status->value }}">{{ $status->label() }}</option>
            @endforeach
        </select>
    </form>
@endif
