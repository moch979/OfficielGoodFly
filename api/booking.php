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

$packageId = null;
$departureId = null;
$departureDate = '';
$hotelId = null;
$hotelLabel = '';
$roomType = '';
$roomPrice = 0;
$requestedPackage = (int) ($payload['packageId'] ?? 0);
if ($requestedPackage > 0) {
    $statement = db()->prepare('SELECT * FROM packages WHERE id = ? AND is_active = 1');
    $statement->execute([$requestedPackage]);
    $package = $statement->fetch();
    if ($package) {
        $packageId = (int) $package['id'];
        $packageTitle = $package['title'];

        $requestedHotel = (int) ($payload['hotelId'] ?? 0);
        if ($requestedHotel > 0) {
            $statement = db()->prepare('SELECT * FROM package_hotels WHERE id = ? AND package_id = ?');
            $statement->execute([$requestedHotel, $packageId]);
            $hotel = $statement->fetch();
            if ($hotel) {
                $hotelId = (int) $hotel['id'];
                $hotelLabel = str_limit(admin_hotel_label($hotel), 255);
                $prices = room_prices($hotel);
                $requestedRoom = $text('roomType');
                if ($requestedRoom !== '' && isset($prices[$requestedRoom])) {
                    $roomType = $requestedRoom;
                    $roomPrice = $prices[$requestedRoom];
                }
            }
        }

        $requestedDeparture = (int) ($payload['departureId'] ?? 0);
        if ($requestedDeparture > 0) {
            $statement = db()->prepare('SELECT * FROM package_departures WHERE id = ? AND package_id = ? AND depart_date >= CURDATE()');
            $statement->execute([$requestedDeparture, $packageId]);
            $departure = $statement->fetch();
            if (!$departure) {
                json_response(['ok' => false, 'message' => t('departure_invalid')], 422);
            }
            if ($departure['seats_left'] !== null && (int) $departure['seats_left'] < $travelers) {
                json_response(['ok' => false, 'message' => t('seats_not_enough')], 422);
            }
            $departureId = (int) $departure['id'];
            $departureDate = $departure['depart_date'] . ($departure['return_date'] ? ' → ' . $departure['return_date'] : '');
            $date = $departure['depart_date'];
        }
    }
}

$statement = db()->prepare('INSERT INTO bookings (full_name, phone, trip_type, travelers, travel_date, notes, package_title, lang, package_id, departure_id, departure_date, hotel_id, hotel_label, room_type, room_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
$statement->execute([$name, $phone, $tripType, $travelers, $date, $notes, $packageTitle, current_lang(), $packageId, $departureId, $departureDate, $hotelId, $hotelLabel, $roomType, $roomPrice]);
$bookingId = (int) db()->lastInsertId();

$roomNames = admin_room_names();
$lines = [
    s('wa_booking_intro', 'طلب جديد من موقع Good Fly Travel:'),
    'الاسم: ' . $name,
    'الهاتف: ' . $phone,
    'الرحلة: ' . $tripType,
    'الأفراد: ' . $travelers,
    'التاريخ: ' . ($departureDate !== '' ? $departureDate : ($date !== '' ? $date : '-')),
];
if ($packageTitle !== '') {
    $lines[] = 'البرنامج: ' . $packageTitle;
}
if ($hotelLabel !== '') {
    $lines[] = 'الفندق: ' . $hotelLabel;
}
if ($roomType !== '') {
    $lines[] = 'الغرفة: ' . $roomNames[$roomType] . ' — التقدير: ' . number_format($roomPrice * $travelers) . ' درهم';
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
