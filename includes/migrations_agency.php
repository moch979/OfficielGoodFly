<?php

/** Gathers every agency detail into one "agency" group and removes duplicated fields. */
function migrate_agency_info(PDO $pdo): void
{
    $value = static function (string $key) use ($pdo): string {
        $statement = $pdo->prepare('SELECT svalue FROM settings WHERE skey = ?');
        $statement->execute([$key]);
        return (string) $statement->fetchColumn();
    };
    foreach ([['license_number', 'footer_license_number', 'GF-8842/2026'], ['phone_display', 'footer_phone', '+212 5 22 00 00 00']] as [$main, $duplicate, $placeholder]) {
        $kept = $value($main);
        $other = $value($duplicate);
        if (($kept === '' || $kept === $placeholder) && $other !== '' && $other !== $placeholder) {
            $pdo->prepare('UPDATE settings SET svalue = ? WHERE skey = ?')->execute([$other, $main]);
        }
    }
    $pdo->exec("UPDATE settings SET sgroup = 'system' WHERE skey IN ('footer_license_number', 'footer_phone')");

    $brandName = $value('about_card_title') ?: 'Good Fly Travel';
    mig_setting($pdo, 'agency_name', $brandName, 'agency', 'اسم الوكالة', 'text', 'يظهر في عنوان الصفحات، وروابط المشاركة، وبريد التنبيهات', 10, true);
    mig_setting($pdo, 'agency_ice', '', 'agency', 'المعرّف الموحد للمقاولة (ICE)', 'text', '15 رقماً. يظهر في التذييل إذا أدخلته', 40);
    mig_setting($pdo, 'agency_if', '', 'agency', 'التعريف الضريبي (IF)', 'text', 'اختياري', 50);
    mig_setting($pdo, 'agency_phone2', '', 'agency', 'هاتف إضافي', 'text', 'اختياري، مثل الجوال أو فرع آخر', 70);
    mig_setting($pdo, 'agency_map_url', '', 'agency', 'رابط الموقع على خرائط Google', 'text', 'من خرائط Google: مشاركة ← نسخ الرابط. يظهر رابط «عرض على الخريطة» تحت العنوان', 120);
    mig_setting($pdo, 'agency_hours', '', 'agency', 'ساعات العمل', 'textarea', 'مثل: الإثنين – السبت: 9:00 – 19:00', 130, true);
    mig_setting($pdo, 'social_facebook', '', 'agency', 'فيسبوك', 'text', 'الرابط الكامل يبدأ بـ https://', 140);
    mig_setting($pdo, 'social_instagram', '', 'agency', 'إنستغرام', 'text', '', 150);
    mig_setting($pdo, 'social_tiktok', '', 'agency', 'تيك توك', 'text', '', 160);
    mig_setting($pdo, 'social_youtube', '', 'agency', 'يوتيوب', 'text', '', 170);

    $moves = [
        'license_number' => ['رقم الترخيص السياحي', '', 20],
        'footer_cr_number' => ['السجل التجاري (RC)', '', 30],
        'phone_display' => ['الهاتف الرئيسي', 'يظهر في الشريط العلوي والتذييل كما تكتبه', 60],
        'wa_number' => ['رقم واتساب', 'بالصيغة الدولية بدون + أو مسافات، مثل 212612345678. تصله طلبات الحجز والاستفسارات', 80],
        'top_email' => ['البريد العام', 'يظهر في الشريط العلوي', 90],
        'footer_email' => ['بريد الحجوزات', 'يظهر في التذييل وبيانات محركات البحث', 100],
        'footer_address' => ['العنوان', 'يظهر في التذييل', 110],
    ];
    $move = $pdo->prepare("UPDATE settings SET sgroup = 'agency', label = ?, hint = ?, sorder = ? WHERE skey = ?");
    foreach ($moves as $key => [$label, $hint, $order]) {
        $move->execute([$label, $hint, $order, $key]);
    }
}

function migrate_google_reviews(PDO $pdo): void
{
    mig_setting($pdo, 'google_reviews_on', '0', 'testimonials', 'عرض تقييم Google الحقيقي', 'bool', 'عند التفعيل تظهر فوق الآراء بطاقة فيها تقييم الوكالة على Google وعدد التقييمات وزر «اكتب تقييمك على Google».', 20);
    mig_setting($pdo, 'google_place_id', 'ChIJf1ZU0EBrpw0RXqlpdm1_3bg', 'testimonials', 'معرّف المكان على Google (Place ID)', 'text', 'يبدأ غالباً بـ ChIJ. ابحث عن اسم وكالتك في: developers.google.com/maps/documentation/places/web-service/place-id', 21);
    mig_setting($pdo, 'google_api_key', '', 'testimonials', 'مفتاح Google Places API', 'password', 'من Google Cloud Console: فعّل «Places API (New)» وأنشئ مفتاحاً. قيّده بعنوان IP الخادم وبـ Places API فقط. يُحفظ ولا يظهر للزوار.', 22);
}

function migrate_google_reviews_cards(PDO $pdo): void
{
    mig_setting($pdo, 'google_reviews_cards', '0', 'testimonials', 'عرض نصوص آراء Google أيضاً', 'bool', 'عند الإيقاف تظهر تحت بطاقة التقييم الآراء التي تضيفها بنفسك من «آراء العملاء». عند التفعيل تحل محلها آخر 5 آراء تختارها Google وتظهر كما هي، ولا يمكن إخفاء أي منها.', 23);
    $pdo->prepare("UPDATE settings SET label = ?, hint = ? WHERE skey = 'google_reviews_on'")->execute([
        'عرض تقييم Google الحقيقي',
        'عند التفعيل تظهر فوق الآراء بطاقة فيها تقييم الوكالة على Google وعدد التقييمات وزر «اكتب تقييمك على Google».',
    ]);
}
