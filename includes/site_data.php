<?php

function load_site(): array
{
    $nav = [];
    foreach (rows('SELECT label_ar, label_en, label_fr, href FROM nav_links WHERE is_active = 1 ORDER BY sort_order, id') as $row) {
        $nav[] = [
            'ar' => $row['label_ar'],
            'en' => $row['label_en'],
            'fr' => $row['label_fr'],
            'href' => $row['href'],
        ];
    }

    $hero = [];
    foreach (rows('SELECT image_url FROM hero_slides WHERE is_active = 1 ORDER BY sort_order, id') as $row) {
        if (trim((string) $row['image_url']) === '') {
            continue;
        }
        $hero[] = ['url' => asset_url($row['image_url'])];
    }

    $categories = [];
    foreach (rows('SELECT slug, label FROM package_categories WHERE is_active = 1 ORDER BY sort_order, id') as $row) {
        $categories[] = ['slug' => $row['slug'], 'label' => $row['label']];
    }

    $packages = [];
    foreach (rows('SELECT * FROM packages WHERE is_active = 1 ORDER BY sort_order, id') as $row) {
        $features = rows('SELECT feature_text FROM package_features WHERE package_id = ? ORDER BY sort_order, id', [$row['id']]);
        $packages[] = [
            'id' => (int) $row['id'],
            'title' => $row['title'],
            'category' => $row['category'],
            'categoryBadge' => $row['category_badge'],
            'hotel' => $row['hotel'],
            'distance' => $row['distance'],
            'description' => $row['description'],
            'price' => $row['price'],
            'image' => asset_url((string) $row['image']),
            'features' => array_column($features, 'feature_text'),
            'hotelClass' => $row['hotel_class'],
            'duration' => (string) $row['duration_days'],
        ];
    }

    $filters = ['trip_type' => [], 'hotel' => [], 'duration' => [], 'booking_type' => []];
    foreach (rows('SELECT group_key, option_value, label FROM filter_options WHERE is_active = 1 ORDER BY sort_order, id') as $row) {
        $group = $row['group_key'];
        if (!isset($filters[$group])) {
            $filters[$group] = [];
        }
        $filters[$group][] = ['value' => $row['option_value'], 'label' => $row['label']];
    }

    $tiers = [];
    foreach (rows('SELECT tier_key, label, price_per_day FROM calc_tiers WHERE is_active = 1 ORDER BY sort_order, id') as $row) {
        $tiers[] = [
            'key' => $row['tier_key'],
            'label' => $row['label'],
            'price' => (int) $row['price_per_day'],
        ];
    }

    $cities = [];
    foreach (rows('SELECT * FROM cities WHERE is_active = 1 ORDER BY sort_order, id') as $row) {
        $features = [];
        foreach (rows('SELECT icon, text FROM city_features WHERE city_id = ? ORDER BY sort_order, id', [$row['id']]) as $feature) {
            $features[] = ['icon' => $feature['icon'], 'text' => $feature['text']];
        }
        $cities[] = [
            'slug' => $row['slug'],
            'tab' => $row['tab_label'],
            'heading' => $row['heading'],
            'image' => asset_url((string) $row['image']),
            'caption' => $row['caption'],
            'features' => $features,
        ];
    }

    $guides = [];
    foreach (rows('SELECT id, icon, title, body FROM guides WHERE is_active = 1 ORDER BY sort_order, id') as $row) {
        $guides[] = [
            'id' => (int) $row['id'],
            'icon' => $row['icon'],
            'title' => $row['title'],
            'body' => $row['body'],
        ];
    }

    $i18n = ['ar' => [], 'en' => [], 'fr' => []];
    foreach (rows('SELECT lang_code, tkey, tvalue FROM translations ORDER BY sort_order, id') as $row) {
        $i18n[$row['lang_code']][$row['tkey']] = $row['tvalue'];
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
        'i18n' => $i18n,
        'stats' => rows('SELECT value_text, label FROM stats WHERE is_active = 1 ORDER BY sort_order, id'),
        'points' => rows('SELECT icon, text FROM about_points WHERE is_active = 1 ORDER BY sort_order, id'),
        'services' => rows('SELECT icon, title, description FROM services WHERE is_active = 1 ORDER BY sort_order, id'),
        'testimonials' => rows('SELECT quote, initials, name, trip_label, stars FROM testimonials WHERE is_active = 1 ORDER BY sort_order, id'),
        'footerQuick' => rows("SELECT label, href FROM footer_links WHERE is_active = 1 AND group_key = 'quick' ORDER BY sort_order, id"),
        'footerLegal' => rows("SELECT label, href FROM footer_links WHERE is_active = 1 AND group_key = 'legal' ORDER BY sort_order, id"),
    ];
}
