@php
    $actionStyles = [
        'created' => 'bg-emerald-600 text-white',
        'updated' => 'bg-blue-600 text-white',
        'deleted' => 'bg-red-600 text-white',
        'restored' => 'bg-emerald-700 text-white',
        'login' => 'bg-brand-navy text-white',
        'logout' => 'bg-brand-navy/70 text-white',
        'login_failed' => 'bg-red-700 text-white',
    ];
    $hasFilters = collect($filters)->except('q')->filter()->isNotEmpty();
@endphp
<x-app-layout>
    <x-slot name="header">{{ __('Activity Log') }}</x-slot>

    <div class="form-anim space-y-5">
        <div class="form-page-head !mb-0 flex-wrap">
            <span class="form-page-icon"><x-icon name="list" :size="26" /></span>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-extrabold tracking-tight text-text-primary dark:text-white">{{ __('Activity log') }}</h1>
                <p class="mt-0.5 text-sm text-text-muted">{{ __('Who did what, in which module, and when — including every sign-in and sign-out.') }}</p>
            </div>
        </div>

        <form method="GET" action="{{ route('activity-logs.index') }}"
            class="grid grid-cols-1 gap-3 border border-border bg-surface p-4 sm:grid-cols-2 lg:grid-cols-[repeat(5,minmax(0,1fr))_auto] lg:items-end dark:border-white/10 dark:bg-surface-dark">
            <div>
                <label for="module" class="block text-xs font-semibold uppercase tracking-wider text-text-muted">{{ __('Module') }}</label>
                <select id="module" name="module" onchange="this.requestSubmit()" class="mt-1 block w-full">
                    <option value="">{{ __('All modules') }}</option>
                    @foreach ($modules as $module)
                        <option value="{{ $module }}" @selected(($filters['module'] ?? null) === $module)>{{ $module }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="action" class="block text-xs font-semibold uppercase tracking-wider text-text-muted">{{ __('Action') }}</label>
                <select id="action" name="action" onchange="this.requestSubmit()" class="mt-1 block w-full">
                    <option value="">{{ __('All actions') }}</option>
                    @foreach ($actions as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['action'] ?? null) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="user_id" class="block text-xs font-semibold uppercase tracking-wider text-text-muted">{{ __('User') }}</label>
                <select id="user_id" name="user_id" onchange="this.requestSubmit()" class="mt-1 block w-full">
                    <option value="">{{ __('Everyone') }}</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected((int) ($filters['user_id'] ?? 0) === $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="from" class="block text-xs font-semibold uppercase tracking-wider text-text-muted">{{ __('From') }}</label>
                <x-text-input id="from" name="from" type="date" class="mt-1 block w-full" onchange="this.form.requestSubmit()" :value="$filters['from'] ?? ''" />
            </div>
            <div>
                <label for="to" class="block text-xs font-semibold uppercase tracking-wider text-text-muted">{{ __('To') }}</label>
                <x-text-input id="to" name="to" type="date" class="mt-1 block w-full" onchange="this.form.requestSubmit()" :value="$filters['to'] ?? ''" />
            </div>
            <div class="flex items-end">
                @if ($hasFilters)
                    <a href="{{ route('activity-logs.index') }}" class="btn-ghost btn-sm inline-flex items-center gap-1">
                        <x-icon name="x" :size="14" /> {{ __('Reset') }}
                    </a>
                @endif
            </div>
        </form>

        <div class="border border-border bg-surface dark:border-white/10 dark:bg-surface-dark">
            @if ($logs->isEmpty())
                <x-empty-state icon="file-text" title="No activity found" description="Nothing matches these filters yet." />
            @else
                <div class="overflow-x-auto">
                    <table data-table class="min-w-full divide-y divide-border text-sm dark:divide-white/10">
                        <thead class="bg-brand-navy/5 text-left text-xs font-semibold uppercase tracking-wider text-text-muted dark:bg-white/5">
                            <tr>
                                <th data-filter="date" class="px-4 py-3">When</th>
                                <th data-filter="select" class="px-4 py-3">User</th>
                                <th data-filter="select" class="px-4 py-3">Module</th>
                                <th class="px-4 py-3">Activity</th>
                                <th class="px-4 py-3">IP</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border dark:divide-white/10">
                            @foreach ($logs as $log)
                                @php $name = $log->user_name ?? __('System / guest'); @endphp
                                <tr class="align-top transition-colors hover:bg-brand-navy/[0.03] dark:hover:bg-white/5" x-data="{ open: false }">
                                    <td class="whitespace-nowrap px-4 py-3 text-xs text-text-primary dark:text-white" data-date="{{ \App\Support\Timezones::toUs($log->created_at)?->toDateString() }}">
                                        <x-datetime :value="$log->created_at" format="M j, Y H:i:s" />
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center {{ $log->user_name ? 'bg-brand-navy text-white' : 'bg-border text-text-muted dark:bg-white/10' }} text-xs font-bold uppercase">{{ mb_substr($name, 0, 1) }}</span>
                                            <span class="font-semibold text-text-primary dark:text-white">{{ $name }}</span>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <span class="inline-flex border border-border px-2 py-0.5 text-xs font-medium text-text-primary dark:border-white/10 dark:text-white">{{ $log->module }}</span>
                                    </td>
                                    <td class="min-w-[18rem] px-4 py-3">
                                        <div class="flex items-start gap-2">
                                            <span class="mt-0.5 inline-flex shrink-0 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide {{ $actionStyles[$log->action] ?? 'bg-border text-text-primary dark:bg-white/10 dark:text-white' }}">
                                                {{ $actions[$log->action] ?? $log->action }}
                                            </span>
                                            <div class="min-w-0 flex-1">
                                                <p class="text-text-primary dark:text-white">{{ $log->description }}</p>
                                                @if ($log->changes)
                                                    <button type="button" x-on:click="open = ! open" class="mt-1 inline-flex items-center gap-1 text-xs font-semibold text-brand-primary hover:underline">
                                                        <span x-text="open ? '▾' : '▸'">▸</span>
                                                        <span x-show="! open">{{ __('Show changes') }} ({{ count($log->changes) }})</span>
                                                        <span x-show="open" x-cloak>{{ __('Hide changes') }}</span>
                                                    </button>
                                                    <div x-show="open" x-cloak x-transition class="mt-2 border-l-2 border-brand-primary bg-bg/60 text-xs dark:bg-white/5">
                                                        @foreach ($log->changes as $change)
                                                            <div class="grid grid-cols-1 gap-1 border-b border-border/60 px-3 py-2 last:border-b-0 sm:grid-cols-[10rem_1fr] dark:border-white/5">
                                                                <span class="font-semibold text-text-muted">{{ $change['field'] }}</span>
                                                                <span class="flex flex-wrap items-center gap-1.5 break-all">
                                                                    @if ($change['old'] !== null && $change['new'] !== null)
                                                                        <span class="bg-red-500/10 px-1.5 py-0.5 text-red-700 line-through dark:text-red-300">{{ $change['old'] }}</span>
                                                                        <span class="text-text-muted">→</span>
                                                                        <span class="bg-emerald-500/10 px-1.5 py-0.5 text-emerald-700 dark:text-emerald-300">{{ $change['new'] }}</span>
                                                                    @else
                                                                        <span class="text-text-primary dark:text-white">{{ $change['new'] ?? $change['old'] }}</span>
                                                                    @endif
                                                                </span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-text-muted" title="{{ $log->user_agent }}">{{ $log->ip_address ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div>{{ $logs->links() }}</div>
    </div>
</x-app-layout>
