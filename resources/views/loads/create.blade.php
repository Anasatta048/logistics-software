<x-app-layout>
    <x-slot name="header">{{ __('Add Load') }}</x-slot>

    <div class="form-page max-w-7xl">
        <div class="form-page-head">
            <span class="form-page-icon"><x-icon name="truck" :size="26" /></span>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-extrabold tracking-tight text-text-primary dark:text-white">{{ __('Book a new load') }}</h1>
                <p class="mt-0.5 text-sm text-text-muted">{{ __('Five quick steps — broker, carrier, route, schedule and notes. The summary updates as you type.') }}</p>
            </div>
            <a href="{{ route('loads.index') }}" class="btn-ghost shrink-0"><x-icon name="arrow-left" :size="15" /> {{ __('All loads') }}</a>
        </div>

        <form id="load-create-form" method="POST" action="{{ route('loads.store') }}" enctype="multipart/form-data" data-load-summary>
            @csrf

            <div class="grid grid-cols-1 items-start gap-5 lg:grid-cols-3">
                <div class="space-y-5 lg:col-span-2">
                    @include('loads.partials.form', ['load' => null])

                    <x-form-section :title="__('Attachments')" icon="upload" :description="__('Rate confirmation, BOL or any paperwork — at least one is required. Images and PDFs are compressed automatically.')" :step="6">
                        <div class="span-2"><x-attachment-dropzone />
                            <x-input-error class="mt-2" :messages="$errors->get('attachments')" />
                            <x-input-error class="mt-2" :messages="$errors->get('attachments.*')" />
                        </div>
                    </x-form-section>
                </div>

                <div class="lg:sticky lg:top-4">
                    @include('loads.partials.summary', ['load' => null])
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
