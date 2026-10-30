<?php

return [
    'userApp' => [
        'latestVersion' => env('USER_APP_LATEST_VERSION', '1.0.0'),
        'minimumVersion' => env('USER_APP_MINIMUM_VERSION', '1.0.0'),
        'forceUpdate' => env('USER_APP_FORCE_UPDATE', false),
        'updateUrl' => env('USER_APP_UPDATE_URL', ''),
    ],

    'partnerApp' => [
        'latestVersion' => env('PARTNER_APP_LATEST_VERSION', '1.0.0'),
        'minimumVersion' => env('PARTNER_APP_MINIMUM_VERSION', '1.0.0'),
        'forceUpdate' => env('PARTNER_APP_FORCE_UPDATE', false),
        'updateUrl' => env('PARTNER_APP_UPDATE_URL', ''),
    ],
];
