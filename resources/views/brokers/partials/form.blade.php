@php($isInactive = old('status', $broker?->status?->value) === 'inactive')
<x-form-section :title="__('Who is the broker?')" icon="briefcase" :description="__('Name and MC number — this is how the broker is found on a load.')" :step="1">
    <x-field name="company_name" :label="__('Broker company name')" icon="briefcase" required>
        <input id="company_name" name="company_name" type="text" value="{{ old('company_name', $broker?->company_name) }}" required autofocus placeholder="e.g. C.H. Robinson">
    </x-field>
    <x-field name="broker_mc_number" :label="__('Broker MC number')" icon="hash" required :hint="__('The number printed on the rate confirmation.')">
        <input id="broker_mc_number" name="broker_mc_number" type="text" value="{{ old('broker_mc_number', $broker?->broker_mc_number) }}" required placeholder="e.g. 550001">
    </x-field>
</x-form-section>

<x-form-section :title="__('Status & notes')" icon="flag" :description="__('Inactive brokers are hidden from the load form.')" :step="2">
    <x-field name="status" :label="__('Status')" icon="flag" required>
        <select id="status" name="status" required>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(old('status', $broker?->status?->value) === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
    </x-field>
    <x-field name="credit_notes" class="span-2" :label="__('Credit rating / notes')" icon="note" :hint="__('Anything your team should know — payment habits, disputes, contacts.')">
        <textarea id="credit_notes" name="credit_notes" rows="3">{{ old('credit_notes', $broker?->credit_notes) }}</textarea>
    </x-field>
</x-form-section>

<x-form-actions :cancel="route('brokers.index')" :submit="__('Save broker')" />
