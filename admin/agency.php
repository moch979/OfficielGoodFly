<?php

require __DIR__ . '/_init.php';
require __DIR__ . '/_layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    try {
        save_settings_groups(['agency'], $_POST);
        flash_set('ok', 'تم حفظ معلومات الوكالة. تظهر الآن في كل صفحات الموقع.');
    } catch (Throwable $e) {
        flash_set('err', $e->getMessage());
    }
    redirect('agency.php');
}

$rows = [];
foreach (rows("SELECT * FROM settings WHERE sgroup = 'agency' ORDER BY sorder, skey") as $row) {
    $rows[$row['skey']] = $row;
}
$sections = [
    'الهوية القانونية' => ['agency_name', 'license_number', 'footer_cr_number', 'agency_ice', 'agency_if'],
    'الهاتف والبريد' => ['phone_display', 'agency_phone2', 'wa_number', 'top_email', 'footer_email'],
    'العنوان وساعات العمل' => ['footer_address', 'agency_map_url', 'agency_hours'],
    'حسابات التواصل الاجتماعي' => ['social_facebook', 'social_instagram', 'social_tiktok', 'social_youtube'],
];
$left = agency_placeholders_left();

admin_header('معلومات الوكالة', 'agency.php');
echo '<p class="intro">كل بيانات الوكالة في مكان واحد. ما تكتبه هنا يظهر تلقائياً في الشريط العلوي، والتذييل، وصفحات البرامج، ورسائل واتساب، وبيانات محركات البحث. الحقول الفارغة لا تظهر للزائر.</p>';

if ($left) {
    echo '<div class="flash warn"><strong>بيانات تجريبية ما زالت ظاهرة للزوار:</strong> ' . h(implode('، ', $left)) . '. استبدلها ببيانات وكالتك قبل النشر.</div>';
}

echo '<form class="card" method="post">' . csrf_field();
echo edit_lang_switch();
foreach ($sections as $title => $keys) {
    echo '<h2 class="form-section">' . h($title) . '</h2>';
    foreach ($keys as $key) {
        if (!isset($rows[$key])) {
            continue;
        }
        $row = $rows[$key];
        $field = ['label' => $row['label'], 'type' => $row['field_type'], 'hint' => $row['hint'], 'translatable' => (int) $row['translatable'] === 1];
        $values = ['ar' => (string) $row['svalue'], 'fr' => (string) $row['svalue_fr'], 'en' => (string) $row['svalue_en']];
        render_field('s_' . $key, $field, static function (string $lang) use ($key) {
            return ($lang === default_lang() ? 'values' : 'values_' . $lang) . '[' . $key . ']';
        }, $values);
        if (isset($left[$key])) {
            echo '<div class="missing">قيمة تجريبية</div>';
        }
    }
}
echo '<div class="actions" style="margin-top:20px"><button class="btn" type="submit">حفظ معلومات الوكالة</button></div></form>';

echo '<div class="card"><h2>إعدادات مرتبطة</h2><ul class="checklist">';
echo '<li><a href="settings.php?group=brand">الشعار وأيقونة المتصفح</a></li>';
echo '<li><a href="settings.php?group=footer">نصوص التذييل: النبذة وعناوين الأعمدة وحقوق النشر</a></li>';
echo '<li><a href="settings.php?group=modal">رسائل واتساب ونموذج الحجز</a></li>';
echo '<li><a href="settings.php?group=notifications">البريد الذي تصله تنبيهات الطلبات</a></li>';
echo '</ul></div>';
admin_footer();
