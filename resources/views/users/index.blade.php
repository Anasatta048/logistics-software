<x-app-layout>
    <x-slot name="header">{{ __('Users') }}</x-slot>

    <div class="space-y-6">
        <div class="form-page-head !mb-0 flex-wrap">
            <span class="form-page-icon"><x-icon name="user" :size="26" /></span>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-extrabold tracking-tight text-text-primary dark:text-white">{{ __('Users') }}</h1>
                <p class="text-sm text-text-muted">{{ __('Everyone with access to the dispatch system.') }}</p>
            </div>
            <x-button variant="primary" as="a" href="{{ route('users.create') }}">
                <x-icon name="plus" :size="16" /> {{ __('Add User') }}
            </x-button>
        </div>

        @if (session('success'))
            <x-alert variant="success">{{ session('success') }}</x-alert>
        @endif

        <x-card :padded="false">
            @if ($users->isEmpty())
                <x-empty-state icon="users" title="No users yet" description="Add your first teammate." />
            @else
                <div class="overflow-x-auto">
                    <table data-table class="min-w-full divide-y divide-border/60 text-sm dark:divide-white/5">
                        <thead class="text-left text-xs uppercase tracking-wider text-text-muted">
                            <tr>
                                <th class="px-4 py-3">Name</th>
                                <th class="px-4 py-3">Email</th>
                                <th class="px-4 py-3">Phone</th>
                                <th data-filter="select" class="px-4 py-3">Role</th>
                                <th class="px-4 py-3">Commission</th>
                                <th data-filter="select" class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/60 dark:divide-white/5">
                            @foreach ($users as $user)
                                <tr class="hover:bg-bg/60 dark:hover:bg-white/5">
                                    <td class="px-4 py-3 font-medium text-text-primary dark:text-white">{{ $user->name }}</td>
                                    <td class="px-4 py-3">{{ $user->email }}</td>
                                    <td class="px-4 py-3">{{ $user->phone ?? '—' }}</td>
                                    <td class="px-4 py-3">{{ $user->getRoleNames()->implode(', ') ?: '—' }}</td>
                                    <td class="px-4 py-3">{{ $user->commissionLabel() }}</td>
                                    <td class="px-4 py-3">
                                        <x-status-badge :status="$user->status" />
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('users.edit', $user) }}" class="text-text-muted hover:text-brand-primary" title="{{ __('Edit') }}">
                                            <x-icon name="pencil" :size="16" />
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>

        <div>{{ $users->links() }}</div>
    </div>
</x-app-layout>
