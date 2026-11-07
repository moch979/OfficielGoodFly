<?php

/**
 * Database changes applied once, in order, on the first request after an
 * update. Each id is recorded in the `migrations` table. Never edit a
 * migration that may already have run somewhere; add a new one instead.
 */
function migrations(): array
{
    return [
        '2026_11_05_01_core_tables' => 'migrate_core_tables',
        '2026_11_05_02_design_settings' => 'migrate_design_settings',
        '2026_11_05_03_content_fixes' => 'migrate_content_fixes',
        '2026_11_05_04_extra_settings' => 'migrate_extra_settings',
        '2026_11_05_05_i18n_columns' => 'migrate_i18n_columns',
        '2026_11_05_06_i18n_content' => 'migrate_i18n_content',
        '2026_11_05_07_admin_features' => 'migrate_admin_features',
        '2026_11_05_08_visitor_language' => 'migrate_visitor_language',
        '2026_11_05_09_logo_brand' => 'migrate_logo_brand',
        '2026_11_05_10_program_pages' => 'migrate_program_pages',
        '2026_11_07_11_agency_info' => 'migrate_agency_info',
    ];
}

require_once __DIR__ . '/migrations_programs.php';
require_once __DIR__ . '/migrations_agency.php';

function run_migrations(): void
{
    $pdo = db();
    $pdo->exec('CREATE TABLE IF NOT EXISTS migrations (
        id VARCHAR(120) PRIMARY KEY,
        applied_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    $applied = $pdo->query('SELECT id FROM migrations')->fetchAll(PDO::FETCH_COLUMN);
    $pending = array_diff_key(migrations(), array_flip($applied));
    if (!$pending) {
        return;
    }

    $pdo->query("SELECT GET_LOCK('goodfly_migrations', 30)");
    try {
        $applied = $pdo->query('SELECT id FROM migrations')->fetchAll(PDO::FETCH_COLUMN);
        foreach (migrations() as $id => $function) {
            if (in_array($id, $applied, true)) {
                continue;
            }
            $function($pdo);
            $pdo->prepare('INSERT INTO migrations (id, applied_at) VALUES (?, ?)')->execute([$id, date('Y-m-d H:i:s')]);
        }
    } finally {
        $pdo->query("SELECT RELEASE_LOCK('goodfly_migrations')");
    }
}

function mig_column_exists(PDO $pdo, string $table, string $column): bool
{
    $statement = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $statement->execute([$table, $column]);
    return (int) $statement->fetchColumn() > 0;
}

function mig_add_column(PDO $pdo, string $table, string $column, string $definition): void
{
    if (!mig_column_exists($pdo, $table, $column)) {
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
    }
}

function mig_setting(PDO $pdo, string $key, string $value, string $group, string $label, string $type = 'text', string $hint = '', int $order = 500, bool $translatable = false, ?string $fr = null, ?string $en = null): void
{
    $exists = $pdo->prepare('SELECT COUNT(*) FROM settings WHERE skey = ?');
    $exists->execute([$key]);
    if ((int) $exists->fetchColumn() > 0) {
        return;
    }
    $hasI18n = mig_column_exists($pdo, 'settings', 'translatable');
    if ($hasI18n) {
        $pdo->prepare('INSERT INTO settings (skey, svalue, svalue_fr, svalue_en, translatable, sgroup, label, field_type, hint, sorder) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([$key, $value, $fr, $en, $translatable ? 1 : 0, $group, $label, $type, $hint, $order]);
    } else {
        $pdo->prepare('INSERT INTO settings (skey, svalue, sgroup, label, field_type, hint, sorder) VALUES (?, ?, ?, ?, ?, ?, ?)')
            ->execute([$key, $value, $group, $label, $type, $hint, $order]);
    }
}

/** Replaces a value only if the admin has not changed it from the shipped default. */
function mig_replace_setting(PDO $pdo, string $key, string $old, string $new): void
{
    $pdo->prepare('UPDATE settings SET svalue = ? WHERE skey = ? AND svalue = ?')->execute([$new, $key, $old]);
}

function migrate_core_tables(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS rate_hits (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        bucket VARCHAR(40) NOT NULL,
        hit_key VARCHAR(190) NOT NULL,
        created_at DATETIME NOT NULL,
        INDEX idx_rate (bucket, hit_key, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    $pdo->exec('CREATE TABLE IF NOT EXISTS snapshots (
        id INT AUTO_INCREMENT PRIMARY KEY,
        label VARCHAR(190) NOT NULL,
        payload LONGTEXT NOT NULL,
        size_bytes INT NOT NULL DEFAULT 0,
        created_by VARCHAR(80) NOT NULL DEFAULT \'\',
        created_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    mig_add_column($pdo, 'settings', 'svalue_fr', 'MEDIUMTEXT NULL AFTER svalue');
    mig_add_column($pdo, 'settings', 'svalue_en', 'MEDIUMTEXT NULL AFTER svalue_fr');
    mig_add_column($pdo, 'settings', 'translatable', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER svalue_en');
}

function migrate_design_settings(PDO $pdo): void
{
    mig_setting($pdo, 'body_font', 'Tajawal', 'design', 'خط النصوص', 'text', 'Tajawal أو Cairo أو Amiri أو Montserrat', 10);
    mig_setting($pdo, 'title_font', 'Amiri', 'design', 'خط العناوين', 'text', 'Amiri أو Cairo أو Tajawal أو Montserrat', 20);
    mig_setting($pdo, 'section_padding', '96', 'design', 'فراغ الأقسام بالبكسل', 'number', 'المسافة أعلى وأسفل الأقسام الكبيرة', 30);
    mig_setting($pdo, 'hero_min_height', '92', 'design', 'ارتفاع الواجهة', 'number', 'نسبة من ارتفاع الشاشة، من 40 إلى 100', 40);
    mig_setting($pdo, 'section_order', 'hero,stats,about,packages,services,calculator,cities,guide,testimonials', 'design', 'ترتيب الأقسام', 'text', 'يُفضَّل ترتيبها من المصمم المرئي', 50);
    mig_setting($pdo, 'show_hero', '1', 'visibility', 'الواجهة الرئيسية', 'bool', '', 5);
}

function migrate_content_fixes(PDO $pdo): void
{
    $wedding = '%photo-1591604466107%';
    $clockTower = '%photo-1565552645632%';
    $pool = '%photo-1542314831%';

    $hero = $pdo->prepare('UPDATE hero_slides SET image_url = ? WHERE image_url LIKE ?');
    $hero->execute(['assets/img/haram-2022.jpg', $wedding]);
    $hero->execute(['assets/img/nabawi-aerial.jpg', $clockTower]);
    $hero->execute(['assets/img/kaaba-night.jpg', $pool]);

    $pdo->prepare("UPDATE settings SET svalue = ? WHERE skey = 'about_image' AND svalue LIKE ?")->execute(['assets/img/nabawi-day-800.jpg', $pool]);

    $package = $pdo->prepare('UPDATE packages SET image = ? WHERE image LIKE ?');
    $package->execute(['assets/img/kaaba-night-800.jpg', $wedding]);
    $package->execute(['assets/img/kiswah-800.jpg', $pool]);
    $package->execute(['assets/img/green-dome-800.jpg', $clockTower]);

    $city = $pdo->prepare('UPDATE cities SET image = ? WHERE image LIKE ?');
    $city->execute(['assets/img/haram-2022-800.jpg', $wedding]);
    $city->execute(['assets/img/nabawi-aerial-800.jpg', $clockTower]);

    $hasRajab = (int) $pdo->query("SELECT COUNT(*) FROM package_categories WHERE slug = 'rajab'")->fetchColumn();
    if (!$hasRajab) {
        $pdo->prepare('INSERT INTO package_categories (slug, label, sort_order) VALUES (?, ?, ?)')->execute(['rajab', 'عمرة رجب وشعبان', 15]);
    }
    $pdo->prepare("UPDATE packages SET category = 'rajab' WHERE category = 'custom' AND title = ?")->execute(['Good Fly - عمرة رجب وشعبان VIP']);

    $pdo->prepare('UPDATE filter_options SET label = ? WHERE label = ?')->execute(['7 أيام (أسبوع)', '7 أيام (أسسبوع)']);
    $pdo->prepare('UPDATE packages SET distance = ? WHERE distance = ?')->execute(['صف أول - مخيمات مكيّفة في منى', 'صف أول - مخيمات مجوفة في منى']);
    $pdo->prepare('UPDATE about_points SET text = ? WHERE text = ?')->execute(['وكالة أسفار مرخّصة ومعتمدة رسمياً', 'اعتماد رسمي من وزارة الحج والعمرة']);
    $pdo->prepare('UPDATE footer_links SET label = ? WHERE label = ?')->execute(['التراخيص والاعتمادات', 'تراخيص وزارة الحج']);

    mig_replace_setting($pdo, 'phone_display', '+966 800 466 3359', '+212 5 22 00 00 00');
    mig_replace_setting($pdo, 'footer_phone', '+966 800 466 3359', '+212 5 22 00 00 00');
    mig_replace_setting($pdo, 'wa_number', '9668004663359', '212600000000');
    mig_replace_setting($pdo, 'modal_phone_ph', '+966 500000000', '+212 6 00 00 00 00');
    mig_replace_setting($pdo, 'footer_address', 'طريق الملك فهد، البرج التجاري، الرياض / جدة', 'شارع محمد الخامس، الدار البيضاء، المغرب');
    mig_replace_setting($pdo, 'announcement', '✨ فتح باب التسجيل الرسمي لرحلات الحج الفاخر وعمرة رمضان المبارك 1446هـ - 2026م', '✨ التسجيل مفتوح لعمرة رمضان 1448هـ (فبراير 2027) وموسم حج 2027م');
    mig_replace_setting($pdo, 'calc_currency', 'دولار / تقريبي', 'درهم / تقريبي');

    $tier = $pdo->prepare('UPDATE calc_tiers SET price_per_day = ? WHERE tier_key = ? AND price_per_day = ?');
    $tier->execute([5000, 'vip', 520]);
    $tier->execute([2900, 'premium', 300]);
    $tier->execute([2300, 'executive', 240]);

    $price = $pdo->prepare('UPDATE packages SET price = ? WHERE price = ?');
    $price->execute(['38,000 درهم', '3,950 $']);
    $price->execute(['115,000 درهم', '11,800 $']);
    $price->execute(['25,000 درهم', '2,550 $']);

    $pdo->exec("DELETE FROM filter_options WHERE group_key = 'trip_type'");
}

function migrate_extra_settings(PDO $pdo): void
{
    mig_setting($pdo, 'site_url', '', 'general', 'رابط الموقع الكامل', 'text', 'مثل https://goodfly.ma — يُستخدم في روابط المشاركة وخريطة الموقع. اتركه فارغاً أثناء التجربة.', 5);
    mig_setting($pdo, 'og_image', 'assets/img/haram-2022.jpg', 'general', 'صورة المشاركة في واتساب وفيسبوك', 'image', 'يُفضّل 1200×630', 30);
    mig_setting($pdo, 'favicon', '', 'brand', 'أيقونة المتصفح', 'image', 'صورة مربعة PNG. اتركها فارغة لاستخدام الأيقونة الافتراضية.', 60);
    mig_setting($pdo, 'search_all_label', 'جميع برامج Good Fly', 'hero', 'خيار «الكل» في نوع الرحلة', 'text', 'أنواع الرحلة تُؤخذ تلقائياً من تصنيفات البرامج', 255);
    mig_setting($pdo, 'calc_note_prefix', 'تقدير من حاسبة Good Fly', 'calculator', 'بداية الملاحظة المرسلة مع الطلب', 'text', '', 335);
    mig_setting($pdo, 'modal_whatsapp_hint', 'لتسريع الرد، أرسل تفاصيل طلبك لنا عبر واتساب أيضاً.', 'modal', 'نص زر واتساب بعد نجاح الطلب', 'text', '', 365);

    $button = $pdo->query("SELECT tvalue FROM translations WHERE lang_code = 'ar' AND tkey = 'btn_consultation'")->fetchColumn();
    mig_setting($pdo, 'btn_consultation', $button !== false && $button !== '' ? (string) $button : 'حجز استشارة / طلب رحلة', 'brand', 'زر الحجز في رأس الصفحة', 'text', '', 55);
}

/** Tables and columns that hold visitor-facing text. */
function i18n_columns(): array
{
    return [
        'hero_slides' => [],
        'stats' => ['label' => 'VARCHAR(255)'],
        'about_points' => ['text' => 'VARCHAR(255)'],
        'package_categories' => ['label' => 'VARCHAR(160)'],
        'packages' => [
            'title' => 'VARCHAR(255)', 'category_badge' => 'VARCHAR(160)', 'hotel' => 'VARCHAR(255)',
            'distance' => 'VARCHAR(255)', 'description' => 'TEXT', 'price' => 'VARCHAR(80)',
        ],
        'package_features' => ['feature_text' => 'VARCHAR(255)'],
        'services' => ['title' => 'VARCHAR(255)', 'description' => 'TEXT'],
        'calc_tiers' => ['label' => 'VARCHAR(255)'],
        'cities' => ['tab_label' => 'VARCHAR(160)', 'heading' => 'VARCHAR(255)', 'caption' => 'VARCHAR(255)'],
        'city_features' => ['text' => 'VARCHAR(255)'],
        'guides' => ['title' => 'VARCHAR(255)', 'body' => 'TEXT'],
        'testimonials' => ['quote' => 'TEXT', 'name' => 'VARCHAR(160)', 'trip_label' => 'VARCHAR(180)'],
        'footer_links' => ['label' => 'VARCHAR(180)'],
        'filter_options' => ['label' => 'VARCHAR(180)'],
    ];
}

/** Settings that look like text but must stay identical in every language. */
function untranslatable_settings(): array
{
    return [
        'phone_display', 'top_email', 'license_number', 'logo_word', 'logo_accent', 'logo_badge', 'logo_icon',
        'hero_cta_primary_href', 'hero_interval', 'calc_default_tier', 'footer_license_number', 'footer_cr_number',
        'footer_phone', 'footer_email', 'wa_number', 'wa_booking_intro', 'modal_phone_ph',
        'site_url', 'body_font', 'title_font', 'section_order', 'lang_label_ar', 'lang_label_en', 'lang_label_fr',
        'password_changed',
    ];
}

function migrate_i18n_columns(PDO $pdo): void
{
    foreach (i18n_columns() as $table => $columns) {
        foreach ($columns as $column => $type) {
            foreach (extra_languages() as $lang) {
                mig_add_column($pdo, $table, $column . '_' . $lang, $type . ' NULL');
            }
        }
    }

    $untranslatable = untranslatable_settings();
    $marks = implode(', ', array_fill(0, count($untranslatable), '?'));
    $pdo->prepare("UPDATE settings SET translatable = 1 WHERE field_type IN ('text', 'textarea', 'html') AND sgroup <> 'system' AND skey NOT IN ($marks)")
        ->execute($untranslatable);
}

function migrate_i18n_content(PDO $pdo): void
{
    require_once __DIR__ . '/translations_seed.php';
    $setting = $pdo->prepare('UPDATE settings SET svalue_fr = ?, svalue_en = ? WHERE skey = ? AND svalue = ? AND (svalue_fr IS NULL OR svalue_fr = \'\')');
    foreach (seed_setting_translations() as $key => $texts) {
        $setting->execute([$texts[1], $texts[2], $key, $texts[0]]);
    }

    $buttons = $pdo->query("SELECT lang_code, tvalue FROM translations WHERE tkey = 'btn_consultation'")->fetchAll(PDO::FETCH_KEY_PAIR);
    if (!empty($buttons['fr']) || !empty($buttons['en'])) {
        $pdo->prepare("UPDATE settings SET svalue_fr = COALESCE(NULLIF(svalue_fr, ''), ?), svalue_en = COALESCE(NULLIF(svalue_en, ''), ?) WHERE skey = 'btn_consultation'")
            ->execute([$buttons['fr'] ?? '', $buttons['en'] ?? '']);
    }

    foreach (seed_row_translations() as $table => $columns) {
        foreach ($columns as $column => $texts) {
            $statement = $pdo->prepare("UPDATE `{$table}` SET `{$column}_fr` = ?, `{$column}_en` = ? WHERE `{$column}` = ? AND (`{$column}_fr` IS NULL OR `{$column}_fr` = '')");
            foreach ($texts as $arabic => $pair) {
                $statement->execute([$pair[0], $pair[1], $arabic]);
            }
        }
    }
}

function migrate_admin_features(PDO $pdo): void
{
    mig_setting($pdo, 'notify_email', '', 'notifications', 'البريد الذي تصله تنبيهات الطلبات', 'text', 'اتركه فارغاً لإيقاف التنبيهات', 10);
    mig_setting($pdo, 'mail_from', '', 'notifications', 'بريد المرسل', 'text', 'مثل no-reply@goodfly.ma — يجب أن يكون من نطاق موقعك', 20);
    mig_setting($pdo, 'smtp_host', '', 'notifications', 'خادم SMTP', 'text', 'من إعدادات بريد الاستضافة، مثل mail.goodfly.ma. اتركه فارغاً لاستخدام بريد الخادم الافتراضي', 30);
    mig_setting($pdo, 'smtp_port', '587', 'notifications', 'منفذ SMTP', 'number', '587 مع TLS أو 465 مع SSL', 40);
    mig_setting($pdo, 'smtp_secure', 'tls', 'notifications', 'نوع التشفير', 'text', 'tls أو ssl أو none', 50);
    mig_setting($pdo, 'smtp_user', '', 'notifications', 'اسم مستخدم SMTP', 'text', '', 60);
    mig_setting($pdo, 'smtp_pass', '', 'notifications', 'كلمة مرور SMTP', 'password', 'تُحفظ في قاعدة البيانات. اتركها كما هي إن لم تتغير', 70);
    mig_setting($pdo, 'design_draft', '', 'system', 'مسودة التصميم', 'text', '', 900);
}

/** Colours taken from the Good Fly logo: navy lettering, royal-blue waves, golden sun. */
function logo_palette(): array
{
    return [
        'color_green' => ['#1D3C78', 'اللون الأساسي (رأس الصفحة وقسم المدن)'],
        'color_darkgreen' => ['#0F2350', 'اللون الداكن (الشريط العلوي والتذييل)'],
        'color_gold' => ['#F2C14E', 'لون التمييز (الأزرار والعناوين الصغيرة)'],
        'color_lightgold' => ['#F8DC8E', 'لون التمييز الفاتح'],
        'color_ivory' => ['#F4F8FC', 'خلفية الصفحة'],
        'color_lightivory' => ['#FBFDFF', 'الخلفية الفاتحة'],
        'color_slate' => ['#24324A', 'لون النصوص'],
    ];
}

function migrate_logo_brand(PDO $pdo): void
{
    $current = $pdo->query("SELECT skey, svalue FROM settings WHERE skey LIKE 'color\\_%'")->fetchAll(PDO::FETCH_KEY_PAIR);
    $differs = false;
    foreach (logo_palette() as $key => [$hex]) {
        if (strcasecmp((string) ($current[$key] ?? ''), $hex) !== 0) {
            $differs = true;
        }
    }
    if ($differs && $current) {
        require_once __DIR__ . '/snapshots.php';
        $payload = json_encode(snapshot_payload(), JSON_UNESCAPED_UNICODE);
        $pdo->prepare('INSERT INTO snapshots (label, payload, size_bytes, created_by, created_at) VALUES (?, ?, ?, ?, ?)')
            ->execute(['تلقائية: الألوان قبل اعتماد ألوان الشعار', $payload, strlen($payload), 'system', date('Y-m-d H:i:s')]);
    }

    $update = $pdo->prepare('UPDATE settings SET svalue = ?, label = ? WHERE skey = ?');
    foreach (logo_palette() as $key => [$hex, $label]) {
        $update->execute([$hex, $label, $key]);
    }
    $pdo->exec("UPDATE settings SET svalue = '' WHERE skey = 'design_draft'");

    mig_setting($pdo, 'logo_image', 'assets/img/logo.png', 'brand', 'صورة الشعار', 'image', 'PNG بخلفية شفافة أو SVG، ويُفضّل عرض 400 بكسل أو أكثر. اتركها فارغة لعرض الشعار النصي.', 1);
    mig_setting($pdo, 'logo_height', '52', 'brand', 'ارتفاع الشعار بالبكسل', 'number', 'بين 32 و96', 2);
    mig_setting($pdo, 'logo_alt', 'Good Fly Travel', 'brand', 'وصف الشعار لقارئات الشاشة', 'text', '', 3);
    $pdo->exec("UPDATE settings SET hint = 'يظهر فقط إذا كانت صورة الشعار فارغة' WHERE skey IN ('logo_word', 'logo_accent', 'logo_badge', 'logo_icon')");
}

function migrate_visitor_language(PDO $pdo): void
{
    mig_add_column($pdo, 'bookings', 'lang', "VARCHAR(5) NOT NULL DEFAULT 'ar'");
    mig_add_column($pdo, 'subscribers', 'lang', "VARCHAR(5) NOT NULL DEFAULT 'ar'");
    $hasIndex = (int) $pdo->query("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND INDEX_NAME = 'idx_bookings_status'")->fetchColumn();
    if (!$hasIndex) {
        $pdo->exec('CREATE INDEX idx_bookings_status ON bookings (status, id)');
    }
}
