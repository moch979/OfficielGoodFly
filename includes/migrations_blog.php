<?php

/** Blog posts with their own pages, and the menu link that replaces "About the agency". */
function migrate_blog(PDO $pdo): void
{
    $translated = '';
    foreach (['title' => 'VARCHAR(255)', 'excerpt' => 'VARCHAR(500)', 'body' => 'MEDIUMTEXT'] as $column => $type) {
        $translated .= "`{$column}` {$type} NOT NULL,\n";
        foreach (extra_languages() as $lang) {
            $translated .= "`{$column}_{$lang}` {$type} NULL,\n";
        }
    }
    $pdo->exec("CREATE TABLE IF NOT EXISTS blog_posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(120) NOT NULL,
        {$translated}
        image VARCHAR(500) NOT NULL DEFAULT '',
        published_at DATE NOT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        UNIQUE KEY uniq_blog_slug (slug),
        INDEX idx_blog_list (is_active, published_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $texts = [
        ['blog_kicker', 'مدونة Good Fly', 'Le blog Good Fly', 'The Good Fly blog', 'السطر الصغير فوق العنوان'],
        ['blog_title', 'نصائح ومقالات لرحلة الحج والعمرة', 'Conseils et articles pour le Hajj et la Omra', 'Hajj & Umrah tips and articles', 'عنوان صفحة المدونة'],
        ['blog_subtitle', 'إرشادات عملية ومستجدات السفر لتستعدوا لرحلتكم الإيمانية بطمأنينة.', 'Conseils pratiques et actualités du voyage pour préparer sereinement votre voyage spirituel.', 'Practical guidance and travel updates to prepare for your spiritual journey with peace of mind.', 'وصف صفحة المدونة'],
        ['blog_read_more', 'اقرأ المقال', 'Lire l’article', 'Read article', 'زر قراءة المقال'],
        ['blog_minutes', 'مدة القراءة: {n} د', '{n} min de lecture', '{n} min read', 'مدة القراءة، استخدم {n} لعدد الدقائق'],
        ['blog_back', 'كل المقالات', 'Tous les articles', 'All articles', 'رابط العودة إلى المدونة'],
        ['blog_related', 'مقالات أخرى', 'Autres articles', 'More articles', 'عنوان المقالات الأخرى أسفل المقال'],
        ['blog_empty', 'لا توجد مقالات منشورة بعد.', 'Aucun article publié pour le moment.', 'No articles published yet.', 'عند عدم وجود مقالات'],
        ['blog_cta_title', 'هل تخططون لرحلتكم القادمة؟', 'Vous préparez votre prochain voyage ?', 'Planning your next trip?', 'عنوان دعوة الحجز أسفل المقال'],
        ['blog_cta_text', 'تواصلوا مع مستشاري Good Fly لاختيار البرنامج المناسب لكم.', 'Contactez les conseillers Good Fly pour choisir le programme qui vous convient.', 'Talk to a Good Fly advisor to choose the right package for you.', 'نص دعوة الحجز أسفل المقال'],
        ['blog_hidden_notice', 'هذا المقال مخفي أو مجدول ولا يراه الزوار. تراه لأنك مسجل في الإدارة.', 'Article masqué ou programmé : visible uniquement par l’administration.', 'Hidden or scheduled article: visible to admins only.', 'تنبيه للمشرف فقط'],
        ['blog_not_found', 'المقال غير موجود', 'Article introuvable', 'Article not found', 'عنوان صفحة مقال غير موجود'],
    ];
    $order = 10;
    foreach ($texts as [$key, $ar, $fr, $en, $label]) {
        mig_setting($pdo, $key, $ar, 'blog', $label, 'text', '', $order, true, $fr, $en);
        $order += 10;
    }

    $renamed = $pdo->prepare("UPDATE nav_links SET label_ar = 'المدونة', label_fr = 'Blog', label_en = 'Blog', href = 'blog' WHERE href = '#about'");
    $renamed->execute();
    $hasLink = (int) $pdo->query("SELECT COUNT(*) FROM nav_links WHERE href = 'blog'")->fetchColumn() > 0;
    if ($renamed->rowCount() === 0 && !$hasLink) {
        $pdo->prepare('INSERT INTO nav_links (label_ar, label_en, label_fr, href, sort_order) VALUES (?, ?, ?, ?, ?)')
            ->execute(['المدونة', 'Blog', 'Blog', 'blog', 20]);
    }

    if ((int) $pdo->query('SELECT COUNT(*) FROM blog_posts')->fetchColumn() > 0) {
        return;
    }
    $insert = $pdo->prepare('INSERT INTO blog_posts (slug, title, title_fr, title_en, excerpt, excerpt_fr, excerpt_en, body, body_fr, body_en, image, published_at, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $insert->execute([
        'prepare-for-umrah',
        'كيف تستعد لرحلة العمرة؟ دليل عملي قبل السفر',
        'Comment se préparer à la Omra ? Guide pratique avant le départ',
        'How to prepare for Umrah: a practical pre-travel guide',
        'الوثائق، الحقيبة، والصحة: خطوات بسيطة تجعل رحلتكم أكثر راحة وطمأنينة.',
        'Documents, bagages et santé : des étapes simples pour un voyage plus serein.',
        'Documents, packing and health: simple steps for a calmer, more comfortable trip.',
        "يبدأ الاستعداد الجيد للعمرة قبل السفر بأسابيع. كلما رتّبتم التفاصيل مبكراً، تفرغتم في مكة والمدينة للعبادة.\n\n## الوثائق\nتأكدوا أن جواز السفر صالح لستة أشهر على الأقل بعد تاريخ العودة، واحتفظوا بنسخة منه على الهاتف. يتولى فريق Good Fly التأشيرة والتأمين، ونرسل لكم كل الوثائق قبل الانطلاق.\n\n## الحقيبة\nملابس إحرام قطنية، حذاء مريح للمشي، أدوية شخصية مع وصفتها، وحقيبة صغيرة للوثائق والهاتف. خففوا الوزن قدر الإمكان، فالتنقل بين الفنادق والحرم يومي.\n\n## الصحة\nابدؤوا المشي يومياً قبل السفر بأسبوعين، واشربوا الماء بكثرة خلال الرحلة. استشيروا الطبيب إن كانت لديكم حالة مزمنة.",
        "Une bonne préparation à la Omra commence plusieurs semaines avant le départ. Plus vous réglez les détails tôt, plus vous serez disponibles pour l’adoration à La Mecque et à Médine.\n\n## Les documents\nVérifiez que votre passeport est valable au moins six mois après la date de retour, et gardez-en une copie sur votre téléphone. L’équipe Good Fly s’occupe du visa et de l’assurance et vous envoie tous les documents avant le départ.\n\n## Les bagages\nVêtements d’ihram en coton, chaussures confortables, médicaments personnels avec l’ordonnance et une petite sacoche pour les documents. Voyagez léger : les trajets entre l’hôtel et le Haram sont quotidiens.\n\n## La santé\nMarchez chaque jour dans les deux semaines précédant le départ et buvez beaucoup d’eau pendant le voyage. Consultez votre médecin si vous suivez un traitement.",
        "Good preparation for Umrah starts weeks before you fly. The sooner the details are settled, the more you can focus on worship in Makkah and Madinah.\n\n## Documents\nMake sure your passport is valid for at least six months after your return date and keep a copy on your phone. The Good Fly team handles your visa and insurance and sends you every document before departure.\n\n## Packing\nCotton ihram clothing, comfortable walking shoes, personal medication with its prescription and a small bag for documents. Travel light: you will walk between the hotel and the Haram every day.\n\n## Health\nWalk daily for two weeks before the trip and drink plenty of water while travelling. See your doctor if you have a chronic condition.",
        'assets/img/kaaba-night-800.jpg',
        date('Y-m-d'),
        10,
    ]);
    $insert->execute([
        'visiting-madinah',
        'زيارة المدينة المنورة: أماكن لا تفوّتوها',
        'Visiter Médine : les lieux à ne pas manquer',
        'Visiting Madinah: places not to miss',
        'من المسجد النبوي إلى مسجد قباء وجبل أحد، هذه أهم محطات الزيارة.',
        'De la Mosquée du Prophète à la mosquée de Quba et au mont Uhud : les étapes essentielles.',
        'From the Prophet’s Mosque to Quba Mosque and Mount Uhud: the essential stops.',
        "المدينة المنورة محطة هادئة في رحلة العمرة، ويستحق كل يوم فيها أن يُخطط له.\n\n## المسجد النبوي والروضة الشريفة\nيحتاج دخول الروضة إلى تصريح مسبق عبر تطبيق نسك، ويتولى مرافقو Good Fly حجز المواعيد لضيوفنا رجالاً ونساءً.\n\n## مسجد قباء\nأول مسجد بُني في الإسلام، والصلاة فيه تعدل عمرة كما ورد في الحديث. يُفضّل زيارته صباحاً قبل الزحام.\n\n## جبل أحد\nموقع الغزوة الشهيرة ومقبرة شهداء أحد، وتشمل برامجنا زيارته مع شرح تاريخي من المرشد.",
        "Médine est une étape apaisante du voyage de Omra, et chaque journée mérite d’être bien organisée.\n\n## La Mosquée du Prophète et la Rawda\nL’accès à la Rawda nécessite une autorisation préalable via l’application Nusuk ; les accompagnateurs Good Fly réservent les créneaux pour nos voyageurs, hommes et femmes.\n\n## La mosquée de Quba\nPremière mosquée de l’islam : y prier équivaut à une Omra selon le hadith. Mieux vaut s’y rendre le matin, avant l’affluence.\n\n## Le mont Uhud\nLieu de la célèbre bataille et du cimetière des martyrs, visité dans nos programmes avec les explications historiques du guide.",
        "Madinah is a peaceful stop on an Umrah journey, and every day there deserves a plan.\n\n## The Prophet’s Mosque and the Rawdah\nEntering the Rawdah requires a prior permit through the Nusuk app; Good Fly guides book the slots for our guests, men and women alike.\n\n## Quba Mosque\nThe first mosque built in Islam; praying there equals an Umrah according to the hadith. Visit in the morning, before the crowds.\n\n## Mount Uhud\nSite of the famous battle and the martyrs’ cemetery, included in our packages with historical commentary from your guide.",
        'assets/img/green-dome-800.jpg',
        date('Y-m-d'),
        20,
    ]);
}
