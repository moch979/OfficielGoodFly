<?php

function send_security_headers(): void
{
    if (headers_sent() || PHP_SAPI === 'cli') {
        return;
    }
    header_remove('X-Powered-By');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: SAMEORIGIN');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    if (is_https()) {
        header('Strict-Transport-Security: max-age=31536000');
    }
}

function start_session(): void
{
    if (PHP_SAPI === 'cli' || session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('GFSESSID');
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => is_https(),
        'path' => base_path() === '' ? '/' : base_path() . '/',
    ]);
    ini_set('session.use_strict_mode', '1');
    session_start();
}

/**
 * Counts an action for this IP in the database and reports whether it is still
 * within $max per $windowSeconds. Session-based counters are useless against
 * bots that drop cookies, so the client IP is the key.
 */
function hit_limit(string $bucket, int $max, int $windowSeconds, ?string $key = null): bool
{
    $key = $key ?? client_ip();
    $since = date('Y-m-d H:i:s', time() - $windowSeconds);
    $count = db()->prepare('SELECT COUNT(*) FROM rate_hits WHERE bucket = ? AND hit_key = ? AND created_at > ?');
    $count->execute([$bucket, $key, $since]);
    if ((int) $count->fetchColumn() >= $max) {
        return false;
    }
    db()->prepare('INSERT INTO rate_hits (bucket, hit_key, created_at) VALUES (?, ?, ?)')->execute([$bucket, $key, date('Y-m-d H:i:s')]);
    if (random_int(1, 50) === 1) {
        db()->prepare('DELETE FROM rate_hits WHERE created_at < ?')->execute([date('Y-m-d H:i:s', time() - 86400)]);
    }
    return true;
}

function limit_reached(string $bucket, int $max, int $windowSeconds, ?string $key = null): bool
{
    $key = $key ?? client_ip();
    $statement = db()->prepare('SELECT COUNT(*) FROM rate_hits WHERE bucket = ? AND hit_key = ? AND created_at > ?');
    $statement->execute([$bucket, $key, date('Y-m-d H:i:s', time() - $windowSeconds)]);
    return (int) $statement->fetchColumn() >= $max;
}

function clear_hits(string $bucket, ?string $key = null): void
{
    db()->prepare('DELETE FROM rate_hits WHERE bucket = ? AND hit_key = ?')->execute([$bucket, $key ?? client_ip()]);
}

const LOGIN_MAX_FAILS = 5;
const LOGIN_WINDOW = 900;

function login_locked(string $username): bool
{
    return limit_reached('login_ip', LOGIN_MAX_FAILS, LOGIN_WINDOW)
        || limit_reached('login_user', LOGIN_MAX_FAILS * 2, LOGIN_WINDOW, mb_strtolower($username));
}

function login_failed(string $username): void
{
    hit_limit('login_ip', PHP_INT_MAX, LOGIN_WINDOW);
    hit_limit('login_user', PHP_INT_MAX, LOGIN_WINDOW, mb_strtolower($username));
}

function login_succeeded(string $username): void
{
    clear_hits('login_ip');
    clear_hits('login_user', mb_strtolower($username));
}

/** Bots fill every field; humans never see the "website" input. */
function honeypot_tripped(array $payload): bool
{
    return trim((string) ($payload['website'] ?? '')) !== '';
}

/** JSON-only endpoints cannot be hit by a cross-site HTML form without a CORS preflight. */
function read_json_body(): array
{
    $type = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
    if (strpos($type, 'application/json') !== 0) {
        json_response(['ok' => false, 'message' => t('bad_request')], 415);
    }
    $raw = file_get_contents('php://input') ?: '';
    if (strlen($raw) > 20000) {
        json_response(['ok' => false, 'message' => t('field_too_long')], 413);
    }
    $payload = json_decode($raw, true);
    if (!is_array($payload)) {
        json_response(['ok' => false, 'message' => t('bad_request')], 400);
    }
    return $payload;
}
