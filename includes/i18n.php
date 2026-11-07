<?php

function languages(): array
{
    return [
        'ar' => ['name' => 'العربية', 'short' => 'ع', 'dir' => 'rtl', 'locale' => 'ar_MA', 'label' => 'العربية'],
        'fr' => ['name' => 'Français', 'short' => 'FR', 'dir' => 'ltr', 'locale' => 'fr_MA', 'label' => 'الفرنسية'],
        'en' => ['name' => 'English', 'short' => 'EN', 'dir' => 'ltr', 'locale' => 'en_US', 'label' => 'الإنجليزية'],
    ];
}

/** Languages stored in extra columns, the Arabic base column has no suffix. */
function extra_languages(): array
{
    return ['fr', 'en'];
}

function default_lang(): string
{
    return 'ar';
}

function set_lang(string $lang): void
{
    $GLOBALS['GF_LANG'] = isset(languages()[$lang]) ? $lang : default_lang();
}

function current_lang(): string
{
    return $GLOBALS['GF_LANG'] ?? default_lang();
}

function lang_dir(?string $lang = null): string
{
    return languages()[$lang ?? current_lang()]['dir'];
}

function lang_url(string $lang, string $anchor = ''): string
{
    $path = base_path() . '/' . ($lang === default_lang() ? '' : $lang . '/');
    return $path . $anchor;
}

function lang_column(string $column, ?string $lang = null): string
{
    $lang = $lang ?? current_lang();
    return $lang === default_lang() ? $column : $column . '_' . $lang;
}

/** Value of $column in the current language, falling back to Arabic when empty. */
function loc(array $row, string $column, ?string $lang = null): string
{
    $lang = $lang ?? current_lang();
    if ($lang !== default_lang()) {
        $value = trim((string) ($row[$column . '_' . $lang] ?? ''));
        if ($value !== '') {
            return $value;
        }
    }
    return (string) ($row[$column] ?? '');
}

function load_settings(): void
{
    $settings = [];
    foreach (db()->query('SELECT skey, svalue, svalue_fr, svalue_en, translatable FROM settings') as $row) {
        $settings[$row['skey']] = [
            'ar' => $row['svalue'],
            'fr' => $row['svalue_fr'],
            'en' => $row['svalue_en'],
            't' => (int) $row['translatable'] === 1,
        ];
    }
    $GLOBALS['SETTINGS'] = $settings;
}

function s(string $key, string $default = ''): string
{
    if (isset($GLOBALS['SETTINGS_OVERRIDE'][$key])) {
        return (string) $GLOBALS['SETTINGS_OVERRIDE'][$key];
    }
    $entry = $GLOBALS['SETTINGS'][$key] ?? null;
    if ($entry === null) {
        return $default;
    }
    $lang = current_lang();
    if ($entry['t'] && $lang !== default_lang()) {
        $value = trim((string) ($entry[$lang] ?? ''));
        if ($value !== '') {
            return $value;
        }
    }
    return $entry['ar'] === null ? $default : (string) $entry['ar'];
}

function setting_exists(string $key): bool
{
    return isset($GLOBALS['SETTINGS'][$key]);
}

/** Fixed interface messages that are not worth exposing in the admin. */
function t(string $key): string
{
    static $strings = [
        'error_title' => ['ar' => 'حدث خطأ', 'fr' => 'Une erreur est survenue', 'en' => 'Something went wrong'],
        'error_generic' => ['ar' => 'تعذر إتمام الطلب الآن. حاول مرة أخرى بعد قليل.', 'fr' => 'Impossible de traiter la demande pour le moment. Réessayez dans un instant.', 'en' => 'We could not complete the request. Please try again shortly.'],
        'name_phone_required' => ['ar' => 'الاسم ورقم الهاتف مطلوبان.', 'fr' => 'Le nom et le téléphone sont obligatoires.', 'en' => 'Name and phone number are required.'],
        'field_too_long' => ['ar' => 'أحد الحقول أطول من المسموح.', 'fr' => 'Un des champs est trop long.', 'en' => 'One of the fields is too long.'],
        'phone_invalid' => ['ar' => 'رقم الهاتف غير صحيح.', 'fr' => 'Numéro de téléphone invalide.', 'en' => 'Invalid phone number.'],
        'date_invalid' => ['ar' => 'تاريخ السفر غير صحيح.', 'fr' => 'Date de voyage invalide.', 'en' => 'Invalid travel date.'],
        'too_many' => ['ar' => 'تم إرسال طلبات كثيرة. حاول بعد قليل.', 'fr' => 'Trop de demandes. Réessayez plus tard.', 'en' => 'Too many requests. Please try again later.'],
        'email_invalid' => ['ar' => 'أدخل بريداً إلكترونياً صحيحاً.', 'fr' => 'Saisissez une adresse e-mail valide.', 'en' => 'Please enter a valid email address.'],
        'bad_request' => ['ar' => 'طلب غير صالح.', 'fr' => 'Requête invalide.', 'en' => 'Invalid request.'],
        'close' => ['ar' => 'إغلاق', 'fr' => 'Fermer', 'en' => 'Close'],
        'menu' => ['ar' => 'القائمة', 'fr' => 'Menu', 'en' => 'Menu'],
        'whatsapp' => ['ar' => 'تواصل عبر واتساب', 'fr' => 'Contacter sur WhatsApp', 'en' => 'Chat on WhatsApp'],
        'slide' => ['ar' => 'الصورة', 'fr' => 'Image', 'en' => 'Slide'],
        'language' => ['ar' => 'اللغة', 'fr' => 'Langue', 'en' => 'Language'],
        'continue_whatsapp' => ['ar' => 'إرسال التفاصيل عبر واتساب', 'fr' => 'Envoyer les détails sur WhatsApp', 'en' => 'Send details on WhatsApp'],
        'sending' => ['ar' => 'جارٍ الإرسال...', 'fr' => 'Envoi en cours...', 'en' => 'Sending...'],
        'ice_label' => ['ar' => 'ICE:', 'fr' => 'ICE :', 'en' => 'ICE:'],
        'if_label' => ['ar' => 'التعريف الضريبي:', 'fr' => 'IF :', 'en' => 'Tax ID:'],
        'view_map' => ['ar' => 'عرض على الخريطة', 'fr' => 'Voir sur la carte', 'en' => 'View on map'],
        'follow_us' => ['ar' => 'تابعونا', 'fr' => 'Suivez-nous', 'en' => 'Follow us'],
        'not_found_title' => ['ar' => 'البرنامج غير موجود', 'fr' => 'Programme introuvable', 'en' => 'Package not found'],
        'not_found_text' => ['ar' => 'ربما انتهى هذا البرنامج أو تغيّر رابطه. تصفح برامجنا الحالية.', 'fr' => 'Ce programme est peut-être terminé ou son lien a changé. Découvrez nos programmes actuels.', 'en' => 'This package may have ended or its link changed. Browse our current packages.'],
        'departure_invalid' => ['ar' => 'موعد الانطلاق المختار غير متاح.', 'fr' => 'La date de départ choisie n’est pas disponible.', 'en' => 'The selected departure is not available.'],
        'seats_not_enough' => ['ar' => 'المقاعد المتبقية في هذا الموعد أقل من عدد المسافرين.', 'fr' => 'Il reste moins de places que de voyageurs pour cette date.', 'en' => 'There are fewer seats left on this departure than travellers.'],
    ];
    $lang = current_lang();
    return $strings[$key][$lang] ?? ($strings[$key]['ar'] ?? $key);
}
