/**
 * Switches booleanos en grillas CRUD (p. ej. permisos de perfil vendedor).
 */
(function (window) {
    'use strict';

    var requestInProgress = false;
    var initialized = false;

    function getMessages() {
        return window.fvConfigCrudBooleanSwitch || {};
    }

    function notify(message, icon) {
        if (!message) {
            return;
        }
        if (typeof swal === 'function') {
            swal({
                title: icon === 'success' ? message : '',
                text: icon === 'success' ? '' : message,
                icon: icon || 'info',
                timer: icon === 'success' ? 1600 : undefined,
            });
            return;
        }
        window.alert(message);
    }

    function extractErrorMessage(xhr, fallback) {
        var message = fallback || '';
        if (xhr.responseJSON && (xhr.responseJSON.message || xhr.responseJSON.msg)) {
            return xhr.responseJSON.message || xhr.responseJSON.msg;
        }
        if (xhr.responseText) {
            try {
                var parsed = JSON.parse(xhr.responseText);
                if (parsed && (parsed.message || parsed.msg)) {
                    return parsed.message || parsed.msg;
                }
            } catch (e) {
                /* ignore */
            }
        }
        return message;
    }

    function sendToggle($, $switch, previousChecked) {
        var url = $switch.data('toggle-url');
        var $label = $switch.next('label');
        var messages = getMessages();

        if (!url || requestInProgress) {
            $switch.prop('checked', previousChecked);
            return;
        }

        requestInProgress = true;
        $switch.prop('disabled', true);
        $label.html('<span class="spinner-border spinner-border-sm" role="status"></span>');

        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
        }).done(function (response) {
            if (response && response.success) {
                var $cell = $switch.closest('td');
                if (response.html && $cell.length) {
                    $cell.html(response.html);
                }
                notify(response.message || messages.success, 'success');
                return;
            }

            $switch.prop('checked', previousChecked);
            notify((response && response.message) || messages.error, 'error');
        }).fail(function (xhr) {
            $switch.prop('checked', previousChecked);
            notify(extractErrorMessage(xhr, messages.error), 'error');
        }).always(function () {
            requestInProgress = false;
            if ($switch.closest('body').length) {
                $switch.prop('disabled', false);
                $label.html('');
            }
        });
    }

    function bindHandlers($) {
        if (!$) {
            return;
        }

        $(document).off('change.fvBooleanSwitch', '.fv-boolean-field-switch').on('change.fvBooleanSwitch', '.fv-boolean-field-switch', function (e) {
            e.stopPropagation();
            var $switch = $(this);
            var isChecked = $switch.is(':checked');
            sendToggle($, $switch, !isChecked);
        });
    }

    window.FvConfigCrudBooleanSwitch = {
        init: function ($) {
            if (initialized) {
                return;
            }
            initialized = true;
            bindHandlers($ || window.jQuery);
        },
    };
})(window);
