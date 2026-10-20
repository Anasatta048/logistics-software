<x-app-layout>
    <x-slot name="header">{{ __('Add Broker') }}</x-slot>

    <x-form-page :title="__('Add Broker')" :subtitle="__('Brokers send you freight. Save the ones you book loads with.')" icon="briefcase" :back="route('brokers.index')" :tips="[__('Type the MC number exactly — it is how loads find the broker.'), __('Set a broker to Inactive to hide it from the load form without deleting it.'), __('Notes are private to your team.')]">
        <form method="POST" action="{{ route('brokers.store') }}">
            @csrf
            @include('brokers.partials.form', ['broker' => null])
        </form>
    </x-form-page>
</x-app-layout>
