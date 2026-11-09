<?php

require __DIR__ . '/_init.php';
require __DIR__ . '/_layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string) ($_POST['action'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);
    try {
        if ($action === 'delete') {
            db()->prepare('DELETE FROM cities WHERE id = ?')->execute([$id]);
            flash_set('ok', 'تم حذف الوجهة.');
        } elseif ($action === 'move') {
            move_record('cities', $id, (string) ($_POST['direction'] ?? 'down'));
            flash_set('ok', 'تم تحديث الترتيب.');
        } elseif ($action === 'save') {
            $id = save_city($_POST, $id);
            flash_set('ok', 'تم حفظ الوجهة.');
        }
    } catch (Throwable $e) {
        flash_set('err', $e->getMessage());
        if ($action === 'save') {
            redirect('cities.php' . ($id ? '?edit=' . $id : '?new=1'));
        }
    }
    redirect('cities.php');
}

$fields = city_fields();
$editId = (int) ($_GET['edit'] ?? 0);
$isNew = isset($_GET['new']);
admin_header('مكة والمدينة', 'cities.php');

if ($editId || $isNew) {
    $city = $editId ? city_record($editId) : blank_record($fields);
    if (!$city) {
        echo '<p>الوجهة غير موجودة.</p>';
        admin_footer();
        exit;
    }
    echo '<p class="intro">كل وجهة تبويب مستقل بصورته ومميزاته. أسطر الأيقونات تقابل أسطر المميزات بالترتيب.</p>';
    echo '<form class="card validate" method="post" enctype="multipart/form-data">' . csrf_field();
    echo '<input type="hidden" name="action" value="save"><input type="hidden" name="id" value="' . (int) ($city['id'] ?? 0) . '">';
    echo edit_lang_switch();
    foreach ($fields as $name => $field) {
        record_field($name, $field, $city);
    }
    echo '<div class="actions" style="margin-top:16px"><button class="btn" type="submit">حفظ</button><a class="btn ghost" href="cities.php">رجوع</a></div></form>';
    admin_footer();
    exit;
}

echo '<p><a class="btn" href="cities.php?new=1">إضافة وجهة</a></p>';
$list = rows('SELECT * FROM cities ORDER BY sort_order, id');
echo '<div class="card" style="overflow:auto"><table><thead><tr><th>الصورة</th><th>التبويب</th><th>المعرّف</th><th>الحالة</th><th>الترتيب</th><th></th></tr></thead><tbody>';
foreach ($list as $row) {
    echo '<tr><td><img class="thumb" alt="" src="' . h(asset_url($row['image'])) . '"></td><td>' . h($row['tab_label']) . '</td><td>' . h($row['slug']) . '</td>';
    echo '<td>' . ((int) $row['is_active'] === 1 ? 'ظاهرة' : 'مخفية') . '</td>';
    echo '<td class="actions">' . move_buttons((int) $row['id']) . '</td><td class="actions">';
    echo '<a class="btn small secondary" href="cities.php?edit=' . (int) $row['id'] . '">تعديل</a>';
    echo delete_button((int) $row['id'], 'حذف الوجهة؟');
    echo '</td></tr>';
}
if (!$list) {
    echo '<tr><td colspan="6">لا توجد وجهات.</td></tr>';
}
echo '</tbody></table></div>';
admin_footer();
