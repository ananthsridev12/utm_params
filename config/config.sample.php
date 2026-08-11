<?php
/**
 * Copy this file to config/config.php and fill in your own values.
 * config/config.php is git-ignored -- never commit real credentials.
 */
return [
    'db' => [
        'host'    => '127.0.0.1',
        'name'    => 'utm_taxonomy',
        'user'    => 'db_user',
        'pass'    => 'db_password',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        // Full base URL of the app, no trailing slash. E.g. https://track.example.com
        'url'          => 'http://localhost:8000',
        'session_name' => 'utm_app_session',
        // Set to false in production.
        'debug'        => true,
        // Force HTTPS-only cookies. Turn on once the site is served over HTTPS.
        'secure_cookies' => false,
    ],
];
