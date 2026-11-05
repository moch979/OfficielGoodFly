<?php

require __DIR__ . '/_init.php';
require __DIR__ . '/_layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    db()->prepare('DELETE FROM subscribers WHERE id = ?')->execute([(int) ($_POST['id'] ?? 0)]);
    flash_set('ok', 'تم حذف المشترك.');
    redirect('subscribers.php');
}

$list = rows('SELECT * FROM subscribers ORDER BY id DESC');
admin_header('النشرة البريدية', 'subscribers.php');
echo '<div class="card" style="overflow:auto"><table><thead><tr><th>البريد</th><th>تاريخ الاشتراك</th><th></th></tr></thead><tbody>';
foreach ($list as $row) {
    echo '<tr><td>' . h($row['email']) . '</td><td>' . h($row['created_at']) . '</td><td>';
    echo '<form method="post" onsubmit="return confirm(\'حذف المشترك؟\')">' . csrf_field() . '<input type="hidden" name="id" value="' . (int) $row['id'] . '"><button class="btn small danger">حذف</button></form>';
    echo '</td></tr>';
}
if (!$list) {
    echo '<tr><td colspan="3">لا يوجد مشتركون بعد.</td></tr>';
}
echo '</tbody></table></div>';
admin_footer();
