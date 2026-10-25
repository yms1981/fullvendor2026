/**
 * Edición de categoría / grupos / subgrupos desde el listado de productos.
 */
(function ($) {
    'use strict';

    var state = {
        proId: 0,
        focus: 'category'
    };

    function tModal(key, fallback) {
        var $m = $('#modal_product_list_taxonomy');
        var v = $m.data(key);
        return (v != null && String(v) !== '') ? String(v) : (fallback || '');
    }

    function parsePayload($el) {
        var raw = $el.attr('data-fv-list-taxonomy') || '{}';
        try {
            return JSON.parse(raw);
        } catch (e) {
            return {};
        }
    }

    function dash(value) {
        var s = $.trim(value == null ? '' : String(value));
        return s !== '' ? s : '—';
    }

    function ensureSelect2() {
        var $sel = $('#fv_list_taxonomy_category');
        if (!$sel.length || !$.fn.select2) {
            return;
        }
        if ($sel.hasClass('select2-hidden-accessible')) {
            $sel.select2('destroy');
        }
        $sel.select2({
            width: '100%',
            dropdownParent: $('#modal_product_list_taxonomy'),
            placeholder: $sel.data('placeholder') || '',
            allowClear: true
        });
    }

    function setAlert(msg) {
        var $a = $('#fv_list_taxonomy_alert');
        if (!msg) {
            $a.addClass('d-none').text('');
            return;
        }
        $a.removeClass('d-none').text(msg);
    }

    function bindImageFallbacks($img, primary, fallbacks) {
        var list = [];
        var seen = {};
        [primary].concat(Array.isArray(fallbacks) ? fallbacks : []).forEach(function (url) {
            url = $.trim(String(url || ''));
            if (!url || seen[url]) {
                return;
            }
            seen[url] = true;
            list.push(url);
        });
        $img.off('error.fvLtx');
        if (!list.length) {
            return false;
        }
        $img.attr('src', list[0]).attr('data-fv-img-i', '0');
        $img.on('error.fvLtx', function () {
            var i = parseInt($img.attr('data-fv-img-i') || '0', 10) + 1;
            if (i < list.length) {
                $img.attr('data-fv-img-i', String(i));
                $img.attr('src', list[i]);
                return;
            }
            $('#fv_ltx_photo').attr('hidden', true);
            $('#fv_ltx_avatar').removeAttr('hidden');
        });
        return true;
    }

    function fillProductCard(payload) {
        var proId = parseInt(payload.pro_id, 10) || 0;
        var name = $.trim(payload.name || '');
        var sku = $.trim(payload.sku || '');

        $('#fv_ltx_pro_id').text(proId > 0 ? ('#' + proId) : '');
        $('#fv_list_taxonomy_product_name').text(name || sku || ('#' + proId));
        var skuLabel = tModal('label-sku', 'SKU');
        $('#fv_ltx_sku').text(sku ? (skuLabel + ': ' + sku) : '');

        var $status = $('#fv_ltx_status');
        if (typeof payload.enabled === 'boolean') {
            var on = !!payload.enabled;
            $status
                .removeClass('fv-ltx-status--on fv-ltx-status--off')
                .addClass(on ? 'fv-ltx-status--on' : 'fv-ltx-status--off')
                .text(on ? tModal('label-enabled', '') : tModal('label-disabled', ''))
                .prop('hidden', false);
        } else {
            $status.prop('hidden', true).text('');
        }

        $('#fv_ltx_price').text(dash(payload.price_display));
        $('#fv_ltx_stock').text(dash(payload.stock_display));
        $('#fv_ltx_moq').text(dash(payload.moq_display));
        $('#fv_ltx_barcode').text(dash(payload.barcode));
        $('#fv_ltx_tags').text(dash(payload.tags));
        $('#fv_ltx_locations').text(dash(payload.locations));

        var desc = $.trim(payload.descriptions || '');
        var $desc = $('#fv_ltx_descriptions');
        if (desc) {
            $desc.removeClass('d-none').text(desc);
        } else {
            $desc.addClass('d-none').text('');
        }

        var $photo = $('#fv_ltx_photo');
        var $img = $('#fv_ltx_photo_img');
        var $avatar = $('#fv_ltx_avatar');
        var $avatarLabel = $('#fv_ltx_avatar_label');
        var avatarLabel = $.trim(payload.avatar_label || sku || String(proId || ''));
        var avatarBg = $.trim(payload.avatar_bg || '#94a3b8');
        var avatarSize = parseInt(payload.avatar_font_size, 10) || 14;

        $avatarLabel.text(avatarLabel).css('font-size', avatarSize + 'px');
        $avatar.css('background', avatarBg);

        var hasImage = !!payload.has_image && !!payload.image_url;
        if (hasImage && bindImageFallbacks($img, payload.image_url, payload.image_fallbacks)) {
            $img.attr('alt', name || sku || '');
            $photo.removeAttr('hidden');
            $avatar.attr('hidden', true);
        } else {
            $img.removeAttr('src').attr('alt', '');
            $photo.attr('hidden', true);
            $avatar.removeAttr('hidden');
        }
    }

    function fillForm(payload) {
        state.proId = parseInt(payload.pro_id, 10) || 0;
        fillProductCard(payload || {});

        var $sel = $('#fv_list_taxonomy_category');
        ensureSelect2();
        var cats = Array.isArray(payload.category_ids) ? payload.category_ids.map(String) : [];
        $sel.val(cats).trigger('change');

        if (typeof window.fvWriteCategoryGroupHiddenInputs === 'function') {
            window.fvWriteCategoryGroupHiddenInputs(payload.groups || [], payload.subgroups || []);
        } else {
            var $box = $('#fv_cat_groups_hidden_inputs').empty();
            (payload.groups || []).forEach(function (item) {
                $box.append(
                    $('<input>', { type: 'hidden', name: 'category_group_ids[]', value: item.id })
                        .attr('data-label', item.label || '')
                );
            });
            (payload.subgroups || []).forEach(function (item) {
                $box.append(
                    $('<input>', { type: 'hidden', name: 'category_subgroup_ids[]', value: item.id })
                        .attr('data-label', item.label || '')
                        .attr('data-parent-group-id', item.parentGroupId || '')
                );
            });
            if (typeof window.fvUpdateCategoryGroupsSummary === 'function') {
                window.fvUpdateCategoryGroupsSummary();
            }
        }
    }

    function openModal(payload, focus) {
        state.focus = focus || 'category';
        setAlert('');
        fillForm(payload || {});
        $('#modal_product_list_taxonomy').modal('show');
        if (state.focus === 'groups') {
            setTimeout(function () {
                $('#fv_list_taxonomy_open_groups').trigger('click');
            }, 350);
        }
    }

    $(document).on('click', '[data-fv-list-taxonomy]', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var $btn = $(this);
        openModal(parsePayload($btn), $btn.data('fv-list-taxonomy-focus') || 'category');
    });

    $(document).on('click', '#fv_list_taxonomy_open_groups', function (e) {
        e.preventDefault();
        var ids = $('#fv_list_taxonomy_category').val() || [];
        if (!ids.length) {
            setAlert(tModal('msg-need-category', ''));
            return;
        }
        setAlert('');
        $('#modal_product_category_groups').modal('show');
    });

    $(document).on('shown.bs.modal', '#modal_product_list_taxonomy', function () {
        ensureSelect2();
    });

    $(document).on('hidden.bs.modal', '#modal_product_list_taxonomy', function () {
        state.proId = 0;
        setAlert('');
    });

    $(document).on('show.bs.modal', '#modal_product_category_groups', function () {
        var zIndex = 1050 + (10 * $('.modal:visible').length);
        $(this).css('z-index', zIndex);
        setTimeout(function () {
            $('.modal-backdrop').last().css('z-index', zIndex - 1);
        }, 0);
    });

    $(document).on('click', '#fv_list_taxonomy_save', function () {
        var proId = state.proId;
        if (!(proId > 0)) {
            return;
        }
        var cats = $('#fv_list_taxonomy_category').val() || [];
        if (!cats.length) {
            setAlert(tModal('msg-need-category', ''));
            return;
        }

        var groupIds = [];
        var subgroupIds = [];
        $('#fv_cat_groups_hidden_inputs input[name="category_group_ids[]"]').each(function () {
            var id = parseInt($(this).val(), 10);
            if (id > 0) {
                groupIds.push(id);
            }
        });
        $('#fv_cat_groups_hidden_inputs input[name="category_subgroup_ids[]"]').each(function () {
            var id = parseInt($(this).val(), 10);
            if (id > 0) {
                subgroupIds.push(id);
            }
        });

        var baseUrl = String($('#modal_product_list_taxonomy').data('save-url') || '').replace(/\/?$/, '/');
        var $btn = $(this).prop('disabled', true);
        setAlert('');

        $.ajax({
            url: baseUrl + proId,
            method: 'POST',
            dataType: 'json',
            data: {
                category_id: cats,
                category_group_ids: groupIds,
                category_subgroup_ids: subgroupIds
            }
        }).done(function (resp) {
            if (resp && resp.success) {
                window.location.reload();
                return;
            }
            setAlert((resp && (resp.message || resp.msg)) || tModal('msg-error', ''));
        }).fail(function (xhr) {
            var msg = tModal('msg-error', '');
            try {
                var body = JSON.parse(xhr.responseText || '{}');
                if (body && (body.message || body.msg)) {
                    msg = body.message || body.msg;
                }
            } catch (e) { /* noop */ }
            setAlert(msg);
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });
})(jQuery);
