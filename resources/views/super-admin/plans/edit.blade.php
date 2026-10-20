<x-app-layout>
    <x-slot name="header">{{ __('Edit Plan') }}</x-slot>

    <x-form-page :title="__('Edit :name', ['name' => $plan->name])" icon="tag" :back="route('super-admin.plans.index')" :back-label="__('All plans')">
        <form method="POST" action="{{ route('super-admin.plans.update', $plan) }}">
            @csrf @method('PUT')
            @include('super-admin.plans._form')
        </form>
    </x-form-page>
</x-app-layout>
