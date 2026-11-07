<?php

require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/site_data.php';
require __DIR__ . '/includes/public_layout.php';

set_lang((string) ($_GET['lang'] ?? default_lang()));
if (designer_mode()) {
    use_design_draft();
    header('Cache-Control: no-store');
}

$site = load_site();
$lang = current_lang();
$canonical = site_url() . '/' . ($lang === default_lang() ? '' : $lang . '/');
$ogImage = absolute_url(s('og_image') !== '' ? s('og_image') : ($site['hero'][0] ?? ''));
$alternates = [];
$langLinks = [];
foreach (languages() as $code => $info) {
    $alternates[$code] = site_url() . '/' . ($code === default_lang() ? '' : $code . '/');
    $langLinks[$code] = lang_url($code) . (designer_mode() ? '?designer=1' : '');
}

public_header([
    'title' => s('site_title'),
    'description' => s('meta_description'),
    'canonical' => $canonical,
    'alternates' => $alternates,
    'langLinks' => $langLinks,
    'ogImage' => $ogImage,
    'preloadImage' => $site['hero'][0] ?? '',
    'isHome' => true,
    'jsonLd' => agency_schema($canonical) + ['image' => $ogImage],
]);
?>
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
                        <a :href="pkg.url" class="relative h-64 overflow-hidden block" :aria-label="pkg.title">
                            <img :src="pkg.image" alt="" loading="lazy" decoding="async" class="w-full h-full object-cover group-hover:scale-110 transition duration-700">
                            <div class="absolute inset-0 bg-gradient-to-t from-brand-darkgreen/80 via-transparent to-transparent"></div>
                            <div class="absolute top-4 start-4 bg-brand-darkgreen/90 text-brand-gold text-xs font-semibold px-3 py-1.5 rounded-full border border-brand-gold/30 backdrop-blur-sm">
                                <i class="fa-solid fa-hotel me-1" aria-hidden="true"></i> <span x-text="pkg.hotel"></span>
                            </div>
                            <div class="absolute top-4 end-4 bg-brand-gold text-brand-darkgreen text-xs font-bold px-3 py-1 rounded-full"><span x-text="pkg.categoryBadge"></span></div>
                            <div class="absolute bottom-4 start-4 text-white" x-show="pkg.distance"><p class="text-xs text-brand-gold/90"><i class="fa-solid fa-location-dot me-1" aria-hidden="true"></i> <span x-text="pkg.distance"></span></p></div>
                        </a>
                        <div class="p-6 flex-1 flex flex-col justify-between">
                            <div>
                                <h3 class="font-serif-title text-2xl font-bold text-brand-green mb-2 group-hover:text-brand-gold transition"><a :href="pkg.url" x-text="pkg.title"></a></h3>
                                <p x-show="pkg.departures.length" class="text-[11px] text-brand-slate mb-2"><i class="fa-regular fa-calendar me-1 text-brand-gold" aria-hidden="true"></i><span x-text="pkg.departures.length ? pkg.departures[0].label : ''"></span></p>
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
                                <div class="flex items-center gap-2 shrink-0">
                                    <a :href="pkg.url" class="text-brand-green border border-brand-green/25 hover:border-brand-gold hover:text-brand-gold font-bold text-xs px-4 py-2.5 rounded-full transition"><?= e('pkg_details_button') ?></a>
                                    <button type="button" @click="openPackageModal(pkg)" class="gold-bg-gradient text-brand-darkgreen font-bold text-xs px-4 py-2.5 rounded-full hover:brightness-110 transition shadow-sm"><?= e('pkg_button') ?></button>
                                </div>
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

<?php
public_footer([
    'hero' => $site['hero'],
    'categories' => $site['categories'],
    'packages' => $site['packages'],
    'filters' => $site['filters'],
    'tiers' => $site['tiers'],
    'cities' => $site['cities'],
    'guides' => $site['guides'],
], true);
