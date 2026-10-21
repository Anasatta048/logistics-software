<?php

namespace App\Http\Middleware;

use App\Services\Demo\DemoService;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Inside a demo sandbox, keeps the visitor to harmless actions: nothing that
 * emails, changes accounts, billing or settings, imports or exports data, or
 * stores large files. Real workspaces and the super admin are not affected.
 */
final class EnforceDemo
{
    public function __construct(private readonly TenantContext $context, private readonly DemoService $demo)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->context->tenant();

        if (! $tenant?->is_demo || $request->user()?->is_super_admin) {
            return $next($request);
        }

        $refuse = fn (string $message): Response => $request->expectsJson()
            ? response()->json(['message' => $message], 403)
            : back()->with('error', $message);

        if ($request->routeIs(...config('demo.hidden'))) {
            return $request->expectsJson() ? response()->json(['message' => 'Not part of the demo.'], 403) : redirect()->route('dashboard')->with('error', 'That page is not part of the demo.');
        }

        if (! $request->isMethodSafe() && ! $request->routeIs('logout') && $request->routeIs(...config('demo.blocked'))) {
            return $refuse('That is switched off in the demo. Request access to use it for real.');
        }

        $limit = (int) config('demo.max_upload_kb') * 1024;

        foreach ($this->files($request->allFiles()) as $file) {
            if ($file->getSize() > $limit) {
                return $refuse('Demo uploads are limited to '.config('demo.max_upload_kb').' KB per file.');
            }
        }

        if ($request->allFiles() !== [] && $this->demo->attachmentCapReached($tenant)) {
            return $refuse('This demo has reached its document limit.');
        }

        return $next($request);
    }

    /** @return iterable<\Illuminate\Http\UploadedFile> */
    private function files(array $files): iterable
    {
        foreach ($files as $file) {
            if (is_array($file)) {
                yield from $this->files($file);
            } elseif ($file !== null) {
                yield $file;
            }
        }
    }
}
