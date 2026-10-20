<x-app-layout>
    <x-slot name="header">{{ __('Add Equipment Type') }}</x-slot>

    <x-form-page :title="__('Add Equipment Type')" :subtitle="__('The kinds of trailer you move freight on, like Reefer or Flatbed.')" icon="package" :back="route('equipment-types.index')" :tips="[__('Use one spelling everywhere — loads and drivers store this exact name.')]">
        <form method="POST" action="{{ route('equipment-types.store') }}">
            @csrf
            @include('equipment-types.partials.form', ['equipmentType' => null])
        </form>
    </x-form-page>
</x-app-layout>
