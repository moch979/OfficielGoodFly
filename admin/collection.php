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
$order = $definition['order'] ?? 'sort_order ASC, id ASC';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string) ($_POST['action'] ?? '');
    try {
        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            $statement = db()->prepare("DELETE FROM {$table} WHERE id = ?");
            $statement->execute([$id]);
            flash_set('ok', 'تم الحذف.');
        } elseif ($action === 'move') {
            move_collection_row($table, (int) ($_POST['id'] ?? 0), (string) ($_POST['direction'] ?? 'down'));
            flash_set('ok', 'تم تحديث الترتيب.');
        } elseif ($action === 'save') {
            $data = [];
            foreach ($definition['fields'] as $name => $field) {
                $data[$name] = collect_field_value($name, $field);
            }
            $id = (int) ($_POST['id'] ?? 0);
            if ($id > 0) {
                $sets = implode(', ', array_map(static function ($column) {
                    return $column . ' = ?';
                }, array_keys($data)));
                $statement = db()->prepare("UPDATE {$table} SET {$sets} WHERE id = ?");
                $statement->execute([...array_values($data), $id]);
                flash_set('ok', 'تم حفظ التعديل.');
            } else {
                $columns = implode(', ', array_keys($data));
                $marks = implode(', ', array_fill(0, count($data), '?'));
                $statement = db()->prepare("INSERT INTO {$table} ({$columns}) VALUES ({$marks})");
                $statement->execute(array_values($data));
                flash_set('ok', 'تمت الإضافة.');
            }
        }
    } catch (Throwable $e) {
        flash_set('err', $e->getMessage());
        $backId = (int) ($_POST['id'] ?? 0);
        redirect('collection.php?type=' . urlencode($type) . ($backId ? '&edit=' . $backId : '&new=1'));
    }
    redirect('collection.php?type=' . urlencode($type));
}

$editId = (int) ($_GET['edit'] ?? 0);
$isNew = isset($_GET['new']);
admin_header($definition['title'], $type);

if ($editId || $isNew) {
    $record = array_fill_keys(array_keys($definition['fields']), '');
    foreach ($definition['fields'] as $name => $field) {
        if (($field['type'] ?? '') === 'checkbox') {
            $record[$name] = 1;
        }
        if ($name === 'stars') {
            $record[$name] = 5;
        }
    }
    if ($editId) {
        $statement = db()->prepare("SELECT * FROM {$table} WHERE id = ?");
        $statement->execute([$editId]);
        $found = $statement->fetch();
        if (!$found) {
            echo '<p>العنصر غير موجود.</p>';
            admin_footer();
            exit;
        }
        $record = array_merge($record, $found);
    }
    echo '<p class="intro">' . h($definition['intro']) . '</p>';
    echo '<form class="card" method="post" enctype="multipart/form-data">';
    echo csrf_field();
    echo '<input type="hidden" name="action" value="save">';
    echo '<input type="hidden" name="id" value="' . (int) $editId . '">';
    foreach ($definition['fields'] as $name => $field) {
        admin_field($name, $field, $record[$name] ?? '');
    }
    echo '<div class="actions" style="margin-top:18px"><button class="btn" type="submit">حفظ</button>';
    echo '<a class="btn ghost" href="collection.php?type=' . h($type) . '">رجوع</a></div></form>';
    admin_footer();
    exit;
}

echo '<p class="intro">' . h($definition['intro']) . '</p>';
echo '<p><a class="btn" href="collection.php?type=' . h($type) . '&new=1">إضافة جديد</a></p>';
$list = rows("SELECT * FROM {$table} ORDER BY {$order}");
echo '<div class="card" style="overflow:auto"><table><thead><tr>';
foreach ($definition['list'] as $label) {
    echo '<th>' . h($label) . '</th>';
}
echo '<th>الترتيب</th><th></th></tr></thead><tbody>';
if (!$list) {
    echo '<tr><td colspan="' . (count($definition['list']) + 2) . '">لا توجد عناصر بعد.</td></tr>';
}
foreach ($list as $row) {
    echo '<tr>';
    foreach ($definition['list'] as $column => $label) {
        $value = (string) ($row[$column] ?? '');
        echo '<td>';
        if ($column === 'image_url' || $column === 'image') {
            echo '<img class="thumb" alt="" src="' . h(asset_url($value)) . '">';
        } else {
            echo h(mb_strimwidth($value, 0, 80, '…'));
        }
        echo '</td>';
    }
    echo '<td class="actions">';
    echo '<form method="post">' . csrf_field() . '<input type="hidden" name="action" value="move"><input type="hidden" name="id" value="' . (int) $row['id'] . '"><input type="hidden" name="direction" value="up"><button class="btn small ghost" type="submit">أعلى</button></form>';
    echo '<form method="post">' . csrf_field() . '<input type="hidden" name="action" value="move"><input type="hidden" name="id" value="' . (int) $row['id'] . '"><input type="hidden" name="direction" value="down"><button class="btn small ghost" type="submit">أسفل</button></form>';
    echo '</td><td class="actions">';
    echo '<a class="btn small secondary" href="collection.php?type=' . h($type) . '&edit=' . (int) $row['id'] . '">تعديل</a>';
    echo '<form method="post" onsubmit="return confirm(\'حذف هذا العنصر؟\')">' . csrf_field() . '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' . (int) $row['id'] . '"><button class="btn small danger" type="submit">حذف</button></form>';
    echo '</td></tr>';
}
echo '</tbody></table></div>';
admin_footer();
