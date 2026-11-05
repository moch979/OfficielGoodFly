<?php

require __DIR__ . '/_init.php';
require __DIR__ . '/_layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (($_POST['action'] ?? '') === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $statement = db()->prepare('SELECT departure_id, travelers, seats_deducted FROM bookings WHERE id = ?');
        $statement->execute([$id]);
        $deleted = $statement->fetch();
        if ($deleted && (int) $deleted['seats_deducted'] === 1 && (int) $deleted['departure_id'] > 0) {
            db()->prepare('UPDATE package_departures SET seats_left = seats_left + ? WHERE id = ?')->execute([(int) $deleted['travelers'], (int) $deleted['departure_id']]);
        }
        db()->prepare('DELETE FROM bookings WHERE id = ?')->execute([$id]);
        flash_set('ok', 'تم حذف الطلب.' . ($deleted && (int) $deleted['seats_deducted'] === 1 ? ' أُعيدت مقاعده إلى الموعد.' : ''));
    }
    redirect('bookings.php?' . http_build_query(array_intersect_key($_GET, array_flip(['status', 'q', 'page']))));
}

$labels = ['all' => 'الكل'] + booking_status_labels();
$roomNames = ['double' => 'ثنائية', 'triple' => 'ثلاثية', 'quad' => 'رباعية'];
$status = (string) ($_GET['status'] ?? 'all');
if (!isset($labels[$status])) {
    $status = 'all';
}
$query = trim((string) ($_GET['q'] ?? ''));

$where = [];
$params = [];
if ($status !== 'all') {
    $where[] = 'status = ?';
    $params[] = $status;
}
if ($query !== '') {
    $where[] = '(full_name LIKE ? OR phone LIKE ? OR trip_type LIKE ? OR package_title LIKE ? OR notes LIKE ?)';
    $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $query) . '%';
    array_push($params, $like, $like, $like, $like, $like);
}
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

if (isset($_GET['export'])) {
    $list = rows('SELECT * FROM bookings' . $whereSql . ' ORDER BY id DESC', $params);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="goodfly-bookings-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['#', 'الاسم', 'الهاتف', 'نوع الرحلة', 'البرنامج', 'الأفراد', 'تاريخ السفر', 'موعد الانطلاق', 'نوع الغرفة', 'الملاحظات', 'اللغة', 'الحالة', 'وقت الإرسال']);
    foreach ($list as $row) {
        $cells = [$row['id'], $row['full_name'], $row['phone'], $row['trip_type'], $row['package_title'], $row['travelers'], $row['travel_date'], $row['departure_date'], $roomNames[$row['room_type']] ?? '', $row['notes'], $row['lang'], $labels[$row['status']] ?? $row['status'], $row['created_at']];
        fputcsv($out, array_map(static function ($cell) {
            $cell = (string) $cell;
            return preg_match('/^[=+\-@\t\r]/', $cell) ? "'" . $cell : $cell;
        }, $cells));
    }
    fclose($out);
    exit;
}

$perPage = 50;
$statement = db()->prepare('SELECT COUNT(*) FROM bookings' . $whereSql);
$statement->execute($params);
$total = (int) $statement->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$page = max(1, min($pages, (int) ($_GET['page'] ?? 1)));
$list = rows('SELECT * FROM bookings' . $whereSql . ' ORDER BY id DESC LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage), $params);

$link = static function (array $changes) use ($status, $query): string {
    return 'bookings.php?' . http_build_query(array_merge(['status' => $status, 'q' => $query], $changes));
};

admin_header('طلبات الحجز', 'bookings.php');
echo '<div class="tabs">';
foreach ($labels as $key => $label) {
    $class = $key === $status ? ' class="active"' : '';
    echo '<a' . $class . ' href="' . h($link(['status' => $key, 'page' => 1])) . '">' . h($label) . '</a>';
}
echo '</div>';
echo '<form class="card actions" method="get" style="gap:10px"><input type="hidden" name="status" value="' . h($status) . '">';
echo '<input type="search" name="q" value="' . h($query) . '" placeholder="بحث بالاسم أو الهاتف أو الرحلة" style="flex:1;min-width:220px">';
echo '<button class="btn secondary" type="submit">بحث</button>';
echo '<a class="btn ghost" href="' . h($link(['export' => 1])) . '"><i class="fa-solid fa-file-excel"></i> تصدير إلى Excel</a></form>';

echo '<div class="card" style="overflow:auto"><p class="hint">' . $total . ' طلب</p><table><thead><tr><th>#</th><th>الاسم</th><th>الهاتف</th><th>الرحلة</th><th>الأفراد</th><th>تاريخ السفر</th><th>اللغة</th><th>الحالة</th><th>الاستلام</th><th></th></tr></thead><tbody>';
foreach ($list as $row) {
    $wa = 'https://wa.me/' . preg_replace('/\D+/', '', $row['phone']);
    echo '<tr><td>' . (int) $row['id'] . '</td><td>' . h($row['full_name']) . '</td>';
    $trip = $row['package_title'] !== '' ? $row['package_title'] : $row['trip_type'];
    $room = $roomNames[$row['room_type']] ?? '';
    echo '<td dir="ltr"><a href="' . h($wa) . '" target="_blank" rel="noopener" title="واتساب">' . h($row['phone']) . '</a></td><td>' . h(mb_strimwidth($trip, 0, 50, '…')) . ($room !== '' ? '<div class="hint">غرفة ' . h($room) . '</div>' : '') . '</td>';
    echo '<td>' . h($row['travelers']) . '</td><td dir="ltr">' . h($row['departure_date'] !== '' ? $row['departure_date'] : $row['travel_date']) . '</td><td>' . h(strtoupper($row['lang'])) . '</td><td>' . h($labels[$row['status']] ?? $row['status']) . '</td><td>' . h($row['created_at']) . '</td>';
    echo '<td class="actions"><a class="btn small" href="booking.php?id=' . (int) $row['id'] . '">تفاصيل</a>' . delete_button((int) $row['id'], 'حذف الطلب نهائياً؟') . '</td></tr>';
}
if (!$list) {
    echo '<tr><td colspan="10">لا توجد طلبات مطابقة.</td></tr>';
}
echo '</tbody></table>';
if ($pages > 1) {
    echo '<div class="pager">';
    for ($i = 1; $i <= $pages; $i++) {
        echo $i === $page ? '<span>' . $i . '</span>' : '<a href="' . h($link(['page' => $i])) . '">' . $i . '</a>';
    }
    echo '</div>';
}
echo '</div>';
admin_footer();
