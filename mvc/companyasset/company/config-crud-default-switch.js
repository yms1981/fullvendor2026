/**
 * Switch "por defecto" en grillas CRUD de configuración.
 */
(function (window) {
    'use strict';

    var requestInProgress = false;
    var initialized = false;

    function getMessages() {
        return window.fvConfigCrudDefaultSwitch || {};
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
                timer: icon === 'success' ? 1800 : undefined,
            });
            return;
        }
        window.alert(message);
    }

    function confirmChange(messages, onConfirm, onCancel) {
        var title = messages.confirmTitle || '';
        var text = messages.confirmText || '';
        var confirmLabel = messages.confirmButton || (window.fvUiLang && window.fvUiLang.default_switch_confirm_button) || '';
        var cancelLabel = messages.cancelButton || (window.fvUiLang && window.fvUiLang.default_switch_cancel_button) || '';

        if (typeof swal === 'function') {
            swal({
                title: title,
                text: text,
                icon: 'warning',
                buttons: {
                    cancel: {
                        text: cancelLabel,
                        value: false,
                        visible: true,
                        className: 'sweet-danager',
                        closeModal: true,
                    },
                    confirm: {
                        text: confirmLabel,
                        value: true,
                        visible: true,
                        className: 'sweet-warning',
                        closeModal: true,
                    },
                },
            }).then(function (confirmed) {
                if (confirmed) {
                    onConfirm();
                } else if (typeof onCancel === 'function') {
                    onCancel();
                }
            });
            return;
        }

        if (window.confirm((title ? title + '\n' : '') + text)) {
            onConfirm();
        } else if (typeof onCancel === 'function') {
            onCancel();
        }
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

    function renumberOrderAvatars($table) {
        if (!$table || !$table.length) {
            return;
        }
        $table.find('tbody tr').each(function (index) {
            var $cell = $(this).find('td.fv-config-crud-order-cell').first();
            if (!$cell.length) {
                return;
            }
            var num = index + 1;
            $cell.attr('data-order', num);
            $cell.find('.fv-config-crud-order-avatar__number').text(num);
        });
    }

    function moveDefaultRowToTop($table, $activeSwitch) {
        if (!$table || !$table.length || !$activeSwitch || !$activeSwitch.length) {
            return;
        }
        var $row = $activeSwitch.closest('tr');
        var $tbody = $table.find('tbody').first();
        if (!$row.length || !$tbody.length) {
            return;
        }

        $tbody.find('tr').removeClass('fondo-default');
        $row.addClass('fondo-default');
        $tbody.find('.fv-config-crud-order-avatar').removeClass('fv-config-crud-order-avatar--default');
        $row.find('.fv-config-crud-order-avatar').addClass('fv-config-crud-order-avatar--default');
        $tbody.prepend($row);
        renumberOrderAvatars($table);

        if (typeof $.fn.DataTable === 'function' && $.fn.DataTable.isDataTable($table)) {
            $table.DataTable().rows().invalidate('dom').draw(false);
        }
    }

    function applyDefaultState($table, $activeSwitch) {
        if (!$table || !$table.length) {
            return;
        }
        $table.find('.fv-default-switch').each(function () {
            var $other = $(this);
            $other.prop('checked', $other.is($activeSwitch));
            $other.prop('disabled', false);
            $other.next('label').html('');
        });
        moveDefaultRowToTop($table, $activeSwitch);
    }

    function sendSetDefault($, $switch) {
        var url = $switch.data('set-default-url');
        var $label = $switch.next('label');
        var $table = $switch.closest('table');
        var messages = getMessages();

        if (!url || requestInProgress) {
            $switch.prop('checked', false);
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
                    $switch = $cell.find('.fv-default-switch').first();
                }
                applyDefaultState($table, $switch);
                notify(response.message || messages.success, 'success');
                return;
            }

            $switch.prop('checked', false);
            notify((response && response.message) || messages.error, 'error');
        }).fail(function (xhr) {
            $switch.prop('checked', false);
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

        $(document).off('change.fvDefaultSwitch', '.fv-default-switch').on('change.fvDefaultSwitch', '.fv-default-switch', function (e) {
            e.stopPropagation();
            var $switch = $(this);
            var isChecked = $switch.is(':checked');
            var messages = getMessages();
            var $table = $switch.closest('table');
            var $others = $table.find('.fv-default-switch:checked').not($switch);

            if (!isChecked) {
                $switch.prop('checked', true);
                notify(messages.cannotUnset, 'warning');
                return;
            }

            if ($others.length) {
                confirmChange(messages, function () {
                    sendSetDefault($, $switch);
                }, function () {
                    $switch.prop('checked', false);
                });
                return;
            }

            sendSetDefault($, $switch);
        });
    }

    window.FvConfigCrudDefaultSwitch = {
        init: function ($) {
            if (initialized) {
                return;
            }
            initialized = true;
            bindHandlers($ || window.jQuery);
        },
        renumberOrderAvatars: renumberOrderAvatars,
    };
})(window);
