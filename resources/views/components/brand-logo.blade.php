@props(['chip' => false])
{{-- The mark shown in the sidebar and on sign-in: the company's own logo once uploaded, else the platform mark. --}}
@php($tenantLogo = app(\App\Support\Tenancy\TenantContext::class)->has() ? \App\Support\Settings\TenantSettings::url('branding.logo') : null)
@if ($tenantLogo)
    <span {{ $attributes->only('class')->merge(['class' => 'inline-flex items-center '.($chip ? 'rounded-lg bg-white px-2.5 py-1.5' : '')]) }}>
        <img src="{{ $tenantLogo }}" alt="{{ config('invoice.company_name') ?: config('app.name') }}" class="max-h-10 w-auto max-w-[11rem] object-contain {{ $chip ? '' : 'rounded bg-white/95 p-1' }}">
    </span>
@else
    <span {{ $attributes->only('class')->merge(['class' => 'inline-flex items-center gap-2.5 '.($chip ? 'rounded-lg bg-brand-navy px-2.5 py-1.5' : '')]) }}>
        <span class="grid h-9 w-9 shrink-0 place-items-center bg-brand-primary text-white"><x-icon name="truck" :size="20" /></span>
        <span class="text-lg font-extrabold leading-none tracking-tight text-white">{{ app(\App\Support\Tenancy\TenantContext::class)->tenant()?->name ?? config('app.name') }}</span>
    </span>
@endif
