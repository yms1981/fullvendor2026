$(function () {
    const SEARCH_HIGHLIGHT_CLASS = 'customer-search-highlight';

    function ensureSearchHighlightStyle() {
        if (document.getElementById('customer-search-highlight-style')) {
            return;
        }
        const style = document.createElement('style');
        style.id = 'customer-search-highlight-style';
        style.textContent = '.' + SEARCH_HIGHLIGHT_CLASS + ' { background-color: #fff3a3; color: inherit; padding: 0 1px; border-radius: 2px; }';
        document.head.appendChild(style);
    }

    function escapeRegExp(str) {
        return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function clearSearchHighlights(scope) {
        const marks = scope.querySelectorAll('mark.' + SEARCH_HIGHLIGHT_CLASS);
        marks.forEach(function(mark) {
            const parent = mark.parentNode;
            if (!parent) {
                return;
            }
            parent.replaceChild(document.createTextNode(mark.textContent || ''), mark);
            parent.normalize();
        });
    }

    function highlightNodeText(textNode, regex) {
        const text = textNode.nodeValue;
        if (!text || !regex.test(text)) {
            regex.lastIndex = 0;
            return;
        }
        regex.lastIndex = 0;
        const frag = document.createDocumentFragment();
        let cursor = 0;
        let match = null;

        while ((match = regex.exec(text)) !== null) {
            const matchText = match[0];
            const idx = match.index;
            if (idx > cursor) {
                frag.appendChild(document.createTextNode(text.slice(cursor, idx)));
            }
            const mark = document.createElement('mark');
            mark.className = SEARCH_HIGHLIGHT_CLASS;
            mark.textContent = matchText;
            frag.appendChild(mark);
            cursor = idx + matchText.length;
        }

        if (cursor < text.length) {
            frag.appendChild(document.createTextNode(text.slice(cursor)));
        }

        if (textNode.parentNode) {
            textNode.parentNode.replaceChild(frag, textNode);
        }
    }

    function applySearchHighlightToCell(cell, regex) {
        const walker = document.createTreeWalker(cell, NodeFilter.SHOW_TEXT, null, false);
        const textNodes = [];
        let node = walker.nextNode();
        while (node) {
            if (node.parentElement && !node.parentElement.closest('mark.' + SEARCH_HIGHLIGHT_CLASS)) {
                textNodes.push(node);
            }
            node = walker.nextNode();
        }
        textNodes.forEach(function(textNode) {
            highlightNodeText(textNode, regex);
        });
    }

    function applyCustomerSearchHighlight() {
        const searchInput = document.querySelector('#form-buscar-filtro input[name="prsearch"]');
        const searchValue = searchInput ? String(searchInput.value || '').trim() : '';
        const dataCells = document.querySelectorAll('#infoTable tbody td[data-field]');
        if (!dataCells.length) {
            return;
        }

        dataCells.forEach(function(cell) {
            clearSearchHighlights(cell);
        });

        if (!searchValue) {
            return;
        }

        const terms = searchValue.split(/\s+/).map(function(term) {
            return term.trim();
        }).filter(function(term) {
            return term.length > 0;
        });

        if (!terms.length) {
            return;
        }

        const regex = new RegExp('(' + terms.map(escapeRegExp).join('|') + ')', 'gi');
        dataCells.forEach(function(cell) {
            applySearchHighlightToCell(cell, regex);
        });
    }

    ensureSearchHighlightStyle();
    applyCustomerSearchHighlight();

    $(document).on('input', '#form-buscar-filtro input[name="prsearch"]', function() {
        applyCustomerSearchHighlight();
    });

    $(document).on('shown.bs.modal', '#modalremoveSeller, #modalasignSeller', function() {
        $(this).find('select.selectpicker').each(function() {
            var $s = $(this);
            if (typeof $s.selectpicker !== 'function') {
                return;
            }
            // Reinit con container=body: si ya estaba inicializado dentro del modal, el menú se corta.
            if ($s.parent().hasClass('bootstrap-select')) {
                $s.selectpicker('destroy');
            }
            $s.selectpicker({
                container: 'body',
                dropupAuto: false,
                liveSearch: true,
                actionsBox: true,
                width: '100%',
                size: 4
            });
        });
    });

    // Al elegir vendedor, cerrar el desplegable para volver al modal y poder Aceptar.
    $(document).on('changed.bs.select', '#select_multpleseller, #select_toremoveseller', function() {
        var $s = $(this);
        if (typeof $s.selectpicker !== 'function') {
            return;
        }
        window.setTimeout(function() {
            var $wrap = $s.parent('.bootstrap-select');
            if ($wrap.hasClass('open') || $wrap.hasClass('show')) {
                $s.selectpicker('toggle');
            }
            // Menú anexado a body (container: body)
            $('body > .dropdown-menu.show, body > .bootstrap-select .dropdown-menu').removeClass('show').hide();
            $wrap.removeClass('open show');
            $wrap.find('.dropdown-toggle').attr('aria-expanded', 'false');
        }, 0);
    });

    $(document).on('hidden.bs.modal', '#modalremoveSeller, #modalasignSeller', function() {
        // Cerrar menús huérfanos en body al cerrar el modal.
        $('.bootstrap-select.open, .bootstrap-select.show').removeClass('open show');
        $('body > .dropdown-menu').removeClass('show').hide();
    });

    $(document).on('click', '.sort', function(e) {
        e.preventDefault()

        let order_type = $(this).data('order_type'),
            order_sort = $(this).data('sort'),
            sort_direction = $(this).data('sort_direction')

        $('#order_type').val(order_type)
        $('#order_sort').val(order_sort)
        $('#sort_direction').val(sort_direction)
        $('#form-buscar-filtro').submit()
    });

    $(document).on("click", "#btn-submit-textsearch", function(e) {
        buscar_porfiltro()
    })

    $(document).on('click', '#sepComma', function(e) {
        let input_todos = $('#input-todos').val()
        $('#form-full-vendor').attr('action',
            options.url + '/product/exportCSVproducts?separador=coma&cantidad=' + input_todos)

    })

    $(document).on('click', '#sepSemicolon', function(e) {
        let input_todos = $('#input-todos').val()
        $('#form-full-vendor').attr('action',
            options.url + '/product/exportCSVproducts?separador=puntoycoma&cantidad=' + input_todos)
    })

    $(document).ready(function() {

        $(".js-example").select2({
            dropdownParent: $("#myselect2"),
            width: '100%'
        });
        // popover de imagenes
        $('[data-toggle="popover-hover"]').popover({
            html: true,
            trigger: 'hover',
            placement: 'left',
            content: function() {
                return '<img src="' + $(this).data('img') + '" class="img-thumbnail" />';
            }
        });

    });

    /*remover categoria en grupo */

//marcar todos  link-todos (igual que /company/products, con fallback lang cmp_clientes/cmp_products)
    $(document).on('click', '#link-todos', function(e) {

        const lang = (options.lang && (options.lang.cmp_clientes || options.lang.cmp_products)) || {}

        var input_todos = $('#input-todos');
        var expanding = input_todos.val() === 'pagina';

        // Mismo criterio que products: usar .checkBoxClass en toda la página
        var $rowCheckboxes = $('.checkBoxClass');
        var $headerCheckbox = $('#ckbCheckAll');

        if (expanding === true) {
            if (typeof window._linkTodosExpanding === 'undefined') window._linkTodosExpanding = false;
            window._linkTodosExpanding = true;
            input_todos.val('todos');
            $headerCheckbox.prop('checked', true);
            $rowCheckboxes.prop('checked', true);
            window._linkTodosExpanding = false;
            if (!$rowCheckboxes.length) {
                console.log('[customers/link-todos] expanding: no se encontraron .checkBoxClass');
            }
            var totalFilteredCount = $('#expand-selection-info').data('total-filtered-items');
            $(this).text(lang.desmarcar_todos || "Don't use all records?");
            $('#contextual-actions-container').removeClass('alert-warning').addClass('alert-primary');
            $('#expansible-info').text(totalFilteredCount + ' ' + (lang.bulk_will_be_used || 'will be used'));
            $('#contextual-actions button,#contextual-actions a').prop('disabled', false);
        } else {
            input_todos.val('pagina');
            $headerCheckbox.prop('checked', false);
            $rowCheckboxes.prop('checked', false);
            $headerCheckbox.attr('title', lang.toltip_paginas || 'Select all on this page');
            $(this).text(lang.marcar_todos || 'Use all records?');
            $('#contextual-actions-container').removeClass('alert-primary').addClass('alert-warning');
            $('#expansible-info').text('0 ' + (lang.bulk_will_be_used || 'will be used'));
            $('#contextual-actions button,#contextual-actions a').prop('disabled', true);
        }

    })

// Igual que /company/products: sincronizar checkboxes de filas con el de cabecera
    function syncRowCheckboxesWithHeader() {
        var $header = $('#ckbCheckAll');
        if (!$header.length) {
            console.log('[customers/ckbCheckAll] No se encontró #ckbCheckAll');
            return false;
        }
        var isChecked = $header.is(':checked');
        var $rowCheckboxes = $('.checkBoxClass');
        if (!$rowCheckboxes.length) {
            console.log('[customers/ckbCheckAll] No se encontraron .checkBoxClass en la página');
            return false;
        }
        $rowCheckboxes.prop('checked', isChecked);
        return true;
    }

// mostrar alerta todos + sincronizar (mismo comportamiento que /company/products)
    $(document).on('change', '#ckbCheckAll', function(e) {
        if (window._linkTodosExpanding) return;
        // Igual que products: seleccionar/deseleccionar todos los checkboxes visibles
        var $allRows = $('.checkBoxClass');
        if (!$allRows.length) {
            console.log('[customers/ckbCheckAll] change: no se encontró ningún .checkBoxClass en la página');
        }
        $allRows.prop('checked', $(this).prop('checked'));
        var checkedCheckboxCount = $('.checkBoxClass:checked').length;
        if ($(this).is(':checked') && checkedCheckboxCount === 0 && $allRows.length > 0) {
            console.log('[customers/ckbCheckAll] change: header checked pero ningún .checkBoxClass quedó marcado (posible fallo al marcar)');
        }
        const lang = (options.lang && (options.lang.cmp_clientes || options.lang.cmp_products)) || {}
        $('#contextual-actions button,#contextual-actions a').prop('disabled', checkedCheckboxCount === 0);
        var inputTodos = $('#input-todos');
        if ($(this).is(':checked')) {
            $('#expand-selection.alert').addClass('show').show();
            $('#contextual-actions-container').addClass('alert alert-warning');
            inputTodos.val('pagina');
            $('#link-todos').text(lang.marcar_todos || 'Use all records?');
            $('#expansible-info').text(checkedCheckboxCount + ' ' + (lang.bulk_will_be_used || 'will be used'));
        } else {
            inputTodos.val('pagina');
            $('#contextual-actions-container').removeClass('alert alert-warning alert-primary');
            $('#expand-selection.alert').hide();
            $('#expansible-info').text('');
        }
    })

    // Click en cabecera: reforzar sync (como respaldo, igual que products)
    $(document).on('click', '#ckbCheckAll', function(e) {
        if (window._linkTodosExpanding) return;
        var self = this;
        setTimeout(function() {
            var $rows = $('.checkBoxClass');
            if ($rows.length) {
                $rows.prop('checked', $(self).is(':checked'));
            } else {
                console.log('[customers/ckbCheckAll] click: no se encontraron .checkBoxClass');
            }
        }, 0);
    })

    // Igual que /company/products: al cambiar un checkbox de fila, actualizar cabecera y contador
    $(document).on('change', '.checkBoxClass', function(e) {
        if (window._linkTodosExpanding) return;
        const lang = (options.lang && (options.lang.cmp_clientes || options.lang.cmp_products)) || {}
        var checkedCheckboxesCount = $('.checkBoxClass:checked').length;

        $('#contextual-actions button,#contextual-actions a').prop('disabled', checkedCheckboxesCount === 0);
        $('#expansible-info').text(checkedCheckboxesCount + ' ' + (lang.bulk_will_be_used || 'will be used'));
        var inputTodos = $('#input-todos');
        inputTodos.val('pagina');
        if (!this.checked) {
            $('#ckbCheckAll').prop('checked', false);
            $('#contextual-actions-container').removeClass('alert alert-warning alert-primary');
            $('#expand-selection.alert').hide();
        }
        if ($('.checkBoxClass:not(:checked)').length === 0) {
            $('#ckbCheckAll').prop('checked', true);
            $('#contextual-actions-container').addClass('alert').addClass((inputTodos.val() === 'todos') ? 'alert-primary' : 'alert-warning');
            $('#expand-selection.alert').addClass('show').show();
        }
        if (checkedCheckboxesCount === 0) {
            $('#expansible-info').text('');
        }
    })

    $(document).on('click', '.boton_estado', function(e) {

        e.preventDefault();

        let icon = 'error'
        if ($(this).hasClass("btn-success")) {
            icon = 'success'
        }

        /*mostrar_toast(icon, mensaje)*/
        let url = $(this).attr('href');
        let loader_text = $(this).data('loader');
        let boton = $(this)

        let fila_padre = $(this).parent().parent()
        let fila_padre_fondo = $(this).parent().parent().hasClass('fondo-default')

        $.ajax({

            url: url,
            type: 'get',
            beforeSend: function() {

                let contenido_boton =
                    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>  ' +
                    loader_text

                boton.html(contenido_boton)
            },
            success: function(response) {

                let columna_boton = boton.parent()
                columna_boton.html(response)
                if (fila_padre_fondo) {
                    fila_padre.removeClass('fondo-default')
                } else {
                    fila_padre.addClass('fondo-default')
                }

            }
        })
    })

    $(document).on('click', '#btn-export-customers, .fv-customers-export-btn, #btn-export', function (e) {
        e.preventDefault();
        const $btn = $(this);
        if ($btn.data('exporting')) {
            return;
        }
        $btn.data('exporting', true).addClass('disabled').attr('aria-disabled', 'true');

        const payload = buildBasePayload();
        payload.separator = 'coma';

        // Sin selección válida: exportar según filtros actuales.
        if (payload.all_checked === 'pagina' && (!payload.customer_ids || payload.customer_ids.length === 0)) {
            payload.all_checked = 'todos';
        }

        const baseUrl = (typeof options !== 'undefined' && options.url) ? options.url : '';
        const exportUrl = baseUrl.replace(/\/$/, '') + '/customers/CSVExport';
        const failMsg = (options && options.lang && (options.lang['cmp-clientes'] || options.lang.cmp_clientes)
            && ((options.lang['cmp-clientes'] && options.lang['cmp-clientes'].lang) || (options.lang.cmp_clientes && options.lang.cmp_clientes.lang) || {}).error_export)
            ? (((options.lang['cmp-clientes'] && options.lang['cmp-clientes'].lang) || (options.lang.cmp_clientes && options.lang.cmp_clientes.lang) || {}).error_export)
            : 'Export failed';

        fetch(exportUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': '*/*',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload),
        })
            .then(async res => {
                const contentType = (res.headers.get('Content-Type') || '').toLowerCase();
                const disposition = res.headers.get('Content-Disposition') || '';
                const looksLikeFile = disposition.toLowerCase().indexOf('attachment') !== -1
                    || contentType.indexOf('spreadsheet') !== -1
                    || contentType.indexOf('officedocument') !== -1
                    || contentType.indexOf('octet-stream') !== -1;

                if (!res.ok || (contentType.indexOf('application/json') !== -1 && !looksLikeFile) || contentType.indexOf('text/html') !== -1) {
                    let message = failMsg;
                    try {
                        const error = await res.json();
                        if (error && error.message && String(error.message).length < 400) {
                            message = error.message;
                        }
                    } catch (parseErr) {
                        // keep default — no mostrar HTML de error de Laravel
                    }
                    if (typeof swal === 'function') {
                        swal({ title: message, icon: 'error' });
                    } else {
                        window.alert(message);
                    }
                    throw new Error(message);
                }

                const blob = await res.blob();
                if (!blob || blob.size < 32) {
                    throw new Error(failMsg);
                }

                const url = window.URL.createObjectURL(blob);
                let fileName = 'customer_list.xlsx';
                if (disposition && disposition.indexOf('filename=') !== -1) {
                    fileName = disposition
                        .split('filename=')[1]
                        .replace(/["']/g, '')
                        .trim();
                }

                const a = document.createElement('a');
                a.href = url;
                a.download = fileName;
                document.body.appendChild(a);
                a.click();
                a.remove();
                window.setTimeout(function () {
                    window.URL.revokeObjectURL(url);
                }, 1000);

                if ($('#modal_export').length) {
                    $('#modal_export').modal('hide');
                }
            })
            .catch(function (err) {
                console.log(err && err.message ? err.message : err);
            })
            .finally(function () {
                $btn.data('exporting', false).removeClass('disabled').removeAttr('aria-disabled');
            });
    });

    function getSelectedCustomerIds() {
        const checked = document.querySelectorAll('.checkBoxClass:checked');

        return Array.from(checked)
            .map(cb => parseInt(cb.value, 10))
            .filter(id => !Number.isNaN(id));
    }

    /** Valores de multiselect; bootstrap-select no siempre sincroniza con .val() nativo. */
    function readSellerMultiselectValues(selectSelector, rootEl) {
        const $el = rootEl ? $(rootEl).find(selectSelector) : $(selectSelector);
        if (!$el.length) {
            return [];
        }
        let raw = null;
        if (typeof $el.selectpicker === 'function') {
            try {
                raw = $el.selectpicker('val');
            } catch (e) {
                raw = null;
            }
        }
        if (raw === null || typeof raw === 'undefined') {
            raw = $el.val();
        }
        let arr = Array.isArray(raw) ? raw : (raw === null || raw === '' ? [] : [raw]);
        arr = arr.map(function(v) {
            const n = parseInt(String(v), 10);
            return Number.isNaN(n) ? null : n;
        }).filter(function(v) {
            return v !== null;
        });
        return arr;
    }

    function getFilters() {
        var st = document.querySelector('[name="status"]');
        var gr = document.querySelector('[name="grupoId"]');
        var se = document.querySelector('[name="vendedorId"]');
        var te = document.querySelector('[name="termId"]');
        var pr = document.querySelector('[name="prsearch"]');
        if (!st || !gr || !se || !te || !pr) {
            return {};
        }
        return {
            status: st.value,
            group: gr.value,
            seller: se.value,
            term: te.value,
            search: pr.value,
        };
    }

    function buildBasePayload() {
        var inputTodos = document.getElementById('input-todos');
        var customerIds = getSelectedCustomerIds();
        var allChecked = inputTodos && inputTodos.value ? inputTodos.value : 'todos';
        if (!allChecked || (allChecked === 'pagina' && customerIds.length === 0)) {
            allChecked = 'todos';
        }
        return {
            customer_ids: customerIds,
            all_checked: allChecked,
            filters: getFilters(),
        };
    }

    function handleBulkFormSubmit(formSelector, modalSelector, selectSelector = null, payloadName = null, newFormData = true) {
        // Delegación en document: los modales pueden estar en el DOM después de que CoreLoader
        // ejecute este script; $(selector).on('submit') sobre 0 nodos no enlaza nada.
        $(document)
            .off('submit.fvCustomerBulk', formSelector)
            .on('submit.fvCustomerBulk', formSelector, function(e) {
                e.preventDefault();

                const form = e.currentTarget;
                const url = form.getAttribute('action');
                if (!url) {
                    return;
                }
                const formData = newFormData ? new FormData() : new FormData(form);
                const payload = { ...Object.fromEntries(formData.entries()), ...buildBasePayload() };
                const lang = (options.lang && options.lang.cmp_clientes) || {};

                if (payload.all_checked === 'pagina' && (!payload.customer_ids || payload.customer_ids.length === 0)) {
                    swal({
                        title: lang.seleccione_cliente || lang.validate_oncustomer || 'Select at least one customer',
                        icon: 'warning'
                    });
                    return;
                }

                if (selectSelector && payloadName) {
                    const modalRoot = form.closest('.modal');
                    payload[payloadName] = readSellerMultiselectValues(selectSelector, modalRoot || null);
                }

                if (payloadName === 'sellers' && (!payload.sellers || payload.sellers.length === 0)) {
                    swal({
                        title: lang.must_selectseller || lang.select_sellers || 'Select at least one seller',
                        icon: 'warning'
                    });
                    return;
                }

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(payload),
                })
                    .then(response => response.text().then(text => {
                        let data = {};
                        if (text) {
                            try {
                                data = JSON.parse(text);
                            } catch (parseError) {
                                data = {};
                            }
                        }

                        var done = response.status >= 200 && response.status < 300;
                        if (done) {
                            try {
                                $(modalSelector).modal('hide');
                            } catch (err) { /* seguir y mostrar aviso */ }
                            var fallbackOk = url.indexOf('unassign') !== -1 || url.indexOf('Unassign') !== -1
                                ? (lang.seller_removed || '')
                                : (lang.seller_asigned || '');
                            var successTitle = (data && typeof data.message === 'string' && data.message)
                                ? data.message
                                : fallbackOk;
                            var doReload = function() {
                                var u = window.location.href.split('#')[0];
                                if (window.location.replace) {
                                    window.location.replace(u);
                                } else {
                                    window.location.href = u;
                                }
                            };
                            if (typeof swal === 'function') {
                                swal({
                                    title: successTitle,
                                    icon: 'success',
                                }).then(function() {
                                    doReload();
                                });
                            } else {
                                doReload();
                            }
                        } else {
                            let title = data.message ?? lang.error_occurred ?? 'An error occurred';
                            const errorsMessages = Object.values(data.errors || {})
                                .reduce((acc, curr) => acc.concat(curr), [])
                                .join('\n') || null;

                            swal({
                                title: title,
                                text: errorsMessages,
                                icon: 'error'
                            });
                        }
                    }))
                    .catch(error => {
                        swal({
                            icon: 'error',
                            title: lang.error_connection || 'Connection error',
                            text: error.message
                        });
                    });
            });
    }

    handleBulkFormSubmit('#form-asign-seller', '#modalasignSeller', '#select_multpleseller', 'sellers');
    handleBulkFormSubmit('#form-remove-seller', '#modalremoveSeller', '#select_toremoveseller', 'sellers');

    // Send catalog: destinatarios = selección de grilla (o “todos” filtrados) × catálogos elegidos; API + catalog_cart + enlace catalogo/producto (alineado con /company/catalog)
    $(document).on('submit', '#form-send', function (e) {
        e.preventDefault();
        const lang = (options.lang && options.lang.cmp_clientes) || {};
        const inputTodos = document.getElementById('input-todos');
        const allMode = inputTodos ? inputTodos.value : 'pagina';
        const customerIds = getSelectedCustomerIds();
        const catalogsRaw = $('#selectCatalogs').val();
        const catalogs = Array.isArray(catalogsRaw) ? catalogsRaw : (catalogsRaw ? [catalogsRaw] : []);

        if (allMode === 'pagina' && (!customerIds || customerIds.length === 0)) {
            swal({
                title: lang.seleccione_cliente || lang.validate_oncustomer || 'Select at least one customer',
                icon: 'warning'
            });
            return;
        }
        if (!catalogs.length) {
            swal({
                title: lang.bulk_send_catalog_select_catalogs || 'Select at least one catalog',
                icon: 'warning'
            });
            return;
        }
        const typeChecked = document.querySelector('#form-send input[name="type"]:checked');
        const sendType = (typeChecked && typeChecked.value) ? String(typeChecked.value) : 'email';
        if (['email', 'sms', 'whatsapp'].indexOf(sendType) === -1) {
            swal({
                title: lang.bulk_send_catalog_invalid_channel || lang.error_generico || 'Error',
                icon: 'warning'
            });
            return;
        }

        const form = e.target;
        const apiBaseUrl = form.getAttribute('data-api-base-url') || '/api';
        const apiToken = form.getAttribute('data-api-token') || '';
        const companyIdRaw = form.getAttribute('data-company-id') || '0';
        const companyId = parseInt(companyIdRaw, 10) || 0;
        const url = apiBaseUrl.replace(/\/$/, '') + '/v2/customer/bulkSendCatalog';

        if (!apiToken || companyId <= 0) {
            swal({
                title: lang.error_generico || 'Error',
                icon: 'error'
            });
            return;
        }
        const expEl = form.querySelector('[name="expiration_days"]');
        const expVal = parseInt(expEl && expEl.value ? expEl.value : '15', 10);
        const expirationDays = isNaN(expVal) || expVal < 1 ? 15 : expVal;
        const catTitleEl = form.querySelector('[name="catalog_title"]');
        const emailBodyEl = form.querySelector('[name="email_text"]');

        const payload = Object.assign({}, buildBasePayload(), {
            company_id: companyId,
            catalogs: catalogs.map(String),
            expiration_days: expirationDays,
            type: sendType,
            catalog_title: catTitleEl && typeof catTitleEl.value === 'string' ? catTitleEl.value : '',
            email_text: emailBodyEl && typeof emailBodyEl.value === 'string' ? emailBodyEl.value : '',
        });

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': 'Bearer ' + apiToken
            },
            body: JSON.stringify(payload)
        })
            .then(function (response) {
                return response.json().then(function (data) {
                    if (response.ok) {
                        swal({
                            title: data.message || lang.bulk_catalog_sent || 'OK',
                            icon: 'success'
                        });
                        $('#exampleModal').modal('hide');
                    } else {
                        var title = typeof data.message === 'string'
                            ? data.message
                            : (data.message && data.message.message ? data.message.message : 'Error');
                        var errorsMessages = Object.values(data.errors || {})
                            .reduce(function (acc, curr) { return acc.concat(curr); }, [])
                            .join('\n') || null;
                        swal({
                            title: title,
                            text: errorsMessages,
                            icon: 'error'
                        });
                    }
                });
            })
            .catch(function (error) {
                swal({
                    icon: 'error',
                    title: 'Error de conexión',
                    text: error.message
                });
            });
    });

    window.eliminarClienteGrupo = function () {
        const {lang} = {...options.lang.cmp_clientes}
        const payload = buildBasePayload();

        swal({
            title: lang.eliminar_cliente_seleccionados,
            icon: 'error',
            buttons: {
                cancel: {
                    text: lang.cancelar,
                    value: false,
                    visible: true,
                    className: "sweet-danager",
                    closeModal: true,
                },
                confirm: {
                    text: lang.delete,
                    value: true,
                    visible: true,
                    className: "sweet-warning",
                    closeModal: true
                },
            },
        }).then((willDelete) => {
            if (willDelete) {
                fetch(options.url + '/customers/bulkDelete', {
                    method: 'DELETE',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                })
                    .then(response => response.json().then(data => {
                        if (response.ok) {
                            var doReload = function() {
                                var u = window.location.href.split('#')[0];
                                if (window.location.replace) {
                                    window.location.replace(u);
                                } else {
                                    window.location.href = u;
                                }
                            };
                            swal({
                                title: data.message || 'Operación realizada con éxito',
                                icon: 'success'
                            }).then(function() {
                                doReload();
                            });
                        } else {
                            let title = data.message ?? 'Ocurrió un error';
                            const errorsMessages = Object.values(data.errors || {})
                                .reduce((acc, curr) => acc.concat(curr), [])
                                .join('\n') || null;

                            swal({
                                title: title,
                                text: errorsMessages,
                                icon: 'error'
                            });
                        }
                    }))
                    .catch(error => {
                        swal({
                            icon: 'error',
                            title: 'Error de conexión',
                            text: error.message
                        });
                    });
            }
        });

    }

    // Cambio de grupo de cliente en el listado (un solo handler; no por fila).
    $(document).on('change', '.customer-group-select', function () {
        var $select = $(this);
        var cfg = window.fvCustomerGroupSelect || {};
        var customerId = $select.data('customer-id');
        var newGroupId = $select.val();
        var originalGroupId = $select.data('original-group-id');
        var $indicator = $select.closest('.customer-group-select-container').find('.group-update-indicator');

        if (String(newGroupId || '') === String(originalGroupId || '')) {
            return;
        }

        $select.prop('disabled', true);
        $indicator.html(
            '<span class="text-info"><i class="mdi mdi-loading mdi-spin"></i> ' +
            (cfg.updating || '') +
            '</span>'
        );

        var apiToken = cfg.apiToken || '';
        var apiBaseUrl = cfg.apiBaseUrl || '/api';
        var updateUrl = apiBaseUrl + '/v2/customer/updateGroup';

        $.ajax({
            url: updateUrl,
            method: 'PUT',
            contentType: 'application/json',
            data: JSON.stringify({
                customer_id: parseInt(customerId, 10),
                group_id: newGroupId === '' || newGroupId === null ? null : parseInt(newGroupId, 10),
            }),
            headers: {
                Accept: 'application/json',
                Authorization: 'Bearer ' + apiToken,
            },
            success: function () {
                $select.data('original-group-id', newGroupId);
                $indicator.html(
                    '<span class="text-success"><i class="mdi mdi-check"></i> ' +
                    (cfg.updated || '') +
                    '</span>'
                );
                setTimeout(function () {
                    $indicator.fadeOut(function () {
                        $indicator.html('').show();
                    });
                }, 2000);
            },
            error: function () {
                $select.val(originalGroupId);
                $indicator.html(
                    '<span class="text-danger"><i class="mdi mdi-alert"></i> ' +
                    (cfg.error || '') +
                    '</span>'
                );
                setTimeout(function () {
                    $indicator.fadeOut(function () {
                        $indicator.html('').show();
                    });
                }, 3000);
            },
            complete: function () {
                $select.prop('disabled', false);
            },
        });
    });
});
