@php
    $groups = \App\Support\Navigation::forCurrentUser();
    $me = Auth::user();
    $role = $me->getRoleNames()->first();
@endphp
{{-- Width comes from CSS only (w-64, or w-20 when <html> carries .sb-collapsed,
     set before first paint in the layout <head>), so a reload never shows a
     half-width sidebar while Alpine boots. --}}
<aside id="app-sidebar"
    x-data="{
        collapsed: document.documentElement.classList.contains('sb-collapsed'),
        open: @js(collect($groups)->flatMap(fn ($g) => collect($g['items'])->filter(fn ($i) => $i['active'] && $i['children'])->pluck('route'))->values()->all()),
        isOpen(key) { return this.open.includes(key) },
        toggleItem(key) { this.open = this.isOpen(key) ? this.open.filter((k) => k !== key) : [...this.open, key] },
        toggle() {
            this.collapsed = !this.collapsed;
            document.documentElement.classList.toggle('sb-collapsed', this.collapsed);
            try { localStorage.setItem('sb-collapsed', this.collapsed ? '1' : '0'); } catch (e) {}
        },
    }"
    class="relative hidden w-64 shrink-0 flex-col overflow-hidden border-r border-black/20 bg-brand-navy text-white lg:flex [.sb-collapsed_&]:w-20">

    {{-- soft brand glow --}}
    <div class="absolute inset-x-0 top-0 h-0.5 bg-brand-primary"></div>

    {{-- Brand --}}
    <div class="relative flex h-16 shrink-0 items-center justify-center border-b border-white/10 px-4 [.sb-collapsed_&]:px-0">
        <a href="{{ route('dashboard') }}" class="flex items-center [.sb-collapsed_&]:hidden">
            <x-brand-logo class="h-11 w-auto" />
        </a>
        <a href="{{ route('dashboard') }}" class="hidden [.sb-collapsed_&]:block">
            <x-application-logo class="h-9 w-9 text-brand-primary" />
        </a>
    </div>

    {{-- Navigation --}}
    <nav class="relative flex-1 overflow-y-auto scrollbar-slim px-3 pb-4 pt-2">
        @foreach ($groups as $group)
            <div class="{{ $group['label'] ? 'mt-5' : '' }}">
                @if ($group['label'])
                    <p class="mb-1.5 px-3 text-[10px] font-bold uppercase tracking-[0.18em] text-white/35 [.sb-collapsed_&]:hidden">
                        {{ $group['label'] }}
                    </p>
                    <div class="mx-3 mb-2 hidden border-t border-white/10 [.sb-collapsed_&]:block"></div>
                @endif

                <ul class="space-y-0.5">
                    @foreach ($group['items'] as $item)
                        @php($hasChildren = $item['children'] !== [])
                        <li>
                            <div class="group relative flex items-center transition
                                {{ $item['active'] ? 'bg-white/10' : 'hover:bg-white/5' }}">
                                @if ($item['active'])
                                    <span class="absolute -left-3 inset-y-0 w-[3px] bg-brand-primary"></span>
                                @endif

                                {{-- With a submenu, a click only opens/closes it (the list is one
                                     click away inside). Collapsed rail has no submenu, so it navigates. --}}
                                <a href="{{ route($item['route']) }}" title="{{ $item['label'] }}"
                                    @if ($hasChildren) x-on:click="if (!collapsed) { $event.preventDefault(); toggleItem(@js($item['route'])) }" @endif
                                    class="flex min-w-0 flex-1 items-center gap-3 px-2 py-2 [.sb-collapsed_&]:justify-center">
                                    <span class="grid h-8 w-8 shrink-0 place-items-center transition
                                        {{ $item['active'] ? 'bg-brand-primary text-white' : 'text-white/55 group-hover:text-white' }}">
                                        <x-icon :name="$item['icon']" :size="18" />
                                    </span>
                                    <span class="truncate text-sm font-medium [.sb-collapsed_&]:hidden
                                        {{ $item['active'] ? 'text-white' : 'text-white/70 group-hover:text-white' }}">{{ $item['label'] }}</span>
                                </a>

                                @if ($hasChildren)
                                    <button type="button" x-on:click="toggleItem(@js($item['route']))"
                                        aria-label="{{ __('Toggle :name menu', ['name' => $item['label']]) }}"
                                        class="mr-1.5 grid h-7 w-7 place-items-center text-white/40 transition hover:bg-white/10 hover:text-white [.sb-collapsed_&]:hidden">
                                        <span class="transition-transform duration-200" x-bind:class="isOpen(@js($item['route'])) && 'rotate-180'">
                                            <x-icon name="chevron-down" :size="14" />
                                        </span>
                                    </button>
                                @endif
                            </div>

                            @if ($hasChildren)
                                <ul x-show="isOpen(@js($item['route']))" x-transition.opacity.duration.150ms
                                    @unless ($item['active']) style="display: none" @endunless
                                    class="relative ml-[1.15rem] mt-0.5 space-y-0.5 border-l border-white/10 pl-3 [.sb-collapsed_&]:hidden">
                                    @foreach ($item['children'] as $child)
                                        <li>
                                            <a href="{{ route($child['route']) }}"
                                                class="flex items-center gap-2 px-2.5 py-1.5 text-[13px] transition
                                                    {{ $child['active'] ? 'bg-white/10 font-semibold text-white' : 'text-white/55 hover:bg-white/5 hover:text-white' }}">
                                                @if ($child['add'])
                                                    <x-icon name="plus" :size="13" class="shrink-0 {{ $child['active'] ? 'text-brand-primary' : 'text-brand-primary/80' }}" />
                                                @else
                                                    <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $child['active'] ? 'bg-brand-primary' : 'bg-white/30' }}"></span>
                                                @endif
                                                <span class="truncate">{{ $child['label'] }}</span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>

    {{-- Collapse --}}
    <div class="relative shrink-0 border-t border-white/10 p-3">
        <button type="button" x-on:click="toggle()"
            class="flex w-full items-center justify-center gap-2 py-1.5 text-xs font-medium text-white/50 transition hover:bg-white/5 hover:text-white">
            <span class="[.sb-collapsed_&]:rotate-180"><x-icon name="chevron-left" :size="14" /></span>
            <span class="[.sb-collapsed_&]:hidden">{{ __('Collapse') }}</span>
        </button>
    </div>
</aside>
