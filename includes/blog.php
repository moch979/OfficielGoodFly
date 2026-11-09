<?php

function blog_path(string $slug = '', ?string $lang = null): string
{
    $lang = $lang ?? current_lang();
    return ($lang === default_lang() ? '' : $lang . '/') . 'blog' . ($slug !== '' ? '/' . rawurlencode($slug) : '');
}

function blog_url(string $slug = '', ?string $lang = null): string
{
    return base_path() . '/' . blog_path($slug, $lang);
}

function blog_absolute_url(string $slug = '', ?string $lang = null): string
{
    return site_url() . '/' . blog_path($slug, $lang);
}

function blog_image(array $row): string
{
    $image = trim((string) ($row['image'] ?? ''));
    return asset_url($image !== '' ? $image : s('og_image', 'assets/img/haram-2022.jpg'));
}

/** Post fields for the visitor, in the current language. */
function blog_post_view(array $row): array
{
    $body = loc($row, 'body');
    $excerpt = trim(loc($row, 'excerpt'));
    if ($excerpt === '') {
        $excerpt = mb_strimwidth(trim(preg_replace('/\s+/u', ' ', str_replace('## ', '', $body)) ?? ''), 0, 220, '…');
    }
    return [
        'row' => $row,
        'slug' => (string) $row['slug'],
        'url' => blog_url((string) $row['slug']),
        'title' => loc($row, 'title'),
        'excerpt' => $excerpt,
        'body' => $body,
        'image' => blog_image($row),
        'date' => format_day((string) $row['published_at']),
        'iso_date' => (string) $row['published_at'],
        'minutes' => max(1, (int) ceil(count(preg_split('/\s+/u', trim($body)) ?: []) / 200)),
    ];
}

function load_blog_posts(?int $limit = null, int $exceptId = 0): array
{
    $sql = 'SELECT * FROM blog_posts WHERE is_active = 1 AND published_at <= CURDATE() AND id <> ? ORDER BY published_at DESC, sort_order, id DESC';
    if ($limit !== null) {
        $sql .= ' LIMIT ' . max(1, $limit);
    }
    return array_map('blog_post_view', rows($sql, [$exceptId]));
}

function load_blog_post(string $slug, bool $includeHidden): ?array
{
    $sql = 'SELECT * FROM blog_posts WHERE slug = ?' . ($includeHidden ? '' : ' AND is_active = 1 AND published_at <= CURDATE()');
    $found = rows($sql, [$slug]);
    return $found ? blog_post_view($found[0]) : null;
}

/** Paragraphs separated by a blank line; a line starting with "## " is a subheading. */
function blog_body_html(string $body): string
{
    $html = '';
    foreach (preg_split('/\R\s*\R/u', trim($body)) ?: [] as $chunk) {
        $chunk = trim($chunk);
        if (strpos($chunk, '## ') === 0) {
            $lines = preg_split('/\R/u', $chunk, 2) ?: [$chunk];
            $html .= '<h2 class="font-serif-title text-2xl sm:text-3xl font-bold text-brand-green mt-10 mb-4">' . h(trim(substr($lines[0], 3))) . '</h2>';
            $chunk = trim($lines[1] ?? '');
        }
        if ($chunk !== '') {
            $html .= '<p class="mb-5">' . nl2br(h($chunk)) . '</p>';
        }
    }
    return $html;
}

function unique_blog_slug(string $slug, int $exceptId): string
{
    $base = $slug !== '' ? $slug : 'post';
    $candidate = $base;
    $check = db()->prepare('SELECT COUNT(*) FROM blog_posts WHERE slug = ? AND id <> ?');
    for ($i = 2; ; $i++) {
        $check->execute([$candidate, $exceptId]);
        if ((int) $check->fetchColumn() === 0) {
            return $candidate;
        }
        $candidate = substr($base, 0, 110) . '-' . $i;
    }
}
