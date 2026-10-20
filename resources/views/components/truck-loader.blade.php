@props(['size' => 'md'])
{{-- A little box truck driving along a moving road. Colours follow currentColor / brand orange. --}}
<span {{ $attributes->merge(['class' => 'truck-loader '.($size === 'sm' ? 'is-sm' : '')]) }} role="status" aria-label="Loading">
    <span class="road"></span><span class="dashes"></span>
    <svg viewBox="0 0 78 40" fill="none" aria-hidden="true">
        <circle class="puff" cx="6" cy="22" r="3" fill="currentColor" /><circle class="puff" cx="6" cy="22" r="2.4" fill="currentColor" />
        <rect x="8" y="6" width="42" height="24" fill="currentColor" />
        <rect x="12" y="10" width="34" height="2" fill="#E8622C" />
        <path d="M50 12h12l8 9v9H50z" fill="#E8622C" />
        <path d="M54 15h7l5 6h-12z" fill="#fff" opacity=".85" />
        <rect x="8" y="30" width="62" height="3" fill="currentColor" />
        <g><circle class="wheel" cx="22" cy="34" r="5" fill="#111827" stroke="#fff" stroke-width="1.5" /><path class="wheel" d="M22 30v8M18 34h8" stroke="#fff" stroke-width="1" /></g>
        <g><circle class="wheel" cx="60" cy="34" r="5" fill="#111827" stroke="#fff" stroke-width="1.5" /><path class="wheel" d="M60 30v8M56 34h8" stroke="#fff" stroke-width="1" /></g>
    </svg>
</span>
