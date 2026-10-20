@php
    $selectedStatus = old('status', $company?->status?->value ?? \App\Enums\CompanyStatus::Active->value);
@endphp
<x-form-section :title="__('Company')" icon="building" :description="__('The carrier that owns the trucks.')" :step="1">
    <x-field name="name" class="span-2" :label="__('Company name')" icon="building" required>
        <input id="name" name="name" type="text" value="{{ old('name', $company?->name) }}" required autofocus placeholder="e.g. Lone Star Carriers">
    </x-field>
    <x-field name="mc_number" :label="__('MC number')" icon="hash" required :hint="__('The MC number belongs to the carrier, not to an individual driver.')">
        <input id="mc_number" name="mc_number" type="text" value="{{ old('mc_number', $company?->mc_number) }}" required>
    </x-field>
    <x-field name="default_commission_percentage" :label="__('Default commission %')" icon="percent" required :hint="__('What this company owes you on every load. You can still change it per load.')">
        <input id="default_commission_percentage" name="default_commission_percentage" type="number" step="0.01" min="0" max="100" value="{{ old('default_commission_percentage', $company?->default_commission_percentage ?? \App\Support\Settings\TenantSettings::get('finance.default_fee_percentage', 10)) }}" required>
    </x-field>
    <x-field name="address" class="span-2" :label="__('Address (city, state)')" icon="map-pin" :hint="__('Printed in the “Bill to” block of invoices.')">
        <input id="address" name="address" type="text" value="{{ old('address', $company?->address) }}" placeholder="KEMP, TX">
    </x-field>
</x-form-section>

<x-form-section :title="__('Contact')" icon="phone" :description="__('Who to call about this company.')" :step="2">
    <x-field name="contact_person" :label="__('Contact person')" icon="user">
        <input id="contact_person" name="contact_person" type="text" value="{{ old('contact_person', $company?->contact_person) }}">
    </x-field>
    <x-field name="phone" :label="__('Phone')" icon="phone">
        <input id="phone" name="phone" type="text" value="{{ old('phone', $company?->phone) }}">
    </x-field>
    <x-field name="email" class="span-2" :label="__('Email')" icon="mail">
        <input id="email" name="email" type="email" value="{{ old('email', $company?->email) }}">
    </x-field>
</x-form-section>

<x-form-section :title="__('Status & notes')" icon="flag" :step="3">
    <x-field name="status" :label="__('Status')" icon="flag" required>
        <select id="status" name="status" required>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected($selectedStatus === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
    </x-field>
    <x-field name="notes" class="span-2" :label="__('Notes')" icon="note">
        <textarea id="notes" name="notes" rows="3">{{ old('notes', $company?->notes) }}</textarea>
    </x-field>
</x-form-section>

<x-form-actions :cancel="route('companies.index')" :submit="__('Save company')" />
