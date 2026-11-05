<?php

const GF_JSON = true;
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/collections.php';
require_once dirname(__DIR__) . '/includes/content_store.php';
require_once dirname(__DIR__) . '/includes/snapshots.php';

header('Cache-Control: no-store');
require_admin();

function designer_fail(string $message, int $status = 400): void
{
    json_response(['ok' => false, 'message' => $message], $status);
}

function default_design(): array
{
    $values = [
        'color_green' => '#1D3C78',
        'color_darkgreen' => '#0F2350',
        'color_gold' => '#F2C14E',
        'color_lightgold' => '#F8DC8E',
        'color_ivory' => '#F4F8FC',
        'color_lightivory' => '#FBFDFF',
        'color_slate' => '#24324A',
        'body_font' => 'Tajawal',
        'title_font' => 'Amiri',
        'section_padding' => '96',
        'hero_min_height' => '92',
        'section_order' => implode(',', gf_content_ids()),
    ];
    foreach (array_keys(designer_flags()) as $flag) {
        $values[$flag] = '1';
    }
    return $values;
}

function public_fields(array $fields): array
{
    $public = [];
    foreach ($fields as $name => $field) {
        $public[$name] = [
            'label' => $field['label'] ?? $name,
            'type' => $field['type'] ?? 'text',
            'hint' => $field['hint'] ?? '',
            'required' => !empty($field['required']),
            'translatable' => !empty($field['translatable']),
            'options' => (object) ($field['options'] ?? []),
        ];
    }
    return $public;
}

function public_values(array $record, array $fields): array
{
    $values = [];
    foreach ($fields as $name => $field) {
        $type = $field['type'] ?? 'text';
        if ($type === 'checkbox') {
            $values[$name] = (int) ($record[$name] ?? 0) === 1 ? 1 : 0;
            continue;
        }
        $values[$name] = (string) ($record[$name] ?? '');
        if (!empty($field['translatable'])) {
            foreach (extra_languages() as $lang) {
                $values[$name . '_' . $lang] = (string) ($record[$name . '_' . $lang] ?? '');
            }
        }
    }
    return $values;
}

function summary_of(array $record, array $list): string
{
    foreach (array_keys($list) as $column) {
        $value = trim((string) ($record[$column] ?? ''));
        if ($value !== '') {
            return mb_strimwidth($value, 0, 70, '…');
        }
    }
    return '#' . (int) ($record['id'] ?? 0);
}

function settings_payload(array $groups): array
{
    if (!$groups) {
        return [];
    }
    $marks = implode(', ', array_fill(0, count($groups), '?'));
    $payload = [];
    foreach (rows("SELECT * FROM settings WHERE sgroup IN ($marks) ORDER BY sorder, skey", $groups) as $item) {
        $payload[] = [
            'key' => $item['skey'],
            'label' => $item['label'],
            'type' => $item['field_type'],
            'hint' => (string) $item['hint'],
            'translatable' => (int) $item['translatable'] === 1,
            'value' => $item['field_type'] === 'password' ? '' : (string) $item['svalue'],
            'value_fr' => (string) $item['svalue_fr'],
            'value_en' => (string) $item['svalue_en'],
        ];
    }
    return $payload;
}

function collection_payload(string $type): array
{
    $definition = collection_definition($type);
    $items = [];
    foreach (rows("SELECT * FROM `{$definition['table']}` ORDER BY sort_order ASC, id ASC") as $record) {
        $items[] = [
            'id' => (int) $record['id'],
            'summary' => summary_of($record, $definition['list']),
            'active' => !isset($record['is_active']) || (int) $record['is_active'] === 1,
            'values' => public_values($record, $definition['fields']),
        ];
    }
    return ['kind' => 'collection', 'type' => $type, 'title' => $definition['title'], 'intro' => $definition['intro'], 'fields' => public_fields($definition['fields']), 'items' => $items];
}

function packages_payload(): array
{
    $fields = package_fields();
    $items = [];
    foreach (rows('SELECT id FROM packages ORDER BY sort_order, id') as $row) {
        $package = package_record((int) $row['id']);
        $items[] = ['id' => (int) $package['id'], 'summary' => $package['title'], 'active' => (int) $package['is_active'] === 1, 'values' => public_values($package, $fields)];
    }
    return ['kind' => 'packages', 'type' => 'packages', 'title' => 'بطاقات البرامج', 'intro' => 'كل برنامج بطاقة في الموقع. التصنيفات في القائمة التالية.', 'fields' => public_fields($fields), 'items' => $items];
}

function cities_payload(): array
{
    $fields = city_fields();
    $items = [];
    foreach (rows('SELECT id FROM cities ORDER BY sort_order, id') as $row) {
        $city = city_record((int) $row['id']);
        $items[] = ['id' => (int) $city['id'], 'summary' => $city['tab_label'], 'active' => (int) $city['is_active'] === 1, 'values' => public_values($city, $fields)];
    }
    return ['kind' => 'cities', 'type' => 'cities', 'title' => 'الوجهات', 'intro' => 'كل وجهة تبويب مستقل بصورته ومميزاته.', 'fields' => public_fields($fields), 'items' => $items];
}

function item_table(string $kind, string $type): string
{
    if ($kind === 'packages') {
        return 'packages';
    }
    if ($kind === 'cities') {
        return 'cities';
    }
    return collection_definition($type)['table'];
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $catalog = designer_catalog();
        $sectionId = (string) ($_GET['section'] ?? '');
        if (!isset($catalog[$sectionId])) {
            designer_fail('قسم غير معروف.');
        }
        $section = $catalog[$sectionId];
        $blocks = [];
        if (($section['special'] ?? '') === 'packages') {
            $blocks[] = packages_payload();
        }
        if (($section['special'] ?? '') === 'cities') {
            $blocks[] = cities_payload();
        }
        foreach ($section['collections'] as $type) {
            $blocks[] = collection_payload($type);
        }
        json_response(['ok' => true, 'label' => $section['label'], 'settings' => settings_payload($section['groups']), 'blocks' => $blocks]);
    }

    if (!csrf_valid()) {
        designer_fail('انتهت صلاحية الصفحة. أعد تحميل المصمم.');
    }
    $action = (string) ($_POST['action'] ?? '');
    $kind = (string) ($_POST['kind'] ?? '');
    $type = (string) ($_POST['type'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);

    switch ($action) {
        case 'save_design':
            set_setting('design_draft', json_encode(sanitize_design_input($_POST), JSON_UNESCAPED_UNICODE));
            json_response(['ok' => true, 'message' => 'حُفظ في المسودة. اضغط «نشر» ليراه الزوار.', 'draft' => true]);
            break;
        case 'publish_design':
            $draft = design_draft();
            if (!$draft) {
                designer_fail('لا توجد تعديلات تصميم غير منشورة.');
            }
            create_snapshot('تلقائية: قبل نشر التصميم');
            foreach ($draft as $key => $value) {
                set_setting($key, (string) $value);
            }
            set_setting('design_draft', '');
            json_response(['ok' => true, 'message' => 'نُشر التصميم على الموقع.', 'draft' => false]);
            break;
        case 'discard_design':
            set_setting('design_draft', '');
            json_response(['ok' => true, 'message' => 'أُلغيت تعديلات التصميم غير المنشورة.', 'draft' => false, 'reload' => true]);
            break;
        case 'reset_design':
            set_setting('design_draft', json_encode(default_design(), JSON_UNESCAPED_UNICODE));
            json_response(['ok' => true, 'message' => 'وُضع التصميم الأصلي في المسودة. راجعه ثم اضغط «نشر».', 'draft' => true, 'reload' => true]);
            break;
        case 'save_settings':
            $catalog = designer_catalog();
            $sectionId = (string) ($_POST['section'] ?? '');
            if (!isset($catalog[$sectionId]) || !$catalog[$sectionId]['groups']) {
                designer_fail('هذا القسم بلا نصوص عامة.');
            }
            save_settings_groups($catalog[$sectionId]['groups'], $_POST);
            json_response(['ok' => true, 'message' => 'تم حفظ النصوص ونشرها.']);
            break;
        case 'save_item':
            if ($kind === 'packages') {
                save_package($_POST, $id);
            } elseif ($kind === 'cities') {
                save_city($_POST, $id);
            } elseif ($kind === 'collection') {
                save_collection_item($type, $_POST, $id);
            } else {
                designer_fail('نوع العنصر غير معروف.');
            }
            json_response(['ok' => true, 'message' => 'تم حفظ العنصر ونشره.']);
            break;
        case 'delete_item':
            if ($id < 1) {
                designer_fail('العنصر غير محدد.');
            }
            if ($kind === 'collection') {
                delete_collection_item($type, $id);
            } else {
                db()->prepare('DELETE FROM `' . item_table($kind, $type) . '` WHERE id = ?')->execute([$id]);
            }
            json_response(['ok' => true, 'message' => 'تم الحذف.']);
            break;
        case 'move_item':
            move_record(item_table($kind, $type), $id, (string) ($_POST['direction'] ?? '') === 'up' ? 'up' : 'down');
            json_response(['ok' => true, 'message' => 'تم تحديث الترتيب.']);
            break;
        case 'snapshot':
            create_snapshot('يدوية من المصمم');
            json_response(['ok' => true, 'message' => 'أُنشئت نقطة استرجاع.']);
            break;
    }
    designer_fail('طلب غير معروف.');
} catch (Throwable $e) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }
    if ($e instanceof PDOException || !$e instanceof RuntimeException) {
        log_error('designer_api: ' . $e->getMessage());
        designer_fail('حدث خطأ غير متوقع أثناء الحفظ.', 500);
    }
    designer_fail($e->getMessage());
}
