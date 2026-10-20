@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-sm font-medium text-text-primary dark:text-gray-200']) }}>
    {{ $value ?? $slot }}
</label>
