<?php

/**
 * Admin definitions for the simple repeatable lists. A field marked
 * 'translatable' has `<column>_fr` and `<column>_en` siblings in its table.
 */
function collections(): array
{
    $sort = ['label' => 'الترتيب', 'type' => 'number'];
    $active = ['label' => 'ظاهر في الموقع', 'type' => 'checkbox'];
    $icon = static function (string $example): array {
        return ['label' => 'أيقونة Font Awesome', 'type' => 'text', 'required' => true, 'max' => 80, 'hint' => 'مثال: ' . $example . ' — تصفح الأيقونات في fontawesome.com/icons'];
    };

    return [
        'nav' => [
            'title' => 'قائمة الموقع',
            'intro' => 'روابط رأس الصفحة. الرابط مثل #packages يصل إلى القسم مباشرة.',
            'table' => 'nav_links',
            'list' => ['label_ar' => 'العربية', 'label_fr' => 'Français', 'href' => 'الرابط'],
            'fields' => [
                'label_ar' => ['label' => 'النص بالعربية', 'type' => 'text', 'required' => true, 'max' => 160],
                'label_fr' => ['label' => 'النص بالفرنسية', 'type' => 'text', 'required' => true, 'max' => 160],
                'label_en' => ['label' => 'النص بالإنجليزية', 'type' => 'text', 'required' => true, 'max' => 160],
                'href' => ['label' => 'الرابط', 'type' => 'text', 'required' => true, 'max' => 255],
                'sort_order' => $sort,
                'is_active' => $active,
            ],
        ],
        'hero' => [
            'title' => 'صور الواجهة الرئيسية',
            'intro' => 'صور تتبدل في أعلى الصفحة. يُفضّل عرض 1600 بكسل أو أكثر.',
            'table' => 'hero_slides',
            'list' => ['image_url' => 'الصورة'],
            'fields' => [
                'image_url' => ['label' => 'الصورة', 'type' => 'image', 'required' => true],
                'sort_order' => $sort,
                'is_active' => $active,
            ],
        ],
        'stats' => [
            'title' => 'أرقام الثقة',
            'intro' => 'الأرقام تحت الواجهة. ضع أرقاماً حقيقية يمكنك إثباتها.',
            'table' => 'stats',
            'list' => ['value_text' => 'الرقم', 'label' => 'الوصف'],
            'fields' => [
                'value_text' => ['label' => 'الرقم أو القيمة', 'type' => 'text', 'required' => true, 'max' => 80],
                'label' => ['label' => 'الوصف', 'type' => 'text', 'required' => true, 'translatable' => true, 'max' => 255],
                'sort_order' => $sort,
                'is_active' => $active,
            ],
        ],
        'about' => [
            'title' => 'نقاط عن الوكالة',
            'intro' => 'المربعات الصغيرة داخل قسم «عن الوكالة».',
            'table' => 'about_points',
            'list' => ['text' => 'النص', 'icon' => 'الأيقونة'],
            'fields' => [
                'icon' => $icon('fa-solid fa-certificate'),
                'text' => ['label' => 'النص', 'type' => 'text', 'required' => true, 'translatable' => true, 'max' => 255],
                'sort_order' => $sort,
                'is_active' => $active,
            ],
        ],
        'categories' => [
            'title' => 'تصنيفات البرامج',
            'intro' => 'تبويبات البرامج وخيارات «نوع الرحلة» في البحث. التصنيف لا يظهر للزائر إلا إذا كان فيه برنامج ظاهر.',
            'table' => 'package_categories',
            'list' => ['label' => 'الاسم', 'slug' => 'المعرّف'],
            'fields' => [
                'label' => ['label' => 'اسم التصنيف', 'type' => 'text', 'required' => true, 'translatable' => true, 'max' => 160],
                'slug' => ['label' => 'المعرّف اللاتيني', 'type' => 'text', 'required' => true, 'max' => 60, 'pattern' => '/^[a-z0-9\-]+$/', 'hint' => 'حروف إنجليزية صغيرة وأرقام وشرطة فقط، مثل ramadan'],
                'sort_order' => $sort,
                'is_active' => $active,
            ],
        ],
        'filters' => [
            'title' => 'خيارات البحث والحجز',
            'intro' => 'قوائم فئة الفندق والمدة في شريط البحث، وأنواع الرحلة في نموذج الحجز. «نوع الرحلة» في البحث يُؤخذ تلقائياً من تصنيفات البرامج.',
            'table' => 'filter_options',
            'list' => ['group_key' => 'المجموعة', 'label' => 'النص', 'option_value' => 'القيمة'],
            'fields' => [
                'group_key' => [
                    'label' => 'المجموعة',
                    'type' => 'select',
                    'required' => true,
                    'options' => [
                        'hotel' => 'فئة الفندق في البحث',
                        'duration' => 'مدة الإقامة في البحث',
                        'booking_type' => 'نوع الرحلة في نموذج الحجز',
                    ],
                ],
                'label' => ['label' => 'النص الظاهر', 'type' => 'text', 'required' => true, 'translatable' => true, 'max' => 180],
                'option_value' => ['label' => 'القيمة الداخلية', 'type' => 'text', 'required' => true, 'max' => 80, 'hint' => 'للبند «الكل» استخدم all. لفئة الفندق اكتب نفس القيمة في حقل فئة الفندق داخل البرنامج.'],
                'sort_order' => $sort,
                'is_active' => $active,
            ],
        ],
        'services' => [
            'title' => 'الخدمات',
            'intro' => 'بطاقات قسم الخدمات.',
            'table' => 'services',
            'list' => ['title' => 'الخدمة'],
            'fields' => [
                'icon' => $icon('fa-solid fa-plane'),
                'title' => ['label' => 'العنوان', 'type' => 'text', 'required' => true, 'translatable' => true, 'max' => 255],
                'description' => ['label' => 'الوصف', 'type' => 'textarea', 'required' => true, 'translatable' => true, 'max' => 2000],
                'sort_order' => $sort,
                'is_active' => $active,
            ],
        ],
        'tiers' => [
            'title' => 'فئات حاسبة التكلفة',
            'intro' => 'سعر اليوم للشخص الواحد بالدرهم لكل فئة.',
            'table' => 'calc_tiers',
            'list' => ['label' => 'الفئة', 'tier_key' => 'المعرّف', 'price_per_day' => 'سعر اليوم'],
            'fields' => [
                'label' => ['label' => 'اسم الفئة', 'type' => 'text', 'required' => true, 'translatable' => true, 'max' => 255],
                'tier_key' => ['label' => 'المعرّف', 'type' => 'text', 'required' => true, 'max' => 40, 'pattern' => '/^[a-z0-9\-]+$/', 'hint' => 'مثال: vip — يطابق «الفئة الافتراضية» في إعدادات الحاسبة'],
                'price_per_day' => ['label' => 'سعر اليوم للشخص', 'type' => 'number', 'required' => true, 'min' => 0],
                'sort_order' => $sort,
                'is_active' => $active,
            ],
        ],
        'guides' => [
            'title' => 'دليل المناسك',
            'intro' => 'الأسئلة والأجوبة في قسم الإرشادات.',
            'table' => 'guides',
            'list' => ['title' => 'العنوان'],
            'fields' => [
                'icon' => $icon('fa-solid fa-kaaba'),
                'title' => ['label' => 'العنوان', 'type' => 'text', 'required' => true, 'translatable' => true, 'max' => 255],
                'body' => ['label' => 'النص', 'type' => 'textarea', 'required' => true, 'translatable' => true, 'max' => 5000],
                'sort_order' => $sort,
                'is_active' => $active,
            ],
        ],
        'testimonials' => [
            'title' => 'آراء العملاء',
            'intro' => 'استخدم آراء حقيقية بموافقة أصحابها.',
            'table' => 'testimonials',
            'list' => ['name' => 'الاسم', 'trip_label' => 'الرحلة'],
            'fields' => [
                'quote' => ['label' => 'الرأي', 'type' => 'textarea', 'required' => true, 'translatable' => true, 'max' => 2000],
                'initials' => ['label' => 'الحروف المختصرة', 'type' => 'text', 'required' => true, 'max' => 20],
                'name' => ['label' => 'الاسم', 'type' => 'text', 'required' => true, 'translatable' => true, 'max' => 160],
                'trip_label' => ['label' => 'وصف الرحلة', 'type' => 'text', 'required' => true, 'translatable' => true, 'max' => 180],
                'stars' => ['label' => 'عدد النجوم من 5', 'type' => 'number', 'required' => true, 'min' => 1, 'max_value' => 5],
                'sort_order' => $sort,
                'is_active' => $active,
            ],
        ],
        'footer' => [
            'title' => 'روابط التذييل',
            'intro' => 'الروابط السريعة والقانونية أسفل الصفحة. ضع روابط حقيقية لصفحات الخصوصية والشروط قبل النشر.',
            'table' => 'footer_links',
            'list' => ['group_key' => 'المجموعة', 'label' => 'النص', 'href' => 'الرابط'],
            'fields' => [
                'group_key' => ['label' => 'المجموعة', 'type' => 'select', 'required' => true, 'options' => ['quick' => 'روابط سريعة', 'legal' => 'روابط قانونية']],
                'label' => ['label' => 'النص', 'type' => 'text', 'required' => true, 'translatable' => true, 'max' => 180],
                'href' => ['label' => 'الرابط', 'type' => 'text', 'required' => true, 'max' => 255],
                'sort_order' => $sort,
                'is_active' => $active,
            ],
        ],
    ];
}

function package_fields(): array
{
    $categories = [];
    foreach (rows('SELECT slug, label FROM package_categories ORDER BY sort_order, id') as $category) {
        $categories[$category['slug']] = $category['label'] . ' (' . $category['slug'] . ')';
    }
    return [
        'title' => ['label' => 'عنوان البرنامج', 'type' => 'text', 'required' => true, 'translatable' => true, 'max' => 255],
        'category' => ['label' => 'التصنيف', 'type' => 'select', 'required' => true, 'options' => $categories, 'hint' => 'أضف تصنيفاً جديداً من «تصنيفات البرامج» إن لم تجده.'],
        'category_badge' => ['label' => 'الشارة على الصورة', 'type' => 'text', 'required' => true, 'translatable' => true, 'max' => 160],
        'hotel' => ['label' => 'اسم الفندق', 'type' => 'text', 'required' => true, 'translatable' => true, 'max' => 255],
        'hotel_class' => ['label' => 'فئة الفندق للبحث', 'type' => 'text', 'max' => 80, 'hint' => 'نفس «القيمة الداخلية» لخيار الفندق في البحث، مثل 5star-view'],
        'distance' => ['label' => 'الموقع أو المسافة', 'type' => 'text', 'translatable' => true, 'max' => 255],
        'duration_days' => ['label' => 'مدة الإقامة للبحث', 'type' => 'text', 'max' => 40, 'hint' => 'رقم الأيام فقط، مثل 7 أو 10 أو 14'],
        'description' => ['label' => 'الوصف', 'type' => 'textarea', 'required' => true, 'translatable' => true, 'max' => 3000],
        'price' => ['label' => 'السعر كما يظهر للزائر', 'type' => 'text', 'required' => true, 'translatable' => true, 'max' => 80, 'hint' => 'مثل 38,000 درهم'],
        'image' => ['label' => 'الصورة', 'type' => 'image', 'required' => true],
        'features' => ['label' => 'المزايا، سطر لكل ميزة', 'type' => 'lines', 'translatable' => true, 'hint' => 'في الفرنسية والإنجليزية اكتب الأسطر بنفس ترتيب العربية'],
        'sort_order' => ['label' => 'الترتيب', 'type' => 'number'],
        'is_active' => ['label' => 'ظاهر في الموقع', 'type' => 'checkbox'],
    ];
}

function city_fields(): array
{
    return [
        'slug' => ['label' => 'المعرّف اللاتيني', 'type' => 'text', 'required' => true, 'max' => 40, 'pattern' => '/^[a-z0-9\-]+$/', 'hint' => 'مثل makkah'],
        'tab_label' => ['label' => 'اسم التبويب', 'type' => 'text', 'required' => true, 'translatable' => true, 'max' => 160],
        'heading' => ['label' => 'عنوان المميزات', 'type' => 'text', 'required' => true, 'translatable' => true, 'max' => 255],
        'caption' => ['label' => 'عبارة أسفل الصورة', 'type' => 'text', 'required' => true, 'translatable' => true, 'max' => 255],
        'image' => ['label' => 'الصورة', 'type' => 'image', 'required' => true],
        'feature_icons' => ['label' => 'أيقونات المميزات، سطر لكل ميزة', 'type' => 'lines', 'hint' => 'مثل fa-solid fa-kaaba — بنفس ترتيب أسطر المميزات'],
        'features' => ['label' => 'المميزات، سطر لكل ميزة', 'type' => 'lines', 'translatable' => true],
        'sort_order' => ['label' => 'الترتيب', 'type' => 'number'],
        'is_active' => ['label' => 'ظاهرة في الموقع', 'type' => 'checkbox'],
    ];
}

function setting_groups(): array
{
    return [
        'general' => 'العنوان والمشاركة',
        'topbar' => 'الشريط العلوي',
        'brand' => 'الشعار',
        'colors' => 'ألوان الموقع',
        'hero' => 'الواجهة والبحث',
        'about' => 'عن الوكالة',
        'packages' => 'قسم البرامج',
        'services' => 'قسم الخدمات',
        'calculator' => 'حاسبة التكلفة',
        'cities' => 'مكة والمدينة',
        'guide' => 'دليل المناسك',
        'testimonials' => 'آراء العملاء',
        'footer' => 'التذييل',
        'modal' => 'الحجز وواتساب',
        'visibility' => 'إظهار الأقسام',
        'design' => 'التصميم العام',
        'notifications' => 'تنبيهات البريد',
    ];
}
