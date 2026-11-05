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
    echo '</urlset>', "\n";
    exit;
}

header('Content-Type: text/plain; charset=utf-8');
echo "User-agent: *\n";
echo "Disallow: " . base_path() . "/admin/\n";
echo "Disallow: " . base_path() . "/api/\n";
echo "Disallow: " . base_path() . "/install.php\n";
echo "\nSitemap: " . $base . "/sitemap.xml\n";
