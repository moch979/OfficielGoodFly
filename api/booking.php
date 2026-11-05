<?php

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'message' => 'Method not allowed'], 405);
}

if (!rate_limit('booking', 8, 600)) {
    json_response(['ok' => false, 'message' => 'تم إرسال طلبات كثيرة. حاول بعد قليل.'], 429);
}

$payload = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$name = trim((string) ($payload['name'] ?? ''));
$phone = trim((string) ($payload['phone'] ?? ''));
$tripType = trim((string) ($payload['tripType'] ?? ''));
$travelers = max(1, (int) ($payload['travelers'] ?? 1));
$date = trim((string) ($payload['date'] ?? ''));
$notes = trim((string) ($payload['notes'] ?? ''));
$packageTitle = trim((string) ($payload['packageTitle'] ?? ''));

if ($name === '' || $phone === '') {
    json_response(['ok' => false, 'message' => 'الاسم ورقم الهاتف مطلوبان.'], 422);
}
if (mb_strlen($name) > 180 || mb_strlen($phone) > 80 || mb_strlen($notes) > 4000) {
    json_response(['ok' => false, 'message' => 'أحد الحقول أطول من المسموح.'], 422);
}

$statement = db()->prepare('INSERT INTO bookings (full_name, phone, trip_type, travelers, travel_date, notes, package_title) VALUES (?, ?, ?, ?, ?, ?, ?)');
$statement->execute([$name, $phone, $tripType, $travelers, $date, $notes, $packageTitle]);

$lines = [
    s('wa_booking_intro', 'طلب جديد من موقع Good Fly Travel:'),
    'الاسم: ' . $name,
    'الهاتف: ' . $phone,
    'الرحلة: ' . $tripType,
    'الأفراد: ' . $travelers,
    'التاريخ: ' . $date,
    'البرنامج: ' . $packageTitle,
    'ملاحظات: ' . $notes,
];
$whatsapp = flag('wa_enabled') ? wa_link(implode("\n", $lines)) : '';
$message = str_replace('{name}', $name, s('modal_success'));

json_response(['ok' => true, 'message' => $message, 'whatsapp' => $whatsapp]);
