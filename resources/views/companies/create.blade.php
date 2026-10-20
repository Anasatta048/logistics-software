<x-app-layout>
    <x-slot name="header">{{ __('Add Company') }}</x-slot>

    <x-form-page :title="__('Add Company')" :subtitle="__('The carrier company that owns the trucks and pays its drivers.')" icon="building" :back="route('companies.index')" :tips="[__('The MC number belongs to the carrier, not to a driver.'), __('Default commission is what the company owes you on each load.'), __('The address prints in the “Bill to” block of invoices.')]">
        <form method="POST" action="{{ route('companies.store') }}">
            @csrf
            @include('companies.partials.form', ['company' => null])
        </form>
    </x-form-page>
</x-app-layout>
