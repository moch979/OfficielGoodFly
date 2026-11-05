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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $status = (string) ($_POST['status'] ?? 'new');
    if (!in_array($status, ['new', 'contacted', 'closed'], true)) {
        $status = 'new';
    }
    db()->prepare('UPDATE bookings SET status = ? WHERE id = ?')->execute([$status, $id]);
    flash_set('ok', 'تم تحديث حالة الطلب.');
    redirect('booking.php?id=' . $id);
}

$labels = ['new' => 'جديد', 'contacted' => 'تم التواصل', 'closed' => 'مغلق'];
admin_header('طلب رقم ' . $id, 'bookings.php');
echo '<div class="card">';
echo '<p><strong>الاسم:</strong> ' . h($booking['full_name']) . '</p>';
echo '<p><strong>الهاتف:</strong> <span dir="ltr">' . h($booking['phone']) . '</span></p>';
echo '<p><strong>نوع الرحلة:</strong> ' . h($booking['trip_type']) . '</p>';
echo '<p><strong>البرنامج:</strong> ' . h($booking['package_title']) . '</p>';
echo '<p><strong>عدد المسافرين:</strong> ' . h($booking['travelers']) . '</p>';
echo '<p><strong>التاريخ:</strong> ' . h($booking['travel_date']) . '</p>';
echo '<p><strong>الملاحظات:</strong><br>' . nl2br(h($booking['notes'])) . '</p>';
echo '<p><strong>وقت الإرسال:</strong> ' . h($booking['created_at']) . '</p>';
echo '<form method="post">' . csrf_field();
echo '<label>حالة الطلب</label><select name="status">';
foreach ($labels as $value => $label) {
    $selected = $booking['status'] === $value ? ' selected' : '';
    echo '<option value="' . h($value) . '"' . $selected . '>' . h($label) . '</option>';
}
echo '</select><div style="margin-top:14px" class="actions"><button class="btn">حفظ الحالة</button><a class="btn ghost" href="bookings.php">كل الطلبات</a></div></form></div>';
admin_footer();
