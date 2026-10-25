/**
 * Modal de especificaciones de producto + auto-asignación de imágenes IA.
 */
(function ($) {
    'use strict';

    function modalEl() {
        return $('#modal_product_specs');
    }

    function tModal(key, fallback) {
        var $m = modalEl();
        var v = $m.attr('data-' + key);
        return (v != null && String(v) !== '') ? String(v) : (fallback || '');
    }

    function setAlert(msg) {
        var $a = $('#fv_specs_alert');
        if (!msg) {
            $a.addClass('d-none').text('');
            return;
        }
        $a.removeClass('d-none').text(msg);
    }

    function escapeHtml(str) {
        return $('<div>').text(str == null ? '' : String(str)).html();
    }

    function rowHtml(name, value) {
        var labelName = tModal('label-name', '');
        var labelValue = tModal('label-value', '');
        var labelRemove = tModal('label-remove', '');
        return (
            '<tr class="fv-specs-row">' +
            '<td><input type="text" class="form-control form-control-sm fv-specs-name" ' +
            'placeholder="' + escapeHtml(labelName) + '" value="' + escapeHtml(name || '') + '"></td>' +
            '<td><input type="text" class="form-control form-control-sm fv-specs-value" ' +
            'placeholder="' + escapeHtml(labelValue) + '" value="' + escapeHtml(value || '') + '"></td>' +
            '<td class="text-center">' +
            '<button type="button" class="btn btn-link btn-sm text-danger fv-specs-remove" title="' +
            escapeHtml(labelRemove) + '"><i class="mdi mdi-close"></i></button>' +
            '</td></tr>'
        );
    }

    function collectRows() {
        var rows = [];
        $('#fv_specs_rows tr').each(function () {
            var name = $.trim($(this).find('.fv-specs-name').val() || '');
            var value = $.trim($(this).find('.fv-specs-value').val() || '');
            if (name === '' && value === '') {
                return;
            }
            rows.push({ name: name, value: value });
        });
        return rows;
    }

    function fillRows(specs) {
        var $tbody = $('#fv_specs_rows').empty();
        var list = Array.isArray(specs) ? specs : [];
        if (!list.length) {
            $tbody.append(rowHtml('', ''));
            return;
        }
        list.forEach(function (row) {
            $tbody.append(rowHtml(row.name || '', row.value || ''));
        });
    }

    function openSpecs(proId) {
        var $modal = modalEl();
        var loadBase = String($modal.attr('data-load-url') || '');
        setAlert('');
        $('#fv_specs_product_name').text(tModal('msg-loading', '…'));
        $('#fv_specs_product_sku').text('');
        fillRows([]);
        $modal.attr('data-pro-id', String(proId));
        $modal.modal('show');

        if (!loadBase) {
            $('#fv_specs_product_name').text('');
            setAlert(tModal('msg-load-error', tModal('msg-error', '')));
            return;
        }

        $.ajax({
            url: loadBase + proId,
            method: 'GET',
            dataType: 'json'
        }).done(function (resp) {
            if (!resp || !resp.success) {
                $('#fv_specs_product_name').text('');
                setAlert((resp && resp.message) || tModal('msg-load-error', tModal('msg-error', '')));
                return;
            }
            $('#fv_specs_product_name').text(resp.name || ('#' + proId));
            var skuLabel = tModal('label-sku', 'SKU');
            $('#fv_specs_product_sku').text(resp.sku ? (skuLabel + ': ' + resp.sku) : '');
            fillRows(resp.specifications || []);
        }).fail(function (xhr) {
            $('#fv_specs_product_name').text('');
            var msg = tModal('msg-load-error', tModal('msg-error', ''));
            try {
                var body = JSON.parse(xhr.responseText || '{}');
                if (body.message) {
                    msg = body.message;
                }
            } catch (e) { /* ignore */ }
            setAlert(msg);
        });
    }

    function saveSpecs() {
        var $modal = modalEl();
        var proId = parseInt($modal.attr('data-pro-id') || '0', 10) || 0;
        if (proId < 1) {
            return;
        }
        var rows = collectRows();
        for (var i = 0; i < rows.length; i++) {
            if (rows[i].name === '' && rows[i].value !== '') {
                setAlert(tModal('msg-name-required', ''));
                return;
            }
        }
        setAlert('');
        var saveBase = String($modal.attr('data-save-url') || '');
        if (!saveBase) {
            setAlert(tModal('msg-error', ''));
            return;
        }
        var $btn = $('#fv_specs_save').prop('disabled', true);
        $.ajax({
            url: saveBase + proId,
            method: 'POST',
            dataType: 'json',
            data: {
                specifications: JSON.stringify(rows)
            }
        }).done(function (resp) {
            if (!resp || !resp.success) {
                setAlert((resp && resp.message) || tModal('msg-error', ''));
                return;
            }
            $modal.modal('hide');
            if (window.toastr && resp.message) {
                toastr.success(resp.message);
            }
        }).fail(function (xhr) {
            var msg = tModal('msg-error', '');
            try {
                var body = JSON.parse(xhr.responseText || '{}');
                if (body.message) {
                    msg = body.message;
                }
            } catch (e) { /* ignore */ }
            setAlert(msg);
        }).always(function () {
            $btn.prop('disabled', false);
        });
    }

    function collectPendingAiImageIds() {
        var ids = [];
        var seen = {};
        $('[data-ai-image-pending="1"]').each(function () {
            var id = parseInt($(this).attr('data-pro-id') || '0', 10);
            if (id > 0 && !seen[id]) {
                seen[id] = true;
                ids.push(id);
            }
        });
        return ids;
    }

    function assignAiImages() {
        var $cfg = $('#fv_product_ai_image_cfg');
        if (!$cfg.length) {
            return;
        }
        var url = String($cfg.attr('data-url') || '');
        if (!url) {
            return;
        }
        var ids = collectPendingAiImageIds().slice(0, 3);
        if (!ids.length) {
            return;
        }
        var attemptedKey = 'fv_ai_img_attempted';
        var attempted = {};
        try {
            attempted = JSON.parse(sessionStorage.getItem(attemptedKey) || '{}') || {};
        } catch (e) {
            attempted = {};
        }
        ids = ids.filter(function (id) {
            return !attempted[String(id)];
        });
        if (!ids.length) {
            return;
        }
        ids.forEach(function (id) {
            attempted[String(id)] = 1;
        });
        try {
            sessionStorage.setItem(attemptedKey, JSON.stringify(attempted));
        } catch (e) { /* ignore */ }

        $.ajax({
            url: url,
            method: 'POST',
            dataType: 'json',
            data: { product_ids: JSON.stringify(ids) }
        }).done(function (resp) {
            if (!resp || !resp.success) {
                return;
            }
            var assigned = Array.isArray(resp.assigned) ? resp.assigned : [];
            if (!assigned.length) {
                return;
            }
            if (window.toastr && resp.message) {
                toastr.success(resp.message);
            }
            window.setTimeout(function () {
                window.location.reload();
            }, 800);
        });
    }

    $(document).on('click', '.fv-product-specs-btn', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var proId = parseInt($(this).attr('data-pro-id') || '0', 10);
        if (proId > 0) {
            openSpecs(proId);
        }
    });

    $(document).on('click', '#fv_specs_add_row', function () {
        $('#fv_specs_rows').append(rowHtml('', ''));
    });

    $(document).on('click', '.fv-specs-remove', function () {
        var $tr = $(this).closest('tr');
        $tr.remove();
        if (!$('#fv_specs_rows tr').length) {
            $('#fv_specs_rows').append(rowHtml('', ''));
        }
    });

    $(document).on('click', '#fv_specs_save', function () {
        saveSpecs();
    });

    $(function () {
        window.setTimeout(assignAiImages, 1200);
    });
})(jQuery);
