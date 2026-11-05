<?php

function collections(): array
{
    return [
        'nav' => [
            'title' => 'قائمة الموقع',
            'intro' => 'روابط الهيدر. حقل الرابط يكون مثل #packages ليصل إلى القسم، ويمكن كتابة العربية والإنجليزية والفرنسية.',
            'table' => 'nav_links',
            'list' => [
                'label_ar' => 'العربية',
                'label_en' => 'English',
                'href' => 'الرابط',
            ],
            'fields' => [
                'label_ar' => ['label' => 'النص بالعربية', 'type' => 'text', 'required' => true],
                'label_en' => ['label' => 'النص بالإنجليزية', 'type' => 'text', 'required' => true],
                'label_fr' => ['label' => 'النص بالفرنسية', 'type' => 'text', 'required' => true],
                'href' => ['label' => 'الرابط', 'type' => 'text', 'required' => true],
                'sort_order' => ['label' => 'الترتيب', 'type' => 'number'],
                'is_active' => ['label' => 'ظاهر في الموقع', 'type' => 'checkbox'],
            ],
        ],
        'hero' => [
            'title' => 'صور الواجهة الرئيسية',
            'intro' => 'صور تتبدل في أعلى الصفحة. يمكن لصق رابط صورة أو رفع ملف.',
            'table' => 'hero_slides',
            'list' => [
                'image_url' => 'الصورة',
            ],
            'fields' => [
                'image_url' => ['label' => 'الصورة', 'type' => 'image', 'required' => true],
                'sort_order' => ['label' => 'الترتيب', 'type' => 'number'],
                'is_active' => ['label' => 'ظاهرة', 'type' => 'checkbox'],
            ],
        ],
        'stats' => [
            'title' => 'أرقام الثقة',
            'intro' => 'الأرقام الأربعة تحت الواجهة، مثل عدد المعتمرين ونسبة الرضا.',
            'table' => 'stats',
            'list' => [
                'value_text' => 'الرقم',
                'label' => 'الوصف',
            ],
            'fields' => [
                'value_text' => ['label' => 'الرقم أو القيمة', 'type' => 'text', 'required' => true],
                'label' => ['label' => 'الوصف', 'type' => 'text', 'required' => true],
                'sort_order' => ['label' => 'الترتيب', 'type' => 'number'],
                'is_active' => ['label' => 'ظاهر', 'type' => 'checkbox'],
            ],
        ],
        'about' => [
            'title' => 'نقاط عن الوكالة',
            'intro' => 'المربعات الصغيرة داخل قسم عن الوكالة، مثل الاعتماد والشراكات.',
            'table' => 'about_points',
            'list' => [
                'text' => 'النص',
                'icon' => 'الأيقونة',
            ],
            'fields' => [
                'icon' => ['label' => 'أيقونة Font Awesome', 'type' => 'text', 'required' => true, 'hint' => 'مثال: fa-solid fa-certificate'],
                'text' => ['label' => 'النص', 'type' => 'text', 'required' => true],
                'sort_order' => ['label' => 'الترتيب', 'type' => 'number'],
                'is_active' => ['label' => 'ظاهرة', 'type' => 'checkbox'],
            ],
        ],
        'categories' => [
            'title' => 'تصنيفات البرامج',
            'intro' => 'تبويبات قسم البرامج. المعرّف اللاتيني يُستخدم للربط مع البرنامج وخيارات البحث، مثل ramadan أو hajj.',
            'table' => 'package_categories',
            'list' => [
                'label' => 'الاسم',
                'slug' => 'المعرّف',
            ],
            'fields' => [
                'label' => ['label' => 'اسم التصنيف', 'type' => 'text', 'required' => true],
                'slug' => ['label' => 'المعرّف اللاتيني', 'type' => 'text', 'required' => true, 'hint' => 'حروف إنجليزية صغيرة بلا مسافات، مثل ramadan'],
                'sort_order' => ['label' => 'الترتيب', 'type' => 'number'],
                'is_active' => ['label' => 'ظاهر', 'type' => 'checkbox'],
            ],
        ],
        'filters' => [
            'title' => 'خيارات البحث والحجز',
            'intro' => 'قوائم نوع الرحلة والفندق والمدة في شريط البحث، وأنواع الرحلة داخل نموذج الحجز.',
            'table' => 'filter_options',
            'list' => [
                'group_key' => 'المجموعة',
                'label' => 'النص',
                'option_value' => 'القيمة',
            ],
            'fields' => [
                'group_key' => [
                    'label' => 'المجموعة',
                    'type' => 'select',
                    'required' => true,
                    'options' => [
                        'trip_type' => 'نوع الرحلة في البحث',
                        'hotel' => 'فئة الفندق في البحث',
                        'duration' => 'مدة الإقامة في البحث',
                        'booking_type' => 'نوع الرحلة في نموذج الحجز',
                    ],
                ],
                'label' => ['label' => 'النص الظاهر', 'type' => 'text', 'required' => true],
                'option_value' => ['label' => 'القيمة الداخلية', 'type' => 'text', 'required' => true, 'hint' => 'للبند «الكل» استخدم all. لفندق البرنامج اكتب نفس القيمة في حقل فئة الفندق داخل البرنامج.'],
                'sort_order' => ['label' => 'الترتيب', 'type' => 'number'],
                'is_active' => ['label' => 'ظاهر', 'type' => 'checkbox'],
            ],
        ],
        'services' => [
            'title' => 'الخدمات',
            'intro' => 'بطاقات قسم الخدمات المتميزة.',
            'table' => 'services',
            'list' => [
                'title' => 'الخدمة',
            ],
            'fields' => [
                'icon' => ['label' => 'الأيقونة', 'type' => 'text', 'required' => true, 'hint' => 'مثال: fa-solid fa-plane'],
                'title' => ['label' => 'العنوان', 'type' => 'text', 'required' => true],
                'description' => ['label' => 'الوصف', 'type' => 'textarea', 'required' => true],
                'sort_order' => ['label' => 'الترتيب', 'type' => 'number'],
                'is_active' => ['label' => 'ظاهرة', 'type' => 'checkbox'],
            ],
        ],
        'tiers' => [
            'title' => 'فئات حاسبة التكلفة',
            'intro' => 'سعر اليوم للشخص الواحد لكل فئة. المعرّف يطابق الفئة الافتراضية في الإعدادات، مثل vip.',
            'table' => 'calc_tiers',
            'list' => [
                'label' => 'الفئة',
                'tier_key' => 'المعرّف',
                'price_per_day' => 'سعر اليوم',
            ],
            'fields' => [
                'label' => ['label' => 'اسم الفئة', 'type' => 'text', 'required' => true],
                'tier_key' => ['label' => 'المعرّف', 'type' => 'text', 'required' => true, 'hint' => 'مثال: vip أو premium أو executive'],
                'price_per_day' => ['label' => 'سعر اليوم للشخص', 'type' => 'number', 'required' => true],
                'sort_order' => ['label' => 'الترتيب', 'type' => 'number'],
                'is_active' => ['label' => 'ظاهرة', 'type' => 'checkbox'],
            ],
        ],
        'guides' => [
            'title' => 'دليل المناسك',
            'intro' => 'الأسئلة والأجوبة في قسم إرشادات الرحلة.',
            'table' => 'guides',
            'list' => [
                'title' => 'العنوان',
            ],
            'fields' => [
                'icon' => ['label' => 'الأيقونة', 'type' => 'text', 'required' => true, 'hint' => 'مثال: fa-solid fa-kaaba'],
                'title' => ['label' => 'العنوان', 'type' => 'text', 'required' => true],
                'body' => ['label' => 'النص', 'type' => 'textarea', 'required' => true],
                'sort_order' => ['label' => 'الترتيب', 'type' => 'number'],
                'is_active' => ['label' => 'ظاهر', 'type' => 'checkbox'],
            ],
        ],
        'testimonials' => [
            'title' => 'آراء العملاء',
            'intro' => 'بطاقات الانطباعات أسفل الموقع.',
            'table' => 'testimonials',
            'list' => [
                'name' => 'الاسم',
                'trip_label' => 'الرحلة',
            ],
            'fields' => [
                'quote' => ['label' => 'الرأي', 'type' => 'textarea', 'required' => true],
                'initials' => ['label' => 'الحروف المختصرة', 'type' => 'text', 'required' => true],
                'name' => ['label' => 'الاسم', 'type' => 'text', 'required' => true],
                'trip_label' => ['label' => 'وصف الرحلة', 'type' => 'text', 'required' => true],
                'stars' => ['label' => 'عدد النجوم من 5', 'type' => 'number', 'required' => true],
                'sort_order' => ['label' => 'الترتيب', 'type' => 'number'],
                'is_active' => ['label' => 'ظاهر', 'type' => 'checkbox'],
            ],
        ],
        'footer' => [
            'title' => 'روابط التذييل',
            'intro' => 'روابط عمود الروابط السريعة والروابط القانونية أسفل الصفحة.',
            'table' => 'footer_links',
            'list' => [
                'group_key' => 'المجموعة',
                'label' => 'النص',
                'href' => 'الرابط',
            ],
            'fields' => [
                'group_key' => [
                    'label' => 'المجموعة',
                    'type' => 'select',
                    'required' => true,
                    'options' => [
                        'quick' => 'روابط سريعة',
                        'legal' => 'روابط قانونية',
                    ],
                ],
                'label' => ['label' => 'النص', 'type' => 'text', 'required' => true],
                'href' => ['label' => 'الرابط', 'type' => 'text', 'required' => true],
                'sort_order' => ['label' => 'الترتيب', 'type' => 'number'],
                'is_active' => ['label' => 'ظاهر', 'type' => 'checkbox'],
            ],
        ],
        'i18n' => [
            'title' => 'ترجمة زر الحجز',
            'intro' => 'نص زر «حجز استشارة» في الهيدر عند تغيير اللغة. مفتاح الزر هو btn_consultation.',
            'table' => 'translations',
            'order' => 'lang_code ASC, sort_order ASC, id ASC',
            'list' => [
                'lang_code' => 'اللغة',
                'tkey' => 'المفتاح',
                'tvalue' => 'النص',
            ],
            'fields' => [
                'lang_code' => [
                    'label' => 'اللغة',
                    'type' => 'select',
                    'required' => true,
                    'options' => [
                        'ar' => 'العربية',
                        'en' => 'English',
                        'fr' => 'Français',
                    ],
                ],
                'tkey' => ['label' => 'المفتاح', 'type' => 'text', 'required' => true, 'hint' => 'للزر الرئيسي اتركه btn_consultation'],
                'tvalue' => ['label' => 'النص', 'type' => 'text', 'required' => true],
                'sort_order' => ['label' => 'الترتيب', 'type' => 'number'],
            ],
        ],
    ];
}

function setting_groups(): array
{
    return [
        'general' => 'العنوان والوصف',
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
    ];
}
