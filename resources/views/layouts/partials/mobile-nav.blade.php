@php($groups = \App\Support\Navigation::forCurrentUser())
<div x-data="{ open: false }" @toggle-mobile-nav.window="open = true" x-show="open" x-cloak
    class="fixed inset-0 z-50 lg:hidden">
    <div class="absolute inset-0 bg-black/40" @click="open = false"></div>
    <div class="relative flex h-full w-72 flex-col bg-brand-navy text-white" x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0">
        <div class="flex h-16 items-center justify-between px-4">
            <x-brand-logo class="h-9 w-auto" />
            <button @click="open = false" class="p-2"><x-icon name="x" :size="20" /></button>
        </div>
        <nav class="flex-1 space-y-6 overflow-y-auto scrollbar-slim px-3 py-4">
            @foreach ($groups as $group)
                <div class="space-y-1">
                    @if ($group['label'])
                        <p class="mb-1 px-2 text-xs font-semibold uppercase tracking-wider text-white/40">{{ $group['label'] }}</p>
                    @endif
                    @foreach ($group['items'] as $item)
                        <a href="{{ route($item['route']) }}" class="flex items-center gap-3 rounded-none px-2.5 py-2 text-sm font-medium {{ $item['active'] ? 'bg-brand-primary/20 text-white' : 'text-white/70 hover:bg-white/5' }}">
                            <x-icon :name="$item['icon']" :size="20" />
                            {{ $item['label'] }}
                        </a>
                        @if ($item['active'] && $item['children'])
                            <div class="ml-6 space-y-0.5 border-l border-white/10 pl-3">
                                @foreach ($item['children'] as $child)
                                    <a href="{{ route($child['route']) }}" class="block rounded-none px-2 py-1.5 text-[13px] {{ $child['active'] ? 'font-semibold text-white' : 'text-white/55' }}">
                                        {{ $child['add'] ? '+ ' : '' }}{{ $child['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    @endforeach
                </div>
            @endforeach
        </nav>
    </div>
</div>
