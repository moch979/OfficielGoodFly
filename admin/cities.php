<?php

require __DIR__ . '/_init.php';
require __DIR__ . '/_layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string) ($_POST['action'] ?? '');
    try {
        if ($action === 'delete') {
            db()->prepare('DELETE FROM cities WHERE id = ?')->execute([(int) $_POST['id']]);
            flash_set('ok', 'تم حذف الوجهة.');
        } elseif ($action === 'move') {
            move_collection_row('cities', (int) $_POST['id'], (string) ($_POST['direction'] ?? 'down'));
            flash_set('ok', 'تم تحديث الترتيب.');
        } elseif ($action === 'save') {
            $slug = trim((string) ($_POST['slug'] ?? ''));
            $tab = trim((string) ($_POST['tab_label'] ?? ''));
            $heading = trim((string) ($_POST['heading'] ?? ''));
            $caption = trim((string) ($_POST['caption'] ?? ''));
            if ($slug === '' || $tab === '' || $heading === '' || $caption === '') {
                throw new RuntimeException('أكمل بيانات الوجهة.');
            }
            if (!preg_match('/^[a-z0-9\-]+$/', $slug)) {
                throw new RuntimeException('المعرّف يجب أن يكون حروفاً إنجليزية صغيرة، مثل makkah.');
            }
            $image = save_upload('image_file') ?: trim((string) ($_POST['image'] ?? ''));
            if ($image === '') {
                throw new RuntimeException('أضف صورة للوجهة.');
            }
            $payload = [$slug, $tab, $heading, $image, $caption, (int) ($_POST['sort_order'] ?? 0), isset($_POST['is_active']) ? 1 : 0];
            $id = (int) ($_POST['id'] ?? 0);
            db()->beginTransaction();
            if ($id > 0) {
                db()->prepare('UPDATE cities SET slug=?, tab_label=?, heading=?, image=?, caption=?, sort_order=?, is_active=? WHERE id=?')->execute([...$payload, $id]);
            } else {
                db()->prepare('INSERT INTO cities (slug, tab_label, heading, image, caption, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)')->execute($payload);
                $id = (int) db()->lastInsertId();
            }
            db()->prepare('DELETE FROM city_features WHERE city_id = ?')->execute([$id]);
            $insert = db()->prepare('INSERT INTO city_features (city_id, icon, text, sort_order) VALUES (?, ?, ?, ?)');
            $lines = preg_split('/\r\n|\r|\n/', (string) ($_POST['features'] ?? '')) ?: [];
            $position = 1;
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                $parts = explode('|', $line, 2);
                if (count($parts) === 2) {
                    $icon = trim($parts[0]);
                    $text = trim($parts[1]);
                } else {
                    $icon = 'fa-solid fa-check';
                    $text = $line;
                }
                if ($text === '') {
                    continue;
                }
                $insert->execute([$id, $icon, $text, $position * 10]);
                $position++;
            }
            db()->commit();
            flash_set('ok', 'تم حفظ الوجهة.');
        }
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        flash_set('err', $e->getMessage());
    }
    redirect('cities.php');
}

$editId = (int) ($_GET['edit'] ?? 0);
$isNew = isset($_GET['new']);
admin_header('مكة والمدينة', 'cities.php');

if ($editId || $isNew) {
    $city = ['id' => 0, 'slug' => '', 'tab_label' => '', 'heading' => '', 'image' => '', 'caption' => '', 'sort_order' => 0, 'is_active' => 1, 'features' => ''];
    if ($editId) {
        $statement = db()->prepare('SELECT * FROM cities WHERE id = ?');
        $statement->execute([$editId]);
        $found = $statement->fetch();
        if (!$found) {
            echo '<p>الوجهة غير موجودة.</p>';
            admin_footer();
            exit;
        }
        $features = rows('SELECT icon, text FROM city_features WHERE city_id = ? ORDER BY sort_order, id', [$editId]);
        $lines = [];
        foreach ($features as $feature) {
            $lines[] = $feature['icon'] . '|' . $feature['text'];
        }
        $city = $found;
        $city['features'] = implode("\n", $lines);
    }
    echo '<p class="intro">كل سطر ميزة بالشكل: fa-solid fa-kaaba|نص الميزة</p>';
    echo '<form class="card" method="post" enctype="multipart/form-data">' . csrf_field();
    echo '<input type="hidden" name="action" value="save"><input type="hidden" name="id" value="' . (int) $city['id'] . '">';
    echo '<label>المعرّف اللاتيني</label><input name="slug" required value="' . h($city['slug']) . '" placeholder="makkah">';
    echo '<label>اسم التبويب</label><input name="tab_label" required value="' . h($city['tab_label']) . '">';
    echo '<label>عنوان المميزات</label><input name="heading" required value="' . h($city['heading']) . '">';
    echo '<label>عبارة أسفل الصورة</label><input name="caption" required value="' . h($city['caption']) . '">';
    echo '<label>رابط الصورة</label><input name="image" value="' . h($city['image']) . '">';
    echo '<input type="file" name="image_file" accept="image/*" style="margin-top:8px">';
    if ($city['image'] !== '') {
        echo '<div style="margin-top:8px"><img class="thumb" alt="" src="' . h(asset_url($city['image'])) . '"></div>';
    }
    echo '<label>المميزات</label><textarea name="features">' . h($city['features']) . '</textarea>';
    echo '<label>الترتيب</label><input type="number" name="sort_order" value="' . h($city['sort_order']) . '">';
    $checked = (int) $city['is_active'] === 1 ? ' checked' : '';
    echo '<label class="check"><input type="checkbox" name="is_active" value="1"' . $checked . '> ظاهرة</label>';
    echo '<div class="actions" style="margin-top:16px"><button class="btn">حفظ</button><a class="btn ghost" href="cities.php">رجوع</a></div></form>';
    admin_footer();
    exit;
}

echo '<p><a class="btn" href="cities.php?new=1">إضافة وجهة</a></p>';
$list = rows('SELECT id, tab_label, slug, caption, is_active FROM cities ORDER BY sort_order, id');
echo '<div class="card" style="overflow:auto"><table><thead><tr><th>التبويب</th><th>المعرّف</th><th>العبارة</th><th>الحالة</th><th></th></tr></thead><tbody>';
foreach ($list as $row) {
    echo '<tr><td>' . h($row['tab_label']) . '</td><td>' . h($row['slug']) . '</td><td>' . h($row['caption']) . '</td>';
    echo '<td>' . ((int) $row['is_active'] === 1 ? 'ظاهرة' : 'مخفية') . '</td><td class="actions">';
    echo '<form method="post">' . csrf_field() . '<input type="hidden" name="action" value="move"><input type="hidden" name="direction" value="up"><input type="hidden" name="id" value="' . (int) $row['id'] . '"><button class="btn small ghost">أعلى</button></form>';
    echo '<form method="post">' . csrf_field() . '<input type="hidden" name="action" value="move"><input type="hidden" name="direction" value="down"><input type="hidden" name="id" value="' . (int) $row['id'] . '"><button class="btn small ghost">أسفل</button></form>';
    echo '<a class="btn small secondary" href="cities.php?edit=' . (int) $row['id'] . '">تعديل</a>';
    echo '<form method="post" onsubmit="return confirm(\'حذف الوجهة؟\')">' . csrf_field() . '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' . (int) $row['id'] . '"><button class="btn small danger">حذف</button></form>';
    echo '</td></tr>';
}
if (!$list) {
    echo '<tr><td colspan="5">لا توجد وجهات.</td></tr>';
}
echo '</tbody></table></div>';
admin_footer();
