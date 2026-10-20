<x-app-layout>
    <x-slot name="header">{{ __('Edit Payment') }} #{{ $payment->id }}</x-slot>

    <x-form-page :title="__('Correct payment #:id', ['id' => $payment->id])" :subtitle="$payment->company?->name" icon="credit-card" :back="route('company-payments.show', $payment)" wide>
        <form method="POST" action="{{ route('company-payments.update', $payment) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('company-payments.partials.form', ['payment' => $payment, 'selectedLoads' => $selectedLoads, 'company' => null])
        </form>
    </x-form-page>
</x-app-layout>
