<?php

const GF_JSON = true;
require_once dirname(__DIR__) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'message' => 'Method not allowed'], 405);
}

$payload = read_json_body();
set_lang((string) ($payload['lang'] ?? default_lang()));

if (honeypot_tripped($payload)) {
    json_response(['ok' => true, 'message' => s('footer_news_success')]);
}

if (!hit_limit('subscribe', 5, 600)) {
    json_response(['ok' => false, 'message' => t('too_many')], 429);
}

$email = trim((string) (is_scalar($payload['email'] ?? '') ? $payload['email'] : ''));
if (mb_strlen($email) > 180 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['ok' => false, 'message' => t('email_invalid')], 422);
}

db()->prepare('INSERT IGNORE INTO subscribers (email, lang) VALUES (?, ?)')->execute([mb_strtolower($email), current_lang()]);

json_response(['ok' => true, 'message' => s('footer_news_success')]);
