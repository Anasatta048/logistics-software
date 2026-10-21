<?php

namespace App\Http\Controllers\Demo;

use App\Http\Controllers\Controller;
use App\Services\Demo\DemoLimitException;
use App\Services\Demo\DemoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Throwable;

/** "Try the live demo": hands a visitor a private one-hour sandbox. */
final class DemoController extends Controller
{
    public function start(Request $request, DemoService $demo): RedirectResponse
    {
        abort_unless(config('demo.enabled'), 404);

        $back = fn (string $message): RedirectResponse => redirect()->to(route('home').'#demo')->withErrors(['demo' => $message]);

        // Bots fill every field and post at once; people do neither.
        $age = DemoService::tokenAgeSeconds($request->input('t'));

        if (filled($request->input('company_website')) || $age === null || $age < (int) config('demo.min_fill_seconds') || $age > 7200) {
            return $back('Please wait a second and press the demo button again.');
        }

        if (blank($request->userAgent())) {
            return $back('Your browser did not identify itself, so we could not start the demo.');
        }

        if (! $this->humanPassed($request)) {
            return $back('Could not confirm you are human. Please try again.');
        }

        try {
            $user = $demo->start((string) $request->ip(), $request->userAgent());
        } catch (DemoLimitException $e) {
            return $back($e->getMessage());
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    /** Cloudflare Turnstile, when keys are configured. */
    private function humanPassed(Request $request): bool
    {
        $secret = config('demo.turnstile.secret');

        if (! $secret) {
            return true;
        }

        try {
            return (bool) Http::asForm()->timeout(5)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => $secret,
                'response' => (string) $request->input('cf-turnstile-response'),
                'remoteip' => $request->ip(),
            ])->json('success');
        } catch (Throwable) {
            return false;
        }
    }
}
