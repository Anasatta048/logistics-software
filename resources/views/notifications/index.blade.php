<x-app-layout>
    <x-slot name="header">{{ __('Notifications') }}</x-slot>

    @php
        $unreadOnPage = $notifications->getCollection()->whereNull('read_at')->count();
        $iconFor = function (string $msg): array {
            $m = strtolower($msg);
            if (str_contains($m, 'cancel')) return ['x-circle', 'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400'];
            if (str_contains($m, 'paid') || str_contains($m, 'payment')) return ['dollar', 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400'];
            if (str_contains($m, 'booked')) return ['truck', 'bg-brand-navy/10 text-brand-navy dark:bg-white/10 dark:text-white'];
            if (str_contains($m, 'status') || str_contains($m, ' now ')) return ['refresh', 'bg-brand-primary/10 text-brand-primary'];
            return ['bell', 'bg-gray-100 text-text-muted dark:bg-white/5'];
        };
    @endphp

    <div class="form-anim space-y-6"
         x-data="{
            filter: 'all',
            unread: {{ $unreadOnPage }},
            token() { return document.querySelector('meta[name=csrf-token]')?.content },
            async post(url) {
                const r = await fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': this.token(), 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                if (!r.ok) throw new Error();
            },
            async markRead(e) {
                try { await this.post(e.target.action); this.unread = Math.max(0, this.unread - 1); return true; }
                catch { e.target.submit(); return false; }
            },
            async markAll(e) {
                try { await this.post(e.target.action); window.dispatchEvent(new CustomEvent('notifs-all-read')); this.unread = 0; }
                catch { e.target.submit(); }
            }
         }">
        <div class="form-page-head !mb-0 flex-wrap">
            <span class="form-page-icon"><x-icon name="bell" :size="26" /></span>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-extrabold tracking-tight text-text-primary dark:text-white">{{ __('Notifications') }}</h1>
                <p class="mt-0.5 text-sm text-text-muted">{{ __('Load bookings, status changes, payments and cancellations.') }}</p>
            </div>
            <form method="POST" action="{{ route('notifications.read-all') }}" @submit.prevent="markAll($event)">
                @csrf
                <button type="submit" class="btn-ghost btn-sm inline-flex items-center gap-1.5" :disabled="unread === 0" :class="unread === 0 && 'opacity-50 cursor-not-allowed'">
                    <x-icon name="check-circle" :size="16" /> {{ __('Mark all read') }}
                </button>
            </form>
        </div>

        <div class="border border-border bg-surface dark:border-white/10 dark:bg-surface-dark">
            {{-- Tabs --}}
            <div class="flex items-center gap-1 border-b border-border px-3 dark:border-white/10">
                @foreach (['all' => __('All'), 'unread' => __('Unread')] as $key => $label)
                    <button type="button" @click="filter = '{{ $key }}'"
                            class="-mb-px inline-flex items-center gap-2 border-b-2 px-3 py-3 text-sm font-semibold transition"
                            :class="filter === '{{ $key }}' ? 'border-brand-primary text-text-primary dark:text-white' : 'border-transparent text-text-muted hover:text-text-primary dark:hover:text-white'">
                        {{ $label }}
                        @if ($key === 'unread')
                            <span x-show="unread > 0" x-text="unread" class="rounded-full bg-brand-primary px-1.5 py-0.5 text-[10px] font-bold leading-none text-white"></span>
                        @else
                            <span class="text-xs font-medium text-text-muted">{{ $notifications->total() }}</span>
                        @endif
                    </button>
                @endforeach
            </div>

            @if ($notifications->isEmpty())
                <x-empty-state icon="bell" title="No notifications yet" description="You're all caught up." />
            @else
                <ul class="divide-y divide-border/60 dark:divide-white/5">
                    @foreach ($notifications as $notification)
                        @php
                            $msg = $notification->data['message'] ?? class_basename($notification->type);
                            [$icon, $tone] = $iconFor($msg);
                            $url = $notification->data['url'] ?? null;
                        @endphp
                        <li x-data="{ read: {{ $notification->read_at ? 'true' : 'false' }} }" @notifs-all-read.window="read = true"
                            x-show="filter === 'all' || !read"
                            class="group relative flex items-start gap-3 px-4 py-4 transition sm:gap-4 sm:px-5 hover:bg-gray-50 dark:hover:bg-white/[0.03]"
                            :class="!read && 'bg-brand-primary/[0.04]'">
                            <span class="absolute inset-y-0 left-0 w-1 bg-brand-primary" x-show="!read"></span>

                            <span class="flex h-10 w-10 shrink-0 items-center justify-center {{ $tone }}">
                                <x-icon :name="$icon" :size="20" />
                            </span>

                            <div class="min-w-0 flex-1">
                                <p class="text-sm leading-snug text-text-primary dark:text-white"
                                   :class="!read ? 'font-semibold' : 'text-text-muted dark:text-white/70'">
                                    {{ $msg }}
                                </p>
                                <p class="mt-1 flex flex-wrap items-center gap-x-2 text-xs text-text-muted">
                                    <span>{{ $notification->created_at->diffForHumans() }}</span>
                                    <span aria-hidden="true">&middot;</span>
                                    <time datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->format('M j, Y g:i A') }}</time>
                                </p>
                            </div>

                            <div class="flex shrink-0 flex-col items-end gap-2 sm:flex-row sm:items-center">
                                @if ($url)
                                    <a href="{{ $url }}" class="btn-ghost btn-sm inline-flex items-center gap-1">
                                        <x-icon name="eye" :size="14" /> {{ __('Open') }}
                                    </a>
                                @endif
                                @unless ($notification->read_at)
                                    <form method="POST" action="{{ route('notifications.read', $notification->id) }}"
                                          x-show="!read"
                                          @submit.prevent="markRead($event).then(ok => { if (ok) read = true })">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center gap-1 text-xs font-semibold text-brand-primary hover:underline" title="{{ __('Mark read') }}">
                                            <x-icon name="check" :size="14" /> <span class="hidden sm:inline">{{ __('Mark read') }}</span>
                                        </button>
                                    </form>
                                @endunless
                            </div>
                        </li>
                    @endforeach
                </ul>
                <div x-show="filter === 'unread' && unread === 0" x-cloak>
                    <x-empty-state icon="check-circle" title="No unread notifications" description="You're all caught up on this page." />
                </div>
            @endif
        </div>

        <div>{{ $notifications->links() }}</div>
    </div>
</x-app-layout>
