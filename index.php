<?php

require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/site_data.php';

set_lang((string) ($_GET['lang'] ?? default_lang()));
if (designer_mode()) {
    use_design_draft();
    header('Cache-Control: no-store');
}

$site = load_site();
$lang = current_lang();
$langs = languages();
$waHeader = wa_link(s('wa_header_message'));
$waFloat = wa_link(s('wa_float_message'));
$pageUrl = static function (string $code): string {
    return lang_url($code) . (designer_mode() ? '?designer=1' : '');
};
$canonical = site_url() . '/' . ($lang === default_lang() ? '' : $lang . '/');
$ogImage = absolute_url(s('og_image') !== '' ? s('og_image') : ($site['hero'][0] ?? ''));
$favicon = s('favicon') !== '' ? asset_url(s('favicon')) : asset_url('assets/img/favicon.svg');
$logoUrl = s('logo_image') !== '' ? asset_url(s('logo_image')) : '';
$logoHeight = max(32, min(96, (int) s('logo_height', '52')));
$logoAlt = s('logo_alt', 'Good Fly Travel');
$cssFile = __DIR__ . '/assets/css/site.css';
$fontFamilies = array_unique([s('body_font', 'Tajawal'), s('title_font', 'Amiri'), 'Montserrat']);
$fontQuery = [
    'Tajawal' => 'family=Tajawal:wght@400;500;700;800',
    'Cairo' => 'family=Cairo:wght@400;600;700;800',
    'Amiri' => 'family=Amiri:wght@400;700',
    'Montserrat' => 'family=Montserrat:wght@600;700;800',
];
$fontUrl = 'https://fonts.googleapis.com/css2?' . implode('&', array_intersect_key($fontQuery, array_flip($fontFamilies))) . '&display=swap';
$telHref = static function (string $phone): string {
    return 'tel:' . preg_replace('/[^\d+]/', '', $phone);
};
$schema = [
    '@context' => 'https://schema.org',
    '@type' => 'TravelAgency',
    'name' => s('about_card_title', 'Good Fly Travel'),
    'url' => $canonical,
    'image' => $ogImage,
    'logo' => $logoUrl !== '' ? absolute_url(s('logo_image')) : null,
    'telephone' => s('footer_phone'),
    'email' => s('footer_email'),
    'address' => ['@type' => 'PostalAddress', 'streetAddress' => s('footer_address'), 'addressCountry' => 'MA'],
];
?>
<!DOCTYPE html>
<html lang="<?= h($lang) ?>" dir="<?= h(lang_dir()) ?>" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e('site_title') ?></title>
    <meta name="description" content="<?= e('meta_description') ?>">
    <?php if (designer_mode()): ?><meta name="robots" content="noindex"><?php endif; ?>
    <link rel="canonical" href="<?= h($canonical) ?>">
    <?php foreach ($langs as $code => $info): ?>
    <link rel="alternate" hreflang="<?= h($code) ?>" href="<?= h(site_url() . '/' . ($code === default_lang() ? '' : $code . '/')) ?>">
    <?php endforeach; ?>
    <link rel="alternate" hreflang="x-default" href="<?= h(site_url() . '/') ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= e('about_card_title', 'Good Fly Travel') ?>">
    <meta property="og:title" content="<?= e('site_title') ?>">
    <meta property="og:description" content="<?= e('meta_description') ?>">
    <meta property="og:url" content="<?= h($canonical) ?>">
    <meta property="og:locale" content="<?= h($langs[$lang]['locale']) ?>">
    <?php if ($ogImage !== ''): ?>
    <meta property="og:image" content="<?= h($ogImage) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <?php endif; ?>
    <meta name="theme-color" content="<?= e('color_green', '#1D3C78') ?>">
    <link rel="icon" href="<?= h($favicon) ?>">
    <?php if (!empty($site['hero'][0])): ?>
    <link rel="preload" as="image" href="<?= h($site['hero'][0]) ?>">
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="<?= h($fontUrl) ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= h(asset_url('assets/css/site.css')) ?>?v=<?= is_file($cssFile) ? filemtime($cssFile) : 0 ?>">
    <script defer src="<?= h(asset_url('assets/js/alpine.min.js')) ?>"></script>
    <style>
        :root {
            --gf-green: <?= color_channels('color_green', '#1D3C78') ?>;
            --gf-darkgreen: <?= color_channels('color_darkgreen', '#0F2350') ?>;
            --gf-gold: <?= color_channels('color_gold', '#F2C14E') ?>;
            --gf-lightgold: <?= color_channels('color_lightgold', '#F8DC8E') ?>;
            --gf-ivory: <?= color_channels('color_ivory', '#F4F8FC') ?>;
            --gf-lightivory: <?= color_channels('color_lightivory', '#FBFDFF') ?>;
            --gf-slate: <?= color_channels('color_slate', '#24324A') ?>;
            --gf-body: <?= gf_font_stack('body_font', 'Tajawal') ?>;
            --gf-title: <?= gf_font_stack('title_font', 'Amiri') ?>;
            --gf-section-y: <?= max(32, min(200, (int) s('section_padding', '96'))) ?>px;
            --gf-hero-min: <?= max(40, min(100, (int) s('hero_min_height', '92'))) ?>vh;
        }
    </style>
    <script type="application/ld+json"><?= json_for_script($schema) ?></script>
</head>
<body x-data="goodFlyApp()" x-init="initApp()" class="antialiased selection:bg-brand-gold selection:text-brand-green">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:start-2 focus:z-[60] focus:bg-white focus:text-brand-green focus:px-4 focus:py-2 focus:rounded-lg"><?= $lang === 'ar' ? 'انتقل إلى المحتوى' : ($lang === 'fr' ? 'Aller au contenu' : 'Skip to content') ?></a>

<?php if (gf_visible('show_topbar')): ?>
    <div data-gf="topbar" class="bg-brand-darkgreen text-brand-gold text-xs py-2.5 px-4 border-b border-brand-gold/20 flex justify-between items-center gap-4 max-w-full overflow-hidden<?= gf_off_class('show_topbar') ?>">
        <div class="hidden md:flex items-center gap-5 mx-auto md:mx-0">
            <span><i class="fa-solid fa-shield-halved text-brand-gold me-1" aria-hidden="true"></i> <?= e('top_license_prefix') ?> <strong><?= e('license_number') ?></strong></span>
            <span class="text-brand-gold/30" aria-hidden="true">|</span>
            <span><i class="fa-solid fa-phone text-xs me-1" aria-hidden="true"></i> <?= e('hotline_label') ?> <a dir="ltr" href="<?= h($telHref(s('phone_display'))) ?>"><strong><?= e('phone_display') ?></strong></a></span>
            <span class="text-brand-gold/30" aria-hidden="true">|</span>
            <a href="mailto:<?= e('top_email') ?>"><i class="fa-solid fa-envelope text-xs me-1" aria-hidden="true"></i> <?= e('top_email') ?></a>
        </div>
        <div class="mx-auto md:mx-0 font-medium tracking-wide text-center"><?= e('announcement') ?></div>
        <?php if (gf_visible('show_langs')): ?>
        <nav data-gf-flag="show_langs" aria-label="<?= h(t('language')) ?>" class="hidden lg:flex items-center gap-3 font-latin text-[11px]<?= gf_off_class('show_langs') ?>">
            <?php foreach ($langs as $code => $info): ?>
            <a href="<?= h($pageUrl($code)) ?>" hreflang="<?= h($code) ?>" lang="<?= h($code) ?>" class="transition px-1 <?= $code === $lang ? 'text-brand-gold font-bold border-b border-brand-gold' : 'text-white/60 hover:text-white' ?>"<?= $code === $lang ? ' aria-current="true"' : '' ?>><?= e('lang_label_' . $code, $info['name']) ?></a>
            <?php endforeach; ?>
        </nav>
        <?php endif; ?>
    </div>
<?php endif; ?>

    <header data-gf="header" class="sticky top-0 z-50 transition-all duration-300" :class="scrolled ? 'bg-brand-green/95 backdrop-blur-md shadow-lux py-3' : 'bg-brand-green/90 backdrop-blur-sm py-4'">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between">
                <?php if ($logoUrl !== ''): ?>
                <a href="#hero" class="gf-logo-badge inline-flex items-center px-3 py-1 transition-transform hover:scale-[1.03]">
                    <img src="<?= h($logoUrl) ?>" alt="<?= h($logoAlt) ?>" style="height: <?= $logoHeight ?>px" class="w-auto">
                </a>
                <?php else: ?>
                <a href="#hero" class="flex items-center gap-3.5 group" aria-label="<?= h($logoAlt) ?>">
                    <div class="w-11 h-11 rounded-full border-2 border-brand-gold flex items-center justify-center bg-brand-darkgreen group-hover:border-white transition-all duration-300 shadow-md">
                        <i class="fa-solid <?= e('logo_icon', 'fa-plane-departure') ?> text-brand-gold text-lg group-hover:scale-110 transition-transform" aria-hidden="true"></i>
                    </div>
                    <div class="flex flex-col">
                        <div class="flex items-center gap-1.5" dir="ltr">
                            <span class="font-latin text-2xl font-black tracking-wider text-white"><?= e('logo_word') ?><span class="text-brand-gold"><?= e('logo_accent') ?></span></span>
                            <span class="text-[10px] bg-brand-gold/20 text-brand-gold px-1.5 py-0.5 rounded font-bold uppercase"><?= e('logo_badge') ?></span>
                        </div>
                        <span class="text-[9px] uppercase tracking-widest text-brand-gold/90 font-medium -mt-1"><?= e('logo_tagline') ?></span>
                    </div>
                </a>
                <?php endif; ?>

                <nav class="hidden lg:flex items-center gap-6 text-sm font-medium" aria-label="<?= h(t('menu')) ?>">
                    <?php foreach ($site['nav'] as $link): ?>
                    <a href="<?= h($link['href']) ?>" class="text-white/90 hover:text-brand-gold transition-colors py-1 relative after:content-[''] after:absolute after:bottom-0 after:start-0 after:w-0 after:h-0.5 after:bg-brand-gold hover:after:w-full after:transition-all"><?= h($link['label']) ?></a>
                    <?php endforeach; ?>
                </nav>

                <div class="hidden lg:flex items-center gap-3">
                    <a href="<?= h($waHeader) ?>" target="_blank" rel="noopener" aria-label="<?= h(t('whatsapp')) ?>" class="bg-emerald-600/90 hover:bg-emerald-600 text-white p-2.5 rounded-full transition shadow-md flex items-center justify-center">
                        <i class="fa-brands fa-whatsapp text-lg" aria-hidden="true"></i>
                    </a>
                    <button type="button" @click="openConsultationModal()" class="gold-bg-gradient text-brand-darkgreen font-extrabold text-xs uppercase tracking-wider px-6 py-3 rounded-full hover:brightness-110 transition shadow-gold-soft flex items-center gap-2 transform hover:-translate-y-0.5">
                        <i class="fa-solid fa-calendar-check" aria-hidden="true"></i>
                        <span><?= e('btn_consultation') ?></span>
                    </button>
                </div>

                <button type="button" @click="mobileMenuOpen = !mobileMenuOpen" class="lg:hidden text-white p-2" aria-controls="mobile-menu" :aria-expanded="mobileMenuOpen.toString()" aria-label="<?= h(t('menu')) ?>">
                    <i class="fa-solid text-2xl" :class="mobileMenuOpen ? 'fa-xmark' : 'fa-bars'" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <div id="mobile-menu" x-show="mobileMenuOpen" x-cloak x-transition.opacity class="lg:hidden bg-brand-darkgreen border-b border-brand-gold/20 px-6 py-6 space-y-4 shadow-2xl">
            <?php if (gf_visible('show_langs')): ?>
            <div data-gf-flag="show_langs" class="flex justify-between items-center pb-3 border-b border-white/10 text-xs text-white/70<?= gf_off_class('show_langs') ?>">
                <span><?= e('lang_choose_label') ?></span>
                <div class="flex gap-3 font-latin">
                    <?php foreach ($langs as $code => $info): ?>
                    <a href="<?= h($pageUrl($code)) ?>" hreflang="<?= h($code) ?>" lang="<?= h($code) ?>" class="<?= $code === $lang ? 'text-brand-gold font-bold' : '' ?>"><?= e('lang_label_' . $code, $info['name']) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php foreach ($site['nav'] as $link): ?>
            <a href="<?= h($link['href']) ?>" @click="mobileMenuOpen = false" class="block text-white font-medium hover:text-brand-gold transition"><?= h($link['label']) ?></a>
            <?php endforeach; ?>
            <div class="pt-4 border-t border-white/10">
                <button type="button" @click="mobileMenuOpen = false; openConsultationModal()" class="w-full gold-bg-gradient text-brand-darkgreen font-bold text-center py-3 rounded-full text-sm"><?= e('btn_consultation') ?></button>
            </div>
        </div>
    </header>

    <main id="main" class="gf-flow">
<?php if (gf_visible('show_hero')): ?>
    <section id="hero" data-gf="hero" class="gf-hero relative flex items-center justify-center overflow-hidden bg-brand-darkgreen<?= gf_off_class('show_hero') ?>"<?= gf_order_attr('hero') ?>>
        <div class="absolute inset-0 z-0" aria-hidden="true">
            <template x-for="(img, idx) in heroImages" :key="idx">
                <div x-show="currentHeroBg === idx" x-transition:enter="transition ease-out duration-1000" x-transition:enter-start="opacity-0 scale-105" x-transition:enter-end="opacity-100 scale-100" class="absolute inset-0 bg-cover bg-center bg-no-repeat" :style="heroSeen.includes(idx) ? `background-image: url('${img}')` : ''"></div>
            </template>
            <div class="absolute inset-0 bg-gradient-to-t from-brand-darkgreen via-brand-green/80 to-brand-darkgreen/60"></div>
            <div class="absolute inset-0 bg-pattern"></div>
        </div>
        <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-white py-20">
            <div class="inline-flex items-center gap-2 px-5 py-2 rounded-full border border-brand-gold/40 bg-brand-green/60 backdrop-blur-md mb-8 shadow-lg">
                <i class="fa-solid fa-award text-brand-gold text-sm" aria-hidden="true"></i>
                <span class="text-brand-gold text-xs font-bold tracking-wider uppercase"><?= e('hero_badge') ?></span>
            </div>
            <h1 class="font-serif-title text-4xl sm:text-6xl md:text-7xl font-bold leading-tight mb-6">
                <?= e('hero_title') ?><br>
                <span class="gold-gradient-text"><?= e('hero_highlight') ?></span>
            </h1>
            <p class="max-w-2xl mx-auto text-base sm:text-xl text-brand-ivory/90 leading-relaxed font-light mb-10"><?= nl('hero_subtitle') ?></p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-16">
                <a href="<?= h(safe_url(s('hero_cta_primary_href', '#packages'))) ?>" class="w-full sm:w-auto gold-bg-gradient text-brand-darkgreen font-extrabold text-sm px-9 py-4 rounded-full hover:shadow-gold-soft hover:scale-105 transition duration-300"><?= e('hero_cta_primary') ?></a>
                <button type="button" @click="openConsultationModal()" class="w-full sm:w-auto bg-brand-green/70 hover:bg-brand-green border border-brand-gold/40 text-white font-bold text-sm px-9 py-4 rounded-full backdrop-blur-md transition duration-300"><?= e('hero_cta_secondary') ?></button>
            </div>
            <?php if (gf_visible('show_hero_search')): ?>
            <form @submit.prevent="applyQuickFilter()" data-gf-flag="show_hero_search" class="bg-brand-green/90 backdrop-blur-xl border border-brand-gold/30 rounded-2xl p-4 sm:p-6 shadow-2xl max-w-4xl mx-auto text-start<?= gf_off_class('show_hero_search') ?>">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <label for="qf-type" class="block text-xs text-brand-gold font-bold mb-1"><?= e('search_label_type') ?></label>
                        <select id="qf-type" x-model="quickFilter.type" class="w-full bg-brand-darkgreen/80 border border-brand-gold/20 rounded-lg px-3 py-2.5 text-xs text-white focus:outline-none focus:border-brand-gold">
                            <template x-for="opt in filters.trip_type" :key="opt.value"><option :value="opt.value" x-text="opt.label"></option></template>
                        </select>
                    </div>
                    <div>
                        <label for="qf-hotel" class="block text-xs text-brand-gold font-bold mb-1"><?= e('search_label_hotel') ?></label>
                        <select id="qf-hotel" x-model="quickFilter.hotel" class="w-full bg-brand-darkgreen/80 border border-brand-gold/20 rounded-lg px-3 py-2.5 text-xs text-white focus:outline-none focus:border-brand-gold">
                            <template x-for="opt in filters.hotel" :key="opt.value"><option :value="opt.value" x-text="opt.label"></option></template>
                        </select>
                    </div>
                    <div>
                        <label for="qf-duration" class="block text-xs text-brand-gold font-bold mb-1"><?= e('search_label_duration') ?></label>
                        <select id="qf-duration" x-model="quickFilter.duration" class="w-full bg-brand-darkgreen/80 border border-brand-gold/20 rounded-lg px-3 py-2.5 text-xs text-white focus:outline-none focus:border-brand-gold">
                            <template x-for="opt in filters.duration" :key="opt.value"><option :value="opt.value" x-text="opt.label"></option></template>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full gold-bg-gradient text-brand-darkgreen font-bold text-xs py-2.5 rounded-lg hover:brightness-110 transition flex items-center justify-center gap-2">
                            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                            <span><?= e('search_button') ?></span>
                        </button>
                    </div>
                </div>
            </form>
            <?php endif; ?>
        </div>
        <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex items-center gap-2 z-10" x-show="heroImages.length > 1">
            <template x-for="(img, idx) in heroImages" :key="'dot-' + idx">
                <button type="button" @click="showHero(idx)" class="h-1.5 rounded-full transition-all duration-300" :class="currentHeroBg === idx ? 'w-8 bg-brand-gold' : 'w-2 bg-white/40'" :aria-label="'<?= h(t('slide')) ?> ' + (idx + 1)" :aria-current="(currentHeroBg === idx).toString()"></button>
            </template>
        </div>
    </section>
<?php endif; ?>

<?php if (gf_visible('show_stats')): ?>
    <section data-gf="stats" class="bg-brand-darkgreen py-10 border-y border-brand-gold/20 text-brand-gold<?= gf_off_class('show_stats') ?>"<?= gf_order_attr('stats') ?>>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <?php foreach ($site['stats'] as $stat): ?>
                <div class="p-4">
                    <div class="font-serif-title text-3xl sm:text-4xl font-bold text-white mb-1" dir="ltr"><?= h($stat['value_text']) ?></div>
                    <div class="text-xs sm:text-sm text-brand-gold/80 font-medium"><?= h($stat['label']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php if (gf_visible('show_about')): ?>
    <section id="about" data-gf="about" class="gf-section bg-white border-b border-brand-gold/20<?= gf_off_class('show_about') ?>"<?= gf_order_attr('about') ?>>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <span class="text-brand-gold font-bold text-xs uppercase tracking-widest block mb-2"><?= e('about_kicker') ?></span>
                    <h2 class="font-serif-title text-3xl sm:text-4xl font-bold text-brand-green mb-6 leading-snug"><?= e('about_title') ?></h2>
                    <div class="text-brand-slate text-sm sm:text-base leading-relaxed mb-6 font-light"><?= raw_html('about_body') ?></div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs font-semibold text-brand-green mb-8">
                        <?php foreach ($site['points'] as $point): ?>
                        <div class="flex items-center gap-2 p-3 bg-brand-ivory rounded-xl border border-brand-gold/30">
                            <i class="<?= h($point['icon']) ?> text-brand-gold text-lg" aria-hidden="true"></i>
                            <span><?= h($point['text']) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="relative">
                    <div class="rounded-3xl overflow-hidden shadow-lux border-2 border-brand-gold/30">
                        <img src="<?= h(asset_url(s('about_image'))) ?>" alt="<?= e('about_image_alt') ?>" loading="lazy" decoding="async" class="w-full h-[380px] object-cover">
                    </div>
                    <div class="absolute -bottom-6 -start-6 bg-brand-darkgreen text-white p-6 rounded-2xl border border-brand-gold/40 shadow-xl hidden sm:block max-w-xs">
                        <div class="text-brand-gold font-bold text-lg mb-1"><?= e('about_card_title') ?></div>
                        <div class="text-xs text-brand-ivory/80"><?= e('about_card_text') ?></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php if (gf_visible('show_packages')): ?>
    <section id="packages" data-gf="packages" class="gf-section bg-brand-ivory relative<?= gf_off_class('show_packages') ?>"<?= gf_order_attr('packages') ?>>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <span class="text-brand-gold font-bold text-xs uppercase tracking-widest block mb-2"><?= e('pkg_kicker') ?></span>
                <h2 class="font-serif-title text-3xl sm:text-5xl font-bold text-brand-green mb-4"><?= e('pkg_title') ?></h2>
                <div class="w-16 h-1 bg-brand-gold mx-auto mb-6"></div>
                <p class="text-brand-slate text-base leading-relaxed"><?= nl('pkg_subtitle') ?></p>
            </div>
            <div class="flex justify-center mb-12 overflow-x-auto py-2">
                <div class="inline-flex p-1.5 rounded-full bg-white shadow-md border border-brand-gold/30" role="tablist">
                    <button type="button" role="tab" :aria-selected="(activeTab === 'all').toString()" @click="selectTab('all')" :class="activeTab === 'all' ? 'gold-bg-gradient text-brand-darkgreen font-bold' : 'text-brand-green hover:text-brand-gold'" class="px-6 py-2.5 rounded-full text-xs sm:text-sm transition duration-300 whitespace-nowrap"><?= e('pkg_tab_all') ?></button>
                    <template x-for="cat in categories" :key="cat.slug">
                        <button type="button" role="tab" :aria-selected="(activeTab === cat.slug).toString()" @click="selectTab(cat.slug)" :class="activeTab === cat.slug ? 'gold-bg-gradient text-brand-darkgreen font-bold' : 'text-brand-green hover:text-brand-gold'" class="px-6 py-2.5 rounded-full text-xs sm:text-sm transition duration-300 whitespace-nowrap" x-text="cat.label"></button>
                    </template>
                </div>
            </div>
            <p x-show="filteredPackages.length === 0" x-cloak class="text-center text-brand-slate mb-8" role="status"><?= e('pkg_empty') ?></p>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <template x-for="pkg in filteredPackages" :key="pkg.id">
                    <article class="bg-white rounded-2xl overflow-hidden shadow-lux hover:shadow-2xl transition duration-500 border border-brand-gold/20 flex flex-col group transform hover:-translate-y-1">
                        <div class="relative h-64 overflow-hidden">
                            <img :src="pkg.image" :alt="pkg.title" loading="lazy" decoding="async" class="w-full h-full object-cover group-hover:scale-110 transition duration-700">
                            <div class="absolute inset-0 bg-gradient-to-t from-brand-darkgreen/80 via-transparent to-transparent"></div>
                            <div class="absolute top-4 start-4 bg-brand-darkgreen/90 text-brand-gold text-xs font-semibold px-3 py-1.5 rounded-full border border-brand-gold/30 backdrop-blur-sm">
                                <i class="fa-solid fa-hotel me-1" aria-hidden="true"></i> <span x-text="pkg.hotel"></span>
                            </div>
                            <div class="absolute top-4 end-4 bg-brand-gold text-brand-darkgreen text-xs font-bold px-3 py-1 rounded-full"><span x-text="pkg.categoryBadge"></span></div>
                            <div class="absolute bottom-4 start-4 text-white" x-show="pkg.distance"><p class="text-xs text-brand-gold/90"><i class="fa-solid fa-location-dot me-1" aria-hidden="true"></i> <span x-text="pkg.distance"></span></p></div>
                        </div>
                        <div class="p-6 flex-1 flex flex-col justify-between">
                            <div>
                                <h3 class="font-serif-title text-2xl font-bold text-brand-green mb-2 group-hover:text-brand-gold transition" x-text="pkg.title"></h3>
                                <p class="text-xs text-gray-600 mb-4 line-clamp-2" x-text="pkg.description"></p>
                                <ul class="space-y-2 mb-6 text-xs text-brand-slate">
                                    <template x-for="(feat, fi) in pkg.features" :key="fi"><li class="flex items-center gap-2"><i class="fa-solid fa-circle-check text-brand-gold text-sm" aria-hidden="true"></i><span x-text="feat"></span></li></template>
                                </ul>
                            </div>
                            <div class="pt-4 border-t border-gray-100 flex items-center justify-between mt-auto gap-3">
                                <div>
                                    <span class="text-[10px] text-gray-500 block"><?= e('pkg_price_label') ?></span>
                                    <span class="font-bold text-xl text-brand-green" x-text="pkg.price"></span>
                                </div>
                                <button type="button" @click="openPackageModal(pkg)" class="gold-bg-gradient text-brand-darkgreen font-bold text-xs px-5 py-2.5 rounded-full hover:brightness-110 transition shadow-sm"><?= e('pkg_button') ?></button>
                            </div>
                        </div>
                    </article>
                </template>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php if (gf_visible('show_services')): ?>
    <section id="services" data-gf="services" class="gf-section bg-brand-darkgreen text-white relative overflow-hidden<?= gf_off_class('show_services') ?>"<?= gf_order_attr('services') ?>>
        <div class="absolute inset-0 bg-pattern opacity-10" aria-hidden="true"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <span class="text-brand-gold font-bold text-xs uppercase tracking-widest block mb-2"><?= e('srv_kicker') ?></span>
                <h2 class="font-serif-title text-3xl sm:text-5xl font-bold mb-4"><?= e('srv_title') ?></h2>
                <div class="w-16 h-1 bg-brand-gold mx-auto mb-6"></div>
                <p class="text-brand-ivory/80 text-base leading-relaxed"><?= nl('srv_subtitle') ?></p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($site['services'] as $service): ?>
                <div class="bg-brand-green/60 border border-brand-gold/20 rounded-2xl p-8 hover:border-brand-gold/60 transition duration-300 group">
                    <div class="w-14 h-14 rounded-2xl bg-brand-darkgreen border border-brand-gold/40 flex items-center justify-center text-brand-gold text-2xl mb-6 group-hover:scale-110 transition duration-300 shadow-lg">
                        <i class="<?= h($service['icon']) ?>" aria-hidden="true"></i>
                    </div>
                    <h3 class="font-serif-title text-xl font-bold mb-3 text-white group-hover:text-brand-gold transition"><?= h($service['title']) ?></h3>
                    <p class="text-xs text-brand-ivory/70 leading-relaxed"><?= nl2br(h($service['description'])) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php if (gf_visible('show_calculator')): ?>
    <section id="calculator" data-gf="calculator" class="gf-section bg-brand-ivory<?= gf_off_class('show_calculator') ?>"<?= gf_order_attr('calculator') ?>>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-3xl shadow-lux p-8 sm:p-12 border border-brand-gold/30 relative overflow-hidden">
                <div class="text-center mb-10">
                    <span class="text-brand-gold font-bold text-xs uppercase tracking-widest block mb-2"><?= e('calc_kicker') ?></span>
                    <h2 class="font-serif-title text-3xl font-bold text-brand-green"><?= e('calc_title') ?></h2>
                    <p class="text-xs text-gray-500 mt-2"><?= e('calc_subtitle') ?></p>
                </div>
                <div class="space-y-8">
                    <div role="group" aria-labelledby="calc-q1">
                        <p id="calc-q1" class="block text-sm font-bold text-brand-green mb-3"><?= e('calc_q1') ?></p>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <template x-for="tier in tiers" :key="tier.key">
                                <button type="button" @click="calc.tier = tier.key; calcCost()" :aria-pressed="(calc.tier === tier.key).toString()" :class="calc.tier===tier.key?'gold-bg-gradient text-brand-darkgreen font-bold border-brand-gold':'bg-gray-50 text-gray-700 border-gray-200'" class="p-3.5 rounded-xl border text-xs text-center transition" x-text="tier.label"></button>
                            </template>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label for="calc-days" class="text-sm font-bold text-brand-green"><?= e('calc_q2') ?></label>
                            <span class="text-sm font-bold text-brand-gold"><span x-text="calc.days"></span> <?= e('calc_days_unit') ?></span>
                        </div>
                        <input id="calc-days" type="range" :min="cfg.daysMin" :max="cfg.daysMax" step="1" x-model="calc.days" @input="calcCost()" class="w-full accent-brand-gold cursor-pointer">
                    </div>
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label for="calc-people" class="text-sm font-bold text-brand-green"><?= e('calc_q3') ?></label>
                            <span class="text-sm font-bold text-brand-gold"><span x-text="calc.people"></span> <?= e('calc_people_unit') ?></span>
                        </div>
                        <input id="calc-people" type="range" :min="cfg.peopleMin" :max="cfg.peopleMax" step="1" x-model="calc.people" @input="calcCost()" class="w-full accent-brand-gold cursor-pointer">
                    </div>
                    <div class="bg-brand-darkgreen text-white p-6 rounded-2xl flex flex-col sm:flex-row items-center justify-between gap-4 border border-brand-gold/40">
                        <div aria-live="polite">
                            <span class="text-xs text-brand-gold block"><?= e('calc_result_label') ?></span>
                            <div class="font-serif-title text-3xl font-bold text-white"><span x-text="calc.totalPrice"></span> <span class="text-sm text-brand-gold"><?= e('calc_currency') ?></span></div>
                            <span class="text-[10px] text-gray-300"><?= e('calc_note') ?></span>
                        </div>
                        <button type="button" @click="openConsultationWithCalc()" class="gold-bg-gradient text-brand-darkgreen font-bold text-xs px-6 py-3.5 rounded-full hover:scale-105 transition shadow-lg"><?= e('calc_cta') ?></button>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php if (gf_visible('show_cities')): ?>
    <section id="cities" data-gf="cities" class="gf-section bg-brand-green text-white relative<?= gf_off_class('show_cities') ?>"<?= gf_order_attr('cities') ?>>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-12">
                <div class="w-full md:w-1/2 space-y-6">
                    <span class="text-brand-gold font-bold text-xs uppercase tracking-widest block"><?= e('city_kicker') ?></span>
                    <h2 class="font-serif-title text-3xl sm:text-5xl font-bold leading-tight"><?= e('city_title') ?></h2>
                    <p class="text-brand-ivory/80 text-sm sm:text-base leading-relaxed font-light"><?= nl('city_intro') ?></p>
                    <div class="flex gap-4 border-b border-brand-gold/20 pb-4" role="tablist">
                        <template x-for="city in cities" :key="city.slug">
                            <button type="button" role="tab" :aria-selected="(cityTab === city.slug).toString()" @click="cityTab = city.slug" :class="cityTab === city.slug ? 'text-brand-gold font-bold border-b-2 border-brand-gold' : 'text-white/60'" class="pb-2 text-sm transition" x-text="city.tab"></button>
                        </template>
                    </div>
                    <div x-show="currentCity" class="space-y-4">
                        <h3 class="font-serif-title text-xl font-bold text-brand-gold" x-text="currentCity ? currentCity.heading : ''"></h3>
                        <ul class="space-y-2 text-xs text-brand-ivory/90">
                            <template x-for="(feature, fi) in (currentCity ? currentCity.features : [])" :key="fi">
                                <li class="flex items-center gap-2"><i :class="feature.icon" class="text-brand-gold" aria-hidden="true"></i><span x-text="feature.text"></span></li>
                            </template>
                        </ul>
                    </div>
                </div>
                <div class="w-full md:w-1/2 relative" x-show="currentCity">
                    <div class="relative rounded-3xl overflow-hidden shadow-2xl border-2 border-brand-gold/30">
                        <img :src="currentCity ? currentCity.image : ''" :alt="currentCity ? currentCity.caption : ''" loading="lazy" decoding="async" class="w-full h-[400px] object-cover transition-all duration-700">
                        <div class="absolute inset-0 bg-gradient-to-t from-brand-darkgreen via-transparent to-transparent"></div>
                        <div class="absolute bottom-6 start-6 end-6 text-center bg-brand-darkgreen/90 backdrop-blur-md py-3 px-4 rounded-xl border border-brand-gold/20">
                            <p class="text-xs text-brand-gold font-bold" x-text="currentCity ? currentCity.caption : ''"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php if (gf_visible('show_guide')): ?>
    <section id="guide" data-gf="guide" class="gf-section bg-brand-darkgreen text-white<?= gf_off_class('show_guide') ?>"<?= gf_order_attr('guide') ?>>
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <span class="text-brand-gold font-bold text-xs uppercase tracking-widest block mb-2"><?= e('guide_kicker') ?></span>
                <h2 class="font-serif-title text-3xl sm:text-5xl font-bold mb-4"><?= e('guide_title') ?></h2>
                <div class="w-16 h-1 bg-brand-gold mx-auto mb-6"></div>
                <p class="text-brand-ivory/80 text-sm"><?= e('guide_subtitle') ?></p>
            </div>
            <div class="space-y-4">
                <template x-for="guide in guides" :key="guide.id">
                    <div class="border border-brand-gold/30 rounded-2xl bg-brand-green/40 overflow-hidden">
                        <button type="button" @click="toggleGuide(guide.id)" :aria-expanded="(activeGuide === guide.id).toString()" class="w-full p-6 text-start font-serif-title text-lg font-bold flex justify-between items-center gap-4 text-brand-gold">
                            <span><i class="me-2" :class="guide.icon" aria-hidden="true"></i> <span x-text="guide.title"></span></span>
                            <i class="fa-solid" :class="activeGuide===guide.id?'fa-chevron-up':'fa-chevron-down'" aria-hidden="true"></i>
                        </button>
                        <div x-show="activeGuide===guide.id" x-cloak class="px-6 pb-6 text-xs text-brand-ivory/80 leading-relaxed border-t border-brand-gold/10 pt-4" x-text="guide.body"></div>
                    </div>
                </template>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php if (gf_visible('show_testimonials')): ?>
    <section data-gf="testimonials" class="gf-section bg-brand-ivory<?= gf_off_class('show_testimonials') ?>"<?= gf_order_attr('testimonials') ?>>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <span class="text-brand-gold font-bold text-xs uppercase tracking-widest block mb-2"><?= e('tst_kicker') ?></span>
                <h2 class="font-serif-title text-3xl sm:text-5xl font-bold text-brand-green mb-4"><?= e('tst_title') ?></h2>
                <div class="w-16 h-1 bg-brand-gold mx-auto mb-6"></div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <?php foreach ($site['testimonials'] as $item): $stars = max(1, min(5, (int) $item['stars'])); ?>
                <figure class="bg-white p-8 rounded-2xl shadow-lux border border-brand-gold/20 flex flex-col justify-between">
                    <div>
                        <div class="flex text-brand-gold mb-4 text-xs" role="img" aria-label="<?= $stars ?>/5">
                            <?php for ($star = 0; $star < $stars; $star++): ?>
                            <i class="fa-solid fa-star" aria-hidden="true"></i>
                            <?php endfor; ?>
                        </div>
                        <blockquote class="text-xs text-brand-slate leading-relaxed mb-6"><?= h($item['quote']) ?></blockquote>
                    </div>
                    <figcaption class="flex items-center gap-3 pt-4 border-t border-gray-100">
                        <div class="w-10 h-10 rounded-full gold-bg-gradient text-brand-darkgreen font-bold flex items-center justify-center" aria-hidden="true"><?= h($item['initials']) ?></div>
                        <div>
                            <div class="font-bold text-sm text-brand-green"><?= h($item['name']) ?></div>
                            <span class="text-[10px] text-gray-500"><?= h($item['trip_label']) ?></span>
                        </div>
                    </figcaption>
                </figure>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>
    </main>

    <footer id="contact" data-gf="footer" class="bg-brand-darkgreen text-white border-t border-brand-gold/20 pt-16 pb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-10 mb-12">
                <div class="space-y-4">
                    <?php if ($logoUrl !== ''): ?>
                    <div class="gf-logo-badge inline-flex px-3 py-1">
                        <img src="<?= h($logoUrl) ?>" alt="<?= h($logoAlt) ?>" loading="lazy" style="height: <?= $logoHeight + 8 ?>px" class="w-auto">
                    </div>
                    <?php else: ?>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full border border-brand-gold flex items-center justify-center bg-brand-green">
                            <i class="fa-solid <?= e('logo_icon', 'fa-plane-departure') ?> text-brand-gold" aria-hidden="true"></i>
                        </div>
                        <span class="font-latin text-2xl font-black text-white" dir="ltr"><?= e('logo_word') ?><span class="text-brand-gold"><?= e('logo_accent') ?></span></span>
                    </div>
                    <?php endif; ?>
                    <p class="text-xs text-brand-ivory/70 leading-relaxed"><?= nl('footer_about') ?></p>
                    <div class="text-[11px] text-brand-gold/80 space-y-1 pt-2">
                        <div><?= e('footer_license_label') ?> <strong><?= e('footer_license_number') ?></strong></div>
                        <div><?= e('footer_cr_label') ?> <strong><?= e('footer_cr_number') ?></strong></div>
                    </div>
                </div>
                <nav aria-label="<?= e('footer_links_title') ?>">
                    <h2 class="font-serif-title text-base font-bold text-brand-gold mb-4"><?= e('footer_links_title') ?></h2>
                    <ul class="space-y-2 text-xs text-brand-ivory/70">
                        <?php foreach ($site['footerQuick'] as $link): ?>
                        <li><a href="<?= h($link['href']) ?>" class="hover:text-brand-gold transition"><?= h($link['label']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </nav>
                <div>
                    <h2 class="font-serif-title text-base font-bold text-brand-gold mb-4"><?= e('footer_contact_title') ?></h2>
                    <ul class="space-y-3 text-xs text-brand-ivory/70">
                        <li class="flex items-start gap-2"><i class="fa-solid fa-location-dot text-brand-gold mt-1" aria-hidden="true"></i><span><?= e('footer_address') ?></span></li>
                        <li class="flex items-center gap-2"><i class="fa-solid fa-phone text-brand-gold" aria-hidden="true"></i><a dir="ltr" href="<?= h($telHref(s('footer_phone'))) ?>"><?= e('footer_phone') ?></a></li>
                        <li class="flex items-center gap-2"><i class="fa-solid fa-envelope text-brand-gold" aria-hidden="true"></i><a href="mailto:<?= e('footer_email') ?>"><?= e('footer_email') ?></a></li>
                    </ul>
                </div>
                <?php if (gf_visible('show_newsletter')): ?>
                <div data-gf-flag="show_newsletter" class="<?= trim(gf_off_class('show_newsletter')) ?>">
                    <h2 class="font-serif-title text-base font-bold text-brand-gold mb-4"><?= e('footer_news_title') ?></h2>
                    <p class="text-xs text-brand-ivory/70 mb-3"><?= e('footer_news_text') ?></p>
                    <form @submit.prevent="subscribe($event)" class="space-y-2">
                        <label for="news-email" class="sr-only"><?= e('footer_news_placeholder') ?></label>
                        <input id="news-email" type="email" name="email" maxlength="180" autocomplete="email" placeholder="<?= e('footer_news_placeholder') ?>" required class="w-full bg-brand-green/80 border border-brand-gold/30 rounded-lg px-3 py-2 text-xs text-white placeholder:text-white/50 focus:outline-none focus:border-brand-gold">
                        <input type="text" name="website" tabindex="-1" autocomplete="off" class="gf-hp" aria-hidden="true">
                        <button type="submit" :disabled="newsSending" class="w-full gold-bg-gradient text-brand-darkgreen font-bold text-xs py-2 rounded-lg hover:brightness-110 transition disabled:opacity-60"><?= e('footer_news_button') ?></button>
                        <p x-show="newsMessage" x-cloak x-text="newsMessage" :class="newsOk ? 'text-emerald-300' : 'text-red-300'" class="text-xs" role="status"></p>
                    </form>
                </div>
                <?php endif; ?>
            </div>
            <div class="pt-8 border-t border-white/10 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-brand-ivory/60">
                <p><?= e('copyright') ?></p>
                <div class="flex flex-wrap justify-center gap-4">
                    <?php foreach ($site['footerLegal'] as $link): ?>
                    <a href="<?= h($link['href']) ?>" class="hover:text-brand-gold"><?= h($link['label']) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </footer>

<?php if (gf_visible('show_whatsapp')): ?>
    <a data-gf="booking" href="<?= h($waFloat) ?>" target="_blank" rel="noopener" aria-label="<?= h(t('whatsapp')) ?>" class="fixed bottom-6 end-6 z-40 bg-emerald-600 hover:bg-emerald-500 text-white w-14 h-14 rounded-full flex items-center justify-center text-2xl shadow-2xl transition hover:scale-110 border-2 border-white<?= gf_off_class('show_whatsapp') ?>">
        <i class="fa-brands fa-whatsapp" aria-hidden="true"></i>
    </a>
<?php endif; ?>

    <div x-show="modalOpen" x-cloak @keydown.escape.window="closeModal()" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
        <div @click.outside="closeModal()" role="dialog" aria-modal="true" aria-labelledby="booking-title" class="bg-white rounded-3xl max-w-xl w-full p-6 sm:p-8 shadow-2xl border border-brand-gold/40 relative overflow-hidden text-start max-h-[92vh] overflow-y-auto">
            <button type="button" @click="closeModal()" class="absolute top-4 end-4 text-gray-400 hover:text-brand-green text-xl" aria-label="<?= h(t('close')) ?>"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
            <div class="mb-6 pe-8">
                <span class="text-brand-gold text-xs font-bold uppercase tracking-widest block"><?= e('modal_kicker') ?></span>
                <h2 id="booking-title" class="font-serif-title text-2xl font-bold text-brand-green" x-text="selectedPackage ? cfg.packagePrefix + selectedPackage.title : cfg.modalTitle"></h2>
                <p class="text-xs text-gray-500 mt-1" x-show="!booked"><?= e('modal_subtitle') ?></p>
            </div>

            <div x-show="booked" x-cloak class="text-center space-y-5 py-4" role="status">
                <div class="w-16 h-16 mx-auto rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-3xl"><i class="fa-solid fa-check" aria-hidden="true"></i></div>
                <p class="text-sm text-brand-green font-bold" x-text="bookedMessage"></p>
                <template x-if="bookedWhatsapp">
                    <div class="space-y-2">
                        <p class="text-xs text-gray-500"><?= e('modal_whatsapp_hint') ?></p>
                        <a :href="bookedWhatsapp" target="_blank" rel="noopener" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm px-6 py-3 rounded-full transition"><i class="fa-brands fa-whatsapp text-lg" aria-hidden="true"></i><?= h(t('continue_whatsapp')) ?></a>
                    </div>
                </template>
                <button type="button" @click="closeModal()" class="text-xs text-gray-500 underline"><?= h(t('close')) ?></button>
            </div>

            <form x-show="!booked" @submit.prevent="submitForm()" class="space-y-4" novalidate>
                <div>
                    <label for="bk-name" class="block text-xs font-bold text-brand-green mb-1"><?= e('modal_name_label') ?></label>
                    <input id="bk-name" type="text" x-model="formData.name" required maxlength="180" autocomplete="name" placeholder="<?= e('modal_name_ph') ?>" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2.5 text-xs focus:outline-none focus:border-brand-gold">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="bk-phone" class="block text-xs font-bold text-brand-green mb-1"><?= e('modal_phone_label') ?></label>
                        <input id="bk-phone" type="tel" dir="ltr" x-model="formData.phone" required maxlength="40" autocomplete="tel" placeholder="<?= e('modal_phone_ph') ?>" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2.5 text-xs text-start focus:outline-none focus:border-brand-gold">
                    </div>
                    <div>
                        <label for="bk-type" class="block text-xs font-bold text-brand-green mb-1"><?= e('modal_type_label') ?></label>
                        <select id="bk-type" x-model="formData.tripType" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2.5 text-xs focus:outline-none focus:border-brand-gold">
                            <template x-if="formData.tripType && !bookingTypes.some(item => item.value === formData.tripType)">
                                <option :value="formData.tripType" x-text="formData.tripType"></option>
                            </template>
                            <template x-for="opt in bookingTypes" :key="opt.value"><option :value="opt.value" x-text="opt.label"></option></template>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="bk-travelers" class="block text-xs font-bold text-brand-green mb-1"><?= e('modal_travelers_label') ?></label>
                        <input id="bk-travelers" type="number" min="1" max="99" x-model="formData.travelers" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2.5 text-xs focus:outline-none focus:border-brand-gold">
                    </div>
                    <div>
                        <label for="bk-date" class="block text-xs font-bold text-brand-green mb-1"><?= e('modal_date_label') ?></label>
                        <input id="bk-date" type="date" :min="today" x-model="formData.date" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2.5 text-xs focus:outline-none focus:border-brand-gold">
                    </div>
                </div>
                <div>
                    <label for="bk-notes" class="block text-xs font-bold text-brand-green mb-1"><?= e('modal_notes_label') ?></label>
                    <textarea id="bk-notes" rows="3" maxlength="2000" x-model="formData.notes" placeholder="<?= e('modal_notes_ph') ?>" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2.5 text-xs focus:outline-none focus:border-brand-gold"></textarea>
                </div>
                <input type="text" x-model="formData.website" tabindex="-1" autocomplete="off" class="gf-hp" aria-hidden="true">
                <p x-show="formError" x-cloak x-text="formError" class="text-xs text-red-700 bg-red-50 rounded-lg px-3 py-2" role="alert"></p>
                <button type="submit" :disabled="sending" class="w-full gold-bg-gradient text-brand-darkgreen font-bold text-sm py-3.5 rounded-xl hover:brightness-110 transition shadow-md flex items-center justify-center gap-2 disabled:opacity-60">
                    <i class="fa-solid" :class="sending ? 'fa-spinner fa-spin' : 'fa-paper-plane'" aria-hidden="true"></i>
                    <span x-text="sending ? cfg.sendingText : cfg.submitText"></span>
                </button>
            </form>
        </div>
    </div>

    <script>
        function goodFlyApp() {
            const site = <?= json_for_script([
                'hero' => $site['hero'],
                'categories' => $site['categories'],
                'packages' => $site['packages'],
                'filters' => $site['filters'],
                'tiers' => $site['tiers'],
                'cities' => $site['cities'],
                'guides' => $site['guides'],
            ]) ?>;
            return {
                scrolled: false,
                mobileMenuOpen: false,
                modalOpen: false,
                sending: false,
                booked: false,
                bookedMessage: '',
                bookedWhatsapp: '',
                formError: '',
                newsSending: false,
                newsMessage: '',
                newsOk: false,
                selectedPackage: null,
                activeTab: 'all',
                cityTab: site.cities[0] ? site.cities[0].slug : '',
                activeGuide: site.guides[0] ? site.guides[0].id : null,
                currentHeroBg: 0,
                heroSeen: [0],
                heroImages: site.hero,
                categories: site.categories,
                packages: site.packages,
                filters: site.filters,
                tiers: site.tiers,
                cities: site.cities,
                guides: site.guides,
                bookingTypes: site.filters.booking_type || [],
                applied: { hotel: 'all', duration: 'all' },
                quickFilter: { type: 'all', hotel: 'all', duration: 'all' },
                today: new Date().toISOString().slice(0, 10),
                calc: {
                    tier: <?= json_for_script(s('calc_default_tier', 'vip')) ?>,
                    days: <?= (int) s('calc_default_days', '10') ?>,
                    people: <?= (int) s('calc_default_people', '2') ?>,
                    totalPrice: '0'
                },
                cfg: {
                    lang: <?= json_for_script($lang) ?>,
                    api: <?= json_for_script(base_path() . '/api/') ?>,
                    heroInterval: <?= max(2000, (int) s('hero_interval', '5000')) ?>,
                    daysMin: <?= (int) s('calc_days_min', '5') ?>,
                    daysMax: <?= (int) s('calc_days_max', '21') ?>,
                    peopleMin: <?= (int) s('calc_people_min', '1') ?>,
                    peopleMax: <?= (int) s('calc_people_max', '10') ?>,
                    modalTitle: <?= json_for_script(s('modal_title')) ?>,
                    packagePrefix: <?= json_for_script(s('modal_package_prefix')) ?>,
                    modalError: <?= json_for_script(s('modal_error')) ?>,
                    newsSuccess: <?= json_for_script(s('footer_news_success')) ?>,
                    daysUnit: <?= json_for_script(s('calc_days_unit')) ?>,
                    peopleUnit: <?= json_for_script(s('calc_people_unit')) ?>,
                    currency: <?= json_for_script(s('calc_currency')) ?>,
                    notePrefix: <?= json_for_script(s('calc_note_prefix')) ?>,
                    submitText: <?= json_for_script(s('modal_submit')) ?>,
                    sendingText: <?= json_for_script(t('sending')) ?>,
                    numberLocale: <?= json_for_script($lang === 'fr' ? 'fr-FR' : 'en-US') ?>
                },
                formData: { name: '', phone: '', tripType: '', travelers: 2, date: '', notes: '', website: '' },
                initApp() {
                    if (!this.formData.tripType && this.bookingTypes.length) {
                        this.formData.tripType = this.bookingTypes[0].value;
                    }
                    window.addEventListener('scroll', () => { this.scrolled = window.scrollY > 50; }, { passive: true });
                    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                    if (this.heroImages.length > 1 && !reduceMotion) {
                        setInterval(() => this.showHero((this.currentHeroBg + 1) % this.heroImages.length), this.cfg.heroInterval);
                    }
                    this.calcCost();
                },
                showHero(idx) {
                    if (!this.heroSeen.includes(idx)) this.heroSeen.push(idx);
                    this.currentHeroBg = idx;
                },
                get currentCity() {
                    return this.cities.find((city) => city.slug === this.cityTab) || this.cities[0] || null;
                },
                get filteredPackages() {
                    return this.packages.filter((item) => {
                        const tabOk = this.activeTab === 'all' || item.category === this.activeTab;
                        const hotelOk = this.applied.hotel === 'all' || item.hotelClass === this.applied.hotel;
                        const durationOk = this.applied.duration === 'all' || String(item.duration) === String(this.applied.duration);
                        return tabOk && hotelOk && durationOk;
                    });
                },
                selectTab(slug) {
                    this.activeTab = slug;
                    this.applied = { hotel: 'all', duration: 'all' };
                },
                calcCost() {
                    const tier = this.tiers.find((item) => item.key === this.calc.tier) || this.tiers[0];
                    if (tier && tier.key !== this.calc.tier) this.calc.tier = tier.key;
                    const price = tier ? Number(tier.price) : 0;
                    const days = parseInt(this.calc.days, 10) || 0;
                    const people = parseInt(this.calc.people, 10) || 0;
                    this.calc.totalPrice = (price * days * people).toLocaleString(this.cfg.numberLocale);
                },
                applyQuickFilter() {
                    this.activeTab = this.quickFilter.type || 'all';
                    this.applied.hotel = this.quickFilter.hotel || 'all';
                    this.applied.duration = this.quickFilter.duration || 'all';
                    const section = document.getElementById('packages');
                    if (section) section.scrollIntoView({ behavior: 'smooth' });
                },
                toggleGuide(id) { this.activeGuide = this.activeGuide === id ? null : id; },
                resetBooking() {
                    this.booked = false;
                    this.formError = '';
                    this.bookedWhatsapp = '';
                },
                openConsultationModal() { this.resetBooking(); this.selectedPackage = null; this.modalOpen = true; },
                openPackageModal(pkg) {
                    this.resetBooking();
                    this.selectedPackage = pkg;
                    this.formData.tripType = pkg.title;
                    this.modalOpen = true;
                },
                openConsultationWithCalc() {
                    this.resetBooking();
                    const tier = this.tiers.find((item) => item.key === this.calc.tier);
                    const tierLabel = tier ? tier.label : this.calc.tier;
                    this.formData.notes = `${this.cfg.notePrefix}: ${this.calc.totalPrice} ${this.cfg.currency} — ${this.calc.days} ${this.cfg.daysUnit} — ${this.calc.people} ${this.cfg.peopleUnit} — ${tierLabel}`;
                    this.formData.travelers = this.calc.people;
                    this.selectedPackage = null;
                    this.modalOpen = true;
                },
                closeModal() { this.modalOpen = false; },
                async postJson(path, body) {
                    const response = await fetch(this.cfg.api + path, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ ...body, lang: this.cfg.lang })
                    });
                    let data = null;
                    try { data = await response.json(); } catch (error) { data = null; }
                    return data || { ok: false };
                },
                async submitForm() {
                    if (this.sending) return;
                    this.formError = '';
                    this.sending = true;
                    try {
                        const data = await this.postJson('booking.php', { ...this.formData, packageTitle: this.selectedPackage ? this.selectedPackage.title : '' });
                        if (!data.ok) {
                            this.formError = data.message || this.cfg.modalError;
                            return;
                        }
                        this.bookedMessage = data.message;
                        this.bookedWhatsapp = data.whatsapp || '';
                        this.booked = true;
                        this.formData.notes = '';
                    } catch (error) {
                        this.formError = this.cfg.modalError;
                    } finally {
                        this.sending = false;
                    }
                },
                async subscribe(event) {
                    if (this.newsSending) return;
                    this.newsSending = true;
                    const form = event.target;
                    try {
                        const data = await this.postJson('subscribe.php', { email: form.email.value, website: form.website.value });
                        this.newsOk = !!data.ok;
                        this.newsMessage = data.message || (data.ok ? this.cfg.newsSuccess : this.cfg.modalError);
                        if (data.ok) form.reset();
                    } catch (error) {
                        this.newsOk = false;
                        this.newsMessage = this.cfg.modalError;
                    } finally {
                        this.newsSending = false;
                    }
                }
            };
        }
    </script>
    <?php designer_bridge(); ?>
</body>
</html>
