<?php

function designer_mode(): bool
{
    static $mode = null;
    if ($mode !== null) {
        return $mode;
    }
    $mode = isset($_GET['designer']) && current_admin() !== null;
    return $mode;
}

function gf_visible(string $flag): bool
{
    return flag($flag) || designer_mode();
}

function gf_off_class(string $flag): string
{
    if (!designer_mode() || flag($flag)) {
        return '';
    }
    return ' gf-off';
}

function gf_content_ids(): array
{
    return ['hero', 'stats', 'about', 'packages', 'services', 'calculator', 'cities', 'guide', 'testimonials'];
}

function gf_content_order(): array
{
    $allowed = gf_content_ids();
    $raw = array_filter(array_map('trim', explode(',', s('section_order', implode(',', $allowed)))));
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
    return $order;
}

function gf_order_attr(string $id): string
{
    $index = array_search($id, gf_content_order(), true);
    $n = $index === false ? 99 : $index + 1;
    return ' style="order:' . $n . '"';
}

function color_channels(string $key, string $fallback): string
{
    $hex = s($key, $fallback);
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $hex)) {
        $hex = $fallback;
    }
    $hex = ltrim($hex, '#');
    return hexdec(substr($hex, 0, 2)) . ' ' . hexdec(substr($hex, 2, 2)) . ' ' . hexdec(substr($hex, 4, 2));
}

function gf_font_options(): array
{
    return [
        'Tajawal' => "'Tajawal', 'Cairo', sans-serif",
        'Cairo' => "'Cairo', 'Tajawal', sans-serif",
        'Amiri' => "'Amiri', serif",
        'Montserrat' => "'Montserrat', sans-serif",
    ];
}

function gf_font_stack(string $key, string $fallback): string
{
    $options = gf_font_options();
    $name = s($key, $fallback);
    if (!isset($options[$name])) {
        $name = $fallback;
    }
    return $options[$name];
}

function designer_fonts(): array
{
    return [
        'Tajawal' => 'تجول',
        'Cairo' => 'القاهرة',
        'Amiri' => 'أميري',
        'Montserrat' => 'مونتسيرات',
    ];
}

function designer_section_flags(): array
{
    return [
        'show_topbar' => 'topbar',
        'show_hero' => 'hero',
        'show_stats' => 'stats',
        'show_about' => 'about',
        'show_packages' => 'packages',
        'show_services' => 'services',
        'show_calculator' => 'calculator',
        'show_cities' => 'cities',
        'show_guide' => 'guide',
        'show_testimonials' => 'testimonials',
        'show_whatsapp' => 'booking',
    ];
}

function designer_flags(): array
{
    return [
        'show_topbar' => 'الشريط العلوي',
        'show_langs' => 'مبدّل اللغات',
        'show_hero' => 'الواجهة الرئيسية',
        'show_hero_search' => 'شريط البحث',
        'show_stats' => 'أرقام الثقة',
        'show_about' => 'عن الوكالة',
        'show_packages' => 'البرامج',
        'show_services' => 'الخدمات',
        'show_calculator' => 'الحاسبة',
        'show_cities' => 'مكة والمدينة',
        'show_guide' => 'دليل المناسك',
        'show_testimonials' => 'آراء العملاء',
        'show_newsletter' => 'النشرة البريدية',
        'show_whatsapp' => 'زر واتساب',
    ];
}

function designer_colors(): array
{
    return [
        'color_green' => 'الأخضر',
        'color_darkgreen' => 'الأخضر الداكن',
        'color_gold' => 'الذهبي',
        'color_lightgold' => 'الذهبي الفاتح',
        'color_ivory' => 'الخلفية العاجية',
        'color_lightivory' => 'العاجي الفاتح',
        'color_slate' => 'لون النص',
    ];
}

function designer_catalog(): array
{
    return [
        'general' => ['label' => 'عنوان الصفحة', 'groups' => ['general'], 'collections' => [], 'zone' => false],
        'topbar' => ['label' => 'الشريط العلوي', 'groups' => ['topbar'], 'collections' => [], 'zone' => true],
        'header' => ['label' => 'الشعار والقائمة', 'groups' => ['brand'], 'collections' => ['nav', 'i18n'], 'zone' => true],
        'hero' => ['label' => 'الواجهة', 'groups' => ['hero'], 'collections' => ['hero', 'filters'], 'zone' => true],
        'stats' => ['label' => 'أرقام الثقة', 'groups' => [], 'collections' => ['stats'], 'zone' => true],
        'about' => ['label' => 'عن الوكالة', 'groups' => ['about'], 'collections' => ['about'], 'zone' => true],
        'packages' => ['label' => 'البرامج', 'groups' => ['packages'], 'collections' => ['categories'], 'special' => 'packages', 'zone' => true],
        'services' => ['label' => 'الخدمات', 'groups' => ['services'], 'collections' => ['services'], 'zone' => true],
        'calculator' => ['label' => 'الحاسبة', 'groups' => ['calculator'], 'collections' => ['tiers'], 'zone' => true],
        'cities' => ['label' => 'مكة والمدينة', 'groups' => ['cities'], 'special' => 'cities', 'collections' => [], 'zone' => true],
        'guide' => ['label' => 'دليل المناسك', 'groups' => ['guide'], 'collections' => ['guides'], 'zone' => true],
        'testimonials' => ['label' => 'آراء العملاء', 'groups' => ['testimonials'], 'collections' => ['testimonials'], 'zone' => true],
        'footer' => ['label' => 'التذييل', 'groups' => ['footer'], 'collections' => ['footer'], 'zone' => true],
        'booking' => ['label' => 'الحجز وواتساب', 'groups' => ['modal'], 'collections' => [], 'zone' => true],
    ];
}

function designer_state(): array
{
    $colors = [];
    foreach (designer_colors() as $key => $label) {
        $colors[$key] = [
            'label' => $label,
            'value' => preg_match('/^#[0-9a-fA-F]{6}$/', s($key)) ? s($key) : '#183B35',
        ];
    }
    $flags = [];
    foreach (designer_flags() as $key => $label) {
        $flags[$key] = ['label' => $label, 'on' => flag($key)];
    }
    return [
        'colors' => $colors,
        'fonts' => designer_fonts(),
        'bodyFont' => array_key_exists(s('body_font', 'Tajawal'), designer_fonts()) ? s('body_font', 'Tajawal') : 'Tajawal',
        'titleFont' => array_key_exists(s('title_font', 'Amiri'), designer_fonts()) ? s('title_font', 'Amiri') : 'Amiri',
        'fontStacks' => gf_font_options(),
        'sectionPadding' => max(32, min(200, (int) s('section_padding', '96'))),
        'heroMin' => max(40, min(100, (int) s('hero_min_height', '92'))),
        'order' => gf_content_order(),
        'orderLabels' => array_combine(gf_content_ids(), array_map(static function ($id) {
            return designer_catalog()[$id]['label'] ?? $id;
        }, gf_content_ids())),
        'flags' => $flags,
        'flagZones' => designer_section_flags(),
        'sections' => array_map(static function ($section) {
            return $section['label'];
        }, designer_catalog()),
    ];
}

function designer_bridge(): void
{
    if (!designer_mode()) {
        return;
    }
    echo '<script>
    (function () {
        if (window.parent === window) return;
        const origin = location.origin;
        let picking = false;
        window.addEventListener("message", function (event) {
            if (event.origin !== origin || !event.data) return;
            if (event.data.type === "gf-pick") {
                picking = !!event.data.on;
                document.body.classList.toggle("gf-pick", picking);
            }
            if (event.data.type === "gf-focus" && event.data.section) {
                document.querySelectorAll("[data-gf].gf-on").forEach(function (el) { el.classList.remove("gf-on"); });
                const target = document.querySelector(\'[data-gf="\' + event.data.section + \'"]\');
                if (target) {
                    target.classList.add("gf-on");
                    target.scrollIntoView({ behavior: "smooth", block: "center" });
                }
            }
        });
        document.addEventListener("click", function (event) {
            if (!picking) return;
            const zone = event.target.closest("[data-gf]");
            if (!zone) return;
            event.preventDefault();
            event.stopPropagation();
            document.querySelectorAll("[data-gf].gf-on").forEach(function (el) { el.classList.remove("gf-on"); });
            zone.classList.add("gf-on");
            window.parent.postMessage({ type: "gf-open", section: zone.getAttribute("data-gf") }, origin);
        }, true);
    })();
    </script>';
}

function ensure_design_settings(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $rows = [
        ['body_font', 'Tajawal', 'design', 'خط النصوص', 'text', 'Tajawal أو Cairo أو Amiri أو Montserrat', 10],
        ['title_font', 'Amiri', 'design', 'خط العناوين', 'text', 'Amiri أو Cairo أو Tajawal أو Montserrat', 20],
        ['section_padding', '96', 'design', 'فراغ الأقسام بالبكسل', 'number', 'المسافة أعلى وأسفل الأقسام الكبيرة', 30],
        ['hero_min_height', '92', 'design', 'ارتفاع الواجهة', 'number', 'نسبة من ارتفاع الشاشة، من 40 إلى 100', 40],
        ['section_order', 'hero,stats,about,packages,services,calculator,cities,guide,testimonials', 'design', 'ترتيب الأقسام', 'text', 'يُفضَّل ترتيبها من المصمم المرئي', 50],
        ['show_hero', '1', 'visibility', 'الواجهة الرئيسية', 'bool', '', 5],
    ];

    try {
        $insert = db()->prepare('INSERT INTO settings (skey, svalue, sgroup, label, field_type, hint, sorder) VALUES (?, ?, ?, ?, ?, ?, ?)');
        foreach ($rows as $row) {
            if (array_key_exists($row[0], $GLOBALS['SETTINGS'])) {
                continue;
            }
            $insert->execute($row);
            $GLOBALS['SETTINGS'][$row[0]] = $row[1];
        }
    } catch (Throwable $e) {
        return;
    }
}
