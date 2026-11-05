<?php

function designer_mode(): bool
{
    static $mode = null;
    if ($mode === null) {
        $mode = isset($_GET['designer']) && current_admin() !== null;
    }
    return $mode;
}

function gf_visible(string $flag): bool
{
    return flag($flag) || designer_mode();
}

function gf_off_class(string $flag): string
{
    return designer_mode() && !flag($flag) ? ' gf-off' : '';
}

function gf_content_ids(): array
{
    return ['hero', 'stats', 'about', 'packages', 'services', 'calculator', 'cities', 'guide', 'testimonials'];
}

function normalize_section_order(string $raw): array
{
    $allowed = gf_content_ids();
    $order = [];
    foreach (array_filter(array_map('trim', explode(',', $raw))) as $id) {
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

function gf_content_order(): array
{
    return normalize_section_order(s('section_order', implode(',', gf_content_ids())));
}

function gf_order_attr(string $id): string
{
    $index = array_search($id, gf_content_order(), true);
    return ' style="order:' . ($index === false ? 99 : $index + 1) . '"';
}

function valid_hex(string $value): bool
{
    return (bool) preg_match('/^#[0-9a-fA-F]{6}$/', $value);
}

function color_channels(string $key, string $fallback): string
{
    $hex = s($key, $fallback);
    $hex = ltrim(valid_hex($hex) ? $hex : $fallback, '#');
    return hexdec(substr($hex, 0, 2)) . ' ' . hexdec(substr($hex, 2, 2)) . ' ' . hexdec(substr($hex, 4, 2));
}

function gf_font_options(): array
{
    return [
        'Tajawal' => "'Tajawal', 'Cairo', sans-serif",
        'Cairo' => "'Cairo', 'Tajawal', sans-serif",
        'Amiri' => "'Amiri', serif",
        'Montserrat' => "'Montserrat', 'Tajawal', sans-serif",
    ];
}

function gf_font_stack(string $key, string $fallback): string
{
    $options = gf_font_options();
    $name = s($key, $fallback);
    return $options[$name] ?? $options[$fallback];
}

function designer_fonts(): array
{
    return ['Tajawal' => 'تجول', 'Cairo' => 'القاهرة', 'Amiri' => 'أميري', 'Montserrat' => 'مونتسيرات'];
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

/** Section flags whose whole zone fades in the designer preview. */
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

/** Every setting the design panel controls; only these may live in the draft. */
function design_keys(): array
{
    return array_merge(
        array_keys(designer_colors()),
        array_keys(designer_flags()),
        ['body_font', 'title_font', 'section_padding', 'hero_min_height', 'section_order']
    );
}

function design_draft(): array
{
    $raw = $GLOBALS['SETTINGS']['design_draft']['ar'] ?? '';
    $draft = $raw ? json_decode((string) $raw, true) : null;
    if (!is_array($draft)) {
        return [];
    }
    return array_intersect_key($draft, array_flip(design_keys()));
}

function has_design_draft(): bool
{
    return design_draft() !== [];
}

/** Makes s() return draft design values for this request (designer preview only). */
function use_design_draft(): void
{
    $GLOBALS['SETTINGS_OVERRIDE'] = array_merge($GLOBALS['SETTINGS_OVERRIDE'] ?? [], design_draft());
}

/** Validates posted design values and returns them as setting strings. */
function sanitize_design_input(array $input): array
{
    $values = [];
    foreach (designer_colors() as $key => $label) {
        $value = trim((string) ($input['colors'][$key] ?? ''));
        if (!valid_hex($value)) {
            throw new RuntimeException('لون غير صالح: ' . $label);
        }
        $values[$key] = $value;
    }
    $fonts = designer_fonts();
    $body = (string) ($input['body_font'] ?? 'Tajawal');
    $title = (string) ($input['title_font'] ?? 'Amiri');
    if (!isset($fonts[$body], $fonts[$title])) {
        throw new RuntimeException('خط غير مدعوم.');
    }
    $values['body_font'] = $body;
    $values['title_font'] = $title;
    $values['section_padding'] = (string) max(32, min(200, (int) ($input['section_padding'] ?? 96)));
    $values['hero_min_height'] = (string) max(40, min(100, (int) ($input['hero_min_height'] ?? 92)));
    $values['section_order'] = implode(',', normalize_section_order((string) ($input['section_order'] ?? '')));
    foreach (designer_flags() as $key => $label) {
        $values[$key] = isset($input['flags'][$key]) && (string) $input['flags'][$key] === '1' ? '1' : '0';
    }
    return $values;
}

function designer_catalog(): array
{
    return [
        'general' => ['label' => 'عنوان الصفحة والمشاركة', 'groups' => ['general'], 'collections' => []],
        'topbar' => ['label' => 'الشريط العلوي', 'groups' => ['topbar'], 'collections' => []],
        'header' => ['label' => 'الشعار والقائمة', 'groups' => ['brand'], 'collections' => ['nav']],
        'hero' => ['label' => 'الواجهة', 'groups' => ['hero'], 'collections' => ['hero', 'filters']],
        'stats' => ['label' => 'أرقام الثقة', 'groups' => [], 'collections' => ['stats']],
        'about' => ['label' => 'عن الوكالة', 'groups' => ['about'], 'collections' => ['about']],
        'packages' => ['label' => 'البرامج', 'groups' => ['packages'], 'collections' => ['categories'], 'special' => 'packages'],
        'services' => ['label' => 'الخدمات', 'groups' => ['services'], 'collections' => ['services']],
        'calculator' => ['label' => 'الحاسبة', 'groups' => ['calculator'], 'collections' => ['tiers']],
        'cities' => ['label' => 'مكة والمدينة', 'groups' => ['cities'], 'collections' => [], 'special' => 'cities'],
        'guide' => ['label' => 'دليل المناسك', 'groups' => ['guide'], 'collections' => ['guides']],
        'testimonials' => ['label' => 'آراء العملاء', 'groups' => ['testimonials'], 'collections' => ['testimonials']],
        'footer' => ['label' => 'التذييل', 'groups' => ['footer'], 'collections' => ['footer']],
        'booking' => ['label' => 'الحجز وواتساب', 'groups' => ['modal'], 'collections' => []],
    ];
}

function designer_state(): array
{
    $colors = [];
    foreach (designer_colors() as $key => $label) {
        $colors[$key] = ['label' => $label, 'value' => valid_hex(s($key)) ? s($key) : '#183B35'];
    }
    $flags = [];
    foreach (designer_flags() as $key => $label) {
        $flags[$key] = ['label' => $label, 'on' => flag($key)];
    }
    $fonts = designer_fonts();
    return [
        'colors' => $colors,
        'fonts' => $fonts,
        'bodyFont' => isset($fonts[s('body_font')]) ? s('body_font') : 'Tajawal',
        'titleFont' => isset($fonts[s('title_font')]) ? s('title_font') : 'Amiri',
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
        'hasDraft' => has_design_draft(),
    ];
}

function designer_bridge(): void
{
    if (!designer_mode()) {
        return;
    }
    ?>
    <script>
    (function () {
        if (window.parent === window) return;
        const origin = location.origin;
        let picking = false;
        function clearActive() {
            document.querySelectorAll('[data-gf].gf-on').forEach(function (el) { el.classList.remove('gf-on'); });
        }
        window.addEventListener('message', function (event) {
            if (event.origin !== origin || !event.data) return;
            if (event.data.type === 'gf-pick') {
                picking = !!event.data.on;
                document.body.classList.toggle('gf-pick', picking);
            }
            if (event.data.type === 'gf-focus' && event.data.section) {
                clearActive();
                const target = document.querySelector('[data-gf="' + event.data.section + '"]');
                if (target) {
                    target.classList.add('gf-on');
                    if (event.data.scroll) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        });
        document.addEventListener('click', function (event) {
            if (!picking) return;
            const zone = event.target.closest('[data-gf]');
            if (!zone) return;
            event.preventDefault();
            event.stopPropagation();
            clearActive();
            zone.classList.add('gf-on');
            window.parent.postMessage({ type: 'gf-open', section: zone.getAttribute('data-gf') }, origin);
        }, true);
        window.parent.postMessage({ type: 'gf-ready' }, origin);
    })();
    </script>
    <?php
}
