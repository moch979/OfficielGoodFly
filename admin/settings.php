<?php

require __DIR__ . '/_init.php';
require __DIR__ . '/_layout.php';
require_once dirname(__DIR__) . '/includes/mailer.php';

$groups = setting_groups();
$group = (string) ($_GET['group'] ?? 'general');
if (!isset($groups[$group])) {
    $group = 'general';
}
if ($group === 'agency') {
    redirect('agency.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $postedGroup = (string) ($_POST['group'] ?? '');
    if (!isset($groups[$postedGroup])) {
        flash_set('err', 'مجموعة غير معروفة.');
        redirect('settings.php');
    }
    try {
        if (($_POST['action'] ?? '') === 'test_mail') {
            $to = trim(s('notify_email'));
            if ($to === '') {
                throw new RuntimeException('احفظ «البريد الذي تصله تنبيهات الطلبات» أولاً.');
            }
            send_mail($to, 'رسالة تجريبية من Good Fly', "هذه رسالة تجريبية من لوحة التحكم.\nإذا وصلتك فتنبيهات الطلبات تعمل.");
            flash_set('ok', 'أُرسلت رسالة تجريبية إلى ' . $to . '. تفقد صندوق الوارد والبريد المزعج.');
        } elseif (($_POST['action'] ?? '') === 'google_test') {
            if (trim(s('google_place_id')) === '' || trim(s('google_api_key')) === '') {
                throw new RuntimeException('احفظ «معرّف المكان» و«مفتاح Google Places API» أولاً.');
            }
            google_reviews_clear_cache();
            $withReviews = s('google_reviews_cards', '0') === '1';
            $data = google_reviews_fetch(trim(s('google_place_id')), trim(s('google_api_key')), default_lang(), $withReviews);
            $message = 'نجح الاتصال بـ Google: التقييم ' . $data['rating'] . ' من 5 بناءً على ' . $data['count'] . ' تقييماً';
            $message .= $withReviews ? '، وجُلب ' . count($data['reviews']) . ' آراء مكتوبة.' : '.';
            $message .= s('google_reviews_on', '0') === '1' ? ' يظهر الآن في الموقع.' : ' فعّل «عرض تقييم Google الحقيقي» واحفظ ليظهر في الموقع.';
            flash_set('ok', $message);
        } else {
            if ($postedGroup === 'design' || $postedGroup === 'colors' || $postedGroup === 'visibility') {
                create_snapshot('تلقائية: قبل تعديل ' . $groups[$postedGroup]);
            }
            save_settings_groups([$postedGroup], $_POST);
            flash_set('ok', 'تم حفظ التفاصيل.');
        }
    } catch (Throwable $e) {
        flash_set('err', $e->getMessage());
    }
    redirect('settings.php?group=' . urlencode($postedGroup));
}

$rows = rows('SELECT * FROM settings WHERE sgroup = ? ORDER BY sorder, skey', [$group]);
$active = $group === 'notifications' ? 'notifications' : 'settings.php';
admin_header($group === 'notifications' ? 'تنبيهات البريد' : 'كل النصوص والتفاصيل', $active);

if ($group === 'notifications') {
    echo '<p class="intro">عند كل طلب حجز جديد تصلك رسالة على البريد الذي تحدده هنا. بريد الخوادم المحلية مثل XAMPP لا يرسل غالباً، لذلك جرّب الإرسال بعد رفع الموقع على الاستضافة باستخدام بيانات SMTP من لوحة الاستضافة.</p>';
} else {
    echo '<p class="intro">كل عبارة في الموقع قابلة للتعديل من هنا بالعربية والفرنسية والإنجليزية. العناصر المتكررة مثل البرامج والخدمات لها صفحات مستقلة، والمصمم المرئي يعرض التعديل على الموقع مباشرة.</p>';
    echo '<div class="tabs">';
    foreach ($groups as $key => $label) {
        if ($key === 'notifications' || $key === 'agency') {
            continue;
        }
        $class = $key === $group ? ' class="active"' : '';
        echo '<a' . $class . ' href="settings.php?group=' . h($key) . '">' . h($label) . '</a>';
    }
    echo '</div>';
}

$hasTranslatable = (bool) array_filter($rows, static function ($row) {
    return (int) $row['translatable'] === 1;
});

echo '<form class="card" method="post" enctype="multipart/form-data">';
echo csrf_field() . '<input type="hidden" name="group" value="' . h($group) . '">';
if ($hasTranslatable) {
    echo edit_lang_switch();
}
foreach ($rows as $row) {
    $key = $row['skey'];
    $field = [
        'label' => $row['label'],
        'type' => $row['field_type'],
        'hint' => $row['hint'],
        'translatable' => (int) $row['translatable'] === 1,
    ];
    $values = ['ar' => (string) $row['svalue'], 'fr' => (string) $row['svalue_fr'], 'en' => (string) $row['svalue_en']];
    render_field('s_' . $key, $field, static function (string $lang) use ($key) {
        return ($lang === default_lang() ? 'values' : 'values_' . $lang) . '[' . $key . ']';
    }, $values, 'file_' . $key);
}
echo '<div class="actions" style="margin-top:18px"><button class="btn" type="submit">حفظ هذا القسم</button></div></form>';

if ($group === 'notifications') {
    echo '<form class="card" method="post">' . csrf_field() . '<input type="hidden" name="group" value="notifications"><input type="hidden" name="action" value="test_mail">';
    echo '<h2>تجربة الإرسال</h2><p class="intro">يرسل رسالة تجريبية إلى البريد المحفوظ أعلاه.</p><button class="btn secondary" type="submit">إرسال رسالة تجريبية</button></form>';
}
if ($group === 'testimonials') {
    echo '<form class="card" method="post">' . csrf_field() . '<input type="hidden" name="group" value="testimonials"><input type="hidden" name="action" value="google_test">';
    echo '<h2>تقييمات Google</h2><p class="intro">يتصل بـ Google الآن بالمفتاح والمعرّف المحفوظين أعلاه ويعرض النتيجة. يحدّث الموقع التقييمات تلقائياً كل 6 ساعات، وGoogle تعطي 5 آراء كحد أقصى وتختار هي أيها يظهر.</p><button class="btn secondary" type="submit">اختبار الاتصال وتحديث التقييمات الآن</button></form>';
}
admin_footer();
