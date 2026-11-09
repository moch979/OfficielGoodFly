<?php

require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/site_data.php';
require __DIR__ . '/includes/public_layout.php';

set_lang((string) ($_GET['lang'] ?? default_lang()));
$slug = (string) ($_GET['slug'] ?? '');
$lang = current_lang();
$backIcon = lang_dir() === 'rtl' ? 'fa-arrow-right' : 'fa-arrow-left';
$nextIcon = lang_dir() === 'rtl' ? 'fa-arrow-left' : 'fa-arrow-right';

function blog_card(array $post, string $nextIcon, string $heading = 'h2'): void
{
    ?>
                <article class="bg-white rounded-2xl overflow-hidden shadow-lux hover:shadow-2xl transition duration-500 border border-brand-gold/20 flex flex-col group transform hover:-translate-y-1">
                    <a href="<?= h($post['url']) ?>" class="relative h-56 overflow-hidden block" tabindex="-1" aria-hidden="true">
                        <img src="<?= h($post['image']) ?>" alt="" loading="lazy" decoding="async" class="w-full h-full object-cover group-hover:scale-110 transition duration-700">
                        <div class="absolute inset-0 bg-gradient-to-t from-brand-darkgreen/70 via-transparent to-transparent"></div>
                    </a>
                    <div class="p-6 flex flex-col flex-1">
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-gray-500 mb-3">
                            <time datetime="<?= h($post['iso_date']) ?>"><i class="fa-regular fa-calendar text-brand-gold me-1" aria-hidden="true"></i><?= h($post['date']) ?></time>
                            <span><i class="fa-regular fa-clock text-brand-gold me-1" aria-hidden="true"></i><?= h(str_replace('{n}', (string) $post['minutes'], s('blog_minutes', '{n}'))) ?></span>
                        </div>
                        <<?= $heading ?> class="font-serif-title text-xl font-bold text-brand-green leading-snug mb-3"><a href="<?= h($post['url']) ?>" class="hover:text-brand-gold transition"><?= h($post['title']) ?></a></<?= $heading ?>>
                        <p class="text-sm text-brand-slate leading-relaxed mb-6 flex-1"><?= h($post['excerpt']) ?></p>
                        <a href="<?= h($post['url']) ?>" class="inline-flex items-center gap-2 text-sm font-bold text-brand-green hover:text-brand-gold transition self-start"><?= e('blog_read_more') ?><i class="fa-solid <?= h($nextIcon) ?> text-xs" aria-hidden="true"></i></a>
                    </div>
                </article>
    <?php
}

if ($slug === '') {
    $posts = load_blog_posts();
    $langLinks = [];
    $alternates = [];
    foreach (languages() as $code => $info) {
        $langLinks[$code] = blog_url('', $code);
        $alternates[$code] = blog_absolute_url('', $code);
    }
    public_header([
        'title' => s('blog_title') . ' | ' . agency_name(),
        'description' => s('blog_subtitle'),
        'canonical' => blog_absolute_url(),
        'alternates' => $alternates,
        'langLinks' => $langLinks,
        'ogImage' => absolute_url(s('og_image')),
        'jsonLd' => [
            '@context' => 'https://schema.org',
            '@type' => 'Blog',
            'name' => s('blog_title'),
            'url' => blog_absolute_url(),
            'publisher' => ['@type' => 'TravelAgency', 'name' => agency_name(), 'url' => site_url() . '/'],
        ],
    ]);
    ?>
    <main id="main">
        <section class="relative bg-brand-darkgreen text-white overflow-hidden">
            <div class="absolute inset-0 bg-pattern" aria-hidden="true"></div>
            <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20 text-center">
                <span class="text-brand-gold font-bold text-xs uppercase tracking-widest block mb-3"><?= e('blog_kicker') ?></span>
                <h1 class="font-serif-title text-3xl sm:text-5xl font-bold leading-tight mb-4"><?= e('blog_title') ?></h1>
                <div class="w-16 h-1 bg-brand-gold mx-auto mb-6"></div>
                <p class="text-brand-ivory/85 text-base leading-relaxed"><?= e('blog_subtitle') ?></p>
            </div>
        </section>

        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
            <?php if ($posts): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($posts as $post) { blog_card($post, $nextIcon); } ?>
            </div>
            <?php else: ?>
            <p class="text-center text-brand-slate py-10"><?= e('blog_empty') ?></p>
            <?php endif; ?>
        </section>
    </main>
    <?php
    public_footer([], false);
    exit;
}

$isAdmin = current_admin() !== null;
$post = preg_match('/^[a-z0-9\-]{1,120}$/', $slug) ? load_blog_post($slug, $isAdmin) : null;

$langLinks = [];
$alternates = [];
foreach (languages() as $code => $info) {
    $langLinks[$code] = $post ? blog_url($slug, $code) : blog_url('', $code);
    $alternates[$code] = $post ? blog_absolute_url($slug, $code) : blog_absolute_url('', $code);
}

if (!$post) {
    http_response_code(404);
    public_header([
        'title' => s('blog_not_found') . ' | ' . agency_name(),
        'description' => s('blog_subtitle'),
        'canonical' => blog_absolute_url(),
        'langLinks' => $langLinks,
        'noindex' => true,
    ]);
    ?>
    <main id="main" class="gf-section max-w-3xl mx-auto px-4 text-center">
        <h1 class="font-serif-title text-4xl font-bold text-brand-green mb-8"><?= e('blog_not_found') ?></h1>
        <a href="<?= h(blog_url()) ?>" class="gold-bg-gradient text-brand-darkgreen font-bold text-sm px-8 py-3 rounded-full inline-block"><?= e('blog_back') ?></a>
    </main>
    <?php
    public_footer([], false);
    exit;
}

$row = $post['row'];
$visible = (int) $row['is_active'] === 1 && $row['published_at'] <= date('Y-m-d');
$related = load_blog_posts(3, (int) $row['id']);

public_header([
    'title' => $post['title'] . ' | ' . agency_name(),
    'description' => mb_strimwidth($post['excerpt'], 0, 300, '…'),
    'canonical' => blog_absolute_url($slug),
    'alternates' => $visible ? $alternates : [],
    'langLinks' => $langLinks,
    'ogImage' => absolute_url($post['image']),
    'ogType' => 'article',
    'preloadImage' => $post['image'],
    'noindex' => !$visible,
    'jsonLd' => [
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'headline' => $post['title'],
        'description' => $post['excerpt'],
        'image' => absolute_url($post['image']),
        'datePublished' => $post['iso_date'],
        'inLanguage' => $lang,
        'mainEntityOfPage' => blog_absolute_url($slug),
        'publisher' => ['@type' => 'TravelAgency', 'name' => agency_name(), 'url' => site_url() . '/'],
    ],
]);
?>
    <main id="main">
        <?php if (!$visible): ?>
        <div class="bg-amber-100 text-amber-900 text-sm text-center py-3 px-4"><?= e('blog_hidden_notice') ?></div>
        <?php endif; ?>

        <section class="relative bg-brand-darkgreen text-white overflow-hidden">
            <img src="<?= h($post['image']) ?>" alt="" class="absolute inset-0 w-full h-full object-cover opacity-40">
            <div class="absolute inset-0 bg-gradient-to-t from-brand-darkgreen via-brand-darkgreen/70 to-brand-darkgreen/30"></div>
            <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pt-10 pb-14 sm:pt-14 sm:pb-20">
                <a href="<?= h(blog_url()) ?>" class="inline-flex items-center gap-2 text-xs text-brand-gold hover:text-white transition mb-6">
                    <i class="fa-solid <?= h($backIcon) ?>" aria-hidden="true"></i><?= e('blog_back') ?>
                </a>
                <h1 class="font-serif-title text-3xl sm:text-5xl font-bold leading-tight mb-6"><?= h($post['title']) ?></h1>
                <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-brand-ivory/90">
                    <time datetime="<?= h($post['iso_date']) ?>"><i class="fa-regular fa-calendar text-brand-gold me-2" aria-hidden="true"></i><?= h($post['date']) ?></time>
                    <span><i class="fa-regular fa-clock text-brand-gold me-2" aria-hidden="true"></i><?= h(str_replace('{n}', (string) $post['minutes'], s('blog_minutes', '{n}'))) ?></span>
                </div>
            </div>
        </section>

        <article class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <?php if (trim(loc($row, 'excerpt')) !== ''): ?>
            <p class="text-lg text-brand-green font-medium leading-relaxed border-s-4 border-brand-gold ps-5 mb-10"><?= h($post['excerpt']) ?></p>
            <?php endif; ?>
            <div class="text-brand-slate text-base sm:text-lg leading-loose">
                <?= blog_body_html($post['body']) ?>
            </div>

            <div class="mt-12 rounded-2xl bg-brand-darkgreen text-white p-6 sm:p-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
                <div>
                    <h2 class="font-serif-title text-2xl font-bold text-brand-gold mb-2"><?= e('blog_cta_title') ?></h2>
                    <p class="text-sm text-brand-ivory/85"><?= e('blog_cta_text') ?></p>
                </div>
                <div class="flex flex-wrap gap-3 shrink-0">
                    <button type="button" @click="openConsultationModal()" class="gold-bg-gradient text-brand-darkgreen font-extrabold text-sm px-6 py-3 rounded-full hover:brightness-110 transition shadow-gold-soft"><?= e('btn_consultation') ?></button>
                    <a href="<?= h(wa_link(s('wa_header_message') . "\n" . $post['title'])) ?>" target="_blank" rel="noopener" aria-label="<?= h(t('whatsapp')) ?>" class="bg-emerald-600 hover:bg-emerald-500 text-white w-12 h-12 rounded-full flex items-center justify-center text-xl transition"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i></a>
                </div>
            </div>
        </article>

        <?php if ($related): ?>
        <section class="bg-white border-t border-brand-gold/20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
                <h2 class="font-serif-title text-3xl font-bold text-brand-green text-center mb-10"><?= e('blog_related') ?></h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php foreach ($related as $item) { blog_card($item, $nextIcon, 'h3'); } ?>
                </div>
            </div>
        </section>
        <?php endif; ?>
    </main>
<?php
public_footer([], false);
