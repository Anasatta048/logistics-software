@php($v = fn (string $k, $d = null) => old($k, $plan?->{$k} ?? $d))
<div class="space-y-5">
    <x-form-section :title="__('Plan details')" icon="tag" :step="1">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-field name="name" :label="__('Name')" icon="tag" required>
                <input id="name" name="name" type="text" value="{{ $v('name') }}" required placeholder="Growth">
            </x-field>
            <x-field name="slug" :label="__('Slug')" icon="link" :hint="__('Leave empty to generate from the name.')">
                <input id="slug" name="slug" type="text" value="{{ $v('slug') }}" placeholder="growth">
            </x-field>
            <x-field name="tagline" :label="__('Tagline')" icon="note" class="sm:col-span-2">
                <input id="tagline" name="tagline" type="text" value="{{ $v('tagline') }}" placeholder="For growing dispatch teams">
            </x-field>
        </div>
    </x-form-section>

    <x-form-section :title="__('Pricing')" icon="dollar" :step="2">
        <div class="grid gap-4 sm:grid-cols-3">
            <x-field name="monthly_price" :label="__('Monthly price ($)')" icon="dollar" required>
                <input id="monthly_price" name="monthly_price" type="number" step="0.01" min="0" value="{{ $v('monthly_price', 0) }}" required>
            </x-field>
            <x-field name="yearly_price" :label="__('Yearly price ($)')" icon="dollar" required>
                <input id="yearly_price" name="yearly_price" type="number" step="0.01" min="0" value="{{ $v('yearly_price', 0) }}" required>
            </x-field>
            <x-field name="trial_days" :label="__('Free trial (days)')" icon="calendar">
                <input id="trial_days" name="trial_days" type="number" min="0" value="{{ $v('trial_days', 14) }}">
            </x-field>
        </div>
    </x-form-section>

    <x-form-section :title="__('Limits')" icon="layers" :description="__('Leave empty for unlimited.')" :step="3">
        <div class="grid gap-4 sm:grid-cols-3">
            <x-field name="max_users" :label="__('Users')" icon="users"><input id="max_users" name="max_users" type="number" min="1" value="{{ $v('max_users') }}"></x-field>
            <x-field name="max_drivers" :label="__('Drivers')" icon="truck"><input id="max_drivers" name="max_drivers" type="number" min="1" value="{{ $v('max_drivers') }}"></x-field>
            <x-field name="max_loads_per_month" :label="__('Loads per month')" icon="package"><input id="max_loads_per_month" name="max_loads_per_month" type="number" min="1" value="{{ $v('max_loads_per_month') }}"></x-field>
        </div>
    </x-form-section>

    <x-form-section :title="__('Features & display')" icon="sparkles" :step="4">
        <div class="grid gap-4">
            <x-field name="features" :label="__('Features')" :hint="__('One feature per line — shown as a tick list on the landing page.')">
                <textarea id="features" name="features" rows="6">{{ old('features', implode("\n", $plan?->features ?? [])) }}</textarea>
            </x-field>
            <div class="grid gap-4 sm:grid-cols-3">
                <x-field name="sort_order" :label="__('Sort order')" icon="list"><input id="sort_order" name="sort_order" type="number" min="0" value="{{ $v('sort_order', 0) }}"></x-field>
                <label class="flex items-center gap-2 self-end pb-2 text-sm"><input type="checkbox" name="is_featured" value="1" class="text-brand-primary focus:ring-brand-primary" @checked(old('is_featured', $plan?->is_featured ?? false))> {{ __('Highlight as most popular') }}</label>
                <label class="flex items-center gap-2 self-end pb-2 text-sm"><input type="checkbox" name="is_active" value="1" class="text-brand-primary focus:ring-brand-primary" @checked(old('is_active', $plan?->is_active ?? true))> {{ __('Visible on landing page') }}</label>
            </div>
        </div>
    </x-form-section>

    <x-form-actions :cancel="route('super-admin.plans.index')" :submit="$plan ? __('Save plan') : __('Create plan')" />
</div>
