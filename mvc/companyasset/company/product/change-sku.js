(function ($) {
    'use strict';

    var lang = window.fvChangeSkuLang || {};

    function t(key, fallback) {
        var value = lang[key];
        return typeof value === 'string' && value.trim() !== '' ? value : (fallback || '');
    }

    function showError(message) {
        var $error = $('#change_sku_error');
        if (!message) {
            $error.hide().text('');
            return;
        }
        $error.text(message).show();
    }

    $(document).on('shown.bs.modal', '#modal_change_sku', function () {
        $('#change_sku_new').val('').trigger('focus');
        showError('');
    });

    $(document).on('click', '#btn_confirm_change_sku', function () {
        var $btn = $(this);
        var newSku = $.trim($('#change_sku_new').val());
        var currentSku = $.trim($('#change_sku_current').text());
        var productId = $('#form_edit').data('product-id');

        showError('');

        if (newSku === '') {
            showError(t('change_sku_required', t('error_sku', '')));
            return;
        }

        if (newSku.toLowerCase() === currentSku.toLowerCase()) {
            showError(t('change_sku_same', ''));
            return;
        }

        var originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');

        $.ajax({
            url: (window.options && window.options.url ? window.options.url : '') + '/products/changeSku/' + productId,
            type: 'POST',
            dataType: 'json',
            data: { sku: newSku },
            success: function (response) {
                $btn.prop('disabled', false).html(originalHtml);

                if (!response || !response.success) {
                    showError((response && response.message) ? response.message : t('error_occurred', ''));
                    return;
                }

                var updatedSku = response.sku || newSku;
                $('#sku_display').val(updatedSku);
                $('#change_sku_current').text(updatedSku);
                $('.fv-product-meta__name').first().text(function (_, text) {
                    return text;
                });

                if (typeof swal !== 'undefined') {
                    swal({
                        title: response.message || t('change_sku_success', ''),
                        icon: 'success',
                        button: {
                            text: t('ok', 'OK'),
                            value: true,
                            visible: true,
                            className: 'btn btn-primary'
                        }
                    }).then(function () {
                        $('#modal_change_sku').modal('hide');
                    });
                } else {
                    $('#modal_change_sku').modal('hide');
                }
            },
            error: function (xhr) {
                $btn.prop('disabled', false).html(originalHtml);
                var message = xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : t('error_occurred', '');
                showError(message);
            }
        });
    });
})(jQuery);
