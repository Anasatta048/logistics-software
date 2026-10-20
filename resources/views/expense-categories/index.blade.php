<x-app-layout>
    <x-slot name="header">{{ __('Expense categories') }}</x-slot>

    <div class="form-page max-w-4xl">
        <div class="form-page-head">
            <span class="form-page-icon"><x-icon name="tag" :size="26" /></span>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-extrabold tracking-tight text-text-primary dark:text-white">{{ __('Expense categories') }}</h1>
                <p class="mt-0.5 text-sm text-text-muted">{{ __('The headings expenses are filed under. Reports group by these, so keep them few and clear.') }}</p>
            </div>
            @can('manage-settings')
                <a href="{{ route('settings.business.edit') }}" class="btn-ghost shrink-0"><x-icon name="arrow-left" :size="15" /> {{ __('Business settings') }}</a>
            @endcan
        </div>

        @if (session('success'))<x-alert variant="success" class="mb-4">{{ session('success') }}</x-alert>@endif
        @if (session('error'))<x-alert variant="error" class="mb-4">{{ session('error') }}</x-alert>@endif
        @if ($errors->any())<x-alert variant="error" class="mb-4">{{ $errors->first() }}</x-alert>@endif

        @can('expense-categories.create')
            <form method="POST" action="{{ route('expense-categories.store') }}" class="mb-5 flex gap-2">
                @csrf
                <input type="text" name="name" required maxlength="80" placeholder="{{ __('New category, e.g. Office tea') }}" class="flex-1 border-border text-sm focus:border-brand-primary focus:ring-brand-primary">
                <button class="btn-primary"><x-icon name="plus" :size="16" /> {{ __('Add') }}</button>
            </form>
        @endcan

        <x-card :padded="false">
            @foreach ($categories as $category)
                <form method="POST" action="{{ route('expense-categories.update', $category) }}" class="flex flex-wrap items-center gap-3 border-b border-border/60 px-4 py-3 last:border-0 dark:border-white/5">
                    @csrf @method('PUT')
                    <input type="text" name="name" value="{{ $category->name }}" required maxlength="80" class="min-w-0 flex-1 border-border text-sm focus:border-brand-primary focus:ring-brand-primary" @cannot('expense-categories.edit') disabled @endcannot>
                    <span class="w-24 text-xs text-text-muted">{{ trans_choice(':count expense|:count expenses', $category->expenses_count) }}</span>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="is_active" value="1" class="text-brand-primary focus:ring-brand-primary" @checked($category->is_active) @cannot('expense-categories.edit') disabled @endcannot> {{ __('Active') }}
                    </label>
                    @can('expense-categories.edit')<button class="btn-ghost btn-sm"><x-icon name="save" :size="14" /></button>@endcan
                    @can('expense-categories.delete')
                        <button type="submit" formaction="{{ route('expense-categories.destroy', $category) }}" formmethod="POST" name="_method" value="DELETE" class="btn-ghost btn-sm !text-status-cancelled" data-confirm="Remove “{{ $category->name }}”?"><x-icon name="trash" :size="14" /></button>
                    @endcan
                </form>
            @endforeach
        </x-card>
    </div>
</x-app-layout>
