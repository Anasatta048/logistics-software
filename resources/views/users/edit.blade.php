<x-app-layout>
    <x-slot name="header">{{ __('Edit User') }}</x-slot>

    <x-form-page :title="__('Edit User')" :subtitle="__('Change anything below, then save.')" icon="user" :back="route('users.index')" :tips="[__('The role decides what this person can see and do.'), __('Inactive users cannot sign in but their history stays.'), __('Commission is only used for dispatchers.')]">
        <form method="POST" action="{{ route('users.update', $user) }}">
            @csrf
            @method('PUT')
            @include('users.partials.form', ['user' => $user])
        </form>
    </x-form-page>
</x-app-layout>
