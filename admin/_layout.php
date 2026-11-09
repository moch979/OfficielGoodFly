<?php

function admin_menu(): array
{
    return [
        ['index.php', 'لوحة التحكم', 'fa-gauge-high', 'index.php'],
        ['designer.php', 'المصمم المرئي', 'fa-pen-ruler', 'designer.php'],
        ['agency.php', 'معلومات الوكالة', 'fa-id-card', 'agency.php'],
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
        ['collection.php?type=blog', 'المدونة', 'fa-newspaper', 'blog'],
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
    echo '<link rel="icon" href="../favicon.ico">';
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
        .form-section{font-size:18px;margin:26px 0 4px;padding-top:14px;border-top:1px solid #dbe4f0}
        .gallery-thumbs{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px}
        .deps table td{padding:4px}.deps tr.past{opacity:.5}
        .req{color:#b42318;margin-inline-start:4px}
        .invalid,.invalid:focus{border-color:#b42318 !important;background:#fff5f5 !important;outline:none}
        .form-errors{position:sticky;top:52px;z-index:6;background:#fdecec;border:1px solid #f3b4b4;color:#8d2f2f;border-radius:12px;padding:10px 14px;margin-bottom:12px;font-size:14px}
        .form-errors ul{margin:6px 0 0;padding-inline-start:20px}
        .hotel-row{border:1px solid #dbe4f0;border-radius:14px;padding:4px 14px 12px;margin-bottom:10px;background:#fbfdff}
        .hotel-row .head{display:flex;justify-content:space-between;align-items:center;margin-top:10px;font-weight:800}
        .hotel-row .grid2{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:0 12px}
        .hotel-row .grid4{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:0 12px}
        .hotel-row label{font-size:12px;margin:10px 0 4px}
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
            function each(list, fn) { Array.prototype.forEach.call(list, fn); }
            function clearRow(row) {
                row.classList.remove("past");
                row.removeAttribute("title");
                each(row.querySelectorAll("input, textarea"), function (input) { input.value = input.type === "hidden" ? "0" : ""; input.classList.remove("invalid"); });
            }
            function renumber(rows) {
                each(rows.querySelectorAll(".row-num"), function (span, index) { span.textContent = index + 1; });
            }
            function rowUsed(row) {
                return Array.prototype.some.call(row.querySelectorAll("input:not([type=hidden]), textarea"), function (input) { return input.value.trim() !== ""; });
            }
            document.addEventListener("click", function (event) {
                var add = event.target.closest(".repeat-add");
                if (add) {
                    var rows = add.closest(".repeat").querySelector(".repeat-rows");
                    var copy = rows.lastElementChild.cloneNode(true);
                    clearRow(copy);
                    rows.appendChild(copy);
                    renumber(rows);
                    var first = copy.querySelector(".lang-pane[data-lang=" + (document.body.dataset.editLang || "ar") + "] input, input[type=date]");
                    if (first) first.focus();
                }
                var remove = event.target.closest(".repeat-remove");
                if (remove) {
                    var row = remove.closest(".repeat-row");
                    var list = row.parentNode;
                    if (list.children.length > 1) row.remove();
                    else clearRow(row);
                    renumber(list);
                }
            });

            function validateForm(form) {
                var problems = [], bad = [];
                each(form.querySelectorAll("[data-required]"), function (input) {
                    var file = input.dataset.file ? form.elements[input.dataset.file] : null;
                    if (input.value.trim() === "" && !(file && file.files && file.files.length)) {
                        bad.push(input);
                        problems.push("الحقل «" + input.dataset.label + "» مطلوب.");
                    }
                });
                var used = 0;
                each(form.querySelectorAll(".hotel-rows .repeat-row"), function (row) {
                    if (!rowUsed(row)) return;
                    used++;
                    var names = row.querySelectorAll("[data-role=hotel-name]");
                    if (!Array.prototype.some.call(names, function (input) { return input.value.trim() !== ""; })) {
                        each(names, function (input) { bad.push(input); });
                        problems.push("الفندق رقم " + used + ": أدخل اسم فندق مكة أو فندق المدينة بالعربية.");
                    }
                    var prices = row.querySelectorAll("[data-role=price]");
                    if (!Array.prototype.some.call(prices, function (input) { return Number(input.value) > 0; })) {
                        each(prices, function (input) { bad.push(input); });
                        problems.push("الفندق رقم " + used + ": أدخل سعر نوع غرفة واحد على الأقل.");
                    }
                });
                var hotelList = form.querySelector(".hotel-rows");
                var priceText = form.elements["price"];
                if (hotelList && used === 0 && !(priceText && priceText.value.trim() !== "")) {
                    each(hotelList.querySelectorAll("[data-role=hotel-name]"), function (input) { bad.push(input); });
                    problems.push("أضف فندقاً واحداً على الأقل بسعره، أو اكتب نص السعر في البطاقة.");
                }
                var dates = 0;
                each(form.querySelectorAll(".dep-rows .repeat-row"), function (row) {
                    if (!rowUsed(row)) return;
                    dates++;
                    var depart = row.querySelector("[name^=dep_date]"), back = row.querySelector("[name^=dep_return]");
                    if (!depart.value || !back.value) {
                        if (!depart.value) bad.push(depart);
                        if (!back.value) bad.push(back);
                        problems.push("الموعد رقم " + dates + ": أدخل تاريخ الانطلاق وتاريخ العودة معاً.");
                    } else if (back.value < depart.value) {
                        bad.push(back);
                        problems.push("الموعد رقم " + dates + ": تاريخ العودة قبل تاريخ الانطلاق.");
                    }
                });
                return { problems: problems, bad: bad };
            }
            document.addEventListener("submit", function (event) {
                var form = event.target;
                if (!form.classList.contains("validate")) return;
                each(form.querySelectorAll(".invalid"), function (input) { input.classList.remove("invalid"); });
                var result = validateForm(form);
                var box = form.querySelector(".form-errors");
                if (!result.problems.length) {
                    if (box) box.remove();
                    return;
                }
                event.preventDefault();
                if (!box) {
                    box = document.createElement("div");
                    box.className = "form-errors";
                    box.setAttribute("role", "alert");
                    form.insertBefore(box, form.querySelector(".langbar") ? form.querySelector(".langbar").nextSibling : form.firstChild);
                }
                box.innerHTML = "<strong>لم يتم الحفظ. أكمل ما يلي ثم احفظ:</strong><ul></ul>";
                result.problems.forEach(function (text) {
                    var item = document.createElement("li");
                    item.textContent = text;
                    box.querySelector("ul").appendChild(item);
                });
                result.bad.forEach(function (input) { input.classList.add("invalid"); });
                var first = result.bad[0];
                if (first) {
                    var pane = first.closest(".lang-pane");
                    if (pane && document.querySelector(".langbar")) setLang(pane.dataset.lang);
                    first.focus({ preventScroll: true });
                    first.scrollIntoView({ block: "center", behavior: "smooth" });
                } else {
                    box.scrollIntoView({ block: "center", behavior: "smooth" });
                }
            }, true);
            document.addEventListener("input", function (event) {
                if (event.target.classList) event.target.classList.remove("invalid");
            });
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
        $required = !empty($field['required']) && $lang === default_lang();
        $check = $required ? ' data-required data-label="' . h($label) . '"' . ($type === 'image' ? ' data-file="' . h($fileName !== '' ? $fileName : 'file_' . $id) . '"' : '') : '';
        echo '<label for="' . h($inputId) . '">' . h($label) . ($required ? '<span class="req" title="حقل مطلوب">*</span>' : '');
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
        if ($type === 'gallery') {
            echo '<textarea id="' . h($inputId) . '" name="' . $name . '" dir="ltr" style="min-height:90px">' . h($value) . '</textarea>';
            echo '<input type="file" name="file_gallery[]" multiple accept="image/jpeg,image/png,image/webp,image/gif" style="margin-top:8px">';
            echo '<div class="gallery-thumbs">';
            foreach (array_filter(split_text_lines($value), 'strlen') as $path) {
                echo '<img class="thumb" alt="" src="' . h(asset_url($path)) . '">';
            }
            echo '</div>';
        } elseif (in_array($type, ['textarea', 'html', 'lines', 'blocks'], true)) {
            $style = $type === 'blocks' ? ' style="min-height:260px"' : '';
            echo '<textarea id="' . h($inputId) . '" name="' . $name . '" dir="' . $dir . '"' . $style . $check . '>' . h($value) . '</textarea>';
        } elseif ($type === 'select') {
            echo '<select id="' . h($inputId) . '" name="' . $name . '"' . $check . '>';
            foreach ($field['options'] ?? [] as $optionValue => $optionLabel) {
                $selected = $value === (string) $optionValue ? ' selected' : '';
                echo '<option value="' . h($optionValue) . '"' . $selected . '>' . h($optionLabel) . '</option>';
            }
            echo '</select>';
        } elseif ($type === 'image') {
            echo '<input id="' . h($inputId) . '" type="text" name="' . $name . '" value="' . h($value) . '" dir="ltr" placeholder="رابط الصورة أو ارفع ملفاً"' . $check . '>';
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
            $inputType = in_array($type, ['number', 'date'], true) ? $type : 'text';
            $max = !empty($field['max']) ? ' maxlength="' . (int) $field['max'] . '"' : '';
            echo '<input id="' . h($inputId) . '" type="' . $inputType . '" name="' . $name . '" value="' . h($value) . '" dir="' . $dir . '"' . $max . $check . '>';
        }
        if ($translatable) {
            echo '</div>';
        }
    }
    if (!empty($field['hint'])) {
        echo '<div class="hint">' . h($field['hint']) . '</div>';
    }
}

function render_departures(array $departures, string $hint): void
{
    echo '<label>مواعيد الانطلاق</label><div class="deps repeat"><table><thead><tr><th>#</th><th>تاريخ الانطلاق<span class="req">*</span></th><th>تاريخ العودة<span class="req">*</span></th><th>المقاعد (اختياري)</th><th></th></tr></thead><tbody class="dep-rows repeat-rows">';
    $rows = array_values(array_filter($departures, 'is_array'));
    if (!$rows) {
        $rows[] = ['id' => 0, 'depart_date' => '', 'return_date' => '', 'seats_left' => ''];
    }
    foreach ($rows as $index => $row) {
        $past = $row['depart_date'] !== '' && $row['depart_date'] < date('Y-m-d');
        echo '<tr class="repeat-row' . ($past ? ' past" title="موعد مضى' : '') . '"><td class="row-num">' . ($index + 1) . '</td>';
        echo '<td><input type="hidden" name="dep_id[]" value="' . (int) $row['id'] . '"><input type="date" name="dep_date[]" value="' . h($row['depart_date']) . '" aria-label="تاريخ الانطلاق"></td>';
        echo '<td><input type="date" name="dep_return[]" value="' . h((string) $row['return_date']) . '" aria-label="تاريخ العودة"></td>';
        echo '<td><input type="number" min="0" name="dep_seats[]" value="' . h((string) ($row['seats_left'] ?? '')) . '" placeholder="غير محدد" style="max-width:120px" aria-label="المقاعد"></td>';
        echo '<td><button type="button" class="btn small ghost repeat-remove">إزالة</button></td></tr>';
    }
    echo '</tbody></table><button type="button" class="btn small secondary repeat-add" style="margin-top:8px">إضافة موعد</button></div>';
    echo '<div class="hint">' . h($hint) . '</div>';
}

function hotel_text_inputs(array $row, string $column, string $label, string $placeholder, bool $isName): void
{
    foreach (languages() as $lang => $info) {
        $key = $lang === default_lang() ? $column : $column . '_' . $lang;
        $role = $isName && $lang === default_lang() ? ' data-role="hotel-name"' : '';
        echo '<div class="lang-pane" data-lang="' . h($lang) . '"><label>' . h($label) . ($isName && $lang === default_lang() ? '<span class="req" title="فندق مكة أو المدينة مطلوب">*</span>' : '') . '<span class="lang-tag">' . h($info['name']) . '</span></label>';
        echo '<input type="text" name="hotel_' . h($key) . '[]" value="' . h((string) ($row[$key] ?? '')) . '" dir="' . h($info['dir']) . '" maxlength="' . HOTEL_TEXT_FIELDS[$column] . '" placeholder="' . h($lang === default_lang() ? $placeholder : '') . '"' . $role . '></div>';
    }
}

function render_hotels(array $hotels, array $field): void
{
    echo '<label>' . h($field['label']) . '<span class="req" title="حقل مطلوب">*</span></label><input type="hidden" name="hotels_form" value="1">';
    echo '<div class="repeat"><div class="hotel-rows repeat-rows">';
    $rows = array_values(array_filter($hotels, 'is_array')) ?: [['id' => 0]];
    foreach ($rows as $index => $row) {
        echo '<div class="hotel-row repeat-row"><div class="head"><span>الخيار رقم <span class="row-num">' . ($index + 1) . '</span></span><button type="button" class="btn small ghost repeat-remove">إزالة</button></div>';
        echo '<input type="hidden" name="hotel_id[]" value="' . (int) ($row['id'] ?? 0) . '">';
        echo '<div class="grid2"><div>';
        hotel_text_inputs($row, 'madinah_hotel', 'فندق المدينة المنورة', 'مثلاً: مجموعة الإيمان', true);
        echo '</div><div>';
        hotel_text_inputs($row, 'makkah_hotel', 'فندق مكة المكرمة', 'مثلاً: أعمار جراند', true);
        echo '</div><div>';
        hotel_text_inputs($row, 'makkah_note', 'المسافة أو النقل في مكة', 'مثلاً: 650 متر من الحرم', false);
        echo '</div><div>';
        hotel_text_inputs($row, 'meals', 'الوجبات', 'مثلاً: بالإفطار', false);
        echo '</div></div><div class="grid4">';
        foreach (room_labels() as $type => $roomLabel) {
            $price = (int) ($row['price_' . $type] ?? 0);
            echo '<div><label>' . h($roomLabel) . ' (درهم)</label><input type="number" min="0" step="1" name="hotel_price_' . h($type) . '[]" value="' . ($price > 0 ? $price : '') . '" placeholder="غير متاحة" data-role="price" dir="ltr"></div>';
        }
        echo '</div></div>';
    }
    echo '</div><button type="button" class="btn small secondary repeat-add">إضافة فندق</button></div>';
    echo '<div class="hint">' . h((string) ($field['hint'] ?? '')) . '</div>';
}

/** Renders a field bound to a database record (collections, packages, cities). */
function record_field(string $name, array $field, array $record): void
{
    if (!empty($field['section'])) {
        echo '<h2 class="form-section">' . h($field['section']) . '</h2>';
    }
    if (($field['type'] ?? '') === 'departures') {
        render_departures((array) ($record['departures'] ?? []), (string) ($field['hint'] ?? ''));
        return;
    }
    if (($field['type'] ?? '') === 'hotels') {
        render_hotels((array) ($record['hotels'] ?? []), $field);
        return;
    }
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
        } elseif ($type === 'date') {
            $record[$name] = date('Y-m-d');
        } elseif (in_array($type, ['departures', 'hotels'], true)) {
            $record[$name] = [];
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
