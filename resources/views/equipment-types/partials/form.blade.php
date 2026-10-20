<x-form-section :title="__('Equipment type')" icon="package" :description="__('This is exactly what a driver and a load will store, so use the same spelling everywhere.')">
    <x-field name="name" class="span-2" :label="__('Name')" icon="package" required>
        <input id="name" name="name" type="text" value="{{ old('name', $equipmentType?->name) }}" required autofocus placeholder="e.g. Reefer">
    </x-field>
</x-form-section>

<x-form-actions :cancel="route('equipment-types.index')" :submit="__('Save equipment type')" />
