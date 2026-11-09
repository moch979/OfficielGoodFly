<?php

require __DIR__ . '/_init.php';
require __DIR__ . '/_layout.php';

$type = (string) ($_GET['type'] ?? '');
$definitions = collections();
if (!isset($definitions[$type])) {
    http_response_code(404);
    exit('القسم غير موجود');
}
$definition = $definitions[$type];
$table = $definition['table'];
$fields = $definition['fields'];
$self = 'collection.php?type=' . urlencode($type);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string) ($_POST['action'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);
    try {
        if ($action === 'delete') {
            delete_collection_item($type, $id);
            flash_set('ok', 'تم الحذف.');
        } elseif ($action === 'move') {
            move_record($table, $id, (string) ($_POST['direction'] ?? 'down'));
            flash_set('ok', 'تم تحديث الترتيب.');
        } elseif ($action === 'save') {
            $id = save_collection_item($type, $_POST, $id);
            flash_set('ok', 'تم الحفظ.');
        }
    } catch (Throwable $e) {
        flash_set('err', $e->getMessage());
        if ($action === 'save') {
            redirect($self . ($id ? '&edit=' . $id : '&new=1'));
        }
    }
    redirect($self);
}

$editId = (int) ($_GET['edit'] ?? 0);
$isNew = isset($_GET['new']);
admin_header($definition['title'], $type);

if ($editId || $isNew) {
    $record = blank_record($fields);
    if ($editId) {
        $statement = db()->prepare("SELECT * FROM `{$table}` WHERE id = ?");
        $statement->execute([$editId]);
        $found = $statement->fetch();
        if (!$found) {
            echo '<p>العنصر غير موجود.</p>';
            admin_footer();
            exit;
        }
        $record = $found;
    }
    echo '<p class="intro">' . h($definition['intro']) . '</p>';
    echo '<form class="card validate" method="post" enctype="multipart/form-data">' . csrf_field();
    echo '<input type="hidden" name="action" value="save"><input type="hidden" name="id" value="' . (int) $editId . '">';
    if (has_translatable($fields)) {
        echo edit_lang_switch();
    }
    foreach ($fields as $name => $field) {
        record_field($name, $field, $record);
    }
    echo '<div class="actions" style="margin-top:18px"><button class="btn" type="submit">حفظ</button>';
    echo '<a class="btn ghost" href="' . h($self) . '">رجوع</a></div></form>';
    admin_footer();
    exit;
}

$orders = ['filters' => 'group_key ASC, sort_order ASC, id ASC', 'blog' => 'published_at DESC, sort_order ASC, id DESC'];
$order = $orders[$type] ?? 'sort_order ASC, id ASC';
$list = rows("SELECT * FROM `{$table}` ORDER BY {$order}");
$translatableColumns = array_keys(array_filter($fields, static function ($field) {
    return !empty($field['translatable']);
}));

echo '<p class="intro">' . h($definition['intro']) . '</p>';
echo '<p class="actions"><a class="btn" href="' . h($self) . '&new=1">إضافة جديد</a>';
if ($type === 'blog') {
    echo '<a class="btn ghost" target="_blank" rel="noopener" href="' . h(blog_url()) . '">عرض صفحة المدونة</a><a class="btn ghost" href="settings.php?group=blog">نصوص صفحة المدونة</a>';
}
echo '</p>';
echo '<div class="card" style="overflow:auto"><table><thead><tr>';
foreach ($definition['list'] as $label) {
    echo '<th>' . h($label) . '</th>';
}
echo '<th>الحالة</th><th>الترتيب</th><th></th></tr></thead><tbody>';
if (!$list) {
    echo '<tr><td colspan="' . (count($definition['list']) + 3) . '">لا توجد عناصر بعد.</td></tr>';
}
foreach ($list as $row) {
    echo '<tr>';
    foreach ($definition['list'] as $column => $label) {
        $value = (string) ($row[$column] ?? '');
        echo '<td>';
        if ($column === 'image_url' || $column === 'image') {
            echo '<img class="thumb" alt="" src="' . h(asset_url($value)) . '">';
        } elseif ($column === 'group_key' && isset($fields['group_key']['options'][$value])) {
            echo h($fields['group_key']['options'][$value]);
        } else {
            echo h(mb_strimwidth($value, 0, 80, '…'));
        }
        echo '</td>';
    }
    $missing = [];
    foreach ($translatableColumns as $column) {
        foreach (extra_languages() as $lang) {
            if (trim((string) ($row[$column] ?? '')) !== '' && trim((string) ($row[$column . '_' . $lang] ?? '')) === '') {
                $missing[$lang] = languages()[$lang]['name'];
            }
        }
    }
    $state = isset($row['is_active']) && (int) $row['is_active'] !== 1 ? 'مخفي' : 'ظاهر';
    echo '<td>' . h($state) . ($missing ? '<div class="missing">ينقصه: ' . h(implode('، ', $missing)) . '</div>' : '') . '</td>';
    echo '<td class="actions">' . move_buttons((int) $row['id']) . '</td><td class="actions">';
    echo '<a class="btn small secondary" href="' . h($self) . '&edit=' . (int) $row['id'] . '">تعديل</a>';
    if ($type === 'blog') {
        echo '<a class="btn small ghost" target="_blank" rel="noopener" href="' . h(blog_url((string) $row['slug'])) . '">الصفحة</a>';
    }
    echo delete_button((int) $row['id'], 'حذف هذا العنصر؟');
    echo '</td></tr>';
}
echo '</tbody></table></div>';
admin_footer();
