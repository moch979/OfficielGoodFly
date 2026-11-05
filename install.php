<?php

/**
 * One-time installer. Runs only while the database is empty; on an installed
 * site it refuses to do anything.
 *   Web: open /install.php and choose the admin username and password.
 *   CLI: php install.php <username> [password]   (a password is generated if omitted)
 */

$config = require __DIR__ . '/config.php';
date_default_timezone_set($config['timezone'] ?? 'Africa/Casablanca');
$isCli = PHP_SAPI === 'cli';
$generatedPassword = false;

function install_page(string $title, string $body, int $status = 200): void
{
    http_response_code($status);
    echo '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title>';
    echo '<style>body{font-family:Tahoma,sans-serif;background:#102924;color:#F7F5EF;padding:40px}.box{max-width:560px;margin:auto;background:#183B35;border:1px solid #D8C39B;border-radius:20px;padding:28px}h1{color:#D8C39B;margin-top:0}label{display:block;margin:12px 0 6px;font-weight:bold}input{width:100%;padding:11px;border-radius:10px;border:0;box-sizing:border-box}button{margin-top:18px;padding:12px 20px;border:0;border-radius:999px;background:#D8C39B;color:#102924;font-weight:bold;cursor:pointer}.err{background:#5c2020;padding:10px;border-radius:10px}a{color:#D8C39B}</style>';
    echo '</head><body><div class="box"><h1>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1>' . $body . '</div></body></html>';
    exit;
}

function install_form(string $error = ''): void
{
    $body = '<p>اختر اسم مستخدم وكلمة مرور قوية لحساب الإدارة. لا توجد كلمة مرور افتراضية.</p>';
    if ($error !== '') {
        $body .= '<p class="err">' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . '</p>';
    }
    $body .= '<form method="post"><label for="u">اسم المستخدم</label><input id="u" name="username" required pattern="[A-Za-z0-9_.\-]{3,40}" autocomplete="username">';
    $body .= '<label for="p">كلمة المرور (10 أحرف على الأقل)</label><input id="p" type="password" name="password" required minlength="10" autocomplete="new-password">';
    $body .= '<label for="c">تأكيد كلمة المرور</label><input id="c" type="password" name="confirm" required minlength="10" autocomplete="new-password">';
    $body .= '<button type="submit">تثبيت الموقع</button></form>';
    install_page('تثبيت Good Fly', $body);
}

try {
    $server = new PDO(
        sprintf('mysql:host=%s;charset=%s', $config['host'], $config['charset']),
        $config['user'],
        $config['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $dbName = str_replace('`', '', $config['name']);
    $server->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    $pdo = new PDO(
        sprintf('mysql:host=%s;dbname=%s;charset=%s', $config['host'], $dbName, $config['charset']),
        $config['user'],
        $config['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    $installed = false;
    try {
        $installed = (int) $pdo->query('SELECT COUNT(*) FROM settings')->fetchColumn() > 0;
    } catch (PDOException $e) {
        $installed = false;
    }
    if ($installed) {
        if ($isCli) {
            echo 'الموقع مثبت مسبقاً. لم يتغير شيء.' . PHP_EOL;
            exit(0);
        }
        install_page('الموقع مثبت مسبقاً', '<p>صفحة التثبيت معطلة لأن قاعدة البيانات تحتوي على بيانات. يُفضّل حذف ملف install.php من الخادم.</p><p><a href="admin/login.php">دخول الإدارة</a></p>', 403);
    }

    if ($isCli) {
        $adminUser = (string) ($argv[1] ?? 'admin');
        $adminPass = (string) ($argv[2] ?? '');
        if ($adminPass === '') {
            $adminPass = rtrim(strtr(base64_encode(random_bytes(12)), '+/', 'Kq'), '=');
            $generatedPassword = true;
        }
    } else {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            install_form();
        }
        $adminUser = trim((string) ($_POST['username'] ?? ''));
        $adminPass = (string) ($_POST['password'] ?? '');
        if ($adminPass !== (string) ($_POST['confirm'] ?? '')) {
            install_form('تأكيد كلمة المرور غير مطابق.');
        }
    }
    if (!preg_match('/^[A-Za-z0-9_.\-]{3,40}$/', $adminUser)) {
        $isCli ? exit("اسم المستخدم: 3 إلى 40 حرفاً إنجليزياً أو أرقاماً.\n") : install_form('اسم المستخدم: 3 إلى 40 حرفاً إنجليزياً أو أرقاماً بلا مسافات.');
    }
    if (strlen($adminPass) < 10) {
        $isCli ? exit("كلمة المرور 10 أحرف على الأقل.\n") : install_form('كلمة المرور 10 أحرف على الأقل.');
    }

    $tables = [
        "CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(80) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS settings (
            skey VARCHAR(120) PRIMARY KEY,
            svalue MEDIUMTEXT NULL,
            sgroup VARCHAR(60) NOT NULL DEFAULT 'general',
            label VARCHAR(200) NOT NULL,
            field_type VARCHAR(30) NOT NULL DEFAULT 'text',
            hint VARCHAR(255) NULL,
            sorder INT NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS nav_links (
            id INT AUTO_INCREMENT PRIMARY KEY,
            label_ar VARCHAR(160) NOT NULL,
            label_en VARCHAR(160) NOT NULL,
            label_fr VARCHAR(160) NOT NULL,
            href VARCHAR(255) NOT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS hero_slides (
            id INT AUTO_INCREMENT PRIMARY KEY,
            image_url TEXT NOT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS stats (
            id INT AUTO_INCREMENT PRIMARY KEY,
            value_text VARCHAR(80) NOT NULL,
            label VARCHAR(255) NOT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS about_points (
            id INT AUTO_INCREMENT PRIMARY KEY,
            icon VARCHAR(80) NOT NULL,
            text VARCHAR(255) NOT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS package_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            slug VARCHAR(60) NOT NULL UNIQUE,
            label VARCHAR(160) NOT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS packages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            category VARCHAR(60) NOT NULL,
            category_badge VARCHAR(160) NOT NULL,
            hotel VARCHAR(255) NOT NULL,
            hotel_class VARCHAR(80) NOT NULL DEFAULT '',
            distance VARCHAR(255) NOT NULL DEFAULT '',
            duration_days VARCHAR(40) NOT NULL DEFAULT '',
            description TEXT NOT NULL,
            price VARCHAR(80) NOT NULL,
            image TEXT NOT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS package_features (
            id INT AUTO_INCREMENT PRIMARY KEY,
            package_id INT NOT NULL,
            feature_text VARCHAR(255) NOT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            CONSTRAINT fk_feature_package FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS services (
            id INT AUTO_INCREMENT PRIMARY KEY,
            icon VARCHAR(80) NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS calc_tiers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tier_key VARCHAR(40) NOT NULL UNIQUE,
            label VARCHAR(255) NOT NULL,
            price_per_day INT NOT NULL DEFAULT 0,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS cities (
            id INT AUTO_INCREMENT PRIMARY KEY,
            slug VARCHAR(40) NOT NULL UNIQUE,
            tab_label VARCHAR(160) NOT NULL,
            heading VARCHAR(255) NOT NULL,
            image TEXT NOT NULL,
            caption VARCHAR(255) NOT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS city_features (
            id INT AUTO_INCREMENT PRIMARY KEY,
            city_id INT NOT NULL,
            icon VARCHAR(80) NOT NULL,
            text VARCHAR(255) NOT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            CONSTRAINT fk_feature_city FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS guides (
            id INT AUTO_INCREMENT PRIMARY KEY,
            icon VARCHAR(80) NOT NULL,
            title VARCHAR(255) NOT NULL,
            body TEXT NOT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS testimonials (
            id INT AUTO_INCREMENT PRIMARY KEY,
            quote TEXT NOT NULL,
            initials VARCHAR(20) NOT NULL,
            name VARCHAR(160) NOT NULL,
            trip_label VARCHAR(180) NOT NULL,
            stars TINYINT NOT NULL DEFAULT 5,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS footer_links (
            id INT AUTO_INCREMENT PRIMARY KEY,
            group_key VARCHAR(40) NOT NULL,
            label VARCHAR(180) NOT NULL,
            href VARCHAR(255) NOT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS filter_options (
            id INT AUTO_INCREMENT PRIMARY KEY,
            group_key VARCHAR(40) NOT NULL,
            option_value VARCHAR(80) NOT NULL,
            label VARCHAR(180) NOT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS translations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lang_code VARCHAR(5) NOT NULL,
            tkey VARCHAR(80) NOT NULL,
            tvalue VARCHAR(255) NOT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            UNIQUE KEY uniq_translation (lang_code, tkey)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS bookings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(180) NOT NULL,
            phone VARCHAR(80) NOT NULL,
            trip_type VARCHAR(180) NOT NULL DEFAULT '',
            travelers INT NOT NULL DEFAULT 1,
            travel_date VARCHAR(40) NOT NULL DEFAULT '',
            notes TEXT NULL,
            package_title VARCHAR(255) NOT NULL DEFAULT '',
            status VARCHAR(40) NOT NULL DEFAULT 'new',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS subscribers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(180) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ];

    foreach ($tables as $sql) {
        $pdo->exec($sql);
    }

    $existing = (int) $pdo->query('SELECT COUNT(*) FROM settings')->fetchColumn();
    if ($existing > 0) {
        if (!$isCli) {
            echo '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><title>تم التثبيت</title></head><body style="font-family:Tahoma;background:#F7F5EF;color:#183B35;padding:40px">';
            echo '<h1>الموقع مثبت مسبقاً</h1><p>لن يتم مسح البيانات الحالية.</p>';
            echo '<p><a href="index.php">فتح الموقع</a> — <a href="admin/login.php">لوحة التحكم</a></p></body></html>';
        } else {
            echo "الموقع مثبت مسبقاً. لم يتم تغيير البيانات." . PHP_EOL;
        }
        exit(0);
    }

    $settings = [
        ['site_title', 'Good Fly Travel | وكالة جود فلاي للأسفار والحج والعمرة - الرحلات الإيمانية الفاخرة', 'general', 'عنوان المتصفح', 'text', ''],
        ['meta_description', 'وكالة جود فلاي للأسفار والحج والعمرة: برامج فاخرة، فنادق مطلة على الحرمين، وتأطير إيماني متكامل.', 'general', 'وصف الموقع', 'textarea', 'يظهر لمحركات البحث.'],

        ['top_license_prefix', 'وكالة معتمدة رسمياً | ترخيص رقم:', 'topbar', 'نص الترخيص', 'text', ''],
        ['license_number', 'GF-8842/2026', 'topbar', 'رقم الترخيص في الشريط', 'text', ''],
        ['hotline_label', 'الخط الساخن:', 'topbar', 'تسمية الهاتف', 'text', ''],
        ['phone_display', '+966 800 466 3359', 'topbar', 'رقم الهاتف الظاهر', 'text', ''],
        ['top_email', 'info@goodfly-travel.com', 'topbar', 'البريد في الشريط العلوي', 'text', ''],
        ['announcement', '✨ فتح باب التسجيل الرسمي لرحلات الحج الفاخر وعمرة رمضان المبارك 1446هـ - 2026م', 'topbar', 'الإعلان المتحرك', 'text', ''],
        ['lang_choose_label', 'اختر اللغة / Language:', 'topbar', 'نص اختيار اللغة في الجوال', 'text', ''],
        ['lang_label_ar', 'العربية', 'topbar', 'اسم اللغة العربية', 'text', ''],
        ['lang_label_en', 'English', 'topbar', 'اسم اللغة الإنجليزية', 'text', ''],
        ['lang_label_fr', 'Français', 'topbar', 'اسم اللغة الفرنسية', 'text', ''],

        ['logo_word', 'GOOD', 'brand', 'الجزء الأول من الشعار', 'text', ''],
        ['logo_accent', 'FLY', 'brand', 'الجزء الذهبي من الشعار', 'text', ''],
        ['logo_badge', 'Travel', 'brand', 'الشارة بجانب الشعار', 'text', ''],
        ['logo_tagline', 'Sacred & Premium Journeys', 'brand', 'السطر تحت الشعار', 'text', ''],
        ['logo_icon', 'fa-plane-departure', 'brand', 'أيقونة الشعار', 'text', 'اسم الأيقونة فقط، مثل fa-plane-departure'],

        ['color_green', '#183B35', 'colors', 'الأخضر الأساسي', 'color', ''],
        ['color_darkgreen', '#102924', 'colors', 'الأخضر الداكن', 'color', ''],
        ['color_gold', '#D8C39B', 'colors', 'الذهبي', 'color', ''],
        ['color_lightgold', '#EADBC0', 'colors', 'الذهبي الفاتح', 'color', ''],
        ['color_ivory', '#F7F5EF', 'colors', 'العاجي', 'color', ''],
        ['color_lightivory', '#FDFCF9', 'colors', 'العاجي الفاتح', 'color', ''],
        ['color_slate', '#2C3E3A', 'colors', 'لون النص', 'color', ''],
        ['body_font', 'Tajawal', 'design', 'خط النصوص', 'text', 'Tajawal أو Cairo أو Amiri أو Montserrat'],
        ['title_font', 'Amiri', 'design', 'خط العناوين', 'text', 'Amiri أو Cairo أو Tajawal أو Montserrat'],
        ['section_padding', '96', 'design', 'فراغ الأقسام بالبكسل', 'number', 'المسافة أعلى وأسفل الأقسام الكبيرة'],
        ['hero_min_height', '92', 'design', 'ارتفاع الواجهة', 'number', 'نسبة من ارتفاع الشاشة، من 40 إلى 100'],
        ['section_order', 'hero,stats,about,packages,services,calculator,cities,guide,testimonials', 'design', 'ترتيب الأقسام', 'text', 'يُفضَّل ترتيبها من المصمم المرئي'],

        ['hero_badge', 'Good Fly Travel - الخيار المعتمد الأول لرحلات الحج والعمرة', 'hero', 'شارة الواجهة', 'text', ''],
        ['hero_title', 'Good Fly Travel', 'hero', 'عنوان الواجهة', 'text', ''],
        ['hero_highlight', 'رحلتكم الإيمانية بمعايير الراحة والجودة الفائقة', 'hero', 'العنوان الذهبي', 'textarea', ''],
        ['hero_subtitle', 'نصحبكم في رحلات الحج والعمرة بتنظيم احترافي شامل، يتضمن أفضل خيارات الطيران، الفنادق المطلة على الحرمين الشريفين، والتأطير الإيماني الرفيع.', 'hero', 'وصف الواجهة', 'textarea', ''],
        ['hero_cta_primary', 'استكشف برامج جود فلاي', 'hero', 'زر الواجهة الأول', 'text', ''],
        ['hero_cta_primary_href', '#packages', 'hero', 'رابط الزر الأول', 'text', ''],
        ['hero_cta_secondary', 'طلب تصميم رحلة خاصة', 'hero', 'زر الواجهة الثاني', 'text', ''],
        ['search_label_type', 'نوع الرحلة', 'hero', 'عنوان حقل نوع الرحلة', 'text', ''],
        ['search_label_hotel', 'فئة الفندق', 'hero', 'عنوان حقل الفندق', 'text', ''],
        ['search_label_duration', 'مدة الإقامة', 'hero', 'عنوان حقل المدة', 'text', ''],
        ['search_button', 'بحث عن برنامج', 'hero', 'زر البحث', 'text', ''],
        ['hero_interval', '5000', 'hero', 'سرعة تبديل الصور بالمللي ثانية', 'number', '5000 تعني 5 ثوانٍ'],
        ['show_hero_search', '1', 'hero', 'إظهار شريط البحث', 'bool', ''],

        ['about_kicker', 'عن وكالة Good Fly Travel', 'about', 'السطر الصغير', 'text', ''],
        ['about_title', 'مصداقية واحترافية متكاملة في خدمة ضيوف الرحمن', 'about', 'عنوان القسم', 'textarea', ''],
        ['about_body', 'تعتبر وكالة <strong>Good Fly Travel (جود فلاي)</strong> إحدى الوكالات الرائدة والمطورة في تنظيم السفر والسياحة الدينية للحج والعمرة. نحن نجمع بين الخبرة العميقة في التعامل مع الضيوف والحلول المبتكرة التي تضمن أعلى درجات الطمأنينة والأمان.', 'about', 'النص التعريفي', 'html', 'يمكن استخدام وسوم مثل strong و br'],
        ['about_image', 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=800&q=80', 'about', 'صورة القسم', 'image', ''],
        ['about_image_alt', 'Good Fly Travel Services', 'about', 'وصف الصورة', 'text', ''],
        ['about_card_title', 'Good Fly Travel', 'about', 'عنوان البطاقة فوق الصورة', 'text', ''],
        ['about_card_text', 'شعارنا: جودة السفر.. وعظمة المقصد.', 'about', 'نص البطاقة فوق الصورة', 'text', ''],

        ['pkg_kicker', 'برامج Good Fly المعالم', 'packages', 'السطر الصغير', 'text', ''],
        ['pkg_title', 'باقات الحج والعمرة الرسمية', 'packages', 'عنوان القسم', 'text', ''],
        ['pkg_subtitle', 'خيارات متنوعة تناسب مختلف التطلعات، تتضمن الطيران، الإقامة المطلة، التأشيرات، والنقل الخاص.', 'packages', 'وصف القسم', 'textarea', ''],
        ['pkg_tab_all', 'جميع البرامج', 'packages', 'تبويب الكل', 'text', ''],
        ['pkg_price_label', 'السعر الساري / للشخص', 'packages', 'عبارة فوق السعر', 'text', ''],
        ['pkg_button', 'تفاصيل وحجز الرحلة', 'packages', 'زر البطاقة', 'text', ''],
        ['pkg_empty', 'لا توجد برامج مطابقة لخيارات البحث حالياً.', 'packages', 'رسالة عدم وجود نتائج', 'text', ''],

        ['srv_kicker', 'خدمات Good Fly VIP', 'services', 'السطر الصغير', 'text', ''],
        ['srv_title', 'خدمات متكاملة تضمن راحتكم الكاملة', 'services', 'عنوان القسم', 'text', ''],
        ['srv_subtitle', 'نحن في Good Fly Travel ندير كافة التفاصيل التنظيمية واللوجستية بدقة واحترافية متناهية.', 'services', 'وصف القسم', 'textarea', ''],

        ['calc_kicker', 'حاسبة Good Fly التقديرية', 'calculator', 'السطر الصغير', 'text', ''],
        ['calc_title', 'احسب تكلفة رحلتك فورياً', 'calculator', 'عنوان القسم', 'text', ''],
        ['calc_subtitle', 'اختر المعايير التي تناسبك واستلم العرض الرسمي المبدئي فوراً', 'calculator', 'وصف القسم', 'text', ''],
        ['calc_q1', '1. فئة الخدمة والإقامة:', 'calculator', 'سؤال الفئة', 'text', ''],
        ['calc_q2', '2. مدة الرحلة:', 'calculator', 'سؤال المدة', 'text', ''],
        ['calc_q3', '3. عدد المسافرين:', 'calculator', 'سؤال العدد', 'text', ''],
        ['calc_days_unit', 'أيام', 'calculator', 'وحدة الأيام', 'text', ''],
        ['calc_people_unit', 'أفراد', 'calculator', 'وحدة الأفراد', 'text', ''],
        ['calc_days_min', '5', 'calculator', 'أقل مدة', 'number', ''],
        ['calc_days_max', '21', 'calculator', 'أقصى مدة', 'number', ''],
        ['calc_people_min', '1', 'calculator', 'أقل عدد مسافرين', 'number', ''],
        ['calc_people_max', '10', 'calculator', 'أقصى عدد مسافرين', 'number', ''],
        ['calc_default_tier', 'vip', 'calculator', 'الفئة الافتراضية', 'text', 'نفس معرّف الفئة مثل vip'],
        ['calc_default_days', '10', 'calculator', 'المدة الافتراضية', 'number', ''],
        ['calc_default_people', '2', 'calculator', 'العدد الافتراضي', 'number', ''],
        ['calc_result_label', 'التقدير الأولي لرحلة Good Fly:', 'calculator', 'عنوان النتيجة', 'text', ''],
        ['calc_currency', 'دولار / تقريبي', 'calculator', 'العملة بعد السعر', 'text', ''],
        ['calc_note', '* تشمل الطيران، الإقامة والتأشيرات والتنقلات', 'calculator', 'ملاحظة النتيجة', 'text', ''],
        ['calc_cta', 'إرسال الطلب وحجز الرحلة', 'calculator', 'زر إرسال التقدير', 'text', ''],

        ['city_kicker', 'وجهات Good Fly المعتمدة', 'cities', 'السطر الصغير', 'text', ''],
        ['city_title', 'مكة المكرمة والمدينة المنورة', 'cities', 'عنوان القسم', 'text', ''],
        ['city_intro', 'تتولى جود فلاي تنظيم كافة التفاصيل السكنية والميدانية بالقرب المباشر من صحن المسجد الحرام والمسجد النبوي الشريف.', 'cities', 'مقدمة القسم', 'textarea', ''],

        ['guide_kicker', 'دليل Good Fly للمسافر', 'guide', 'السطر الصغير', 'text', ''],
        ['guide_title', 'إرشادات ومناسك الرحلة', 'guide', 'عنوان القسم', 'text', ''],
        ['guide_subtitle', 'معلومات هامة تضمن استعدادكم الكامل لأداء المناسك بطمأنينة', 'guide', 'وصف القسم', 'text', ''],

        ['tst_kicker', 'ثقة عملائنا', 'testimonials', 'السطر الصغير', 'text', ''],
        ['tst_title', 'آراء وانطباعات معتمري Good Fly', 'testimonials', 'عنوان القسم', 'text', ''],

        ['footer_about', 'وكالة أسفار ورائدة في تنظيم رحلات الحج والعمرة والسياحة الفاخرة، مرخصة ومعتمدة رسمياً لتقديم أعلى معايير الجودة.', 'footer', 'نبذة التذييل', 'textarea', ''],
        ['footer_license_label', 'رقم الترخيص السياحي:', 'footer', 'تسمية الترخيص', 'text', ''],
        ['footer_license_number', 'GF-8842/2026', 'footer', 'رقم الترخيص', 'text', ''],
        ['footer_cr_label', 'السجل التجاري:', 'footer', 'تسمية السجل', 'text', ''],
        ['footer_cr_number', '1010892231', 'footer', 'رقم السجل', 'text', ''],
        ['footer_links_title', 'روابط سريعة', 'footer', 'عنوان عمود الروابط', 'text', ''],
        ['footer_contact_title', 'اتصل بـ Good Fly', 'footer', 'عنوان عمود الاتصال', 'text', ''],
        ['footer_address', 'طريق الملك فهد، البرج التجاري، الرياض / جدة', 'footer', 'العنوان', 'text', ''],
        ['footer_phone', '+966 800 466 3359', 'footer', 'هاتف التذييل', 'text', ''],
        ['footer_email', 'booking@goodfly-travel.com', 'footer', 'بريد التذييل', 'text', ''],
        ['footer_news_title', 'النشرة البريدية الرسمية', 'footer', 'عنوان النشرة', 'text', ''],
        ['footer_news_text', 'اشترك لتصلك عروض Good Fly الحصرية قبل الجميع.', 'footer', 'وصف النشرة', 'text', ''],
        ['footer_news_placeholder', 'بريدك الإلكتروني', 'footer', 'تلميح حقل البريد', 'text', ''],
        ['footer_news_button', 'اشتراك رسمي', 'footer', 'زر الاشتراك', 'text', ''],
        ['footer_news_success', 'شكراً لاشتراككم في نشرة Good Fly Travel الرسمية', 'footer', 'رسالة نجاح الاشتراك', 'text', ''],
        ['copyright', '© 2026 Good Fly Travel. جميع الحقوق محفوظة لشركة جود فلاي للأسفار والحج والعمرة.', 'footer', 'حقوق النشر', 'text', ''],

        ['modal_kicker', 'Good Fly Travel', 'modal', 'سطر النموذج العلوي', 'text', ''],
        ['modal_title', 'طلب حجز واستشارة رحلة', 'modal', 'عنوان النموذج', 'text', ''],
        ['modal_package_prefix', 'حجز برنامج: ', 'modal', 'بداية العنوان عند اختيار برنامج', 'text', ''],
        ['modal_subtitle', 'يرجى تعبئة الحقول وسيتم التواصل معكم فورياً بواسطة مستشاري جود فلاي.', 'modal', 'وصف النموذج', 'textarea', ''],
        ['modal_name_label', 'الاسم الكامل *', 'modal', 'حقل الاسم', 'text', ''],
        ['modal_name_ph', 'مثال: محمد عبدالله', 'modal', 'تلميح الاسم', 'text', ''],
        ['modal_phone_label', 'رقم الواتساب / الهاتف *', 'modal', 'حقل الهاتف', 'text', ''],
        ['modal_phone_ph', '+966 500000000', 'modal', 'تلميح الهاتف', 'text', ''],
        ['modal_type_label', 'نوع الرحلة المطلوبة', 'modal', 'حقل نوع الرحلة', 'text', ''],
        ['modal_travelers_label', 'عدد المسافرين', 'modal', 'حقل العدد', 'text', ''],
        ['modal_date_label', 'التاريخ المتوقع', 'modal', 'حقل التاريخ', 'text', ''],
        ['modal_notes_label', 'ملاحظات أو طلبات خاصة', 'modal', 'حقل الملاحظات', 'text', ''],
        ['modal_notes_ph', 'اكتب استفساراتك أو المتطلبات الخاصة...', 'modal', 'تلميح الملاحظات', 'text', ''],
        ['modal_submit', 'إرسال الطلب لـ Good Fly', 'modal', 'زر الإرسال', 'text', ''],
        ['modal_success', 'شكراً لكم {name}، تم إرسال طلبكم لـ Good Fly Travel بنجاح.', 'modal', 'رسالة النجاح', 'text', 'استخدم {name} ليظهر اسم العميل'],
        ['modal_error', 'تعذر حفظ الطلب. حاول مرة أخرى.', 'modal', 'رسالة الخطأ', 'text', ''],
        ['wa_number', '9668004663359', 'modal', 'رقم واتساب بدون + أو مسافات', 'text', ''],
        ['wa_header_message', 'السلام عليكم وكالة جود فلاي، أرغب في الاستفسار عن برامج الحج والعمرة', 'modal', 'رسالة زر واتساب في الهيدر', 'textarea', ''],
        ['wa_float_message', 'السلام عليكم وكالة جود فلاي Good Fly، أرغب في الاستفسار عن رحلات الحج والعمرة', 'modal', 'رسالة زر واتساب العائم', 'textarea', ''],
        ['wa_booking_intro', 'طلب جديد من موقع Good Fly Travel:', 'modal', 'مقدمة رسالة طلب الحجز', 'text', ''],
        ['wa_enabled', '1', 'modal', 'فتح واتساب بعد إرسال الطلب', 'bool', 'الطلب يُحفظ في لوحة التحكم في الحالتين'],

        ['show_hero', '1', 'visibility', 'الواجهة الرئيسية', 'bool', ''],
        ['show_topbar', '1', 'visibility', 'الشريط العلوي', 'bool', ''],
        ['show_langs', '1', 'visibility', 'مبدّل اللغات', 'bool', ''],
        ['show_stats', '1', 'visibility', 'أرقام الثقة', 'bool', ''],
        ['show_about', '1', 'visibility', 'عن الوكالة', 'bool', ''],
        ['show_packages', '1', 'visibility', 'البرامج', 'bool', ''],
        ['show_services', '1', 'visibility', 'الخدمات', 'bool', ''],
        ['show_calculator', '1', 'visibility', 'الحاسبة', 'bool', ''],
        ['show_cities', '1', 'visibility', 'مكة والمدينة', 'bool', ''],
        ['show_guide', '1', 'visibility', 'دليل المناسك', 'bool', ''],
        ['show_testimonials', '1', 'visibility', 'آراء العملاء', 'bool', ''],
        ['show_newsletter', '1', 'visibility', 'النشرة البريدية', 'bool', ''],
        ['show_whatsapp', '1', 'visibility', 'زر واتساب العائم', 'bool', ''],
        ['password_changed', '1', 'system', 'تم تغيير كلمة المرور', 'bool', ''],
    ];

    $pdo->beginTransaction();

    $settingInsert = $pdo->prepare('INSERT INTO settings (skey, svalue, sgroup, label, field_type, hint, sorder) VALUES (?, ?, ?, ?, ?, ?, ?)');
    foreach ($settings as $index => $setting) {
        $settingInsert->execute([
            $setting[0], $setting[1], $setting[2], $setting[3], $setting[4], $setting[5], ($index + 1) * 10,
        ]);
    }

    $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)')
        ->execute([$adminUser, password_hash($adminPass, PASSWORD_DEFAULT)]);

    $nav = $pdo->prepare('INSERT INTO nav_links (label_ar, label_en, label_fr, href, sort_order) VALUES (?, ?, ?, ?, ?)');
    $navRows = [
        ['الرئيسية', 'Home', 'Accueil', '#hero', 10],
        ['عن الوكالة', 'About Us', 'À Propos', '#about', 20],
        ['برامج الرحلات', 'Tour Packages', 'Nos Offres', '#packages', 30],
        ['خدماتنا المتميزة', 'VIP Services', 'Services VIP', '#services', 40],
        ['حاسبة التكلفة', 'Cost Estimator', 'Calculateur', '#calculator', 50],
        ['مكة والمدينة', 'Holy Cities', 'Villes Saintes', '#cities', 60],
        ['دليل المناسك', 'Pilgrim Guide', 'Guide du Pèlerin', '#guide', 70],
    ];
    foreach ($navRows as $row) {
        $nav->execute($row);
    }

    $hero = $pdo->prepare('INSERT INTO hero_slides (image_url, sort_order) VALUES (?, ?)');
    foreach ([
        ['https://images.unsplash.com/photo-1591604466107-ec97de577aff?auto=format&fit=crop&w=1920&q=80', 10],
        ['https://images.unsplash.com/photo-1565552645632-d725f8bfc19a?auto=format&fit=crop&w=1920&q=80', 20],
        ['https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=1920&q=80', 30],
    ] as $row) {
        $hero->execute($row);
    }

    $stat = $pdo->prepare('INSERT INTO stats (value_text, label, sort_order) VALUES (?, ?, ?)');
    foreach ([
        ['+18,500', 'معتمر وحاج خدمناهم في جود فلاي', 10],
        ['100%', 'عقود رسمية وفنادق معتمدة', 20],
        ['24 / 7', 'مرافقة ميدانية وطبية شاملة', 30],
        ['4.9 / 5', 'معدل رضا المعتمرين والضيوف', 40],
    ] as $row) {
        $stat->execute($row);
    }

    $point = $pdo->prepare('INSERT INTO about_points (icon, text, sort_order) VALUES (?, ?, ?)');
    $point->execute(['fa-solid fa-certificate', 'اعتماد رسمي من وزارة الحج والعمرة', 10]);
    $point->execute(['fa-solid fa-handshake', 'شراكات مباشرة مع كبرى سلاسل الفنادق', 20]);

    $category = $pdo->prepare('INSERT INTO package_categories (slug, label, sort_order) VALUES (?, ?, ?)');
    foreach ([
        ['ramadan', 'عمرة رمضان', 10],
        ['hajj', 'الحج الملكي', 20],
        ['custom', 'البرامج المخصصة', 30],
    ] as $row) {
        $category->execute($row);
    }

    $package = $pdo->prepare('INSERT INTO packages (title, category, category_badge, hotel, hotel_class, distance, duration_days, description, price, image, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $feature = $pdo->prepare('INSERT INTO package_features (package_id, feature_text, sort_order) VALUES (?, ?, ?)');
    $packageRows = [
        [
            'Good Fly - عمرة العشر الأواخر من رمضان', 'ramadan', 'عمرة رمضان', 'دار التوحيد إنتركونتيننتال', '5star-view',
            'مباشرة أمام باب الملك فهد', '10',
            'رحلة فاخرة تنظمها Good Fly بالكامل وتشمل الطيران والإقامة في أجنحة مطلة ومباشرة على صحن المطاف.',
            '3,950 $', 'https://images.unsplash.com/photo-1591604466107-ec97de577aff?auto=format&fit=crop&w=800&q=80', 10,
            [
                'طيران مباشر وحقائب إضافية',
                'جناح مطل مباشرة على الكعبة',
                'بوفيه مفتوح كامل للإفطار والسحور',
                'مرافق خاص وتأطير ديني 24 ساعة',
            ],
        ],
        [
            'Good Fly - برنامج الحج الملكي VIP', 'hajj', 'الحج الملكي', 'فيرمونت أبراج الساعة', '5star-first',
            'صف أول - مخيمات مجوفة في منى', '14',
            'خدمة متكاملة للحجاج تضمن أداء العبادة براحة تامة في مخيمات فاخرة مكيفة ومجهزة بالكامل في المشاعر المقدسة.',
            '11,800 $', 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=800&q=80', 20,
            [
                'مخيمات VIP خاصة بمشعر منى وعرفات',
                'تنقل بقطار المشاعر وحافلات خاصة',
                'فريق طبي وإرشادي متواجد باستمرار',
                'هدايا ومستلزمات الحج من Good Fly',
            ],
        ],
        [
            'Good Fly - عمرة رجب وشعبان VIP', 'custom', 'عائلات وVIP', 'رافلز مكة / الأناضول', '5star-view',
            'إطلالة مباشرة على المسجد الحرام', '7',
            'رحلة عائلية هادئة ومنظمة للاستعداد لرمضان تشمل مكة والمدينة وتنقلات قطار الحرمين السريع.',
            '2,550 $', 'https://images.unsplash.com/photo-1565552645632-d725f8bfc19a?auto=format&fit=crop&w=800&q=80', 30,
            [
                'إقامة 5 نجوم في مكة والمدينة',
                'قطار الحرمين السريع - الدرجة الأولى',
                'جولات تاريخية ومعالم إيمانية',
                'استقبال وتوديع خاص في المطارات',
            ],
        ],
    ];
    foreach ($packageRows as $row) {
        $features = array_pop($row);
        $package->execute($row);
        $packageId = (int) $pdo->lastInsertId();
        foreach ($features as $featureIndex => $text) {
            $feature->execute([$packageId, $text, ($featureIndex + 1) * 10]);
        }
    }

    $service = $pdo->prepare('INSERT INTO services (icon, title, description, sort_order) VALUES (?, ?, ?, ?)');
    foreach ([
        ['fa-solid fa-plane', 'طيران منتظم ومشرّف', 'حجوزات طيران عبر أرقى خطوط الطيران العالمية والمحلية مع مراعاة المواعيد المريحة وحقائب الأمتعة الإضافية.', 10],
        ['fa-solid fa-hotel', 'فنادق صف أول مطلة', 'إقامة راقية في فنادق أبراج الساعة وجبل عمر والمنطقة الشمالية بالمدينة المنورة لضمان وصول سريع للمسجدين.', 20],
        ['fa-solid fa-book-quran', 'تأطير وإرشاد فقهي', 'مرشدون ودعاة معتمدون لتقديم الشرح والتوجيه اليومي والإجابة عن استفسارات المعتمرين والحجاج.', 30],
        ['fa-solid fa-bus', 'تنقلات فاخرة بسيارات خاصة', 'حافلات سيارات VIP ووسائل نقل حديثة مكيفة لنقل الضيوف بين المطار والحرمين والمشاعر المقدسة.', 40],
        ['fa-solid fa-passport', 'استخراج التأشيرات والتصاريح', 'إنهاء إجراءات التأشيرات والتصاريح الإلكترونية الخاصة بدخول الروضة الشريفة وأداء العمرة بسهولة.', 50],
        ['fa-solid fa-headset', 'عناية خاصة واستجابة 24 ساعة', 'فريق دعم ميداني يتواجد معكم طوال أيام الرحلة للتكفل بأي طلبات خاصة أو حالة طارئة.', 60],
    ] as $row) {
        $service->execute($row);
    }

    $tier = $pdo->prepare('INSERT INTO calc_tiers (tier_key, label, price_per_day, sort_order) VALUES (?, ?, ?, ?)');
    $tier->execute(['vip', 'جناح ملكي VIP (5 نجوم)', 520, 10]);
    $tier->execute(['premium', 'غرفة مطلة على الحرم (5 نجوم)', 300, 20]);
    $tier->execute(['executive', 'صف أول قريب (4-5 نجوم)', 240, 30]);

    $city = $pdo->prepare('INSERT INTO cities (slug, tab_label, heading, image, caption, sort_order) VALUES (?, ?, ?, ?, ?, ?)');
    $cityFeature = $pdo->prepare('INSERT INTO city_features (city_id, icon, text, sort_order) VALUES (?, ?, ?, ?)');
    $city->execute(['makkah', 'مكة المكرمة', 'مميزات الإقامة مع Good Fly بمكة:', 'https://images.unsplash.com/photo-1591604466107-ec97de577aff?auto=format&fit=crop&w=1000&q=80', 'المسجد الحرام - مكة المكرمة', 10]);
    $makkahId = (int) $pdo->lastInsertId();
    foreach ([
        ['fa-solid fa-kaaba', 'فنادق الخمس نجوم المطلة مباشرة على صحن الكعبة'],
        ['fa-solid fa-person-walking', 'وصول مباشر للمصليات بدون الحاجة للمواصلات'],
        ['fa-solid fa-map-location-dot', 'زيارات ميدانية للمشاعر المقدسة والمعالم الإيمانية'],
    ] as $index => $row) {
        $cityFeature->execute([$makkahId, $row[0], $row[1], ($index + 1) * 10]);
    }
    $city->execute(['madinah', 'المدينة المنورة', 'مميزات الإقامة مع Good Fly بالمدينة:', 'https://images.unsplash.com/photo-1565552645632-d725f8bfc19a?auto=format&fit=crop&w=1000&q=80', 'المسجد النبوي الشريف - المدينة المنورة', 20]);
    $madinahId = (int) $pdo->lastInsertId();
    foreach ([
        ['fa-solid fa-mosque', 'فنادق المنطقة المركزية الشمالية المحاذية للساحة النبوية'],
        ['fa-solid fa-hand-holding-heart', 'تنظيم تصاريح دخول الروضة الشريفة للرجال والنساء'],
        ['fa-solid fa-tree', 'جولات إيمانية لمسجد قباء وجبل أحد والمساجد السبعة'],
    ] as $index => $row) {
        $cityFeature->execute([$madinahId, $row[0], $row[1], ($index + 1) * 10]);
    }

    $guide = $pdo->prepare('INSERT INTO guides (icon, title, body, sort_order) VALUES (?, ?, ?, ?)');
    $guide->execute(['fa-solid fa-kaaba', 'أحكام وصفة مناسك العمرة الصحيحة', 'تبدأ بالإحرام من الميقات المحدد، والتلبية حتى الوصول للبيت الحرام، ثم الطواف 7 أشواط، وصلاة ركعتين خلف المقام، والسعي بين الصفا والمروة 7 أشواط، والتحلل بالحلق أو التقصير.', 10]);
    $guide->execute(['fa-solid fa-suitcase-rolling', 'الحقيبة الشخصية والتجهيزات الموصى بها', 'ننصح بملابس إحرام قطنية خفيفة، أحذية مريحة مخصصة للمشي، وحقيبة صغيرة للوثائق. تقدم وكالة Good Fly لجميع ضيوفها حقائب هدايا ومظلات شمسية واقية.', 20]);
    $guide->execute(['fa-solid fa-shield-virus', 'الإرشادات الصحية والتصاريح', 'حرصاً على سلامتكم، يتولى فريق Good Fly مراجعة الاشتراطات الصحية والتطعيمات واستخراج تصاريح الروضة الشريفة مسبقاً قبل وصولكم.', 30]);

    $testimonial = $pdo->prepare('INSERT INTO testimonials (quote, initials, name, trip_label, stars, sort_order) VALUES (?, ?, ?, ?, ?, ?)');
    $testimonial->execute(['تعاملنا مع وكالة Good Fly Travel في عمرة رمضان، وكانت الرحلة منظمة بأعلى مستوى من الاحترافية، بدءاً من الاستقبال وحتى العودة.', 'أ.م', 'أحمد المحمدي', 'عمرة VIP رمضان 1445هـ', 5, 10]);
    $testimonial->execute(['خدمة كبار السن مع جود فلاي كانت متميزة للغاية، تم توفير النقل الخاص والكرسي المتحرك والمرافق دون أي تأخير.', 'د.س', 'د. سارة المنصوري', 'رحلة الحج الملكي', 5, 20]);
    $testimonial->execute(['الفنادق القريبة والمطلة أتاحت لنا أداء الصلوات في الحرم بسهولة، شكراً لفريق جود فلاي على حرصهم ومصداقيتهم.', 'م.خ', 'م. خالد البلوشي', 'عمرة عائلية مخصصة', 5, 30]);

    $footer = $pdo->prepare('INSERT INTO footer_links (group_key, label, href, sort_order) VALUES (?, ?, ?, ?)');
    foreach ([
        ['quick', 'عن Good Fly', '#about', 10],
        ['quick', 'برامج العمرة والحج', '#packages', 20],
        ['quick', 'خدمات VIP الخاصة', '#services', 30],
        ['quick', 'حاسبة التكلفة الفورية', '#calculator', 40],
        ['quick', 'دليل المناسك والإرشادات', '#guide', 50],
        ['legal', 'سياسة الخصوصية', '#', 10],
        ['legal', 'الشروط والأحكام', '#', 20],
        ['legal', 'تراخيص وزارة الحج', '#', 30],
    ] as $row) {
        $footer->execute($row);
    }

    $filter = $pdo->prepare('INSERT INTO filter_options (group_key, option_value, label, sort_order) VALUES (?, ?, ?, ?)');
    foreach ([
        ['trip_type', 'all', 'جميع برامج Good Fly', 10],
        ['trip_type', 'ramadan', 'عمرة رمضان المبارك', 20],
        ['trip_type', 'rajab', 'عمرة رجب وشعبان', 30],
        ['trip_type', 'hajj', 'الحج الملكي الفاخر', 40],
        ['trip_type', 'custom', 'رحلة عائلية مخصصة', 50],
        ['hotel', 'all', 'جميع الدرجات', 10],
        ['hotel', '5star-view', '5 نجوم - إطلالة على الحرم', 20],
        ['hotel', '5star-first', '5 نجوم - صف أول', 30],
        ['hotel', 'standard', '4-5 نجوم - متميز', 40],
        ['duration', 'all', 'أي مدة', 10],
        ['duration', '7', '7 أيام (أسسبوع)', 20],
        ['duration', '10', '10 أيام', 30],
        ['duration', '14', '14 يوم (أسبوعين)', 40],
        ['booking_type', 'عمرة رمضان', 'عمرة رمضان المبارك', 10],
        ['booking_type', 'عمرة رجب وشعبان', 'عمرة رجب وشعبان', 20],
        ['booking_type', 'الحج الملكي', 'الحج الملكي الفاخر', 30],
        ['booking_type', 'برنامج خاص', 'رحلة عائلية مخصصة', 40],
    ] as $row) {
        $filter->execute($row);
    }

    $translation = $pdo->prepare('INSERT INTO translations (lang_code, tkey, tvalue, sort_order) VALUES (?, ?, ?, ?)');
    $translation->execute(['ar', 'btn_consultation', 'حجز استشارة / طلب رحلة', 10]);
    $translation->execute(['en', 'btn_consultation', 'Book Consultation', 20]);
    $translation->execute(['fr', 'btn_consultation', 'Réserver Un Expert', 30]);

    $pdo->commit();

    $uploads = __DIR__ . '/uploads';
    if (!is_dir($uploads)) {
        mkdir($uploads, 0775, true);
    }

    require_once __DIR__ . '/includes/functions.php';
    require_once __DIR__ . '/includes/i18n.php';
    require_once __DIR__ . '/includes/migrations.php';
    require_once __DIR__ . '/includes/design.php';
    run_migrations();

    if ($isCli) {
        echo 'تم التثبيت بنجاح.' . PHP_EOL;
        echo 'المستخدم: ' . $adminUser . PHP_EOL;
        if ($generatedPassword) {
            echo 'كلمة المرور المولّدة (احفظها الآن، لن تظهر مجدداً): ' . $adminPass . PHP_EOL;
        }
        exit(0);
    }

    install_page('تم تثبيت Good Fly', '<p>تم إنشاء قاعدة البيانات وحساب الإدارة <strong>' . htmlspecialchars($adminUser, ENT_QUOTES, 'UTF-8') . '</strong>.</p><p>احذف ملف install.php من الخادم الآن.</p><p><a href="index.php">فتح الموقع</a> — <a href="admin/login.php">دخول الإدارة</a></p>');
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (!$isCli) {
        install_page('تعذر التثبيت', '<p>تحقق من بيانات قاعدة البيانات في config.local.php.</p><pre style="white-space:pre-wrap">' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</pre>', 500);
    } else {
        fwrite(STDERR, $e->getMessage() . PHP_EOL);
    }
    exit(1);
}
