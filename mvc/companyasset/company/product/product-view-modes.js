/**
 * FullVendor — modos de vista productos (tabla / tarjetas) y filtro categoría con avatar.
 */
(function (window, $) {
    'use strict';

    if (!$) {
        return;
    }

    var STORAGE_KEY = 'fv-products-view-mode';

    function lang(key, fallback) {
        var L = window.fvProductsViewLang || {};
        var v = L[key];
        return (typeof v === 'string' && v !== '') ? v : fallback;
    }

    function getSavedMode() {
        try {
            return window.localStorage.getItem(STORAGE_KEY) || 'table';
        } catch (e) {
            return 'table';
        }
    }

    function saveMode(mode) {
        try {
            window.localStorage.setItem(STORAGE_KEY, mode);
        } catch (e) {
            /* ignore */
        }
    }

    function initPopovers($root) {
        if (typeof $.fn.popover !== 'function') {
            return;
        }
        var $els = ($root || $(document)).find('[data-toggle="popover-hover"]');
        $els.each(function () {
            var $t = $(this);
            if ($t.data('bs.popover')) {
                try { $t.popover('dispose'); } catch (e) { /* ignore */ }
            }
        });
        var tpl = '<div class="popover fv-popover-img-preview" role="tooltip"><div class="arrow"></div><div class="popover-body"></div></div>';
        $els.popover({
            html: true,
            trigger: 'hover focus',
            placement: 'right',
            container: 'body',
            template: tpl,
            title: '',
            sanitize: false,
            content: function () {
                var $el = $(this);
                var img = String($el.attr('data-img') || '').trim();
                if (!img || img.indexOf('data:') === 0) {
                    var $thumb = $el.find('img').first();
                    if ($thumb.length) {
                        var liveSrc = String($thumb.attr('src') || '').trim();
                        if (liveSrc && liveSrc.indexOf('data:') !== 0) {
                            img = liveSrc;
                        }
                    }
                }
                if (!img) {
                    return '';
                }
                var $wrap = $('<div class="fv-popover-img-preview__inner"/>');
                $wrap.append($('<img>', {
                    src: img,
                    alt: '',
                    class: 'fv-popover-img-preview__photo',
                    draggable: false
                }));
                return $wrap;
            }
        }).on('inserted.bs.popover', function () {
            var inst = $(this).data('bs.popover');
            if (inst && typeof inst.getTipElement === 'function') {
                var tip = inst.getTipElement();
                if (tip) {
                    tip.classList.add('fv-popover-img-preview');
                }
            }
        });
    }

    function formatCategoryOption(state) {
        if (!state.id) {
            return state.text;
        }
        var $el = $(state.element);
        var thumb = $el.data('cat-thumb') || '';
        var name = $el.data('cat-name') || state.text;
        var fallbacks = $el.data('cat-fallbacks') || [];
        if (typeof fallbacks === 'string') {
            try {
                fallbacks = JSON.parse(fallbacks);
            } catch (e) {
                fallbacks = [];
            }
        }
        if (!Array.isArray(fallbacks)) {
            fallbacks = [];
        }
        var $wrap = $('<span class="fv-cat-select-option"></span>');
        if (thumb) {
            var $img = $('<img>', { src: thumb, alt: '', class: 'fv-cat-select-option__img' });
            $img.on('error', function () {
                var i = parseInt($img.attr('data-fv-img-i') || '0', 10);
                if (i < fallbacks.length) {
                    $img.attr('data-fv-img-i', String(i + 1));
                    $img.attr('src', fallbacks[i]);
                    return;
                }
                $img.replaceWith($('<span class="fv-cat-select-option__ph"><i class="fa-solid fa-tags"></i></span>'));
            });
            $wrap.append($img);
        } else {
            $wrap.append($('<span class="fv-cat-select-option__ph"><i class="fa-solid fa-tags"></i></span>'));
        }
        $wrap.append($('<span class="fv-cat-select-option__label"></span>').text(name));
        return $wrap;
    }

    function initCategorySelect() {
        var $sel = $('#categoryId');
        if (!$sel.length || typeof $.fn.select2 !== 'function') {
            return;
        }
        if ($sel.hasClass('select2-hidden-accessible')) {
            $sel.select2('destroy');
        }
        $sel.select2({
            width: '100%',
            minimumResultsForSearch: 0,
            dropdownParent: $sel.closest('.filter-select-wrapper').length ? $sel.closest('.col-lg-2, .col-md-4, .col-sm-6, .col-12').first() : $('body'),
            templateResult: formatCategoryOption,
            templateSelection: formatCategoryOption
        }).off('change.fvCatFilter').on('change.fvCatFilter', function () {
            if (typeof window.buscar_porfiltro === 'function') {
                window.buscar_porfiltro();
            }
        });
    }

    function applyViewMode(mode) {
        var isCards = mode === 'cards';
        var $tableWrap = $('#fv-products-table-view');
        var $cards = $('#fv-products-cards-view');

        if (!$tableWrap.length || !$cards.length) {
            return;
        }

        $tableWrap.toggleClass('d-none', isCards);
        $cards.toggleClass('d-none', !isCards).attr('aria-hidden', isCards ? 'false' : 'true');

        $('.fv-products-view-toggle [data-view-mode]').each(function () {
            var $btn = $(this);
            var active = $btn.data('view-mode') === mode;
            $btn.toggleClass('btn-primary', active)
                .toggleClass('btn-outline-secondary', !active)
                .attr('aria-pressed', active ? 'true' : 'false');
        });

        if (isCards) {
            initPopovers($cards);
        }
    }

    function initViewToggle() {
        var $toggle = $('.fv-products-view-toggle');
        if (!$toggle.length) {
            return;
        }

        $(document).off('click.fvProductsView', '.fv-products-view-toggle [data-view-mode]')
            .on('click.fvProductsView', '.fv-products-view-toggle [data-view-mode]', function () {
                var mode = $(this).data('view-mode');
                saveMode(mode);
                applyViewMode(mode);
            });

        applyViewMode(getSavedMode());
    }

    function syncProductCheckboxes($source) {
        var val = $source.val();
        var checked = $source.prop('checked');
        $('#infoTable .checkBoxClass[value="' + val + '"], #fv-products-cards-view .checkBoxClass[value="' + val + '"]')
            .prop('checked', checked);
        if (typeof window.updateBulkButtonsState === 'function') {
            window.updateBulkButtonsState();
        }
    }

    function initCheckboxSync() {
        $(document).off('change.fvProdCheckSync', '#infoTable .checkBoxClass, #fv-products-cards-view .checkBoxClass')
            .on('change.fvProdCheckSync', '#infoTable .checkBoxClass, #fv-products-cards-view .checkBoxClass', function () {
                syncProductCheckboxes($(this));
            });
    }

    function boot() {
        initCategorySelect();
        initViewToggle();
        initCheckboxSync();
        initPopovers($('#main-content'));
    }

    window.FVProductViewModes = {
        refreshPopovers: initPopovers,
        applyViewMode: applyViewMode
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    $(window).on('load', boot);
})(window, window.jQuery);
