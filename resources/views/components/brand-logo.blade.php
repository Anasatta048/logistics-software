@props(['chip' => false])
{{-- The full Dreamelect logo. The wordmark is white, so on a light surface it
     sits on a navy chip; on the navy sidebar / auth panel it is used bare. --}}
@if ($chip)
    <span class="inline-flex items-center rounded-lg bg-brand-navy px-2.5 py-1.5">
        <img src="{{ asset('images/logo.png') }}" alt="Dreamelect Logistics" {{ $attributes->merge(['class' => 'h-8 w-auto']) }}>
    </span>
@else
    <img src="{{ asset('images/logo.png') }}" alt="Dreamelect Logistics" {{ $attributes->merge(['class' => 'h-10 w-auto']) }}>
@endif
