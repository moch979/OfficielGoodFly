<?php

const GF_JSON = true;
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'message' => 'Method not allowed'], 405);
}

$payload = read_json_body();
set_lang((string) ($payload['lang'] ?? default_lang()));

if (honeypot_tripped($payload)) {
    json_response(['ok' => true, 'message' => str_replace('{name}', '', s('modal_success')), 'whatsapp' => '']);
}

if (!hit_limit('booking', 6, 600)) {
    json_response(['ok' => false, 'message' => t('too_many')], 429);
}

$text = static function (string $key) use ($payload): string {
    $value = $payload[$key] ?? '';
    return is_scalar($value) ? trim(preg_replace('/\s+/u', ' ', (string) $value) ?? '') : '';
};

$name = $text('name');
$phone = $text('phone');
$tripType = $text('tripType');
$packageTitle = $text('packageTitle');
$date = $text('date');
$notes = is_scalar($payload['notes'] ?? '') ? trim((string) ($payload['notes'] ?? '')) : '';
$travelers = (int) ($payload['travelers'] ?? 1);

if ($name === '' || $phone === '') {
    json_response(['ok' => false, 'message' => t('name_phone_required')], 422);
}
if (mb_strlen($name) > 180 || mb_strlen($phone) > 40 || mb_strlen($tripType) > 180 || mb_strlen($packageTitle) > 255 || mb_strlen($notes) > 2000) {
    json_response(['ok' => false, 'message' => t('field_too_long')], 422);
}
if (!preg_match('/^\+?[\d\s\-().]{6,40}$/', $phone)) {
    json_response(['ok' => false, 'message' => t('phone_invalid')], 422);
}
if ($date !== '') {
    $parsed = DateTime::createFromFormat('!Y-m-d', $date);
    if (!$parsed || $parsed->format('Y-m-d') !== $date || $parsed < new DateTime('today')) {
        json_response(['ok' => false, 'message' => t('date_invalid')], 422);
    }
}
$travelers = max(1, min(99, $travelers));

$statement = db()->prepare('INSERT INTO bookings (full_name, phone, trip_type, travelers, travel_date, notes, package_title, lang) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
$statement->execute([$name, $phone, $tripType, $travelers, $date, $notes, $packageTitle, current_lang()]);
$bookingId = (int) db()->lastInsertId();

$lines = [
    s('wa_booking_intro', 'طلب جديد من موقع Good Fly Travel:'),
    'الاسم: ' . $name,
    'الهاتف: ' . $phone,
    'الرحلة: ' . $tripType,
    'الأفراد: ' . $travelers,
    'التاريخ: ' . ($date !== '' ? $date : '-'),
];
if ($packageTitle !== '') {
    $lines[] = 'البرنامج: ' . $packageTitle;
}
if ($notes !== '') {
    $lines[] = 'ملاحظات: ' . $notes;
}
$lines[] = 'اللغة: ' . languages()[current_lang()]['name'];

$whatsapp = flag('wa_enabled') && wa_number() !== '' ? wa_link(implode("\n", $lines)) : '';
$message = str_replace('{name}', $name, s('modal_success'));
$body = json_encode(['ok' => true, 'message' => $message, 'whatsapp' => $whatsapp], JSON_UNESCAPED_UNICODE);

// Answer the visitor first; the notification email can take seconds over SMTP.
ignore_user_abort(true);
header('Content-Type: application/json; charset=utf-8');
header('Content-Length: ' . strlen($body));
header('Connection: close');
echo $body;
while (ob_get_level() > 0) {
    ob_end_flush();
}
flush();
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}

notify_new_booking($bookingId, $lines);
