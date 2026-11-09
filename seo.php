<?php

require __DIR__ . '/includes/bootstrap.php';

$base = site_url();

if (($_GET['file'] ?? '') === 'sitemap') {
    header('Content-Type: application/xml; charset=utf-8');
    $updated = date('Y-m-d');
    echo '<?xml version="1.0" encoding="UTF-8"?>', "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">', "\n";
    foreach (languages() as $code => $info) {
        echo '  <url>', "\n";
        echo '    <loc>', h($base . '/' . ($code === default_lang() ? '' : $code . '/')), '</loc>', "\n";
        echo '    <lastmod>', $updated, '</lastmod>', "\n";
        foreach (languages() as $alt => $altInfo) {
            echo '    <xhtml:link rel="alternate" hreflang="', h($alt), '" href="', h($base . '/' . ($alt === default_lang() ? '' : $alt . '/')), '"/>', "\n";
        }
        echo '  </url>', "\n";
    }
    foreach (rows('SELECT slug FROM packages WHERE is_active = 1 AND slug IS NOT NULL ORDER BY sort_order, id') as $package) {
        foreach (languages() as $code => $info) {
            echo '  <url>', "\n";
            echo '    <loc>', h(program_absolute_url($package['slug'], $code)), '</loc>', "\n";
            foreach (languages() as $alt => $altInfo) {
                echo '    <xhtml:link rel="alternate" hreflang="', h($alt), '" href="', h(program_absolute_url($package['slug'], $alt)), '"/>', "\n";
            }
            echo '  </url>', "\n";
        }
    }
    $blogPages = array_merge([''], array_column(rows('SELECT slug FROM blog_posts WHERE is_active = 1 AND published_at <= CURDATE() ORDER BY published_at DESC, id DESC'), 'slug'));
    foreach ($blogPages as $slug) {
        foreach (languages() as $code => $info) {
            echo '  <url>', "\n";
            echo '    <loc>', h(blog_absolute_url($slug, $code)), '</loc>', "\n";
            foreach (languages() as $alt => $altInfo) {
                echo '    <xhtml:link rel="alternate" hreflang="', h($alt), '" href="', h(blog_absolute_url($slug, $alt)), '"/>', "\n";
            }
            echo '  </url>', "\n";
        }
    }
    echo '</urlset>', "\n";
    exit;
}

header('Content-Type: text/plain; charset=utf-8');
echo "User-agent: *\n";
echo "Disallow: " . base_path() . "/admin/\n";
echo "Disallow: " . base_path() . "/api/\n";
echo "Disallow: " . base_path() . "/install.php\n";
echo "\nSitemap: " . $base . "/sitemap.xml\n";
