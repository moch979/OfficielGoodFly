<?php

require __DIR__ . '/_init.php';
require __DIR__ . '/_layout.php';

$categories = rows('SELECT slug, label FROM package_categories ORDER BY sort_order, id');
$hotelOptions = rows("SELECT option_value, label FROM filter_options WHERE group_key = 'hotel' AND option_value <> 'all' ORDER BY sort_order, id");
$durationOptions = rows("SELECT option_value, label FROM filter_options WHERE group_key = 'duration' AND option_value <> 'all' ORDER BY sort_order, id");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string) ($_POST['action'] ?? '');
    try {
        if ($action === 'delete') {
            db()->prepare('DELETE FROM packages WHERE id = ?')->execute([(int) $_POST['id']]);
            flash_set('ok', 'تم حذف البرنامج.');
        } elseif ($action === 'move') {
            move_collection_row('packages', (int) $_POST['id'], (string) ($_POST['direction'] ?? 'down'));
            flash_set('ok', 'تم تحديث الترتيب.');
        } elseif ($action === 'save') {
            $title = trim((string) ($_POST['title'] ?? ''));
            $category = trim((string) ($_POST['category'] ?? ''));
            $badge = trim((string) ($_POST['category_badge'] ?? ''));
            $hotel = trim((string) ($_POST['hotel'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            $price = trim((string) ($_POST['price'] ?? ''));
            if ($title === '' || $category === '' || $badge === '' || $hotel === '' || $description === '' || $price === '') {
                throw new RuntimeException('أكمل الحقول الأساسية للبرنامج.');
            }
            $image = save_upload('image_file') ?: trim((string) ($_POST['image'] ?? ''));
            if ($image === '') {
                throw new RuntimeException('أضف صورة للبرنامج.');
            }
            $payload = [
                $title,
                $category,
                $badge,
                $hotel,
                trim((string) ($_POST['hotel_class'] ?? '')),
                trim((string) ($_POST['distance'] ?? '')),
                trim((string) ($_POST['duration_days'] ?? '')),
                $description,
                $price,
                $image,
                (int) ($_POST['sort_order'] ?? 0),
                isset($_POST['is_active']) ? 1 : 0,
            ];
            $id = (int) ($_POST['id'] ?? 0);
            db()->beginTransaction();
            if ($id > 0) {
                $statement = db()->prepare('UPDATE packages SET title=?, category=?, category_badge=?, hotel=?, hotel_class=?, distance=?, duration_days=?, description=?, price=?, image=?, sort_order=?, is_active=? WHERE id=?');
                $statement->execute([...$payload, $id]);
            } else {
                $statement = db()->prepare('INSERT INTO packages (title, category, category_badge, hotel, hotel_class, distance, duration_days, description, price, image, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $statement->execute($payload);
                $id = (int) db()->lastInsertId();
            }
            db()->prepare('DELETE FROM package_features WHERE package_id = ?')->execute([$id]);
            $insertFeature = db()->prepare('INSERT INTO package_features (package_id, feature_text, sort_order) VALUES (?, ?, ?)');
            $lines = preg_split('/\r\n|\r|\n/', (string) ($_POST['features'] ?? '')) ?: [];
            $position = 1;
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                $insertFeature->execute([$id, $line, $position * 10]);
                $position++;
            }
            db()->commit();
            flash_set('ok', 'تم حفظ البرنامج.');
        }
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        flash_set('err', $e->getMessage());
    }
    redirect('packages.php');
}

$editId = (int) ($_GET['edit'] ?? 0);
$isNew = isset($_GET['new']);
admin_header('برامج الحج والعمرة', 'packages.php');

if ($editId || $isNew) {
    $package = [
        'id' => 0, 'title' => '', 'category' => $categories[0]['slug'] ?? '', 'category_badge' => '',
        'hotel' => '', 'hotel_class' => '', 'distance' => '', 'duration_days' => '', 'description' => '',
        'price' => '', 'image' => '', 'sort_order' => 0, 'is_active' => 1, 'features' => '',
    ];
    if ($editId) {
        $statement = db()->prepare('SELECT * FROM packages WHERE id = ?');
        $statement->execute([$editId]);
        $found = $statement->fetch();
        if (!$found) {
            echo '<p>البرنامج غير موجود.</p>';
            admin_footer();
            exit;
        }
        $features = rows('SELECT feature_text FROM package_features WHERE package_id = ? ORDER BY sort_order, id', [$editId]);
        $package = $found;
        $package['features'] = implode("\n", array_column($features, 'feature_text'));
    }
    echo '<p class="intro">كل حقل في بطاقة البرنامج: العنوان، السعر، الفندق، المزايا، والصورة. المزايا سطر لكل ميزة.</p>';
    echo '<form class="card" method="post" enctype="multipart/form-data">';
    echo csrf_field() . '<input type="hidden" name="action" value="save"><input type="hidden" name="id" value="' . (int) $package['id'] . '">';
    echo '<label>عنوان البرنامج</label><input name="title" required value="' . h($package['title']) . '">';
    echo '<label>التصنيف</label><select name="category">';
    foreach ($categories as $category) {
        $selected = (string) $package['category'] === (string) $category['slug'] ? ' selected' : '';
        echo '<option value="' . h($category['slug']) . '"' . $selected . '>' . h($category['label']) . ' (' . h($category['slug']) . ')</option>';
    }
    echo '</select><div class="hint">أضف تصنيفاً جديداً من صفحة تصنيفات البرامج إن لم تجده.</div>';
    echo '<label>شارة التصنيف على الصورة</label><input name="category_badge" required value="' . h($package['category_badge']) . '">';
    echo '<label>اسم الفندق</label><input name="hotel" required value="' . h($package['hotel']) . '">';
    echo '<label>فئة الفندق للبحث</label><input name="hotel_class" list="hotel-classes" value="' . h($package['hotel_class']) . '">';
    echo '<datalist id="hotel-classes">';
    foreach ($hotelOptions as $option) {
        echo '<option value="' . h($option['option_value']) . '">' . h($option['label']) . '</option>';
    }
    echo '</datalist><div class="hint">اكتب نفس قيمة خيار البحث، مثل 5star-view، حتى يعمل فلتر الفندق.</div>';
    echo '<label>المسافة أو الموقع</label><input name="distance" value="' . h($package['distance']) . '">';
    echo '<label>مدة الإقامة للبحث</label><input name="duration_days" list="durations" value="' . h($package['duration_days']) . '">';
    echo '<datalist id="durations">';
    foreach ($durationOptions as $option) {
        echo '<option value="' . h($option['option_value']) . '">' . h($option['label']) . '</option>';
    }
    echo '</datalist>';
    echo '<label>الوصف</label><textarea name="description" required>' . h($package['description']) . '</textarea>';
    echo '<label>السعر كما يظهر للزائر</label><input name="price" required value="' . h($package['price']) . '" placeholder="3,950 $">';
    echo '<label>رابط الصورة</label><input name="image" value="' . h($package['image']) . '">';
    echo '<input type="file" name="image_file" accept="image/*" style="margin-top:8px">';
    if ($package['image'] !== '') {
        echo '<div style="margin-top:8px"><img class="thumb" alt="" src="' . h(asset_url($package['image'])) . '"></div>';
    }
    echo '<label>المزايا، سطر لكل ميزة</label><textarea name="features">' . h($package['features']) . '</textarea>';
    echo '<label>الترتيب</label><input type="number" name="sort_order" value="' . h($package['sort_order']) . '">';
    $checked = (int) $package['is_active'] === 1 ? ' checked' : '';
    echo '<label class="check"><input type="checkbox" name="is_active" value="1"' . $checked . '> ظاهر في الموقع</label>';
    echo '<div class="actions" style="margin-top:16px"><button class="btn" type="submit">حفظ البرنامج</button><a class="btn ghost" href="packages.php">رجوع</a></div></form>';
    admin_footer();
    exit;
}

echo '<p class="intro">أضف أو عدّل أو أخفِ أي برنامج. السعر نص حر، فتقدر تكتب العملة بالطريقة التي تريدها.</p>';
echo '<p><a class="btn" href="packages.php?new=1">إضافة برنامج</a></p>';
$list = rows('SELECT id, title, price, category, is_active, image FROM packages ORDER BY sort_order, id');
echo '<div class="card" style="overflow:auto"><table><thead><tr><th>الصورة</th><th>البرنامج</th><th>السعر</th><th>التصنيف</th><th>الحالة</th><th></th></tr></thead><tbody>';
foreach ($list as $row) {
    echo '<tr><td><img class="thumb" alt="" src="' . h(asset_url($row['image'])) . '"></td>';
    echo '<td>' . h($row['title']) . '</td><td>' . h($row['price']) . '</td><td>' . h($row['category']) . '</td>';
    echo '<td>' . ((int) $row['is_active'] === 1 ? 'ظاهر' : 'مخفي') . '</td><td class="actions">';
    echo '<form method="post">' . csrf_field() . '<input type="hidden" name="action" value="move"><input type="hidden" name="direction" value="up"><input type="hidden" name="id" value="' . (int) $row['id'] . '"><button class="btn small ghost">أعلى</button></form>';
    echo '<form method="post">' . csrf_field() . '<input type="hidden" name="action" value="move"><input type="hidden" name="direction" value="down"><input type="hidden" name="id" value="' . (int) $row['id'] . '"><button class="btn small ghost">أسفل</button></form>';
    echo '<a class="btn small secondary" href="packages.php?edit=' . (int) $row['id'] . '">تعديل</a>';
    echo '<form method="post" onsubmit="return confirm(\'حذف البرنامج؟\')">' . csrf_field() . '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' . (int) $row['id'] . '"><button class="btn small danger">حذف</button></form>';
    echo '</td></tr>';
}
if (!$list) {
    echo '<tr><td colspan="6">لا توجد برامج.</td></tr>';
}
echo '</tbody></table></div>';
admin_footer();
