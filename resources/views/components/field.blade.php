@props(['label', 'name' => null, 'icon' => null, 'hint' => null, 'required' => false, 'for' => null])
{{--
    One form field: label (+ required star), an icon inside the control, a
    plain-language hint under it and the validation error. The control itself
    goes in the slot — any input / select / textarea inherits the styling.
--}}
@php($for ??= $name)
<div {{ $attributes->merge(['class' => 'field']) }}>
    <label @if ($for) for="{{ $for }}" @endif class="field-label">
        {{ $label }}@if ($required)<span class="field-req" title="{{ __('Required') }}">*</span>@endif
    </label>
    <div class="field-wrap {{ $icon ? 'has-icon' : '' }}">
        @if ($icon)
            <span class="field-icon"><x-icon :name="$icon" :size="17" /></span>
        @endif
        {{ $slot }}
    </div>
    @if ($hint)
        <p class="field-hint">{{ $hint }}</p>
    @endif
    @if ($name)
        <x-input-error class="mt-1.5" :messages="$errors->get($name)" />
    @endif
</div>
