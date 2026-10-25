/**
 * Switch activar/desactivar en columnas status de grillas CRUD.
 */
(function (window) {
    'use strict';

    var initialized = false;

    function reloadGrid() {
        if (typeof window.fvReloadConfigCrudGrid === 'function') {
            window.fvReloadConfigCrudGrid();
            return;
        }
        window.location.reload();
    }

    function resolveSuccessMessage(isChecked, serverMessage) {
        var message = (serverMessage && String(serverMessage).trim()) || '';
        if (message) {
            return message;
        }
        var cfg = window.fvConfigCrudStatusSwitch || {};
        return isChecked
            ? (cfg.enabled || '')
            : (cfg.disabled || '');
    }

    function showSuccessThenReload(message) {
        if (message && typeof swal === 'function') {
            swal({
                title: '',
                text: message,
                icon: 'success',
                timer: 1800,
                buttons: false,
            }).then(reloadGrid);
            return;
        }
        if (message) {
            window.alert(message);
        }
        reloadGrid();
    }

    function bindHandlers($) {
        if (!$) {
            return;
        }

        $(document).off('change.fvStatusSwitch', '.status-switch').on('change.fvStatusSwitch', '.status-switch', function (e) {
            e.stopPropagation();
            var $switch = $(this);
            var isChecked = $switch.is(':checked');
            var url = isChecked ? $switch.data('enable-url') : $switch.data('disable-url');
            var $label = $switch.next('label');

            if (window.__statusSwitchRequestInProgress) {
                $switch.prop('checked', !isChecked);
                return;
            }

            if (!url) {
                $switch.prop('checked', !isChecked);
                return;
            }

            $switch.prop('disabled', true);
            $label.html('<span class="spinner-border spinner-border-sm" role="status"></span>');

            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'json',
                beforeSend: function () {
                    window.__statusSwitchRequestInProgress = true;
                },
            }).done(function (response) {
                var data = response;
                if (typeof response === 'string') {
                    try {
                        data = response.trim().startsWith('{') ? JSON.parse(response) : null;
                    } catch (err) {
                        data = null;
                    }
                }

                var $cell = $switch.closest('td');
                var $wrap = $cell.length ? $cell : $switch.closest('.fv-product-card__toggle, .custom-switch').parent();
                if (!$wrap.length) {
                    $wrap = $switch.parent();
                }

                if (data && typeof data === 'object' && (data.success || data.html || data.message)) {
                    showSuccessThenReload(resolveSuccessMessage(isChecked, data.message));
                    return;
                }

                $wrap.html(typeof response === 'string' ? response : (data && data.html ? data.html : ''));
                reloadGrid();
            }).fail(function (xhr) {
                $switch.prop('checked', !isChecked);
                $switch.prop('disabled', false);
                $label.html('');

                var errorMsg = (window.fvConfigCrudStatusSwitch && window.fvConfigCrudStatusSwitch.error)
                    || (window.fvUiLang && window.fvUiLang.status_switch_error)
                    || (window.fvUiLang && window.fvUiLang.message_error_generic)
                    || '';
                if (xhr.responseJSON && (xhr.responseJSON.message || xhr.responseJSON.msg)) {
                    errorMsg = xhr.responseJSON.message || xhr.responseJSON.msg;
                } else if (xhr.responseText) {
                    try {
                        var parsed = JSON.parse(xhr.responseText);
                        if (parsed && (parsed.message || parsed.msg)) {
                            errorMsg = parsed.message || parsed.msg;
                        }
                    } catch (err2) {
                        /* ignore */
                    }
                }

                if (errorMsg && typeof swal === 'function') {
                    swal({ title: '', text: errorMsg, icon: 'error' });
                } else if (errorMsg) {
                    window.alert(errorMsg);
                }
            }).always(function () {
                window.__statusSwitchRequestInProgress = false;
            });
        });
    }

    window.FvConfigCrudStatusSwitch = {
        init: function ($) {
            if (initialized) {
                return;
            }
            initialized = true;
            bindHandlers($ || window.jQuery);
        },
    };
})(window);
