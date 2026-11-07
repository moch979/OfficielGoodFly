<?php

require __DIR__ . '/_init.php';
require __DIR__ . '/_layout.php';

$counts = [
    'طلبات جديدة' => (int) db()->query("SELECT COUNT(*) FROM bookings WHERE status = 'new'")->fetchColumn(),
    'كل الطلبات' => (int) db()->query('SELECT COUNT(*) FROM bookings')->fetchColumn(),
    'البرامج الظاهرة' => (int) db()->query('SELECT COUNT(*) FROM packages WHERE is_active = 1')->fetchColumn(),
    'المشتركون' => (int) db()->query('SELECT COUNT(*) FROM subscribers')->fetchColumn(),
];
$latest = rows('SELECT id, full_name, phone, trip_type, status, lang, created_at FROM bookings ORDER BY id DESC LIMIT 6');
$labels = booking_status_labels();

$checklist = [];
if (s('password_changed', '0') !== '1') {
    $checklist[] = ['كلمة مرور الإدارة ما زالت الافتراضية', 'account.php'];
}
$agencyLeft = agency_placeholders_left();
if ($agencyLeft) {
    $checklist[] = ['معلومات الوكالة ما زالت تجريبية: ' . implode('، ', $agencyLeft), 'agency.php'];
}
$sampleTestimonials = (int) db()->query("SELECT COUNT(*) FROM testimonials WHERE name IN ('أحمد المحمدي', 'د. سارة المنصوري', 'م. خالد البلوشي') AND is_active = 1")->fetchColumn();
if ($sampleTestimonials > 0) {
    $checklist[] = ['آراء العملاء التجريبية ما زالت ظاهرة (' . $sampleTestimonials . ')', 'collection.php?type=testimonials'];
}
$emptyLegal = (int) db()->query("SELECT COUNT(*) FROM footer_links WHERE group_key = 'legal' AND is_active = 1 AND (href = '#' OR href = '')")->fetchColumn();
if ($emptyLegal > 0) {
    $checklist[] = ['روابط قانونية بلا صفحات (' . $emptyLegal . ')، مثل سياسة الخصوصية', 'collection.php?type=footer'];
}
if (trim(s('site_url')) === '') {
    $checklist[] = ['رابط الموقع الكامل غير محدد (مطلوب لروابط المشاركة وخريطة الموقع)', 'settings.php?group=general'];
}
if (trim(s('notify_email')) === '') {
    $checklist[] = ['تنبيهات البريد عند الطلبات الجديدة غير مفعّلة', 'settings.php?group=notifications'];
}
$untranslated = (int) db()->query("SELECT COUNT(*) FROM settings WHERE translatable = 1 AND svalue <> '' AND (svalue_fr IS NULL OR svalue_fr = '' OR svalue_en IS NULL OR svalue_en = '')")->fetchColumn();
if ($untranslated > 3) {
    $checklist[] = [$untranslated . ' نصاً عاماً ينقصه الفرنسية أو الإنجليزية', 'settings.php'];
}

admin_header('لوحة التحكم', 'index.php');
echo '<p class="actions" style="margin-top:0"><a class="btn" href="designer.php"><i class="fa-solid fa-pen-ruler"></i> فتح المصمم المرئي</a>';
foreach (languages() as $code => $info) {
    echo '<a class="btn ghost small" target="_blank" rel="noopener" href="' . h(lang_url($code)) . '">الموقع: ' . h($info['name']) . '</a>';
}
echo '</p>';
if (has_design_draft()) {
    echo '<div class="flash warn">في المصمم تعديلات تصميم غير منشورة. افتح المصمم لنشرها أو تجاهلها.</div>';
}
echo '<div class="stats">';
foreach ($counts as $label => $count) {
    echo '<div class="card stat"><span>' . h($label) . '</span><strong>' . h($count) . '</strong></div>';
}
echo '</div>';

if ($checklist) {
    echo '<div class="card"><h2>قبل النشر</h2><p class="intro">هذه البيانات تجريبية أو ناقصة، والزائر يراها كما هي.</p><ul class="checklist">';
    foreach ($checklist as [$text, $link]) {
        echo '<li><a href="' . h($link) . '">' . h($text) . '</a></li>';
    }
    echo '</ul></div>';
}

echo '<div class="card"><h2>آخر طلبات الحجز</h2>';
if (!$latest) {
    echo '<p class="intro">لا توجد طلبات بعد. أي نموذج يُرسل من الموقع يظهر هنا.</p>';
} else {
    echo '<div style="overflow:auto"><table><thead><tr><th>الاسم</th><th>الهاتف</th><th>الرحلة</th><th>اللغة</th><th>الحالة</th><th>الوقت</th><th></th></tr></thead><tbody>';
    foreach ($latest as $row) {
        echo '<tr><td>' . h($row['full_name']) . '</td><td dir="ltr">' . h($row['phone']) . '</td><td>' . h($row['trip_type']) . '</td>';
        echo '<td>' . h(languages()[$row['lang']]['name'] ?? $row['lang']) . '</td><td>' . h($labels[$row['status']] ?? $row['status']) . '</td><td>' . h($row['created_at']) . '</td>';
        echo '<td><a class="btn small" href="booking.php?id=' . (int) $row['id'] . '">فتح</a></td></tr>';
    }
    echo '</tbody></table></div>';
}
echo '</div>';
admin_footer();
