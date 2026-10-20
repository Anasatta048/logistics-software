<x-app-layout>
    <x-slot name="header">{{ __('Edit Broker') }}</x-slot>

    <x-form-page :title="__('Edit Broker')" :subtitle="__('Change anything below, then save.')" icon="briefcase" :back="route('brokers.index')" :tips="[__('Type the MC number exactly — it is how loads find the broker.'), __('Set a broker to Inactive to hide it from the load form without deleting it.'), __('Notes are private to your team.')]">
        <form method="POST" action="{{ route('brokers.update', $broker) }}">
            @csrf
            @method('PUT')
            @include('brokers.partials.form', ['broker' => $broker])
        </form>
    </x-form-page>
</x-app-layout>
