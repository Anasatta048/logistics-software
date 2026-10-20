<?php

namespace App\Services\Report;

use App\Mail\ScheduledReportMail;
use App\Models\SavedReport;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sends the saved reports that are due. Each report is produced by calling its
 * own download URL as the person who saved it, through the normal HTTP stack,
 * so it contains exactly what that person is allowed to see and what the
 * on-screen export would give them: one code path, no second report engine.
 */
final class ScheduledReportRunner
{
    public function __construct(private readonly TenantContext $context)
    {
    }

    public function isDue(SavedReport $report, Carbon $now): bool
    {
        if ($report->schedule === 'none' || $report->recipientList() === []) {
            return false;
        }

        if ($report->last_sent_at?->isSameDay($now)) {
            return false;
        }

        return match ($report->schedule) {
            'weekly' => $now->isoWeekday() === (int) $report->schedule_day,
            'monthly' => $now->day === min((int) $report->schedule_day, $now->daysInMonth),
            default => false,
        };
    }

    /** @return int how many reports were mailed */
    public function runDue(?Carbon $now = null): int
    {
        $now ??= now();
        $sent = 0;

        SavedReport::withoutGlobalScopes()->where('schedule', '!=', 'none')->orderBy('id')->each(function (SavedReport $report) use ($now, &$sent): void {
            $tenant = Tenant::query()->find($report->tenant_id);

            if ($tenant === null || ! $tenant->isUsable() || ! $this->context->run($tenant, fn () => $this->isDue($report, $now))) {
                return;
            }

            try {
                $this->context->run($tenant, fn () => $this->send($report));
                $sent++;
            } catch (\Throwable $e) {
                report($e);
            }
        });

        return $sent;
    }

    public function send(SavedReport $report): void
    {
        [$content, $mime, $extension] = $this->render($report);

        Mail::to($report->recipientList())->send(new ScheduledReportMail(
            $report->name,
            $content,
            str($report->name)->slug().'-'.now()->format('Y-m-d').'.'.$extension,
            $mime,
        ));

        $report->forceFill(['last_sent_at' => now()])->save();
    }

    /** @return array{0: string, 1: string, 2: string} content, mime type, file extension */
    public function render(SavedReport $report): array
    {
        $user = User::withoutGlobalScopes()->find($report->user_id);
        abort_if($user === null || ! $user->isActive(), 403, 'The person who saved this report can no longer sign in.');

        $format = $report->format === 'pdf' ? 'pdf' : 'csv';
        $url = route($report->report, array_merge($report->filters ?? [], ['export' => $format]));

        Auth::guard('web')->setUser($user);

        try {
            $response = app(Kernel::class)->handle(Request::create($url, 'GET'));
        } finally {
            Auth::guard('web')->forgetUser();
            $this->context->forget();
        }

        abort_unless($response->getStatusCode() === 200, 403, 'The report could not be produced for this person.');

        if ($response instanceof StreamedResponse) {
            ob_start();
            $response->sendContent();
            $content = (string) ob_get_clean();
        } else {
            $content = (string) $response->getContent();
        }

        return [$content, $format === 'pdf' ? 'application/pdf' : 'text/csv', $format];
    }
}
