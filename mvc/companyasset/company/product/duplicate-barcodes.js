(function ($) {
    'use strict';

    if (window.__fvDuplicateBarcodesBound) {
        return;
    }
    window.__fvDuplicateBarcodesBound = true;

    function alertRoot() {
        return document.getElementById('fv-product-quality-alert');
    }

    function meta(key, fallback) {
        var el = alertRoot();
        if (!el) {
            return fallback || '';
        }
        return String(el.getAttribute(key) || fallback || '');
    }

    function apiHeaders() {
        var token = meta('data-api-token', '');
        var headers = {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        };
        if (token !== '') {
            headers.Authorization = 'Bearer ' + token;
        }
        return headers;
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function notify(icon, text) {
        if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
            return Swal.fire({
                icon: icon,
                text: text,
                confirmButtonText: 'OK',
            });
        }
        window.alert(text);
        return Promise.resolve();
    }

    function renderGroups(groups) {
        var $wrap = $('#fv-duplicate-barcodes-groups');
        var $loading = $('#fv-duplicate-barcodes-loading');
        var $empty = $('#fv-duplicate-barcodes-empty');
        var groupTpl = document.getElementById('fv-duplicate-barcodes-group-tpl');
        var rowTpl = document.getElementById('fv-duplicate-barcodes-row-tpl');
        $loading.addClass('d-none');
        $wrap.empty();

        if (!Array.isArray(groups) || groups.length < 1) {
            $empty.removeClass('d-none');
            return;
        }
        $empty.addClass('d-none');

        var editBase = meta('data-edit-url-base', '');
        var groupLabelTpl = meta('data-group-label', ':barcode (:count)');
        var btnSave = meta('data-btn-save', 'Save');
        var btnEdit = meta('data-btn-edit', 'Edit');
        var noImageUrl = meta('data-no-image-url', '');
        var apiBase = meta('data-api-base', '/api').replace(/\/$/, '');

        groups.forEach(function (group) {
            var barcode = String(group.barcode || '');
            var products = Array.isArray(group.products) ? group.products : [];
            var $group = $(groupTpl.content.cloneNode(true));
            var title = groupLabelTpl
                .replace(':barcode', barcode)
                .replace(':count', String(products.length));
            $group.find('.js-fv-group-title').text(title);
            $group.find('.js-fv-group-count').text(String(products.length));
            var $body = $group.find('.js-fv-group-body');

            products.forEach(function (product) {
                var $row = $(rowTpl.content.cloneNode(true));
                var proId = parseInt(product.pro_id || '0', 10) || 0;
                var imageUrl = String(product.image_url || '').trim();
                if (imageUrl === '' && proId > 0) {
                    imageUrl = apiBase + '/v2/public/products/' + proId + '/image';
                }
                var $thumb = $row.find('.js-fv-row-thumb');
                $thumb
                    .attr('alt', String(product.name || product.sku || ''))
                    .attr('src', imageUrl || noImageUrl);
                if (noImageUrl !== '') {
                    $thumb.on('error', function () {
                        this.onerror = null;
                        this.src = noImageUrl;
                    });
                }
                $row.find('.js-fv-row-sku').text(String(product.sku || ''));
                $row.find('.js-fv-row-name').text(String(product.name || ''));
                $row.find('.js-fv-row-barcode')
                    .val(String(product.barcode || ''))
                    .attr('data-pro-id', String(proId))
                    .attr('data-original', String(product.barcode || ''));
                $row.find('.js-fv-row-save')
                    .text(btnSave)
                    .attr('data-pro-id', String(proId));
                $row.find('.js-fv-row-edit')
                    .text(btnEdit)
                    .attr('href', editBase + proId);
                $body.append($row);
            });

            $wrap.append($group);
        });
    }

    function loadGroups() {
        var companyId = parseInt(meta('data-company-id', '0'), 10) || 0;
        var base = meta('data-api-base', '/api').replace(/\/$/, '');
        $('#fv-duplicate-barcodes-loading').removeClass('d-none');
        $('#fv-duplicate-barcodes-empty').addClass('d-none');
        $('#fv-duplicate-barcodes-groups').empty();

        if (companyId < 1) {
            $('#fv-duplicate-barcodes-loading').addClass('d-none');
            $('#fv-duplicate-barcodes-empty').removeClass('d-none');
            return;
        }

        $.ajax({
            url: base + '/v2/product/duplicate-barcodes',
            method: 'GET',
            headers: apiHeaders(),
            data: { company_id: companyId },
        })
            .done(function (resp) {
                renderGroups((resp && resp.groups) || []);
            })
            .fail(function () {
                $('#fv-duplicate-barcodes-loading').addClass('d-none');
                notify('error', meta('data-msg-error', 'Error'));
            });
    }

    function saveBarcode(proId, barcode, $btn) {
        var companyId = parseInt(meta('data-company-id', '0'), 10) || 0;
        var base = meta('data-api-base', '/api').replace(/\/$/, '');
        if (proId < 1 || companyId < 1) {
            return;
        }
        $btn.prop('disabled', true);
        $.ajax({
            url: base + '/v2/product/' + proId + '/barcode',
            method: 'PATCH',
            headers: apiHeaders(),
            data: JSON.stringify({
                company_id: companyId,
                barcode: barcode,
            }),
            processData: false,
            contentType: 'application/json',
        })
            .done(function () {
                notify('success', meta('data-msg-saved', 'OK')).then(function () {
                    loadGroups();
                });
            })
            .fail(function (xhr) {
                var msg = meta('data-msg-error', 'Error');
                try {
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                } catch (e) {}
                notify('error', msg);
            })
            .always(function () {
                $btn.prop('disabled', false);
            });
    }

    $(function () {
        $(document).on('click', '.js-fv-open-duplicate-barcodes', function (e) {
            e.preventDefault();
            $('#fv-duplicate-barcodes-modal').modal('show');
        });

        $('#fv-duplicate-barcodes-modal').on('shown.bs.modal', function () {
            loadGroups();
        });

        $(document).on('click', '#fv-duplicate-barcodes-modal .js-fv-row-save', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var proId = parseInt($btn.attr('data-pro-id') || '0', 10) || 0;
            var $input = $btn.closest('tr').find('.js-fv-row-barcode');
            var barcode = String($input.val() || '').trim();
            saveBarcode(proId, barcode, $btn);
        });
    });
})(jQuery);
