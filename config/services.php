<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Maps
    |--------------------------------------------------------------------------
    |
    | One billing-enabled key powers both the browser Places/Map widget and the
    | server-side Routes API. Without a key the app falls back to StubMapService
    | (straight-line distance scaled by a road factor) so local work and tests
    | never hit the network. See App\Services\Map\MapService.
    |
    */

    'ghostscript' => [
        // Optional path to Ghostscript; PDFs are only compressed when it is available.
        'binary' => env('GHOSTSCRIPT_BINARY'),
    ],

    'google_maps' => [
        // Server key: Places, Geocoding, Routes. Never sent to the browser.
        'key' => env('GOOGLE_MAPS_API_KEY'),
        // Browser key: Maps JavaScript API only; restrict it to your domain (HTTP referrers).
        'browser_key' => env('GOOGLE_MAPS_BROWSER_KEY'),
    ],

    // google | geoapify. Blank = whichever has a key (Geoapify first).
    'map_provider' => env('MAP_PROVIDER'),

    /*
    | Geoapify: address autocomplete, truck routing and map tiles. `key` stays on
    | the server; `tiles_key` is shipped to the browser, so restrict it to your
    | domain in the Geoapify dashboard. Takes precedence over Google when set.
    */
    'geoapify' => [
        'key' => env('GEOAPIFY_API_KEY'),
        'tiles_key' => env('GEOAPIFY_TILES_KEY'),
    ],

];
