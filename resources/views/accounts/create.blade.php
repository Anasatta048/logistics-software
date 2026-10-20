<x-app-layout>
    <x-slot name="header">{{ __('Add Account') }}</x-slot>

    <x-form-page :title="__('Add Account')" :subtitle="__('An account in your books: cash, bank, income, expense…')" icon="layers" :back="route('accounts.index')" :tips="[__('Pick the type carefully — it decides where the account appears in reports.'), __('Opening balance can only be set when the account is created.')]">
        <form method="POST" action="{{ route('accounts.store') }}">
            @csrf
            @include('accounts.partials.form', ['account' => null])
        </form>
    </x-form-page>
</x-app-layout>
