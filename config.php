<?php
/**
 * Local XAMPP defaults. On the live server, create config.local.php next to
 * this file (it is ignored by git and blocked from the web) and return only
 * the keys you want to override, for example:
 *
 *   <?php return ['user' => 'goodfly', 'pass' => '...', 'debug' => false];
 */
$config = [
    'host' => '127.0.0.1',
    'name' => 'goodfly_site',
    'user' => 'root',
    'pass' => 'root',
    'charset' => 'utf8mb4',
    'timezone' => 'Africa/Casablanca',
    'debug' => false,
];

$local = __DIR__ . '/config.local.php';
if (is_file($local)) {
    $overrides = require $local;
    if (is_array($overrides)) {
        $config = array_merge($config, $overrides);
    }
}

return $config;
