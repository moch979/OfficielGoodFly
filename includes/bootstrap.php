<?php

date_default_timezone_set('Asia/Riyadh');

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/design.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

boot();
