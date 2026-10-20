<x-app-layout>
    <x-slot name="header">{{ __('Add Driver') }}</x-slot>

    <x-form-page :title="__('Add Driver')" :subtitle="__('A driver and the truck they drive. Pick the company first.')" icon="users" :back="route('drivers.index')" :tips="[__('A driver belongs to one company.'), __('Truck number is shown on every load they run.'), __('Dimensions are optional — add them if you match loads by size.')]">
        <form method="POST" action="{{ route('drivers.store') }}">
            @csrf
            @include('drivers.partials.form', ['driver' => null])
        </form>
    </x-form-page>
</x-app-layout>
