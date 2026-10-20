@props(['load'])
@can('cancel', $load)
    @if ($load->isCancellable())
        <x-modal name="cancel-load" maxWidth="sm">
            <form method="POST" action="{{ route('loads.cancel', $load) }}" class="p-6 space-y-4">
                @csrf
                <h4 class="text-base font-semibold text-status-cancelled">{{ __('Cancel Load') }}</h4>
                <p class="text-sm text-text-muted">
                    {{ __('This will mark :ref as cancelled. This cannot be undone.', ['ref' => $load->reference_number]) }}
                </p>
                <div>
                    <x-input-label for="reason" :value="__('Cancellation Reason')" />
                    <x-text-input id="reason" name="reason" type="text" class="mt-1 block w-full" required />
                    <x-input-error class="mt-2" :messages="$errors->get('reason')" />
                </div>
                <div class="flex items-center justify-end gap-3 pt-2">
                    <x-button type="button" variant="ghost" x-on:click="$dispatch('close-modal', 'cancel-load')">
                        {{ __('Keep Load') }}
                    </x-button>
                    <x-button type="submit" variant="danger">{{ __('Cancel Load') }}</x-button>
                </div>
            </form>
        </x-modal>
    @endif
@endcan
