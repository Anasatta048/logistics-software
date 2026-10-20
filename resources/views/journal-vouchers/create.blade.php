<x-app-layout>
    <x-slot name="header">{{ __('New Journal Voucher') }}</x-slot>

    <x-form-page :title="__('New journal voucher')" :subtitle="__('A manual entry that moves money from one account to another.')" icon="note" :back="route('journal-vouchers.index')"
        :tips="[__('Debit is where the value goes, credit is where it comes from.'), __('The two accounts must be different.'), __('Explain the entry in the narration so anyone can understand it later.')]">
        <form method="POST" action="{{ route('journal-vouchers.store') }}" enctype="multipart/form-data">
            @csrf
            <x-form-section :title="__('The entry')" icon="repeat" :description="__('How much, when, and between which accounts.')" :step="1">
                <x-field name="date" :label="__('Date')" icon="calendar" required>
                    <input id="date" name="date" type="date" value="{{ old('date', \App\Support\Timezones::today()) }}" required>
                </x-field>
                <x-field name="amount" :label="__('Amount')" icon="dollar" required>
                    <input id="amount" name="amount" type="number" step="0.01" min="0.01" value="{{ old('amount') }}" required placeholder="0.00">
                </x-field>
                <x-field name="debit_account_id" :label="__('Debit account')" icon="download" required :hint="__('Receives the value.')">
                    <select id="debit_account_id" name="debit_account_id" required>
                        <option value="">{{ __('Select account') }}</option>
                        @foreach ($accounts as $account)
                            <option value="{{ $account->id }}" @selected(old('debit_account_id') == $account->id)>{{ $account->name }}</option>
                        @endforeach
                    </select>
                </x-field>
                <x-field name="credit_account_id" :label="__('Credit account')" icon="upload" required :hint="__('Gives the value.')">
                    <select id="credit_account_id" name="credit_account_id" required>
                        <option value="">{{ __('Select account') }}</option>
                        @foreach ($accounts as $account)
                            <option value="{{ $account->id }}" @selected(old('credit_account_id') == $account->id)>{{ $account->name }}</option>
                        @endforeach
                    </select>
                </x-field>
            </x-form-section>

            <x-form-section :title="__('Explain it')" icon="note" :description="__('A short reason and, if you have one, the document.')" :step="2">
                <x-field name="narration" class="span-2" :label="__('Narration')" icon="note" required>
                    <textarea id="narration" name="narration" rows="3" required>{{ old('narration') }}</textarea>
                </x-field>
                <x-field name="attachment" class="span-2" :label="__('Attachment')" icon="upload" :hint="__('PDF, JPG or PNG up to 5 MB — compressed automatically.')">
                    <input id="attachment" name="attachment" type="file" accept=".pdf,.jpg,.jpeg,.png">
                </x-field>
            </x-form-section>

            <x-form-actions :cancel="route('journal-vouchers.index')" :submit="__('Save voucher')" />
        </form>
    </x-form-page>
</x-app-layout>
