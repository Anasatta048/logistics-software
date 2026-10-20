<x-app-layout>
    <x-slot name="header">{{ $load->reference_number }}
        <span class="ml-2 align-middle"><x-status-badge :status="$load->status" /></span>
    </x-slot>

    <div class="form-page max-w-7xl space-y-5">
        <div class="form-page-head !mb-0 flex-wrap">
            <span class="form-page-icon"><x-icon name="truck" :size="26" /></span>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-extrabold tracking-tight text-text-primary dark:text-white">{{ __('Edit load') }} {{ $load->reference_number }}</h1>
                <p class="mt-0.5 text-sm text-text-muted">{{ __('Update the details, or move the load to its next step.') }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <x-load-status-select :load="$load" />
                @if (($next = $load->status->next()) && ! $next->isManual())
                    @can('advanceStatus', [$load, $next])
                        <x-status-advance :status="$next" x-data
                            x-on:click="$dispatch('open-modal', 'change-status')"
                            :label="__('Advance to :status', ['status' => $next->label()])" />
                    @endcan
                @endif
                @can('cancel', $load)
                    <button type="button" class="btn-danger btn-sm" x-data x-on:click="$dispatch('open-modal', 'cancel-load')">
                        <x-icon name="x-circle" :size="14" /> {{ __('Cancel load') }}
                    </button>
                @endcan
                <a href="{{ route('loads.index') }}" class="btn-ghost btn-sm"><x-icon name="arrow-left" :size="14" /> {{ __('All loads') }}</a>
            </div>
        </div>

        @if (session('success'))
            <x-alert variant="success">{{ session('success') }}</x-alert>
        @endif
        <form id="load-edit-form" method="POST" action="{{ route('loads.update', $load) }}" data-load-summary>
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">
                <div class="space-y-5 lg:col-span-2">
                    @include('loads.partials.form', ['load' => $load])
                </div>

                <div class="lg:sticky lg:top-6">
                    @include('loads.partials.summary', ['submitLabel' => __('Save Changes')])
                </div>
            </div>
        </form>

        <x-card class="space-y-6">
            @include('loads.partials.history', ['load' => $load])
            @include('loads.partials.attachments', ['load' => $load])
        </x-card>
    </div>

    @if (($next = $load->status->next()) && ! $next->isManual())
        @can('advanceStatus', [$load, $next])
            <x-modal name="change-status" maxWidth="sm">
                <form method="POST" action="{{ route('loads.status', $load) }}" class="p-6 space-y-4">
                    @csrf
                    <h4 class="text-base font-semibold text-text-primary dark:text-white">
                        {{ __('Advance to :status', ['status' => $next->label()]) }}
                    </h4>
                    <p class="text-sm text-text-muted">
                        {{ __('Move :ref from :from to :to.', ['ref' => $load->reference_number, 'from' => $load->status->label(), 'to' => $next->label()]) }}
                    </p>
                    <input type="hidden" name="status" value="{{ $next->value }}">
                    <div>
                        <x-input-label for="advance_reason" :value="__('Note (optional)')" />
                        <x-text-input id="advance_reason" name="reason" type="text" class="mt-1 block w-full" />
                        <x-input-error class="mt-2" :messages="$errors->get('status')" />
                    </div>
                    <div class="flex items-center justify-end gap-3 pt-2">
                        <x-button type="button" variant="ghost" x-on:click="$dispatch('close-modal', 'change-status')">
                            {{ __('Cancel') }}
                        </x-button>
                        <x-button type="submit" variant="primary">{{ __('Update Status') }}</x-button>
                    </div>
                </form>
            </x-modal>
        @endcan
    @endif

    @include('loads.partials.cancel-modal', ['load' => $load])
</x-app-layout>
