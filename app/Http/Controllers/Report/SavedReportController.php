<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\SavedReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** A person's own saved report views and the emails they ask for. */
final class SavedReportController extends Controller
{
    private const MAX_PER_USER = 25;

    public function index(Request $request): View
    {
        return view('reports.saved', [
            'saved' => SavedReport::query()->where('user_id', $request->user()->id)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'report' => ['required', 'string'],
            'name' => ['required', 'string', 'max:80'],
            'filters' => ['nullable', 'array'],
            'filters.*' => ['nullable', 'string', 'max:100'],
            ...$this->scheduleRules(),
        ]);

        $this->assertReportAllowed($data['report']);

        if (SavedReport::query()->where('user_id', $request->user()->id)->count() >= self::MAX_PER_USER) {
            throw ValidationException::withMessages(['name' => 'You can keep up to '.self::MAX_PER_USER.' saved reports. Remove one first.']);
        }

        SavedReport::create([
            'user_id' => $request->user()->id,
            'report' => $data['report'],
            'name' => $data['name'],
            'filters' => $this->cleanFilters($data['filters'] ?? []),
            ...$this->scheduleData($request, $data),
        ]);

        return back()->with('success', "Saved “{$data['name']}”.");
    }

    public function update(Request $request, SavedReport $savedReport): RedirectResponse
    {
        abort_unless($savedReport->user_id === $request->user()->id, 404);

        $data = $request->validate(['name' => ['required', 'string', 'max:80'], ...$this->scheduleRules()]);
        $savedReport->update(['name' => $data['name'], ...$this->scheduleData($request, $data)]);

        return back()->with('success', 'Saved.');
    }

    public function destroy(Request $request, SavedReport $savedReport): RedirectResponse
    {
        abort_unless($savedReport->user_id === $request->user()->id, 404);
        $savedReport->delete();

        return back()->with('success', 'Removed.');
    }

    /** @return array<string, mixed> */
    private function scheduleRules(): array
    {
        return [
            'schedule' => ['nullable', Rule::in(['none', 'weekly', 'monthly'])],
            'schedule_day' => ['nullable', 'integer', 'min:1', 'max:28'],
            'format' => ['nullable', Rule::in(['csv', 'pdf'])],
            'recipients' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function scheduleData(Request $request, array $data): array
    {
        $schedule = $data['schedule'] ?? 'none';
        $recipients = collect(preg_split('/[\s,;]+/', (string) ($data['recipients'] ?? '')))->filter()->unique()->values();

        if ($schedule !== 'none') {
            $bad = $recipients->first(fn ($e) => ! filter_var($e, FILTER_VALIDATE_EMAIL));
            if ($recipients->isEmpty() || $bad || $recipients->count() > 5) {
                throw ValidationException::withMessages(['recipients' => 'Give one to five valid email addresses.']);
            }
        }

        return [
            'schedule' => $schedule,
            'schedule_day' => $schedule === 'weekly' ? min(7, max(1, (int) ($data['schedule_day'] ?? 1))) : (int) ($data['schedule_day'] ?? 1),
            'format' => $data['format'] ?? 'csv',
            'recipients' => $recipients->implode(', ') ?: $request->user()->email,
        ];
    }

    /** Only real report pages the person may open can be saved. */
    private function assertReportAllowed(string $route): void
    {
        abort_unless(Str::startsWith($route, 'reports.') && $route !== 'reports.index' && Route::has($route), 422, 'Unknown report.');

        $ability = 'reports.'.Str::camel(Str::after($route, 'reports.'));
        abort_unless(Gate::allows($ability), 403);
    }

    /**
     * Keep the filters, never the paging or export switches.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function cleanFilters(array $filters): array
    {
        return array_filter(
            array_diff_key($filters, array_flip(['export', 'page', 'section', '_token'])),
            fn ($v) => $v !== null && $v !== '',
        );
    }
}
