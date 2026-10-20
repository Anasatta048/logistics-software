<x-app-layout>
    <x-slot name="header">{{ __('Edit Expense') }}</x-slot>

    <x-form-page :title="__('Edit Expense')" :subtitle="__('Change anything below, then save.')" icon="receipt" :back="route('expenses.index')" :tips="[__('Attach the receipt photo or PDF — it is compressed automatically.'), __('Pick an account if you want it to hit your books.')]">
        <form method="POST" action="{{ route('expenses.update', $expense) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('expenses.partials.form', ['expense' => $expense])
        </form>
    </x-form-page>
</x-app-layout>
