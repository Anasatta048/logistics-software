<x-app-layout>
    <x-slot name="header">{{ __('Record Company Payment') }}</x-slot>

    <x-form-page :title="__('Record a payment')" :subtitle="__('Enter what arrived in USD. Part payments and advances are both fine — the company ledger keeps track.')" icon="credit-card" :back="route('company-payments.index')" wide>
        <form method="POST" action="{{ route('company-payments.store') }}" enctype="multipart/form-data">
            @csrf
            @include('company-payments.partials.form', ['payment' => null, 'selectedLoads' => null, 'company' => $company ?? null])
        </form>
    </x-form-page>
</x-app-layout>
