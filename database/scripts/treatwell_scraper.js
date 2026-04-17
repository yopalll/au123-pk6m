/**
 * treatwell_scraper.js
 * ====================
 * Professional scraping script for Treatwell.co.uk
 * 
 * CARA PAKAI:
 * 1. Buka Chrome → F12 → Console
 * 2. Buka halaman listing Treatwell (misal: treatwell.co.uk/places/...)
 * 3. Copy-paste script ini ke Console, tekan Enter
 * 4. Script akan otomatis scrape semua salon di halaman listing
 * 5. Setelah selesai, data akan otomatis ter-download sebagai JSON
 * 
 * TIPS:
 * - Jalankan per kota/area untuk hasil lebih rapi
 * - Ganti MAX_PAGES sesuai kebutuhan
 * - Data yang dihasilkan sudah TERSTRUKTUR sesuai database VIYGO
 */

(async function scrapeTreatwell() {
    const MAX_PAGES = 50;          // Maksimum halaman yang di-scrape
    const DELAY_MS = 2000;         // Delay antar request (jangan terlalu cepat!)
    const DETAIL_DELAY_MS = 3000;  // Delay saat buka detail page

    const allSalons = [];
    let currentPage = 1;

    // ── Helper: delay ──────────────────────────────────────────
    function sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    // ── Helper: safe fetch with retry ──────────────────────────
    async function safeFetch(url, retries = 3) {
        for (let i = 0; i < retries; i++) {
            try {
                const resp = await fetch(url);
                if (resp.ok) return await resp.text();
                if (resp.status === 429) {
                    console.warn(`⏳ Rate limited, waiting 10s...`);
                    await sleep(10000);
                    continue;
                }
            } catch (e) {
                console.warn(`⚠️ Fetch failed (attempt ${i + 1}): ${e.message}`);
                await sleep(3000);
            }
        }
        return null;
    }

    // ── Helper: parse HTML string into DOM ─────────────────────
    function parseHTML(htmlString) {
        const parser = new DOMParser();
        return parser.parseFromString(htmlString, 'text/html');
    }

    // ── Helper: safe query ─────────────────────────────────────
    function qs(doc, selector) {
        const el = doc.querySelector(selector);
        return el ? el.textContent.trim() : '';
    }

    function qsAttr(doc, selector, attr) {
        const el = doc.querySelector(selector);
        return el ? (el.getAttribute(attr) || '').trim() : '';
    }

    function qsAll(doc, selector) {
        return Array.from(doc.querySelectorAll(selector));
    }

    // ── STEP 1: Scrape listing page ────────────────────────────
    function scrapeListingPage(doc) {
        const venueCards = qsAll(doc, '[data-testid="search-result-venue"], .venue-card, .search-result');

        if (venueCards.length === 0) {
            // Fallback: try structured data
            const scripts = qsAll(doc, 'script[type="application/ld+json"]');
            for (const script of scripts) {
                try {
                    const data = JSON.parse(script.textContent);
                    if (data['@type'] === 'ItemList') {
                        return data.itemListElement.map(item => ({
                            name: item.name || '',
                            url: item.url || '',
                        }));
                    }
                } catch (e) { }
            }
        }

        return venueCards.map(card => {
            const linkEl = card.querySelector('a[href*="/place/"]');
            return {
                name: card.querySelector('h2, h3, [class*="name"]')?.textContent?.trim() || '',
                url: linkEl ? new URL(linkEl.href, window.location.origin).href : '',
            };
        }).filter(s => s.url);
    }

    // ── STEP 2: Scrape detail page ─────────────────────────────
    async function scrapeDetailPage(url) {
        const html = await safeFetch(url);
        if (!html) return null;

        const doc = parseHTML(html);

        // === Structured Data (JSON-LD) - PALING RELIABLE ===
        let structuredData = {};
        const scripts = qsAll(doc, 'script[type="application/ld+json"]');
        for (const script of scripts) {
            try {
                const data = JSON.parse(script.textContent);
                if (data['@type'] === 'LocalBusiness' || data['@type'] === 'HealthAndBeautyBusiness') {
                    structuredData = data;
                    break;
                }
                // Sometimes it's nested in @graph
                if (data['@graph']) {
                    for (const item of data['@graph']) {
                        if (item['@type'] === 'LocalBusiness' || item['@type'] === 'HealthAndBeautyBusiness') {
                            structuredData = item;
                            break;
                        }
                    }
                }
            } catch (e) { }
        }

        // === Basic Info ===
        const salon = {
            nama_salon: structuredData.name || qs(doc, 'h1'),
            alamat: '',
            kota: '',
            provinsi: '',
            kode_pos: '',
            rating: 0,
            total_review: 0,
            opening_time: '09:00',
            closing_time: '17:00',
            deskripsi: '',
            phone_number: structuredData.telephone || '',
            latitude: null,
            longitude: null,
            image_urls: [],
            services: [],
            staff: [],
            source_url: url,
        };

        // === Address ===
        if (structuredData.address) {
            const addr = structuredData.address;
            salon.alamat = [addr.streetAddress, addr.addressLocality, addr.postalCode]
                .filter(Boolean).join(', ');
            salon.kota = addr.addressLocality || '';
            salon.provinsi = addr.addressRegion || '';
            salon.kode_pos = addr.postalCode || '';
        }

        // === Rating ===
        if (structuredData.aggregateRating) {
            salon.rating = parseFloat(structuredData.aggregateRating.ratingValue) || 0;
            salon.total_review = parseInt(structuredData.aggregateRating.reviewCount) || 0;
        }

        // === Description ===
        salon.deskripsi = structuredData.description || qs(doc, '[class*="description"], [data-testid*="description"]');

        // === Geo ===
        if (structuredData.geo) {
            salon.latitude = parseFloat(structuredData.geo.latitude) || null;
            salon.longitude = parseFloat(structuredData.geo.longitude) || null;
        }

        // === Opening Hours ===
        if (structuredData.openingHoursSpecification?.length > 0) {
            // Get today's hours
            const today = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][new Date().getDay()];
            const todayHours = structuredData.openingHoursSpecification.find(h =>
                h.dayOfWeek === today || (Array.isArray(h.dayOfWeek) && h.dayOfWeek.includes(today))
            ) || structuredData.openingHoursSpecification[0];

            if (todayHours) {
                salon.opening_time = todayHours.opens || '09:00';
                salon.closing_time = todayHours.closes || '17:00';
            }
        }

        // === Images ===
        if (structuredData.image) {
            const images = Array.isArray(structuredData.image) ? structuredData.image : [structuredData.image];
            salon.image_urls = images.filter(u => typeof u === 'string');
        }
        // Also grab from gallery
        qsAll(doc, '[class*="gallery"] img, [class*="carousel"] img, [class*="photo"] img').forEach(img => {
            const src = img.src || img.dataset?.src || '';
            if (src && src.startsWith('http') && !salon.image_urls.includes(src)) {
                salon.image_urls.push(src);
            }
        });

        // === Services ===
        // Look for service/treatment elements
        qsAll(doc, '[class*="treatment-item"], [class*="service-item"], [data-testid*="treatment"]').forEach(el => {
            const name = el.querySelector('[class*="name"], [class*="title"], h3, h4')?.textContent?.trim();
            const priceText = el.querySelector('[class*="price"]')?.textContent?.trim() || '';
            const durationText = el.querySelector('[class*="duration"]')?.textContent?.trim() || '';

            if (name) {
                salon.services.push({
                    nama: name,
                    harga: parseFloat(priceText.replace(/[^0-9.]/g, '')) || 0,
                    durasi: parseDuration(durationText),
                    kategori: guessCategory(name),
                });
            }
        });

        // === Staff/Team ===
        qsAll(doc, '[class*="team-member"], [class*="staff"], [data-testid*="team"]').forEach(el => {
            const name = el.querySelector('[class*="name"], h3, h4, span')?.textContent?.trim();
            if (name && name.length > 1 && name.length < 50) {
                salon.staff.push({ name });
            }
        });

        return salon;
    }

    // ── Helper: parse duration ─────────────────────────────────
    function parseDuration(text) {
        if (!text) return 60;
        let total = 0;
        const hrs = text.match(/(\d+)\s*(?:hr|hour)/i);
        const mins = text.match(/(\d+)\s*(?:min)/i);
        if (hrs) total += parseInt(hrs[1]) * 60;
        if (mins) total += parseInt(mins[1]);
        return total || 60;
    }

    // ── Helper: guess category ─────────────────────────────────
    function guessCategory(name) {
        const n = name.toLowerCase();
        if (/hair|cut|blow|colour|highlight|balayage|perm|braid|extension|keratin|trim|fade|barber/.test(n)) return 'Hair';
        if (/facial|face|derma|peel|hydrat|anti-aging/.test(n)) return 'Face';
        if (/nail|manicure|pedicure|gel|acrylic|shellac|polish/.test(n)) return 'Nails';
        if (/massage|spa|reflexology|aromatherapy|stone|swedish/.test(n)) return 'Massage';
        if (/wax|thread|laser|hair removal|epilat|sugar|brazilian|bikini/.test(n)) return 'Hair Removal';
        if (/brow|lash|eyebrow|eyelash|tint|lamination/.test(n)) return 'Eyebrows & Lashes';
        if (/body|scrub|wrap|tan/.test(n)) return 'Body';
        if (/aesthetic|filler|botox|lip/.test(n)) return 'Medical Aesthetics';
        return 'Other';
    }

    // ── MAIN EXECUTION ─────────────────────────────────────────
    console.log('🚀 Starting Treatwell Scraper...');
    console.log(`📋 Will scrape up to ${MAX_PAGES} pages\n`);

    // Get the current listing page URL base
    const baseUrl = window.location.href.replace(/\/page-\d+\/?/, '/');

    for (let page = 1; page <= MAX_PAGES; page++) {
        const pageUrl = page === 1 ? baseUrl : `${baseUrl}page-${page}/`;
        console.log(`\n📄 Page ${page}: ${pageUrl}`);

        const html = page === 1 ? document.documentElement.outerHTML : await safeFetch(pageUrl);
        if (!html) {
            console.log('❌ No more pages. Stopping.');
            break;
        }

        const doc = page === 1 ? document : parseHTML(html);
        const listings = scrapeListingPage(doc);

        if (listings.length === 0) {
            console.log('❌ No listings found on this page. Stopping.');
            break;
        }

        console.log(`   Found ${listings.length} salons`);

        for (let i = 0; i < listings.length; i++) {
            const listing = listings[i];
            console.log(`   [${i + 1}/${listings.length}] Scraping: ${listing.name || listing.url}`);

            const detail = await scrapeDetailPage(listing.url);
            if (detail) {
                allSalons.push(detail);
            }

            await sleep(DETAIL_DELAY_MS);
        }

        console.log(`\n✅ Page ${page} done. Total salons collected: ${allSalons.length}`);
        await sleep(DELAY_MS);
    }

    // ── Download Results ───────────────────────────────────────
    console.log(`\n🎉 Scraping complete! Total: ${allSalons.length} salons`);

    const timestamp = new Date().toISOString().split('T')[0];
    const filename = `treatwell_scrape_${timestamp}.json`;

    const blob = new Blob([JSON.stringify(allSalons, null, 2)], { type: 'application/json' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename;
    link.click();

    console.log(`📥 Downloaded as ${filename}`);
    console.log('💡 Letakkan file JSON ini di database/data/ lalu jalankan parser');

    // Make data accessible in console
    window.__treatwellData = allSalons;
    console.log('💡 Data juga tersedia di window.__treatwellData');

})();
