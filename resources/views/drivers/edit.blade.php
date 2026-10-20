<x-app-layout>
    <x-slot name="header">{{ __('Edit Driver') }}</x-slot>

    <x-form-page :title="__('Edit Driver')" :subtitle="__('Change anything below, then save.')" icon="users" :back="route('drivers.index')" :tips="[__('A driver belongs to one company.'), __('Truck number is shown on every load they run.'), __('Dimensions are optional — add them if you match loads by size.')]">
        <form method="POST" action="{{ route('drivers.update', $driver) }}">
            @csrf
            @method('PUT')
            @include('drivers.partials.form', ['driver' => $driver])
        </form>
    </x-form-page>
</x-app-layout>
