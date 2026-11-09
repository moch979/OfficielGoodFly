<?php

function migrate_program_pages(PDO $pdo): void
{
    mig_add_column($pdo, 'packages', 'slug', 'VARCHAR(120) NULL AFTER id');
    mig_add_column($pdo, 'packages', 'gallery', 'TEXT NULL');
    foreach (['itinerary', 'includes', 'excludes'] as $column) {
        mig_add_column($pdo, 'packages', $column, 'TEXT NULL');
        foreach (extra_languages() as $lang) {
            mig_add_column($pdo, 'packages', $column . '_' . $lang, 'TEXT NULL');
        }
    }
    foreach (['price_double', 'price_triple', 'price_quad'] as $column) {
        mig_add_column($pdo, 'packages', $column, 'INT NOT NULL DEFAULT 0');
    }

    $pdo->exec('CREATE TABLE IF NOT EXISTS package_departures (
        id INT AUTO_INCREMENT PRIMARY KEY,
        package_id INT NOT NULL,
        depart_date DATE NOT NULL,
        return_date DATE NULL,
        seats_left INT NOT NULL DEFAULT 0,
        INDEX idx_departure_package (package_id, depart_date),
        CONSTRAINT fk_departure_package FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    mig_add_column($pdo, 'bookings', 'package_id', 'INT NULL');
    mig_add_column($pdo, 'bookings', 'departure_id', 'INT NULL');
    mig_add_column($pdo, 'bookings', 'departure_date', "VARCHAR(40) NOT NULL DEFAULT ''");
    mig_add_column($pdo, 'bookings', 'room_type', "VARCHAR(20) NOT NULL DEFAULT ''");
    mig_add_column($pdo, 'bookings', 'seats_deducted', 'TINYINT(1) NOT NULL DEFAULT 0');

    $used = [];
    foreach ($pdo->query('SELECT id, title, title_en, title_fr, slug FROM packages ORDER BY id')->fetchAll() as $row) {
        if (trim((string) $row['slug']) !== '') {
            $used[] = $row['slug'];
            continue;
        }
        $base = slugify((string) ($row['title_en'] ?: $row['title_fr'] ?: '')) ?: 'program-' . $row['id'];
        $slug = $base;
        for ($i = 2; in_array($slug, $used, true); $i++) {
            $slug = $base . '-' . $i;
        }
        $used[] = $slug;
        $pdo->prepare('UPDATE packages SET slug = ? WHERE id = ?')->execute([$slug, $row['id']]);
    }
    $hasIndex = (int) $pdo->query("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'packages' AND INDEX_NAME = 'uniq_package_slug'")->fetchColumn();
    if (!$hasIndex) {
        $pdo->exec('CREATE UNIQUE INDEX uniq_package_slug ON packages (slug)');
    }

    $texts = [
        ['pkg_details_button', 'التفاصيل', 'Détails', 'Details', 'زر صفحة البرنامج في البطاقة'],
        ['pkg_from_label', 'ابتداءً من', 'À partir de', 'From', 'عبارة قبل أقل سعر'],
        ['currency_label', 'درهم', 'MAD', 'MAD', 'العملة بعد الأسعار'],
        ['prog_back', 'كل البرامج', 'Tous les programmes', 'All packages', 'رابط العودة'],
        ['prog_book_cta', 'احجز هذا البرنامج', 'Réserver ce programme', 'Book this package', 'زر الحجز'],
        ['prog_itinerary_title', 'البرنامج يوماً بيوم', 'Programme jour par jour', 'Day-by-day itinerary', 'عنوان قسم اليوميات'],
        ['prog_day_label', 'اليوم', 'Jour', 'Day', 'كلمة «اليوم» قبل الرقم'],
        ['prog_includes_title', 'يشمل البرنامج', 'Le programme comprend', 'What’s included', 'عنوان ما يشمله'],
        ['prog_excludes_title', 'لا يشمل', 'Non compris', 'Not included', 'عنوان ما لا يشمله'],
        ['prog_gallery_title', 'معرض الصور', 'Galerie photos', 'Photo gallery', 'عنوان المعرض'],
        ['prog_rooms_title', 'الأسعار حسب نوع الغرفة', 'Prix selon le type de chambre', 'Prices by room type', 'عنوان جدول الأسعار'],
        ['prog_per_person', 'للشخص', 'par personne', 'per person', 'عبارة بعد السعر'],
        ['room_double', 'غرفة ثنائية', 'Chambre double', 'Double room', 'اسم الغرفة الثنائية'],
        ['room_triple', 'غرفة ثلاثية', 'Chambre triple', 'Triple room', 'اسم الغرفة الثلاثية'],
        ['room_quad', 'غرفة رباعية', 'Chambre quadruple', 'Quad room', 'اسم الغرفة الرباعية'],
        ['prog_departures_title', 'مواعيد الانطلاق', 'Dates de départ', 'Departure dates', 'عنوان المواعيد'],
        ['prog_return_label', 'العودة', 'Retour', 'Return', 'كلمة العودة'],
        ['prog_seats_left', '{n} مقاعد متبقية', '{n} places restantes', '{n} seats left', 'استخدم {n} لعدد المقاعد'],
        ['prog_full', 'مكتمل', 'Complet', 'Full', 'موعد بلا مقاعد'],
        ['prog_no_departures', 'تواصل معنا لمعرفة المواعيد القادمة.', 'Contactez-nous pour les prochaines dates.', 'Contact us for upcoming dates.', 'عند عدم وجود مواعيد'],
        ['prog_hidden_notice', 'هذا البرنامج مخفي ولا يراه الزوار. تراه لأنك مسجل في الإدارة.', 'Programme masqué : visible uniquement par l’administration.', 'Hidden package: visible to admins only.', 'تنبيه للمشرف فقط'],
        ['modal_departure_label', 'موعد الانطلاق', 'Date de départ', 'Departure date', 'حقل الموعد في نموذج الحجز'],
        ['modal_departure_other', 'موعد آخر / لم أحدد بعد', 'Autre date / pas encore décidé', 'Another date / not sure yet', 'خيار عدم تحديد الموعد'],
        ['modal_room_label', 'نوع الغرفة', 'Type de chambre', 'Room type', 'حقل الغرفة في نموذج الحجز'],
        ['modal_room_any', 'لم أحدد', 'Indifférent', 'No preference', 'خيار عدم تحديد الغرفة'],
        ['modal_estimate_label', 'التكلفة التقديرية', 'Coût estimé', 'Estimated cost', 'عنوان التقدير في النموذج'],
    ];
    $order = 10;
    foreach ($texts as [$key, $ar, $fr, $en, $label]) {
        mig_setting($pdo, $key, $ar, 'program', $label, 'text', '', $order, true, $fr, $en);
        $order += 10;
    }

    $pdo->prepare("UPDATE settings SET svalue = ?, svalue_fr = ?, svalue_en = ? WHERE skey = 'pkg_button' AND svalue = ?")
        ->execute(['احجز الآن', 'Réserver', 'Book now', 'تفاصيل وحجز الرحلة']);
    $pdo->exec("UPDATE settings SET field_type = 'text', hint = 'اختياري إذا أدخلت أسعار الغرف، فيظهر «ابتداءً من» أقل سعر' WHERE skey = 'pkg_price_label'");

    $sample = $pdo->prepare('SELECT id FROM packages WHERE title = ? AND (itinerary IS NULL OR itinerary = \'\')');
    $sample->execute(['Good Fly - عمرة العشر الأواخر من رمضان']);
    $ramadanId = $sample->fetchColumn();
    if ($ramadanId) {
        $pdo->prepare('UPDATE packages SET gallery = ?, price_double = 42000, price_triple = 38000, price_quad = 35000,
            itinerary = ?, itinerary_fr = ?, itinerary_en = ?, includes = ?, includes_fr = ?, includes_en = ?, excludes = ?, excludes_fr = ?, excludes_en = ? WHERE id = ?')
            ->execute([
                json_encode(['assets/img/kaaba-night-800.jpg', 'assets/img/haram-2022-800.jpg', 'assets/img/nabawi-day-800.jpg', 'assets/img/green-dome-800.jpg']),
                "الوصول إلى المدينة المنورة\nاستقبال في المطار والانتقال إلى الفندق قرب المسجد النبوي، وصلاة المغرب في الحرم.\n\nزيارة المدينة المنورة\nزيارة مسجد قباء وجبل أحد والمساجد السبعة، مع تصريح الروضة الشريفة.\n\nالانتقال إلى مكة وأداء العمرة\nالإحرام من ميقات ذي الحليفة والانتقال بقطار الحرمين، ثم أداء مناسك العمرة مع المرشد.\n\nالعشر الأواخر في مكة\nإقامة في جناح مطل على الكعبة، وإفطار وسحور يومي، وصلاة التراويح والقيام في الحرم.\n\nالعودة\nالانتقال إلى مطار جدة والعودة بعد صلاة العيد.",
                "Arrivée à Médine\nAccueil à l’aéroport, transfert à l’hôtel près de la Mosquée du Prophète et prière du Maghrib au Haram.\n\nVisite de Médine\nMosquée de Quba, mont Uhud et les Sept Mosquées, avec l’autorisation d’accès à la Rawda.\n\nDépart pour La Mecque et Omra\nIhram au miqat de Dhul Hulayfa, train Haramain, puis accomplissement de la Omra avec le guide.\n\nLes dix dernières nuits à La Mecque\nSuite avec vue sur la Kaaba, ftour et shour chaque jour, Tarawih et Qiyam au Haram.\n\nRetour\nTransfert à l’aéroport de Djeddah et retour après la prière de l’Aïd.",
                "Arrival in Madinah\nAirport welcome, transfer to a hotel near the Prophet’s Mosque and Maghrib prayer at the Haram.\n\nMadinah visits\nQuba Mosque, Mount Uhud and the Seven Mosques, with a Rawdah entry permit.\n\nTo Makkah for Umrah\nIhram at the Dhul Hulayfah miqat, Haramain train, then Umrah with your guide.\n\nThe last ten nights in Makkah\nSuite overlooking the Kaaba, daily iftar and suhoor, Taraweeh and Qiyam at the Haram.\n\nReturn\nTransfer to Jeddah airport and flight home after Eid prayer.",
                "تذكرة الطيران ذهاباً وإياباً\nالتأشيرة والتأمين الصحي\nالإقامة في فنادق 5 نجوم مع الإفطار والسحور\nالتنقلات وقطار الحرمين\nمرشد ديني مرافق طوال الرحلة",
                "Billet d’avion aller-retour\nVisa et assurance santé\nHôtels 5 étoiles avec ftour et shour\nTransferts et train Haramain\nGuide religieux pendant tout le voyage",
                "Return flights\nVisa and health insurance\n5-star hotels with iftar and suhoor\nTransfers and Haramain train\nReligious guide throughout the trip",
                "المصاريف الشخصية\nالهدي والأضاحي\nالوجبات غير المذكورة",
                "Dépenses personnelles\nHadiy et sacrifices\nRepas non mentionnés",
                "Personal expenses\nHady and sacrifices\nMeals not listed",
                $ramadanId,
            ]);
    }
}

function migrate_monthly_departures(PDO $pdo): void
{
    mig_setting($pdo, 'pkg_next_departure', 'أقرب رحلة', 'program', 'عبارة قبل أقرب موعد في البطاقة', 'text', '', 200, true, 'Prochain départ', 'Next departure');
    mig_setting($pdo, 'pkg_all_dates', 'كل المواعيد ({n})', 'program', 'رابط كل المواعيد في البطاقة', 'text', 'استخدم {n} لعدد المواعيد', 210, true, 'Toutes les dates ({n})', 'All dates ({n})');
}

/** Hotel options per program (Madinah + Makkah hotels with a price per room type), and seats that may be left untracked. */
function migrate_package_hotels(PDO $pdo): void
{
    $translated = '';
    foreach (['madinah_hotel' => 160, 'makkah_hotel' => 160, 'makkah_note' => 255, 'meals' => 160] as $column => $length) {
        foreach (array_merge([''], array_map(static function ($lang) {
            return '_' . $lang;
        }, extra_languages())) as $suffix) {
            $translated .= "`{$column}{$suffix}` VARCHAR({$length}) NOT NULL DEFAULT '',\n";
        }
    }
    $pdo->exec("CREATE TABLE IF NOT EXISTS package_hotels (
        id INT AUTO_INCREMENT PRIMARY KEY,
        package_id INT NOT NULL,
        {$translated}
        price_double INT NOT NULL DEFAULT 0,
        price_triple INT NOT NULL DEFAULT 0,
        price_quad INT NOT NULL DEFAULT 0,
        price_quint INT NOT NULL DEFAULT 0,
        sort_order INT NOT NULL DEFAULT 0,
        INDEX idx_hotel_package (package_id, sort_order),
        CONSTRAINT fk_hotel_package FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec('ALTER TABLE package_departures MODIFY seats_left INT NULL DEFAULT NULL');
    mig_add_column($pdo, 'bookings', 'hotel_id', 'INT NULL');
    mig_add_column($pdo, 'bookings', 'hotel_label', "VARCHAR(255) NOT NULL DEFAULT ''");
    mig_add_column($pdo, 'bookings', 'room_price', 'INT NOT NULL DEFAULT 0');

    $hasHotels = (int) $pdo->query('SELECT COUNT(*) FROM package_hotels')->fetchColumn() > 0;
    if (!$hasHotels) {
        $insert = $pdo->prepare('INSERT INTO package_hotels (package_id, makkah_hotel, makkah_hotel_fr, makkah_hotel_en, makkah_note, makkah_note_fr, makkah_note_en, price_double, price_triple, price_quad, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 10)');
        foreach ($pdo->query('SELECT * FROM packages WHERE price_double > 0 OR price_triple > 0 OR price_quad > 0')->fetchAll() as $row) {
            $insert->execute([
                $row['id'], str_limit((string) $row['hotel'], 160), str_limit((string) $row['hotel_fr'], 160), str_limit((string) $row['hotel_en'], 160),
                str_limit((string) $row['distance'], 255), str_limit((string) $row['distance_fr'], 255), str_limit((string) $row['distance_en'], 255),
                (int) $row['price_double'], (int) $row['price_triple'], (int) $row['price_quad'],
            ]);
        }
    }

    $texts = [
        ['room_quint', 'غرفة خماسية', 'Chambre quintuple', 'Quintuple room', 'اسم الغرفة الخماسية'],
        ['prog_hotels_title', 'الفنادق والأسعار', 'Hôtels et tarifs', 'Hotels & prices', 'عنوان جدول الفنادق'],
        ['prog_hotels_note', 'الأسعار للشخص الواحد بالدرهم حسب نوع الغرفة.', 'Prix par personne selon le type de chambre.', 'Prices per person by room type.', 'سطر تحت جدول الفنادق'],
        ['prog_madinah', 'المدينة المنورة', 'Médine', 'Madinah', 'عمود فندق المدينة'],
        ['prog_makkah', 'مكة المكرمة', 'La Mecque', 'Makkah', 'عمود فندق مكة'],
        ['prog_seats_open', 'متاح', 'Disponible', 'Available', 'موعد بلا عدد مقاعد محدد'],
        ['modal_hotel_label', 'الفندق', 'Hôtel', 'Hotel', 'حقل الفندق في نموذج الحجز'],
        ['modal_hotel_any', 'لم أحدد بعد', 'Pas encore choisi', 'Not decided yet', 'خيار عدم تحديد الفندق'],
    ];
    $order = 300;
    foreach ($texts as [$key, $ar, $fr, $en, $label]) {
        mig_setting($pdo, $key, $ar, 'program', $label, 'text', '', $order, true, $fr, $en);
        $order += 10;
    }
}
