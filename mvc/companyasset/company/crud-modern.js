/**
 * FullVendor — Mejoras globales CRUD: DataTables, filtros y contenedores.
 * No altera la configuración específica de cada módulo; solo defaults y clases UI.
 */
(function (window, $) {
    'use strict';

    if (!$) {
        return;
    }

    var FV_CRUD = {
        initialized: false,

        init: function () {
            if (this.initialized) {
                return;
            }
            this.initialized = true;
            this.applyDataTableDefaults();
            this.enhanceDom();
            this.enhanceButtons();
            this.enhancePageHeadings();
            this.initResponsiveCrudTables();
            this.bindDataTableHooks();
            this.bindClearSearchReload();
        },

        applyDataTableDefaults: function () {
            if (!$.fn.dataTable) {
                return;
            }

            var lang = window.fvDataTableLanguage || null;

            $.extend(true, $.fn.dataTable.defaults, {
                deferRender: true,
                processing: true,
                autoWidth: false,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, 250], [10, 25, 50, 100, 250]],
                dom: '<"fv-dt-top"lf>rt<"fv-dt-bottom"ip>',
                language: lang || $.fn.dataTable.defaults.language || {}
            });
        },

        enhanceDom: function () {
            this.enhanceFilterPanels();
            this.enhanceToolbars();
            this.wrapTables();
        },

        enhanceFilterPanels: function () {
            $('#main-content #form-buscar-filtro').each(function () {
                var $form = $(this);
                $form.addClass('fv-filter-form');

                var $panel = $form.closest('.col-12.mb-4');
                if ($panel.length) {
                    $panel.addClass('fv-filter-panel');
                    return;
                }

                $panel = $form.closest('.row.border');
                if ($panel.length) {
                    $panel.addClass('fv-filter-panel fv-filter-panel--enhanced');
                    return;
                }

                $form.parent().addClass('fv-filter-panel');
            });

            $('#main-content .col-12.p-4 > .row.border').each(function () {
                $(this).addClass('fv-filter-panel fv-filter-panel--enhanced');
            });

            $('#main-content .dataTables_wrapper.dt-bootstrap4 > #form-buscar-filtro')
                .closest('.dataTables_wrapper')
                .addClass('fv-filter-toolbar');
        },

        enhanceToolbars: function () {
            $('#main-content #module-actions').addClass('fv-crud-toolbar');

            $('#main-content .card-body > .row:first-child .form-group').each(function () {
                var $group = $(this);
                if ($group.find('.btn-success, .btn-primary').length >= 2) {
                    $group.addClass('fv-crud-toolbar');
                }
            });
        },

        shouldSkipButton: function ($btn) {
            if (!$btn || !$btn.length) {
                return true;
            }
            if ($btn.closest('.pagination, .dataTables_paginate, .fv-company-nav, .navbar, .modal-header .close, .swal2-container').length) {
                return true;
            }
            if ($btn.closest('.fv-catalog-studio, #fvCatalogStudio').length) {
                return true;
            }
            if ($btn.hasClass('navbar-toggler') || $btn.hasClass('page-link')) {
                return true;
            }
            return false;
        },

        enhanceButtons: function () {
            var self = this;
            $('#main-content .btn, .modal .btn').each(function () {
                var $btn = $(this);
                if (self.shouldSkipButton($btn)) {
                    return;
                }
                if ($btn.closest('.fv-global-search-modal, .swal2-container, .fv-company-nav, .navbar').length) {
                    return;
                }
                if ($btn.closest('.fv-catalog-studio, #fvCatalogStudio').length) {
                    return;
                }
                if ($btn.closest('.fv-products-view-toggle').length) {
                    return;
                }
                if (!$btn.hasClass('fv-crud-btn')) {
                    $btn.addClass('fv-crud-btn');
                }
                if ($btn.hasClass('btn-sm') && !$btn.hasClass('fv-crud-btn--row')) {
                    if ($btn.closest('.fv-products-view-toggle, .fv-inventory-stock-filter').length) {
                        return;
                    }
                    if ($btn.is('[data-view-mode]')) {
                        return;
                    }
                    // Botones con texto visible (no solo icono de fila).
                    if ($btn.hasClass('fv-btn-with-label') || $btn.is('#btnAddFormat, #btnDeleteFormat')) {
                        return;
                    }
                    $btn.addClass('fv-crud-btn--row');
                }
            });
        },

        pageIconMap: {
            dashboard: 'fa-solid fa-chart-simple',
            products: 'fa-solid fa-box',
            product: 'fa-solid fa-box',
            inventory: 'fa-solid fa-clipboard-list',
            inventario: 'fa-solid fa-clipboard-list',
            customers: 'fa-solid fa-sack-dollar',
            customer: 'fa-solid fa-user',
            orders: 'fa-solid fa-paper-plane',
            order: 'fa-solid fa-file-invoice',
            warehouses: 'fa-solid fa-warehouse',
            warehouse: 'fa-solid fa-warehouse',
            catalog: 'fa-solid fa-file-circle-plus',
            units: 'fa-solid fa-ruler-combined',
            terms: 'fa-solid fa-file-invoice-dollar',
            groups: 'fa-solid fa-users',
            users: 'fa-solid fa-user-lock',
            userprofile: 'fa-solid fa-id-card',
            sellerprofile: 'fa-solid fa-id-card',
            seller: 'fa-solid fa-user-tie',
            currencies: 'fa-solid fa-coins',
            currency: 'fa-solid fa-coins',
            logs: 'fa-solid fa-database',
            import: 'fa-solid fa-file-import',
            profile: 'fa-solid fa-gear',
            cart: 'fa-solid fa-cart-shopping',
            categories: 'fa-solid fa-tags',
            categorias: 'fa-solid fa-tags',
            country: 'fa-solid fa-globe',
            state: 'fa-solid fa-map',
            city: 'fa-solid fa-city',
            search: 'fa-solid fa-magnifying-glass',
            logo: 'fa-solid fa-image',
            email: 'fa-solid fa-envelope',
            sms: 'fa-solid fa-comment-sms',
            lists: 'fa-solid fa-list',
            add: 'fa-solid fa-plus',
            edit: 'fa-solid fa-pen-to-square',
            view: 'fa-solid fa-eye',
            export: 'fa-solid fa-file-export',
            csvimport: 'fa-solid fa-file-import'
        },

        resolvePageIcon: function () {
            var parts = (window.location.pathname || '').toLowerCase().split('/').filter(Boolean);
            var map = this.pageIconMap;
            for (var i = parts.length - 1; i >= 0; i--) {
                if (map[parts[i]]) {
                    return map[parts[i]];
                }
            }
            return 'fa-solid fa-layer-group';
        },

        splitHeadingMarkup: function (text) {
            var parts = String(text || '').trim().split(/\s+/);
            if (!parts.length || !parts[0]) {
                return '';
            }
            var last = parts.pop();
            var lead = parts.join(' ');
            var accent = '<span class="fv-form-hero__accent"></span>';
            var $accent = $(accent).text(last);
            if (!lead) {
                return $accent.prop('outerHTML');
            }
            return $('<span></span>').text(lead + ' ').html() + $accent.prop('outerHTML');
        },

        enhancePageHeadings: function () {
            var self = this;
            var selector = [
                '#main-content .page-header h1.page-title',
                '#main-content .page-header h2.page-title',
                '#main-content .page-header h3.page-title',
                '#main-content h1.page-title',
                '#main-content h2.page-title',
                '#main-content h3.page-title'
            ].join(', ');

            $(selector).each(function () {
                var $title = $(this);
                if ($title.closest('.fv-form-hero, .fv-page-heading').length
                    || $title.hasClass('fv-form-hero__title')
                    || $title.hasClass('fv-page-heading__title')
                    || $title.attr('data-fv-heading-enhanced') === '1') {
                    return;
                }

                var text = $.trim($title.text());
                if (!text) {
                    return;
                }

                var $header = $title.closest('.page-header');
                var $anchor = $('<span class="fv-form-hero-anchor" hidden></span>');
                $title.before($anchor);
                var description = $.trim($title.attr('data-fv-page-description') || '');
                var $subtitle = $title.siblings('.fv-page-subtitle').first();
                if (!description && $subtitle.length) {
                    description = $.trim($subtitle.text());
                    $subtitle.remove();
                }

                var icon = self.resolvePageIcon();
                var $hero = $('<section class="fv-form-hero"></section>');
                $hero.append(
                    '<span class="fv-form-hero__watermark" aria-hidden="true"><i class="' + icon + '"></i></span>'
                );

                var $main = $('<div class="fv-form-hero__main"></div>');
                $main.append(
                    '<span class="fv-form-hero__icon" aria-hidden="true"><i class="' + icon + '"></i></span>'
                );
                var $copy = $('<div class="fv-form-hero__copy"></div>');
                $title.attr('data-fv-heading-enhanced', '1');
                $title.addClass('fv-form-hero__title fv-page-heading__title page-title').html(self.splitHeadingMarkup(text));
                $copy.append($title);
                if (description) {
                    $copy.append($('<p class="fv-form-hero__text"></p>').text(description));
                }
                $main.append($copy);
                $hero.append($main);

                var $actions = $('<div class="fv-form-hero__actions"></div>');
                if (description) {
                    var $info = $('<button type="button" class="fv-form-hero__info"></button>');
                    $info.attr('title', description).attr('aria-label', description);
                    $info.append('<i class="fa-solid fa-circle-info" aria-hidden="true"></i>');
                    $actions.append($info);
                }

                var $actionHost = $header.length && $header.parent().is('[class*="col-"]') ? $header.parent().siblings() : $();
                $actionHost.find('> a.btn, > button.btn').each(function () {
                    $actions.append($(this).addClass('fv-form-hero__cta'));
                });

                if ($actions.children().length) {
                    $hero.append($actions);
                }

                if ($header.length) {
                    $header.addClass('fv-page-header').empty().append($hero);
                } else {
                    $anchor.replaceWith($hero);
                    $hero.wrap('<div class="page-header fv-page-header"></div>');
                }
            });
        },

        wrapTables: function () {
            $('#main-content table#infoTable, #main-content table.dataTable').each(function () {
                var $table = $(this);
                if ($table.attr('data-fv-server-list') === '1') {
                    return;
                }
                if ($table.closest('.fv-table-shell').length) {
                    return;
                }

                if ($table.closest('.dataTables_wrapper').length) {
                    var $wrapper = $table.closest('.dataTables_wrapper');
                    if (!$wrapper.parent().hasClass('fv-table-shell')) {
                        $wrapper.wrap('<div class="fv-table-shell"></div>');
                    }
                    return;
                }

                var $responsive = $table.closest('.table-responsive');
                if ($responsive.length && !$responsive.hasClass('fv-table-shell')) {
                    $responsive.addClass('fv-table-shell');
                    return;
                }

                $table.wrap('<div class="fv-table-shell"></div>');
            });
        },

        modernizeWrapper: function ($wrapper) {
            if (!$wrapper || !$wrapper.length) {
                return;
            }
            $wrapper.addClass('fv-dt-modern');

            var $filterInput = $wrapper.find('.dataTables_filter input');
            if ($filterInput.length && !$filterInput.attr('placeholder')) {
                $filterInput.attr('placeholder', $filterInput.attr('aria-label') || '…');
            }
        },

        initResponsiveCrudTables: function () {
            $('.fv-modern-grid table[data-fv-server-list="1"]').each(function () {
                var $table = $(this);
                if ($table.data('fvCrudResponsive')) {
                    return;
                }
                $table.data('fvCrudResponsive', true);
            });
        },

        bindDataTableHooks: function () {
            var self = this;

            $(document).on('init.dt', function (event, settings) {
                var api = new $.fn.dataTable.Api(settings);
                var $wrapper = $(api.table().container());
                self.modernizeWrapper($wrapper);
            });

            $(document).on('draw.dt', function (event, settings) {
                var api = new $.fn.dataTable.Api(settings);
                var $wrapper = $(api.table().container());
                self.modernizeWrapper($wrapper);
                self.enhanceButtons();
            });
        },

        /**
         * Al vaciar el buscador (prsearch), reenviar el filtro para volver a listar todo.
         */
        bindClearSearchReload: function () {
            if (this._clearSearchBound) {
                return;
            }
            this._clearSearchBound = true;

            $(document).on('input', '#form-buscar-filtro input[name="prsearch"]', function () {
                var input = this;
                var value = String(input.value || '').trim();

                if (value !== '') {
                    input._fvHadSearch = true;
                    return;
                }

                var hadSearch = !!input._fvHadSearch
                    || String(input.defaultValue || '').trim() !== '';
                if (!hadSearch) {
                    return;
                }

                input._fvHadSearch = false;

                if (typeof window.buscar_porfiltro === 'function') {
                    window.buscar_porfiltro();
                    return;
                }

                var form = input.form || document.getElementById('form-buscar-filtro');
                if (form) {
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        $(form).trigger('submit');
                    }
                }
            });
        }
    };

    window.FVCrudModern = FV_CRUD;

    function boot() {
        FV_CRUD.init();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    $(window).on('load', function () {
        FV_CRUD.enhanceDom();
        FV_CRUD.enhanceButtons();
        FV_CRUD.enhancePageHeadings();
    });

    if ($.fn.dataTable) {
        FV_CRUD.applyDataTableDefaults();
    }
})(window, window.jQuery);
