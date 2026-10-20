<x-app-layout>
    <x-slot name="header">{{ __('Access Request') }}</x-slot>

    <x-form-page :title="$req->company_name" :subtitle="__('Received :when', ['when' => $req->created_at->format('d M Y, H:i')])" icon="inbox" :back="route('super-admin.requests.index')" :back-label="__('All requests')">
        @if (session('success'))<x-alert variant="success">{{ session('success') }}</x-alert>@endif

        <x-form-section :title="__('Applicant')" icon="user">
            <dl class="grid gap-4 text-sm sm:grid-cols-2">
                @foreach ([
                    [__('Contact'), $req->contact_name],
                    [__('Email'), $req->email],
                    [__('Phone'), $req->phone ?: '—'],
                    [__('Fleet size'), $req->fleet_size ?: '—'],
                    [__('Plan interested in'), $req->plan?->name ?? '—'],
                    [__('Status'), $req->status->label()],
                ] as [$l, $val])
                    <div><dt class="text-xs font-semibold uppercase tracking-wider text-text-muted">{{ $l }}</dt><dd class="mt-0.5 font-medium text-text-primary dark:text-white">{{ $val }}</dd></div>
                @endforeach
            </dl>
            @if ($req->message)
                <div class="mt-4 border-l-2 border-brand-primary bg-bg p-3 text-sm dark:bg-white/5">{{ $req->message }}</div>
            @endif
        </x-form-section>

        <x-form-section :title="__('Decision')" icon="check-circle" :description="$req->reviewed_at ? __('Last reviewed by :who on :when', ['who' => $req->reviewer?->name ?? '—', 'when' => $req->reviewed_at->format('d M Y')]) : null">
            <form method="POST" action="{{ route('super-admin.requests.update', $req) }}" class="space-y-4">
                @csrf @method('PUT')
                <x-field name="admin_notes" :label="__('Internal notes')" :hint="__('Only you can see these.')">
                    <textarea id="admin_notes" name="admin_notes" rows="3">{{ old('admin_notes', $req->admin_notes) }}</textarea>
                </x-field>
                <div class="flex flex-wrap gap-2">
                    <button name="status" value="approved" class="btn-primary"><x-icon name="check" :size="16" /> {{ __('Approve') }}</button>
                    <button name="status" value="rejected" class="btn-danger"><x-icon name="x" :size="16" /> {{ __('Reject') }}</button>
                    <button name="status" value="pending" class="btn-ghost">{{ __('Back to pending') }}</button>
                </div>
            </form>
        </x-form-section>

        <form method="POST" action="{{ route('super-admin.requests.destroy', $req) }}">
            @csrf @method('DELETE')
            <button class="btn-ghost btn-sm !text-status-cancelled" data-confirm="Delete this request permanently?"><x-icon name="trash" :size="14" /> {{ __('Delete request') }}</button>
        </form>
    </x-form-page>
</x-app-layout>
