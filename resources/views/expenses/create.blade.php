<x-app-layout>
    <x-slot name="header">{{ __('Add Expense') }}</x-slot>

    <x-form-page :title="__('Add Expense')" :subtitle="__('Record money the business spent.')" icon="receipt" :back="route('expenses.index')" :tips="[__('Attach the receipt photo or PDF — it is compressed automatically.'), __('Pick an account if you want it to hit your books.')]">
        <form method="POST" action="{{ route('expenses.store') }}" enctype="multipart/form-data">
            @csrf
            @include('expenses.partials.form', ['expense' => null])
        </form>
    </x-form-page>
</x-app-layout>
