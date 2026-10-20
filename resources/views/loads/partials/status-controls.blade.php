@props(['load'])
@php
    use App\Enums\LoadStatus;
    use Illuminate\Support\Facades\Gate;

    $options = collect(LoadStatus::cases())->filter(fn (LoadStatus $s): bool => $s->isManual()
        && $load->status->canTransitionTo($s)
        && Gate::allows('advanceStatus', [$load, $s]));
    $next = $load->status->next();
    $canNext = $next && ! $next->isManual() && Gate::allows('advanceStatus', [$load, $next]);
    $dots = ['booked' => '#1B2A4A', 'pickup' => '#3A5A8C', 'loaded' => '#3A5A8C', 'delivered' => '#C9972E', 'paid' => '#2E7D32', 'cancelled' => '#C0392B'];
@endphp
{{-- Status badge + change-status dropdown + advance button. Saves over AJAX and swaps itself for the fresh copy. --}}
<div x-data="statusControl(@js(route('loads.status', $load)))" @click.outside="open = false" class="flex flex-wrap items-center gap-2" data-status-cell>
    <x-status-badge :status="$load->status" />

    @if ($options->isNotEmpty())
        <div class="cs" :class="{ 'is-open': open, 'is-busy': busy }">
            <button type="button" class="cs-btn !py-1 text-xs" @click="open = !open" aria-haspopup="listbox">
                <span>{{ __('Change status') }}</span><x-icon name="chevron-down" :size="14" />
            </button>
            <div class="cs-menu" x-show="open" x-cloak x-transition.opacity.duration.100ms role="listbox">
                @foreach ($options as $option)
                    <div class="cs-opt" role="option" @click="set('{{ $option->value }}')" style="--dot: {{ $dots[$option->value] ?? '#9CA3AF' }}">
                        <span class="cs-dot"></span>{{ $option->label() }}
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if ($canNext)
        <button type="button" @click="set('{{ $next->value }}')" :disabled="busy"
            class="btn-ghost btn-sm !border-status-paid/40 !text-status-paid hover:!bg-status-paid/10 disabled:opacity-50">
            <span x-show="!busy">{{ __('Mark :status', ['status' => $next->label()]) }}</span>
            <span x-show="busy" x-cloak>{{ __('Saving…') }}</span>
            <x-icon name="chevron-right" :size="14" />
        </button>
    @endif
</div>
