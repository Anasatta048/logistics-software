<x-app-layout>
    <x-slot name="header">{{ __('Add Plan') }}</x-slot>

    <x-form-page :title="__('New plan')" :subtitle="__('It appears on the landing page as soon as it is active.')" icon="tag" :back="route('super-admin.plans.index')" :back-label="__('All plans')">
        <form method="POST" action="{{ route('super-admin.plans.store') }}">
            @csrf
            @include('super-admin.plans._form')
        </form>
    </x-form-page>
</x-app-layout>
