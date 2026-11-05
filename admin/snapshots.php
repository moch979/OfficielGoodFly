<?php

require __DIR__ . '/_init.php';
require __DIR__ . '/_layout.php';

if (isset($_GET['download'])) {
    $id = (int) $_GET['download'];
    $statement = db()->prepare('SELECT payload, created_at FROM snapshots WHERE id = ?');
    $statement->execute([$id]);
    $row = $statement->fetch();
    if ($row) {
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="goodfly-content-' . date('Y-m-d-His', strtotime($row['created_at'])) . '.json"');
        echo $row['payload'];
        exit;
    }
}
if (isset($_GET['sql'])) {
    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="goodfly-full-backup-' . date('Y-m-d-His') . '.sql"');
    stream_sql_dump();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string) ($_POST['action'] ?? '');
    try {
        if ($action === 'create') {
            $label = trim((string) ($_POST['label'] ?? ''));
            create_snapshot($label !== '' ? $label : 'يدوية');
            flash_set('ok', 'أُنشئت نقطة استرجاع.');
        } elseif ($action === 'restore') {
            restore_snapshot((int) ($_POST['id'] ?? 0));
            flash_set('ok', 'تمت الاستعادة. حُفظت نسخة من الوضع السابق في الأعلى إن أردت التراجع.');
        } elseif ($action === 'delete') {
            db()->prepare('DELETE FROM snapshots WHERE id = ?')->execute([(int) ($_POST['id'] ?? 0)]);
            flash_set('ok', 'تم حذف نقطة الاسترجاع.');
        } elseif ($action === 'import') {
            if (empty($_FILES['file']['tmp_name']) || ($_FILES['file']['error'] ?? 1) !== UPLOAD_ERR_OK) {
                throw new RuntimeException('اختر ملف نسخة احتياطية بصيغة JSON.');
            }
            if ($_FILES['file']['size'] > 20 * 1024 * 1024) {
                throw new RuntimeException('الملف أكبر من المسموح.');
            }
            $payload = json_decode((string) file_get_contents($_FILES['file']['tmp_name']), true);
            if (!is_array($payload)) {
                throw new RuntimeException('الملف ليس بصيغة JSON صحيحة.');
            }
            create_snapshot('تلقائية: قبل استيراد ملف');
            restore_payload($payload);
            flash_set('ok', 'تم استيراد المحتوى من الملف.');
        }
    } catch (Throwable $e) {
        flash_set('err', $e->getMessage());
    }
    redirect('snapshots.php');
}

$list = rows('SELECT id, label, size_bytes, created_by, created_at FROM snapshots ORDER BY id DESC');
admin_header('النسخ والاسترجاع', 'snapshots.php');
echo '<p class="intro">نقطة الاسترجاع تحفظ كل محتوى الموقع وتصميمه: النصوص والبرامج والصور والألوان. لا تشمل طلبات الحجز ولا المشتركين، فالاسترجاع لا يحذف أي طلب. تُنشأ نقطة تلقائياً قبل كل نشر من المصمم وقبل كل استرجاع، ويُحتفظ بآخر ' . SNAPSHOT_KEEP . ' نقطة.</p>';

echo '<div class="card"><h2>إنشاء نقطة الآن</h2><form method="post" class="actions">' . csrf_field() . '<input type="hidden" name="action" value="create">';
echo '<input type="text" name="label" maxlength="190" placeholder="وصف قصير، مثل: قبل تغيير أسعار رمضان" style="flex:1;min-width:240px"><button class="btn" type="submit">إنشاء</button></form></div>';

echo '<div class="card"><h2>نسخة احتياطية كاملة</h2><p class="intro">ملف SQL يضم كل الجداول بما فيها الطلبات والمشتركون. احفظه خارج الخادم، ويُستعاد من phpMyAdmin.</p>';
echo '<a class="btn secondary" href="snapshots.php?sql=1"><i class="fa-solid fa-database"></i> تنزيل نسخة كاملة</a></div>';

echo '<div class="card"><h2>استيراد محتوى من ملف</h2><p class="intro">ملف JSON نزّلته سابقاً من القائمة أدناه. يستبدل المحتوى الحالي بعد حفظ نقطة استرجاع تلقائية.</p>';
echo '<form method="post" enctype="multipart/form-data" class="actions" onsubmit="return confirm(\'سيُستبدل كل محتوى الموقع الحالي. متابعة؟\')">' . csrf_field() . '<input type="hidden" name="action" value="import"><input type="file" name="file" accept="application/json,.json" required><button class="btn ghost" type="submit">استيراد</button></form></div>';

echo '<div class="card" style="overflow:auto"><h2>نقاط الاسترجاع</h2><table><thead><tr><th>#</th><th>الوصف</th><th>بواسطة</th><th>الوقت</th><th>الحجم</th><th></th></tr></thead><tbody>';
foreach ($list as $row) {
    echo '<tr><td>' . (int) $row['id'] . '</td><td>' . h($row['label']) . '</td><td>' . h($row['created_by']) . '</td><td>' . h($row['created_at']) . '</td><td>' . h(number_format($row['size_bytes'] / 1024, 0)) . ' KB</td><td class="actions">';
    echo '<form method="post" onsubmit="return confirm(\'استعادة الموقع إلى هذه النقطة؟ سيُحفظ الوضع الحالي أولاً.\')">' . csrf_field() . '<input type="hidden" name="action" value="restore"><input type="hidden" name="id" value="' . (int) $row['id'] . '"><button class="btn small secondary" type="submit">استعادة</button></form>';
    echo '<a class="btn small ghost" href="snapshots.php?download=' . (int) $row['id'] . '">تنزيل</a>';
    echo delete_button((int) $row['id'], 'حذف نقطة الاسترجاع؟');
    echo '</td></tr>';
}
if (!$list) {
    echo '<tr><td colspan="6">لا توجد نقاط بعد.</td></tr>';
}
echo '</tbody></table></div>';
admin_footer();
