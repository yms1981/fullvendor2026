/**
 * FullVendor Catalog Studio
 */
(function ($) {
    'use strict';

    function qs(root, sel) {
        return root.querySelector(sel);
    }

    function qsa(root, sel) {
        return Array.prototype.slice.call(root.querySelectorAll(sel));
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

    function buildAttach(templateKey, showPrices, theme) {
        theme = theme || {};
        var hex = function (v, fb) {
            return normalizeHex(v, fb).replace(/^#/, '');
        };
        return [
            String(templateKey || 'classic-grid'),
            showPrices ? '1' : '0',
            hex(theme.banner_bg, '#111827'),
            hex(theme.banner_text, '#FFFFFF'),
            hex(theme.footer_bg, '#111827'),
            hex(theme.footer_text, '#E5E7EB'),
            hex(theme.accent, '#B91C1C')
        ].join('::');
    }

    function escapeHtml(str) {
        return String(str == null ? '' : str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function productRatingHtml(product) {
        var rating = parseFloat(product && product.rating != null ? product.rating : 3.5);
        if (isNaN(rating)) {
            rating = 3.5;
        }
        rating = Math.max(0, Math.min(5, Math.round(rating * 10) / 10));
        var full = Math.floor(rating);
        var frac = rating - full;
        var half = (frac >= 0.25 && frac < 0.75) ? 1 : 0;
        if (frac >= 0.75) {
            full += 1;
            half = 0;
        }
        if (full > 5) {
            full = 5;
            half = 0;
        }
        var empty = Math.max(0, 5 - full - half);
        var html = '<div class="product-rating" aria-label="' + escapeHtml(rating.toFixed(1)) + '">';
        var i;
        for (i = 0; i < full; i++) {
            html += '<span class="product-rating__star is-full">★</span>';
        }
        if (half) {
            html += '<span class="product-rating__star is-half">☆</span>';
        }
        for (i = 0; i < empty; i++) {
            html += '<span class="product-rating__star is-empty">☆</span>';
        }
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
        if (!title) {
            return '';
        }
        var img = escapeHtml(imageUrl || '');
        // Solo foto real: sin placeholder vacío que ocupe espacio.
        var imgHtml = img
            ? '<img class="fv-tax-photo" src="' + img + '" alt="">'
            : '';
        var tag = level === 'category' ? 'h2' : (level === 'group' ? 'h3' : 'h4');
        var cls = 'fv-tax-header fv-tax-header--' + level + (imgHtml ? ' has-photo' : '');
        return (
            '<header class="' + cls + '">' +
            imgHtml +
            '<' + tag + ' class="fv-tax-title">' + title + '</' + tag + '>' +
            '</header>'
        );
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

        if (!hasAny && !catName) {
            return '';
        }

        html += '<div class="fv-tax-block fv-tax-block--category">';
        if (catName) {
            html += taxonomyHeaderHtml('category', catName, catImage);
        }

        groups.forEach(function (group) {
            var gName = group.group_name || '';
            var gImage = group.group_image || '';
            var subs = Array.isArray(group.subgroups) ? group.subgroups : [];
            var gProducts = Array.isArray(group.products) ? group.products : [];
            html += '<div class="fv-tax-block fv-tax-block--group">';
            if (gName) {
                html += taxonomyHeaderHtml('group', gName, gImage);
            }
            subs.forEach(function (sub) {
                var sName = sub.subgroup_name || '';
                var sImage = sub.subgroup_image || '';
                var sProducts = Array.isArray(sub.products) ? sub.products : [];
                if (!sProducts.length) {
                    return;
                }
                html += '<div class="fv-tax-block fv-tax-block--subgroup">';
                if (sName) {
                    html += taxonomyHeaderHtml('subgroup', sName, sImage);
                }
                html += '<div class="fv-tax-products">' + productsBatchHtml(sProducts, layout, showPrices) + '</div>';
                html += '</div>';
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
        /* Encabezados tipográficos: sin cajas, sin sombra, mínimo espacio. */
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
        '.banner{width:100%!important;max-width:none!important;margin:0!important;box-sizing:border-box;}',
        '.banner .wrap-inner{display:flex!important;align-items:center;justify-content:flex-start!important;gap:1rem;max-width:none!important;width:100%!important;margin:0!important;padding:0 1.25rem;box-sizing:border-box;}',
        '.banner .wrap-inner > div:not(.fv-catalog-logo):not([data-slot="logo"]){order:1;flex:1 1 auto;min-width:0;}',
        '[data-slot="logo"]{order:0!important;}',
        '.footer,[data-slot="footer"]{display:block!important;width:100%!important;max-width:none!important;margin:0!important;padding:0.9rem 1.25rem!important;box-sizing:border-box;text-align:center;font-size:0.78rem;line-height:1.4;background:var(--fv-footer-bg,var(--fv-banner-bg,#111827))!important;color:var(--fv-footer-text,var(--fv-banner-text,#fff))!important;}',
        '.footer .wrap-inner,[data-slot="footer"] .wrap-inner{max-width:none!important;width:100%!important;margin:0 auto;}',
        '.product-img{background:#fff!important;overflow:hidden;}',
        '.product-img img{width:100%;height:100%;object-fit:contain;object-position:center;background:#fff!important;display:block;}'
    ].join('');

    function CatalogStudio(root) {
        this.root = root;
        this.$root = $(root);
        this.mode = root.getAttribute('data-mode') || 'create';
        this.catalogId = parseInt(root.getAttribute('data-catalog-id') || '0', 10) || 0;
        this.isDefault = root.getAttribute('data-is-default') === '1';
        this.saveUrl = root.getAttribute('data-save-url') || '';
        this.backUrl = root.getAttribute('data-back-url') || '';
        this.templatesBase = (root.getAttribute('data-templates-base') || '').replace(/\/?$/, '/');
        this.productSearchUrl = root.getAttribute('data-product-search-url') || '';
        this.previewUrl = root.getAttribute('data-preview-url') || '';
        this.exportPdfUrl = root.getAttribute('data-export-pdf-url') || '';
        this.suggestMessageUrl = root.getAttribute('data-suggest-message-url') || '';
        this.companyName = root.getAttribute('data-company-name') || 'FullVendor';
        this.companyLogoUrl = root.getAttribute('data-company-logo') || '';
        this.catalogLogoUrl = root.getAttribute('data-catalog-logo') || '';
        this.companyContact = {
            email: root.getAttribute('data-company-email') || '',
            phone: root.getAttribute('data-company-phone') || '',
            cell: root.getAttribute('data-company-cell') || '',
            website: root.getAttribute('data-company-website') || '',
            address: root.getAttribute('data-company-address') || '',
            city: root.getAttribute('data-company-city') || '',
            state: root.getAttribute('data-company-state') || '',
            country: root.getAttribute('data-company-country') || '',
            zip: root.getAttribute('data-company-zip') || ''
        };
        this.localLogoObjectUrl = '';
        this.previewSections = [];
        this.previewRequestSeq = 0;
        this.templateCache = {};
        this.previewTimer = null;
        this.dragItem = null;

        this.form = qs(root, '#fvStudioForm');
        this.frame = qs(root, '#fvStudioPreviewFrame');
        this.sidebar = qs(root, '.fv-catalog-studio__sidebar');
        this.left = document.getElementById('fvStudioCatsLeft');
        this.right = document.getElementById('fvStudioCatsRight');
        this.hiddenWrap = qs(root, '#fvStudioCatsHidden');
        this.summary = qs(root, '#fvStudioCatsSummary');
        this.catsModal = document.getElementById('fvStudioCatsModal');
        this.categoryPool = this.readJsonScript('fvStudioCatsPoolJson') || [];
        this.selectedCategoryIds = this.readJsonScript('fvStudioCatsSelectedJson') || [];
        if (!Array.isArray(this.categoryPool)) {
            this.categoryPool = [];
        }
        if (!Array.isArray(this.selectedCategoryIds)) {
            this.selectedCategoryIds = [];
        }
        this.selectedCategoryIds = this.selectedCategoryIds.map(function (id) {
            return parseInt(id, 10) || 0;
        }).filter(function (id) { return id > 0; });

        this.templateDefaults = this.readJsonScript('fvStudioTemplateDefaultsJson') || {};
        this.themeColors = {
            banner_bg: normalizeHex((qs(root, '#fvThemeBannerBg') || {}).value, '#111827'),
            banner_text: normalizeHex((qs(root, '#fvThemeBannerText') || {}).value, '#FFFFFF'),
            footer_bg: normalizeHex((qs(root, '#fvThemeFooterBg') || {}).value, '#111827'),
            footer_text: normalizeHex((qs(root, '#fvThemeFooterText') || {}).value, '#E5E7EB'),
            accent: normalizeHex((qs(root, '#fvThemeAccent') || {}).value, '#B91C1C')
        };

        this.bind();
        this.initSelect2();
        this.renderCategorySummary();
        this.syncCategoryHidden();
        this.schedulePreview();
    }

    CatalogStudio.prototype.readJsonScript = function (id) {
        var el = document.getElementById(id);
        if (!el) return null;
        try {
            return JSON.parse(el.textContent || 'null');
        } catch (e) {
            return null;
        }
    };

    CatalogStudio.prototype.bind = function () {
        var self = this;

        qsa(this.root, 'input[name="product_source"]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                self.toggleSourcePanels();
                self.schedulePreview();
            });
        });

        qsa(this.root, 'input[name="template_key"]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                if (self.sidebar) {
                    var keepTop = self.sidebar.scrollTop;
                    window.requestAnimationFrame(function () {
                        self.sidebar.scrollTop = keepTop;
                        window.scrollTo(0, 0);
                    });
                }
                self.applyTemplateDefaultColors(radio.value, false);
                self.schedulePreview(true);
            });
        });

        qsa(this.root, '.fv-studio-theme-field input[type="color"]').forEach(function (input) {
            input.addEventListener('input', function () {
                self.syncThemeColorsFromInputs();
                self.schedulePreview(true);
            });
        });

        qsa(this.root, '.fv-studio-color-preset').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var bannerBg = btn.getAttribute('data-banner-bg');
                var bannerText = btn.getAttribute('data-banner-text');
                // Mismo estilo header/footer: el pie adopta el color del banner.
                self.applyThemeColors({
                    banner_bg: bannerBg,
                    banner_text: bannerText,
                    footer_bg: bannerBg,
                    footer_text: bannerText,
                    accent: btn.getAttribute('data-accent')
                }, true);
                self.schedulePreview(true);
            });
        });

        var msg = qs(this.root, '#fvStudioMessage');
        var name = qs(this.root, '#fvStudioName');
        var prices = qs(this.root, '#fvStudioShowPrices');
        if (msg) msg.addEventListener('input', function () { self.schedulePreview(true); });
        if (name && !name.disabled) name.addEventListener('input', function () { self.schedulePreview(true); });
        if (prices && prices.type === 'checkbox') {
            prices.addEventListener('change', function () { self.schedulePreview(true); });
        }

        var filter = qs(this.root, '#fvStudioTemplateFilter');
        if (filter) {
            filter.addEventListener('input', function () {
                var q = String(filter.value || '').toLowerCase().trim();
                qsa(self.root, '.fv-studio-template').forEach(function (el) {
                    var hay = el.getAttribute('data-name') || '';
                    el.classList.toggle('is-filtered-out', q !== '' && hay.indexOf(q) === -1);
                });
            });
        }

        var refresh = qs(this.root, '#fvStudioRefreshPreview');
        if (refresh) refresh.addEventListener('click', function () { self.schedulePreview(false); });

        var exportPdf = qs(this.root, '#fvStudioExportPdf');
        if (exportPdf) {
            exportPdf.addEventListener('click', function () {
                self.exportPreviewPdf();
            });
        }

        var cancel = qs(this.root, '#fvStudioCancel');
        if (cancel) {
            cancel.addEventListener('click', function () {
                window.location.href = self.backUrl;
            });
        }

        var save = qs(this.root, '#fvStudioSave');
        if (save) save.addEventListener('click', function () { self.save(); });

        var logoInput = qs(this.root, '#fvStudioLogoInput');
        if (logoInput) {
            logoInput.addEventListener('change', function () {
                self.onLogoFileChange(logoInput);
            });
        }
        var logoClear = qs(this.root, '#fvStudioLogoClear');
        if (logoClear) {
            logoClear.addEventListener('click', function () {
                self.clearLogo();
            });
        }
        var suggestBtn = qs(this.root, '#fvStudioSuggestMessage');
        if (suggestBtn) {
            suggestBtn.addEventListener('click', function () {
                self.suggestFeaturedMessage();
            });
        }

        this.bindDualList();
        this.bindCategoriesModal();
        this.externalProducts = [];
        this.bindOrderSource();
        this.bindExcelSource();
        this.toggleSourcePanels();
    };

    CatalogStudio.prototype.getProductSource = function () {
        return (qs(this.root, 'input[name="product_source"]:checked') || {}).value || 'categories';
    };

    CatalogStudio.prototype.showLoadedProductsBox = function (source, visible) {
        var map = {
            order: '#fvStudioOrderProductsBox',
            excel: '#fvStudioExcelProductsBox'
        };
        Object.keys(map).forEach(function (key) {
            var el = qs(this.root, map[key]);
            if (!el) return;
            if (key === source && visible) {
                el.removeAttribute('hidden');
            } else {
                el.setAttribute('hidden', 'hidden');
            }
        }.bind(this));
    };

    CatalogStudio.prototype.clearExternalProducts = function () {
        this.externalProducts = [];
        var wrap = qs(this.root, '#fvStudioExternalProducts');
        if (wrap) {
            wrap.innerHTML = '';
        }
        ['#fvStudioOrderProductsList', '#fvStudioExcelProductsList'].forEach(function (sel) {
            var list = qs(this.root, sel);
            if (list) list.innerHTML = '';
        }.bind(this));
        this.showLoadedProductsBox('', false);
    };

    CatalogStudio.prototype.renderLoadedProductsList = function (source, products) {
        var listId = source === 'excel' ? '#fvStudioExcelProductsList' : '#fvStudioOrderProductsList';
        var list = qs(this.root, listId);
        if (!list) {
            return;
        }
        list.innerHTML = '';
        (products || []).forEach(function (row) {
            var li = document.createElement('li');
            li.textContent = row.text || ((row.sku || '') + ' | ' + (row.name || ''));
            list.appendChild(li);
        });
    };

    CatalogStudio.prototype.setExternalProducts = function (source, products) {
        var list = Array.isArray(products) ? products : [];
        var normalized = [];
        var seen = {};
        list.forEach(function (row) {
            var id = parseInt(row.id || row.pro_id || 0, 10) || 0;
            if (id < 1 || seen[id]) {
                return;
            }
            seen[id] = true;
            normalized.push({
                id: id,
                text: row.text || ((row.sku || '') + ' | ' + (row.name || '')),
                sku: row.sku || '',
                name: row.name || ''
            });
        });
        this.externalProducts = normalized;

        var wrap = qs(this.root, '#fvStudioExternalProducts');
        if (wrap) {
            wrap.innerHTML = '';
            normalized.forEach(function (row) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'products[]';
                input.value = String(row.id);
                wrap.appendChild(input);
            });
        }

        this.renderLoadedProductsList(source, normalized);
        this.showLoadedProductsBox(source, normalized.length > 0);
        return normalized.length;
    };

    CatalogStudio.prototype.formatProductsCount = function (count) {
        var tpl = this.root.getAttribute('data-msg-products-count') || '{count}';
        return String(tpl).split('{count}').join(String(count));
    };

    CatalogStudio.prototype.bindOrderSource = function () {
        var self = this;
        var loadBtn = qs(this.root, '#fvStudioOrderLoad');
        if (loadBtn) {
            loadBtn.addEventListener('click', function () {
                self.loadProductsFromOrder();
            });
        }
    };

    CatalogStudio.prototype.loadProductsFromOrder = function () {
        var self = this;
        var $order = this.$root.find('#fvStudioOrder');
        var orderId = parseInt(($order.val && $order.val()) || '0', 10) || 0;
        var status = qs(this.root, '#fvStudioOrderStatus');
        if (orderId < 1) {
            if (status) status.textContent = this.root.getAttribute('data-msg-order-required') || '';
            return;
        }
        var url = this.root.getAttribute('data-order-products-url') || '';
        if (!url) {
            return;
        }
        if (status) status.textContent = this.root.getAttribute('data-msg-order-loading') || '';
        var loadBtn = qs(this.root, '#fvStudioOrderLoad');
        if (loadBtn) loadBtn.disabled = true;
        $.ajax({
            url: url,
            method: 'POST',
            dataType: 'json',
            data: { order_id: orderId }
        }).done(function (resp) {
            var products = (resp && resp.products) ? resp.products : [];
            var count = self.setExternalProducts('order', products);
            if (!count) {
                if (status) {
                    status.textContent = (resp && resp.msg) || self.root.getAttribute('data-msg-order-empty') || '';
                }
                self.schedulePreview();
                return;
            }
            var loaded = self.root.getAttribute('data-msg-order-loaded') || '';
            if (status) {
                status.textContent = loaded
                    ? loaded.split('{count}').join(String(count))
                    : self.formatProductsCount(count);
            }
            self.schedulePreview();
        }).fail(function () {
            if (status) status.textContent = self.root.getAttribute('data-msg-order-empty') || '';
            self.clearExternalProducts();
            self.schedulePreview();
        }).always(function () {
            if (loadBtn) loadBtn.disabled = false;
        });
    };

    CatalogStudio.prototype.bindExcelSource = function () {
        var self = this;
        var btn = qs(this.root, '#fvStudioExcelImport');
        if (!btn) {
            return;
        }
        btn.addEventListener('click', function () {
            self.importSkusFromExcel();
        });
    };

    CatalogStudio.prototype.importSkusFromExcel = function () {
        var self = this;
        var input = qs(this.root, '#fvStudioExcelFile');
        var status = qs(this.root, '#fvStudioExcelStatus');
        if (!input || !input.files || !input.files.length) {
            if (status) status.textContent = this.root.getAttribute('data-msg-excel-required') || '';
            return;
        }
        var url = this.root.getAttribute('data-excel-import-url') || '';
        if (!url) {
            return;
        }
        if (status) status.textContent = this.root.getAttribute('data-msg-excel-loading') || '';
        var btn = qs(this.root, '#fvStudioExcelImport');
        if (btn) btn.disabled = true;
        var fd = new FormData();
        fd.append('file', input.files[0]);
        $.ajax({
            url: url,
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json'
        }).done(function (resp) {
            if (!resp || resp.success === false) {
                if (status) {
                    status.textContent = (resp && resp.msg) || self.root.getAttribute('data-msg-excel-error') || '';
                }
                self.clearExternalProducts();
                self.schedulePreview();
                return;
            }
            var products = resp.products || [];
            var count = self.setExternalProducts('excel', products);
            if (!count) {
                if (status) {
                    var notFoundMsg = self.root.getAttribute('data-msg-excel-not-found') || '';
                    if (resp && resp.not_found && notFoundMsg) {
                        status.textContent = notFoundMsg.split('{count}').join(String(resp.not_found));
                    } else {
                        status.textContent = (resp && resp.msg) || self.root.getAttribute('data-msg-excel-empty') || '';
                    }
                }
                self.schedulePreview();
                return;
            }
            var loaded = self.root.getAttribute('data-msg-excel-loaded') || '';
            var msg = loaded
                ? loaded.split('{count}').join(String(count))
                : self.formatProductsCount(count);
            if (resp.not_found) {
                msg += ' (' + String(resp.not_found) + ')';
            }
            if (status) status.textContent = msg;
            self.schedulePreview();
        }).fail(function () {
            if (status) status.textContent = self.root.getAttribute('data-msg-excel-error') || '';
            self.clearExternalProducts();
            self.schedulePreview();
        }).always(function () {
            if (btn) btn.disabled = false;
        });
    };

    CatalogStudio.prototype.getShowPrices = function () {
        var el = qs(this.root, '#fvStudioShowPrices');
        if (!el) {
            return true;
        }
        if (el.type === 'checkbox') {
            return !!el.checked;
        }
        return String(el.value || '') === '1';
    };

    CatalogStudio.prototype.syncThemeColorsFromInputs = function () {
        this.themeColors = {
            banner_bg: normalizeHex((qs(this.root, '#fvThemeBannerBg') || {}).value, '#111827'),
            banner_text: normalizeHex((qs(this.root, '#fvThemeBannerText') || {}).value, '#FFFFFF'),
            footer_bg: normalizeHex((qs(this.root, '#fvThemeFooterBg') || {}).value, '#111827'),
            footer_text: normalizeHex((qs(this.root, '#fvThemeFooterText') || {}).value, '#E5E7EB'),
            accent: normalizeHex((qs(this.root, '#fvThemeAccent') || {}).value, '#B91C1C')
        };
        return this.themeColors;
    };

    CatalogStudio.prototype.applyThemeColors = function (colors, writeInputs) {
        var next = {
            banner_bg: normalizeHex(colors && colors.banner_bg, this.themeColors.banner_bg),
            banner_text: normalizeHex(colors && colors.banner_text, this.themeColors.banner_text),
            footer_bg: normalizeHex(colors && colors.footer_bg, this.themeColors.footer_bg),
            footer_text: normalizeHex(colors && colors.footer_text, this.themeColors.footer_text),
            accent: normalizeHex(colors && colors.accent, this.themeColors.accent)
        };
        this.themeColors = next;
        if (writeInputs) {
            var map = {
                '#fvThemeBannerBg': next.banner_bg,
                '#fvThemeBannerText': next.banner_text,
                '#fvThemeFooterBg': next.footer_bg,
                '#fvThemeFooterText': next.footer_text,
                '#fvThemeAccent': next.accent
            };
            Object.keys(map).forEach(function (sel) {
                var el = qs(this.root, sel);
                if (el) el.value = map[sel];
            }.bind(this));
        }
        return next;
    };

    CatalogStudio.prototype.applyTemplateDefaultColors = function (templateKey, force) {
        var defaults = this.templateDefaults && this.templateDefaults[templateKey]
            ? this.templateDefaults[templateKey]
            : null;
        if (!defaults) {
            return;
        }
        // Al cambiar plantilla, cargar su paleta por defecto (el usuario puede retocar después).
        this.applyThemeColors(defaults, true);
    };

    CatalogStudio.prototype.applyThemeToDocument = function (doc) {
        if (!doc || !doc.documentElement) return;
        var theme = this.syncThemeColorsFromInputs();
        var rootStyle = doc.documentElement.style;
        rootStyle.setProperty('--fv-banner-bg', theme.banner_bg);
        rootStyle.setProperty('--fv-banner-text', theme.banner_text);
        rootStyle.setProperty('--fv-footer-bg', theme.footer_bg);
        rootStyle.setProperty('--fv-footer-text', theme.footer_text);
        rootStyle.setProperty('--fv-accent', theme.accent);
    };

    CatalogStudio.prototype.getActiveLogoUrl = function () {
        if (this.localLogoObjectUrl) {
            return this.localLogoObjectUrl;
        }
        if (this.catalogLogoUrl) {
            return this.catalogLogoUrl;
        }
        return this.companyLogoUrl || '';
    };

    CatalogStudio.prototype.buildCompanyFooterHtml = function () {
        var escapeHtml = function (s) {
            return String(s == null ? '' : s)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        };
        var c = this.companyContact || {};
        var lines = [];
        if (this.companyName) {
            lines.push('<strong>' + escapeHtml(this.companyName) + '</strong>');
        }
        var contact = [];
        if (c.email) contact.push(escapeHtml(c.email));
        var phones = [c.phone, c.cell].filter(Boolean);
        if (phones.length) contact.push(escapeHtml(phones.join(' · ')));
        if (contact.length) lines.push(contact.join(' &nbsp;|&nbsp; '));
        var loc = [c.address, c.city, c.state, c.zip, c.country].filter(Boolean);
        if (loc.length) lines.push(escapeHtml(loc.join(', ')));
        if (c.website) lines.push(escapeHtml(c.website));
        return lines.join('<br>');
    };

    CatalogStudio.prototype.fillPreviewFooter = function (doc) {
        if (!doc) return;
        var footer = doc.querySelector('[data-slot="footer"], footer.footer, .footer');
        if (!footer) return;
        var html = this.buildCompanyFooterHtml();
        if (!html) return;
        var inner = footer.querySelector('.wrap-inner');
        if (inner) {
            inner.innerHTML = html;
        } else {
            footer.innerHTML = html;
        }
    };

    CatalogStudio.prototype.blobOrUrlToDataUri = function (url) {
        if (!url) {
            return Promise.resolve('');
        }
        if (String(url).indexOf('data:') === 0) {
            return Promise.resolve(url);
        }
        return fetch(url, { credentials: 'same-origin' })
            .then(function (r) {
                if (!r.ok) throw new Error('fetch logo');
                return r.blob();
            })
            .then(function (blob) {
                return new Promise(function (resolve, reject) {
                    var reader = new FileReader();
                    reader.onload = function () { resolve(String(reader.result || '')); };
                    reader.onerror = reject;
                    reader.readAsDataURL(blob);
                });
            })
            .catch(function () { return ''; });
    };

    CatalogStudio.prototype.preparePreviewHtmlForPdf = function () {
        var self = this;
        var doc = this.frame && this.frame.contentDocument ? this.frame.contentDocument : null;
        if (!doc || !doc.documentElement) {
            return Promise.reject(new Error('empty'));
        }

        self.fillPreviewFooter(doc);

        var logoUrl = self.getActiveLogoUrl();
        var logoWrap = doc.querySelector('[data-slot="logo"]');
        var bannerInner = doc.querySelector('.banner .wrap-inner, [data-slot="banner"] .wrap-inner');
        var maxProducts = 0; // 0 = sin límite: todos los productos de cada categoría
        var ensureLogo = Promise.resolve();
        if (logoUrl) {
            ensureLogo = self.blobOrUrlToDataUri(logoUrl).then(function (dataUri) {
                var finalSrc = dataUri || logoUrl;
                if (!logoWrap) {
                    logoWrap = doc.createElement('div');
                    logoWrap.className = 'fv-catalog-logo';
                    logoWrap.setAttribute('data-slot', 'logo');
                }
                logoWrap.innerHTML = '<img src="' + finalSrc.replace(/"/g, '&quot;') + '" alt="">';
                if (bannerInner) {
                    if (logoWrap.parentNode !== bannerInner) {
                        bannerInner.insertBefore(logoWrap, bannerInner.firstChild);
                    } else if (bannerInner.firstElementChild !== logoWrap) {
                        bannerInner.insertBefore(logoWrap, bannerInner.firstElementChild);
                    }
                }
            });
        }

        return ensureLogo.then(function () {
            var products = Array.prototype.slice.call(doc.querySelectorAll('.product, .product--row'));
            products.forEach(function (el) {
                el.removeAttribute('data-fv-pdf-skip');
            });

            // NO convertir todas las fotos a base64 (revienta memoria PHP en el POST).
            // Solo absolutizar URLs; el servidor embebe desde disco/API.
            var imgs = Array.prototype.slice.call(doc.querySelectorAll('img[src]'));
            var total = imgs.length || 1;
            var done = 0;
            var report = function () {
                if (typeof self._pdfProgress === 'function') {
                    var pct = 5 + Math.round((done / total) * 45);
                    self._pdfProgress(pct);
                }
            };
            report();
            var tasks = imgs.map(function (img) {
                var src = img.getAttribute('src') || '';
                if (!src || src.indexOf('data:') === 0) {
                    done++;
                    report();
                    return Promise.resolve();
                }
                // blob: local (logo recién elegido) sí hay que serializar.
                if (src.indexOf('blob:') === 0) {
                    return self.blobOrUrlToDataUri(src).then(function (dataUri) {
                        if (dataUri) img.setAttribute('src', dataUri);
                    }).then(function () {
                        done++;
                        report();
                    }, function () {
                        done++;
                        report();
                    });
                }
                var abs = src;
                if (src.indexOf('//') === 0) {
                    abs = window.location.protocol + src;
                } else if (src.charAt(0) === '/') {
                    abs = window.location.origin + src;
                } else if (src.indexOf('http') !== 0) {
                    abs = window.location.origin + '/' + src.replace(/^\.\//, '');
                }
                if (abs !== src) {
                    img.setAttribute('src', abs);
                }
                done++;
                report();
                return Promise.resolve();
            });
            return Promise.all(tasks);
        }).then(function () {
            if (typeof self._pdfProgress === 'function') {
                self._pdfProgress(55);
            }
            var cloneRoot = doc.documentElement.cloneNode(true);
            cloneRoot.querySelectorAll('[data-fv-pdf-skip]').forEach(function (el) {
                if (el.parentNode) el.parentNode.removeChild(el);
            });
            cloneRoot.querySelectorAll('script, link[rel="stylesheet"]').forEach(function (el) {
                if (el.parentNode) el.parentNode.removeChild(el);
            });
            return '<!DOCTYPE html>\n' + cloneRoot.outerHTML;
        });
    };

    CatalogStudio.prototype.setLogoPreview = function (url, hintText) {
        var img = qs(this.root, '#fvStudioLogoImg');
        var placeholder = qs(this.root, '#fvStudioLogoPlaceholder');
        var clearBtn = qs(this.root, '#fvStudioLogoClear');
        var hint = qs(this.root, '#fvStudioLogoHint');
        if (img) {
            if (url) {
                img.src = url;
                img.hidden = false;
            } else {
                img.removeAttribute('src');
                img.hidden = true;
            }
        }
        if (placeholder) {
            placeholder.hidden = !!url;
        }
        if (clearBtn) {
            clearBtn.hidden = !url;
        }
        if (hint && hintText) {
            hint.textContent = hintText;
        }
    };

    CatalogStudio.prototype.onLogoFileChange = function (input) {
        var file = input && input.files && input.files[0] ? input.files[0] : null;
        if (!file) {
            return;
        }
        if (this.localLogoObjectUrl) {
            try { URL.revokeObjectURL(this.localLogoObjectUrl); } catch (e) { /* ignore */ }
        }
        this.localLogoObjectUrl = URL.createObjectURL(file);
        this.catalogLogoUrl = '';
        var uuidEl = qs(this.root, '#fvStudioLogoUuid');
        var clearEl = qs(this.root, '#fvStudioClearLogo');
        if (uuidEl) uuidEl.value = '';
        if (clearEl) clearEl.value = '0';
        this.setLogoPreview(this.localLogoObjectUrl, this.root.getAttribute('data-msg-logo-help') || '');
        this.schedulePreview(true);
    };

    CatalogStudio.prototype.clearLogo = function () {
        var input = qs(this.root, '#fvStudioLogoInput');
        var uuidEl = qs(this.root, '#fvStudioLogoUuid');
        var clearEl = qs(this.root, '#fvStudioClearLogo');
        if (input) input.value = '';
        if (uuidEl) uuidEl.value = '';
        if (clearEl) clearEl.value = '1';
        if (this.localLogoObjectUrl) {
            try { URL.revokeObjectURL(this.localLogoObjectUrl); } catch (e) { /* ignore */ }
        }
        this.localLogoObjectUrl = '';
        this.catalogLogoUrl = '';
        var fallback = this.companyLogoUrl || '';
        this.setLogoPreview(
            fallback,
            fallback
                ? (this.root.getAttribute('data-msg-logo-company') || '')
                : (this.root.getAttribute('data-msg-logo-placeholder') || '')
        );
        this.schedulePreview(true);
    };

    CatalogStudio.prototype.suggestFeaturedMessage = function () {
        var self = this;
        var url = this.suggestMessageUrl;
        var btn = qs(this.root, '#fvStudioSuggestMessage');
        var msgEl = qs(this.root, '#fvStudioMessage');
        if (!url || !msgEl) {
            return;
        }
        var catalogName = (qs(this.root, '#fvStudioName') || {}).value || '';
        var categoryNames = this.getSelectedCategoryIds().map(function (id) {
            return self.categoryNameById(id);
        }).filter(Boolean);

        if (btn) btn.classList.add('is-loading');
        var icon = btn ? btn.querySelector('i') : null;
        if (icon) {
            icon.classList.remove('mdi-help-circle-outline');
            icon.classList.add('mdi-loading', 'mdi-spin');
        }
        var fd = new FormData();
        fd.set('catalog_name', catalogName);
        categoryNames.forEach(function (n) { fd.append('category_names[]', n); });

        fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (res) {
            return res.json().catch(function () { return null; }).then(function (json) {
                return { ok: res.ok, json: json };
            });
        }).then(function (pack) {
            var json = pack && pack.json;
            if (json && json.success && json.text) {
                msgEl.value = String(json.text);
                self.schedulePreview(true);
                return;
            }
            var code = json && json.code ? String(json.code) : '';
            var err = (json && json.msg) || '';
            if (code === 'openai_not_configured') {
                err = self.root.getAttribute('data-msg-ai-unavailable') || err;
            } else if (code === 'openai_quota_exceeded') {
                err = self.root.getAttribute('data-msg-ai-quota') || err;
            }
            window.alert(err || self.root.getAttribute('data-msg-ai-error') || '');
        }).catch(function () {
            window.alert(self.root.getAttribute('data-msg-ai-error') || '');
        }).finally(function () {
            if (btn) btn.classList.remove('is-loading');
            if (icon) {
                icon.classList.add('mdi-help-circle-outline');
                icon.classList.remove('mdi-loading', 'mdi-spin');
            }
        });
    };

    CatalogStudio.prototype.toggleSourcePanels = function () {
        var self = this;
        var source = this.getProductSource ? this.getProductSource() : ((qs(this.root, 'input[name="product_source"]:checked') || {}).value || 'categories');
        var prev = this._lastProductSource || '';
        if (prev && prev !== source) {
            this.clearExternalProducts();
            if (prev === 'skus') {
                var $skus = this.$root.find('#fvStudioProducts');
                if ($skus.length) {
                    $skus.val(null).trigger('change');
                }
            }
        }
        this._lastProductSource = source;

        qsa(this.root, '[data-source-panel]').forEach(function (panel) {
            var match = panel.getAttribute('data-source-panel') === source;
            if (match) {
                panel.removeAttribute('hidden');
            } else {
                panel.setAttribute('hidden', 'hidden');
            }
        });
        if (source === 'skus') {
            this.ensureProductsSelect2();
        }
        if (source === 'order') {
            window.requestAnimationFrame(function () {
                self.ensureOrderSelect2();
                var $order = self.$root.find('#fvStudioOrder');
                if ($order.length && $order.data('select2')) {
                    $order.next('.select2-container').css('width', '100%');
                }
            });
        }
    };

    /**
     * Espera a que Select2 esté cargado (CoreLoader lo trae en el footer).
     */
    CatalogStudio.prototype.whenSelect2Ready = function (cb) {
        var tries = 0;
        var tick = function () {
            if (typeof $.fn.select2 === 'function') {
                cb();
                return;
            }
            if (tries++ < 50) {
                setTimeout(tick, 100);
            }
        };
        tick();
    };

    CatalogStudio.prototype.initSelect2 = function () {
        var self = this;
        this.whenSelect2Ready(function () {
            self.ensureProductsSelect2();
            self.ensureOrderSelect2();
        });
    };

    CatalogStudio.prototype.ensureProductsSelect2 = function () {
        var $sel = this.$root.find('#fvStudioProducts');
        if (!$sel.length || typeof $.fn.select2 !== 'function') {
            return;
        }
        var url = this.productSearchUrl || String($sel.data('search-url') || '');
        var self = this;

        if ($sel.hasClass('select2-hidden-accessible')) {
            // Ya inicializado: solo asegurar ancho al mostrar el panel.
            $sel.next('.select2-container').css('width', '100%');
            return;
        }

        var opts = {
            width: '100%',
            allowClear: true,
            placeholder: String($sel.data('placeholder') || ''),
            closeOnSelect: false,
            dropdownParent: this.$root,
            minimumInputLength: 1,
            language: {
                inputTooShort: function () {
                    return String($sel.data('msg-input-too-short') || $sel.data('placeholder') || '');
                },
                searching: function () {
                    return String($sel.data('msg-searching') || '…');
                },
                noResults: function () {
                    return String($sel.data('msg-no-results') || '');
                }
            }
        };

        if (url) {
            opts.ajax = {
                url: url,
                dataType: 'json',
                delay: 280,
                data: function (params) {
                    return {
                        search: params.term || '',
                        q: params.term || '',
                        term: params.term || '',
                        page: params.page || 1
                    };
                },
                processResults: function (data, params) {
                    params.page = params.page || 1;
                    var rows = (data && (data.items || data.results)) || [];
                    if (!Array.isArray(rows)) {
                        rows = [];
                    }
                    var mapped = rows.map(function (row) {
                        var id = row.id || row.pro_id || row.product_id;
                        return {
                            id: id,
                            text: row.text || row.name_completo || ((row.sku || '') + ' | ' + (row.name || '')),
                            sku: row.sku || '',
                            name: row.name || row.text || ''
                        };
                    }).filter(function (row) {
                        return row.id != null && Number(row.id) > 0;
                    });
                    return {
                        results: mapped,
                        pagination: {
                            more: (params.page * 15) < Number((data && data.total_count) || 0)
                        }
                    };
                },
                cache: true
            };
        }

        $sel.select2(opts);
        $sel.next('.select2-container').css('width', '100%');
        $sel.off('change.fvStudioSkus').on('change.fvStudioSkus', function () {
            self.schedulePreview();
        });
    };

    CatalogStudio.prototype.ensureOrderSelect2 = function () {
        var self = this;
        var $sel = this.$root.find('#fvStudioOrder');
        if (!$sel.length || typeof $.fn.select2 !== 'function') {
            return;
        }
        if ($sel.hasClass('select2-hidden-accessible')) {
            $sel.next('.select2-container').css('width', '100%');
            return;
        }
        var url = String($sel.data('search-url') || this.root.getAttribute('data-order-search-url') || '');
        var opts = {
            width: '100%',
            allowClear: true,
            placeholder: String($sel.data('placeholder') || ''),
            dropdownParent: $(document.body),
            minimumInputLength: 0
        };
        if (url) {
            opts.ajax = {
                url: url,
                dataType: 'json',
                delay: 280,
                data: function (params) {
                    return { q: params.term || '', search: params.term || '', page: params.page || 1 };
                },
                processResults: function (data) {
                    var rows = (data && (data.results || data.items)) || [];
                    if (!Array.isArray(rows)) rows = [];
                    return {
                        results: rows.map(function (row) {
                            return {
                                id: row.id || row.order_id,
                                text: row.text || row.label || ('#' + (row.id || row.order_id))
                            };
                        }).filter(function (r) { return r.id; }),
                        pagination: {
                            more: !!(data && data.pagination && data.pagination.more)
                        }
                    };
                },
                cache: true
            };
        }
        $sel.select2(opts);
        $sel.next('.select2-container').css('width', '100%');
        $sel.off('select2:open.fvStudioOrder').on('select2:open.fvStudioOrder', function () {
            var $search = $('.select2-container--open .select2-search__field');
            if ($search.length) {
                $search.trigger('input');
            }
        });
        $sel.off('change.fvStudioOrder').on('change.fvStudioOrder', function () {
            var id = parseInt($sel.val() || '0', 10) || 0;
            if (id > 0) {
                self.loadProductsFromOrder();
            } else {
                self.clearExternalProducts();
                self.schedulePreview();
            }
        });
    };

    CatalogStudio.prototype.getSelectedSkuIds = function () {
        var $sel = this.$root.find('#fvStudioProducts');
        var ids = [];
        if ($sel.length) {
            var vals = $sel.val();
            if (Array.isArray(vals)) {
                ids = vals.map(function (v) { return parseInt(v, 10) || 0; }).filter(Boolean);
            }
        }
        // También productos cargados desde orden/Excel.
        qsa(this.root, '#fvStudioExternalProducts input[name="products[]"]').forEach(function (inp) {
            var id = parseInt(inp.value, 10) || 0;
            if (id > 0 && ids.indexOf(id) === -1) {
                ids.push(id);
            }
        });
        return ids;
    };

    CatalogStudio.prototype.categoryNameById = function (id) {
        var found = this.categoryPool.find(function (row) {
            return parseInt(row.id, 10) === parseInt(id, 10);
        });
        return found ? String(found.name || id) : String(id);
    };

    CatalogStudio.prototype.createCategoryItem = function (id, name, withOrder) {
        var li = document.createElement('li');
        li.className = 'fv-studio-dual__item' + (withOrder ? ' is-selected' : '');
        li.setAttribute('data-id', String(id));
        li.setAttribute('draggable', withOrder ? 'true' : 'false');
        if (withOrder) {
            var orderEl = document.createElement('span');
            orderEl.className = 'fv-studio-dual__order';
            orderEl.setAttribute('aria-hidden', 'true');
            li.appendChild(orderEl);
        }
        var nameEl = document.createElement('span');
        nameEl.className = 'fv-studio-dual__name';
        nameEl.textContent = name || this.categoryNameById(id);
        li.appendChild(nameEl);
        return li;
    };

    CatalogStudio.prototype.renderCategorySummary = function () {
        if (!this.summary) return;
        var ids = this.selectedCategoryIds || [];
        var emptyMsg = this.summary.getAttribute('data-empty')
            || this.root.getAttribute('data-msg-categories-none')
            || '';
        if (ids.length === 0) {
            this.summary.innerHTML = '<p class="fv-studio-cats-summary__empty">' + escapeHtml(emptyMsg) + '</p>';
        } else {
            var html = '<ol class="fv-studio-cats-summary__list">';
            ids.forEach(function (id, idx) {
                html += '<li data-id="' + escapeHtml(String(id)) + '">'
                    + '<span class="fv-studio-cats-summary__order">' + (idx + 1) + '</span>'
                    + '<span class="fv-studio-cats-summary__name">' + escapeHtml(this.categoryNameById(id)) + '</span>'
                    + '</li>';
            }.bind(this));
            html += '</ol>';
            this.summary.innerHTML = html;
        }
        var countEl = qs(this.root, '#fvStudioCatsCount');
        if (countEl) {
            var tpl = this.root.getAttribute('data-msg-categories-count') || '{count}';
            countEl.textContent = String(tpl).split('{count}').join(String(ids.length));
        }
    };

    CatalogStudio.prototype.populateCategoriesModal = function () {
        if (!this.right) return;
        var selected = (this.selectedCategoryIds || []).slice();
        var selectedMap = {};
        selected.forEach(function (id) { selectedMap[id] = true; });

        this.right.innerHTML = '';
        selected.forEach(function (id) {
            this.right.appendChild(this.createCategoryItem(id, this.categoryNameById(id), true));
        }.bind(this));

        if (this.left) {
            this.left.innerHTML = '';
            this.categoryPool.forEach(function (row) {
                var id = parseInt(row.id, 10) || 0;
                if (id <= 0 || selectedMap[id]) return;
                this.left.appendChild(this.createCategoryItem(id, row.name, false));
            }.bind(this));
        }
        this.refreshCategoryOrders();
    };

    CatalogStudio.prototype.applyCategoriesFromModal = function () {
        this.selectedCategoryIds = this.getModalSelectedCategoryIds();
        this.renderCategorySummary();
        this.syncCategoryHidden();
        this.schedulePreview();
        this.hideCategoriesModal();
        // Persistir orden al instante (no esperar a Save).
        this.persistCategoryOrder();
    };

    CatalogStudio.prototype.persistCategoryOrder = function () {
        var self = this;
        var catalogId = parseInt(this.catalogId || this.root.getAttribute('data-catalog-id') || '0', 10) || 0;
        var url = this.root.getAttribute('data-save-categories-url') || '';
        if (catalogId <= 0 || !url) {
            return;
        }
        var ids = this.getSelectedCategoryIds();
        if (!ids.length) {
            return;
        }
        var fd = new FormData();
        fd.append('catalog_id', String(catalogId));
        ids.forEach(function (id) {
            fd.append('categories[]', String(id));
        });
        // CSRF: CodeIgniter suele poner csrf_test_name en el form del studio.
        var csrfInput = (this.form && qs(this.form, 'input[name="csrf_test_name"], input[name="_token"]'))
            || document.querySelector('input[name="csrf_test_name"], input[name="_token"]');
        if (csrfInput && csrfInput.name && csrfInput.value) {
            fd.append(csrfInput.name, csrfInput.value);
        }

        $.ajax({
            url: url,
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json'
        }).done(function (resp) {
            if (resp && (resp.success === true || resp.status === 'success')) {
                var ok = self.root.getAttribute('data-msg-categories-saved') || '';
                if (ok && window.toastr) {
                    window.toastr.success(ok);
                }
                return;
            }
            var err = (resp && (resp.msg || resp.message))
                || self.root.getAttribute('data-msg-save-error')
                || '';
            if (err) {
                window.alert(err);
            }
        }).fail(function (xhr) {
            var err = self.root.getAttribute('data-msg-save-error') || '';
            try {
                var json = JSON.parse(xhr.responseText || '{}');
                err = json.msg || json.message || err;
            } catch (e) { /* ignore */ }
            if (err) {
                window.alert(err);
            }
        });
    };

    CatalogStudio.prototype.getModalSelectedCategoryIds = function () {
        if (!this.right) return [];
        return qsa(this.right, '.fv-studio-dual__item').map(function (item) {
            return parseInt(item.getAttribute('data-id') || '0', 10) || 0;
        }).filter(function (id) { return id > 0; });
    };

    CatalogStudio.prototype.showCategoriesModal = function () {
        this.populateCategoriesModal();
        if (!this.catsModal) return;
        var $modal = $(this.catsModal);
        if ($modal.modal) {
            $modal.modal('show');
            return;
        }
        if (window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(this.catsModal).show();
        }
    };

    CatalogStudio.prototype.hideCategoriesModal = function () {
        if (!this.catsModal) return;
        var $modal = $(this.catsModal);
        if ($modal.modal) {
            $modal.modal('hide');
            return;
        }
        if (window.bootstrap && window.bootstrap.Modal) {
            var inst = window.bootstrap.Modal.getInstance(this.catsModal);
            if (inst) inst.hide();
        }
    };

    CatalogStudio.prototype.bindCategoriesModal = function () {
        var self = this;
        var editBtn = qs(this.root, '#fvStudioCatsEditBtn');
        if (editBtn) {
            editBtn.addEventListener('click', function () {
                self.showCategoriesModal();
            });
        }
        if (this.summary) {
            this.summary.addEventListener('click', function () {
                self.showCategoriesModal();
            });
            this.summary.style.cursor = 'pointer';
        }
        var applyBtn = qs(document, '#fvStudioCatsApply');
        if (applyBtn) {
            applyBtn.addEventListener('click', function () {
                self.applyCategoriesFromModal();
            });
        }
    };

    CatalogStudio.prototype.refreshCategoryOrders = function () {
        var self = this;
        if (!this.right) return;
        qsa(this.right, '.fv-studio-dual__item').forEach(function (item, idx) {
            self.ensureItemStructure(item, true);
            var orderEl = item.querySelector('.fv-studio-dual__order');
            if (orderEl) orderEl.textContent = String(idx + 1);
        });
        if (this.left) {
            qsa(this.left, '.fv-studio-dual__item').forEach(function (item) {
                self.ensureItemStructure(item, false);
            });
        }
    };

    CatalogStudio.prototype.getSelectedCategoryIds = function () {
        if (Array.isArray(this.selectedCategoryIds)) {
            return this.selectedCategoryIds.slice();
        }
        if (!this.hiddenWrap) return [];
        return qsa(this.hiddenWrap, 'input[name^="categ_selected"]').map(function (input) {
            return parseInt(input.value || '0', 10) || 0;
        }).filter(function (id) { return id > 0; });
    };

    CatalogStudio.prototype.moveSelected = function (direction) {
        if (!this.right) return;
        var item = qs(this.right, '.fv-studio-dual__item.is-active');
        if (!item) return;
        if (direction < 0 && item.previousElementSibling) {
            this.right.insertBefore(item, item.previousElementSibling);
        } else if (direction > 0 && item.nextElementSibling) {
            this.right.insertBefore(item.nextElementSibling, item);
        } else {
            return;
        }
        this.refreshCategoryOrders();
    };

    CatalogStudio.prototype.bindDualList = function () {
        var self = this;
        if (!this.right) return;

        function activate(list, item) {
            qsa(list, '.fv-studio-dual__item').forEach(function (el) {
                el.classList.toggle('is-active', el === item);
            });
        }

        var lists = [this.right];
        if (this.left) lists.push(this.left);

        lists.forEach(function (list) {
            list.addEventListener('click', function (e) {
                var item = e.target.closest('.fv-studio-dual__item');
                if (!item) return;
                activate(list, item);
            });

            list.addEventListener('dblclick', function (e) {
                var item = e.target.closest('.fv-studio-dual__item');
                if (!item) return;
                if (list === self.left) {
                    self.right.appendChild(item);
                } else if (self.left) {
                    self.left.appendChild(item);
                }
                item.classList.remove('is-active');
                self.refreshCategoryOrders();
            });
        });

        this.right.addEventListener('dragstart', function (e) {
            var item = e.target.closest('.fv-studio-dual__item');
            if (!item) return;
            self.dragItem = item;
            item.classList.add('is-dragging');
            try { e.dataTransfer.setData('text/plain', item.getAttribute('data-id') || ''); } catch (err) { /* ignore */ }
            e.dataTransfer.effectAllowed = 'move';
        });
        this.right.addEventListener('dragend', function () {
            if (self.dragItem) self.dragItem.classList.remove('is-dragging');
            self.dragItem = null;
            qsa(self.right, '.fv-studio-dual__item').forEach(function (el) {
                el.classList.remove('is-drag-over');
            });
        });
        this.right.addEventListener('dragover', function (e) {
            e.preventDefault();
            var over = e.target.closest('.fv-studio-dual__item');
            qsa(self.right, '.fv-studio-dual__item').forEach(function (el) {
                el.classList.toggle('is-drag-over', el === over && el !== self.dragItem);
            });
        });
        this.right.addEventListener('drop', function (e) {
            e.preventDefault();
            var over = e.target.closest('.fv-studio-dual__item');
            if (!self.dragItem || !over || over === self.dragItem) return;
            var rect = over.getBoundingClientRect();
            var before = (e.clientY - rect.top) < rect.height / 2;
            if (before) {
                self.right.insertBefore(self.dragItem, over);
            } else {
                self.right.insertBefore(self.dragItem, over.nextSibling);
            }
            self.refreshCategoryOrders();
        });

        var addBtn = qs(document, '#fvCatAdd');
        var remBtn = qs(document, '#fvCatRemove');
        var upBtn = qs(document, '#fvCatUp');
        var downBtn = qs(document, '#fvCatDown');

        if (addBtn) {
            addBtn.addEventListener('click', function () {
                if (!self.left) return;
                var item = qs(self.left, '.fv-studio-dual__item.is-active') || qs(self.left, '.fv-studio-dual__item');
                if (!item) return;
                self.right.appendChild(item);
                item.classList.remove('is-active');
                self.refreshCategoryOrders();
            });
        }
        if (remBtn) {
            remBtn.addEventListener('click', function () {
                if (!self.left) return;
                var item = qs(self.right, '.fv-studio-dual__item.is-active') || qs(self.right, '.fv-studio-dual__item');
                if (!item) return;
                self.left.appendChild(item);
                item.classList.remove('is-active');
                self.refreshCategoryOrders();
            });
        }
        if (upBtn) upBtn.addEventListener('click', function () { self.moveSelected(-1); });
        if (downBtn) downBtn.addEventListener('click', function () { self.moveSelected(1); });
    };

    CatalogStudio.prototype.syncCategoryHidden = function () {
        if (!this.hiddenWrap) return;
        this.hiddenWrap.innerHTML = '';
        (this.selectedCategoryIds || []).forEach(function (id, idx) {
            if (!id) return;
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'categ_selected[' + idx + ']';
            input.value = String(id);
            this.hiddenWrap.appendChild(input);
        }.bind(this));
    };

    CatalogStudio.prototype.ensureItemStructure = function (item, withOrder) {
        if (!item) return;
        var nameEl = item.querySelector('.fv-studio-dual__name');
        if (!nameEl) {
            var text = (item.textContent || '').trim();
            item.innerHTML = '';
            nameEl = document.createElement('span');
            nameEl.className = 'fv-studio-dual__name';
            nameEl.textContent = text;
            item.appendChild(nameEl);
        }
        var orderEl = item.querySelector('.fv-studio-dual__order');
        if (withOrder) {
            if (!orderEl) {
                orderEl = document.createElement('span');
                orderEl.className = 'fv-studio-dual__order';
                orderEl.setAttribute('aria-hidden', 'true');
                item.insertBefore(orderEl, nameEl);
            }
            item.setAttribute('draggable', 'true');
            item.classList.add('is-selected');
        } else {
            if (orderEl) orderEl.remove();
            item.setAttribute('draggable', 'false');
            item.classList.remove('is-selected');
        }
    };

    CatalogStudio.prototype.getSelectedTemplateKey = function () {
        var el = qs(this.root, 'input[name="template_key"]:checked');
        return el ? el.value : 'classic-grid';
    };

    CatalogStudio.prototype.schedulePreview = function (keepData) {
        var self = this;
        clearTimeout(this.previewTimer);
        this.previewTimer = setTimeout(function () {
            self.loadPreviewData(!keepData).then(function () {
                self.renderPreview();
            });
        }, keepData ? 160 : 280);
    };

    CatalogStudio.prototype.loadPreviewData = function (force) {
        var self = this;
        var source = (qs(this.root, 'input[name="product_source"]:checked') || {}).value || 'categories';
        if (!this.previewUrl) {
            this.previewSections = [];
            return Promise.resolve([]);
        }

        var payload = {};
        var formBody = new URLSearchParams();
        if (source === 'skus' || source === 'order' || source === 'excel') {
            payload.product_ids = this.getSelectedSkuIds();
            if (!payload.product_ids.length) {
                this.previewSections = [];
                return Promise.resolve([]);
            }
            payload.product_ids.forEach(function (id) {
                formBody.append('product_ids[]', String(id));
            });
        } else {
            payload.category_ids = this.getSelectedCategoryIds();
            payload.category_names = payload.category_ids.map(function (id) {
                return self.categoryNameById(id);
            });
            if (!payload.category_ids.length) {
                this.previewSections = [];
                return Promise.resolve([]);
            }
            payload.category_ids.forEach(function (id) {
                formBody.append('category_ids[]', String(id));
            });
            payload.category_names.forEach(function (name) {
                formBody.append('category_names[]', String(name));
            });
        }

        var key = JSON.stringify(payload) + '|' + source;
        if (!force && this._lastPreviewKey === key && this.previewSections && this.previewSections.length) {
            return Promise.resolve(this.previewSections);
        }

        var seq = ++this.previewRequestSeq;
        var loadingMsg = this.root.getAttribute('data-msg-loading-preview') || '';
        var bar = qs(this.root, '.fv-catalog-studio__preview-bar span');
        if (bar && loadingMsg) {
            if (!bar.getAttribute('data-label')) {
                bar.setAttribute('data-label', bar.textContent || '');
            }
            bar.textContent = loadingMsg;
        }

        return fetch(this.previewUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formBody.toString()
        }).then(function (res) {
            return res.json();
        }).then(function (resp) {
            if (seq !== self.previewRequestSeq) return self.previewSections;
            if (bar && bar.getAttribute('data-label')) {
                bar.textContent = bar.getAttribute('data-label');
            }
            if (resp && resp.success) {
                self.previewSections = Array.isArray(resp.sections) ? resp.sections : [];
                self._lastPreviewKey = key;
            } else {
                self.previewSections = [];
            }
            return self.previewSections;
        }).catch(function () {
            if (seq !== self.previewRequestSeq) return self.previewSections;
            if (bar && bar.getAttribute('data-label')) {
                bar.textContent = bar.getAttribute('data-label');
            }
            self.previewSections = [];
            return [];
        });
    };

    CatalogStudio.prototype.loadTemplateHtml = function (key) {
        var self = this;
        var fallback = 'classic-grid';
        if (this.templateCache[key]) {
            return Promise.resolve(this.templateCache[key]);
        }
        var url = this.templatesBase + encodeURIComponent(key) + '.html?v=' + Date.now();
        return fetch(url, { credentials: 'same-origin' })
            .then(function (res) {
                if (!res.ok) throw new Error('template missing');
                return res.text();
            })
            .then(function (html) {
                self.templateCache[key] = html;
                return html;
            })
            .catch(function () {
                if (key === fallback) {
                    throw new Error('template missing');
                }
                return self.loadTemplateHtml(fallback);
            });
    };

    CatalogStudio.prototype.renderPreview = function () {
        var self = this;
        var key = this.getSelectedTemplateKey();
        var catalogName = (qs(this.root, '#fvStudioName') || {}).value || 'Catalog';
        var message = (qs(this.root, '#fvStudioMessage') || {}).value || '';
        var showPrices = this.getShowPrices();
        var emptyMsg = this.root.getAttribute('data-msg-empty-preview') || '';
        var sections = this.previewSections || [];

        this.loadTemplateHtml(key).then(function (html) {
            var docHtml = html
                .replace(/\{\{catalog_name\}\}/g, escapeHtml(catalogName))
                .replace(/\{\{company_name\}\}/g, escapeHtml(self.companyName));

            // Inject category CSS once into template head
            if (docHtml.indexOf('category-block{') === -1) {
                docHtml = docHtml.replace('</style>', PREVIEW_EXTRA_CSS + '</style>');
            }

            var blob = new Blob([docHtml], { type: 'text/html' });
            var url = URL.createObjectURL(blob);
            var sidebarTop = self.sidebar ? self.sidebar.scrollTop : 0;
            self.frame.onload = function () {
                try {
                    if (self.sidebar) {
                        self.sidebar.scrollTop = sidebarTop;
                    }
                    window.scrollTo(0, 0);
                    var doc = self.frame.contentDocument;
                    if (!doc) return;
                    self.applyThemeToDocument(doc);
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
                    var logoUrl = self.getActiveLogoUrl();
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
                    self.fillPreviewFooter(doc);
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
                            /* Secciones con encabezado de categoría/grupo/subgrupo + productos. */
                            productsEl.innerHTML = sections.map(function (section) {
                                return categorySectionHtml(section, layout, showPrices);
                            }).join('');
                        }
                    }
                } catch (e) {
                    /* ignore */
                }
                URL.revokeObjectURL(url);
            };
            self.frame.src = url;
        }).catch(function () {
            self.frame.srcdoc = '<p style="padding:2rem;font-family:sans-serif;color:#64748b;">Template unavailable</p>';
        });
    };

    /**
     * Overlay de progreso del export PDF (porcentaje).
     */
    CatalogStudio.prototype.ensurePdfLoader = function () {
        var el = document.getElementById('fvStudioPdfLoader');
        if (el) return el;
        el = document.createElement('div');
        el.id = 'fvStudioPdfLoader';
        el.className = 'fv-studio-pdf-loader';
        el.setAttribute('hidden', 'hidden');
        el.innerHTML = '<div class="fv-studio-pdf-loader__card" role="status" aria-live="polite">'
            + '<span class="fv-studio-pdf-loader__spinner" aria-hidden="true"></span>'
            + '<p class="fv-studio-pdf-loader__text" data-pct-text></p>'
            + '<div class="fv-studio-pdf-loader__bar" aria-hidden="true"><span data-pct-bar></span></div>'
            + '</div>';
        document.body.appendChild(el);
        return el;
    };

    CatalogStudio.prototype.setPdfProgress = function (pct) {
        pct = Math.max(0, Math.min(100, Math.round(Number(pct) || 0)));
        var el = this.ensurePdfLoader();
        var tpl = this.root.getAttribute('data-msg-generating-pdf-pct')
            || ((this.root.getAttribute('data-msg-generating-pdf') || '') + ' {pct}%');
        var text = String(tpl).replace(/\{pct\}/g, String(pct));
        var textEl = el.querySelector('[data-pct-text]');
        var barEl = el.querySelector('[data-pct-bar]');
        if (textEl) textEl.textContent = text;
        if (barEl) barEl.style.width = pct + '%';
        el.removeAttribute('hidden');
    };

    CatalogStudio.prototype.hidePdfLoader = function () {
        var el = document.getElementById('fvStudioPdfLoader');
        if (el) el.setAttribute('hidden', 'hidden');
        this._pdfProgress = null;
        if (this._pdfPulseTimer) {
            clearInterval(this._pdfPulseTimer);
            this._pdfPulseTimer = null;
        }
    };

    CatalogStudio.prototype.postPdfFormData = function (url, fd, onProgress, fallbackMsg) {
        fallbackMsg = fallbackMsg || 'error';
        return new Promise(function (resolve, reject) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', url);
            xhr.responseType = 'blob';
            xhr.withCredentials = true;
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.upload.onprogress = function (e) {
                if (e.lengthComputable && typeof onProgress === 'function') {
                    onProgress(58 + Math.round((e.loaded / Math.max(1, e.total)) * 18));
                }
            };
            xhr.onprogress = function (e) {
                if (e.lengthComputable && e.total > 0 && typeof onProgress === 'function') {
                    onProgress(88 + Math.round((e.loaded / e.total) * 10));
                }
            };
            xhr.onload = function () {
                var ctype = String(xhr.getResponseHeader('Content-Type') || '');
                var cd = String(xhr.getResponseHeader('Content-Disposition') || '');
                if (xhr.status >= 200 && xhr.status < 300
                    && (ctype.indexOf('application/pdf') !== -1
                        || ctype.indexOf('application/zip') !== -1
                        || ctype.indexOf('octet-stream') !== -1
                        || /\.zip["']?\s*$/i.test(cd)
                        || /\.pdf["']?\s*$/i.test(cd))) {
                    resolve({ blob: xhr.response, contentType: ctype, contentDisposition: cd });
                    return;
                }
                var finishReject = function (msg) {
                    reject(new Error(msg || fallbackMsg));
                };
                var reader = new FileReader();
                reader.onload = function () {
                    var msg = fallbackMsg;
                    try {
                        var json = JSON.parse(String(reader.result || ''));
                        if (json && json.msg) msg = json.msg;
                    } catch (e) { /* ignore */ }
                    finishReject(msg);
                };
                reader.onerror = function () { finishReject(fallbackMsg); };
                if (xhr.response && xhr.response.size) {
                    reader.readAsText(xhr.response);
                } else {
                    finishReject(fallbackMsg + (xhr.status ? (' (' + xhr.status + ')') : ''));
                }
            };
            xhr.onerror = function () { reject(new Error(fallbackMsg)); };
            xhr.send(fd);
        });
    };

    /**
     * Exporta PDF desde el servidor (secciones actuales), sin enviar el HTML del iframe.
     * El HTML del preview (~miles de productos) colgaba Dompdf a los 87%.
     */
    CatalogStudio.prototype.exportPreviewPdf = function () {
        var self = this;
        var url = this.exportPdfUrl || this.root.getAttribute('data-export-pdf-url') || '';
        var emptyMsg = this.root.getAttribute('data-msg-export-pdf-empty') || '';
        var errorMsg = this.root.getAttribute('data-msg-export-pdf-error') || '';
        if (!url) {
            if (errorMsg) window.alert(errorMsg);
            return;
        }

        var catalogName = (qs(this.root, '#fvStudioName') || {}).value || 'Catalog';
        var $btn = this.$root.find('#fvStudioExportPdf');
        if ($btn.data('busy')) return;
        $btn.data('busy', true).prop('disabled', true).addClass('is-busy');

        var pulseAt = 12;
        self.setPdfProgress(2);
        self._pdfProgress = function (pct) { self.setPdfProgress(pct); };

        self.loadPreviewData(true).then(function () {
            self.setPdfProgress(18);
            var source = (qs(self.root, 'input[name="product_source"]:checked') || {}).value || 'categories';
            var theme = self.syncThemeColorsFromInputs ? self.syncThemeColorsFromInputs() : (self.themeColors || {});
            var fd = new FormData();
            fd.append('catalog_name', catalogName);
            fd.append('catalog_id', String(self.catalogId || 0));
            fd.append('show_prices', self.getShowPrices ? (self.getShowPrices() ? '1' : '0') : '1');
            fd.append('theme_banner_bg', theme.banner_bg || '');
            fd.append('theme_banner_text', theme.banner_text || '');
			var msgEl = qs(self.root, '#fvStudioMessage');
            if (msgEl && msgEl.value) fd.append('message', String(msgEl.value));
            var logoUrl = self.getActiveLogoUrl ? self.getActiveLogoUrl() : '';
            if (logoUrl && logoUrl.indexOf('blob:') !== 0 && logoUrl.indexOf('data:') !== 0) {
                fd.append('logo_url', logoUrl);
            }

            if (source === 'skus' || source === 'order' || source === 'excel') {
                var skuIds = self.getSelectedSkuIds ? self.getSelectedSkuIds() : [];
                if (!skuIds.length) throw new Error(emptyMsg || 'empty');
                skuIds.forEach(function (id) { fd.append('product_ids[]', String(id)); });
            } else {
                var catIds = self.getSelectedCategoryIds ? self.getSelectedCategoryIds() : [];
                if (!catIds.length) throw new Error(emptyMsg || 'empty');
                catIds.forEach(function (id) { fd.append('category_ids[]', String(id)); });
                catIds.forEach(function (id) {
                    fd.append('category_names[]', String(self.categoryNameById(id) || ''));
                });
            }

            self.setPdfProgress(28);
            self._pdfPulseTimer = setInterval(function () {
                // Sube hasta 94% mientras el servidor genera (antes se congelaba en 87%).
                pulseAt = Math.min(94, pulseAt + 1);
                self.setPdfProgress(pulseAt);
            }, 700);
            return self.postPdfFormData(url, fd, function (pct) {
                self.setPdfProgress(Math.max(pct, pulseAt));
            }, errorMsg);
        }).then(function (result) {
            if (self._pdfPulseTimer) {
                clearInterval(self._pdfPulseTimer);
                self._pdfPulseTimer = null;
            }
            self.setPdfProgress(97);
            var blob = result && result.blob ? result.blob : result;
            var ctype = (result && result.contentType) ? String(result.contentType) : '';
            var cd = (result && result.contentDisposition) ? String(result.contentDisposition) : '';
            if (!(blob instanceof Blob) || blob.size <= 0) {
                throw new Error(errorMsg || 'error');
            }
            var objectUrl = URL.createObjectURL(blob);
            var a = document.createElement('a');
            var safe = String(catalogName || 'catalog').replace(/[^\w\-]+/g, '_');
            var isZip = ctype.indexOf('application/zip') !== -1
                || /\.zip["']?\s*$/i.test(cd)
                || (blob.type && blob.type.indexOf('zip') !== -1);
            var m = cd.match(/filename\*?=(?:UTF-8''|")?([^\";]+)/i);
            var downloadName = m ? decodeURIComponent(m[1].replace(/["']/g, '')) : (safe + (isZip ? '.zip' : '.pdf'));
            a.href = objectUrl;
            a.download = downloadName;
            document.body.appendChild(a);
            a.click();
            a.remove();
            setTimeout(function () { URL.revokeObjectURL(objectUrl); }, 1500);
            self.setPdfProgress(100);
            window.__fvLastPdfExport = {
                ok: true,
                bytes: blob.size,
                type: blob.type || ctype,
                name: downloadName,
                at: new Date().toISOString()
            };
        }).catch(function (err) {
            window.__fvLastPdfExport = {
                ok: false,
                error: (err && err.message) ? err.message : String(err),
                at: new Date().toISOString()
            };
            var msg = (err && err.message) ? String(err.message) : '';
            if (!msg || msg === 'error' || msg === 'empty' || msg === 'network') {
                msg = errorMsg || emptyMsg || msg;
            }
            if (msg) window.alert(msg);
        }).then(function () {
            $btn.data('busy', false).prop('disabled', false).removeClass('is-busy');
            setTimeout(function () { self.hidePdfLoader(); }, 350);
        });
    };

    CatalogStudio.prototype.save = function () {
        var self = this;
        var nameEl = qs(this.root, '#fvStudioName');
        var name = nameEl ? String(nameEl.value || '').trim() : '';
        if (!this.isDefault && name === '') {
            window.alert(this.root.getAttribute('data-msg-required') || '');
            if (nameEl) nameEl.focus();
            return;
        }

        this.syncCategoryHidden();
        var source = this.getProductSource ? this.getProductSource() : 'categories';
        var fd = new FormData(this.form);
        if (source === 'categories') {
            fd.delete('products[]');
            var $selClear = this.$root.find('#fvStudioProducts');
            if ($selClear.length) {
                $selClear.val(null);
            }
        } else {
            Array.from(fd.keys()).forEach(function (key) {
                if (String(key).indexOf('categ_selected') === 0 || key === 'categories[]') {
                    fd.delete(key);
                }
            });
            fd.delete('products[]');
            this.getSelectedSkuIds().forEach(function (id) {
                fd.append('products[]', String(id));
            });
        }
        fd.set('attach', buildAttach(this.getSelectedTemplateKey(), this.getShowPrices(), this.syncThemeColorsFromInputs()));
        fd.set('template_key', this.getSelectedTemplateKey());
        fd.set('show_prices', this.getShowPrices() ? '1' : '0');
        var theme = this.themeColors || {};
        fd.set('theme_banner_bg', theme.banner_bg || '#111827');
        fd.set('theme_banner_text', theme.banner_text || '#FFFFFF');
        fd.set('theme_footer_bg', theme.footer_bg || '#111827');
        fd.set('theme_footer_text', theme.footer_text || '#E5E7EB');
        fd.set('theme_accent', theme.accent || '#B91C1C');

        // Asegurar logo en el multipart (FormData del form a veces omite file hidden).
        var logoInput = qs(this.root, '#fvStudioLogoInput');
        var clearEl = qs(this.root, '#fvStudioClearLogo');
        var uuidEl = qs(this.root, '#fvStudioLogoUuid');
        var clearVal = clearEl ? String(clearEl.value || '0') : '0';
        fd.set('clear_logo', clearVal === '1' ? '1' : '0');
        if (uuidEl) {
            fd.set('logo_uuid', String(uuidEl.value || ''));
        }
        if (clearVal !== '1' && logoInput && logoInput.files && logoInput.files[0]) {
            fd.set('logo', logoInput.files[0], logoInput.files[0].name || 'logo.jpg');
        }

        var $btn = this.$root.find('#fvStudioSave');
        var original = $btn.html();
        $btn.prop('disabled', true).html(escapeHtml(this.root.getAttribute('data-msg-saving') || '...'));

        $.ajax({
            url: this.saveUrl,
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json'
        }).done(function (resp) {
            if (resp && (resp.success === true || resp.status === 'success')) {
                var ok = self.root.getAttribute('data-msg-save-ok') || '';
                if (ok && window.toastr) {
                    window.toastr.success(ok);
                } else if (ok) {
                    window.alert(ok);
                }
                window.location.href = self.backUrl;
                return;
            }
            var err = (resp && (resp.msg || resp.message)) || self.root.getAttribute('data-msg-save-error') || '';
            window.alert(err);
        }).fail(function (xhr) {
            var err = self.root.getAttribute('data-msg-save-error') || '';
            try {
                var json = JSON.parse(xhr.responseText || '{}');
                err = json.msg || json.message || err;
            } catch (e) { /* ignore */ }
            window.alert(err);
        }).always(function () {
            $btn.prop('disabled', false).html(original);
        });
    };

    $(function () {
        var root = document.getElementById('fvCatalogStudio');
        if (!root) return;
        document.body.classList.add('fv-catalog-studio-page');
        window.scrollTo(0, 0);
        new CatalogStudio(root);
    });
})(jQuery);
