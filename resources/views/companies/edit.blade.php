<x-app-layout>
    <x-slot name="header">{{ __('Edit Company') }}</x-slot>

    <x-form-page :title="__('Edit Company')" :subtitle="__('Change anything below, then save.')" icon="building" :back="route('companies.index')" :tips="[__('The MC number belongs to the carrier, not to a driver.'), __('Default commission is what the company owes you on each load.'), __('The address prints in the “Bill to” block of invoices.')]">
        <form method="POST" action="{{ route('companies.update', $company) }}">
            @csrf
            @method('PUT')
            @include('companies.partials.form', ['company' => $company])
        </form>
    </x-form-page>
</x-app-layout>
