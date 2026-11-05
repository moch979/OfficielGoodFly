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

if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="goodfly-subscribers-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['email', 'lang', 'created_at']);
    foreach ($list as $row) {
        fputcsv($out, [$row['email'], $row['lang'], $row['created_at']]);
    }
    fclose($out);
    exit;
}

admin_header('النشرة البريدية', 'subscribers.php');
echo '<p class="actions"><a class="btn ghost" href="subscribers.php?export=1"><i class="fa-solid fa-file-excel"></i> تصدير القائمة</a><span class="hint">' . count($list) . ' مشترك</span></p>';
echo '<div class="card" style="overflow:auto"><table><thead><tr><th>البريد</th><th>اللغة</th><th>تاريخ الاشتراك</th><th></th></tr></thead><tbody>';
foreach ($list as $row) {
    echo '<tr><td dir="ltr">' . h($row['email']) . '</td><td>' . h(strtoupper($row['lang'])) . '</td><td>' . h($row['created_at']) . '</td><td>';
    echo delete_button((int) $row['id'], 'حذف المشترك؟');
    echo '</td></tr>';
}
if (!$list) {
    echo '<tr><td colspan="4">لا يوجد مشتركون بعد.</td></tr>';
}
echo '</tbody></table></div>';
admin_footer();
