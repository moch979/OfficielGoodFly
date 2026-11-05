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
        ['bookings.php', 'طلبات الحجز', 'fa-inbox', 'bookings.php'],
        ['subscribers.php', 'النشرة البريدية', 'fa-envelope', 'subscribers.php'],
        ['settings.php?group=notifications', 'تنبيهات البريد', 'fa-bell', 'notifications'],
        ['snapshots.php', 'النسخ والاسترجاع', 'fa-clock-rotate-left', 'snapshots.php'],
        ['account.php', 'الحساب وكلمة المرور', 'fa-user-lock', 'account.php'],
    ];
}

function admin_header(string $title, string $active): void
{
    $flash = flash_take();
    $admin = current_admin();
    $newBookings = (int) db()->query("SELECT COUNT(*) FROM bookings WHERE status = 'new'")->fetchColumn();
    echo '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<meta name="robots" content="noindex"><title>' . h($title) . ' | إدارة Good Fly</title>';
    echo '<link rel="icon" href="../assets/img/favicon.svg">';
    echo '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    echo '<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">';
    echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';
    $logo = s('logo_image') !== '' ? '<img src="' . h(asset_url(s('logo_image'))) . '" alt="' . h(s('logo_alt', 'Good Fly Travel')) . '">' : 'GOOD FLY';
    echo '<style>' . admin_css() . '</style></head><body data-edit-lang="ar"><div class="layout"><aside class="sidebar"><div class="brand">' . $logo . '</div>';
    foreach (admin_menu() as $item) {
        $class = $item[3] === $active ? ' class="active"' : '';
        $badge = $item[0] === 'bookings.php' && $newBookings > 0 ? '<b class="badge">' . $newBookings . '</b>' : '';
        echo '<a' . $class . ' href="' . h($item[0]) . '"><i class="fa-solid ' . h($item[2]) . '"></i><span>' . h($item[1]) . '</span>' . $badge . '</a>';
    }
    echo '<a href="../index.php" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square"></i><span>عرض الموقع</span></a>';
    echo '<form method="post" action="logout.php">' . csrf_field() . '<button type="submit" class="linklike"><i class="fa-solid fa-right-from-bracket"></i><span>خروج (' . h($admin['username'] ?? '') . ')</span></button></form>';
    echo '</aside><main class="content"><div class="top"><h1>' . h($title) . '</h1></div>';
    if ($flash) {
        $class = ($flash['type'] ?? '') === 'ok' ? 'ok' : 'err';
        echo '<div class="flash ' . $class . '" role="status">' . h($flash['message'] ?? '') . '</div>';
    }
}

function admin_footer(): void
{
    echo '</main></div><script>' . admin_js() . '</script></body></html>';
}

function admin_css(): string
{
    return '
        *{box-sizing:border-box} body{margin:0;font-family:Tajawal,Tahoma,sans-serif;background:#F4F8FC;color:#1D3C78}
        a{color:#1D3C78} .layout{display:flex;min-height:100vh}
        .sidebar{width:270px;background:#0F2350;color:#fff;padding:22px 14px;flex-shrink:0}
        .brand{font-weight:800;letter-spacing:1px;padding:8px 12px 18px;color:#F2C14E}
        .brand img{display:block;height:54px;width:auto;background:#fff;border-radius:14px;padding:4px 10px}
        .sidebar a,.sidebar .linklike{display:flex;align-items:center;gap:10px;color:rgba(255,255,255,.82);text-decoration:none;padding:9px 12px;border-radius:12px;margin-bottom:3px;font-size:14px;width:100%;background:none;border:0;font-family:inherit;cursor:pointer;text-align:start}
        .sidebar a.active,.sidebar a:hover,.sidebar .linklike:hover{background:rgba(242,193,78,.16);color:#F8DC8E}
        .badge{margin-inline-start:auto;background:#F2C14E;color:#0F2350;border-radius:999px;padding:1px 8px;font-size:12px}
        .content{flex:1;padding:28px;min-width:0}
        .top{display:flex;justify-content:space-between;gap:12px;align-items:center;margin-bottom:18px;flex-wrap:wrap}
        h1{font-size:28px;margin:0} h2{margin:0 0 8px}
        .card{background:#fff;border:1px solid rgba(242,193,78,.45);border-radius:18px;padding:18px 20px;box-shadow:0 12px 30px -24px rgba(29,60,120,.8);margin-bottom:16px}
        label{display:block;font-weight:800;margin:14px 0 6px;font-size:14px}
        input[type=text],input[type=number],input[type=password],input[type=email],input[type=date],input[type=search],textarea,select{width:100%;padding:11px 12px;border:1px solid #d9e1ec;border-radius:12px;font-family:inherit;background:#fff;color:#1D3C78;font-size:14px}
        textarea{min-height:110px;resize:vertical}
        .btn,.btn:visited{display:inline-flex;align-items:center;justify-content:center;gap:6px;background:linear-gradient(135deg,#F2C14E,#E3A93A);color:#0F2350;border:0;border-radius:999px;padding:10px 16px;font-weight:800;cursor:pointer;text-decoration:none;font-family:inherit;font-size:14px}
        .btn.secondary{background:#1D3C78;color:#fff}.btn.ghost{background:transparent;color:#1D3C78;border:1px solid rgba(29,60,120,.2)}
        .btn.danger{background:#8d2f2f;color:#fff}.btn.small{padding:6px 10px;font-size:12px}
        table{width:100%;border-collapse:collapse} th,td{padding:10px 8px;border-bottom:1px solid #eee;text-align:start;vertical-align:middle;font-size:14px}
        .flash{padding:12px 14px;border-radius:12px;margin-bottom:14px}.ok{background:#e7f6ee}.err{background:#fdecec}.warn{background:#fff6e0}
        .hint{color:#66708a;font-size:12px;font-weight:500;margin-top:4px}
        .intro{color:#3F4E66;margin:0 0 16px}
        .actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
        .thumb{width:72px;height:48px;object-fit:cover;border-radius:8px;border:1px solid #dbe4f0}
        .tabs{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px}
        .tabs a{text-decoration:none;padding:8px 12px;border-radius:999px;background:#fff;border:1px solid #dbe4f0;font-size:13px}
        .tabs a.active{background:#1D3C78;color:#F4F8FC}
        .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:18px}
        .stat strong{display:block;font-size:28px}
        .check{display:flex;align-items:center;gap:8px;font-weight:700}
        .check input{width:auto}
        .langbar{position:sticky;top:0;z-index:5;display:flex;gap:6px;align-items:center;flex-wrap:wrap;background:#F4F8FC;padding:10px 0;margin:-6px 0 6px;font-size:13px;font-weight:700}
        .langbar button{border:1px solid #dbe4f0;background:#fff;border-radius:999px;padding:6px 12px;cursor:pointer;font-family:inherit;font-weight:700}
        .langbar button.on{background:#1D3C78;color:#fff;border-color:#1D3C78}
        .lang-tag{display:inline-block;font-size:11px;background:#F8DC8E;color:#0F2350;border-radius:6px;padding:1px 6px;margin-inline-start:6px;font-weight:700}
        .ref{font-size:12px;color:#66708a;background:#f6f9fd;border:1px dashed #dbe4f0;border-radius:8px;padding:6px 8px;margin-bottom:6px;white-space:pre-line;max-height:90px;overflow:auto}
        body[data-edit-lang="ar"] .lang-pane:not([data-lang="ar"]),
        body[data-edit-lang="fr"] .lang-pane:not([data-lang="fr"]),
        body[data-edit-lang="en"] .lang-pane:not([data-lang="en"]){display:none}
        .missing{color:#8d2f2f;font-size:12px;font-weight:700}
        .checklist li{margin:6px 0}
        .pager{display:flex;gap:6px;margin-top:12px;flex-wrap:wrap}
        .pager a,.pager span{padding:6px 10px;border-radius:8px;border:1px solid #dbe4f0;background:#fff;text-decoration:none;font-size:13px}
        .pager span{background:#1D3C78;color:#fff}
        @media(max-width:900px){.layout{display:block}.sidebar{width:auto}.content{padding:16px}}
    ';
}

function admin_js(): string
{
    return '
        (function () {
            var saved = localStorage.getItem("gfEditLang") || "ar";
            function setLang(lang) {
                document.body.dataset.editLang = lang;
                localStorage.setItem("gfEditLang", lang);
                document.querySelectorAll(".langbar button").forEach(function (b) { b.classList.toggle("on", b.dataset.lang === lang); });
            }
            document.querySelectorAll(".langbar button").forEach(function (b) {
                b.addEventListener("click", function () { setLang(b.dataset.lang); });
            });
            if (document.querySelector(".langbar")) setLang(saved);
        })();
    ';
}

function edit_lang_switch(): string
{
    $html = '<div class="langbar"><span>لغة التحرير:</span>';
    foreach (languages() as $code => $info) {
        $html .= '<button type="button" data-lang="' . h($code) . '">' . h($info['name']) . '</button>';
    }
    return $html . '<span class="hint">الحقول غير المترجمة تظهر للزائر بالعربية.</span></div>';
}

/**
 * Renders one field. $nameFor($lang) returns the input name for a language,
 * $values holds ['ar' => ..., 'fr' => ..., 'en' => ...].
 */
function render_field(string $id, array $field, callable $nameFor, array $values, string $fileName = ''): void
{
    $type = $field['type'] ?? 'text';
    $translatable = !empty($field['translatable']);
    $label = $field['label'] ?? $id;
    $arabic = (string) ($values['ar'] ?? '');

    if ($type === 'checkbox' || $type === 'bool') {
        $checked = $arabic === '1' ? ' checked' : '';
        echo '<label class="check"><input type="checkbox" id="' . h($id) . '" name="' . h($nameFor('ar')) . '" value="1"' . $checked . '> ' . h($label) . '</label>';
        if (!empty($field['hint'])) {
            echo '<div class="hint">' . h($field['hint']) . '</div>';
        }
        return;
    }

    $langs = $translatable ? array_keys(languages()) : [default_lang()];
    foreach ($langs as $lang) {
        $value = (string) ($values[$lang] ?? '');
        $inputId = $id . ($lang === default_lang() ? '' : '_' . $lang);
        $dir = languages()[$lang]['dir'];
        if ($translatable) {
            echo '<div class="lang-pane" data-lang="' . h($lang) . '">';
        }
        echo '<label for="' . h($inputId) . '">' . h($label);
        if ($translatable) {
            echo '<span class="lang-tag">' . h(languages()[$lang]['name']) . '</span>';
            if ($lang !== default_lang() && trim($value) === '' && trim($arabic) !== '') {
                echo ' <span class="missing">غير مترجم</span>';
            }
        }
        echo '</label>';
        if ($translatable && $lang !== default_lang() && trim($arabic) !== '') {
            echo '<div class="ref" dir="rtl">' . h(mb_strimwidth($arabic, 0, 400, '…')) . '</div>';
        }
        $name = h($nameFor($lang));
        if (in_array($type, ['textarea', 'html', 'lines'], true)) {
            echo '<textarea id="' . h($inputId) . '" name="' . $name . '" dir="' . $dir . '">' . h($value) . '</textarea>';
        } elseif ($type === 'select') {
            echo '<select id="' . h($inputId) . '" name="' . $name . '">';
            foreach ($field['options'] ?? [] as $optionValue => $optionLabel) {
                $selected = $value === (string) $optionValue ? ' selected' : '';
                echo '<option value="' . h($optionValue) . '"' . $selected . '>' . h($optionLabel) . '</option>';
            }
            echo '</select>';
        } elseif ($type === 'image') {
            echo '<input id="' . h($inputId) . '" type="text" name="' . $name . '" value="' . h($value) . '" dir="ltr" placeholder="رابط الصورة أو ارفع ملفاً">';
            echo '<input type="file" name="' . h($fileName !== '' ? $fileName : 'file_' . $id) . '" accept="image/jpeg,image/png,image/webp,image/gif" style="margin-top:8px">';
            if ($value !== '') {
                echo '<div style="margin-top:8px"><img class="thumb" src="' . h(asset_url($value)) . '" alt=""></div>';
            }
        } elseif ($type === 'color') {
            $color = valid_hex($value) ? $value : '#1D3C78';
            echo '<input id="' . h($inputId) . '" type="color" name="' . $name . '" value="' . h($color) . '" style="height:48px;width:120px;padding:4px">';
        } elseif ($type === 'password') {
            $placeholder = $value !== '' ? 'محفوظة — اكتب لتغييرها' : '';
            echo '<input id="' . h($inputId) . '" type="password" name="' . $name . '" value="" autocomplete="new-password" placeholder="' . h($placeholder) . '">';
        } else {
            $inputType = $type === 'number' ? 'number' : 'text';
            $max = !empty($field['max']) ? ' maxlength="' . (int) $field['max'] . '"' : '';
            echo '<input id="' . h($inputId) . '" type="' . $inputType . '" name="' . $name . '" value="' . h($value) . '" dir="' . $dir . '"' . $max . '>';
        }
        if ($translatable) {
            echo '</div>';
        }
    }
    if (!empty($field['hint'])) {
        echo '<div class="hint">' . h($field['hint']) . '</div>';
    }
}

/** Renders a field bound to a database record (collections, packages, cities). */
function record_field(string $name, array $field, array $record): void
{
    $values = ['ar' => (string) ($record[$name] ?? '')];
    foreach (extra_languages() as $lang) {
        $values[$lang] = (string) ($record[$name . '_' . $lang] ?? '');
    }
    if (($field['type'] ?? '') === 'checkbox') {
        $values['ar'] = (string) (int) ($record[$name] ?? 0);
    }
    render_field($name, $field, static function (string $lang) use ($name) {
        return $lang === default_lang() ? $name : $name . '_' . $lang;
    }, $values, 'file_' . $name);
}

function has_translatable(array $fields): bool
{
    foreach ($fields as $field) {
        if (!empty($field['translatable'])) {
            return true;
        }
    }
    return false;
}

function blank_record(array $fields): array
{
    $record = ['id' => 0];
    foreach ($fields as $name => $field) {
        $type = $field['type'] ?? 'text';
        if ($type === 'checkbox') {
            $record[$name] = 1;
        } elseif ($type === 'select') {
            $record[$name] = (string) array_key_first($field['options'] ?? []);
        } elseif ($name === 'stars') {
            $record[$name] = 5;
        } else {
            $record[$name] = '';
        }
    }
    return $record;
}

function move_buttons(int $id, string $extra = ''): string
{
    $html = '';
    foreach (['up' => 'أعلى', 'down' => 'أسفل'] as $direction => $label) {
        $html .= '<form method="post">' . csrf_field() . $extra . '<input type="hidden" name="action" value="move"><input type="hidden" name="direction" value="' . $direction . '"><input type="hidden" name="id" value="' . $id . '"><button class="btn small ghost" type="submit" aria-label="' . $label . '">' . $label . '</button></form>';
    }
    return $html;
}

function delete_button(int $id, string $question): string
{
    return '<form method="post" onsubmit="return confirm(' . h(json_encode($question, JSON_UNESCAPED_UNICODE)) . ')">' . csrf_field() . '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' . $id . '"><button class="btn small danger" type="submit">حذف</button></form>';
}
