<?php

function admin_menu(): array
{
    return [
        ['index.php', 'لوحة التحكم', 'fa-gauge-high', 'index.php'],
        ['designer.php', 'المصمم المرئي', 'fa-pen-ruler', 'designer.php'],
        ['settings.php', 'كل النصوص والتفاصيل', 'fa-sliders', 'settings.php'],
        ['collection.php?type=nav', 'قائمة الموقع', 'fa-bars', 'nav'],
        ['collection.php?type=hero', 'صور الواجهة', 'fa-images', 'hero'],
        ['collection.php?type=stats', 'أرقام الثقة', 'fa-chart-simple', 'stats'],
        ['collection.php?type=about', 'نقاط عن الوكالة', 'fa-certificate', 'about'],
        ['collection.php?type=categories', 'تصنيفات البرامج', 'fa-tags', 'categories'],
        ['packages.php', 'برامج الحج والعمرة', 'fa-kaaba', 'packages.php'],
        ['collection.php?type=filters', 'خيارات البحث والحجز', 'fa-filter', 'filters'],
        ['collection.php?type=services', 'الخدمات', 'fa-concierge-bell', 'services'],
        ['collection.php?type=tiers', 'فئات الحاسبة', 'fa-calculator', 'tiers'],
        ['cities.php', 'مكة والمدينة', 'fa-mosque', 'cities.php'],
        ['collection.php?type=guides', 'دليل المناسك', 'fa-book-open', 'guides'],
        ['collection.php?type=testimonials', 'آراء العملاء', 'fa-star', 'testimonials'],
        ['collection.php?type=footer', 'روابط التذييل', 'fa-link', 'footer'],
        ['collection.php?type=i18n', 'ترجمة زر الحجز', 'fa-language', 'i18n'],
        ['bookings.php', 'طلبات الحجز', 'fa-inbox', 'bookings.php'],
        ['subscribers.php', 'النشرة البريدية', 'fa-envelope', 'subscribers.php'],
        ['account.php', 'الحساب وكلمة المرور', 'fa-user-lock', 'account.php'],
    ];
}

function admin_header(string $title, string $active): void
{
    $flash = flash_take();
    $admin = current_admin();
    echo '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . h($title) . ' | إدارة Good Fly</title>';
    echo '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    echo '<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">';
    echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';
    echo '<style>
        *{box-sizing:border-box} body{margin:0;font-family:Tajawal,Tahoma,sans-serif;background:#F7F5EF;color:#183B35}
        a{color:#183B35} .layout{display:flex;min-height:100vh}
        .sidebar{width:280px;background:#102924;color:#fff;padding:22px 14px;flex-shrink:0}
        .brand{font-weight:800;letter-spacing:1px;padding:8px 12px 18px;color:#D8C39B}
        .sidebar a{display:flex;align-items:center;gap:10px;color:rgba(255,255,255,.82);text-decoration:none;padding:10px 12px;border-radius:12px;margin-bottom:4px;font-size:14px}
        .sidebar a.active,.sidebar a:hover{background:rgba(216,195,155,.16);color:#EADBC0}
        .content{flex:1;padding:28px;min-width:0}
        .top{display:flex;justify-content:space-between;gap:12px;align-items:center;margin-bottom:18px;flex-wrap:wrap}
        h1{font-size:28px;margin:0} h2{margin:0 0 8px}
        .card{background:#fff;border:1px solid rgba(216,195,155,.45);border-radius:18px;padding:18px 20px;box-shadow:0 12px 30px -24px rgba(24,59,53,.8)}
        label{display:block;font-weight:800;margin:14px 0 6px;font-size:14px}
        input[type=text],input[type=number],input[type=password],input[type=email],input[type=date],textarea,select{width:100%;padding:11px 12px;border:1px solid #e6e1d6;border-radius:12px;font-family:inherit;background:#fff;color:#183B35}
        textarea{min-height:110px;resize:vertical}
        .btn,.btn:visited{display:inline-flex;align-items:center;justify-content:center;gap:6px;background:linear-gradient(135deg,#D8C39B,#C4A976);color:#102924;border:0;border-radius:999px;padding:10px 16px;font-weight:800;cursor:pointer;text-decoration:none}
        .btn.secondary{background:#183B35;color:#fff}.btn.ghost{background:transparent;color:#183B35;border:1px solid rgba(24,59,53,.2)}
        .btn.danger{background:#8d2f2f;color:#fff}.btn.small{padding:6px 10px;font-size:12px}
        table{width:100%;border-collapse:collapse} th,td{padding:10px 8px;border-bottom:1px solid #eee;text-align:right;vertical-align:middle;font-size:14px}
        .flash{padding:12px 14px;border-radius:12px;margin-bottom:14px}.ok{background:#e7f6ee}.err{background:#fdecec}
        .hint{color:#6d746f;font-size:12px;font-weight:500;margin-top:4px}
        .intro{color:#405650;margin:0 0 16px}
        .actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
        .thumb{width:72px;height:48px;object-fit:cover;border-radius:8px;border:1px solid #eadfca}
        .tabs{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px}
        .tabs a{text-decoration:none;padding:8px 12px;border-radius:999px;background:#fff;border:1px solid #eadfca;font-size:13px}
        .tabs a.active{background:#183B35;color:#F7F5EF}
        .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:18px}
        .stat strong{display:block;font-size:28px}
        .check{display:flex;align-items:center;gap:8px;font-weight:700}
        .check input{width:auto}
        @media(max-width:900px){.layout{display:block}.sidebar{width:auto}.content{padding:16px}}
    </style></head><body><div class="layout"><aside class="sidebar"><div class="brand">GOOD FLY</div>';
    foreach (admin_menu() as $item) {
        $class = $item[3] === $active ? ' class="active"' : '';
        echo '<a' . $class . ' href="' . h($item[0]) . '"><i class="fa-solid ' . h($item[2]) . '"></i><span>' . h($item[1]) . '</span></a>';
    }
    echo '<a href="../index.php" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i><span>عرض الموقع</span></a>';
    echo '<a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>خروج (' . h($admin['username'] ?? '') . ')</span></a>';
    echo '</aside><main class="content"><div class="top"><h1>' . h($title) . '</h1></div>';
    if ($flash) {
        $class = ($flash['type'] ?? '') === 'ok' ? 'ok' : 'err';
        echo '<div class="flash ' . $class . '">' . h($flash['message'] ?? '') . '</div>';
    }
}

function admin_footer(): void
{
    echo '</main></div></body></html>';
}

function admin_field(string $name, array $field, $value): void
{
    $type = $field['type'] ?? 'text';
    echo '<label for="' . h($name) . '">' . h($field['label'] ?? $name) . '</label>';
    if ($type === 'textarea' || $type === 'html') {
        echo '<textarea id="' . h($name) . '" name="' . h($name) . '">' . h((string) $value) . '</textarea>';
    } elseif ($type === 'select') {
        echo '<select id="' . h($name) . '" name="' . h($name) . '">';
        foreach ($field['options'] as $optionValue => $optionLabel) {
            $selected = (string) $value === (string) $optionValue ? ' selected' : '';
            echo '<option value="' . h($optionValue) . '"' . $selected . '>' . h($optionLabel) . '</option>';
        }
        echo '</select>';
    } elseif ($type === 'checkbox') {
        $checked = (string) $value === '1' || $value === 1 || $value === true ? ' checked' : '';
        echo '<label class="check"><input type="checkbox" id="' . h($name) . '" name="' . h($name) . '" value="1"' . $checked . '> ظاهر</label>';
    } elseif ($type === 'image') {
        echo '<input id="' . h($name) . '" type="text" name="' . h($name) . '" value="' . h((string) $value) . '" placeholder="رابط الصورة أو ارفع ملفاً">';
        echo '<input type="file" name="file_' . h($name) . '" accept="image/*" style="margin-top:8px">';
        if ((string) $value !== '') {
            echo '<div style="margin-top:8px"><img class="thumb" src="' . h(asset_url((string) $value)) . '" alt=""></div>';
        }
    } else {
        $inputType = $type === 'number' ? 'number' : ($type === 'color' ? 'color' : 'text');
        echo '<input id="' . h($name) . '" type="' . h($inputType) . '" name="' . h($name) . '" value="' . h((string) $value) . '">';
    }
    if (!empty($field['hint'])) {
        echo '<div class="hint">' . h($field['hint']) . '</div>';
    }
}

function collect_field_value(string $name, array $field)
{
    $type = $field['type'] ?? 'text';
    if ($type === 'checkbox') {
        return isset($_POST[$name]) ? 1 : 0;
    }
    if ($type === 'image') {
        $uploaded = save_upload('file_' . $name);
        if ($uploaded) {
            return $uploaded;
        }
    }
    $value = trim((string) ($_POST[$name] ?? ''));
    if ($type === 'number') {
        return (int) $value;
    }
    if ($type === 'html') {
        return clean_rich_text($value);
    }
    if ($type === 'select') {
        $options = $field['options'] ?? [];
        if (!array_key_exists($value, $options)) {
            throw new RuntimeException('قيمة غير مسموحة في حقل ' . ($field['label'] ?? $name));
        }
    }
    if (!empty($field['required']) && $value === '') {
        throw new RuntimeException('الحقل «' . ($field['label'] ?? $name) . '» مطلوب.');
    }
    if ($type === 'color' && !preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
        throw new RuntimeException('لون غير صالح في حقل ' . ($field['label'] ?? $name));
    }
    return $value;
}

function move_collection_row(string $table, int $id, string $direction): void
{
    $allowed = [
        'nav_links', 'hero_slides', 'stats', 'about_points', 'package_categories', 'filter_options',
        'services', 'calc_tiers', 'guides', 'testimonials', 'footer_links', 'translations', 'packages', 'cities',
    ];
    if (!in_array($table, $allowed, true)) {
        return;
    }
    $ids = array_map('intval', array_column(rows("SELECT id FROM {$table} ORDER BY sort_order ASC, id ASC"), 'id'));
    $index = array_search($id, $ids, true);
    if ($index === false) {
        return;
    }
    $swap = $direction === 'up' ? $index - 1 : $index + 1;
    if (!isset($ids[$swap])) {
        return;
    }
    $temp = $ids[$index];
    $ids[$index] = $ids[$swap];
    $ids[$swap] = $temp;
    $statement = db()->prepare("UPDATE {$table} SET sort_order = ? WHERE id = ?");
    foreach ($ids as $position => $rowId) {
        $statement->execute([($position + 1) * 10, $rowId]);
    }
}
