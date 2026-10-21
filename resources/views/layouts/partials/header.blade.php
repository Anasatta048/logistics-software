@php
    $unread = Auth::user()->unreadNotifications()->count();
    $recent = Auth::user()->notifications()->latest()->limit(8)->get()->map(fn ($n) => [
        'id' => $n->id,
        'message' => $n->data['message'] ?? class_basename($n->type),
        'url' => $n->data['url'] ?? null,
        'read' => $n->read_at !== null,
        'time' => $n->created_at->diffForHumans(null, true, true).' ago',
    ])->all();
@endphp
<header x-data="{ userMenu: false }" class="relative z-30 flex h-16 shrink-0 items-center justify-between border-b border-border bg-surface px-4 dark:border-white/10 dark:bg-surface-dark sm:px-6">
    <div class="flex items-center gap-3">
        @if (($navPosition ?? 'sidebar') === 'sidebar')
            <button class="p-2 text-text-muted hover:bg-border/50 lg:hidden" @click="$dispatch('toggle-mobile-nav')" aria-label="{{ __('Menu') }}">
                <x-icon name="menu" :size="20" />
            </button>
        @else
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                <x-brand-logo :chip="true" class="h-8 w-auto" />
            </a>
        @endif
        @isset($header)
            <div class="border-l-2 border-brand-primary pl-3 text-base font-semibold tracking-tight text-text-primary dark:text-white">{{ $header }}</div>
        @endisset
    </div>

    <div class="flex items-center gap-1">
        <div class="relative flex items-center gap-1" @click.outside="open = false" @keydown.escape.window="open = false" x-data="notificationBell(@js([
            'userId' => Auth::id(),
            'unread' => $unread,
            'items' => $recent,
            'appName' => config('app.name'),
            'indexUrl' => route('notifications.index'),
            'readUrl' => route('notifications.read', '__ID__'),
            'readAllUrl' => route('notifications.read-all'),
            'latest' => Auth::user()->unreadNotifications()->latest()->limit(1)->get()
                ->map(fn ($n) => ['message' => $n->data['message'] ?? '', 'url' => $n->data['url'] ?? null])->all(),
        ]))">
            <button type="button" x-show="permission === 'default'" x-cloak x-on:click="enable()"
                class="hidden items-center gap-1.5 border border-border px-2.5 py-1.5 text-xs font-medium text-text-muted transition hover:border-brand-primary hover:text-brand-primary dark:border-white/10 sm:inline-flex">
                <x-icon name="bell" :size="14" /> {{ __('Enable desktop alerts') }}
            </button>

            <button type="button" @click="open = !open" class="relative p-2 text-text-muted transition hover:bg-border/50 hover:text-text-primary dark:hover:bg-white/5 dark:hover:text-white"
                title="{{ __('Notifications') }}" aria-label="{{ __('Notifications') }}">
                <x-icon name="bell" :size="20" />
                <span x-show="unread > 0" x-cloak x-text="unread > 99 ? '99+' : unread"
                    class="absolute right-0 top-0 flex h-4 min-w-4 items-center justify-center bg-brand-primary px-1 text-[10px] font-bold text-white"></span>
            </button>

            {{-- Dropdown --}}
            <div x-show="open" x-cloak x-transition.origin.top.right.duration.150ms
                class="absolute right-0 top-full z-40 mt-2 w-[22rem] max-w-[calc(100vw-1.5rem)] border border-border bg-surface shadow-2xl shadow-black/10 dark:border-white/10 dark:bg-surface-dark">
                <div class="flex items-center justify-between border-b border-border px-4 py-3 dark:border-white/10">
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm font-bold text-text-primary dark:text-white">{{ __('Notifications') }}</h3>
                        <span x-show="unread > 0" x-cloak class="bg-brand-primary px-1.5 py-0.5 text-[10px] font-bold text-white" x-text="unread + ' new'"></span>
                    </div>
                    <button type="button" x-show="unread > 0" x-cloak @click="markAll()" class="text-xs font-semibold text-brand-primary hover:underline">{{ __('Mark all read') }}</button>
                </div>

                <ul class="max-h-96 divide-y divide-border overflow-y-auto dark:divide-white/5">
                    <template x-for="item in items" x-bind:key="item.id">
                        <li>
                            <button type="button" @click="openItem(item)"
                                class="relative flex w-full items-start gap-3 px-4 py-3 text-left transition hover:bg-bg dark:hover:bg-white/5"
                                x-bind:class="item.read ? '' : 'bg-brand-primary/[0.04]'">
                                <span class="absolute inset-y-0 left-0 w-0.5 bg-brand-primary" x-show="!item.read"></span>
                                <span class="mt-0.5 grid h-8 w-8 shrink-0 place-items-center bg-brand-navy text-white">
                                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 6h13v10H1zM14 9h4l3 3v4h-7"/><circle cx="6" cy="17.5" r="1.8"/><circle cx="17" cy="17.5" r="1.8"/></svg>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-[13px] leading-snug" x-bind:class="item.read ? 'text-text-muted' : 'font-medium text-text-primary dark:text-white'" x-text="item.message"></span>
                                    <span class="mt-0.5 block text-[11px] text-text-muted" x-text="item.time"></span>
                                </span>
                            </button>
                        </li>
                    </template>
                    <li x-show="items.length === 0" x-cloak class="px-4 py-10 text-center">
                        <div class="mx-auto mb-2 grid h-10 w-10 place-items-center border border-border text-text-muted dark:border-white/10"><x-icon name="bell" :size="18" /></div>
                        <p class="text-sm font-medium text-text-primary dark:text-white">{{ __('All caught up') }}</p>
                        <p class="text-xs text-text-muted">{{ __('New load activity will show up here.') }}</p>
                    </li>
                </ul>

                <a href="{{ route('notifications.index') }}" class="block border-t border-border px-4 py-2.5 text-center text-xs font-semibold text-text-muted transition hover:bg-bg hover:text-brand-primary dark:border-white/10 dark:hover:bg-white/5">
                    {{ __('View all notifications') }}
                </a>
            </div>
        </div>

        @can('viewAny', App\Models\Load::class)
            @php($boards = \App\Support\LoadBoards::links())
            @if ($boards)
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button type="button" @click="open = !open" class="flex items-center gap-1.5 p-2 text-text-muted transition hover:bg-border/50 hover:text-text-primary dark:hover:bg-white/5 dark:hover:text-white" title="{{ __('Load boards') }}" aria-label="{{ __('Load boards') }}">
                        <x-icon name="truck" :size="20" />
                        <span class="hidden text-xs font-semibold lg:inline">{{ __('Load boards') }}</span>
                    </button>
                    <div x-show="open" x-cloak x-transition.origin.top.right.duration.150ms class="absolute right-0 z-40 mt-2 w-56 border border-border bg-surface py-1 shadow-2xl shadow-black/10 dark:border-white/10 dark:bg-surface-dark">
                        @foreach ($boards as $board)
                            <a href="{{ $board['url'] }}" target="_blank" rel="noopener noreferrer" class="flex items-center justify-between gap-2 px-3 py-2 text-sm text-text-primary hover:bg-bg dark:text-white dark:hover:bg-white/5">
                                <span class="truncate">{{ $board['name'] }}</span>
                                <x-icon name="link" :size="14" class="shrink-0 text-text-muted" />
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        @endcan

        <button
            x-data
            @click="
                const html = document.documentElement;
                const dark = html.classList.toggle('dark');
                try { localStorage.setItem('theme', dark ? 'dark' : 'light'); } catch (e) {}
            "
            class="p-2 text-text-muted transition hover:bg-border/50 hover:text-text-primary dark:hover:bg-white/5 dark:hover:text-white" title="{{ __('Toggle theme') }}">
            <x-icon name="sun" :size="20" class="dark:hidden" />
            <x-icon name="moon" :size="20" class="hidden dark:block" />
        </button>

        <div class="relative ml-1" @click.outside="userMenu = false">
            <button @click="userMenu = !userMenu" class="flex items-center gap-2 px-2 py-1.5 transition hover:bg-border/50 dark:hover:bg-white/5">
                <span class="flex h-8 w-8 items-center justify-center bg-brand-navy text-sm font-bold text-white">
                    {{ Str::of(Auth::user()->name)->substr(0, 1)->upper() }}
                </span>
                <span class="hidden text-left sm:block">
                    <span class="block text-sm font-semibold leading-tight text-text-primary dark:text-white">{{ Auth::user()->name }}</span>
                    <span class="block text-[11px] capitalize leading-tight text-text-muted">{{ Auth::user()->getRoleNames()->first() }}</span>
                </span>
                <x-icon name="chevron-down" :size="16" class="hidden text-text-muted sm:block" />
            </button>
            <div x-show="userMenu" x-cloak x-transition.origin.top.right.duration.150ms class="absolute right-0 z-40 mt-2 w-52 border border-border bg-surface py-1 shadow-2xl shadow-black/10 dark:border-white/10 dark:bg-surface-dark">
                <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-3 py-2 text-sm text-text-primary hover:bg-bg dark:text-white dark:hover:bg-white/5">
                    <x-icon name="settings" :size="16" /> {{ __('Profile & Settings') }}
                </a>
                <button type="button" data-install-app hidden class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-text-primary hover:bg-bg dark:text-white dark:hover:bg-white/5">
                    <x-icon name="download" :size="16" /> {{ __('Install app') }}
                </button>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-2 border-t border-border px-3 py-2 text-left text-sm text-status-cancelled hover:bg-bg dark:border-white/10 dark:hover:bg-white/5">
                        <x-icon name="logout" :size="16" /> {{ __('Log Out') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
