(function ($) {
    'use strict';

    function getLang() {
        return window.fvProductInventoryLang || {};
    }

    function getCfg() {
        return window.fvProductInventoryConfig || {};
    }

    function t(key, fallback) {
        var lang = getLang();
        var v = lang[key];
        return typeof v === 'string' && v !== '' ? v : (fallback || '');
    }

    function formatNumber(value) {
        var n = parseFloat(value);
        if (isNaN(n)) {
            return '0.00';
        }
        return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function formatDate(value) {
        if (!value) {
            return '';
        }
        if (typeof window.fvFormatDisplayDate === 'function') {
            return window.fvFormatDisplayDate(value);
        }
        var d = new Date(value);
        if (isNaN(d.getTime())) {
            return String(value);
        }
        var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        var day = String(d.getDate()).padStart(2, '0');
        return day + '-' + months[d.getMonth()] + '-' + d.getFullYear();
    }

    function movementTypeLabel(tipoD) {
        if (tipoD === 'I') {
            return t('submayor_type_inventory', 'Inventory entry');
        }
        if (tipoD === 'D') {
            return t('submayor_type_dispatch', 'Dispatch');
        }
        return t('submayor_type_other', 'Other');
    }

    function resetModal() {
        $('#fv-inventory-submayor-loader').addClass('d-none');
        $('#fv-inventory-submayor-error').addClass('d-none').text('');
        $('#fv-inventory-submayor-content').addClass('d-none');
        $('#fv-inventory-submayor-tbody').empty();
        $('#fv-inventory-submayor-empty').addClass('d-none');
        $('#fv-inventory-submayor-summary').text('');
    }

    function showLoader() {
        resetModal();
        $('#fv-inventory-submayor-loader').removeClass('d-none');
    }

    function showError(message) {
        $('#fv-inventory-submayor-loader').addClass('d-none');
        $('#fv-inventory-submayor-error').removeClass('d-none').text(message || t('inventory_error_generic', 'Error loading data'));
    }

    function renderMovements(data, sku, name) {
        $('#fv-inventory-submayor-loader').addClass('d-none');
        $('#fv-inventory-submayor-content').removeClass('d-none');

        var product = data.product || {};
        var movements = Array.isArray(data.movements) ? data.movements : [];
        var title = t('submayor_modal_title', 'Inventory movements');
        $('#fv-inventory-submayor-title').text(title + ' — ' + (sku || product.sku || '') + ' ' + (name || product.name || ''));

        var summary = t('submayor_summary', 'Current inventory: %s | Movements: %d');
        summary = summary.replace('%s', formatNumber(product.inventory_qty)).replace('%d', String(movements.length));
        $('#fv-inventory-submayor-summary').text(summary);

        if (!movements.length) {
            $('#fv-inventory-submayor-empty').removeClass('d-none');
            return;
        }

        var rows = '';
        movements.forEach(function (m) {
            rows += '<tr>' +
                '<td>' + formatDate(m.movement_date) + '</td>' +
                '<td>' + (m.order_number || '') + '</td>' +
                '<td>' + movementTypeLabel(m.tipo_d || '') + '</td>' +
                '<td class="text-right text-success">' + (parseFloat(m.qty_in) > 0 ? formatNumber(m.qty_in) : '') + '</td>' +
                '<td class="text-right text-danger">' + (parseFloat(m.qty_out) > 0 ? formatNumber(m.qty_out) : '') + '</td>' +
                '<td class="text-right font-weight-bold">' + formatNumber(m.balance) + '</td>' +
                '<td class="text-right">' + formatNumber(m.sale_price) + '</td>' +
                '<td>' + (m.comments || '') + '</td>' +
                '</tr>';
        });
        $('#fv-inventory-submayor-tbody').html(rows);
    }

    $(document).on('click', '.fv-inventory-submayor-btn', function () {
        var cfg = getCfg();
        var proId = $(this).data('pro-id');
        var $row = $(this).closest('tr');
        var sku = $row.data('sku') || '';
        var name = $row.data('name') || '';
        var baseUrl = cfg.movementsUrl || '';

        if (!proId || !baseUrl) {
            return;
        }

        $('#fv-inventory-submayor-modal').modal('show');
        showLoader();

        $.ajax({
            url: baseUrl + proId,
            method: 'GET',
            dataType: 'json'
        }).done(function (resp) {
            if (resp && resp.product) {
                renderMovements(resp, sku, name);
            } else if (resp && resp.data) {
                renderMovements(resp.data, sku, name);
            } else {
                showError(t('inventory_error_generic', 'Error loading data'));
            }
        }).fail(function (xhr) {
            var msg = t('inventory_error_generic', 'Error loading data');
            if (xhr.responseJSON && xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            }
            showError(msg);
        });
    });

    $('#fv-inventory-submayor-modal').on('hidden.bs.modal', resetModal);

    $(document).on('click', '.fv-inventory-stock-btn', function () {
        var value = $(this).data('stock-filter') || 'all';
        $('#fv-inventory-stock-filter').val(value);
        $('.fv-inventory-stock-btn').removeClass('active');
        $(this).addClass('active');
        $('#fv-inventory-filter-form').trigger('submit');
    });

    $(document).on('click', '#fv-inventory-cat-select-all', function (e) {
        e.preventDefault();
        var $form = $('#fv-inventory-filter-form');
        if (!$form.length) {
            return;
        }
        $form.find('input[name="categories[]"]').prop('checked', true);
        $form.trigger('submit');
    });

    $(document).on('click', '#fv-inventory-cat-clear', function (e) {
        e.preventDefault();
        var $form = $('#fv-inventory-filter-form');
        if (!$form.length) {
            return;
        }
        $form.find('input[name="categories[]"]').prop('checked', false);
        $form.trigger('submit');
    });

    function exportInventory(typeFile) {
        var cfg = getCfg();
        var $form = $('#fv-inventory-filter-form');
        var exportUrl = (cfg.exportUrl || '').trim();
        if (!$form.length || !exportUrl) {
            window.alert(t('inventory_export_failed', 'Export failed'));
            return;
        }

        typeFile = (typeFile === 'pdf') ? 'pdf' : 'excel';

        var $buttons = $('[data-export-type]');
        if ($buttons.first().data('exporting')) {
            return;
        }
        $buttons.each(function () {
            $(this).data('exporting', true).prop('disabled', true);
        });

        var formData = new FormData($form[0]);
        formData.append('type_file', typeFile);
        var headings = typeFile === 'pdf'
            ? (cfg.exportHeadingsPdf || cfg.exportHeadings || [])
            : (cfg.exportHeadings || []);
        headings.forEach(function (heading, index) {
            formData.append('headings[' + index + ']', heading);
        });

        if (typeFile === 'pdf') {
            var pdfLabels = cfg.exportPdfLabels || {};
            Object.keys(pdfLabels).forEach(function (key) {
                formData.append('pdf_labels[' + key + ']', pdfLabels[key]);
            });
        }

        var today = new Date();
        var dateStamp = String(today.getFullYear())
            + String(today.getMonth() + 1).padStart(2, '0')
            + String(today.getDate()).padStart(2, '0');
        var downloadFileName = 'product_inventory_' + dateStamp + (typeFile === 'pdf' ? '.pdf' : '.xlsx');

        fetch(exportUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': '*/*'
            }
        }).then(function (response) {
            var contentType = (response.headers.get('Content-Type') || '').toLowerCase();
            var disposition = response.headers.get('Content-Disposition') || '';
            var looksLikeFile = disposition.toLowerCase().indexOf('attachment') !== -1
                || contentType.indexOf('application/pdf') !== -1
                || contentType.indexOf('spreadsheet') !== -1
                || contentType.indexOf('officedocument') !== -1
                || contentType.indexOf('octet-stream') !== -1;

            if (!response.ok || (contentType.indexOf('application/json') !== -1 && !looksLikeFile)) {
                return response.text().then(function (body) {
                    var msg = t('inventory_export_failed', 'Export failed');
                    try {
                        var parsed = JSON.parse(body);
                        if (parsed && parsed.message) {
                            msg = parsed.message;
                        }
                    } catch (e) {
                        // keep default
                    }
                    throw new Error(msg);
                });
            }

            if (disposition) {
                var filenameMatch = disposition.match(/filename="?([^";]+)"?/i);
                if (filenameMatch && filenameMatch[1]) {
                    downloadFileName = filenameMatch[1].trim();
                }
            }
            return response.blob();
        }).then(function (blob) {
            if (!blob || blob.size < 32) {
                throw new Error(t('inventory_export_failed', 'Export failed'));
            }
            var file = window.URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = file;
            a.download = downloadFileName;
            document.body.appendChild(a);
            a.click();
            a.remove();
            window.setTimeout(function () {
                window.URL.revokeObjectURL(file);
            }, 1000);
        }).catch(function (err) {
            window.alert((err && err.message) || t('inventory_export_failed', 'Export failed'));
        }).finally(function () {
            $buttons.each(function () {
                $(this).data('exporting', false).prop('disabled', false);
            });
        });
    }

    window.fvExportInventory = exportInventory;

    $(document).on('click', '[data-export-type]', function (e) {
        e.preventDefault();
        var typeFile = $(this).attr('data-export-type') || $(this).data('export-type') || 'excel';
        exportInventory(typeFile);
    });

    if ($.fn.DataTable && $('#fv-product-inventory-table tbody tr[data-pro-id]').length) {
        $('#fv-product-inventory-table').DataTable({
            paging: false,
            searching: false,
            info: false,
            ordering: true,
            order: [[0, 'asc']],
            columnDefs: [{ orderable: false, targets: -1 }]
        });
    }
}(jQuery));
