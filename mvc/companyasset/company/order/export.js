/**
 * Exportación Excel de órdenes (listado y vista detalle).
 */
(function ($, window) {
    'use strict';

    if (!$ || !window) {
        return;
    }

    function orderLang() {
        if (typeof options !== 'undefined' && options.lang && options.lang['cmp-orders']) {
            return options.lang['cmp-orders'];
        }
        if (typeof options !== 'undefined' && options.lang && options.lang.cmp_orders && options.lang.cmp_orders.lang) {
            return options.lang.cmp_orders.lang;
        }
        return {};
    }

    function baseUrl() {
        if (typeof options !== 'undefined' && options.url) {
            return String(options.url).replace(/\/$/, '');
        }
        return '';
    }

    function showWarn(msg) {
        if (typeof Swal !== 'undefined' && Swal.fire) {
            Swal.fire({ icon: 'warning', title: msg });
        } else if (typeof swal !== 'undefined') {
            swal({ title: msg, icon: 'warning' });
        } else {
            window.alert(msg);
        }
    }

    function showError(msg) {
        if (typeof Swal !== 'undefined' && Swal.fire) {
            Swal.fire({ icon: 'error', title: msg });
        } else if (typeof swal !== 'undefined') {
            swal({ title: msg, icon: 'error' });
        } else {
            window.alert(msg);
        }
    }

    function downloadBlob(blob, filename) {
        var url = window.URL.createObjectURL(blob);
        var anchor = document.createElement('a');
        anchor.href = url;
        anchor.download = filename;
        document.body.appendChild(anchor);
        anchor.click();
        anchor.remove();
        window.URL.revokeObjectURL(url);
    }

    function filenameFromDisposition(disposition, fallback) {
        var match = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/.exec(disposition || '');
        if (match && match[1]) {
            return match[1].replace(/['"]/g, '');
        }
        return fallback;
    }

    function collectSelectedOrderIds() {
        var selectedIds = [];
        $('#infoTable tbody input.checkBoxClass:checked').each(function () {
            var id = parseInt($(this).val(), 10);
            if (!Number.isNaN(id) && id > 0) {
                selectedIds.push(id);
            }
        });
        return selectedIds;
    }

    function currentViewOrderId() {
        if (typeof window.fvOrderExportId !== 'undefined') {
            var fromFv = parseInt(window.fvOrderExportId, 10);
            if (!Number.isNaN(fromFv) && fromFv > 0) {
                return fromFv;
            }
        }
        if (typeof window.order_id !== 'undefined' && window.order_id !== null && String(window.order_id).trim() !== '') {
            var id = parseInt(window.order_id, 10);
            if (!Number.isNaN(id) && id > 0) {
                return id;
            }
        }
        var match = window.location.pathname.match(/\/orders\/view\/(\d+)/i)
            || window.location.pathname.match(/\/warehouse\/warehouse_orderview\/(\d+)/i);
        if (match) {
            return parseInt(match[1], 10) || 0;
        }
        return 0;
    }

    function buildExportFormData() {
        var lang = orderLang();
        var formEl = document.getElementById('form-buscar-filtro');
        var formData = formEl ? new FormData(formEl) : new FormData();
        var inputTodos = document.getElementById('input-todos');
        var exportAll = inputTodos && inputTodos.value === 'todos';
        var viewOrderId = currentViewOrderId();
        var selectedIds = collectSelectedOrderIds();

        // Vista detalle de una orden
        if (viewOrderId > 0 && selectedIds.length === 0 && !exportAll) {
            formData.append('order_ids[0]', String(viewOrderId));
            return { formData: formData, ok: true };
        }

        if (!exportAll && selectedIds.length === 0) {
            showWarn(lang.alert_select_at_least_one || lang.select_customer || '');
            return { formData: null, ok: false };
        }

        if (!exportAll) {
            selectedIds.forEach(function (id, index) {
                formData.append('order_ids[' + index + ']', String(id));
            });
        }

        return { formData: formData, ok: true };
    }

    $(document).on('click', '#modal_export #btn-export', function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        var lang = orderLang();
        var built = buildExportFormData();
        if (!built.ok || !built.formData) {
            return;
        }

        var $btn = $(this);
        var innerHtml = $btn.html();
        $btn.prop('disabled', true);
        $btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + (lang.bnExport || lang.bnExport_modal || ''));

        fetch(baseUrl() + '/orders/export', {
            method: 'POST',
            body: built.formData,
            credentials: 'same-origin'
        }).then(function (response) {
            var contentType = response.headers.get('Content-Type') || '';
            if (contentType.indexOf('application/json') !== -1) {
                return response.json().then(function (json) {
                    showError(json.msg || json.message || lang.invalid_request || 'Error');
                });
            }

            return response.blob().then(function (blob) {
                var fallback = 'orders_' + new Date().toISOString().slice(0, 10).replace(/-/g, '') + '.xlsx';
                var filename = filenameFromDisposition(response.headers.get('Content-Disposition'), fallback);
                downloadBlob(blob, filename);
                $('#modal_export').modal('hide');
            });
        }).catch(function () {
            showError(lang.invalid_request || 'Error');
        }).finally(function () {
            $btn.prop('disabled', false);
            $btn.html(innerHtml);
        });
    });
})(window.jQuery, window);
