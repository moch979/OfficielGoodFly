<?php

require __DIR__ . '/_init.php';
require __DIR__ . '/_layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string) ($_POST['action'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);
    try {
        if ($action === 'delete') {
            db()->prepare('DELETE FROM packages WHERE id = ?')->execute([$id]);
            flash_set('ok', 'تم حذف البرنامج.');
        } elseif ($action === 'move') {
            move_record('packages', $id, (string) ($_POST['direction'] ?? 'down'));
            flash_set('ok', 'تم تحديث الترتيب.');
        } elseif ($action === 'save') {
            $id = save_package($_POST, $id);
            flash_set('ok', 'تم حفظ البرنامج.');
        } elseif ($action === 'duplicate') {
            $newId = duplicate_package($id);
            flash_set('ok', 'أُنشئت نسخة مخفية من البرنامج. عدّلها ثم فعّل «ظاهر في الموقع». المواعيد لا تُنسخ.');
            redirect('packages.php?edit=' . $newId);
        }
    } catch (Throwable $e) {
        flash_set('err', $e->getMessage());
        if ($action === 'save') {
            redirect('packages.php' . ($id ? '?edit=' . $id : '?new=1'));
        }
    }
    redirect('packages.php');
}

$fields = package_fields();
$editId = (int) ($_GET['edit'] ?? 0);
$isNew = isset($_GET['new']);
admin_header('برامج الحج والعمرة', 'packages.php');

if ($editId || $isNew) {
    $package = $editId ? package_record($editId) : blank_record($fields);
    if (!$package) {
        echo '<p>البرنامج غير موجود.</p>';
        admin_footer();
        exit;
    }
    echo '<p class="intro">البطاقة في الصفحة الرئيسية، وصفحة مستقلة للبرنامج فيها اليوميات والأسعار والمواعيد. كل نص بالعربية والفرنسية والإنجليزية؛ اختر لغة التحرير من الشريط.</p>';
    if (!empty($package['slug'])) {
        echo '<p class="actions">';
        foreach (languages() as $code => $info) {
            echo '<a class="btn small ghost" target="_blank" rel="noopener" href="' . h(program_url($package['slug'], $code)) . '">معاينة الصفحة: ' . h($info['name']) . '</a>';
        }
        echo '</p>';
    }
    echo '<form class="card" method="post" enctype="multipart/form-data">' . csrf_field();
    echo '<input type="hidden" name="action" value="save"><input type="hidden" name="id" value="' . (int) ($package['id'] ?? 0) . '">';
    echo edit_lang_switch();
    foreach ($fields as $name => $field) {
        record_field($name, $field, $package);
    }
    echo '<div class="actions" style="margin-top:16px"><button class="btn" type="submit">حفظ البرنامج</button><a class="btn ghost" href="packages.php">رجوع</a></div></form>';
    admin_footer();
    exit;
}

echo '<p class="intro">أضف أو عدّل أو أخفِ أي برنامج. السعر نص حر لكل لغة.</p>';
echo '<p><a class="btn" href="packages.php?new=1">إضافة برنامج</a></p>';
$list = rows('SELECT p.*, c.label AS category_label FROM packages p LEFT JOIN package_categories c ON c.slug = p.category ORDER BY p.sort_order, p.id');
$departures = upcoming_departures(array_column($list, 'id'));
echo '<div class="card" style="overflow:auto"><table><thead><tr><th>الصورة</th><th>البرنامج</th><th>السعر</th><th>التصنيف</th><th>المواعيد القادمة</th><th>الحالة</th><th>الترتيب</th><th></th></tr></thead><tbody>';
foreach ($list as $row) {
    $missing = [];
    foreach (extra_languages() as $lang) {
        if (trim((string) $row['title_' . $lang]) === '' || trim((string) $row['description_' . $lang]) === '') {
            $missing[] = languages()[$lang]['name'];
        }
    }
    echo '<tr><td><img class="thumb" alt="" src="' . h(asset_url($row['image'])) . '"></td>';
    echo '<td>' . h($row['title']) . ($missing ? '<div class="missing">ينقصه: ' . h(implode('، ', $missing)) . '</div>' : '') . '</td>';
    $upcoming = $departures[(int) $row['id']] ?? [];
    $seats = array_sum(array_column($upcoming, 'seats'));
    echo '<td>' . h(display_price($row)) . '</td><td>' . h($row['category_label'] ?? $row['category']) . '</td>';
    echo '<td>' . ($upcoming ? count($upcoming) . ' موعد، ' . $seats . ' مقعد' : '<span class="hint">لا يوجد</span>') . '</td>';
    echo '<td>' . ((int) $row['is_active'] === 1 ? 'ظاهر' : 'مخفي') . '</td>';
    echo '<td class="actions">' . move_buttons((int) $row['id']) . '</td><td class="actions">';
    echo '<a class="btn small secondary" href="packages.php?edit=' . (int) $row['id'] . '">تعديل</a>';
    echo '<a class="btn small ghost" target="_blank" rel="noopener" href="' . h(program_url((string) $row['slug'])) . '">الصفحة</a>';
    echo '<form method="post">' . csrf_field() . '<input type="hidden" name="action" value="duplicate"><input type="hidden" name="id" value="' . (int) $row['id'] . '"><button class="btn small ghost" type="submit">نسخ</button></form>';
    echo delete_button((int) $row['id'], 'حذف البرنامج؟');
    echo '</td></tr>';
}
if (!$list) {
    echo '<tr><td colspan="8">لا توجد برامج.</td></tr>';
}
echo '</tbody></table></div>';
admin_footer();
