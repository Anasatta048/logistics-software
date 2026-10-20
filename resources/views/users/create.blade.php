<x-app-layout>
    <x-slot name="header">{{ __('Add User') }}</x-slot>

    <x-form-page :title="__('Add User')" :subtitle="__('A person who can sign in to Dreamelect.')" icon="user" :back="route('users.index')" :tips="[__('The role decides what this person can see and do.'), __('Inactive users cannot sign in but their history stays.'), __('Commission is only used for dispatchers.')]">
        <form method="POST" action="{{ route('users.store') }}">
            @csrf
            @include('users.partials.form', ['user' => null])
        </form>
    </x-form-page>
</x-app-layout>
