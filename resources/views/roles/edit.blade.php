<x-app-layout>
    <x-slot name="header">{{ __('Edit Role') }}</x-slot>

    <x-form-page :title="__('Edit role: :name', ['name' => $role->name])" :subtitle="__('Changes apply to every user on this role right away.')" icon="key" :back="route('roles.index')" :back-label="__('All roles')" wide
        :tips="[__('Untick a right to take it away from everyone on this role.'), __('Users must sign in again for some changes to show in their menu.')]">
        <form method="POST" action="{{ route('roles.update', $role) }}">
            @csrf
            @method('PUT')
            @include('roles.partials.form', ['role' => $role])
        </form>
    </x-form-page>
</x-app-layout>
