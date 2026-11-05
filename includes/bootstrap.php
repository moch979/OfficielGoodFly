<?php

$gfConfig = require dirname(__DIR__) . '/config.php';
date_default_timezone_set($gfConfig['timezone'] ?? 'Africa/Casablanca');
unset($gfConfig);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/i18n.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/images.php';
require_once __DIR__ . '/migrations.php';
require_once __DIR__ . '/design.php';

register_error_handling();
send_security_headers();
start_session();
boot();
