<x-app-layout>
    <x-slot name="header">{{ __('Companies') }}</x-slot>

    <div class="space-y-6">
        <div class="form-page-head !mb-0 flex-wrap">
            <span class="form-page-icon"><x-icon name="building" :size="26" /></span>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-extrabold tracking-tight text-text-primary dark:text-white">{{ __('Companies') }}</h1>
                <p class="text-sm text-text-muted">
                    {{ __('The carriers that own the trucks and the MC numbers. They owe you a % of each load.') }}
                </p>
            </div>
            @can('create', App\Models\Company::class)
                <x-button variant="primary" as="a" href="{{ route('companies.create') }}">
                    <x-icon name="plus" :size="16" /> {{ __('Add Company') }}
                </x-button>
            @endcan
        </div>

        @if (session('success'))
            <x-alert variant="success">{{ session('success') }}</x-alert>
        @endif

        <x-card :padded="false">
            @if ($companies->isEmpty())
                <x-empty-state icon="building" title="No companies found"
                    description="Try a different filter, or add your first carrier.">
                    <x-slot name="action">
                        @can('create', App\Models\Company::class)
                            <x-button variant="primary" as="a" href="{{ route('companies.create') }}">{{ __('Add Company') }}</x-button>
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
                                <th class="px-4 py-3">Commission</th>
                                <th class="px-4 py-3">Drivers</th>
                                <th class="px-4 py-3">Contact</th>
                                <th data-filter="select" class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/60 dark:divide-white/5">
                            @foreach ($companies as $company)
                                <tr class="hover:bg-bg/60 dark:hover:bg-white/5">
                                    <td class="px-4 py-3 font-medium text-text-primary dark:text-white">{{ $company->name }}</td>
                                    <td class="px-4 py-3">{{ $company->mc_number }}</td>
                                    <td class="px-4 py-3">{{ $company->default_commission_percentage }}%</td>
                                    <td class="px-4 py-3">{{ $company->drivers_count }}</td>
                                    <td class="px-4 py-3">{{ $company->contact_person ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <x-status-badge :status="$company->status" />
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-3">
                                            @can('viewAny', App\Models\CompanyPayment::class)
                                                <a href="{{ route('companies.ledger', $company) }}" class="text-xs font-medium text-brand-primary hover:underline">{{ __('Ledger') }}</a>
                                            @endcan
                                            @can('update', $company)
                                                <a href="{{ route('companies.edit', $company) }}" class="text-text-muted hover:text-brand-primary" title="{{ __('Edit') }}">
                                                    <x-icon name="pencil" :size="16" />
                                                </a>
                                            @endcan
                                            @can('delete', $company)
                                                <form method="POST" action="{{ route('companies.destroy', $company) }}" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-text-muted hover:text-status-cancelled" title="{{ __('Remove') }}"
                                                        data-confirm="Remove this company? Its drivers and loads stay on record.">
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

        <div>{{ $companies->links() }}</div>
    </div>
</x-app-layout>
