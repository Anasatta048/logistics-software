<x-app-layout>
    <x-slot name="header">{{ __('Edit Account') }}</x-slot>

    <x-form-page :title="__('Edit Account')" :subtitle="__('Change anything below, then save.')" icon="layers" :back="route('accounts.index')" :tips="[__('Pick the type carefully — it decides where the account appears in reports.'), __('Opening balance can only be set when the account is created.')]">
        <form method="POST" action="{{ route('accounts.update', $account) }}">
            @csrf
            @method('PUT')
            @include('accounts.partials.form', ['account' => $account])
        </form>
    </x-form-page>
</x-app-layout>
