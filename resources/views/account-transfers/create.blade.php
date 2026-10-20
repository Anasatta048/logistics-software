<x-app-layout>
    <x-slot name="header">{{ __('New transfer') }}</x-slot>

    <x-form-page :title="__('Move money between accounts')" :subtitle="__('Both accounts are updated at once and the move shows in each ledger.')" icon="repeat" :back="route('account-transfers.index')" :back-label="__('All transfers')"
        :tips="[__('Use this for cash deposited to a bank, bank to bank, or wallet withdrawals.'), __('Bank charges are taken from the sending account on top of the amount.'), __('Made a mistake? Reverse the transfer from the list.')]">
        @if ($errors->any())<x-alert variant="error">{{ $errors->first() }}</x-alert>@endif
        <form method="POST" action="{{ route('account-transfers.store') }}" class="space-y-5">
            @csrf
            <x-form-section :title="__('From and to')" icon="repeat" :step="1">
                <x-field name="from_account_id" :label="__('From account')" icon="wallet" required>
                    <select id="from_account_id" name="from_account_id" required>
                        <option value="">{{ __('Select…') }}</option>
                        @foreach ($accounts as $a)<option value="{{ $a->id }}" @selected(old('from_account_id') == $a->id)>{{ $a->name }} — Rs {{ number_format((float) $a->current_balance, 2) }}</option>@endforeach
                    </select>
                </x-field>
                <x-field name="to_account_id" :label="__('To account')" icon="wallet" required>
                    <select id="to_account_id" name="to_account_id" required>
                        <option value="">{{ __('Select…') }}</option>
                        @foreach ($accounts as $a)<option value="{{ $a->id }}" @selected(old('to_account_id') == $a->id)>{{ $a->name }} — Rs {{ number_format((float) $a->current_balance, 2) }}</option>@endforeach
                    </select>
                </x-field>
            </x-form-section>
            <x-form-section :title="__('Amount')" icon="dollar" :step="2">
                <x-field name="amount" :label="__('Amount (Rs)')" icon="dollar" required><input id="amount" name="amount" type="number" step="0.01" min="0.01" value="{{ old('amount') }}" required></x-field>
                <x-field name="fee" :label="__('Bank charges (Rs)')" icon="percent" :hint="__('Optional. Taken from the sending account.')"><input id="fee" name="fee" type="number" step="0.01" min="0" value="{{ old('fee', 0) }}"></x-field>
                <x-field name="transfer_date" :label="__('Date')" icon="calendar" required><input id="transfer_date" name="transfer_date" type="date" value="{{ old('transfer_date', now()->toDateString()) }}" required></x-field>
                <x-field name="reference" :label="__('Reference no.')" icon="hash"><input id="reference" name="reference" type="text" maxlength="60" value="{{ old('reference') }}"></x-field>
                <x-field name="narration" :label="__('Note')" class="span-2"><input id="narration" name="narration" type="text" maxlength="255" value="{{ old('narration') }}"></x-field>
            </x-form-section>
            <x-form-actions :cancel="route('account-transfers.index')" :submit="__('Transfer')" />
        </form>
    </x-form-page>
</x-app-layout>
