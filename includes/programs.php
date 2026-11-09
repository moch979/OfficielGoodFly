<?php

const ROOM_TYPES = ['double', 'triple', 'quad', 'quint'];
const HOTEL_TEXT_FIELDS = ['madinah_hotel' => 160, 'makkah_hotel' => 160, 'makkah_note' => 255, 'meals' => 160];

function booking_status_labels(): array
{
    return ['new' => 'جديد', 'contacted' => 'تم التواصل', 'confirmed' => 'مؤكد', 'closed' => 'مغلق'];
}

function program_path(string $slug, ?string $lang = null): string
{
    $lang = $lang ?? current_lang();
    return ($lang === default_lang() ? '' : $lang . '/') . 'program/' . rawurlencode($slug);
}

function program_url(string $slug, ?string $lang = null): string
{
    return base_path() . '/' . program_path($slug, $lang);
}

function program_absolute_url(string $slug, ?string $lang = null): string
{
    return site_url() . '/' . program_path($slug, $lang);
}

function money(int $amount): string
{
    $currency = s('currency_label', 'درهم');
    switch (current_lang()) {
        case 'fr':
            return number_format($amount, 0, ',', "\u{202F}") . ' ' . $currency;
        case 'en':
            return $currency . ' ' . number_format($amount);
        default:
            return number_format($amount) . ' ' . $currency;
    }
}

function room_labels(): array
{
    return ['double' => s('room_double', 'ثنائية'), 'triple' => s('room_triple', 'ثلاثية'), 'quad' => s('room_quad', 'رباعية'), 'quint' => s('room_quint', 'خماسية')];
}

/** Room types with a price, in ROOM_TYPES order. Works on a hotel option row. */
function room_prices(array $row): array
{
    $prices = [];
    foreach (ROOM_TYPES as $type) {
        $price = (int) ($row['price_' . $type] ?? 0);
        if ($price > 0) {
            $prices[$type] = $price;
        }
    }
    return $prices;
}

/** Hotel options grouped by package id, localized for the visitor. */
function package_hotels(array $packageIds): array
{
    $packageIds = array_values(array_unique(array_map('intval', $packageIds)));
    if (!$packageIds) {
        return [];
    }
    $marks = implode(', ', array_fill(0, count($packageIds), '?'));
    $grouped = [];
    foreach (rows("SELECT * FROM package_hotels WHERE package_id IN ($marks) ORDER BY sort_order, id", $packageIds) as $row) {
        $hotel = ['id' => (int) $row['id'], 'prices' => room_prices($row)];
        foreach (array_keys(HOTEL_TEXT_FIELDS) as $column) {
            $hotel[$column] = loc($row, $column);
        }
        $hotel['label'] = hotel_label($hotel);
        $grouped[(int) $row['package_id']][] = $hotel;
    }
    return $grouped;
}

function hotel_label(array $hotel): string
{
    $parts = [];
    if ($hotel['makkah_hotel'] !== '') {
        $parts[] = s('prog_makkah', 'مكة المكرمة') . ': ' . $hotel['makkah_hotel'];
    }
    if ($hotel['madinah_hotel'] !== '') {
        $parts[] = s('prog_madinah', 'المدينة المنورة') . ': ' . $hotel['madinah_hotel'];
    }
    return implode(' — ', $parts);
}

/** Arabic names for the agency side (admin, notifications), whatever the visitor's language. */
function admin_room_names(): array
{
    return ['double' => 'ثنائية', 'triple' => 'ثلاثية', 'quad' => 'رباعية', 'quint' => 'خماسية'];
}

function admin_hotel_label(array $row): string
{
    $parts = [];
    if (trim((string) $row['makkah_hotel']) !== '') {
        $parts[] = 'مكة: ' . $row['makkah_hotel'];
    }
    if (trim((string) $row['madinah_hotel']) !== '') {
        $parts[] = 'المدينة: ' . $row['madinah_hotel'];
    }
    return implode(' — ', $parts);
}

/** Room types priced by at least one hotel, so empty columns are not shown. */
function hotel_room_types(array $hotels): array
{
    return array_values(array_filter(ROOM_TYPES, static function ($type) use ($hotels) {
        foreach ($hotels as $hotel) {
            if (isset($hotel['prices'][$type])) {
                return true;
            }
        }
        return false;
    }));
}

function hotels_price_range(array $hotels): array
{
    $all = [];
    foreach ($hotels as $hotel) {
        $all = array_merge($all, array_values($hotel['prices']));
    }
    return $all ? [min($all), max($all)] : [];
}

/** Text shown on cards: the admin's price text, else "from <cheapest room of any hotel>". */
function display_price(array $package, array $hotels): string
{
    $text = loc($package, 'price');
    if (trim($text) !== '') {
        return $text;
    }
    $range = hotels_price_range($hotels);
    return $range ? s('pkg_from_label', 'ابتداءً من') . ' ' . money($range[0]) : '';
}

/** Card hotel text: the admin's text, else the Makkah hotel of the first option. */
function display_hotel(array $package, array $hotels): string
{
    $text = loc($package, 'hotel');
    if (trim($text) !== '' || !$hotels) {
        return $text;
    }
    return $hotels[0]['makkah_hotel'] !== '' ? $hotels[0]['makkah_hotel'] : $hotels[0]['madinah_hotel'];
}

function month_name(int $time): string
{
    $months = [
        'ar' => ['يناير', 'فبراير', 'مارس', 'أبريل', 'ماي', 'يونيو', 'يوليوز', 'غشت', 'شتنبر', 'أكتوبر', 'نونبر', 'دجنبر'],
        'fr' => ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'],
        'en' => ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
    ];
    return $months[current_lang()][(int) date('n', $time) - 1];
}

function format_day(string $ymd): string
{
    $time = strtotime($ymd);
    if ($time === false) {
        return $ymd;
    }
    $month = month_name($time);
    return current_lang() === 'en' ? $month . ' ' . date('j, Y', $time) : date('j', $time) . ' ' . $month . ' ' . date('Y', $time);
}

function format_month(string $ymd): string
{
    $time = strtotime($ymd);
    return $time === false ? $ymd : month_name($time) . ' ' . date('Y', $time);
}

/** Departures keyed by "Y-m", each with a localized month label. */
function departures_by_month(array $departures): array
{
    $months = [];
    foreach ($departures as $departure) {
        $key = substr($departure['date'], 0, 7);
        $months[$key] = $months[$key] ?? ['label' => format_month($departure['date']), 'items' => []];
        $months[$key]['items'][] = $departure;
    }
    return $months;
}

/** Splits "title\nbody\n\ntitle\nbody" into day blocks. */
function parse_blocks(string $text): array
{
    $blocks = [];
    foreach (preg_split('/\R\s*\R/u', trim($text)) ?: [] as $chunk) {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/u', $chunk) ?: []), 'strlen'));
        if ($lines) {
            $blocks[] = ['title' => array_shift($lines), 'body' => implode("\n", $lines)];
        }
    }
    return $blocks;
}

/** Day blocks in the current language; a missing translated day falls back to Arabic. */
function localized_blocks(array $row, string $column): array
{
    $arabic = parse_blocks((string) ($row[$column] ?? ''));
    if (current_lang() === default_lang()) {
        return $arabic;
    }
    $translated = parse_blocks((string) ($row[$column . '_' . current_lang()] ?? ''));
    foreach ($arabic as $index => $block) {
        if (isset($translated[$index])) {
            $arabic[$index] = $translated[$index];
        }
    }
    return $arabic;
}

function localized_lines(array $row, string $column): array
{
    $arabic = split_text_lines((string) ($row[$column] ?? ''));
    if (current_lang() === default_lang()) {
        return array_values(array_filter($arabic, 'strlen'));
    }
    $translated = split_text_lines((string) ($row[$column . '_' . current_lang()] ?? ''));
    $lines = [];
    foreach ($arabic as $index => $line) {
        if ($line === '') {
            continue;
        }
        $lines[] = ($translated[$index] ?? '') !== '' ? $translated[$index] : $line;
    }
    return $lines;
}

function split_text_lines(string $text): array
{
    return array_map('trim', preg_split('/\R/u', $text) ?: []);
}

function gallery_list(array $row): array
{
    $list = json_decode((string) ($row['gallery'] ?? ''), true);
    return is_array($list) ? array_values(array_filter($list, 'is_string')) : [];
}

/** Upcoming departures grouped by package id. */
function upcoming_departures(array $packageIds): array
{
    $packageIds = array_values(array_unique(array_map('intval', $packageIds)));
    if (!$packageIds) {
        return [];
    }
    $marks = implode(', ', array_fill(0, count($packageIds), '?'));
    $grouped = [];
    foreach (rows("SELECT * FROM package_departures WHERE package_id IN ($marks) AND depart_date >= CURDATE() ORDER BY depart_date, id", $packageIds) as $row) {
        $grouped[(int) $row['package_id']][] = [
            'id' => (int) $row['id'],
            'date' => $row['depart_date'],
            'return' => (string) $row['return_date'],
            'day' => format_day($row['depart_date']),
            'label' => format_day($row['depart_date']) . ($row['return_date'] ? ' → ' . format_day($row['return_date']) : ''),
            'seats' => $row['seats_left'] === null ? null : max(0, (int) $row['seats_left']),
        ];
    }
    return $grouped;
}

/** Null seats means the agency does not track seats for that date. */
function departure_full(array $departure): bool
{
    return $departure['seats'] !== null && $departure['seats'] < 1;
}

function seats_text(?int $seats): string
{
    if ($seats === null) {
        return s('prog_seats_open', 'متاح');
    }
    return $seats > 0 ? str_replace('{n}', (string) $seats, s('prog_seats_left', '{n}')) : s('prog_full', 'مكتمل');
}

/** Booking-related data for one package, shared by cards and the program page. */
function package_booking_data(array $row, array $departures, array $hotels): array
{
    return [
        'id' => (int) $row['id'],
        'slug' => (string) $row['slug'],
        'url' => program_url((string) $row['slug']),
        'title' => loc($row, 'title'),
        'hotels' => array_map(static function ($hotel) {
            $rooms = [];
            foreach ($hotel['prices'] as $type => $price) {
                $rooms[] = ['type' => $type, 'label' => room_labels()[$type], 'price' => $price, 'priceText' => money($price)];
            }
            return ['id' => $hotel['id'], 'label' => $hotel['label'], 'rooms' => $rooms];
        }, $hotels),
        'departures' => array_map(static function ($d) {
            return $d + ['seatsText' => seats_text($d['seats']), 'full' => departure_full($d)];
        }, $departures),
    ];
}

function load_program(string $slug, bool $includeHidden): ?array
{
    $statement = db()->prepare('SELECT * FROM packages WHERE slug = ?' . ($includeHidden ? '' : ' AND is_active = 1'));
    $statement->execute([$slug]);
    $row = $statement->fetch();
    if (!$row) {
        return null;
    }
    $features = array_map(static function ($feature) {
        return loc($feature, 'feature_text');
    }, rows('SELECT * FROM package_features WHERE package_id = ? ORDER BY sort_order, id', [$row['id']]));
    $category = rows('SELECT * FROM package_categories WHERE slug = ?', [$row['category']]);
    $departures = upcoming_departures([(int) $row['id']])[(int) $row['id']] ?? [];
    $hotels = package_hotels([(int) $row['id']])[(int) $row['id']] ?? [];

    return [
        'row' => $row,
        'title' => loc($row, 'title'),
        'badge' => loc($row, 'category_badge'),
        'category' => $category ? loc($category[0], 'label') : '',
        'hotel' => display_hotel($row, $hotels),
        'distance' => loc($row, 'distance'),
        'description' => loc($row, 'description'),
        'price' => display_price($row, $hotels),
        'image' => asset_url((string) $row['image']),
        'gallery' => array_map('asset_url', gallery_list($row)),
        'features' => $features,
        'itinerary' => localized_blocks($row, 'itinerary'),
        'includes' => localized_lines($row, 'includes'),
        'excludes' => localized_lines($row, 'excludes'),
        'hotels' => $hotels,
        'departures' => $departures,
        'duration' => (string) $row['duration_days'],
        'booking' => package_booking_data($row, $departures, $hotels),
    ];
}

function unique_package_slug(string $slug, int $exceptId): string
{
    $base = $slug !== '' ? $slug : 'program';
    $candidate = $base;
    $check = db()->prepare('SELECT COUNT(*) FROM packages WHERE slug = ? AND id <> ?');
    for ($i = 2; ; $i++) {
        $check->execute([$candidate, $exceptId]);
        if ((int) $check->fetchColumn() === 0) {
            return $candidate;
        }
        $candidate = substr($base, 0, 74) . '-' . $i;
    }
}

function departures_for_admin(int $packageId): array
{
    if ($packageId < 1) {
        return [];
    }
    return rows('SELECT id, depart_date, return_date, seats_left FROM package_departures WHERE package_id = ? ORDER BY depart_date, id', [$packageId]);
}

function posted_list(array $source, string $key): array
{
    return array_values(array_map(static function ($value) {
        return is_scalar($value) ? trim((string) $value) : '';
    }, (array) ($source[$key] ?? [])));
}

/**
 * Validated rows from dep_id[], dep_date[], dep_return[], dep_seats[].
 * Fully empty rows are skipped; empty seats means seats are not tracked.
 */
function parse_departures(array $source): array
{
    $ids = posted_list($source, 'dep_id');
    $dates = posted_list($source, 'dep_date');
    $returns = posted_list($source, 'dep_return');
    $seats = posted_list($source, 'dep_seats');
    $valid = static function (string $value): ?string {
        $date = DateTime::createFromFormat('!Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value ? $value : null;
    };

    $rows = [];
    foreach ($dates as $index => $rawDate) {
        $rawReturn = $returns[$index] ?? '';
        $rawSeats = $seats[$index] ?? '';
        if ($rawDate === '' && $rawReturn === '' && $rawSeats === '') {
            continue;
        }
        $where = 'الموعد رقم ' . (count($rows) + 1) . ': ';
        if ($rawDate === '' || $rawReturn === '') {
            throw new RuntimeException($where . 'أدخل تاريخ الانطلاق وتاريخ العودة معاً.');
        }
        $date = $valid($rawDate);
        $return = $valid($rawReturn);
        if ($date === null || $return === null) {
            throw new RuntimeException($where . 'تاريخ غير صحيح.');
        }
        if ($return < $date) {
            throw new RuntimeException($where . 'تاريخ العودة قبل تاريخ الانطلاق.');
        }
        if ($rawSeats !== '' && !ctype_digit($rawSeats)) {
            throw new RuntimeException($where . 'عدد المقاعد يجب أن يكون رقماً، أو اتركه فارغاً.');
        }
        $rows[] = ['id' => (int) ($ids[$index] ?? 0), 'date' => $date, 'return' => $return, 'seats' => $rawSeats === '' ? null : min(9999, (int) $rawSeats)];
    }
    return $rows;
}

function save_departures(int $packageId, array $rows): void
{
    $keep = [];
    $update = db()->prepare('UPDATE package_departures SET depart_date = ?, return_date = ?, seats_left = ? WHERE id = ? AND package_id = ?');
    $insert = db()->prepare('INSERT INTO package_departures (package_id, depart_date, return_date, seats_left) VALUES (?, ?, ?, ?)');
    foreach ($rows as $row) {
        if ($row['id'] > 0) {
            $update->execute([$row['date'], $row['return'], $row['seats'], $row['id'], $packageId]);
            $keep[] = $row['id'];
        } else {
            $insert->execute([$packageId, $row['date'], $row['return'], $row['seats']]);
            $keep[] = (int) db()->lastInsertId();
        }
    }
    delete_rows_except('package_departures', $packageId, $keep);
}

function delete_rows_except(string $table, int $packageId, array $keep): void
{
    if ($keep) {
        $marks = implode(', ', array_fill(0, count($keep), '?'));
        db()->prepare("DELETE FROM `{$table}` WHERE package_id = ? AND id NOT IN ($marks)")->execute([$packageId, ...$keep]);
    } else {
        db()->prepare("DELETE FROM `{$table}` WHERE package_id = ?")->execute([$packageId]);
    }
}

function hotel_columns(): array
{
    $columns = [];
    foreach (array_keys(HOTEL_TEXT_FIELDS) as $column) {
        $columns[] = $column;
        foreach (extra_languages() as $lang) {
            $columns[] = $column . '_' . $lang;
        }
    }
    foreach (ROOM_TYPES as $type) {
        $columns[] = 'price_' . $type;
    }
    return $columns;
}

function hotels_for_admin(int $packageId): array
{
    return $packageId < 1 ? [] : rows('SELECT * FROM package_hotels WHERE package_id = ? ORDER BY sort_order, id', [$packageId]);
}

/**
 * Validated rows from hotel_id[], hotel_<column>[] and hotel_price_<room>[].
 * A used row needs a Makkah or Madinah hotel (in Arabic) and at least one room price.
 */
function parse_hotels(array $source): array
{
    $ids = posted_list($source, 'hotel_id');
    $lists = [];
    foreach (hotel_columns() as $column) {
        $lists[$column] = posted_list($source, 'hotel_' . $column);
    }
    $rows = [];
    foreach (array_keys($ids) as $index) {
        $row = [];
        foreach ($lists as $column => $values) {
            $row[$column] = $values[$index] ?? '';
        }
        if (implode('', $row) === '') {
            continue;
        }
        $where = 'الفندق رقم ' . (count($rows) + 1) . ': ';
        if ($row['makkah_hotel'] === '' && $row['madinah_hotel'] === '') {
            throw new RuntimeException($where . 'أدخل اسم فندق مكة أو فندق المدينة بالعربية.');
        }
        foreach (HOTEL_TEXT_FIELDS as $column => $max) {
            foreach (array_merge([$column], array_map(static function ($lang) use ($column) {
                return $column . '_' . $lang;
            }, extra_languages())) as $key) {
                if (mb_strlen($row[$key]) > $max) {
                    throw new RuntimeException($where . 'نص أطول من ' . $max . ' حرفاً.');
                }
            }
        }
        foreach (ROOM_TYPES as $type) {
            $raw = $row['price_' . $type];
            if ($raw !== '' && !ctype_digit($raw)) {
                throw new RuntimeException($where . 'السعر يجب أن يكون رقماً بالدرهم بدون فواصل.');
            }
            $row['price_' . $type] = min(10000000, (int) $raw);
        }
        if (!room_prices($row)) {
            throw new RuntimeException($where . 'أدخل سعر نوع غرفة واحد على الأقل.');
        }
        $row['id'] = (int) ($ids[$index] ?? 0);
        $rows[] = $row;
    }
    return $rows;
}

function save_hotels(int $packageId, array $rows): void
{
    $columns = hotel_columns();
    $sets = implode(', ', array_map(static function ($column) {
        return "`{$column}` = ?";
    }, $columns));
    $update = db()->prepare("UPDATE package_hotels SET {$sets}, sort_order = ? WHERE id = ? AND package_id = ?");
    $insert = db()->prepare('INSERT INTO package_hotels (package_id, `' . implode('`, `', $columns) . '`, sort_order) VALUES (?, ' . implode(', ', array_fill(0, count($columns), '?')) . ', ?)');
    $existing = array_map('intval', array_column(rows('SELECT id FROM package_hotels WHERE package_id = ?', [$packageId]), 'id'));
    $keep = [];
    foreach ($rows as $position => $row) {
        $values = array_map(static function ($column) use ($row) {
            return $row[$column];
        }, $columns);
        $order = ($position + 1) * 10;
        if (in_array($row['id'], $existing, true)) {
            $update->execute([...$values, $order, $row['id'], $packageId]);
            $keep[] = $row['id'];
        } else {
            $insert->execute([$packageId, ...$values, $order]);
            $keep[] = (int) db()->lastInsertId();
        }
    }
    delete_rows_except('package_hotels', $packageId, $keep);
}

/** Gallery from the textarea (one path per line) plus any newly uploaded files. */
function collect_gallery(array $source): string
{
    $paths = array_values(array_filter(split_text_lines((string) ($source['gallery'] ?? '')), 'strlen'));
    $paths = array_merge($paths, save_uploads('file_gallery'));
    return json_encode(array_slice(array_values(array_unique($paths)), 0, 30), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

/** Copies a package with its features, hotels and gallery as a hidden draft. Departures are not copied. */
function duplicate_package(int $id): int
{
    $statement = db()->prepare('SELECT * FROM packages WHERE id = ?');
    $statement->execute([$id]);
    $row = $statement->fetch();
    if (!$row) {
        throw new RuntimeException('البرنامج غير موجود.');
    }
    unset($row['id']);
    $row['title'] = str_limit($row['title'] . ' (نسخة)', 255);
    foreach (extra_languages() as $lang) {
        if (trim((string) $row['title_' . $lang]) !== '') {
            $row['title_' . $lang] = str_limit($row['title_' . $lang] . ' (copie)', 255);
        }
    }
    $row['slug'] = unique_package_slug(($row['slug'] ?: 'program') . '-copy', 0);
    $row['is_active'] = 0;
    $row['sort_order'] = next_sort_order('packages');

    db()->beginTransaction();
    try {
        $columns = array_keys($row);
        $names = implode(', ', array_map(static function ($column) {
            return "`{$column}`";
        }, $columns));
        db()->prepare("INSERT INTO packages ({$names}) VALUES (" . implode(', ', array_fill(0, count($columns), '?')) . ')')->execute(array_values($row));
        $newId = (int) db()->lastInsertId();
        db()->prepare('INSERT INTO package_features (package_id, feature_text, feature_text_fr, feature_text_en, sort_order)
            SELECT ?, feature_text, feature_text_fr, feature_text_en, sort_order FROM package_features WHERE package_id = ?')->execute([$newId, $id]);
        $hotelColumns = '`' . implode('`, `', hotel_columns()) . '`, sort_order';
        db()->prepare("INSERT INTO package_hotels (package_id, {$hotelColumns}) SELECT ?, {$hotelColumns} FROM package_hotels WHERE package_id = ?")->execute([$newId, $id]);
        db()->commit();
    } catch (Throwable $e) {
        db()->rollBack();
        throw $e;
    }
    return $newId;
}
