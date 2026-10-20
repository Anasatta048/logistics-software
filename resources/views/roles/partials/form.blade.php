@php
    $labels = config('permission-labels');
    $selectedNow = old('permissions', $selected);
    $moduleOrder = array_keys($labels['modules']);
    $actionOrder = array_keys($labels['actions']);
    $moduleData = $grouped->sortBy(fn ($permissions, $module) => ($i = array_search($module, $moduleOrder, true)) === false ? 99 : $i)->map(fn ($permissions, $module) => [
        'key' => $module,
        'label' => $labels['modules'][$module][0] ?? str($module)->replace('-', ' ')->title()->toString(),
        'icon' => $labels['modules'][$module][1] ?? 'shield',
        'desc' => $labels['modules'][$module][2] ?? '',
        'perms' => $permissions->sortBy(fn ($p) => (($i = array_search(str($p->name)->after('.')->toString(), $actionOrder, true)) === false ? 99 : $i))->map(fn ($p) => [
            'name' => $p->name,
            'label' => $labels['actions'][str($p->name)->after('.')->toString()][0] ?? str($p->name)->after('.')->replace('-', ' ')->title()->toString(),
            'hint' => $labels['actions'][str($p->name)->after('.')->toString()][1] ?? '',
            'view' => str($p->name)->endsWith('.view'),
        ])->values(),
    ])->values();
@endphp

<div class="space-y-5" x-data="roleMatrix(@js($moduleData), @js(array_values($selectedNow)))">
    <x-form-section :title="__('Role name')" icon="key" :description="__('Think of a job title. You hand this role to users on their own form.')" :step="1">
        <x-field name="name" :label="__('Name')" icon="tag" required class="span-2"
            :hint="__('Lower case, dashes instead of spaces — for example: night-dispatcher.')">
            <input id="name" name="name" type="text" value="{{ old('name', $role?->name) }}" required autofocus placeholder="e.g. night-dispatcher">
        </x-field>
    </x-form-section>

    <section class="form-section">
        <header class="form-section-head">
            <span class="form-section-icon"><x-icon name="shield" :size="18" /></span>
            <div class="min-w-0 flex-1">
                <h3 class="form-section-title"><span class="form-section-step">2</span>{{ __('What can this role do?') }}</h3>
                <p class="form-section-desc">{{ __('Tick what is allowed. Anything left unticked is refused — even if the user has the role.') }}</p>
            </div>
            <div class="hidden text-right sm:block">
                <p class="text-2xl font-extrabold leading-none text-brand-primary" x-text="count"></p>
                <p class="text-[11px] text-text-muted">{{ __('of') }} <span x-text="total"></span> {{ __('rights') }}</p>
            </div>
        </header>

        <div class="flex flex-wrap items-center gap-2 border-b border-border px-5 py-3 dark:border-white/10">
            <div class="relative min-w-[12rem] flex-1">
                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-text-muted"><x-icon name="search" :size="15" /></span>
                <input type="search" x-model="q" placeholder="{{ __('Find a module…') }}" class="w-full border-border py-2 pl-9 text-sm focus:border-brand-primary focus:ring-brand-primary dark:border-white/10 dark:bg-transparent">
            </div>
            <button type="button" class="btn-ghost btn-sm" @click="setAll(true)"><x-icon name="check-square" :size="14" /> {{ __('Everything') }}</button>
            <button type="button" class="btn-ghost btn-sm" @click="viewOnly()"><x-icon name="eye" :size="14" /> {{ __('View only') }}</button>
            <button type="button" class="btn-ghost btn-sm" @click="setAll(false)"><x-icon name="x" :size="14" /> {{ __('Clear') }}</button>
        </div>

        <div class="grid gap-px bg-border p-px sm:grid-cols-2 dark:bg-white/10">
            <template x-for="m in visible" :key="m.key">
                <div class="bg-surface p-4 dark:bg-surface-dark" :class="moduleOn(m) ? 'shadow-[inset_3px_0_0_#E8622C]' : ''">
                    <div class="mb-3 flex items-start gap-3">
                        <span class="grid h-9 w-9 shrink-0 place-items-center transition" :class="moduleOn(m) ? 'bg-brand-primary text-white' : 'bg-brand-navy/10 text-brand-navy dark:bg-white/10 dark:text-white'">
                            <svg viewBox="0 0 24 24" class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path :d="icons[m.icon] || icons.shield"/></svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-bold text-text-primary dark:text-white" x-text="m.label"></p>
                            <p class="text-xs text-text-muted" x-text="m.desc"></p>
                        </div>
                        <button type="button" class="text-[11px] font-bold uppercase tracking-wide text-brand-primary hover:underline" @click="toggleModule(m)" x-text="allOn(m) ? '{{ __('None') }}' : '{{ __('All') }}'"></button>
                    </div>
                    <div class="space-y-1">
                        <template x-for="p in m.perms" :key="p.name">
                            <label class="flex cursor-pointer items-center gap-3 px-2 py-1.5 transition hover:bg-bg dark:hover:bg-white/5" :title="p.hint">
                                <input type="checkbox" name="permissions[]" :value="p.name" x-model="picked" class="h-4 w-4 text-brand-primary focus:ring-brand-primary">
                                <span class="flex-1 text-[13px] text-text-primary dark:text-white" x-text="p.label"></span>
                            </label>
                        </template>
                    </div>
                </div>
            </template>
            <p x-show="visible.length === 0" x-cloak class="bg-surface p-8 text-center text-sm text-text-muted sm:col-span-2 dark:bg-surface-dark">{{ __('No module matches that search.') }}</p>
        </div>
        <x-input-error class="m-4" :messages="$errors->get('permissions')" />
    </section>

    <x-form-actions :cancel="route('roles.index')" :submit="__('Save role')" />
</div>
