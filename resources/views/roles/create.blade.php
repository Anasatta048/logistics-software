<x-app-layout>
    <x-slot name="header">{{ __('Add Role') }}</x-slot>

    <x-form-page :title="__('Create a role')" :subtitle="__('A role decides what a user can see and do.')" icon="key" :back="route('roles.index')" :back-label="__('All roles')" wide
        :tips="[__('Name the role like a job: dispatcher, accountant, viewer.'), __('Use “View only” as a safe start, then tick extra rights.'), __('You can change a role later — everyone on it updates instantly.')]">
        <form method="POST" action="{{ route('roles.store') }}">
            @csrf
            @include('roles.partials.form', ['role' => null])
        </form>
    </x-form-page>
</x-app-layout>
