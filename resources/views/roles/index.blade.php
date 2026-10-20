<x-app-layout>
    <x-slot name="header">{{ __('Roles') }}</x-slot>

    <div class="space-y-5">
        <div class="form-page-head !mb-0">
            <span class="form-page-icon"><x-icon name="key" :size="26" /></span>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-extrabold tracking-tight text-text-primary dark:text-white">{{ __('Roles & rights') }}</h1>
                <p class="mt-0.5 text-sm text-text-muted">{{ __('A role is a named set of rights. Give a user a role and that is all they can reach.') }}</p>
            </div>
            @can('roles.create')
                <a href="{{ route('roles.create') }}" class="btn-primary shrink-0"><x-icon name="plus" :size="16" /> {{ __('Add role') }}</a>
            @endcan
        </div>

        @if (session('success'))<x-alert variant="success">{{ session('success') }}</x-alert>@endif
        @if (session('error'))<x-alert variant="error">{{ session('error') }}</x-alert>@endif

        @php($total = \Spatie\Permission\Models\Permission::count())
        <div class="form-anim grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($roles as $role)
                @php($pct = $total ? round($role->permissions->count() / $total * 100) : 0)
                <article class="group relative border border-border bg-surface p-5 transition hover:-translate-y-0.5 hover:border-brand-primary dark:border-white/10 dark:bg-surface-dark">
                    <div class="flex items-start gap-3">
                        <span class="grid h-11 w-11 shrink-0 place-items-center bg-brand-navy text-white transition group-hover:bg-brand-primary"><x-icon name="shield" :size="20" /></span>
                        <div class="min-w-0 flex-1">
                            <h3 class="truncate text-base font-bold capitalize text-text-primary dark:text-white">{{ str($role->name)->replace('-', ' ') }}</h3>
                            <p class="text-xs text-text-muted">
                                <x-icon name="users" :size="12" class="mr-0.5 inline" /> {{ trans_choice(':count user|:count users', $role->users_count) }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="mb-1 flex items-center justify-between text-xs">
                            <span class="text-text-muted">{{ __('Access') }}</span>
                            <span class="font-bold text-text-primary dark:text-white">{{ $role->permissions->count() }} / {{ $total }}</span>
                        </div>
                        <div class="h-1.5 w-full bg-border dark:bg-white/10"><div class="h-full bg-brand-primary transition-all" style="width: {{ $pct }}%"></div></div>
                    </div>

                    @php($modules = $role->permissions->map(fn ($p) => str($p->name)->before('.')->toString())->unique()->values())
                    <div class="mt-4 flex flex-wrap gap-1.5">
                        @forelse ($modules->take(6) as $module)
                            <span class="bg-bg px-2 py-0.5 text-[11px] font-semibold text-text-muted dark:bg-white/5">{{ config("permission-labels.modules.$module.0", str($module)->replace('-', ' ')->title()) }}</span>
                        @empty
                            <span class="text-xs text-text-muted">{{ __('No rights yet') }}</span>
                        @endforelse
                        @if ($modules->count() > 6)
                            <span class="px-1 py-0.5 text-[11px] font-semibold text-brand-primary">+{{ $modules->count() - 6 }}</span>
                        @endif
                    </div>

                    <div class="mt-5 flex items-center gap-2 border-t border-border pt-4 dark:border-white/10">
                        @can('update', $role)
                            <a href="{{ route('roles.edit', $role) }}" class="btn-ghost btn-sm flex-1"><x-icon name="pencil" :size="14" /> {{ __('Edit rights') }}</a>
                        @endcan
                        @if (! $role->isStandard() && $role->users_count === 0)
                            <form method="POST" action="{{ route('roles.destroy', $role) }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-ghost btn-sm !text-status-cancelled" title="{{ __('Remove') }}" data-confirm="Remove the role “{{ $role->name }}”?"><x-icon name="trash" :size="14" /></button>
                            </form>
                        @else
                            <span class="btn-ghost btn-sm cursor-not-allowed opacity-40" title="{{ __('In use — move the users off it first') }}"><x-icon name="trash" :size="14" /></span>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</x-app-layout>
