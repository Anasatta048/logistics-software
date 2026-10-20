<x-app-layout>
    <x-slot name="header">{{ __('Brokers') }}</x-slot>

    <div class="space-y-6">
        <div class="form-page-head !mb-0 flex-wrap">
            <span class="form-page-icon"><x-icon name="briefcase" :size="26" /></span>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-extrabold tracking-tight text-text-primary dark:text-white">{{ __('Brokers') }}</h1>
                <p class="text-sm text-text-muted">{{ __('Every broker your loads are booked through.') }}</p>
            </div>
            @can('create', App\Models\Broker::class)
                <x-button variant="primary" as="a" href="{{ route('brokers.create') }}">
                    <x-icon name="plus" :size="16" /> {{ __('Add Broker') }}
                </x-button>
            @endcan
        </div>

        @if (session('success'))
            <x-alert variant="success">{{ session('success') }}</x-alert>
        @endif

        <x-card :padded="false">
            @if ($brokers->isEmpty())
                <x-empty-state icon="building" title="No brokers found" description="Try a different filter, or add your first broker.">
                    <x-slot name="action">
                        @can('create', App\Models\Broker::class)
                            <x-button variant="primary" as="a" href="{{ route('brokers.create') }}">{{ __('Add Broker') }}</x-button>
                        @endcan
                    </x-slot>
                </x-empty-state>
            @else
                <div class="overflow-x-auto">
                    <table data-table class="min-w-full divide-y divide-border/60 text-sm dark:divide-white/5">
                        <thead class="text-left text-xs uppercase tracking-wider text-text-muted">
                            <tr>
                                <th class="px-4 py-3">Company</th>
                                <th class="px-4 py-3">MC Number</th>
                                <th data-filter="select" class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/60 dark:divide-white/5">
                            @foreach ($brokers as $broker)
                                <tr class="hover:bg-bg/60 dark:hover:bg-white/5">
                                    <td class="px-4 py-3 font-medium text-text-primary dark:text-white">{{ $broker->company_name }}</td>
                                    <td class="px-4 py-3">{{ $broker->broker_mc_number }}</td>
                                    <td class="px-4 py-3">
                                        <x-status-badge :status="$broker->status" />
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-3">
                                            @can('update', $broker)
                                                <a href="{{ route('brokers.edit', $broker) }}" class="text-text-muted hover:text-brand-primary" title="{{ __('Edit') }}">
                                                    <x-icon name="pencil" :size="16" />
                                                </a>
                                            @endcan
                                            @can('delete', $broker)
                                                <form method="POST" action="{{ route('brokers.destroy', $broker) }}" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-text-muted hover:text-status-cancelled" title="{{ __('Remove') }}"
                                                        data-confirm="Remove this broker?">
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

        <div>{{ $brokers->links() }}</div>
    </div>
</x-app-layout>
