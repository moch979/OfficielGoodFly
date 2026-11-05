<?php

function load_site(): array
{
    $nav = [];
    foreach (rows('SELECT label_ar, label_en, label_fr, href FROM nav_links WHERE is_active = 1 ORDER BY sort_order, id') as $row) {
        $label = trim((string) ($row['label_' . current_lang()] ?? ''));
        $nav[] = ['label' => $label !== '' ? $label : $row['label_ar'], 'href' => safe_url($row['href'])];
    }

    $hero = [];
    foreach (rows('SELECT image_url FROM hero_slides WHERE is_active = 1 ORDER BY sort_order, id') as $row) {
        if (trim((string) $row['image_url']) !== '') {
            $hero[] = asset_url($row['image_url']);
        }
    }

    $packages = [];
    $packageRows = rows('SELECT * FROM packages WHERE is_active = 1 ORDER BY sort_order, id');
    $featuresByPackage = [];
    if ($packageRows) {
        $ids = array_column($packageRows, 'id');
        $marks = implode(', ', array_fill(0, count($ids), '?'));
        foreach (rows("SELECT * FROM package_features WHERE package_id IN ($marks) ORDER BY sort_order, id", $ids) as $feature) {
            $featuresByPackage[$feature['package_id']][] = loc($feature, 'feature_text');
        }
    }
    foreach ($packageRows as $row) {
        $packages[] = [
            'id' => (int) $row['id'],
            'title' => loc($row, 'title'),
            'category' => $row['category'],
            'categoryBadge' => loc($row, 'category_badge'),
            'hotel' => loc($row, 'hotel'),
            'distance' => loc($row, 'distance'),
            'description' => loc($row, 'description'),
            'price' => loc($row, 'price'),
            'image' => asset_url((string) $row['image']),
            'features' => $featuresByPackage[$row['id']] ?? [],
            'hotelClass' => $row['hotel_class'],
            'duration' => (string) $row['duration_days'],
        ];
    }

    $usedCategories = array_unique(array_column($packages, 'category'));
    $categories = [];
    foreach (rows('SELECT * FROM package_categories WHERE is_active = 1 ORDER BY sort_order, id') as $row) {
        if (in_array($row['slug'], $usedCategories, true)) {
            $categories[] = ['slug' => $row['slug'], 'label' => loc($row, 'label')];
        }
    }

    $filters = ['trip_type' => [['value' => 'all', 'label' => s('search_all_label', 'الكل')]], 'hotel' => [], 'duration' => [], 'booking_type' => []];
    foreach ($categories as $category) {
        $filters['trip_type'][] = ['value' => $category['slug'], 'label' => $category['label']];
    }
    foreach (rows("SELECT * FROM filter_options WHERE is_active = 1 AND group_key IN ('hotel', 'duration', 'booking_type') ORDER BY sort_order, id") as $row) {
        $filters[$row['group_key']][] = ['value' => $row['option_value'], 'label' => loc($row, 'label')];
    }

    $tiers = [];
    foreach (rows('SELECT * FROM calc_tiers WHERE is_active = 1 ORDER BY sort_order, id') as $row) {
        $tiers[] = ['key' => $row['tier_key'], 'label' => loc($row, 'label'), 'price' => (int) $row['price_per_day']];
    }

    $cities = [];
    $cityRows = rows('SELECT * FROM cities WHERE is_active = 1 ORDER BY sort_order, id');
    $featuresByCity = [];
    if ($cityRows) {
        $ids = array_column($cityRows, 'id');
        $marks = implode(', ', array_fill(0, count($ids), '?'));
        foreach (rows("SELECT * FROM city_features WHERE city_id IN ($marks) ORDER BY sort_order, id", $ids) as $feature) {
            $featuresByCity[$feature['city_id']][] = ['icon' => $feature['icon'], 'text' => loc($feature, 'text')];
        }
    }
    foreach ($cityRows as $row) {
        $cities[] = [
            'slug' => $row['slug'],
            'tab' => loc($row, 'tab_label'),
            'heading' => loc($row, 'heading'),
            'image' => asset_url((string) $row['image']),
            'caption' => loc($row, 'caption'),
            'features' => $featuresByCity[$row['id']] ?? [],
        ];
    }

    $guides = [];
    foreach (rows('SELECT * FROM guides WHERE is_active = 1 ORDER BY sort_order, id') as $row) {
        $guides[] = ['id' => (int) $row['id'], 'icon' => $row['icon'], 'title' => loc($row, 'title'), 'body' => loc($row, 'body')];
    }

    $localize = static function (array $list, array $columns): array {
        return array_map(static function ($row) use ($columns) {
            foreach ($columns as $column) {
                $row[$column] = loc($row, $column);
            }
            return $row;
        }, $list);
    };

    $footer = ['quick' => [], 'legal' => []];
    foreach (rows('SELECT * FROM footer_links WHERE is_active = 1 ORDER BY sort_order, id') as $row) {
        $footer[$row['group_key']][] = ['label' => loc($row, 'label'), 'href' => safe_url($row['href'])];
    }

    return [
        'nav' => $nav,
        'hero' => $hero,
        'categories' => $categories,
        'packages' => $packages,
        'filters' => $filters,
        'tiers' => $tiers,
        'cities' => $cities,
        'guides' => $guides,
        'stats' => $localize(rows('SELECT * FROM stats WHERE is_active = 1 ORDER BY sort_order, id'), ['label']),
        'points' => $localize(rows('SELECT * FROM about_points WHERE is_active = 1 ORDER BY sort_order, id'), ['text']),
        'services' => $localize(rows('SELECT * FROM services WHERE is_active = 1 ORDER BY sort_order, id'), ['title', 'description']),
        'testimonials' => $localize(rows('SELECT * FROM testimonials WHERE is_active = 1 ORDER BY sort_order, id'), ['quote', 'name', 'trip_label']),
        'footerQuick' => $footer['quick'] ?? [],
        'footerLegal' => $footer['legal'] ?? [],
    ];
}
