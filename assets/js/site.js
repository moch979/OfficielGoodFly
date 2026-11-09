/* Alpine component shared by the home page and program pages. Data comes from window.GF_DATA. */
function goodFlyApp() {
    const data = window.GF_DATA || { site: {}, cfg: {} };
    const site = data.site;
    const cfg = data.cfg;
    const filters = site.filters || {};

    return {
        cfg,
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
        cityTab: site.cities && site.cities[0] ? site.cities[0].slug : '',
        activeGuide: site.guides && site.guides[0] ? site.guides[0].id : null,
        currentHeroBg: 0,
        heroSeen: [0],
        heroImages: site.hero || [],
        categories: site.categories || [],
        packages: site.packages || [],
        filters,
        tiers: site.tiers || [],
        cities: site.cities || [],
        guides: site.guides || [],
        bookingTypes: filters.booking_type || [],
        applied: { hotel: 'all', duration: 'all' },
        quickFilter: { type: 'all', hotel: 'all', duration: 'all' },
        today: new Date().toISOString().slice(0, 10),
        calc: { tier: cfg.calcTier, days: cfg.calcDays, people: cfg.calcPeople, totalPrice: '0' },
        formData: { name: '', phone: '', tripType: '', travelers: 2, date: '', notes: '', website: '', packageId: '', departureId: '', hotelId: '', roomType: '' },

        initApp() {
            if (!this.formData.tripType && this.bookingTypes.length) {
                this.formData.tripType = this.bookingTypes[0].value;
            }
            window.addEventListener('scroll', () => { this.scrolled = window.scrollY > 50; }, { passive: true });
            const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (this.heroImages.length > 1 && !reduceMotion) {
                setInterval(() => this.showHero((this.currentHeroBg + 1) % this.heroImages.length), cfg.heroInterval);
            }
            this.calcCost();
        },
        money(amount) {
            const n = Number(amount) || 0;
            if (cfg.lang === 'fr') return n.toLocaleString('fr-FR') + ' ' + cfg.moneyCurrency;
            if (cfg.lang === 'en') return cfg.moneyCurrency + ' ' + n.toLocaleString('en-US');
            return n.toLocaleString('en-US') + ' ' + cfg.moneyCurrency;
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
        get pkgDepartures() {
            return this.selectedPackage && this.selectedPackage.departures ? this.selectedPackage.departures : [];
        },
        get pkgHotels() {
            return this.selectedPackage && this.selectedPackage.hotels ? this.selectedPackage.hotels : [];
        },
        get pkgRooms() {
            const hotel = this.pkgHotels.find((h) => String(h.id) === String(this.formData.hotelId));
            return hotel ? hotel.rooms : [];
        },
        chooseHotel() {
            const rooms = this.pkgRooms;
            if (!rooms.some((r) => r.type === this.formData.roomType)) this.formData.roomType = rooms.length === 1 ? rooms[0].type : '';
        },
        get estimateText() {
            const room = this.pkgRooms.find((r) => r.type === this.formData.roomType);
            const travelers = parseInt(this.formData.travelers, 10) || 0;
            return room && travelers > 0 ? this.money(room.price * travelers) : '';
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
            this.calc.totalPrice = (price * days * people).toLocaleString(cfg.numberLocale);
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
            this.formData.departureId = '';
            this.formData.hotelId = '';
            this.formData.roomType = '';
        },
        openConsultationModal() {
            this.resetBooking();
            this.selectedPackage = null;
            this.formData.packageId = '';
            this.modalOpen = true;
        },
        openPackageModal(pkg, departureId, hotelId) {
            this.resetBooking();
            this.selectedPackage = pkg;
            this.formData.packageId = pkg.id;
            this.formData.tripType = pkg.title;
            if (departureId) this.formData.departureId = String(departureId);
            const hotels = pkg.hotels || [];
            if (hotelId) this.formData.hotelId = String(hotelId);
            else if (hotels.length === 1) this.formData.hotelId = String(hotels[0].id);
            this.chooseHotel();
            this.modalOpen = true;
            this.$nextTick(() => {
                const select = document.getElementById('bk-type');
                if (select) select.value = this.formData.tripType;
            });
        },
        openPackageById(id, departureId, hotelId) {
            const pkg = this.packages.find((item) => item.id === id);
            if (pkg) this.openPackageModal(pkg, departureId, hotelId);
        },
        openConsultationWithCalc() {
            this.openConsultationModal();
            const tier = this.tiers.find((item) => item.key === this.calc.tier);
            const tierLabel = tier ? tier.label : this.calc.tier;
            this.formData.notes = `${cfg.notePrefix}: ${this.calc.totalPrice} ${cfg.currency} — ${this.calc.days} ${cfg.daysUnit} — ${this.calc.people} ${cfg.peopleUnit} — ${tierLabel}`;
            this.formData.travelers = this.calc.people;
        },
        closeModal() { this.modalOpen = false; },
        async postJson(path, body) {
            const response = await fetch(cfg.api + path, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ...body, lang: cfg.lang })
            });
            let result = null;
            try { result = await response.json(); } catch (error) { result = null; }
            return result || { ok: false };
        },
        async submitForm() {
            if (this.sending) return;
            this.formError = '';
            this.sending = true;
            try {
                const payload = { ...this.formData, packageTitle: this.selectedPackage ? this.selectedPackage.title : '' };
                if (payload.departureId) payload.date = '';
                const result = await this.postJson('booking.php', payload);
                if (!result.ok) {
                    this.formError = result.message || cfg.modalError;
                    return;
                }
                this.bookedMessage = result.message;
                this.bookedWhatsapp = result.whatsapp || '';
                this.booked = true;
                this.formData.notes = '';
            } catch (error) {
                this.formError = cfg.modalError;
            } finally {
                this.sending = false;
            }
        },
        async subscribe(event) {
            if (this.newsSending) return;
            this.newsSending = true;
            const form = event.target;
            try {
                const result = await this.postJson('subscribe.php', { email: form.email.value, website: form.website.value });
                this.newsOk = !!result.ok;
                this.newsMessage = result.message || (result.ok ? cfg.newsSuccess : cfg.modalError);
                if (result.ok) form.reset();
            } catch (error) {
                this.newsOk = false;
                this.newsMessage = cfg.modalError;
            } finally {
                this.newsSending = false;
            }
        }
    };
}
