<?php

/**
 * Shared shell for public pages: <head>, top bar, header, footer, booking
 * modal and the Alpine app bootstrap. Pages print only their own <main>.
 */

function public_home_url(): string
{
    return lang_url(current_lang()) . (designer_mode() ? '?designer=1' : '');
}

/**
 * On pages other than home, "#packages" must point to the home page section.
 * Site pages such as "blog" or "program/<slug>" get the visitor's language prefix.
 */
function public_href(string $href, bool $isHome): string
{
    $href = safe_url($href);
    if (preg_match('#^(blog|program)(/[a-z0-9\-]+)?/?$#', $href)) {
        return lang_url(current_lang()) . $href;
    }
    return !$isHome && strpos($href, '#') === 0 ? lang_url(current_lang()) . $href : $href;
}

function tel_href(string $phone): string
{
    return 'tel:' . preg_replace('/[^\d+]/', '', $phone);
}

function agency_name(): string
{
    return s('agency_name', s('about_card_title', 'Good Fly Travel'));
}

/** Social accounts the admin filled in: network => [url, icon, label]. */
function agency_socials(): array
{
    $networks = [
        'facebook' => ['fa-facebook-f', 'Facebook'],
        'instagram' => ['fa-instagram', 'Instagram'],
        'tiktok' => ['fa-tiktok', 'TikTok'],
        'youtube' => ['fa-youtube', 'YouTube'],
    ];
    $socials = [];
    foreach ($networks as $network => [$icon, $label]) {
        $url = trim(s('social_' . $network));
        if ($url !== '' && preg_match('#^https://#i', $url)) {
            $socials[$network] = ['url' => $url, 'icon' => $icon, 'label' => $label];
        }
    }
    return $socials;
}

/** schema.org TravelAgency for the home page and as program provider. */
function agency_schema(string $url): array
{
    return array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'TravelAgency',
        'name' => agency_name(),
        'url' => $url,
        'logo' => s('logo_image') !== '' ? absolute_url(s('logo_image')) : null,
        'telephone' => s('phone_display') ?: null,
        'email' => s('footer_email') ?: (s('top_email') ?: null),
        'address' => s('footer_address') !== '' ? ['@type' => 'PostalAddress', 'streetAddress' => s('footer_address'), 'addressCountry' => 'MA'] : null,
        'hasMap' => s('agency_map_url') ?: null,
        'sameAs' => array_values(array_column(agency_socials(), 'url')) ?: null,
    ]);
}

/**
 * $meta keys: title, description, canonical, alternates [lang => url], langLinks [lang => url],
 * ogImage, ogType, jsonLd (array), preloadImage, isHome (bool)
 */
function public_header(array $meta): void
{
    $lang = current_lang();
    $langs = languages();
    $isHome = !empty($meta['isHome']);
    $nav = load_nav();
    $logoUrl = s('logo_image') !== '' ? asset_url(s('logo_image')) : '';
    $logoHeight = max(32, min(96, (int) s('logo_height', '52')));
    $logoAlt = s('logo_alt', 'Good Fly Travel');
    $homeLink = $isHome ? '#hero' : public_home_url();
    $favicon = s('favicon') !== '' ? asset_url(s('favicon')) : '';
    $cssFile = project_root() . '/assets/css/site.css';
    $fonts = array_unique([s('body_font', 'Tajawal'), s('title_font', 'Amiri'), 'Montserrat']);
    $fontQuery = [
        'Tajawal' => 'family=Tajawal:wght@400;500;700;800',
        'Cairo' => 'family=Cairo:wght@400;600;700;800',
        'Amiri' => 'family=Amiri:wght@400;700',
        'Montserrat' => 'family=Montserrat:wght@600;700;800',
    ];
    $fontUrl = 'https://fonts.googleapis.com/css2?' . implode('&', array_intersect_key($fontQuery, array_flip($fonts))) . '&display=swap';
    $ogImage = (string) ($meta['ogImage'] ?? '');
    ?>
<!DOCTYPE html>
<html lang="<?= h($lang) ?>" dir="<?= h(lang_dir()) ?>" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($meta['title']) ?></title>
    <meta name="description" content="<?= h($meta['description']) ?>">
    <?php if (designer_mode() || !empty($meta['noindex'])): ?><meta name="robots" content="noindex"><?php endif; ?>
    <link rel="canonical" href="<?= h($meta['canonical']) ?>">
    <?php foreach ($meta['alternates'] ?? [] as $code => $url): ?>
    <link rel="alternate" hreflang="<?= h($code) ?>" href="<?= h($url) ?>">
    <?php endforeach; ?>
    <?php if (isset($meta['alternates'][default_lang()])): ?>
    <link rel="alternate" hreflang="x-default" href="<?= h($meta['alternates'][default_lang()]) ?>">
    <?php endif; ?>
    <meta property="og:type" content="<?= h($meta['ogType'] ?? 'website') ?>">
    <meta property="og:site_name" content="<?= h(agency_name()) ?>">
    <meta property="og:title" content="<?= h($meta['title']) ?>">
    <meta property="og:description" content="<?= h($meta['description']) ?>">
    <meta property="og:url" content="<?= h($meta['canonical']) ?>">
    <meta property="og:locale" content="<?= h($langs[$lang]['locale']) ?>">
    <?php if ($ogImage !== ''): ?>
    <meta property="og:image" content="<?= h($ogImage) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <?php endif; ?>
    <meta name="theme-color" content="<?= e('color_green', '#1D3C78') ?>">
    <?php if ($favicon !== ''): ?>
    <link rel="icon" href="<?= h($favicon) ?>">
    <link rel="apple-touch-icon" href="<?= h($favicon) ?>">
    <?php else: ?>
    <link rel="icon" href="<?= h(asset_url('favicon.ico')) ?>" sizes="16x16 32x32 48x48">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= h(asset_url('assets/img/favicon-192.png')) ?>">
    <link rel="apple-touch-icon" href="<?= h(asset_url('assets/img/apple-touch-icon.png')) ?>">
    <?php endif; ?>
    <?php if (!empty($meta['preloadImage'])): ?>
    <link rel="preload" as="image" href="<?= h($meta['preloadImage']) ?>">
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="<?= h($fontUrl) ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= h(asset_url('assets/css/site.css')) ?>?v=<?= is_file($cssFile) ? filemtime($cssFile) : 0 ?>">
    <script defer src="<?= h(asset_url('assets/js/site.js')) ?>?v=<?= @filemtime(project_root() . '/assets/js/site.js') ?: 0 ?>"></script>
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
    <?php if (!empty($meta['jsonLd'])): ?>
    <script type="application/ld+json"><?= json_for_script($meta['jsonLd']) ?></script>
    <?php endif; ?>
</head>
<body x-data="goodFlyApp()" x-init="initApp()" class="antialiased selection:bg-brand-gold selection:text-brand-green">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:start-2 focus:z-[60] focus:bg-white focus:text-brand-green focus:px-4 focus:py-2 focus:rounded-lg"><?= $lang === 'ar' ? 'انتقل إلى المحتوى' : ($lang === 'fr' ? 'Aller au contenu' : 'Skip to content') ?></a>

<?php if (gf_visible('show_topbar')): ?>
    <div data-gf="topbar" class="bg-brand-darkgreen text-brand-gold text-xs py-2.5 px-4 border-b border-brand-gold/20 flex justify-between items-center gap-4 max-w-full overflow-hidden<?= gf_off_class('show_topbar') ?>">
        <div class="hidden md:flex items-center gap-5 mx-auto md:mx-0">
            <span><i class="fa-solid fa-shield-halved text-brand-gold me-1" aria-hidden="true"></i> <?= e('top_license_prefix') ?> <strong><?= e('license_number') ?></strong></span>
            <span class="text-brand-gold/30" aria-hidden="true">|</span>
            <span><i class="fa-solid fa-phone text-xs me-1" aria-hidden="true"></i> <?= e('hotline_label') ?> <a dir="ltr" href="<?= h(tel_href(s('phone_display'))) ?>"><strong><?= e('phone_display') ?></strong></a></span>
            <span class="text-brand-gold/30" aria-hidden="true">|</span>
            <a href="mailto:<?= e('top_email') ?>"><i class="fa-solid fa-envelope text-xs me-1" aria-hidden="true"></i> <?= e('top_email') ?></a>
        </div>
        <div class="mx-auto md:mx-0 font-medium tracking-wide text-center"><?= e('announcement') ?></div>
        <?php if (gf_visible('show_langs')): ?>
        <nav data-gf-flag="show_langs" aria-label="<?= h(t('language')) ?>" class="hidden lg:flex items-center gap-3 font-latin text-[11px]<?= gf_off_class('show_langs') ?>">
            <?php foreach ($langs as $code => $info): ?>
            <a href="<?= h($meta['langLinks'][$code]) ?>" hreflang="<?= h($code) ?>" lang="<?= h($code) ?>" class="transition px-1 <?= $code === $lang ? 'text-brand-gold font-bold border-b border-brand-gold' : 'text-white/60 hover:text-white' ?>"<?= $code === $lang ? ' aria-current="true"' : '' ?>><?= e('lang_label_' . $code, $info['name']) ?></a>
            <?php endforeach; ?>
        </nav>
        <?php endif; ?>
    </div>
<?php endif; ?>

    <header data-gf="header" class="sticky top-0 z-50 transition-all duration-300" :class="scrolled ? 'bg-brand-green/95 backdrop-blur-md shadow-lux py-3' : 'bg-brand-green/90 backdrop-blur-sm py-4'">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between">
                <?php if ($logoUrl !== ''): ?>
                <a href="<?= h($homeLink) ?>" class="gf-logo-badge inline-flex items-center px-3 py-1 transition-transform hover:scale-[1.03]">
                    <img src="<?= h($logoUrl) ?>" alt="<?= h($logoAlt) ?>" style="height: <?= $logoHeight ?>px" class="w-auto">
                </a>
                <?php else: ?>
                <a href="<?= h($homeLink) ?>" class="flex items-center gap-3.5 group" aria-label="<?= h($logoAlt) ?>">
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
                    <?php foreach ($nav as $link): ?>
                    <a href="<?= h(public_href($link['href'], $isHome)) ?>" class="text-white/90 hover:text-brand-gold transition-colors py-1 relative after:content-[''] after:absolute after:bottom-0 after:start-0 after:w-0 after:h-0.5 after:bg-brand-gold hover:after:w-full after:transition-all"><?= h($link['label']) ?></a>
                    <?php endforeach; ?>
                </nav>

                <div class="hidden lg:flex items-center gap-3">
                    <a href="<?= h(wa_link(s('wa_header_message'))) ?>" target="_blank" rel="noopener" aria-label="<?= h(t('whatsapp')) ?>" class="bg-emerald-600/90 hover:bg-emerald-600 text-white p-2.5 rounded-full transition shadow-md flex items-center justify-center">
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
                    <a href="<?= h($meta['langLinks'][$code]) ?>" hreflang="<?= h($code) ?>" lang="<?= h($code) ?>" class="<?= $code === $lang ? 'text-brand-gold font-bold' : '' ?>"><?= e('lang_label_' . $code, $info['name']) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php foreach ($nav as $link): ?>
            <a href="<?= h(public_href($link['href'], $isHome)) ?>" @click="mobileMenuOpen = false" class="block text-white font-medium hover:text-brand-gold transition"><?= h($link['label']) ?></a>
            <?php endforeach; ?>
            <div class="pt-4 border-t border-white/10">
                <button type="button" @click="mobileMenuOpen = false; openConsultationModal()" class="w-full gold-bg-gradient text-brand-darkgreen font-bold text-center py-3 rounded-full text-sm"><?= e('btn_consultation') ?></button>
            </div>
        </div>
    </header>
<?php
}

/** $app: data for the Alpine app (hero, packages, filters, tiers, cities, guides, openPackageId). */
function public_footer(array $app, bool $isHome): void
{
    $footer = load_footer_links();
    $logoUrl = s('logo_image') !== '' ? asset_url(s('logo_image')) : '';
    $logoHeight = max(32, min(96, (int) s('logo_height', '52')));
    $lang = current_lang();
    $site = array_merge([
        'hero' => [], 'categories' => [], 'packages' => [], 'tiers' => [], 'cities' => [], 'guides' => [],
        'filters' => ['trip_type' => [], 'hotel' => [], 'duration' => [], 'booking_type' => load_booking_types()],
    ], $app);
    $cfg = [
        'lang' => $lang,
        'api' => base_path() . '/api/',
        'heroInterval' => max(2000, (int) s('hero_interval', '5000')),
        'daysMin' => (int) s('calc_days_min', '5'),
        'daysMax' => (int) s('calc_days_max', '21'),
        'peopleMin' => (int) s('calc_people_min', '1'),
        'peopleMax' => (int) s('calc_people_max', '10'),
        'calcTier' => s('calc_default_tier', 'vip'),
        'calcDays' => (int) s('calc_default_days', '10'),
        'calcPeople' => (int) s('calc_default_people', '2'),
        'modalTitle' => s('modal_title'),
        'packagePrefix' => s('modal_package_prefix'),
        'modalError' => s('modal_error'),
        'newsSuccess' => s('footer_news_success'),
        'daysUnit' => s('calc_days_unit'),
        'peopleUnit' => s('calc_people_unit'),
        'currency' => s('calc_currency'),
        'moneyCurrency' => s('currency_label', 'درهم'),
        'notePrefix' => s('calc_note_prefix'),
        'submitText' => s('modal_submit'),
        'sendingText' => t('sending'),
        'numberLocale' => $lang === 'fr' ? 'fr-FR' : 'en-US',
    ];
    ?>
    <footer id="contact" data-gf="footer" class="bg-brand-darkgreen text-white border-t border-brand-gold/20 pt-16 pb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-10 mb-12">
                <div class="space-y-4">
                    <?php if ($logoUrl !== ''): ?>
                    <div class="gf-logo-badge inline-flex px-3 py-1">
                        <img src="<?= h($logoUrl) ?>" alt="<?= e('logo_alt', 'Good Fly Travel') ?>" loading="lazy" style="height: <?= $logoHeight + 8 ?>px" class="w-auto">
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
                        <?php if (s('license_number') !== ''): ?><div><?= e('footer_license_label') ?> <strong><?= e('license_number') ?></strong></div><?php endif; ?>
                        <?php if (s('footer_cr_number') !== ''): ?><div><?= e('footer_cr_label') ?> <strong><?= e('footer_cr_number') ?></strong></div><?php endif; ?>
                        <?php if (s('agency_ice') !== ''): ?><div><?= h(t('ice_label')) ?> <strong dir="ltr"><?= e('agency_ice') ?></strong></div><?php endif; ?>
                        <?php if (s('agency_if') !== ''): ?><div><?= h(t('if_label')) ?> <strong dir="ltr"><?= e('agency_if') ?></strong></div><?php endif; ?>
                    </div>
                    <?php $socials = agency_socials(); if ($socials): ?>
                    <div class="flex items-center gap-2 pt-2" aria-label="<?= h(t('follow_us')) ?>">
                        <?php foreach ($socials as $social): ?>
                        <a href="<?= h($social['url']) ?>" target="_blank" rel="noopener" aria-label="<?= h($social['label']) ?>" class="w-9 h-9 rounded-full border border-brand-gold/40 text-brand-gold hover:bg-brand-gold hover:text-brand-darkgreen flex items-center justify-center transition"><i class="fa-brands <?= h($social['icon']) ?>" aria-hidden="true"></i></a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <nav aria-label="<?= e('footer_links_title') ?>">
                    <h2 class="font-serif-title text-base font-bold text-brand-gold mb-4"><?= e('footer_links_title') ?></h2>
                    <ul class="space-y-2 text-xs text-brand-ivory/70">
                        <?php foreach ($footer['quick'] as $link): ?>
                        <li><a href="<?= h(public_href($link['href'], $isHome)) ?>" class="hover:text-brand-gold transition"><?= h($link['label']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </nav>
                <div>
                    <h2 class="font-serif-title text-base font-bold text-brand-gold mb-4"><?= e('footer_contact_title') ?></h2>
                    <ul class="space-y-3 text-xs text-brand-ivory/70">
                        <?php if (s('footer_address') !== ''): ?>
                        <li class="flex items-start gap-2"><i class="fa-solid fa-location-dot text-brand-gold mt-1" aria-hidden="true"></i><span><?= e('footer_address') ?><?php if (s('agency_map_url') !== ''): ?><br><a href="<?= h(safe_url(s('agency_map_url'))) ?>" target="_blank" rel="noopener" class="text-brand-gold hover:underline"><?= h(t('view_map')) ?></a><?php endif; ?></span></li>
                        <?php endif; ?>
                        <?php foreach (array_filter([s('phone_display'), s('agency_phone2')], 'strlen') as $phone): ?>
                        <li class="flex items-center gap-2"><i class="fa-solid fa-phone text-brand-gold" aria-hidden="true"></i><a dir="ltr" href="<?= h(tel_href($phone)) ?>"><?= h($phone) ?></a></li>
                        <?php endforeach; ?>
                        <?php if (wa_number() !== ''): ?>
                        <li class="flex items-center gap-2"><i class="fa-brands fa-whatsapp text-brand-gold" aria-hidden="true"></i><a dir="ltr" href="<?= h(wa_link(s('wa_float_message'))) ?>" target="_blank" rel="noopener">+<?= h(wa_number()) ?></a></li>
                        <?php endif; ?>
                        <?php $email = s('footer_email') !== '' ? s('footer_email') : s('top_email'); if ($email !== ''): ?>
                        <li class="flex items-center gap-2"><i class="fa-solid fa-envelope text-brand-gold" aria-hidden="true"></i><a href="mailto:<?= h($email) ?>"><?= h($email) ?></a></li>
                        <?php endif; ?>
                        <?php if (s('agency_hours') !== ''): ?>
                        <li class="flex items-start gap-2"><i class="fa-regular fa-clock text-brand-gold mt-1" aria-hidden="true"></i><span><?= nl('agency_hours') ?></span></li>
                        <?php endif; ?>
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
                    <?php foreach ($footer['legal'] as $link): ?>
                    <a href="<?= h(public_href($link['href'], $isHome)) ?>" class="hover:text-brand-gold"><?= h($link['label']) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </footer>

<?php if (gf_visible('show_whatsapp')): ?>
    <a data-gf="booking" href="<?= h(wa_link(s('wa_float_message'))) ?>" target="_blank" rel="noopener" aria-label="<?= h(t('whatsapp')) ?>" class="fixed bottom-6 end-6 z-40 bg-emerald-600 hover:bg-emerald-500 text-white w-14 h-14 rounded-full flex items-center justify-center text-2xl shadow-2xl transition hover:scale-110 border-2 border-white<?= gf_off_class('show_whatsapp') ?>">
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
                <div x-show="pkgHotels.length" x-cloak>
                    <label for="bk-hotel" class="block text-xs font-bold text-brand-green mb-1"><?= e('modal_hotel_label') ?></label>
                    <select id="bk-hotel" x-model="formData.hotelId" @change="chooseHotel()" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2.5 text-xs focus:outline-none focus:border-brand-gold">
                        <option value="" x-show="pkgHotels.length > 1"><?= e('modal_hotel_any') ?></option>
                        <template x-for="hotel in pkgHotels" :key="hotel.id"><option :value="String(hotel.id)" x-text="hotel.label"></option></template>
                    </select>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" x-show="pkgDepartures.length || pkgRooms.length" x-cloak>
                    <div x-show="pkgDepartures.length">
                        <label for="bk-departure" class="block text-xs font-bold text-brand-green mb-1"><?= e('modal_departure_label') ?></label>
                        <select id="bk-departure" x-model="formData.departureId" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2.5 text-xs focus:outline-none focus:border-brand-gold">
                            <option value=""><?= e('modal_departure_other') ?></option>
                            <template x-for="d in pkgDepartures" :key="d.id"><option :value="String(d.id)" :disabled="d.full" x-text="d.label + ' — ' + d.seatsText"></option></template>
                        </select>
                    </div>
                    <div x-show="pkgRooms.length">
                        <label for="bk-room" class="block text-xs font-bold text-brand-green mb-1"><?= e('modal_room_label') ?></label>
                        <select id="bk-room" x-model="formData.roomType" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2.5 text-xs focus:outline-none focus:border-brand-gold">
                            <option value=""><?= e('modal_room_any') ?></option>
                            <template x-for="r in pkgRooms" :key="r.type"><option :value="r.type" x-text="r.label + ' — ' + r.priceText"></option></template>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="bk-travelers" class="block text-xs font-bold text-brand-green mb-1"><?= e('modal_travelers_label') ?></label>
                        <input id="bk-travelers" type="number" min="1" max="99" x-model="formData.travelers" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2.5 text-xs focus:outline-none focus:border-brand-gold">
                    </div>
                    <div x-show="!formData.departureId">
                        <label for="bk-date" class="block text-xs font-bold text-brand-green mb-1"><?= e('modal_date_label') ?></label>
                        <input id="bk-date" type="date" :min="today" x-model="formData.date" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2.5 text-xs focus:outline-none focus:border-brand-gold">
                    </div>
                </div>
                <div x-show="estimateText" x-cloak class="flex items-center justify-between gap-3 bg-brand-ivory border border-brand-gold/40 rounded-xl px-4 py-3 text-sm">
                    <span class="text-brand-slate"><?= e('modal_estimate_label') ?></span>
                    <strong class="text-brand-green" x-text="estimateText"></strong>
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

    <script>window.GF_DATA = <?= json_for_script(['site' => $site, 'cfg' => $cfg]) ?>;</script>
    <?php designer_bridge(); ?>
</body>
</html>
<?php
}
