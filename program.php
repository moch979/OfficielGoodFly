<?php

require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/site_data.php';
require __DIR__ . '/includes/public_layout.php';

set_lang((string) ($_GET['lang'] ?? default_lang()));
$slug = (string) ($_GET['slug'] ?? '');
$isAdmin = current_admin() !== null;
$program = preg_match('/^[a-z0-9\-]{1,120}$/', $slug) ? load_program($slug, $isAdmin) : null;
$lang = current_lang();

$langLinks = [];
$alternates = [];
foreach (languages() as $code => $info) {
    $langLinks[$code] = $program ? program_url($slug, $code) : lang_url($code);
    $alternates[$code] = $program ? program_absolute_url($slug, $code) : site_url() . '/';
}

if (!$program) {
    http_response_code(404);
    public_header([
        'title' => t('not_found_title') . ' | ' . agency_name(),
        'description' => s('meta_description'),
        'canonical' => site_url() . '/',
        'langLinks' => $langLinks,
        'noindex' => true,
    ]);
    ?>
    <main id="main" class="gf-section max-w-3xl mx-auto px-4 text-center">
        <h1 class="font-serif-title text-4xl font-bold text-brand-green mb-4"><?= h(t('not_found_title')) ?></h1>
        <p class="text-brand-slate mb-8"><?= h(t('not_found_text')) ?></p>
        <a href="<?= h(lang_url($lang)) ?>#packages" class="gold-bg-gradient text-brand-darkgreen font-bold text-sm px-8 py-3 rounded-full inline-block"><?= e('prog_back') ?></a>
    </main>
    <?php
    public_footer([], false);
    exit;
}

$row = $program['row'];
$booking = $program['booking'];
$rooms = $program['rooms'];
$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'TouristTrip',
    'name' => $program['title'],
    'description' => $program['description'],
    'image' => absolute_url((string) $row['image']),
    'url' => program_absolute_url($slug),
    'provider' => ['@type' => 'TravelAgency', 'name' => agency_name(), 'url' => site_url() . '/'],
];
if ($rooms) {
    $jsonLd['offers'] = ['@type' => 'AggregateOffer', 'priceCurrency' => 'MAD', 'lowPrice' => min($rooms), 'highPrice' => max($rooms)];
}
if ($program['itinerary']) {
    $jsonLd['itinerary'] = ['@type' => 'ItemList', 'itemListElement' => array_map(static function ($day, $index) {
        return ['@type' => 'ListItem', 'position' => $index + 1, 'name' => $day['title']];
    }, $program['itinerary'], array_keys($program['itinerary']))];
}

public_header([
    'title' => $program['title'] . ' | ' . agency_name(),
    'description' => mb_strimwidth($program['description'], 0, 300, '…'),
    'canonical' => program_absolute_url($slug),
    'alternates' => (int) $row['is_active'] === 1 ? $alternates : [],
    'langLinks' => $langLinks,
    'ogImage' => absolute_url((string) $row['image']),
    'ogType' => 'article',
    'preloadImage' => $program['image'],
    'noindex' => (int) $row['is_active'] !== 1,
    'jsonLd' => $jsonLd,
]);
?>
    <main id="main">
        <?php if ((int) $row['is_active'] !== 1): ?>
        <div class="bg-amber-100 text-amber-900 text-sm text-center py-3 px-4"><?= e('prog_hidden_notice') ?></div>
        <?php endif; ?>

        <section class="relative bg-brand-darkgreen text-white overflow-hidden">
            <img src="<?= h($program['image']) ?>" alt="" class="absolute inset-0 w-full h-full object-cover opacity-40">
            <div class="absolute inset-0 bg-gradient-to-t from-brand-darkgreen via-brand-darkgreen/70 to-brand-darkgreen/30"></div>
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-10 pb-14 sm:pt-14 sm:pb-20">
                <a href="<?= h(lang_url($lang)) ?>#packages" class="inline-flex items-center gap-2 text-xs text-brand-gold hover:text-white transition mb-6">
                    <i class="fa-solid <?= lang_dir() === 'rtl' ? 'fa-arrow-right' : 'fa-arrow-left' ?>" aria-hidden="true"></i><?= e('prog_back') ?>
                </a>
                <div class="flex flex-wrap items-center gap-2 mb-4">
                    <?php if ($program['badge'] !== ''): ?><span class="bg-brand-gold text-brand-darkgreen text-xs font-bold px-3 py-1 rounded-full"><?= h($program['badge']) ?></span><?php endif; ?>
                    <?php if ($program['category'] !== '' && $program['category'] !== $program['badge']): ?><span class="border border-brand-gold/40 text-brand-gold text-xs px-3 py-1 rounded-full"><?= h($program['category']) ?></span><?php endif; ?>
                </div>
                <h1 class="font-serif-title text-3xl sm:text-5xl font-bold leading-tight max-w-4xl mb-6"><?= h($program['title']) ?></h1>
                <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-brand-ivory/90 mb-8">
                    <?php if ($program['hotel'] !== ''): ?><span><i class="fa-solid fa-hotel text-brand-gold me-2" aria-hidden="true"></i><?= h($program['hotel']) ?></span><?php endif; ?>
                    <?php if ($program['distance'] !== ''): ?><span><i class="fa-solid fa-location-dot text-brand-gold me-2" aria-hidden="true"></i><?= h($program['distance']) ?></span><?php endif; ?>
                    <?php if ($program['duration'] !== ''): ?><span><i class="fa-regular fa-clock text-brand-gold me-2" aria-hidden="true"></i><?= h($program['duration']) ?> <?= e('calc_days_unit') ?></span><?php endif; ?>
                </div>
                <div class="flex flex-wrap items-center gap-4">
                    <?php if ($program['price'] !== ''): ?>
                    <div class="text-2xl font-bold text-white"><?= h($program['price']) ?></div>
                    <?php endif; ?>
                    <button type="button" @click="openPackageById(<?= (int) $row['id'] ?>)" class="gold-bg-gradient text-brand-darkgreen font-extrabold text-sm px-8 py-3.5 rounded-full hover:brightness-110 transition shadow-gold-soft"><?= e('prog_book_cta') ?></button>
                </div>
            </div>
        </section>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 grid grid-cols-1 lg:grid-cols-3 gap-10">
            <div class="lg:col-span-2 space-y-12">
                <section>
                    <p class="text-brand-slate leading-relaxed text-base"><?= nl2br(h($program['description'])) ?></p>
                    <?php if ($program['features']): ?>
                    <ul class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-6">
                        <?php foreach ($program['features'] as $feature): ?>
                        <li class="flex items-center gap-2 bg-white border border-brand-gold/30 rounded-xl px-4 py-3 text-sm text-brand-green"><i class="fa-solid fa-circle-check text-brand-gold" aria-hidden="true"></i><?= h($feature) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </section>

                <?php if ($program['itinerary']): ?>
                <section aria-labelledby="itinerary-title">
                    <h2 id="itinerary-title" class="font-serif-title text-3xl font-bold text-brand-green mb-6"><?= e('prog_itinerary_title') ?></h2>
                    <ol class="relative border-s-2 border-brand-gold/40 ms-4 space-y-8">
                        <?php foreach ($program['itinerary'] as $index => $day): ?>
                        <li class="ps-8 relative">
                            <span class="absolute -start-[1.15rem] top-0 w-9 h-9 rounded-full gold-bg-gradient text-brand-darkgreen font-bold text-sm flex items-center justify-center shadow" aria-hidden="true"><?= $index + 1 ?></span>
                            <span class="text-[11px] font-bold uppercase tracking-widest text-brand-gold"><?= e('prog_day_label') ?> <?= $index + 1 ?></span>
                            <h3 class="font-bold text-lg text-brand-green mt-1 mb-2"><?= h($day['title']) ?></h3>
                            <?php if ($day['body'] !== ''): ?><p class="text-sm text-brand-slate leading-relaxed"><?= nl2br(h($day['body'])) ?></p><?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ol>
                </section>
                <?php endif; ?>

                <?php if ($program['includes'] || $program['excludes']): ?>
                <section class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php if ($program['includes']): ?>
                    <div class="bg-white rounded-2xl border border-brand-gold/30 p-6">
                        <h2 class="font-bold text-lg text-brand-green mb-4"><?= e('prog_includes_title') ?></h2>
                        <ul class="space-y-2 text-sm text-brand-slate">
                            <?php foreach ($program['includes'] as $item): ?>
                            <li class="flex items-start gap-2"><i class="fa-solid fa-check text-emerald-600 mt-1" aria-hidden="true"></i><span><?= h($item) ?></span></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    <?php if ($program['excludes']): ?>
                    <div class="bg-white rounded-2xl border border-brand-gold/30 p-6">
                        <h2 class="font-bold text-lg text-brand-green mb-4"><?= e('prog_excludes_title') ?></h2>
                        <ul class="space-y-2 text-sm text-brand-slate">
                            <?php foreach ($program['excludes'] as $item): ?>
                            <li class="flex items-start gap-2"><i class="fa-solid fa-xmark text-red-600 mt-1" aria-hidden="true"></i><span><?= h($item) ?></span></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </section>
                <?php endif; ?>

                <?php if ($program['gallery']): ?>
                <section aria-labelledby="gallery-title" x-data="{ open: null }">
                    <h2 id="gallery-title" class="font-serif-title text-3xl font-bold text-brand-green mb-6"><?= e('prog_gallery_title') ?></h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        <?php foreach ($program['gallery'] as $index => $image): ?>
                        <button type="button" @click="open = <?= json_for_script($image) ?>" class="block rounded-xl overflow-hidden aspect-[4/3] group">
                            <img src="<?= h($image) ?>" alt="<?= h($program['title']) ?> <?= $index + 1 ?>" loading="lazy" decoding="async" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <div x-show="open" x-cloak @click="open = null" @keydown.escape.window="open = null" class="fixed inset-0 z-50 bg-black/85 flex items-center justify-center p-4" role="dialog" aria-modal="true">
                        <img :src="open" alt="" class="max-w-full max-h-[90vh] rounded-xl shadow-2xl">
                        <button type="button" class="absolute top-4 end-4 text-white text-3xl" aria-label="<?= h(t('close')) ?>"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
                    </div>
                </section>
                <?php endif; ?>
            </div>

            <aside class="space-y-6 lg:sticky lg:top-28 self-start">
                <?php if ($rooms): ?>
                <div class="bg-white rounded-2xl shadow-lux border border-brand-gold/30 p-6">
                    <h2 class="font-bold text-lg text-brand-green mb-4"><?= e('prog_rooms_title') ?></h2>
                    <ul class="divide-y divide-gray-100">
                        <?php foreach ($rooms as $type => $price): ?>
                        <li class="flex items-center justify-between py-3 text-sm">
                            <span class="text-brand-slate"><?= h(room_labels()[$type]) ?></span>
                            <span class="text-end"><strong class="text-brand-green text-base"><?= h(money($price)) ?></strong> <span class="text-[11px] text-gray-500"><?= e('prog_per_person') ?></span></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <div class="bg-white rounded-2xl shadow-lux border border-brand-gold/30 p-6">
                    <h2 class="font-bold text-lg text-brand-green mb-4"><?= e('prog_departures_title') ?></h2>
                    <?php if ($program['departures']): ?>
                    <ul class="space-y-3">
                        <?php foreach ($program['departures'] as $departure): $full = $departure['seats'] < 1; ?>
                        <li class="rounded-xl border <?= $full ? 'border-gray-200 bg-gray-50' : 'border-brand-gold/40' ?> p-4">
                            <div class="font-bold text-sm text-brand-green"><i class="fa-regular fa-calendar text-brand-gold me-2" aria-hidden="true"></i><?= h(format_day($departure['date'])) ?></div>
                            <?php if ($departure['return'] !== ''): ?>
                            <div class="text-xs text-gray-500 mt-1"><?= e('prog_return_label') ?>: <?= h(format_day($departure['return'])) ?></div>
                            <?php endif; ?>
                            <div class="flex items-center justify-between gap-3 mt-3">
                                <span class="text-xs font-bold <?= $full ? 'text-red-700' : ($departure['seats'] <= 5 ? 'text-amber-700' : 'text-emerald-700') ?>"><?= h(seats_text($departure['seats'])) ?></span>
                                <?php if (!$full): ?>
                                <button type="button" @click="openPackageById(<?= (int) $row['id'] ?>, <?= (int) $departure['id'] ?>)" class="gold-bg-gradient text-brand-darkgreen font-bold text-xs px-4 py-2 rounded-full"><?= e('pkg_button') ?></button>
                                <?php endif; ?>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php else: ?>
                    <p class="text-sm text-brand-slate"><?= e('prog_no_departures') ?></p>
                    <?php endif; ?>
                    <button type="button" @click="openPackageById(<?= (int) $row['id'] ?>)" class="w-full mt-5 gold-bg-gradient text-brand-darkgreen font-extrabold text-sm py-3 rounded-full"><?= e('prog_book_cta') ?></button>
                    <a href="<?= h(wa_link(s('wa_header_message') . "\n" . $program['title'])) ?>" target="_blank" rel="noopener" class="w-full mt-3 inline-flex items-center justify-center gap-2 border border-emerald-600 text-emerald-700 font-bold text-sm py-3 rounded-full hover:bg-emerald-50 transition"><i class="fa-brands fa-whatsapp text-lg" aria-hidden="true"></i><?= h(t('whatsapp')) ?></a>
                </div>
            </aside>
        </div>
    </main>
<?php
public_footer(['packages' => [$booking]], false);
