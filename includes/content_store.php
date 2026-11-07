<?php

/**
 * Validation and persistence shared by the admin pages and the visual designer,
 * so both write content through exactly the same rules.
 */

function split_lines(string $text): array
{
    return array_map('trim', preg_split('/\r\n|\r|\n/', $text) ?: []);
}

function field_value_for_lang(array $source, string $name, string $lang): string
{
    $key = $lang === default_lang() ? $name : $name . '_' . $lang;
    $value = $source[$key] ?? '';
    return is_scalar($value) ? trim((string) $value) : '';
}

function check_text_rules(string $value, array $field, string $label): void
{
    $max = (int) ($field['max'] ?? 0);
    if ($max > 0 && mb_strlen($value) > $max) {
        throw new RuntimeException('النص في «' . $label . '» أطول من ' . $max . ' حرفاً.');
    }
}

/** Reads one record from $source (normally $_POST) according to the field definitions. */
function collect_record(array $fields, array $source): array
{
    $record = [];
    foreach ($fields as $name => $field) {
        $type = $field['type'] ?? 'text';
        $label = $field['label'] ?? $name;
        if (in_array($type, ['gallery', 'departures'], true) || (in_array($type, ['lines', 'blocks'], true) && empty($field['column']))) {
            continue;
        }
        if ($type === 'checkbox') {
            $record[$name] = isset($source[$name]) && (string) $source[$name] !== '0' ? 1 : 0;
            continue;
        }
        if ($type === 'image') {
            $uploaded = save_upload('file_' . $name);
            $value = $uploaded ?: field_value_for_lang($source, $name, default_lang());
            if (!empty($field['required']) && $value === '') {
                throw new RuntimeException('أضف صورة في «' . $label . '».');
            }
            $record[$name] = $value;
            continue;
        }
        if ($type === 'number') {
            $value = (int) field_value_for_lang($source, $name, default_lang());
            if (isset($field['min'])) {
                $value = max((int) $field['min'], $value);
            }
            if (isset($field['max_value'])) {
                $value = min((int) $field['max_value'], $value);
            }
            $record[$name] = $value;
            continue;
        }

        $value = field_value_for_lang($source, $name, default_lang());
        if ($type === 'select' && !array_key_exists($value, $field['options'] ?? [])) {
            throw new RuntimeException('اختر قيمة صحيحة في «' . $label . '».');
        }
        if (!empty($field['required']) && $value === '') {
            throw new RuntimeException('الحقل «' . $label . '» مطلوب.');
        }
        if (!empty($field['pattern']) && $value !== '' && !preg_match($field['pattern'], $value)) {
            throw new RuntimeException('صيغة «' . $label . '» غير صحيحة. ' . ($field['hint'] ?? ''));
        }
        check_text_rules($value, $field, $label);
        $record[$name] = $value;

        if (!empty($field['translatable'])) {
            foreach (extra_languages() as $lang) {
                $translated = field_value_for_lang($source, $name, $lang);
                check_text_rules($translated, $field, $label . ' (' . languages()[$lang]['name'] . ')');
                $record[$name . '_' . $lang] = $translated;
            }
        }
    }
    return $record;
}

function next_sort_order(string $table): int
{
    return (int) db()->query("SELECT COALESCE(MAX(sort_order), 0) + 10 FROM `{$table}`")->fetchColumn();
}

function write_record(string $table, array $record, int $id): int
{
    try {
        if ($id > 0) {
            $sets = implode(', ', array_map(static function ($column) {
                return "`{$column}` = ?";
            }, array_keys($record)));
            db()->prepare("UPDATE `{$table}` SET {$sets} WHERE id = ?")->execute([...array_values($record), $id]);
            return $id;
        }
        if (array_key_exists('sort_order', $record) && (int) $record['sort_order'] === 0) {
            $record['sort_order'] = next_sort_order($table);
        }
        $columns = implode(', ', array_map(static function ($column) {
            return "`{$column}`";
        }, array_keys($record)));
        $marks = implode(', ', array_fill(0, count($record), '?'));
        db()->prepare("INSERT INTO `{$table}` ({$columns}) VALUES ({$marks})")->execute(array_values($record));
        return (int) db()->lastInsertId();
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            throw new RuntimeException('هذه القيمة مستخدمة في عنصر آخر. اختر معرّفاً مختلفاً.');
        }
        throw $e;
    }
}

function collection_definition(string $type): array
{
    $definitions = collections();
    if (!isset($definitions[$type])) {
        throw new RuntimeException('القسم غير موجود.');
    }
    return $definitions[$type];
}

function save_collection_item(string $type, array $source, int $id): int
{
    $definition = collection_definition($type);
    $record = collect_record($definition['fields'], $source);
    $oldSlug = null;
    if ($type === 'categories' && $id > 0) {
        $statement = db()->prepare('SELECT slug FROM package_categories WHERE id = ?');
        $statement->execute([$id]);
        $oldSlug = $statement->fetchColumn();
    }
    $id = write_record($definition['table'], $record, $id);
    if ($oldSlug !== null && $oldSlug !== false && $oldSlug !== $record['slug']) {
        db()->prepare('UPDATE packages SET category = ? WHERE category = ?')->execute([$record['slug'], $oldSlug]);
    }
    return $id;
}

function delete_collection_item(string $type, int $id): void
{
    $definition = collection_definition($type);
    if ($type === 'categories') {
        $statement = db()->prepare('SELECT COUNT(*) FROM packages p JOIN package_categories c ON c.slug = p.category WHERE c.id = ?');
        $statement->execute([$id]);
        $count = (int) $statement->fetchColumn();
        if ($count > 0) {
            throw new RuntimeException('لا يمكن حذف هذا التصنيف لأن فيه ' . $count . ' برنامج. انقل البرامج إلى تصنيف آخر أولاً.');
        }
    }
    db()->prepare("DELETE FROM `{$definition['table']}` WHERE id = ?")->execute([$id]);
}

function movable_tables(): array
{
    return ['nav_links', 'hero_slides', 'stats', 'about_points', 'package_categories', 'filter_options', 'services', 'calc_tiers', 'guides', 'testimonials', 'footer_links', 'packages', 'cities'];
}

function move_record(string $table, int $id, string $direction): void
{
    if (!in_array($table, movable_tables(), true)) {
        return;
    }
    $ids = array_map('intval', array_column(rows("SELECT id FROM `{$table}` ORDER BY sort_order ASC, id ASC"), 'id'));
    $index = array_search($id, $ids, true);
    if ($index === false) {
        return;
    }
    $swap = $direction === 'up' ? $index - 1 : $index + 1;
    if (!isset($ids[$swap])) {
        return;
    }
    [$ids[$index], $ids[$swap]] = [$ids[$swap], $ids[$index]];
    $statement = db()->prepare("UPDATE `{$table}` SET sort_order = ? WHERE id = ?");
    foreach ($ids as $position => $rowId) {
        $statement->execute([($position + 1) * 10, $rowId]);
    }
}

function package_record(int $id): ?array
{
    $statement = db()->prepare('SELECT * FROM packages WHERE id = ?');
    $statement->execute([$id]);
    $package = $statement->fetch();
    if (!$package) {
        return null;
    }
    $features = rows('SELECT * FROM package_features WHERE package_id = ? ORDER BY sort_order, id', [$id]);
    $package['features'] = implode("\n", array_column($features, 'feature_text'));
    foreach (extra_languages() as $lang) {
        $package['features_' . $lang] = implode("\n", array_map(static function ($row) use ($lang) {
            return (string) ($row['feature_text_' . $lang] ?? '');
        }, $features));
    }
    $package['gallery'] = implode("\n", gallery_list($package));
    $package['departures'] = departures_for_admin($id);
    return $package;
}

function save_package(array $source, int $id): int
{
    $record = collect_record(package_fields(), $source);
    if (!room_prices($record) && trim((string) $record['price']) === '') {
        throw new RuntimeException('أدخل سعر غرفة واحدة على الأقل، أو نص السعر في البطاقة.');
    }
    $slug = $record['slug'] !== '' ? $record['slug'] : slugify($record['title_en'] ?: $record['title_fr']);
    $record['slug'] = unique_package_slug($slug !== '' ? $slug : 'program', $id);
    $record['gallery'] = collect_gallery($source);
    $arabic = split_lines((string) ($source['features'] ?? ''));
    $translated = [];
    foreach (extra_languages() as $lang) {
        $translated[$lang] = split_lines((string) ($source['features_' . $lang] ?? ''));
    }

    db()->beginTransaction();
    try {
        $id = write_record('packages', $record, $id);
        db()->prepare('DELETE FROM package_features WHERE package_id = ?')->execute([$id]);
        $insert = db()->prepare('INSERT INTO package_features (package_id, feature_text, feature_text_fr, feature_text_en, sort_order) VALUES (?, ?, ?, ?, ?)');
        $position = 1;
        foreach ($arabic as $index => $line) {
            if ($line === '') {
                continue;
            }
            $insert->execute([$id, str_limit($line, 255), str_limit($translated['fr'][$index] ?? '', 255), str_limit($translated['en'][$index] ?? '', 255), $position * 10]);
            $position++;
        }
        save_departures($id, $source);
        db()->commit();
    } catch (Throwable $e) {
        db()->rollBack();
        throw $e;
    }
    return $id;
}

function city_record(int $id): ?array
{
    $statement = db()->prepare('SELECT * FROM cities WHERE id = ?');
    $statement->execute([$id]);
    $city = $statement->fetch();
    if (!$city) {
        return null;
    }
    $features = rows('SELECT * FROM city_features WHERE city_id = ? ORDER BY sort_order, id', [$id]);
    $city['feature_icons'] = implode("\n", array_column($features, 'icon'));
    $city['features'] = implode("\n", array_column($features, 'text'));
    foreach (extra_languages() as $lang) {
        $city['features_' . $lang] = implode("\n", array_map(static function ($row) use ($lang) {
            return (string) ($row['text_' . $lang] ?? '');
        }, $features));
    }
    return $city;
}

function save_city(array $source, int $id): int
{
    $record = collect_record(city_fields(), $source);
    $icons = split_lines((string) ($source['feature_icons'] ?? ''));
    $arabic = split_lines((string) ($source['features'] ?? ''));
    $translated = [];
    foreach (extra_languages() as $lang) {
        $translated[$lang] = split_lines((string) ($source['features_' . $lang] ?? ''));
    }

    db()->beginTransaction();
    try {
        $id = write_record('cities', $record, $id);
        db()->prepare('DELETE FROM city_features WHERE city_id = ?')->execute([$id]);
        $insert = db()->prepare('INSERT INTO city_features (city_id, icon, text, text_fr, text_en, sort_order) VALUES (?, ?, ?, ?, ?, ?)');
        $position = 1;
        foreach ($arabic as $index => $line) {
            if ($line === '') {
                continue;
            }
            $icon = $icons[$index] ?? '';
            if (!preg_match('/^[a-z0-9\- ]{3,80}$/', $icon)) {
                $icon = 'fa-solid fa-check';
            }
            $insert->execute([$id, $icon, str_limit($line, 255), str_limit($translated['fr'][$index] ?? '', 255), str_limit($translated['en'][$index] ?? '', 255), $position * 10]);
            $position++;
        }
        db()->commit();
    } catch (Throwable $e) {
        db()->rollBack();
        throw $e;
    }
    return $id;
}

/** Saves every setting of the given groups from $source['values'] / ['values_fr'] / ['values_en']. */
function save_settings_groups(array $groups, array $source): void
{
    $groups = array_values(array_diff($groups, ['system']));
    if (!$groups) {
        return;
    }
    $marks = implode(', ', array_fill(0, count($groups), '?'));
    $fields = rows("SELECT skey, field_type, translatable, label FROM settings WHERE sgroup IN ($marks)", $groups);
    $values = is_array($source['values'] ?? null) ? $source['values'] : [];
    $emailKeys = ['notify_email', 'mail_from', 'top_email', 'footer_email'];

    $updates = [];
    foreach ($fields as $field) {
        $key = $field['skey'];
        $type = $field['field_type'];
        $raw = is_scalar($values[$key] ?? null) ? trim((string) $values[$key]) : '';
        if ($type === 'bool') {
            $value = isset($values[$key]) ? '1' : '0';
        } elseif ($type === 'password') {
            if ($raw === '') {
                continue;
            }
            $value = $raw;
        } elseif ($type === 'image') {
            $value = save_upload('file_' . $key) ?: $raw;
        } elseif ($type === 'number') {
            $value = (string) (int) $raw;
        } elseif ($type === 'html') {
            $value = clean_rich_text($raw);
        } elseif ($type === 'color') {
            if (!valid_hex($raw)) {
                throw new RuntimeException('لون غير صالح في «' . $field['label'] . '».');
            }
            $value = $raw;
        } else {
            $value = $raw;
        }
        if (in_array($key, $emailKeys, true) && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('البريد في «' . $field['label'] . '» غير صحيح.');
        }
        if ($key === 'site_url' && $value !== '' && !preg_match('#^https?://[^\s/]+(/[^\s]*)?$#i', $value)) {
            throw new RuntimeException('رابط الموقع يجب أن يبدأ بـ https://');
        }
        if ($key === 'smtp_secure' && !in_array($value, ['tls', 'ssl', 'none'], true)) {
            throw new RuntimeException('نوع التشفير يجب أن يكون tls أو ssl أو none.');
        }
        if ($key === 'wa_number') {
            $value = preg_replace('/\D+/', '', $value) ?? '';
            if ($value !== '' && (strlen($value) < 9 || strlen($value) > 15)) {
                throw new RuntimeException('رقم واتساب يجب أن يكون بالصيغة الدولية، مثل 212612345678.');
            }
        }
        if (in_array($key, ['phone_display', 'agency_phone2'], true) && $value !== '' && !preg_match('/^\+?[\d\s\-().]{6,30}$/', $value)) {
            throw new RuntimeException('رقم الهاتف في «' . $field['label'] . '» غير صحيح.');
        }
        if ((strpos($key, 'social_') === 0 || $key === 'agency_map_url') && $value !== '' && !preg_match('#^https://[^\s]+$#i', $value)) {
            throw new RuntimeException('الرابط في «' . $field['label'] . '» يجب أن يبدأ بـ https://');
        }
        if ($key === 'agency_ice' && $value !== '' && !preg_match('/^\d{15}$/', $value)) {
            throw new RuntimeException('رقم ICE يتكون من 15 رقماً.');
        }
        if (mb_strlen($value) > 20000) {
            throw new RuntimeException('النص في «' . $field['label'] . '» طويل جداً.');
        }

        $row = ['svalue' => $value];
        if ((int) $field['translatable'] === 1) {
            foreach (extra_languages() as $lang) {
                $list = is_array($source['values_' . $lang] ?? null) ? $source['values_' . $lang] : [];
                $translated = is_scalar($list[$key] ?? null) ? trim((string) $list[$key]) : '';
                $row['svalue_' . $lang] = $type === 'html' ? clean_rich_text($translated) : $translated;
            }
        }
        $updates[$key] = $row;
    }

    db()->beginTransaction();
    try {
        foreach ($updates as $key => $row) {
            $sets = implode(', ', array_map(static function ($column) {
                return "`{$column}` = ?";
            }, array_keys($row)));
            db()->prepare("UPDATE settings SET {$sets} WHERE skey = ?")->execute([...array_values($row), $key]);
        }
        db()->commit();
    } catch (Throwable $e) {
        db()->rollBack();
        throw $e;
    }
}

/** Shipped sample values that must be replaced with the agency's real details before launch. */
function agency_placeholders(): array
{
    return [
        'license_number' => ['GF-8842/2026', 'رقم الترخيص السياحي'],
        'footer_cr_number' => ['1010892231', 'السجل التجاري'],
        'phone_display' => ['+212 5 22 00 00 00', 'الهاتف الرئيسي'],
        'wa_number' => ['212600000000', 'رقم واتساب'],
        'footer_address' => ['شارع محمد الخامس، الدار البيضاء، المغرب', 'العنوان'],
        'top_email' => ['info@goodfly-travel.com', 'البريد العام'],
        'footer_email' => ['booking@goodfly-travel.com', 'بريد الحجوزات'],
    ];
}

/** Agency fields still holding a sample value: key => label. */
function agency_placeholders_left(): array
{
    $left = [];
    foreach (agency_placeholders() as $key => [$placeholder, $label]) {
        if (trim(s($key)) === $placeholder) {
            $left[$key] = $label;
        }
    }
    return $left;
}

function set_setting(string $key, string $value): void
{
    db()->prepare('UPDATE settings SET svalue = ? WHERE skey = ?')->execute([$value, $key]);
    if (isset($GLOBALS['SETTINGS'][$key])) {
        $GLOBALS['SETTINGS'][$key]['ar'] = $value;
    }
}
