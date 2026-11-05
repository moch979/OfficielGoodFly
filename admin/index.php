<?php

require __DIR__ . '/_init.php';
require __DIR__ . '/_layout.php';

$counts = [
    'طلبات جديدة' => (int) db()->query("SELECT COUNT(*) FROM bookings WHERE status = 'new'")->fetchColumn(),
    'كل الطلبات' => (int) db()->query('SELECT COUNT(*) FROM bookings')->fetchColumn(),
    'البرامج' => (int) db()->query('SELECT COUNT(*) FROM packages')->fetchColumn(),
    'المشتركون' => (int) db()->query('SELECT COUNT(*) FROM subscribers')->fetchColumn(),
];
$latest = rows('SELECT id, full_name, phone, trip_type, status, created_at FROM bookings ORDER BY id DESC LIMIT 6');

admin_header('لوحة التحكم', 'index.php');
if (s('password_changed', '0') !== '1') {
    echo '<div class="flash err">كلمة المرور ما زالت الافتراضية. غيّرها من صفحة الحساب.</div>';
}
echo '<p style="margin-top:0"><a class="btn" href="designer.php">فتح المصمم المرئي</a></p>';
echo '<div class="stats">';
foreach ($counts as $label => $count) {
    echo '<div class="card stat"><span>' . h($label) . '</span><strong>' . h($count) . '</strong></div>';
}
echo '</div>';
echo '<div class="card"><h2>آخر طلبات الحجز</h2>';
if (!$latest) {
    echo '<p class="intro">لا توجد طلبات بعد. أي نموذج يُرسل من الموقع يظهر هنا.</p>';
} else {
    echo '<table><thead><tr><th>الاسم</th><th>الهاتف</th><th>الرحلة</th><th>الحالة</th><th></th></tr></thead><tbody>';
    foreach ($latest as $row) {
        echo '<tr><td>' . h($row['full_name']) . '</td><td dir="ltr">' . h($row['phone']) . '</td><td>' . h($row['trip_type']) . '</td><td>' . h($row['status']) . '</td>';
        echo '<td><a class="btn small" href="booking.php?id=' . (int) $row['id'] . '">فتح</a></td></tr>';
    }
    echo '</tbody></table>';
}
echo '</div>';
admin_footer();
