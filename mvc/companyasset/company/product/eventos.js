$(function () {
    /**
     * Persistencia de selección entre páginas (mismo filtro, distinto ?page=).
     *
     * Modelo de estado (sessionStorage):
     *   {
     *     v: 2,
     *     inputTodos: 'todos' | 'pagina',
     *     // Solo cuando inputTodos === 'todos': IDs que el usuario desmarcó manualmente.
     *     excludedIds: [String],
     *     // Solo cuando inputTodos === 'pagina': IDs explícitamente marcados (acumulados entre páginas).
     *     includedIds: [String]
     *   }
     */
    function fvProductsFilterSignature() {
        var q = window.location.search ? window.location.search.substring(1) : '';
        if (!q) {
            return 'default';
        }
        return q.split('&').filter(function (p) {
            return p && !/^page=/i.test(p) && !/^per_page=/i.test(p);
        }).sort().join('&') || 'default';
    }

    function fvProductsSelectionStorageKey() {
        var form = document.getElementById('form-buscar-filtro');
        if (!form || form.getAttribute('data-fv-products-selection') !== '1') {
            return null;
        }
        var cid = form.getAttribute('data-list-company-id') || '0';
        return 'fv_products_bulk_sel_' + cid + '_' + fvProductsFilterSignature();
    }

    function fvProductsSelectionRead() {
        var k = fvProductsSelectionStorageKey();
        if (!k || !window.sessionStorage) {
            return null;
        }
        try {
            var raw = sessionStorage.getItem(k);
            if (!raw) {
                return null;
            }
            var st = JSON.parse(raw);
            if (!st) {
                return null;
            }
            if (st.v === 2) {
                st.excludedIds = Array.isArray(st.excludedIds) ? st.excludedIds.map(String) : [];
                st.includedIds = Array.isArray(st.includedIds) ? st.includedIds.map(String) : [];
                return st;
            }
            // Migración desde v=1 (solo inputTodos + ids visibles)
            if (st.v === 1) {
                var inputTodos = st.inputTodos === 'todos' ? 'todos' : 'pagina';
                if (inputTodos === 'todos' && st.allHeaderChecked) {
                    return { v: 2, inputTodos: 'todos', excludedIds: [], includedIds: [] };
                }
                return {
                    v: 2,
                    inputTodos: 'pagina',
                    excludedIds: [],
                    includedIds: (Array.isArray(st.ids) ? st.ids : []).map(String)
                };
            }
            return null;
        } catch (e) {
            return null;
        }
    }

    function fvProductsSelectionWrite(st) {
        var k = fvProductsSelectionStorageKey();
        if (!k || !window.sessionStorage) {
            return;
        }
        try {
            sessionStorage.setItem(k, JSON.stringify(st));
        } catch (e) { /* quota / private mode */ }
    }

    /**
     * Toma el estado anterior y lo combina con la selección actualmente visible en la página.
     */
    function fvProductsSelectionPersist() {
        var k = fvProductsSelectionStorageKey();
        if (!k) {
            return;
        }

        var prev = fvProductsSelectionRead() || { v: 2, inputTodos: 'pagina', excludedIds: [], includedIds: [] };
        var inputTodos = ($('#input-todos').val() === 'todos') ? 'todos' : 'pagina';

        var $rows = $('input.checkBoxClass');
        var visibleIds = [];
        var visibleCheckedIds = [];
        $rows.each(function () {
            var id = String($(this).val());
            visibleIds.push(id);
            if ($(this).is(':checked')) {
                visibleCheckedIds.push(id);
            }
        });
        var visibleIdSet = new Set(visibleIds);
        var visibleCheckedSet = new Set(visibleCheckedIds);

        var state;
        if (inputTodos === 'todos') {
            // Mantener excludedIds previos; ajustar con los cambios visibles.
            var prevExcluded = prev.inputTodos === 'todos' ? new Set(prev.excludedIds) : new Set();
            visibleIds.forEach(function (id) {
                if (visibleCheckedSet.has(id)) {
                    prevExcluded.delete(id);
                } else {
                    prevExcluded.add(id);
                }
            });
            state = { v: 2, inputTodos: 'todos', excludedIds: Array.from(prevExcluded), includedIds: [] };
        } else {
            // Modo página: acumular includedIds.
            var prevIncluded = prev.inputTodos === 'pagina' ? new Set(prev.includedIds) : new Set();
            visibleIds.forEach(function (id) {
                if (visibleCheckedSet.has(id)) {
                    prevIncluded.add(id);
                } else {
                    prevIncluded.delete(id);
                }
            });
            state = { v: 2, inputTodos: 'pagina', excludedIds: [], includedIds: Array.from(prevIncluded) };
        }
        fvProductsSelectionWrite(state);
    }

    function fvProductsLang() {
        try {
            return (options.lang && options.lang.cmp_products) ? options.lang.cmp_products : {};
        } catch (e) {
            return {};
        }
    }

    function fvProductsHideExpandAlert() {
        return !!window.fvHideExpandSelectionAlert
            || $('#contextual-actions-container').attr('data-hide-expand-alert') === '1';
    }

    function fvProductsClearExpandStripe() {
        $('#contextual-actions-container').removeClass('alert alert-warning alert-primary');
        $('#expansible-info').text('');
        $('#expand-selection.alert').hide();
    }

    function fvProductsShowExpandStripe(mode) {
        if (fvProductsHideExpandAlert()) {
            fvProductsClearExpandStripe();
            return;
        }
        var $container = $('#contextual-actions-container');
        if (mode === 'todos') {
            $container.removeClass('alert-warning').addClass('alert alert-primary');
        } else {
            $container.removeClass('alert-primary').addClass('alert alert-warning');
        }
        $('#expand-selection.alert').addClass('show').show();
    }

    function fvProductsTotalFilteredCount() {
        var t = parseInt($('#expand-selection-info').data('total-filtered-items'), 10);
        return isNaN(t) ? 0 : t;
    }

    function fvProductsSetExpansibleInfo(text) {
        if (fvProductsHideExpandAlert()) {
            $('#expansible-info').text('');
            return;
        }
        $('#expansible-info').text(text || '');
    }

    /**
     * Aplica el estado guardado al DOM (checkboxes, banners, contadores).
     */
    function fvProductsSelectionRestore() {
        if (!fvProductsSelectionStorageKey() || !$('#infoTable input.checkBoxClass').length) {
            updateBulkButtonsState();
            return;
        }

        var st = fvProductsSelectionRead();
        var lang = fvProductsLang();

        var $rows = $('input.checkBoxClass');

        if (!st) {
            // Si el servidor renderizó la página en modo "todos" (input_todos=todos en URL)
            // y no hay estado guardado todavía, asumir modo todos sin exclusiones y persistirlo.
            if ($('#input-todos').val() === 'todos') {
                st = { v: 2, inputTodos: 'todos', excludedIds: [], includedIds: [] };
                fvProductsSelectionWrite(st);
            } else {
                $('#input-todos').val('pagina');
                $('#ckbCheckAll').prop('checked', false);
                $rows.prop('checked', false);
                fvProductsClearExpandStripe();
                updateBulkButtonsState();
                return;
            }
        }

        if (st.inputTodos === 'todos') {
            $('#input-todos').val('todos');
            var excluded = new Set(st.excludedIds);
            $rows.each(function () {
                $(this).prop('checked', !excluded.has(String($(this).val())));
            });
            var totalChecked = $rows.filter(':checked').length;
            var totalRows = $rows.length;
            $('#ckbCheckAll').prop('checked', totalRows > 0 && totalChecked === totalRows);
            fvProductsShowExpandStripe('todos');
            $('#link-todos').text(lang.desmarcar_todos || '');
            $('#ckbCheckAll').attr('title', lang.desmarcar_todos || lang.toltip_paginas || '');
            var effectiveCount = Math.max(0, fvProductsTotalFilteredCount() - excluded.size);
            fvProductsSetExpansibleInfo(effectiveCount + ' ' + (lang.bulk_will_be_used || ''));
        } else {
            $('#input-todos').val('pagina');
            var included = new Set(st.includedIds);
            $rows.each(function () {
                $(this).prop('checked', included.has(String($(this).val())));
            });
            var checked = $rows.filter(':checked').length;
            var total = $rows.length;
            $('#ckbCheckAll').prop('checked', total > 0 && checked === total);
            if (included.size > 0) {
                fvProductsShowExpandStripe('pagina');
                fvProductsSetExpansibleInfo(included.size + ' ' + (lang.bulk_will_be_used || ''));
            } else {
                fvProductsClearExpandStripe();
            }
            $('#link-todos').text(lang.marcar_todos || '');
            $('#ckbCheckAll').attr('title', lang.toltip_paginas || '');
        }

        updateBulkButtonsState();
    }

    function setContextualBulkButtonsEnabled(enabled) {
        $('#contextual-actions .contextual-action-btn').each(function () {
            var $el = $(this);
            if (enabled) {
                $el.removeClass('btn-secondary btn-primary btn-danger disabled fv-bulk-idle').addClass('btn-success');
                $el.attr('aria-disabled', 'false');
                if (this.tagName === 'BUTTON') {
                    $el.prop('disabled', false);
                } else {
                    $el.css('pointer-events', '');
                }
            } else {
                $el.removeClass('btn-success btn-primary btn-danger').addClass('btn-secondary disabled fv-bulk-idle');
                $el.attr('aria-disabled', 'true');
                if (this.tagName === 'BUTTON') {
                    $el.prop('disabled', true);
                } else {
                    $el.css('pointer-events', 'none');
                }
            }
        });
    }

    function fvProductsHasBulkSelection() {
        var inputTodos = $('#input-todos').val() === 'todos' ? 'todos' : 'pagina';
        if (inputTodos === 'todos') {
            var excluded = 0;
            var st = fvProductsSelectionRead();
            if (st && st.inputTodos === 'todos' && Array.isArray(st.excludedIds)) {
                excluded = st.excludedIds.length;
            }
            return Math.max(0, fvProductsTotalFilteredCount() - excluded) > 0;
        }

        var seen = {};
        var visibleCount = 0;
        $('#infoTable tbody .checkBoxClass:checked, #fv-products-cards-view .checkBoxClass:checked').each(function () {
            var id = String($(this).val() || '');
            if (!id || seen[id]) {
                return;
            }
            seen[id] = true;
            visibleCount += 1;
        });
        if (visibleCount > 0) {
            return true;
        }

        var stp = fvProductsSelectionRead();
        if (!stp || stp.inputTodos !== 'pagina' || !Array.isArray(stp.includedIds) || !stp.includedIds.length) {
            return false;
        }
        var onPage = {};
        $('#infoTable tbody .checkBoxClass, #fv-products-cards-view .checkBoxClass').each(function () {
            var id = String($(this).val() || '');
            if (id) {
                onPage[id] = true;
            }
        });
        for (var i = 0; i < stp.includedIds.length; i++) {
            if (!onPage[String(stp.includedIds[i])]) {
                return true;
            }
        }
        return false;
    }

    function updateBulkButtonsState() {
        setContextualBulkButtonsEnabled(fvProductsHasBulkSelection());
    }

    window.updateBulkButtonsState = updateBulkButtonsState;
    window.fvProductsHasBulkSelection = fvProductsHasBulkSelection;

    document.addEventListener('click', function (e) {
        var btn = e.target && e.target.closest ? e.target.closest('#contextual-actions .contextual-action-btn') : null;
        if (!btn) {
            return;
        }
        if (btn.disabled || btn.classList.contains('fv-bulk-idle') || btn.getAttribute('aria-disabled') === 'true' || !fvProductsHasBulkSelection()) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
        }
    }, true);

    // Exponer helper para que los modals construyan el payload masivo.
    window.fvProductsCollectBulkData = function () {
        var inputTodos = $('#input-todos').val() === 'todos' ? 'todos' : 'pagina';
        var st = fvProductsSelectionRead();
        if (inputTodos === 'todos') {
            var excluded = (st && st.inputTodos === 'todos' && Array.isArray(st.excludedIds))
                ? st.excludedIds.slice() : [];
            // Incluir los desmarcados visibles que aún no se hayan persistido.
            var visibleUnchecked = $('input.checkBoxClass').filter(function () { return !$(this).is(':checked'); })
                .map(function () { return String($(this).val()); }).get();
            var merged = new Set(excluded);
            visibleUnchecked.forEach(function (id) { merged.add(id); });
            // Quitar los que sí están marcados (por si reactivó alguno).
            $('input.checkBoxClass:checked').each(function () { merged.delete(String($(this).val())); });
            return { products: 'todos', excluded_ids: JSON.stringify(Array.from(merged)) };
        }
        var included = new Set();
        if (st && st.inputTodos === 'pagina' && Array.isArray(st.includedIds)) {
            st.includedIds.forEach(function (id) { included.add(String(id)); });
        }
        // Refuerzo: filas visibles marcadas; quitar las desmarcadas visibles.
        $('input.checkBoxClass:checked').each(function () { included.add(String($(this).val())); });
        $('input.checkBoxClass').filter(function () { return !$(this).is(':checked'); })
            .each(function () { included.delete(String($(this).val())); });
        return { products: JSON.stringify(Array.from(included)), excluded_ids: '' };
    };

    $(document).on('click', '.sort', function (e) {
        e.preventDefault();
        fvProductsSelectionPersist();

        let order_type = $(this).data('order_type'),
            order_sort = $(this).data('sort'),
            sort_direction = $(this).data('sort_direction');

        $('#order_type').val(order_type);
        $('#order_sort').val(order_sort);
        $('#sort_direction').val(sort_direction);
        $('#form-buscar-filtro').submit();
    });

    $(document).on("click", "#btn-submit-textsearch", function () {
        buscar_porfiltro();
    });

    $(document).on('click', '#sepComma', function () {
        let input_todos = $('#input-todos').val();
        $('#form-full-vendor').attr('action',
            options.url + '/product/exportCSVproducts?separador=coma&cantidad=' + input_todos);
    });

    $(document).on('click', '#sepSemicolon', function () {
        let input_todos = $('#input-todos').val();
        $('#form-full-vendor').attr('action',
            options.url + '/product/exportCSVproducts?separador=puntoycoma&cantidad=' + input_todos);
    });

    $(document).ready(function () {

        $(".js-example").select2({
            dropdownParent: $("#myselect2"),
            width: '100%'
        });

        // Popover imagen: Bootstrap se carga en el footer de forma asíncrona; sin reintentos $.fn.popover puede no existir aún.
        (function initProductImagePopovers(attempt) {
            attempt = attempt || 0;
            if (typeof jQuery === 'undefined' || typeof jQuery.fn.popover !== 'function') {
                if (attempt < 120) {
                    setTimeout(function () { initProductImagePopovers(attempt + 1); }, 50);
                }
                return;
            }
            var $els = $('[data-toggle="popover-hover"]');
            if (!$els.length) {
                return;
            }
            $els.each(function () {
                try {
                    var $t = jQuery(this);
                    if ($t.data('bs.popover')) {
                        $t.popover('dispose');
                    }
                } catch (e) { /* ignore */ }
            });
            var popoverImgTpl = '<div class="popover fv-popover-img-preview" role="tooltip"><div class="arrow"></div><div class="popover-body"></div></div>';
            $els.popover({
                html: true,
                trigger: 'hover focus',
                placement: 'right',
                container: 'body',
                template: popoverImgTpl,
                title: '',
                sanitize: false,
                content: function () {
                    var $el = jQuery(this);
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
                    var $wrap = jQuery('<div class="fv-popover-img-preview__inner"/>');
                    $wrap.append(jQuery('<img>', {
                        src: img,
                        alt: '',
                        class: 'fv-popover-img-preview__photo',
                        draggable: false
                    }));
                    return $wrap;
                }
            }).on('inserted.bs.popover', function () {
                var inst = jQuery(this).data('bs.popover');
                if (inst && typeof inst.getTipElement === 'function') {
                    var tip = inst.getTipElement();
                    if (tip) {
                        tip.classList.add('fv-popover-img-preview');
                    }
                }
            });
            if (window.FVProductViewModes && typeof window.FVProductViewModes.refreshPopovers === 'function') {
                window.FVProductViewModes.refreshPopovers($('#main-content'));
            }
        })(0);

    });

    // Toggle expand/collapse "Use all records found?"
    $(document).on('click', '#link-todos', function () {
        var lang = fvProductsLang();
        var input_todos = $('#input-todos');
        var expanding = input_todos.val() === 'pagina';

        if (expanding) {
            input_todos.val('todos');
            $('.checkBoxClass').prop('checked', true);
            $('#ckbCheckAll').prop('checked', true);
            $(this).text(lang.desmarcar_todos || '');
            fvProductsShowExpandStripe('todos');
            // Persistir modo todos sin exclusiones.
            fvProductsSelectionWrite({ v: 2, inputTodos: 'todos', excludedIds: [], includedIds: [] });
            fvProductsSetExpansibleInfo(fvProductsTotalFilteredCount() + ' ' + (lang.bulk_will_be_used || ''));
        } else {
            input_todos.val('pagina');
            $('.checkBoxClass').prop('checked', false);
            $('#ckbCheckAll').prop('checked', false);
            $('#ckbCheckAll').attr('title', lang.toltip_paginas || '');
            $(this).text(lang.marcar_todos || '');
            fvProductsClearExpandStripe();
            fvProductsSelectionWrite({ v: 2, inputTodos: 'pagina', excludedIds: [], includedIds: [] });
        }
        updateBulkButtonsState();
    });

    // Checkbox del header (#ckbCheckAll)
    $(document).on('change', '#ckbCheckAll', function () {
        var lang = fvProductsLang();
        var checked = $(this).prop('checked');
        $('.checkBoxClass').prop('checked', checked);
        var inputTodos = $('#input-todos');

        if (checked) {
            // Marcar todo equivale a "Use all records"
            inputTodos.val('todos');
            fvProductsShowExpandStripe('todos');
            $('#link-todos').text(lang.desmarcar_todos || '');
            $(this).attr('title', lang.desmarcar_todos || lang.toltip_paginas || '');
            fvProductsSelectionWrite({ v: 2, inputTodos: 'todos', excludedIds: [], includedIds: [] });
            fvProductsSetExpansibleInfo(fvProductsTotalFilteredCount() + ' ' + (lang.bulk_will_be_used || ''));
        } else {
            inputTodos.val('pagina');
            $('#link-todos').text(lang.marcar_todos || '');
            $(this).attr('title', lang.toltip_paginas || '');
            fvProductsClearExpandStripe();
            fvProductsSelectionWrite({ v: 2, inputTodos: 'pagina', excludedIds: [], includedIds: [] });
        }
        updateBulkButtonsState();
    });

    // Cambio individual en una fila
    $(document).on('change', '.checkBoxClass', function () {
        var lang = fvProductsLang();
        var inputTodos = $('#input-todos').val() === 'todos' ? 'todos' : 'pagina';

        if (inputTodos === 'todos') {
            // No cambiar de modo: solo persistir exclusiones.
            fvProductsSelectionPersist();
            var st = fvProductsSelectionRead();
            var excluded = (st && Array.isArray(st.excludedIds)) ? st.excludedIds.length : 0;
            var effective = Math.max(0, fvProductsTotalFilteredCount() - excluded);
            fvProductsSetExpansibleInfo(effective + ' ' + (lang.bulk_will_be_used || ''));
            // Header refleja si todos los visibles siguen marcados
            var total = $('.checkBoxClass').length;
            var checked = $('.checkBoxClass:checked').length;
            $('#ckbCheckAll').prop('checked', total > 0 && total === checked);
        } else {
            fvProductsSelectionPersist();
            var st2 = fvProductsSelectionRead();
            var includedTotal = (st2 && Array.isArray(st2.includedIds)) ? st2.includedIds.length : 0;
            fvProductsSetExpansibleInfo(includedTotal + ' ' + (lang.bulk_will_be_used || ''));
            var total2 = $('.checkBoxClass').length;
            var checked2 = $('.checkBoxClass:checked').length;
            $('#ckbCheckAll').prop('checked', total2 > 0 && total2 === checked2);
            if (includedTotal > 0) {
                fvProductsShowExpandStripe('pagina');
            } else {
                fvProductsClearExpandStripe();
            }
        }
        updateBulkButtonsState();
    });

    // Persistir antes de paginar y propagar input_todos=todos al href para que el
    // server-side renderice la siguiente página con todos los checkboxes marcados.
    // El restore se encargará de desmarcar los IDs excluidos al cargar la página.
    $(document).on('click', '#list .pagination a', function (e) {
        fvProductsSelectionPersist();
        var $a = $(this);
        var href = $a.attr('href');
        if (!href || /^javascript:/i.test(href)) {
            return;
        }
        var inputTodos = $('#input-todos').val() === 'todos' ? 'todos' : 'pagina';
        var hrefNoTodos = href.replace(/([?&])input_todos=[^&]*/i, function (m, sep) {
            return sep === '?' ? '?' : '';
        });
        hrefNoTodos = hrefNoTodos.replace(/\?&/, '?').replace(/&&+/g, '&').replace(/[?&]$/, '');
        var newHref;
        if (inputTodos === 'todos') {
            var sep = hrefNoTodos.indexOf('?') === -1 ? '?' : '&';
            newHref = hrefNoTodos + sep + 'input_todos=todos';
        } else {
            newHref = hrefNoTodos;
        }
        if (newHref !== href) {
            e.preventDefault();
            $a.attr('href', newHref);
            window.location.href = newHref;
        }
    });

    $(document).ready(function () {
        setTimeout(fvProductsSelectionRestore, 0);
    });

    $(document).on('click', '.boton_estado', function (e) {

        e.preventDefault();

        let icon = 'error';
        if ($(this).hasClass("btn-success")) {
            icon = 'success';
        }

        let url = $(this).attr('href');
        let loader_text = $(this).data('loader');
        let boton = $(this);

        let fila_padre = $(this).parent().parent();
        let fila_padre_fondo = $(this).parent().parent().hasClass('fondo-default');

        $.ajax({

            url: url,
            type: 'get',
            beforeSend: function () {

                let contenido_boton =
                    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>  ' +
                    loader_text;

                boton.html(contenido_boton);
            },
            success: function (response) {

                let columna_boton = boton.parent();
                columna_boton.html(response);
                if (fila_padre_fondo) {
                    fila_padre.removeClass('fondo-default');
                } else {
                    fila_padre.addClass('fondo-default');
                }

            }
        });

    });

});
