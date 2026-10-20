@php($isInactive = old('status', $driver?->status?->value) === 'inactive')
<x-form-section :title="__('Driver')" icon="users" :description="__('Who is driving, and for which company.')" :step="1">
    <x-field name="company_id" class="span-2" :label="__('Company')" icon="building" required :hint="__('The company owns the truck and pays its driver — that arrangement stays outside this system.')">
        <select id="company_id" name="company_id" required>
            <option value="">{{ __('Select a company') }}</option>
            @foreach ($companies as $company)
                <option value="{{ $company->id }}" @selected((int) old('company_id', $driver?->company_id) === $company->id)>{{ $company->name }} ({{ $company->mc_number }})</option>
            @endforeach
        </select>
    </x-field>
    <x-field name="name" :label="__('Driver name')" icon="user" required>
        <input id="name" name="name" type="text" value="{{ old('name', $driver?->name) }}" required autofocus>
    </x-field>
    <x-field name="phone" :label="__('Phone')" icon="phone" required>
        <input id="phone" name="phone" type="text" value="{{ old('phone', $driver?->phone) }}" required>
    </x-field>
    <x-field name="email" class="span-2" :label="__('Email')" icon="mail">
        <input id="email" name="email" type="email" value="{{ old('email', $driver?->email) }}">
    </x-field>
</x-form-section>

<x-form-section :title="__('Truck')" icon="truck" :description="__('What they drive. Dimensions are optional.')" :step="2">
    <x-field name="truck_number" :label="__('Truck number')" icon="hash" required>
        <input id="truck_number" name="truck_number" type="text" value="{{ old('truck_number', $driver?->truck_number) }}" required>
    </x-field>
    <x-field name="trailer_number" :label="__('Trailer number')" icon="hash">
        <input id="trailer_number" name="trailer_number" type="text" value="{{ old('trailer_number', $driver?->trailer_number) }}">
    </x-field>
    <x-field name="equipment_type" :label="__('Equipment type')" icon="package" required>
        <select id="equipment_type" name="equipment_type" required>
            @foreach ($equipmentTypes as $equipmentType)
                <option value="{{ $equipmentType->name }}" @selected(old('equipment_type', $driver?->equipment_type) === $equipmentType->name)>{{ $equipmentType->name }}</option>
            @endforeach
        </select>
        @if ((old('_method') || $driver?->equipment_type) && ! $equipmentTypes->contains('name', old('equipment_type', $driver?->equipment_type)))
            <p class="field-hint !text-status-cancelled">{{ __('This driver is on') }} “{{ $driver?->equipment_type }}” {{ __('which is not on the list — pick a type or add it.') }}</p>
        @endif
    </x-field>
    <x-field name="status" :label="__('Status')" icon="flag" required>
        <select id="status" name="status" required>
            <option value="available" @selected(! $isInactive)>{{ __('Active — status follows their loads') }}</option>
            <option value="inactive" @selected($isInactive)>{{ __('Inactive') }}</option>
        </select>
    </x-field>
    @foreach (['truck_length_ft' => ['Length (ft)', 'map'], 'truck_width_ft' => ['Width (ft)', 'map'], 'truck_height_ft' => ['Height (ft)', 'map'], 'truck_max_weight_lbs' => ['Max weight (lbs)', 'package']] as $field => [$label, $icon])
        <x-field :name="$field" :label="__($label)" :icon="$icon">
            <input id="{{ $field }}" name="{{ $field }}" type="number" step="any" min="0" value="{{ old($field, $driver?->{$field}) }}">
        </x-field>
    @endforeach
</x-form-section>

<x-form-section :title="__('Notes')" icon="note" :step="3">
    <x-field name="notes" class="span-2" :label="__('Notes')" icon="note">
        <textarea id="notes" name="notes" rows="3">{{ old('notes', $driver?->notes) }}</textarea>
    </x-field>
</x-form-section>

<x-custom-fields entity="driver" :model="$driver" />

<x-form-actions :cancel="route('drivers.index')" :submit="__('Save driver')" />
