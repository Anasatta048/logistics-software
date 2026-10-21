<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/** Terms, Privacy and Refund pages: public, linked from the landing footer and the sign-in page. */
final class LegalController extends Controller
{
    public const PAGES = [
        'terms' => 'Terms of Service',
        'privacy' => 'Privacy Policy',
        'refund-policy' => 'Refund Policy',
    ];

    public function __invoke(string $page): View
    {
        abort_unless(isset(self::PAGES[$page]), 404);

        return view('legal.'.$page, [
            'title' => self::PAGES[$page],
            'legal' => config('saas.legal'),
            'supportEmail' => config('saas.support_email'),
            'updated' => '1 November 2026',
        ]);
    }
}
