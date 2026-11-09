<?php

require __DIR__ . '/_init.php';
require __DIR__ . '/_layout.php';

$id = (int) ($_GET['id'] ?? 0);
$statement = db()->prepare('SELECT * FROM bookings WHERE id = ?');
$statement->execute([$id]);
$booking = $statement->fetch();
if (!$booking) {
    flash_set('err', 'الطلب غير موجود.');
    redirect('bookings.php');
}

$labels = booking_status_labels();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $status = (string) ($_POST['status'] ?? 'new');
    if (!isset($labels[$status])) {
        $status = 'new';
    }
    $message = 'تم تحديث حالة الطلب.';
    $departureId = (int) $booking['departure_id'];
    if ($departureId > 0 && (int) $booking['seats_deducted'] === 0) {
        $tracked = db()->prepare('SELECT seats_left IS NOT NULL FROM package_departures WHERE id = ?');
        $tracked->execute([$departureId]);
        if ((int) $tracked->fetchColumn() !== 1) {
            $departureId = 0;
        }
    }
    db()->beginTransaction();
    try {
        db()->prepare('UPDATE bookings SET status = ? WHERE id = ?')->execute([$status, $id]);
        if ($departureId > 0 && $status === 'confirmed' && (int) $booking['seats_deducted'] === 0) {
            db()->prepare('UPDATE package_departures SET seats_left = GREATEST(0, seats_left - ?) WHERE id = ?')->execute([(int) $booking['travelers'], $departureId]);
            db()->prepare('UPDATE bookings SET seats_deducted = 1 WHERE id = ?')->execute([$id]);
            $message = 'تم تأكيد الطلب وخُصم ' . (int) $booking['travelers'] . ' مقعد من الموعد.';
        } elseif ($departureId > 0 && $status !== 'confirmed' && (int) $booking['seats_deducted'] === 1) {
            db()->prepare('UPDATE package_departures SET seats_left = seats_left + ? WHERE id = ?')->execute([(int) $booking['travelers'], $departureId]);
            db()->prepare('UPDATE bookings SET seats_deducted = 0 WHERE id = ?')->execute([$id]);
            $message = 'تم تحديث الحالة وأُعيد ' . (int) $booking['travelers'] . ' مقعد إلى الموعد.';
        }
        db()->commit();
        flash_set('ok', $message);
    } catch (Throwable $e) {
        db()->rollBack();
        flash_set('err', 'تعذر تحديث الحالة.');
        log_error('booking status #' . $id . ': ' . $e->getMessage());
    }
    redirect('booking.php?id=' . $id);
}

$package = null;
if ((int) $booking['package_id'] > 0) {
    $statement = db()->prepare('SELECT id, slug, title FROM packages WHERE id = ?');
    $statement->execute([(int) $booking['package_id']]);
    $package = $statement->fetch() ?: null;
}
$seatsLeft = null;
if ((int) $booking['departure_id'] > 0) {
    $statement = db()->prepare('SELECT seats_left FROM package_departures WHERE id = ?');
    $statement->execute([(int) $booking['departure_id']]);
    $seatsLeft = $statement->fetchColumn();
}
$roomNames = admin_room_names();
$phoneDigits = preg_replace('/\D+/', '', $booking['phone']);

admin_header('طلب رقم ' . $id, 'bookings.php');
echo '<div class="card">';
echo '<p><strong>الاسم:</strong> ' . h($booking['full_name']) . '</p>';
echo '<p><strong>الهاتف:</strong> <span dir="ltr">' . h($booking['phone']) . '</span></p>';
echo '<p class="actions"><a class="btn small" style="background:#059669;color:#fff" target="_blank" rel="noopener" href="https://wa.me/' . h($phoneDigits) . '"><i class="fa-brands fa-whatsapp"></i> مراسلة عبر واتساب</a>';
echo '<a class="btn small ghost" href="tel:' . h(preg_replace('/[^\d+]/', '', $booking['phone'])) . '"><i class="fa-solid fa-phone"></i> اتصال</a></p>';
echo '<p><strong>نوع الرحلة:</strong> ' . h($booking['trip_type']) . '</p>';
echo '<p><strong>البرنامج:</strong> ' . h($booking['package_title'] ?: '-');
if ($package) {
    echo ' <a class="btn small ghost" href="packages.php?edit=' . (int) $package['id'] . '">فتح البرنامج</a>';
}
echo '</p>';
echo '<p><strong>عدد المسافرين:</strong> ' . h($booking['travelers']) . '</p>';
if ($booking['departure_date'] !== '') {
    echo '<p><strong>موعد الانطلاق:</strong> <span dir="ltr">' . h($booking['departure_date']) . '</span>';
    if ($seatsLeft !== null && $seatsLeft !== false && $seatsLeft !== '') {
        echo ' <span class="hint">(المقاعد المتبقية الآن: ' . (int) $seatsLeft . ')</span>';
    }
    echo '</p>';
} else {
    echo '<p><strong>تاريخ السفر المطلوب:</strong> ' . h($booking['travel_date'] ?: '-') . '</p>';
}
if ($booking['hotel_label'] !== '') {
    echo '<p><strong>الفندق:</strong> ' . h($booking['hotel_label']) . '</p>';
}
if ($booking['room_type'] !== '') {
    $price = (int) $booking['room_price'];
    echo '<p><strong>نوع الغرفة:</strong> ' . h($roomNames[$booking['room_type']] ?? $booking['room_type']);
    if ($price > 0) {
        echo ' — ' . number_format($price) . ' درهم للشخص، التقدير: <strong>' . number_format($price * (int) $booking['travelers']) . ' درهم</strong> <span class="hint">(بالسعر وقت الطلب)</span>';
    }
    echo '</p>';
}
echo '<p><strong>لغة الزائر:</strong> ' . h(languages()[$booking['lang']]['name'] ?? $booking['lang']) . '</p>';
echo '<p><strong>الملاحظات:</strong><br>' . nl2br(h($booking['notes'] ?: '-')) . '</p>';
echo '<p><strong>وقت الإرسال:</strong> ' . h($booking['created_at']) . '</p>';
echo '<form method="post">' . csrf_field() . '<label for="status">حالة الطلب</label><select id="status" name="status">';
foreach ($labels as $value => $label) {
    $selected = $booking['status'] === $value ? ' selected' : '';
    echo '<option value="' . h($value) . '"' . $selected . '>' . h($label) . '</option>';
}
echo '</select>';
if ((int) $booking['departure_id'] > 0 && ($seatsLeft !== null && $seatsLeft !== false || (int) $booking['seats_deducted'] === 1)) {
    echo '<div class="hint">' . ((int) $booking['seats_deducted'] === 1 ? 'مقاعد هذا الطلب مخصومة من الموعد. تغيير الحالة من «مؤكد» يعيدها.' : 'اختيار «مؤكد» يخصم مقاعد هذا الطلب من الموعد تلقائياً.') . '</div>';
}
echo '<div style="margin-top:14px" class="actions"><button class="btn">حفظ الحالة</button><a class="btn ghost" href="bookings.php">كل الطلبات</a></div></form></div>';
admin_footer();
