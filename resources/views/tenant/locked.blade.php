<x-guest-layout>
    <div class="text-center">
        <span class="mx-auto grid h-16 w-16 place-items-center bg-warning/15 text-warning"><x-icon name="lock" :size="32" /></span>
        <h1 class="mt-5 text-2xl font-extrabold tracking-tight text-text-primary">{{ __('Workspace locked') }}</h1>
        <p class="mt-2 text-sm text-text-muted">{{ $tenant->lockedReason() }}</p>
        <p class="mt-1 text-sm text-text-muted">{{ __('Contact the platform team to restore access. Your data is safe.') }}</p>
        <a href="{{ route('billing.index') }}" class="btn-primary mt-5 w-full">{{ __('Renew subscription') }}</a>
        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button class="btn-primary w-full">{{ __('Log out') }}</button>
        </form>
    </div>
</x-guest-layout>
