<?php

require __DIR__ . '/_init.php';
require __DIR__ . '/_layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (($_POST['action'] ?? '') === 'delete') {
        db()->prepare('DELETE FROM bookings WHERE id = ?')->execute([(int) $_POST['id']]);
        flash_set('ok', 'تم حذف الطلب.');
    }
    redirect('bookings.php');
}

$status = (string) ($_GET['status'] ?? 'all');
$allowed = ['all', 'new', 'contacted', 'closed'];
if (!in_array($status, $allowed, true)) {
    $status = 'all';
}
$sql = 'SELECT * FROM bookings';
$params = [];
if ($status !== 'all') {
    $sql .= ' WHERE status = ?';
    $params[] = $status;
}
$sql .= ' ORDER BY id DESC';
$list = rows($sql, $params);
$labels = ['new' => 'جديد', 'contacted' => 'تم التواصل', 'closed' => 'مغلق', 'all' => 'الكل'];

admin_header('طلبات الحجز', 'bookings.php');
echo '<div class="tabs">';
foreach ($labels as $key => $label) {
    $class = $key === $status ? ' class="active"' : '';
    echo '<a' . $class . ' href="bookings.php?status=' . h($key) . '">' . h($label) . '</a>';
}
echo '</div><div class="card" style="overflow:auto"><table><thead><tr><th>#</th><th>الاسم</th><th>الهاتف</th><th>الرحلة</th><th>الأفراد</th><th>التاريخ</th><th>الحالة</th><th>الاستلام</th><th></th></tr></thead><tbody>';
foreach ($list as $row) {
    echo '<tr><td>' . (int) $row['id'] . '</td><td>' . h($row['full_name']) . '</td><td dir="ltr">' . h($row['phone']) . '</td><td>' . h($row['trip_type']) . '</td>';
    echo '<td>' . h($row['travelers']) . '</td><td>' . h($row['travel_date']) . '</td><td>' . h($labels[$row['status']] ?? $row['status']) . '</td><td>' . h($row['created_at']) . '</td>';
    echo '<td class="actions"><a class="btn small" href="booking.php?id=' . (int) $row['id'] . '">تفاصيل</a>';
    echo '<form method="post" onsubmit="return confirm(\'حذف الطلب؟\')">' . csrf_field() . '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' . (int) $row['id'] . '"><button class="btn small danger">حذف</button></form></td></tr>';
}
if (!$list) {
    echo '<tr><td colspan="9">لا توجد طلبات في هذا التصنيف.</td></tr>';
}
echo '</tbody></table></div>';
admin_footer();
