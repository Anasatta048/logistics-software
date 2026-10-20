@php($editing = isset($user) && $user)
<x-form-section :title="__('Who is this?')" icon="user" :description="__('Their name and how they sign in.')" :step="1">
    <x-field name="name" :label="__('Full name')" icon="user" required>
        <input id="name" name="name" type="text" value="{{ old('name', $user?->name) }}" required autofocus autocomplete="name">
    </x-field>
    <x-field name="phone" :label="__('Phone')" icon="phone">
        <input id="phone" name="phone" type="text" value="{{ old('phone', $user?->phone) }}" autocomplete="tel">
    </x-field>
    <x-field name="email" class="span-2" :label="__('Email (used to sign in)')" icon="mail" required>
        <input id="email" name="email" type="email" value="{{ old('email', $user?->email) }}" required autocomplete="username">
    </x-field>
    <x-field name="password" :label="__('Password')" icon="lock" :required="! $editing" :hint="$editing ? __('Leave both boxes empty to keep the current password.') : __('At least 8 characters.')">
        <input id="password" name="password" type="password" autocomplete="new-password" @required(! $editing)>
    </x-field>
    <x-field name="password_confirmation" :label="__('Repeat password')" icon="check" :required="! $editing">
        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" @required(! $editing)>
    </x-field>
</x-form-section>

<x-form-section :title="__('What can they do?')" icon="key" :description="__('The role decides what this person can reach.')" :step="2">
    <x-field name="role" :label="__('Role')" icon="shield" required>
        <select id="role" name="role" required>
            @foreach ($roles as $role)
                <option value="{{ $role }}" @selected(old('role', $user?->getRoleNames()->first()) === $role)>{{ $role }}</option>
            @endforeach
        </select>
        <p class="field-hint"><a href="{{ route('roles.index') }}" class="font-semibold text-brand-primary hover:underline">{{ __('Manage roles and their rights') }}</a></p>
    </x-field>
    <x-field name="status" :label="__('Status')" icon="flag" required :hint="__('Inactive people cannot sign in.')">
        <select id="status" name="status" required>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(old('status', $user?->status?->value) === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
    </x-field>
</x-form-section>

@php($allocated = collect(old('companies', $user?->companies->pluck('id')->all() ?? []))->map(fn ($id) => (int) $id)->all())
<div x-data="{ show: @js(old('role', $user?->getRoleNames()->first() ?? $roles->first()) === 'dispatcher') }"
     x-show="show" x-cloak
     x-init="show = document.getElementById('role').value === 'dispatcher'; window.jQuery && window.jQuery('#role').on('change', () => show = document.getElementById('role').value === 'dispatcher')"
     @change.document="if ($event.target.id === 'role') show = $event.target.value === 'dispatcher'">
    <x-form-section :title="__('Companies')" icon="building" :description="__('A dispatcher can only book loads for these companies and pick their drivers.')" :step="3">
        <x-field name="companies" class="span-2" :label="__('Allocated companies')" icon="building" :hint="__('Pick one or more. Only used for dispatchers.')">
            <select id="companies" name="companies[]" multiple data-multi-placeholder="{{ __('Search and pick companies…') }}">
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}" @selected(in_array($company->id, $allocated, true))>{{ $company->name }} ({{ $company->mc_number }})</option>
                @endforeach
            </select>
        </x-field>
    </x-form-section>
</div>

<div x-data="{ type: @js(old('commission_type', $user?->commission_type?->value ?? 'percentage')) }">
    <x-form-section :title="__('Commission')" icon="percent" :description="__('How this person is paid on every dispatch.')" :step="4">
        <x-field name="commission_type" :label="__('Commission type')" icon="percent" required :hint="__('Percentage is a share of what the company owes on the load; fixed is the same every time.')">
            <select id="commission_type" name="commission_type" required x-model="type">
                @foreach ($commissionTypes as $commissionType)
                    <option value="{{ $commissionType->value }}" @selected(old('commission_type', $user?->commission_type?->value ?? 'percentage') === $commissionType->value)>{{ $commissionType->label() }}</option>
                @endforeach
            </select>
        </x-field>
        <x-field name="commission_percentage" x-show="type === 'percentage'" x-cloak :label="__('Commission %')" icon="percent">
            <input id="commission_percentage" name="commission_percentage" type="number" step="0.01" min="0" max="100" value="{{ old('commission_percentage', $user?->commission_percentage) }}">
        </x-field>
        <x-field name="commission_fixed_amount" x-show="type === 'fixed'" x-cloak :label="__('Fixed amount per dispatch (USD)')" icon="dollar" :hint="__('Set in USD, converted to PKR at payout time.')">
            <input id="commission_fixed_amount" name="commission_fixed_amount" type="number" step="0.01" min="0" value="{{ old('commission_fixed_amount', $user?->commission_fixed_amount) }}">
        </x-field>
    </x-form-section>
</div>

<x-form-actions :cancel="route('users.index')" :submit="__('Save user')" />
