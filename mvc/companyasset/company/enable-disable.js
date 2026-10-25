(function() {
    // Una sola alerta: no volver a mostrar si ya se mostró hace menos de 3 segundos
    var ALERT_COOLDOWN_MS = 3000;
    window.__enableDisableLastAlertAt = window.__enableDisableLastAlertAt || 0;
    window.__enableDisableRequestInProgress = window.__enableDisableRequestInProgress || false;
    window.__statusSwitchRequestInProgress = window.__statusSwitchRequestInProgress || false;

    function showErrorAlertOnce(msg, boton) {
        var now = Date.now();
        if (now - window.__enableDisableLastAlertAt < ALERT_COOLDOWN_MS) {
            if (boton && boton.prop) {
                boton.prop('disabled', false);
            }
            return;
        }
        window.__enableDisableLastAlertAt = now;
        if (boton && boton.prop) {
            boton.prop('disabled', false);
        }
        if (typeof swal !== 'undefined' && typeof swal === 'function') {
            swal({ title: '', text: msg, icon: 'error' });
        } else {
            alert(msg);
        }
    }

    function resolveErrorMessage(xhr) {
        var msg = (window.fvUiText && window.fvUiText('message_error_generic'))
            || (window.fvUiText && window.fvUiText('error_occurred'))
            || '';
        try {
            if (xhr.responseJSON && (xhr.responseJSON.message || xhr.responseJSON.msg)) {
                msg = xhr.responseJSON.message || xhr.responseJSON.msg;
            } else if (xhr.responseText) {
                var d = JSON.parse(xhr.responseText);
                msg = d.message || d.msg || msg;
            }
        } catch (e) {}
        return msg;
    }

    function parseAjaxData(response) {
        var data = response;
        if (typeof response === 'string') {
            try {
                data = response.trim().startsWith('{') ? JSON.parse(response) : null;
            } catch (e) {
                data = null;
            }
        }
        return data;
    }

    function showSuccessThenReload(message) {
        if (message && typeof swal !== 'undefined' && typeof swal === 'function') {
            swal({ title: '', text: message, icon: 'success' }).then(function () {
                window.location.reload();
            });
            return;
        }
        if (message) {
            alert(message);
        }
        window.location.reload();
    }

    function applyStatusSwitchSuccess($switch, data, response, isChecked) {
        var $wrap = $switch.closest('.fv-product-card__toggle, td, .custom-switch').parent();
        if ($switch.closest('td').length) {
            $wrap = $switch.closest('td');
        } else if ($switch.closest('.fv-product-card__toggle').length) {
            $wrap = $switch.closest('.fv-product-card__toggle');
        } else {
            $wrap = $switch.closest('.custom-switch').parent();
        }

        var $card = $switch.closest('.fv-product-card');
        if ($card.length) {
            $card.toggleClass('fv-product-card--disabled', !isChecked);
        }

        if (data && data.html) {
            $wrap.html(data.html);
        }

        showSuccessThenReload((data && data.message) ? data.message : '');
    }

    $(function () {
        // Quitar TODOS los handlers de .boton_estado para que solo quede uno (evitar doble petición y doble alerta)
        $(document).off('click', '.boton_estado').on('click.enableDisable', '.boton_estado', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            if (window.__enableDisableRequestInProgress) {
                return false;
            }

            var url = $(this).attr('href');
            var boton = $(this);
            var fila_padre = $(this).parent().parent();
            var fila_padre_fondo = fila_padre.hasClass('fondo-default');

            $.ajax({
                url: url,
                type: 'get',
                beforeSend: function() {
                    window.__enableDisableRequestInProgress = true;
                    boton.prop('disabled', true);
                },
                success: function(response) {
                    var data = parseAjaxData(response);
                    if (data && typeof data === 'object') {
                        if (data.message || data.error) {
                            if (!data.success) {
                                var msg = data.message || data.error || (window.fvUiText && window.fvUiText('message_error_generic')) || (window.fvUiText && window.fvUiText('error_occurred')) || '';
                                showErrorAlertOnce(msg, boton);
                                return;
                            }
                        }
                        if (data.success && data.html && data.message) {
                            var columna_boton = boton.parent();
                            columna_boton.html(data.html);
                            if (fila_padre_fondo) {
                                fila_padre.removeClass('fondo-default');
                            } else {
                                fila_padre.addClass('fondo-default');
                            }
                            showSuccessThenReload(data.message);
                            return;
                        }
                    }
                    var columna_boton = boton.parent();
                    columna_boton.html(typeof response === 'string' ? response : (data && data.html ? data.html : ''));
                    if (fila_padre_fondo) {
                        fila_padre.removeClass('fondo-default');
                    } else {
                        fila_padre.addClass('fondo-default');
                    }
                },
                error: function(xhr) {
                    showErrorAlertOnce(resolveErrorMessage(xhr), boton);
                },
                complete: function() {
                    window.__enableDisableRequestInProgress = false;
                }
            });
        });

        // Switch activar/desactivar (vista tabla y tarjetas de productos)
        $(document).off('change.fvProductStatusSwitch', '.status-switch').on('change.fvProductStatusSwitch', '.status-switch', function (e) {
            // Si el CRUD de configuración ya enlazó este switch, no duplicar.
            if (window.FvConfigCrudStatusSwitch && $(this).closest('.fv-config-crud-datagrid-wrap--active').length) {
                return;
            }

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
            if ($label.length) {
                $label.data('fv-prev-html', $label.html());
                $label.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
            }

            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'json',
                beforeSend: function () {
                    window.__statusSwitchRequestInProgress = true;
                }
            }).done(function (response) {
                var data = parseAjaxData(response);

                if (data && typeof data === 'object') {
                    if (data.success === false || (!data.success && (data.error || data.message) && !data.html)) {
                        $switch.prop('checked', !isChecked);
                        $switch.prop('disabled', false);
                        if ($label.length) {
                            $label.html($label.data('fv-prev-html') || '');
                        }
                        showErrorAlertOnce(data.message || data.error || resolveErrorMessage({}), $switch);
                        return;
                    }

                    if (data.success || data.html) {
                        applyStatusSwitchSuccess($switch, data, response, isChecked);
                        return;
                    }
                }

                applyStatusSwitchSuccess($switch, data || {}, response, isChecked);
            }).fail(function (xhr) {
                $switch.prop('checked', !isChecked);
                $switch.prop('disabled', false);
                if ($label.length) {
                    $label.html($label.data('fv-prev-html') || '');
                }
                showErrorAlertOnce(resolveErrorMessage(xhr), $switch);
            }).always(function () {
                window.__statusSwitchRequestInProgress = false;
            });
        });
    });
})();
