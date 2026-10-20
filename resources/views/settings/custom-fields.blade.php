<x-app-layout>
    <x-slot name="header">{{ __('Custom fields') }}</x-slot>

    <div class="form-page max-w-5xl">
        <div class="form-page-head">
            <span class="form-page-icon"><x-icon name="layers" :size="26" /></span>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-extrabold tracking-tight text-text-primary dark:text-white">{{ __('Custom fields') }}</h1>
                <p class="mt-0.5 text-sm text-text-muted">{{ __('Add the details your company tracks that we do not ask for: a permit number, a customer PO, a driver licence class...') }}</p>
            </div>
            <a href="{{ route('settings.business.edit') }}" class="btn-ghost shrink-0"><x-icon name="arrow-left" :size="15" /> {{ __('Business settings') }}</a>
        </div>

        @if (session('success'))<x-alert variant="success" class="mb-4">{{ session('success') }}</x-alert>@endif
        @if ($errors->any())<x-alert variant="error" class="mb-4">{{ $errors->first() }}</x-alert>@endif

        <x-card class="mb-6">
            <h2 class="mb-3 font-bold text-text-primary dark:text-white">{{ __('Add a field') }}</h2>
            <form method="POST" action="{{ route('custom-fields.store') }}" x-data="{ type: 'text' }" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @csrf
                <div>
                    <label class="text-xs font-semibold text-text-muted">{{ __('Add it to') }}</label>
                    <select name="entity" class="mt-1 block w-full border-border text-sm">
                        @foreach (\App\Models\CustomField::ENTITIES as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-text-muted">{{ __('Label') }}</label>
                    <input name="label" required maxlength="80" class="mt-1 block w-full border-border text-sm" placeholder="Customer PO number">
                </div>
                <div>
                    <label class="text-xs font-semibold text-text-muted">{{ __('Type') }}</label>
                    <select name="type" x-model="type" class="mt-1 block w-full border-border text-sm">
                        @foreach (\App\Models\CustomField::TYPES as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div x-show="type === 'select'" x-cloak>
                    <label class="text-xs font-semibold text-text-muted">{{ __('Choices (comma or new line)') }}</label>
                    <input name="options" class="mt-1 block w-full border-border text-sm" placeholder="Class A, Class B, CDL">
                </div>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_required" value="1" class="text-brand-primary focus:ring-brand-primary"> {{ __('Must be filled in') }}</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="show_in_list" value="1" class="text-brand-primary focus:ring-brand-primary"> {{ __('Show as a column in the list') }}</label>
                <div class="sm:col-span-2 lg:col-span-4"><button class="btn-primary"><x-icon name="plus" :size="16" /> {{ __('Add field') }}</button></div>
            </form>
        </x-card>

        @foreach (\App\Models\CustomField::ENTITIES as $entity => $entityLabel)
            <h2 class="mb-2 mt-6 text-sm font-semibold uppercase tracking-wider text-text-muted">{{ $entityLabel }}</h2>
            <x-card :padded="false">
                @forelse ($fields[$entity] ?? [] as $field)
                    <form method="POST" action="{{ route('custom-fields.update', $field) }}" class="flex flex-wrap items-center gap-3 border-b border-border/60 px-4 py-3 last:border-0 dark:border-white/5 {{ $field->is_active ? '' : 'opacity-60' }}">
                        @csrf @method('PUT')
                        <input name="label" value="{{ $field->label }}" required maxlength="80" class="min-w-0 flex-1 border-border text-sm">
                        <span class="w-24 text-xs text-text-muted">{{ \App\Models\CustomField::TYPES[$field->type] }}</span>
                        @if ($field->type === 'select')
                            <input name="options" value="{{ implode(', ', $field->options ?? []) }}" class="w-56 border-border text-sm" title="{{ __('Choices') }}">
                        @endif
                        <label class="flex items-center gap-1 text-xs"><input type="checkbox" name="is_required" value="1" @checked($field->is_required)> {{ __('Required') }}</label>
                        <label class="flex items-center gap-1 text-xs"><input type="checkbox" name="show_in_list" value="1" @checked($field->show_in_list)> {{ __('In list') }}</label>
                        <label class="flex items-center gap-1 text-xs"><input type="checkbox" name="is_active" value="1" @checked($field->is_active)> {{ __('Active') }}</label>
                        <button class="btn-ghost btn-sm"><x-icon name="save" :size="14" /></button>
                        <button type="submit" formaction="{{ route('custom-fields.destroy', $field) }}" name="_method" value="DELETE" class="btn-ghost btn-sm !text-status-cancelled" data-confirm="Remove the field “{{ $field->label }}”?"><x-icon name="trash" :size="14" /></button>
                    </form>
                @empty
                    <p class="px-4 py-4 text-sm text-text-muted">{{ __('No custom fields yet.') }}</p>
                @endforelse
            </x-card>
        @endforeach
    </div>
</x-app-layout>
