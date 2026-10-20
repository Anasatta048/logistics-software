<x-app-layout>
    <x-slot name="header">{{ __('Drivers') }}</x-slot>

    <div class="space-y-6">
        <div class="form-page-head !mb-0 flex-wrap">
            <span class="form-page-icon"><x-icon name="users" :size="26" /></span>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-extrabold tracking-tight text-text-primary dark:text-white">{{ __('Drivers') }}</h1>
                <p class="text-sm text-text-muted">{{ __('Every driver on the fleet, with their current status.') }}</p>
            </div>
            @can('create', App\Models\Driver::class)
                <x-button variant="primary" as="a" href="{{ route('drivers.create') }}">
                    <x-icon name="plus" :size="16" /> {{ __('Add Driver') }}
                </x-button>
            @endcan
        </div>

        @if (session('success'))
            <x-alert variant="success">{{ session('success') }}</x-alert>
        @endif

        <x-card :padded="false">
            @if ($drivers->isEmpty())
                <x-empty-state icon="users" title="No drivers found" description="Try a different filter, or add your first driver.">
                    <x-slot name="action">
                        @can('create', App\Models\Driver::class)
                            <x-button variant="primary" as="a" href="{{ route('drivers.create') }}">{{ __('Add Driver') }}</x-button>
                        @endcan
                    </x-slot>
                </x-empty-state>
            @else
                <div class="overflow-x-auto">
                    <table data-table class="min-w-full divide-y divide-border/60 text-sm dark:divide-white/5">
                        <thead class="text-left text-xs uppercase tracking-wider text-text-muted">
                            <tr>
                                <th class="px-4 py-3">Driver</th>
                                <th data-filter="select" class="px-4 py-3">Company</th>
                                <th class="px-4 py-3">Truck / Trailer</th>
                                <th data-filter="select" class="px-4 py-3">Equipment</th>
                                <th class="px-4 py-3">Phone</th>
                                <th data-filter="select" class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/60 dark:divide-white/5">
                            @foreach ($drivers as $driver)
                                <tr class="hover:bg-bg/60 dark:hover:bg-white/5">
                                    <td class="px-4 py-3 font-medium text-text-primary dark:text-white">{{ $driver->name }}</td>
                                    <td class="px-4 py-3">
                                        {{ $driver->company?->name ?? '—' }}
                                        @if ($driver->company?->mc_number)
                                            <br>
                                            <span class="text-text-muted">{{ $driver->company->mc_number }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ $driver->truck_number }}<br>
                                        <span class="text-text-muted">{{ $driver->trailer_number ?? '—' }}</span>
                                    </td>
                                    <td class="px-4 py-3">{{ $driver->equipment_type }}</td>
                                    <td class="px-4 py-3">{{ $driver->phone }}</td>
                                    <td class="px-4 py-3">
                                        <x-status-badge :status="$driver->status" />
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-3">
                                            @can('update', $driver)
                                                <a href="{{ route('drivers.edit', $driver) }}" class="text-text-muted hover:text-brand-primary" title="{{ __('Edit') }}">
                                                    <x-icon name="pencil" :size="16" />
                                                </a>
                                            @endcan
                                            @can('delete', $driver)
                                                <form method="POST" action="{{ route('drivers.destroy', $driver) }}" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-text-muted hover:text-status-cancelled" title="{{ __('Remove') }}"
                                                        data-confirm="Remove this driver?">
                                                        <x-icon name="trash" :size="16" />
                                                    </button>
                                                </form>
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

        <div>{{ $drivers->links() }}</div>
    </div>
</x-app-layout>
