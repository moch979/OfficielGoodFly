<?php

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/collections.php';
require_once __DIR__ . '/_layout.php';

if (!current_admin()) {
    json_response(['ok' => false, 'message' => 'يلزم تسجيل الدخول من جديد.'], 401);
}

function designer_fail(string $message, int $status = 400): void
{
    json_response(['ok' => false, 'message' => $message], $status);
}

function designer_check_csrf(): void
{
    $sent = $_POST['csrf'] ?? '';
    $known = $_SESSION['csrf'] ?? '';
    if (!is_string($sent) || !is_string($known) || $known === '' || !hash_equals($known, $sent)) {
        designer_fail('انتهت صلاحية الصفحة. أعد تحميل المصمم.');
    }
}

function designer_section(string $id): array
{
    $catalog = designer_catalog();
    if (!isset($catalog[$id])) {
        designer_fail('قسم غير معروف.');
    }
    return $catalog[$id];
}

function designer_settings_payload(array $groups): array
{
    if (!$groups) {
        return [];
    }
    $marks = implode(', ', array_fill(0, count($groups), '?'));
    $items = rows("SELECT skey, svalue, label, field_type, hint FROM settings WHERE sgroup IN ($marks) ORDER BY sorder, skey", $groups);
    $payload = [];
    foreach ($items as $item) {
        $payload[] = [
            'key' => $item['skey'],
            'label' => $item['label'],
            'type' => $item['field_type'],
            'hint' => $item['hint'],
            'value' => (string) $item['svalue'],
        ];
    }
    return $payload;
}

function designer_collection_payload(string $type): array
{
    $definitions = collections();
    if (!isset($definitions[$type])) {
        designer_fail('مجموعة غير معروفة.');
    }
    $definition = $definitions[$type];
    $order = $definition['order'] ?? 'sort_order ASC, id ASC';
    $table = $definition['table'];
    $records = rows("SELECT * FROM {$table} ORDER BY {$order}");
    $items = [];
    foreach ($records as $record) {
        $items[] = [
            'id' => (int) $record['id'],
            'summary' => designer_summary($record, $definition['list']),
            'values' => designer_public_values($record, $definition['fields']),
        ];
    }
    return [
        'kind' => 'collection',
        'type' => $type,
        'title' => $definition['title'],
        'intro' => $definition['intro'],
        'fields' => designer_public_fields($definition['fields']),
        'items' => $items,
    ];
}

function designer_public_fields(array $fields): array
{
    $public = [];
    foreach ($fields as $name => $field) {
        $public[$name] = [
            'label' => $field['label'] ?? $name,
            'type' => $field['type'] ?? 'text',
            'hint' => $field['hint'] ?? '',
            'required' => !empty($field['required']),
            'options' => $field['options'] ?? new stdClass(),
        ];
    }
    return $public;
}

function designer_public_values(array $record, array $fields): array
{
    $values = [];
    foreach ($fields as $name => $field) {
        $value = $record[$name] ?? '';
        if (($field['type'] ?? '') === 'checkbox') {
            $values[$name] = (int) $value === 1 ? 1 : 0;
        } elseif (($field['type'] ?? '') === 'number') {
            $values[$name] = (int) $value;
        } else {
            $values[$name] = (string) $value;
        }
    }
    return $values;
}

function designer_summary(array $record, array $list): string
{
    foreach ($list as $column => $label) {
        $value = trim((string) ($record[$column] ?? ''));
        if ($value !== '') {
            return $value;
        }
    }
    return '#' . (int) ($record['id'] ?? 0);
}

function designer_packages_payload(): array
{
    $categories = rows('SELECT slug, label FROM package_categories ORDER BY sort_order, id');
    $options = [];
    foreach ($categories as $category) {
        $options[$category['slug']] = $category['label'];
    }
    if (!$options) {
        $options['custom'] = 'مخصص';
    }
    $fields = [
        'title' => ['label' => 'عنوان البرنامج', 'type' => 'text', 'required' => true],
        'category' => ['label' => 'التصنيف', 'type' => 'select', 'required' => true, 'options' => $options],
        'category_badge' => ['label' => 'شارة التصنيف على الصورة', 'type' => 'text', 'required' => true],
        'hotel' => ['label' => 'اسم الفندق', 'type' => 'text', 'required' => true],
        'hotel_class' => ['label' => 'قيمة فئة الفندق للبحث', 'type' => 'text', 'hint' => 'مثل 5star-view، وتطابق خيار البحث'],
        'distance' => ['label' => 'الموقع أو المسافة', 'type' => 'text'],
        'duration_days' => ['label' => 'مدة الإقامة للبحث', 'type' => 'text', 'hint' => 'مثل 7 أو 10 أو 14'],
        'description' => ['label' => 'الوصف', 'type' => 'textarea', 'required' => true],
        'price' => ['label' => 'السعر كما يظهر للزائر', 'type' => 'text', 'required' => true],
        'image' => ['label' => 'الصورة', 'type' => 'image', 'required' => true],
        'features' => ['label' => 'المزايا', 'type' => 'textarea', 'hint' => 'سطر لكل ميزة'],
        'sort_order' => ['label' => 'الترتيب', 'type' => 'number'],
        'is_active' => ['label' => 'ظاهر في الموقع', 'type' => 'checkbox'],
    ];
    $items = [];
    foreach (rows('SELECT * FROM packages ORDER BY sort_order, id') as $package) {
        $features = rows('SELECT feature_text FROM package_features WHERE package_id = ? ORDER BY sort_order, id', [$package['id']]);
        $package['features'] = implode("\n", array_column($features, 'feature_text'));
        $items[] = [
            'id' => (int) $package['id'],
            'summary' => $package['title'],
            'values' => designer_public_values($package, $fields),
            'preview' => asset_url((string) $package['image']),
        ];
    }
    return [
        'kind' => 'packages',
        'type' => 'packages',
        'title' => 'بطاقات البرامج',
        'intro' => 'كل برنامج يظهر كبطاقة. التصنيفات تُضاف من القائمة أسفل النصوص.',
        'fields' => designer_public_fields($fields),
        'items' => $items,
    ];
}

function designer_cities_payload(): array
{
    $fields = [
        'slug' => ['label' => 'المعرّف اللاتيني', 'type' => 'text', 'required' => true, 'hint' => 'مثل makkah'],
        'tab_label' => ['label' => 'اسم التبويب', 'type' => 'text', 'required' => true],
        'heading' => ['label' => 'عنوان المميزات', 'type' => 'text', 'required' => true],
        'caption' => ['label' => 'عبارة أسفل الصورة', 'type' => 'text', 'required' => true],
        'image' => ['label' => 'الصورة', 'type' => 'image', 'required' => true],
        'features' => ['label' => 'المميزات', 'type' => 'textarea', 'hint' => 'كل سطر: fa-solid fa-kaaba|نص الميزة'],
        'sort_order' => ['label' => 'الترتيب', 'type' => 'number'],
        'is_active' => ['label' => 'ظاهرة', 'type' => 'checkbox'],
    ];
    $items = [];
    foreach (rows('SELECT * FROM cities ORDER BY sort_order, id') as $city) {
        $features = rows('SELECT icon, text FROM city_features WHERE city_id = ? ORDER BY sort_order, id', [$city['id']]);
        $lines = [];
        foreach ($features as $feature) {
            $lines[] = $feature['icon'] . '|' . $feature['text'];
        }
        $city['features'] = implode("\n", $lines);
        $items[] = [
            'id' => (int) $city['id'],
            'summary' => $city['tab_label'],
            'values' => designer_public_values($city, $fields),
            'preview' => asset_url((string) $city['image']),
        ];
    }
    return [
        'kind' => 'cities',
        'type' => 'cities',
        'title' => 'الوجهات',
        'intro' => 'كل وجهة تبويب مستقل مع صورته ومميزاته.',
        'fields' => designer_public_fields($fields),
        'items' => $items,
    ];
}

function designer_save_settings(string $sectionId): void
{
    $section = designer_section($sectionId);
    $groups = $section['groups'];
    if (!$groups) {
        designer_fail('هذا القسم بلا نصوص عامة.');
    }
    $marks = implode(', ', array_fill(0, count($groups), '?'));
    $fields = rows("SELECT skey, field_type, sgroup FROM settings WHERE sgroup IN ($marks)", $groups);
    $update = db()->prepare('UPDATE settings SET svalue = ? WHERE skey = ?');
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
        $update->execute([$value, $key]);
    }
}

function designer_save_collection(string $type): void
{
    $definitions = collections();
    if (!isset($definitions[$type])) {
        designer_fail('مجموعة غير معروفة.');
    }
    $definition = $definitions[$type];
    $data = [];
    foreach ($definition['fields'] as $name => $field) {
        $data[$name] = collect_field_value($name, $field);
    }
    $table = $definition['table'];
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
        $sets = implode(', ', array_map(static function ($column) {
            return $column . ' = ?';
        }, array_keys($data)));
        $statement = db()->prepare("UPDATE {$table} SET {$sets} WHERE id = ?");
        $statement->execute([...array_values($data), $id]);
        return;
    }
    if (array_key_exists('sort_order', $data) && (int) $data['sort_order'] === 0) {
        $data['sort_order'] = (int) db()->query("SELECT COALESCE(MAX(sort_order), 0) + 10 FROM {$table}")->fetchColumn();
    }
    $columns = implode(', ', array_keys($data));
    $marks = implode(', ', array_fill(0, count($data), '?'));
    $statement = db()->prepare("INSERT INTO {$table} ({$columns}) VALUES ({$marks})");
    $statement->execute(array_values($data));
}

function designer_save_package(): void
{
    $title = trim((string) ($_POST['title'] ?? ''));
    $category = trim((string) ($_POST['category'] ?? ''));
    $badge = trim((string) ($_POST['category_badge'] ?? ''));
    $hotel = trim((string) ($_POST['hotel'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $price = trim((string) ($_POST['price'] ?? ''));
    if ($title === '' || $category === '' || $badge === '' || $hotel === '' || $description === '' || $price === '') {
        throw new RuntimeException('أكمل الحقول الأساسية للبرنامج.');
    }
    $known = rows('SELECT slug FROM package_categories WHERE slug = ?', [$category]);
    if (!$known) {
        throw new RuntimeException('التصنيف غير موجود. أضفه أولاً من تصنيفات هذا القسم.');
    }
    $image = save_upload('file_image') ?: trim((string) ($_POST['image'] ?? ''));
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
        db()->prepare('UPDATE packages SET title=?, category=?, category_badge=?, hotel=?, hotel_class=?, distance=?, duration_days=?, description=?, price=?, image=?, sort_order=?, is_active=? WHERE id=?')->execute([...$payload, $id]);
    } else {
        if ($payload[10] === 0) {
            $payload[10] = (int) db()->query('SELECT COALESCE(MAX(sort_order), 0) + 10 FROM packages')->fetchColumn();
        }
        db()->prepare('INSERT INTO packages (title, category, category_badge, hotel, hotel_class, distance, duration_days, description, price, image, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')->execute($payload);
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
}

function designer_save_city(): void
{
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
    $image = save_upload('file_image') ?: trim((string) ($_POST['image'] ?? ''));
    if ($image === '') {
        throw new RuntimeException('أضف صورة للوجهة.');
    }
    $payload = [$slug, $tab, $heading, $image, $caption, (int) ($_POST['sort_order'] ?? 0), isset($_POST['is_active']) ? 1 : 0];
    $id = (int) ($_POST['id'] ?? 0);
    db()->beginTransaction();
    if ($id > 0) {
        db()->prepare('UPDATE cities SET slug=?, tab_label=?, heading=?, image=?, caption=?, sort_order=?, is_active=? WHERE id=?')->execute([...$payload, $id]);
    } else {
        if ($payload[5] === 0) {
            $payload[5] = (int) db()->query('SELECT COALESCE(MAX(sort_order), 0) + 10 FROM cities')->fetchColumn();
        }
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
}

function designer_save_design(): void
{
    $update = db()->prepare('UPDATE settings SET svalue = ? WHERE skey = ?');
    foreach (designer_colors() as $key => $label) {
        $value = trim((string) ($_POST['colors'][$key] ?? ''));
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
            throw new RuntimeException('لون غير صالح: ' . $label);
        }
        $update->execute([$value, $key]);
    }
    $fonts = designer_fonts();
    $body = (string) ($_POST['body_font'] ?? 'Tajawal');
    $title = (string) ($_POST['title_font'] ?? 'Amiri');
    if (!isset($fonts[$body]) || !isset($fonts[$title])) {
        throw new RuntimeException('خط غير مدعوم.');
    }
    $padding = max(32, min(200, (int) ($_POST['section_padding'] ?? 96)));
    $hero = max(40, min(100, (int) ($_POST['hero_min_height'] ?? 92)));
    $allowed = gf_content_ids();
    $raw = array_filter(array_map('trim', explode(',', (string) ($_POST['section_order'] ?? ''))));
    $order = [];
    foreach ($raw as $id) {
        if (in_array($id, $allowed, true) && !in_array($id, $order, true)) {
            $order[] = $id;
        }
    }
    foreach ($allowed as $id) {
        if (!in_array($id, $order, true)) {
            $order[] = $id;
        }
    }
    $update->execute([$body, 'body_font']);
    $update->execute([$title, 'title_font']);
    $update->execute([(string) $padding, 'section_padding']);
    $update->execute([(string) $hero, 'hero_min_height']);
    $update->execute([implode(',', $order), 'section_order']);
    foreach (designer_flags() as $key => $label) {
        $update->execute([isset($_POST['flags'][$key]) && (string) $_POST['flags'][$key] === '1' ? '1' : '0', $key]);
    }
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $sectionId = (string) ($_GET['section'] ?? '');
        $section = designer_section($sectionId);
        $blocks = [];
        foreach ($section['collections'] as $type) {
            $blocks[] = designer_collection_payload($type);
        }
        if (($section['special'] ?? '') === 'packages') {
            array_unshift($blocks, designer_packages_payload());
        }
        if (($section['special'] ?? '') === 'cities') {
            array_unshift($blocks, designer_cities_payload());
        }
        json_response([
            'ok' => true,
            'label' => $section['label'],
            'settings' => designer_settings_payload($section['groups']),
            'blocks' => $blocks,
        ]);
    }

    designer_check_csrf();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'save_design') {
        designer_save_design();
        json_response(['ok' => true, 'message' => 'تم حفظ التصميم.']);
    }
    if ($action === 'save_settings') {
        designer_save_settings((string) ($_POST['section'] ?? ''));
        json_response(['ok' => true, 'message' => 'تم حفظ النصوص.']);
    }
    if ($action === 'save_item') {
        $kind = (string) ($_POST['kind'] ?? '');
        if ($kind === 'packages') {
            designer_save_package();
        } elseif ($kind === 'cities') {
            designer_save_city();
        } elseif ($kind === 'collection') {
            designer_save_collection((string) ($_POST['type'] ?? ''));
        } else {
            designer_fail('نوع العنصر غير معروف.');
        }
        json_response(['ok' => true, 'message' => 'تم حفظ العنصر.']);
    }
    if ($action === 'delete_item') {
        $kind = (string) ($_POST['kind'] ?? '');
        $id = (int) ($_POST['id'] ?? 0);
        if ($id < 1) {
            designer_fail('العنصر غير محدد.');
        }
        if ($kind === 'packages') {
            db()->prepare('DELETE FROM packages WHERE id = ?')->execute([$id]);
        } elseif ($kind === 'cities') {
            db()->prepare('DELETE FROM cities WHERE id = ?')->execute([$id]);
        } elseif ($kind === 'collection') {
            $type = (string) ($_POST['type'] ?? '');
            $definitions = collections();
            if (!isset($definitions[$type])) {
                designer_fail('مجموعة غير معروفة.');
            }
            $table = $definitions[$type]['table'];
            db()->prepare("DELETE FROM {$table} WHERE id = ?")->execute([$id]);
        } else {
            designer_fail('نوع العنصر غير معروف.');
        }
        json_response(['ok' => true, 'message' => 'تم الحذف.']);
    }
    if ($action === 'move_item') {
        $kind = (string) ($_POST['kind'] ?? '');
        $direction = (string) ($_POST['direction'] ?? '') === 'up' ? 'up' : 'down';
        $id = (int) ($_POST['id'] ?? 0);
        if ($kind === 'packages') {
            move_collection_row('packages', $id, $direction);
        } elseif ($kind === 'cities') {
            move_collection_row('cities', $id, $direction);
        } elseif ($kind === 'collection') {
            $type = (string) ($_POST['type'] ?? '');
            $definitions = collections();
            if (!isset($definitions[$type])) {
                designer_fail('مجموعة غير معروفة.');
            }
            move_collection_row($definitions[$type]['table'], $id, $direction);
        } else {
            designer_fail('نوع العنصر غير معروف.');
        }
        json_response(['ok' => true, 'message' => 'تم تحديث الترتيب.']);
    }
    designer_fail('طلب غير معروف.');
} catch (Throwable $e) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }
    designer_fail($e->getMessage());
}
