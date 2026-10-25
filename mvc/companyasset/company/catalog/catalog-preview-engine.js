/**
 * Motor compartido de preview de catálogo (lista + studio).
 */
(function (global) {
    'use strict';

    function escapeHtml(str) {
        return String(str == null ? '' : str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function normalizeHex(value, fallback) {
        var raw = String(value == null ? '' : value).trim().replace(/^#/, '');
        if (/^[0-9a-fA-F]{6}$/.test(raw)) {
            return '#' + raw.toUpperCase();
        }
        if (/^[0-9a-fA-F]{3}$/.test(raw)) {
            return ('#' + raw[0] + raw[0] + raw[1] + raw[1] + raw[2] + raw[2]).toUpperCase();
        }
        return normalizeHex(fallback || '#111827', '#111827');
    }

    function productRatingHtml(product) {
        var rating = parseFloat(product && product.rating != null ? product.rating : 3.5);
        if (isNaN(rating)) rating = 3.5;
        rating = Math.max(0, Math.min(5, Math.round(rating * 10) / 10));
        var full = Math.floor(rating);
        var frac = rating - full;
        var half = (frac >= 0.25 && frac < 0.75) ? 1 : 0;
        if (frac >= 0.75) { full += 1; half = 0; }
        if (full > 5) { full = 5; half = 0; }
        var empty = Math.max(0, 5 - full - half);
        var html = '<div class="product-rating" aria-label="' + escapeHtml(rating.toFixed(1)) + '">';
        var i;
        for (i = 0; i < full; i++) html += '<span class="product-rating__star is-full">★</span>';
        if (half) html += '<span class="product-rating__star is-half">☆</span>';
        for (i = 0; i < empty; i++) html += '<span class="product-rating__star is-empty">☆</span>';
        html += '<span class="product-rating__value">' + escapeHtml(rating.toFixed(1)) + '</span></div>';
        return html;
    }

    function productCardHtml(layout, product, showPrices) {
        var sku = escapeHtml(product.sku || '');
        var name = escapeHtml(product.name || product.text || sku);
        var price = escapeHtml(product.price_fmt || product.price || '');
        var img = escapeHtml(product.image || '');
        var ratingHtml = productRatingHtml(product);
        var imgHtml = img
            ? '<img src="' + img + '" alt="">'
            : '<span style="color:#94a3b8;font-size:1.4rem;">◼</span>';
        var priceClass = 'product-price' + (showPrices ? '' : ' is-hidden');
        var priceHtml = '<div class="' + priceClass + '">' + (showPrices ? price : '') + '</div>';
        if (layout === 'table') {
            return (
                '<div class="product product--row">' +
                '<div class="product-img">' + imgHtml + '</div>' +
                '<div class="product-sku">' + sku + '</div>' +
                '<div class="product-name">' + name + '</div>' +
                ratingHtml +
                priceHtml +
                '</div>'
            );
        }
        return (
            '<article class="product">' +
            '<div class="product-img">' + imgHtml + '</div>' +
            '<div class="product-body">' +
            '<div class="product-sku">' + sku + '</div>' +
            '<div class="product-name">' + name + '</div>' +
            ratingHtml +
            priceHtml +
            '</div></article>'
        );
    }

    function taxonomyHeaderHtml(level, name, imageUrl) {
        var title = escapeHtml(name || '');
        if (!title) return '';
        var img = escapeHtml(imageUrl || '');
        var imgHtml = img ? '<img class="fv-tax-photo" src="' + img + '" alt="">' : '';
        var tag = level === 'category' ? 'h2' : (level === 'group' ? 'h3' : 'h4');
        var cls = 'fv-tax-header fv-tax-header--' + level + (imgHtml ? ' has-photo' : '');
        return '<header class="' + cls + '">' + imgHtml + '<' + tag + ' class="fv-tax-title">' + title + '</' + tag + '></header>';
    }

    function productsBatchHtml(products, layout, showPrices) {
        return (Array.isArray(products) ? products : []).map(function (p) {
            return productCardHtml(layout, p, showPrices);
        }).join('');
    }

    function categorySectionHtml(section, layout, showPrices) {
        var html = '';
        var catName = section.category_name || '';
        var catImage = section.category_image || '';
        var groups = Array.isArray(section.groups) ? section.groups : [];
        var ungrouped = Array.isArray(section.products) ? section.products : [];
        var hasAny =
            ungrouped.length > 0 ||
            groups.some(function (g) {
                return (g.products && g.products.length) ||
                    (g.subgroups && g.subgroups.some(function (s) { return s.products && s.products.length; }));
            });
        if (!hasAny && !catName) return '';
        html += '<div class="fv-tax-block fv-tax-block--category">';
        if (catName) html += taxonomyHeaderHtml('category', catName, catImage);
        groups.forEach(function (group) {
            var gName = group.group_name || '';
            var gImage = group.group_image || '';
            var subs = Array.isArray(group.subgroups) ? group.subgroups : [];
            var gProducts = Array.isArray(group.products) ? group.products : [];
            html += '<div class="fv-tax-block fv-tax-block--group">';
            if (gName) html += taxonomyHeaderHtml('group', gName, gImage);
            subs.forEach(function (sub) {
                var sName = sub.subgroup_name || '';
                var sImage = sub.subgroup_image || '';
                var sProducts = Array.isArray(sub.products) ? sub.products : [];
                if (!sProducts.length) return;
                html += '<div class="fv-tax-block fv-tax-block--subgroup">';
                if (sName) html += taxonomyHeaderHtml('subgroup', sName, sImage);
                html += '<div class="fv-tax-products">' + productsBatchHtml(sProducts, layout, showPrices) + '</div></div>';
            });
            if (gProducts.length) {
                html += '<div class="fv-tax-products">' + productsBatchHtml(gProducts, layout, showPrices) + '</div>';
            }
            html += '</div>';
        });
        if (ungrouped.length) {
            html += '<div class="fv-tax-products">' + productsBatchHtml(ungrouped, layout, showPrices) + '</div>';
        }
        html += '</div>';
        return html;
    }

    var PREVIEW_EXTRA_CSS = [
        '.fv-tax-block,.fv-tax-products{display:contents;}',
        '.fv-tax-header{grid-column:1/-1;width:100%;display:flex;align-items:center;gap:0.65rem;margin:0;padding:0;background:transparent;border:0;border-radius:0;box-shadow:none;}',
        '.fv-tax-header--category{margin:1.35rem 0 0.55rem;padding:0 0 0.45rem;border-bottom:2px solid #111827;gap:0.75rem;}',
        '.fv-tax-header--category:first-child{margin-top:0.25rem;}',
        '.fv-tax-header--group{margin:1rem 0 0.35rem;padding:0 0 0 0.65rem;border-left:3px solid #dc2626;}',
        '.fv-tax-header--subgroup{margin:0.65rem 0 0.25rem;padding:0;}',
        '.fv-tax-photo{width:2.25rem;height:2.25rem;border-radius:0.35rem;object-fit:contain;background:transparent;flex:0 0 auto;border:0;}',
        '.fv-tax-header--category .fv-tax-photo{width:2.75rem;height:2.75rem;}',
        '.fv-tax-header--group .fv-tax-photo,.fv-tax-header--subgroup .fv-tax-photo{width:1.75rem;height:1.75rem;}',
        '.fv-tax-title{margin:0;font-weight:700;line-height:1.2;letter-spacing:.04em;text-transform:uppercase;}',
        '.fv-tax-header--category .fv-tax-title{font-size:1.05rem;font-weight:800;color:#111827;letter-spacing:.06em;}',
        '.fv-tax-header--group .fv-tax-title{font-size:0.88rem;font-weight:700;color:#1f2937;}',
        '.fv-tax-header--subgroup .fv-tax-title{font-size:0.78rem;font-weight:600;color:#64748b;letter-spacing:.03em;}',
        '.products[data-layout="table"]{display:flex;flex-direction:column;gap:0;width:100%;}',
        '.products[data-layout="table"] .fv-tax-header{display:flex;margin:0.85rem 0 0.35rem;padding:0.35rem 0;border-bottom:1px solid #cbd5e1;}',
        '.products[data-layout="table"] .product--row{display:grid;grid-template-columns:48px 110px minmax(0,1fr) auto auto;gap:0.65rem;align-items:center;padding:0.5rem 0.35rem;border-bottom:1px solid #e2e8f0;}',
        '.products[data-layout="table"] .product-img{width:42px;height:42px;aspect-ratio:auto;border:1px solid #e2e8f0;border-radius:0.2rem;}',
        '.products[data-layout="table"] .product-sku{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:0.78rem;color:#475569;}',
        '.products[data-layout="table"] .product-name{font-weight:600;font-size:0.9rem;}',
        '.products[data-layout="table"] .product-price{text-align:right;white-space:nowrap;}',
        '.fv-catalog-logo{margin:0;display:flex;align-items:center;flex:0 0 auto;order:0;}',
        '.fv-catalog-logo img{max-height:52px;max-width:180px;object-fit:contain;background:#fff;border-radius:0;padding:0;}',
        '.banner .wrap-inner{display:flex!important;align-items:center;justify-content:flex-start;gap:1rem;}',
        '.banner .wrap-inner > div:not(.fv-catalog-logo):not([data-slot="logo"]){order:1;flex:1 1 auto;min-width:0;}',
        '[data-slot="logo"]{order:0!important;}',
        '.product-img{background:#fff!important;overflow:hidden;}',
        '.product-img img{width:100%;height:100%;object-fit:contain;object-position:center;background:#fff!important;display:block;}',
        '.product-rating{display:flex;align-items:center;gap:0.08rem;margin:0.2rem 0 0.35rem;color:#f59e0b;font-size:0.85rem;line-height:1;}',
        '.product-rating__value{margin-left:0.25rem;color:#64748b;font-size:0.75rem;font-weight:700;}'
    ].join('');

    function applyTheme(doc, theme) {
        if (!doc || !doc.documentElement) return;
        theme = theme || {};
        var style = doc.documentElement.style;
        style.setProperty('--fv-banner-bg', normalizeHex(theme.banner_bg, '#111827'));
        style.setProperty('--fv-banner-text', normalizeHex(theme.banner_text, '#FFFFFF'));
        style.setProperty('--fv-footer-bg', normalizeHex(theme.footer_bg, '#111827'));
        style.setProperty('--fv-footer-text', normalizeHex(theme.footer_text, '#E5E7EB'));
        style.setProperty('--fv-accent', normalizeHex(theme.accent, '#B91C1C'));
    }

    /**
     * @param {HTMLIFrameElement} frame
     * @param {object} opts
     */
    function renderToIframe(frame, opts) {
        opts = opts || {};
        var templateHtml = String(opts.templateHtml || '');
        var catalogName = opts.catalogName || 'Catalog';
        var companyName = opts.companyName || 'FullVendor';
        var message = opts.message || '';
        var logoUrl = opts.logoUrl || '';
        var showPrices = opts.showPrices !== false;
        var sections = Array.isArray(opts.sections) ? opts.sections : [];
        var emptyMsg = opts.emptyMsg || '';
        var theme = opts.theme || {};

        if (!frame || !templateHtml) {
            return Promise.reject(new Error('preview missing'));
        }

        var docHtml = templateHtml
            .replace(/\{\{catalog_name\}\}/g, escapeHtml(catalogName))
            .replace(/\{\{company_name\}\}/g, escapeHtml(companyName));
        if (docHtml.indexOf('category-block{') === -1 && docHtml.indexOf('fv-tax-header{') === -1) {
            docHtml = docHtml.replace('</style>', PREVIEW_EXTRA_CSS + '</style>');
        }

        return new Promise(function (resolve) {
            var blob = new Blob([docHtml], { type: 'text/html' });
            var url = URL.createObjectURL(blob);
            frame.onload = function () {
                try {
                    var doc = frame.contentDocument;
                    if (!doc) {
                        resolve(false);
                        return;
                    }
                    applyTheme(doc, theme);
                    var msgEl = doc.querySelector('[data-slot="message"]');
                    if (msgEl) {
                        if (String(message).trim() !== '') {
                            msgEl.textContent = message;
                            msgEl.classList.add('is-visible');
                        } else {
                            msgEl.textContent = '';
                            msgEl.classList.remove('is-visible');
                        }
                    }
                    var logoWrap = doc.querySelector('[data-slot="logo"]');
                    var bannerInner = doc.querySelector('.banner .wrap-inner, [data-slot="banner"] .wrap-inner, header.banner .wrap-inner');
                    if (logoUrl) {
                        if (!logoWrap) {
                            logoWrap = doc.createElement('div');
                            logoWrap.className = 'fv-catalog-logo';
                            logoWrap.setAttribute('data-slot', 'logo');
                            var insertAt = bannerInner || doc.querySelector('.wrap') || doc.body;
                            insertAt.insertBefore(logoWrap, insertAt.firstChild);
                        }
                        logoWrap.innerHTML = '<img src="' + escapeHtml(logoUrl) + '" alt="">';
                        if (bannerInner && logoWrap.parentNode === bannerInner && bannerInner.firstElementChild !== logoWrap) {
                            bannerInner.insertBefore(logoWrap, bannerInner.firstElementChild);
                        } else if (bannerInner && logoWrap.parentNode !== bannerInner) {
                            bannerInner.insertBefore(logoWrap, bannerInner.firstChild);
                        }
                    } else if (logoWrap) {
                        logoWrap.innerHTML = '';
                    }
                    var productsEl = doc.querySelector('[data-slot="products"]');
                    var emptyEl = doc.querySelector('[data-slot="empty"]');
                    var layout = (productsEl && productsEl.getAttribute('data-layout')) || 'grid';
                    if (productsEl) {
                        var countSectionProducts = function (s) {
                            var n = (s.products && s.products.length) || 0;
                            (s.groups || []).forEach(function (g) {
                                n += (g.products && g.products.length) || 0;
                                (g.subgroups || []).forEach(function (sub) {
                                    n += (sub.products && sub.products.length) || 0;
                                });
                            });
                            return n;
                        };
                        var totalProducts = sections.reduce(function (n, s) {
                            return n + countSectionProducts(s);
                        }, 0);
                        if (!sections.length || totalProducts === 0) {
                            productsEl.innerHTML = '';
                            if (emptyEl) {
                                emptyEl.hidden = false;
                                emptyEl.textContent = emptyMsg;
                            }
                        } else {
                            if (emptyEl) emptyEl.hidden = true;
                            productsEl.innerHTML = sections.map(function (section) {
                                return categorySectionHtml(section, layout, showPrices);
                            }).join('');
                        }
                    }
                    resolve(true);
                } catch (e) {
                    resolve(false);
                }
                URL.revokeObjectURL(url);
            };
            frame.src = url;
        });
    }

    global.FvCatalogPreview = {
        escapeHtml: escapeHtml,
        normalizeHex: normalizeHex,
        renderToIframe: renderToIframe,
        applyTheme: applyTheme
    };
})(window);
