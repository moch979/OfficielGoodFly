<?php

const GOOGLE_REVIEWS_TTL = 21600;
const GOOGLE_REVIEWS_RETRY = 900;

function google_reviews_configured(): bool
{
    return s('google_reviews_on', '0') === '1' && trim(s('google_api_key')) !== '' && trim(s('google_place_id')) !== '';
}

function google_reviews_cache_file(string $lang): string
{
    $dir = storage_path('cache');
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return $dir . '/google-reviews-' . $lang . '.json';
}

function google_reviews_clear_cache(): void
{
    foreach (array_keys(languages()) as $lang) {
        @unlink(google_reviews_cache_file($lang));
    }
}

/**
 * Rating summary and latest reviews of the agency on Google for the current
 * language, or null when the feature is off or Google has never answered.
 * Each language is fetched at most once every GOOGLE_REVIEWS_TTL seconds so
 * visitors never wait on Google and the monthly free quota is not exceeded.
 */
function google_reviews(): ?array
{
    if (!google_reviews_configured()) {
        return null;
    }
    $lang = current_lang();
    $file = google_reviews_cache_file($lang);
    $placeId = trim(s('google_place_id'));
    $withReviews = s('google_reviews_cards', '0') === '1';
    $cached = is_file($file) ? json_decode((string) @file_get_contents($file), true) : null;
    if (!is_array($cached) || ($cached['place'] ?? '') !== $placeId || (bool) ($cached['withReviews'] ?? false) !== $withReviews) {
        $cached = null;
    }
    if ($cached && time() - (int) ($cached['fetched_at'] ?? 0) < GOOGLE_REVIEWS_TTL) {
        return $cached['data'] ?? null;
    }

    try {
        $data = google_reviews_fetch($placeId, trim(s('google_api_key')), $lang, $withReviews);
        $cached = ['place' => $placeId, 'withReviews' => $withReviews, 'fetched_at' => time(), 'data' => $data];
    } catch (Throwable $e) {
        log_error('Google reviews: ' . $e->getMessage());
        $cached = [
            'place' => $placeId,
            'withReviews' => $withReviews,
            'fetched_at' => time() - GOOGLE_REVIEWS_TTL + GOOGLE_REVIEWS_RETRY,
            'data' => $cached['data'] ?? null,
        ];
    }
    @file_put_contents($file, json_encode($cached, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    return $cached['data'];
}

/**
 * Calls the Places API (New) once and keeps only what the site displays.
 * Review texts are billed at a higher rate, so they are requested only when shown.
 */
function google_reviews_fetch(string $placeId, string $apiKey, string $lang, bool $withReviews = false): array
{
    if (!preg_match('/^[A-Za-z0-9_\-]{10,300}$/', $placeId)) {
        throw new RuntimeException('معرّف المكان (Place ID) غير صحيح.');
    }
    if (!function_exists('curl_init')) {
        throw new RuntimeException('إضافة cURL غير مفعّلة في PHP على الخادم.');
    }
    $curl = curl_init('https://places.googleapis.com/v1/places/' . rawurlencode($placeId) . '?languageCode=' . rawurlencode($lang));
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_TIMEOUT => 6,
        CURLOPT_HTTPHEADER => [
            'X-Goog-Api-Key: ' . $apiKey,
            'X-Goog-FieldMask: rating,userRatingCount,googleMapsUri,googleMapsLinks' . ($withReviews ? ',reviews' : ''),
        ],
    ]);
    $body = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $error = curl_error($curl);
    curl_close($curl);
    if ($body === false) {
        throw new RuntimeException('تعذر الاتصال بـ Google: ' . $error);
    }
    $json = json_decode((string) $body, true);
    if ($status !== 200 || !is_array($json)) {
        $message = is_array($json) ? (string) ($json['error']['message'] ?? '') : '';
        throw new RuntimeException('ردّ Google بالرمز ' . $status . ($message !== '' ? ': ' . $message : ''));
    }

    $reviews = [];
    foreach ($json['reviews'] ?? [] as $review) {
        $text = trim((string) ($review['originalText']['text'] ?? $review['text']['text'] ?? ''));
        if ($text === '') {
            continue;
        }
        $reviews[] = [
            'author' => (string) ($review['authorAttribution']['displayName'] ?? ''),
            'authorUrl' => (string) ($review['authorAttribution']['uri'] ?? ''),
            'photo' => (string) ($review['authorAttribution']['photoUri'] ?? ''),
            'rating' => max(1, min(5, (int) ($review['rating'] ?? 5))),
            'text' => $text,
            'textLang' => (string) ($review['originalText']['languageCode'] ?? ''),
            'time' => (string) ($review['relativePublishTimeDescription'] ?? ''),
        ];
    }
    $links = $json['googleMapsLinks'] ?? [];
    return [
        'rating' => round((float) ($json['rating'] ?? 0), 1),
        'count' => (int) ($json['userRatingCount'] ?? 0),
        'url' => (string) ($links['reviewsUri'] ?? $json['googleMapsUri'] ?? ''),
        'writeUrl' => (string) ($links['writeAReviewUri'] ?? 'https://search.google.com/local/writereview?placeid=' . rawurlencode($placeId)),
        'reviews' => $reviews,
    ];
}

function initials_of(string $name): string
{
    $parts = preg_split('/\s+/u', trim($name)) ?: [];
    $letters = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $letters .= mb_substr($part, 0, 1);
    }
    return mb_strtoupper($letters);
}
