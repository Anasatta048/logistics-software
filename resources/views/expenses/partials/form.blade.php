<x-form-section :title="__('What was spent?')" icon="receipt" :step="1">
    <x-field name="expense_category_id" :label="__('Category')" icon="tag" required :hint="__('Manage the list in Business settings → Expense categories.')">
        <select id="expense_category_id" name="expense_category_id" required>
            <option value="">{{ __('Select category') }}</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) old('expense_category_id', $expense?->expense_category_id) === (string) $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
    </x-field>
    <x-field name="payee" :label="__('Paid to')" icon="user" :hint="__('Person or business that received the money.')">
        <input id="payee" name="payee" type="text" maxlength="120" value="{{ old('payee', $expense?->payee) }}" placeholder="e.g. K-Electric, landlord, DAT">
    </x-field>
    <x-field name="reference" :label="__('Reference no.')" icon="hash" :hint="__('Receipt, bill or cheque number.')">
        <input id="reference" name="reference" type="text" maxlength="60" value="{{ old('reference', $expense?->reference) }}">
    </x-field>
    <x-field name="amount" :label="__('Amount')" icon="dollar" required>
        <input id="amount" name="amount" type="number" step="0.01" min="0.01" value="{{ old('amount', $expense?->amount) }}" required placeholder="0.00">
    </x-field>
    <x-field name="expense_date" :label="__('Date')" icon="calendar" required>
        <input id="expense_date" name="expense_date" type="date" value="{{ old('expense_date', $expense?->expense_date?->format('Y-m-d')) }}" required>
    </x-field>
    <x-field name="payment_method" :label="__('Payment method')" icon="credit-card" required>
        <select id="payment_method" name="payment_method" required>
            <option value="">{{ __('Select method') }}</option>
            @foreach (\App\Enums\PaymentMethod::enabled(old('payment_method', $expense?->payment_method)) as $method)
                <option value="{{ $method->value }}" @selected(old('payment_method', $expense?->payment_method?->value) === $method->value)>{{ $method->label() }}</option>
            @endforeach
        </select>
    </x-field>
</x-form-section>

<x-form-section :title="__('Books & proof')" icon="layers" :description="__('Optional — link it to an account and keep the receipt.')" :step="2">
    <x-field name="account_id" :label="__('Account (optional)')" icon="layers">
        <select id="account_id" name="account_id">
            <option value="">{{ __('No account') }}</option>
            @foreach ($accounts as $account)
                <option value="{{ $account->id }}" @selected((string) old('account_id', $expense?->account_id) === (string) $account->id)>{{ $account->name }} ({{ $account->type->label() }})</option>
            @endforeach
        </select>
    </x-field>
    <x-field name="attachment" :label="__('Receipt')" icon="upload" :hint="$expense?->attachment_path ? __('Current file is kept unless you upload a new one.') : __('PDF, JPG or PNG up to 5 MB — compressed automatically.')">
        <input id="attachment" name="attachment" type="file" accept=".pdf,.jpg,.jpeg,.png">
    </x-field>
    <x-field name="notes" class="span-2" :label="__('Notes')" icon="note">
        <textarea id="notes" name="notes" rows="3">{{ old('notes', $expense?->notes) }}</textarea>
    </x-field>
</x-form-section>

<x-form-actions :cancel="route('expenses.index')" :submit="__('Save expense')" />
