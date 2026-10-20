<x-app-layout>
    <x-slot name="header">{{ __('Loads') }}</x-slot>

    <div class="space-y-6">
        <div class="form-page-head !mb-0 flex-wrap">
            <span class="form-page-icon"><x-icon name="truck" :size="26" /></span>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-extrabold tracking-tight text-text-primary dark:text-white">{{ __('Loads') }}</h1>
                <p class="text-sm text-text-muted">{{ __('Every load from booked to paid, in one board.') }}</p>
            </div>
            @can('create', App\Models\Load::class)
                <x-button variant="primary" as="a" href="{{ route('loads.create') }}">
                    <x-icon name="plus" :size="16" /> {{ __('New Load') }}
                </x-button>
            @endcan
        </div>

        @if (session('success'))
            <x-alert variant="success">{{ session('success') }}</x-alert>
        @endif

        <x-card :padded="false">
            @if ($loads->isEmpty())
                <x-empty-state icon="truck" title="No loads found" description="Try a different filter, or book your first load.">
                    <x-slot name="action">
                        @can('create', App\Models\Load::class)
                            <x-button variant="primary" as="a" href="{{ route('loads.create') }}">{{ __('New Load') }}</x-button>
                        @endcan
                    </x-slot>
                </x-empty-state>
            @else
                <div class="overflow-x-auto">
                    <table data-table class="min-w-full divide-y divide-border/60 text-sm dark:divide-white/5">
                        <thead class="text-left text-xs uppercase tracking-wider text-text-muted">
                            <tr>
                                <th class="px-4 py-3">Reference</th>
                                <th data-filter="select" class="px-4 py-3">Broker</th>
                                <th data-filter="select" class="px-4 py-3">Company</th>
                                <th class="px-4 py-3">Route</th>
                                <th data-filter="select" class="px-4 py-3">Driver</th>
                                <th data-filter="date" class="px-4 py-3">Pickup</th>
                                <th class="px-4 py-3">Miles</th>
                                <th class="px-4 py-3">Rate / mi</th>
                                <th data-filter="select" class="px-4 py-3">Status</th>
                                @foreach (\App\Services\Support\CustomFields::for('load')->where('show_in_list', true) as $cf)
                                    <th class="px-4 py-3">{{ $cf->label }}</th>
                                @endforeach
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/60 dark:divide-white/5">
                            @foreach ($loads as $load)
                                <tr class="hover:bg-bg/60 dark:hover:bg-white/5">
                                    <td class="px-4 py-3 font-medium text-text-primary dark:text-white">{{ $load->reference_number }}</td>
                                    <td class="px-4 py-3">
                                        {{ $load->broker->company_name ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ $load->company?->name ?? '—' }}<br>
                                        <span class="text-text-muted">{{ $load->company?->mc_number }}</span>
                                    </td>
                                    @php($short = fn (?string $a) => \Illuminate\Support\Str::limit(collect(explode(',', (string) $a))->map('trim')->filter()->take(2)->implode(', '), 28))
                                    <td class="max-w-[14rem] px-4 py-3 text-xs" title="{{ $load->origin_address }} → {{ $load->destination_address }}">
                                        <span class="block truncate font-medium">{{ $short($load->origin_address) }}</span>
                                        <span class="block truncate text-text-muted">&rarr; {{ $short($load->destination_address) }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ $load->driverLabel() ?? __('Unassigned') }}<br>
                                        <span class="text-text-muted">
                                            {{ $load->driver?->truck_number ?? ($load->driver_name ? __('Typed in') : '') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap" data-date="{{ \App\Support\Timezones::toUs($load->pickup_at)?->toDateString() }}">
                                        <x-datetime :value="$load->pickup_at" />
                                    </td>
                                    <td class="px-4 py-3">{{ $load->total_miles }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        ${{ number_format((float) $load->rate, 2) }}<br>
                                        <span class="text-text-muted">${{ number_format((float) $load->rate_per_mile, 2) }}/mi</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        @include('loads.partials.status-controls', ['load' => $load])
                                    </td>
                                    @foreach (\App\Services\Support\CustomFields::for('load')->where('show_in_list', true) as $cf)
                                        <td class="px-4 py-3 text-text-muted">{{ \App\Services\Support\CustomFields::display($cf, $load->customValue($cf->key)) }}</td>
                                    @endforeach
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-3">
                                            @can('view', $load)
                                                <a href="{{ route('loads.show', $load) }}" class="text-text-muted hover:text-brand-primary" title="{{ __('View') }}">
                                                    <x-icon name="eye" :size="16" />
                                                </a>
                                            @endcan
                                            @can('update', $load)
                                                <a href="{{ route('loads.edit', $load) }}" class="text-text-muted hover:text-brand-primary" title="{{ __('Edit') }}">
                                                    <x-icon name="pencil" :size="16" />
                                                </a>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>

        <div>{{ $loads->links() }}</div>
    </div>
</x-app-layout>
