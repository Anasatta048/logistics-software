<x-app-layout>
    <x-slot name="header">{{ __('Business settings') }}</x-slot>

    <div class="form-page max-w-6xl">
        <div class="form-page-head">
            <span class="form-page-icon"><x-icon name="settings" :size="26" /></span>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-extrabold tracking-tight text-text-primary dark:text-white">{{ __('Business settings') }}</h1>
                <p class="mt-0.5 text-sm text-text-muted">{{ __('Everything that is specific to your company. Changes apply to the whole team.') }}</p>
            </div>
        </div>

        @if (session('success'))<x-alert variant="success" class="mb-4">{{ session('success') }}</x-alert>@endif

        <div class="grid gap-5 lg:grid-cols-[15rem_1fr]">
            <nav class="h-fit space-y-1 border border-border bg-surface p-2 dark:border-white/10 dark:bg-surface-dark">
                @foreach ($groups as $key => $g)
                    <a href="{{ route('settings.business.edit', $key) }}" class="flex items-center gap-2.5 px-3 py-2.5 text-sm font-semibold {{ $key === $group ? 'bg-brand-navy text-white' : 'text-text-muted hover:bg-bg dark:hover:bg-white/5' }}">
                        <x-icon :name="$g['icon']" :size="17" /> {{ __($g['label']) }}
                    </a>
                @endforeach
                <div class="my-2 border-t border-border dark:border-white/10"></div>
                @foreach ([
                    ['expense-categories.index', __('Expense categories'), 'tag', 'expense-categories.view'],
                    ['equipment-types.index', __('Equipment types'), 'truck', 'equipment-types.view'],
                    ['settings.timezones.edit', __('Timezones'), 'clock', 'settings.manage'],
                    ['roles.index', __('Roles & rights'), 'key', 'roles.view'],
                    ['users.index', __('Users'), 'users', 'users.view'],
                ] as [$route, $label, $icon, $perm])
                    @if (Route::has($route) && auth()->user()->can($perm))
                        <a href="{{ route($route) }}" class="flex items-center gap-2.5 px-3 py-2.5 text-sm font-medium text-text-muted hover:bg-bg dark:hover:bg-white/5">
                            <x-icon :name="$icon" :size="17" /> {{ $label }} <x-icon name="chevron-right" :size="14" class="ml-auto" />
                        </a>
                    @endif
                @endforeach
            </nav>

            <form method="POST" action="{{ route('settings.business.update', $group) }}" class="space-y-5">
                @csrf @method('PATCH')
                <x-form-section :title="__($schema['label'])" :icon="$schema['icon']" :description="__($schema['description'])">
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ($schema['fields'] as $name => $field)
                            @php($value = old($name, $values[$name]))
                            @php($wide = in_array($field['type'], ['textarea', 'multiselect'], true))
                            <x-field :name="$name" :label="__($field['label'])" :hint="isset($field['hint']) ? __($field['hint']) : null" :required="$field['required'] ?? false" class="{{ $wide ? 'sm:col-span-2' : '' }}">
                                @switch($field['type'])
                                    @case('textarea')
                                        <textarea id="{{ $name }}" name="{{ $name }}" rows="3" maxlength="{{ $field['max'] ?? 500 }}">{{ $value }}</textarea>
                                        @break
                                    @case('select')
                                        <select id="{{ $name }}" name="{{ $name }}">
                                            @foreach (\App\Support\Settings\TenantSettings::options($field['options']) as $optValue => $optLabel)
                                                <option value="{{ $optValue }}" @selected((string) $value === (string) $optValue)>{{ $optLabel }}</option>
                                            @endforeach
                                        </select>
                                        @break
                                    @case('multiselect')
                                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                            @foreach (\App\Support\Settings\TenantSettings::options($field['options']) as $optValue => $optLabel)
                                                <label class="flex items-center gap-2 border border-border px-3 py-2 text-sm dark:border-white/10">
                                                    <input type="checkbox" name="{{ $name }}[]" value="{{ $optValue }}" class="text-brand-primary focus:ring-brand-primary" @checked(in_array($optValue, (array) $value, true))>
                                                    {{ $optLabel }}
                                                </label>
                                            @endforeach
                                        </div>
                                        @break
                                    @case('color')
                                        <input id="{{ $name }}" name="{{ $name }}" type="color" value="{{ $value }}" class="h-10 w-24 cursor-pointer p-1">
                                        @break
                                    @case('number')
                                        <input id="{{ $name }}" name="{{ $name }}" type="number" value="{{ $value }}" min="{{ $field['min'] ?? 0 }}" max="{{ $field['max'] ?? '' }}" step="{{ $field['step'] ?? 1 }}">
                                        @break
                                    @default
                                        <input id="{{ $name }}" name="{{ $name }}" type="{{ $field['type'] === 'email' ? 'email' : 'text' }}" value="{{ $value }}" maxlength="{{ $field['max'] ?? 255 }}" @required($field['required'] ?? false)>
                                @endswitch
                            </x-field>
                        @endforeach
                    </div>
                </x-form-section>
                <x-form-actions :submit="__('Save changes')" />
            </form>
        </div>
    </div>
</x-app-layout>
