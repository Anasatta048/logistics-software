<x-app-layout>
    <x-slot name="header">{{ __('Edit Equipment Type') }}</x-slot>

    <x-form-page :title="__('Edit Equipment Type')" :subtitle="__('Change anything below, then save.')" icon="package" :back="route('equipment-types.index')" :tips="[__('Use one spelling everywhere — loads and drivers store this exact name.')]">
        <form method="POST" action="{{ route('equipment-types.update', $equipmentType) }}">
            @csrf
            @method('PUT')
            @include('equipment-types.partials.form', ['equipmentType' => $equipmentType])
        </form>
    </x-form-page>
</x-app-layout>
