<?php

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'message' => 'Method not allowed'], 405);
}

if (!rate_limit('subscribe', 6, 600)) {
    json_response(['ok' => false, 'message' => 'حاول بعد قليل.'], 429);
}

$email = trim((string) ($_POST['email'] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['ok' => false, 'message' => 'أدخل بريداً إلكترونياً صحيحاً.'], 422);
}

$statement = db()->prepare('INSERT IGNORE INTO subscribers (email) VALUES (?)');
$statement->execute([$email]);

json_response(['ok' => true, 'message' => s('footer_news_success')]);
