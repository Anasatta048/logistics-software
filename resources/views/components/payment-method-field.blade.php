@props(['name' => 'payment_method', 'value' => null, 'required' => true])
@php
    $current = old($name, $value instanceof \BackedEnum ? $value->value : $value);
@endphp
<div>
    <x-input-label :for="$name" :value="__('Payment method')" />
    <select id="{{ $name }}" name="{{ $name }}" @required($required)
        class="mt-1 block w-full rounded-input border-border bg-surface text-text-primary shadow-sm focus:border-brand-primary focus:ring-brand-primary dark:bg-surface-dark dark:border-white/10 dark:text-white">
        <option value="">{{ __('Select method') }}</option>
        @foreach (\App\Enums\PaymentMethod::enabled(old('payment_method', $value ?? null)) as $method)
            <option value="{{ $method->value }}" @selected($current === $method->value)>{{ $method->label() }}</option>
        @endforeach
    </select>
    <x-input-error class="mt-2" :messages="$errors->get($name)" />
</div>
