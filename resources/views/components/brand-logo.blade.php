@props(['chip' => false])
{{-- The platform mark: a truck plate + the product name. Tenants are named inside the app. --}}
@php($textClass = $chip ? 'text-white' : 'text-white')
<span {{ $attributes->only('class')->merge(['class' => 'inline-flex items-center gap-2.5 '.($chip ? 'rounded-lg bg-brand-navy px-2.5 py-1.5' : '')]) }}>
    <span class="grid h-9 w-9 shrink-0 place-items-center bg-brand-primary text-white"><x-icon name="truck" :size="20" /></span>
    <span class="text-lg font-extrabold leading-none tracking-tight {{ $textClass }}">{{ config('app.name') }}</span>
</span>
