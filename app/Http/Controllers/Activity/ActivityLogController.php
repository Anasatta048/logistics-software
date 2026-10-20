<?php

namespace App\Http\Controllers\Activity;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Support\Timezones;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin-only: the readable audit trail of every module plus sign-ins/outs.
 */
final class ActivityLogController extends Controller
{
    public const ACTIONS = [
        'created' => 'Created',
        'updated' => 'Updated',
        'deleted' => 'Deleted',
        'restored' => 'Restored',
        'login' => 'Sign in',
        'logout' => 'Sign out',
        'login_failed' => 'Failed sign in',
    ];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'module' => ['nullable', 'string', 'max:64'],
            'action' => ['nullable', 'in:'.implode(',', array_keys(self::ACTIONS))],
            'user_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $logs = ActivityLog::query()
            ->when($filters['module'] ?? null, fn ($q, $v) => $q->where('module', $v))
            ->when($filters['action'] ?? null, fn ($q, $v) => $q->where('action', $v))
            ->when($filters['user_id'] ?? null, fn ($q, $v) => $q->where('user_id', $v))
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->where(
                fn ($w) => $w->where('description', 'like', "%{$v}%")->orWhere('subject_label', 'like', "%{$v}%")
            ))
            ->when($filters['from'] ?? null, fn ($q, $v) => $q->where('created_at', '>=', Timezones::parseUs($v.' 00:00:00')))
            ->when($filters['to'] ?? null, fn ($q, $v) => $q->where('created_at', '<=', Timezones::parseUs($v.' 23:59:59')))
            ->latest('created_at')
            ->latest('id')
            ->paginate(300)
            ->withQueryString();

        return view('activity-logs.index', [
            'logs' => $logs,
            'filters' => $filters,
            'actions' => self::ACTIONS,
            'modules' => ActivityLog::query()->distinct()->orderBy('module')->pluck('module'),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
