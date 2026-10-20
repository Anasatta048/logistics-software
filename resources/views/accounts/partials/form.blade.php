<x-form-section :title="__('Account details')" icon="layers" :description="__('Where money is recorded in your books.')">
    <x-field name="name" :label="__('Name')" icon="tag" required>
        <input id="name" name="name" type="text" value="{{ old('name', $account?->name) }}" required autofocus>
    </x-field>
    <x-field name="type" :label="__('Type')" icon="layers" required>
        <select id="type" name="type" required>
            @foreach ($types as $type)
                <option value="{{ $type->value }}" @selected(old('type', $account?->type?->value) === $type->value)>{{ $type->label() }}</option>
            @endforeach
        </select>
    </x-field>
    @if (! $account)
        <x-field name="opening_balance" :label="__('Opening balance')" icon="dollar" required :hint="__('What the account already holds today. Cannot be changed later.')">
            <input id="opening_balance" name="opening_balance" type="number" step="0.01" min="0" value="{{ old('opening_balance', 0) }}" required>
        </x-field>
    @endif
    <x-field name="status" :label="__('Status')" icon="flag" required>
        <select id="status" name="status" required>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(old('status', $account?->status?->value) === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
    </x-field>
</x-form-section>

<x-form-actions :cancel="route('accounts.index')" :submit="__('Save account')" />
