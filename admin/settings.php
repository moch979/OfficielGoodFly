<?php

require __DIR__ . '/_init.php';
require __DIR__ . '/_layout.php';

$groups = setting_groups();
$group = (string) ($_GET['group'] ?? 'general');
if (!isset($groups[$group])) {
    $group = 'general';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $postedGroup = (string) ($_POST['group'] ?? '');
    if (!isset($groups[$postedGroup])) {
        flash_set('err', 'مجموعة غير معروفة.');
        redirect('settings.php');
    }
    $fields = rows('SELECT skey, field_type FROM settings WHERE sgroup = ?', [$postedGroup]);
    try {
        $update = db()->prepare('UPDATE settings SET svalue = ? WHERE skey = ? AND sgroup = ?');
        foreach ($fields as $field) {
            $key = $field['skey'];
            $type = $field['field_type'];
            if ($type === 'bool') {
                $value = isset($_POST['values'][$key]) ? '1' : '0';
            } elseif ($type === 'image') {
                $uploaded = save_upload('file_' . $key);
                $value = $uploaded ?: trim((string) ($_POST['values'][$key] ?? ''));
            } elseif ($type === 'number') {
                $value = (string) (int) ($_POST['values'][$key] ?? 0);
            } elseif ($type === 'html') {
                $value = clean_rich_text((string) ($_POST['values'][$key] ?? ''));
            } elseif ($type === 'color') {
                $value = trim((string) ($_POST['values'][$key] ?? ''));
                if (!preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
                    throw new RuntimeException('لون غير صالح.');
                }
            } else {
                $value = trim((string) ($_POST['values'][$key] ?? ''));
            }
            $update->execute([$value, $key, $postedGroup]);
        }
        flash_set('ok', 'تم حفظ التفاصيل.');
    } catch (Throwable $e) {
        flash_set('err', $e->getMessage());
    }
    redirect('settings.php?group=' . urlencode($postedGroup));
}

$rows = rows('SELECT * FROM settings WHERE sgroup = ? ORDER BY sorder, skey', [$group]);
admin_header('كل النصوص والتفاصيل', 'settings.php');
echo '<p class="intro">كل عبارة في الموقع قابلة للتعديل من هنا: العناوين، الألوان، الهاتف، واتساب، وإظهار الأقسام أو إخفاؤها. العناصر المتكررة مثل البرامج والخدمات لها صفحات مستقلة في القائمة.</p>';
echo '<div class="tabs">';
foreach ($groups as $key => $label) {
    $class = $key === $group ? ' class="active"' : '';
    echo '<a' . $class . ' href="settings.php?group=' . h($key) . '">' . h($label) . '</a>';
}
echo '</div><form class="card" method="post" enctype="multipart/form-data">';
echo csrf_field();
echo '<input type="hidden" name="group" value="' . h($group) . '">';
foreach ($rows as $row) {
    $name = 'values[' . $row['skey'] . ']';
    echo '<label for="' . h($row['skey']) . '">' . h($row['label']) . '</label>';
    $type = $row['field_type'];
    $value = (string) $row['svalue'];
    if ($type === 'textarea' || $type === 'html') {
        echo '<textarea id="' . h($row['skey']) . '" name="' . h($name) . '">' . h($value) . '</textarea>';
    } elseif ($type === 'bool') {
        $checked = $value === '1' ? ' checked' : '';
        echo '<label class="check"><input type="checkbox" id="' . h($row['skey']) . '" name="' . h($name) . '" value="1"' . $checked . '> نعم</label>';
    } elseif ($type === 'image') {
        echo '<input id="' . h($row['skey']) . '" type="text" name="' . h($name) . '" value="' . h($value) . '">';
        echo '<input type="file" name="file_' . h($row['skey']) . '" accept="image/*" style="margin-top:8px">';
        if ($value !== '') {
            echo '<div style="margin-top:8px"><img class="thumb" alt="" src="' . h(asset_url($value)) . '"></div>';
        }
    } elseif ($type === 'color') {
        $color = preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? $value : '#183B35';
        echo '<input id="' . h($row['skey']) . '" type="color" name="' . h($name) . '" value="' . h($color) . '" style="height:48px;padding:4px">';
    } elseif ($type === 'number') {
        echo '<input id="' . h($row['skey']) . '" type="number" name="' . h($name) . '" value="' . h($value) . '">';
    } else {
        echo '<input id="' . h($row['skey']) . '" type="text" name="' . h($name) . '" value="' . h($value) . '">';
    }
    if (!empty($row['hint'])) {
        echo '<div class="hint">' . h($row['hint']) . '</div>';
    }
}
echo '<div style="margin-top:18px"><button class="btn" type="submit">حفظ هذا القسم</button></div></form>';
admin_footer();
