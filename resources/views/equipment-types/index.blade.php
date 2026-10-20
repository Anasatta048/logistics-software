<x-app-layout>
    <x-slot name="header">{{ __('Equipment Types') }}</x-slot>

    <div class="space-y-6">
        <div class="form-page-head !mb-0 flex-wrap">
            <span class="form-page-icon"><x-icon name="package" :size="26" /></span>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-extrabold tracking-tight text-text-primary dark:text-white">{{ __('Equipment Types') }}</h1>
                <p class="text-sm text-text-muted">
                    {{ __('The list drivers and loads are typed against. Add what your fleet actually runs.') }}
                </p>
            </div>
            @can('create', App\Models\EquipmentType::class)
                <x-button variant="primary" as="a" href="{{ route('equipment-types.create') }}">
                    <x-icon name="plus" :size="16" /> {{ __('Add Type') }}
                </x-button>
            @endcan
        </div>

        @if (session('success'))
            <x-alert variant="success">{{ session('success') }}</x-alert>
        @endif
        @if (session('error'))
            <x-alert variant="danger">{{ session('error') }}</x-alert>
        @endif

        <x-card :padded="false">
            @if ($equipmentTypes->isEmpty())
                <x-empty-state icon="truck" title="No equipment types yet"
                    description="Add one — a driver and a load both need a type before they can be saved.">
                    <x-slot name="action">
                        @can('create', App\Models\EquipmentType::class)
                            <x-button variant="primary" as="a" href="{{ route('equipment-types.create') }}">{{ __('Add Type') }}</x-button>
                        @endcan
                    </x-slot>
                </x-empty-state>
            @else
                <div class="overflow-x-auto">
                    <table data-table class="min-w-full divide-y divide-border/60 text-sm dark:divide-white/5">
                        <thead class="text-left text-xs uppercase tracking-wider text-text-muted">
                            <tr>
                                <th class="px-4 py-3">Name</th>
                                <th class="px-4 py-3 text-right">Drivers</th>
                                <th class="px-4 py-3 text-right">Loads</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/60 dark:divide-white/5">
                            @foreach ($equipmentTypes as $equipmentType)
                                @php $inUse = $equipmentType->drivers_count + $equipmentType->loads_count > 0; @endphp
                                <tr class="hover:bg-bg/60 dark:hover:bg-white/5">
                                    <td class="px-4 py-3 font-medium text-text-primary dark:text-white">{{ $equipmentType->name }}</td>
                                    <td class="px-4 py-3 text-right text-text-muted">{{ $equipmentType->drivers_count }}</td>
                                    <td class="px-4 py-3 text-right text-text-muted">{{ $equipmentType->loads_count }}</td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-3">
                                            @can('update', $equipmentType)
                                                <a href="{{ route('equipment-types.edit', $equipmentType) }}" class="text-text-muted hover:text-brand-primary" title="{{ __('Edit') }}">
                                                    <x-icon name="pencil" :size="16" />
                                                </a>
                                            @endcan
                                            @can('delete', $equipmentType)
                                                @if ($inUse)
                                                    <span class="text-text-muted/50" title="{{ __('Still in use — rename it instead') }}">
                                                        <x-icon name="trash" :size="16" />
                                                    </span>
                                                @else
                                                    <form method="POST" action="{{ route('equipment-types.destroy', $equipmentType) }}" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-text-muted hover:text-status-cancelled" title="{{ __('Remove') }}"
                                                            data-confirm="Remove this equipment type?">
                                                            <x-icon name="trash" :size="16" />
                                                        </button>
                                                    </form>
                                                @endif
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
    </div>
</x-app-layout>
