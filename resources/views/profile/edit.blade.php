<x-app-layout>
    <x-slot name="header">{{ __('Settings') }}</x-slot>

    @php
        $canTz = Gate::allows('manage-settings');
        $tabs = array_filter([
            'profile' => ['Profile', 'user'],
            'password' => ['Password', 'lock'],
            'appearance' => ['Appearance', 'palette'],
            'notifications' => ['Notifications', 'bell'],
            'invoice' => $canTz ? ['Invoice', 'file-text'] : null,
            'timezones' => $canTz ? ['Timezones', 'clock'] : null,
        ]);
        $initial = $tab ?? ($errors->updatePassword->any() ? 'password' : ($errors->has('zones') || $errors->has('zones.*') ? 'timezones' : null));
        $preferences = Auth::user()->notification_preferences ?? \App\Models\User::defaultNotificationPreferences();
        $field = '';
    @endphp

    <div class="form-page max-w-5xl" x-data="{
            tab: @js($initial) || (location.hash || '').slice(1) || (() => { try { return sessionStorage.getItem('settings-tab') } catch (e) { return null } })() || 'profile',
            go(t) { this.tab = t; history.replaceState(null, '', '#' + t); try { sessionStorage.setItem('settings-tab', t) } catch (e) {} },
            init() { if (!@js(array_keys($tabs)).includes(this.tab)) this.tab = 'profile' },
        }">

        <div class="form-page-head">
            <span class="form-page-icon"><x-icon name="settings" :size="26" /></span>
            <div>
                <h1 class="text-xl font-extrabold tracking-tight text-text-primary dark:text-white">{{ __('Settings') }}</h1>
                <p class="mt-0.5 text-sm text-text-muted">{{ __('Your account, how the app looks, and what it tells you about.') }}</p>
            </div>
        </div>

        <div class="tabs mb-5 bg-surface dark:bg-surface-dark" role="tablist">
            @foreach ($tabs as $key => [$label, $icon])
                <button type="button" role="tab" class="tab" :class="{ 'is-active': tab === '{{ $key }}' }" @click="go('{{ $key }}')">
                    <x-icon :name="$icon" :size="16" /> {{ __($label) }}
                </button>
            @endforeach
        </div>

        {{-- PROFILE --}}
        <div x-show="tab === 'profile'" x-cloak class="form-anim space-y-5">
            <form method="post" action="{{ route('profile.update') }}" class="space-y-5">
                @csrf @method('patch')
                <x-form-section :title="__('Your details')" icon="user" :description="__('This is how you appear to your team and where we send sign-in emails.')">
                    <x-field name="name" :label="__('Full name')" icon="user" required>
                        <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autocomplete="name">
                    </x-field>
                    <x-field name="email" :label="__('Email address')" icon="mail" required :hint="__('You sign in with this email.')">
                        <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="username">
                    </x-field>
                </x-form-section>
                <x-form-actions :submit="__('Save changes')" />
            </form>

            <x-form-section :title="__('Delete account')" icon="trash" :description="__('Permanent. Only do this if you really will not use the app again.')">
                <div class="span-2">@include('profile.partials.delete-user-form')</div>
            </x-form-section>
        </div>

        {{-- PASSWORD --}}
        <div x-show="tab === 'password'" x-cloak class="form-anim space-y-5">
            <form method="post" action="{{ route('password.update') }}" class="space-y-5">
                @csrf @method('put')
                <x-form-section :title="__('Change password')" icon="lock" :description="__('Use a long password you do not use anywhere else.')">
                    <x-field class="span-2" :label="__('Current password')" icon="lock" for="update_password_current_password">
                        <input id="update_password_current_password" name="current_password" type="password" autocomplete="current-password">
                        <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-1.5" />
                    </x-field>
                    <x-field :label="__('New password')" icon="key" for="update_password_password" :hint="__('At least 8 characters.')">
                        <input id="update_password_password" name="password" type="password" autocomplete="new-password">
                        <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-1.5" />
                    </x-field>
                    <x-field :label="__('Repeat new password')" icon="check" for="update_password_password_confirmation">
                        <input id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password">
                        <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-1.5" />
                    </x-field>
                </x-form-section>
                <x-form-actions :submit="__('Update password')" icon="lock" />
            </form>
        </div>

        {{-- APPEARANCE --}}
        <div x-show="tab === 'appearance'" x-cloak class="form-anim space-y-5">
            <form method="POST" action="{{ route('settings.appearance') }}" class="space-y-5">
                @csrf @method('PATCH')
                <x-form-section :title="__('Theme')" icon="palette" :description="__('Pick what is easiest on your eyes. “System” follows your device.')">
                    <div class="span-2 grid gap-3 sm:grid-cols-3">
                        @foreach (['light' => ['Light', 'sun', 'Bright and clean'], 'dark' => ['Dark', 'moon', 'Easy at night'], 'system' => ['System', 'settings', 'Match my device']] as $value => [$label, $icon, $sub])
                            <label class="group relative flex cursor-pointer items-center gap-3 border p-4 transition hover:border-brand-primary
                                {{ (Auth::user()->theme_mode ?? 'system') === $value ? 'border-brand-primary bg-brand-primary/5' : 'border-border dark:border-white/10' }}">
                                <input type="radio" name="theme_mode" value="{{ $value }}" class="peer sr-only" @checked((Auth::user()->theme_mode ?? 'system') === $value)>
                                <span class="grid h-10 w-10 place-items-center bg-brand-navy text-white transition group-hover:scale-105"><x-icon :name="$icon" :size="18" /></span>
                                <span><span class="block text-sm font-bold text-text-primary dark:text-white">{{ __($label) }}</span><span class="block text-xs text-text-muted">{{ __($sub) }}</span></span>
                                <span class="absolute right-3 top-3 hidden text-brand-primary peer-checked:block"><x-icon name="check-circle" :size="18" /></span>
                            </label>
                        @endforeach
                    </div>
                </x-form-section>
                <x-form-actions :submit="__('Save appearance')" />
            </form>
        </div>

        {{-- NOTIFICATIONS --}}
        <div x-show="tab === 'notifications'" x-cloak class="form-anim space-y-5">
            <form method="POST" action="{{ route('settings.notifications') }}" class="space-y-5">
                @csrf @method('PATCH')
                <x-form-section :title="__('Tell me when…')" icon="bell" :description="__('Switch off what you do not need. You still see everything in the Loads list.')">
                    <div class="span-2 grid gap-3 sm:grid-cols-2">
                        @foreach ([
                            'load_booked' => ['A load is booked', 'truck'],
                            'load_status_changed' => ['A load changes status', 'refresh'],
                            'load_cancelled' => ['A load is cancelled', 'x-circle'],
                            'load_paid' => ['A load is paid', 'dollar'],
                        ] as $key => [$label, $icon])
                            <label class="flex cursor-pointer items-center gap-3 border border-border p-3 transition hover:border-brand-primary dark:border-white/10">
                                <span class="grid h-9 w-9 place-items-center bg-brand-primary/10 text-brand-primary"><x-icon :name="$icon" :size="17" /></span>
                                <span class="flex-1 text-sm font-medium text-text-primary dark:text-white">{{ __($label) }}</span>
                                <input type="checkbox" name="preferences[]" value="{{ $key }}" class="h-5 w-5 text-brand-primary focus:ring-brand-primary" @checked($preferences[$key] ?? true)>
                            </label>
                        @endforeach
                    </div>
                </x-form-section>
                <x-form-actions :submit="__('Save notifications')" />
            </form>
        </div>

        {{-- INVOICE --}}
        @if ($canTz)
            @php($invoiceProfile = \App\Support\InvoiceProfile::get())
            <div x-show="tab === 'invoice'" x-cloak class="form-anim space-y-5">
                <form method="POST" action="{{ route('settings.invoice.update') }}" class="space-y-5">
                    @csrf @method('PATCH')
                    <x-form-section :title="__('Invoice details')" icon="file-text" :description="__('Printed on every invoice PDF — the name at the top and the contact details in the footer.')">
                        <x-field name="company_name" class="span-2" :label="__('Company name')" icon="building" required>
                            <input id="company_name" name="company_name" type="text" value="{{ old('company_name', $invoiceProfile['company_name']) }}" required>
                        </x-field>
                        <x-field name="email" :label="__('Contact email')" icon="mail" required>
                            <input id="email_invoice" name="email" type="email" value="{{ old('email', $invoiceProfile['email']) }}" required>
                        </x-field>
                        <x-field name="phone" :label="__('Contact phone')" icon="phone" required :hint="__('Used when the dispatcher has no phone of their own.')">
                            <input id="phone_invoice" name="phone" type="text" value="{{ old('phone', $invoiceProfile['phone']) }}" required>
                        </x-field>
                        <x-field name="website" class="span-2" :label="__('Website')" icon="globe">
                            <input id="website" name="website" type="text" value="{{ old('website', $invoiceProfile['website']) }}">
                        </x-field>
                    </x-form-section>
                    <x-form-actions :submit="__('Save invoice details')" />
                </form>
            </div>
        @endif

        {{-- TIMEZONES --}}
        @if ($canTz)
            <div x-show="tab === 'timezones'" x-cloak class="form-anim space-y-5">
                @php($rows = old('zones', $zones ?? \App\Support\Timezones::zones()))
                @php($zoneOptions = $options ?? \App\Support\Timezones::usOptions())
                @php($maxZones = $max ?? \App\Http\Controllers\Settings\TimezoneController::MAX_ZONES)
                <form method="POST" action="{{ route('settings.timezones.update') }}" x-data="{ rows: @js(array_values($rows)), max: {{ $maxZones }} }" class="space-y-5">
                    @csrf @method('PATCH')
                    <x-form-section :title="__('Team timezones')" icon="clock" :description="__('Only US zones. Every date and time is shown in each zone below; the first one is the default when dispatchers type a time.')">
                        <div class="span-2 space-y-3">
                            <template x-for="(row, index) in rows" :key="index">
                                <div class="grid grid-cols-1 gap-3 border border-border p-3 sm:grid-cols-[8rem_1fr_auto] sm:items-end dark:border-white/10">
                                    <x-field :label="__('Short name')" icon="tag"><input type="text" maxlength="16" required placeholder="ET" x-model="row.label" :name="`zones[${index}][label]`"></x-field>
                                    <x-field :label="__('Timezone')" icon="globe">
                                        <select required x-model="row.timezone" :name="`zones[${index}][timezone]`">
                                            @foreach ($zoneOptions as $id => $name)
                                                <option value="{{ $id }}">{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </x-field>
                                    <button type="button" class="btn-ghost btn-sm !text-status-cancelled" x-show="rows.length > 1 && index > 0" x-on:click="rows.splice(index, 1)">
                                        <x-icon name="trash" :size="14" /> {{ __('Remove') }}
                                    </button>
                                </div>
                            </template>
                            <x-input-error :messages="$errors->get('zones')" />
                            @foreach ($errors->get('zones.*') as $messages)
                                <x-input-error :messages="$messages" />
                            @endforeach
                            <button type="button" class="btn-ghost btn-sm" x-show="rows.length < max" x-on:click="rows.push({ label: '', timezone: 'America/Chicago' })">
                                <x-icon name="plus" :size="14" /> {{ __('Add a timezone') }}
                            </button>
                        </div>
                    </x-form-section>
                    <x-form-actions :submit="__('Save timezones')" />
                </form>
            </div>
        @endif
    </div>
</x-app-layout>
