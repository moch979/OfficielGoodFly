<?php

const ROOM_TYPES = ['double', 'triple', 'quad'];

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
    return ['double' => s('room_double', 'ثنائية'), 'triple' => s('room_triple', 'ثلاثية'), 'quad' => s('room_quad', 'رباعية')];
}

/** Room types with a price, cheapest last is irrelevant: keeps double/triple/quad order. */
function room_prices(array $package): array
{
    $prices = [];
    foreach (ROOM_TYPES as $type) {
        $price = (int) ($package['price_' . $type] ?? 0);
        if ($price > 0) {
            $prices[$type] = $price;
        }
    }
    return $prices;
}

/** Text shown on cards: the admin's price text, else "from <cheapest room>". */
function display_price(array $package): string
{
    $text = loc($package, 'price');
    if (trim($text) !== '') {
        return $text;
    }
    $prices = room_prices($package);
    return $prices ? s('pkg_from_label', 'ابتداءً من') . ' ' . money(min($prices)) : '';
}

function format_day(string $ymd): string
{
    $time = strtotime($ymd);
    if ($time === false) {
        return $ymd;
    }
    $months = [
        'ar' => ['يناير', 'فبراير', 'مارس', 'أبريل', 'ماي', 'يونيو', 'يوليوز', 'غشت', 'شتنبر', 'أكتوبر', 'نونبر', 'دجنبر'],
        'fr' => ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'],
        'en' => ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
    ];
    $month = $months[current_lang()][(int) date('n', $time) - 1];
    return current_lang() === 'en' ? $month . ' ' . date('j, Y', $time) : date('j', $time) . ' ' . $month . ' ' . date('Y', $time);
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
            'label' => format_day($row['depart_date']) . ($row['return_date'] ? ' → ' . format_day($row['return_date']) : ''),
            'seats' => max(0, (int) $row['seats_left']),
        ];
    }
    return $grouped;
}

function seats_text(int $seats): string
{
    return $seats > 0 ? str_replace('{n}', (string) $seats, s('prog_seats_left', '{n}')) : s('prog_full', 'مكتمل');
}

/** Booking-related data for one package, shared by cards and the program page. */
function package_booking_data(array $row, array $departures): array
{
    $rooms = [];
    foreach (room_prices($row) as $type => $price) {
        $rooms[] = ['type' => $type, 'label' => room_labels()[$type], 'price' => $price, 'priceText' => money($price)];
    }
    return [
        'id' => (int) $row['id'],
        'slug' => (string) $row['slug'],
        'url' => program_url((string) $row['slug']),
        'title' => loc($row, 'title'),
        'rooms' => $rooms,
        'departures' => array_map(static function ($d) {
            return $d + ['seatsText' => seats_text($d['seats'])];
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

    return [
        'row' => $row,
        'title' => loc($row, 'title'),
        'badge' => loc($row, 'category_badge'),
        'category' => $category ? loc($category[0], 'label') : '',
        'hotel' => loc($row, 'hotel'),
        'distance' => loc($row, 'distance'),
        'description' => loc($row, 'description'),
        'price' => display_price($row),
        'image' => asset_url((string) $row['image']),
        'gallery' => array_map('asset_url', gallery_list($row)),
        'features' => $features,
        'itinerary' => localized_blocks($row, 'itinerary'),
        'includes' => localized_lines($row, 'includes'),
        'excludes' => localized_lines($row, 'excludes'),
        'rooms' => room_prices($row),
        'departures' => $departures,
        'duration' => (string) $row['duration_days'],
        'booking' => package_booking_data($row, $departures),
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

/** Rows posted as dep_id[], dep_date[], dep_return[], dep_seats[]. Rows left empty are removed. */
function save_departures(int $packageId, array $source): void
{
    $ids = (array) ($source['dep_id'] ?? []);
    $dates = (array) ($source['dep_date'] ?? []);
    $returns = (array) ($source['dep_return'] ?? []);
    $seats = (array) ($source['dep_seats'] ?? []);
    $valid = static function ($value): ?string {
        $value = trim((string) $value);
        $date = DateTime::createFromFormat('!Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value ? $value : null;
    };

    $keep = [];
    $update = db()->prepare('UPDATE package_departures SET depart_date = ?, return_date = ?, seats_left = ? WHERE id = ? AND package_id = ?');
    $insert = db()->prepare('INSERT INTO package_departures (package_id, depart_date, return_date, seats_left) VALUES (?, ?, ?, ?)');
    foreach ($dates as $index => $rawDate) {
        if (trim((string) $rawDate) === '') {
            continue;
        }
        $date = $valid($rawDate);
        if ($date === null) {
            throw new RuntimeException('تاريخ انطلاق غير صحيح: ' . $rawDate);
        }
        $return = trim((string) ($returns[$index] ?? '')) === '' ? null : $valid($returns[$index]);
        if (trim((string) ($returns[$index] ?? '')) !== '' && $return === null) {
            throw new RuntimeException('تاريخ عودة غير صحيح: ' . $returns[$index]);
        }
        if ($return !== null && $return < $date) {
            throw new RuntimeException('تاريخ العودة قبل تاريخ الانطلاق في موعد ' . $date . '.');
        }
        $count = max(0, min(9999, (int) ($seats[$index] ?? 0)));
        $id = (int) ($ids[$index] ?? 0);
        if ($id > 0) {
            $update->execute([$date, $return, $count, $id, $packageId]);
            $keep[] = $id;
        } else {
            $insert->execute([$packageId, $date, $return, $count]);
            $keep[] = (int) db()->lastInsertId();
        }
    }
    if ($keep) {
        $marks = implode(', ', array_fill(0, count($keep), '?'));
        db()->prepare("DELETE FROM package_departures WHERE package_id = ? AND id NOT IN ($marks)")->execute([$packageId, ...$keep]);
    } else {
        db()->prepare('DELETE FROM package_departures WHERE package_id = ?')->execute([$packageId]);
    }
}

/** Gallery from the textarea (one path per line) plus any newly uploaded files. */
function collect_gallery(array $source): string
{
    $paths = array_values(array_filter(split_text_lines((string) ($source['gallery'] ?? '')), 'strlen'));
    $paths = array_merge($paths, save_uploads('file_gallery'));
    return json_encode(array_slice(array_values(array_unique($paths)), 0, 30), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

/** Copies a package with its features and gallery as a hidden draft. Departures are not copied. */
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
        db()->commit();
    } catch (Throwable $e) {
        db()->rollBack();
        throw $e;
    }
    return $newId;
}
