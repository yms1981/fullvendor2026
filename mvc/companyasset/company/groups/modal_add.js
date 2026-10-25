$(document).ready(function() {
    function groupFormLang(key) {
        const lang = window.fvGroupFormLang || {};
        const value = lang[key];
        if (typeof value === 'string' && value.trim() !== '') {
            return value;
        }
        return (window.fvUiText && window.fvUiText(key)) || '';
    }

    function formatGroupNameExistsMessage(name) {
        const template = groupFormLang('group_name_already_exists');
        const trimmed = String(name || '').trim();
        if (template && template.indexOf('%') !== -1 && trimmed !== '') {
            return template.replace('%s', trimmed);
        }
        if (template) {
            return template;
        }
        return groupFormLang('group_existente') || groupFormLang('group_duplicate');
    }

    function isGroupNameDuplicateError(response) {
        const body = response && response.responseJSON ? response.responseJSON : {};
        let msg = body.message || body.msg || '';
        const errors = body.errors || {};
        if (errors.name) {
            const nameErr = Array.isArray(errors.name) ? errors.name.join(' ') : errors.name;
            msg = msg + ' ' + nameErr;
        }
        msg = String(msg).toLowerCase();
        return msg.indexOf('already exists') !== -1
            || msg.indexOf('ya existe') !== -1
            || msg.indexOf('group name') !== -1
            || msg.indexOf('nombre del grupo') !== -1
            || !!errors.name;
    }

    function localizeGroupDuplicateResponse(response, name) {
        if (!isGroupNameDuplicateError(response)) {
            return response;
        }
        const localized = formatGroupNameExistsMessage(name);
        if (!localized) {
            return response;
        }
        return {
            responseJSON: {
                message: localized,
                errors: { name: [localized] }
            }
        };
    }

    // Manejar el campo de ajuste de precio
    function initPriceAdjustment() {
        const select = $("select[name='percentage_on_price']");
        const container = $("#price-adjustment-field-modal");
        const label = $("#price-adjustment-label-modal");

        function togglePriceAdjustmentField(value) {
            if (value === "Increase on Price List %") {
                container.show();
                label.text(groupFormLang('percentage_increase_label'));
            } else if (value === "Decrease on Price List %") {
                container.show();
                label.text(groupFormLang('percentage_decrease_label'));
            } else {
                container.hide();
                label.text("");
            }
        }

        select.off("change").on("change", function () {
            togglePriceAdjustmentField($(this).val());
        });

        togglePriceAdjustmentField(select.val());
    }

    // Cuando se abre el modal de agregar
    $('#modal_add_group').on('show.bs.modal', function (event) {
        const modalBody = $('#modal_add_group_body');
        
        // Mostrar spinner mientras carga
        modalBody.html('<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Cargando...</span></div></div>');
        
        // Cargar el formulario de agregar
        $.ajax({
            url: options.url + '/groups/addGroup',
            type: 'GET',
            dataType: 'html',
            success: function(response) {
                modalBody.html(response);
                // Inicializar el campo de ajuste de precio
                initPriceAdjustment();
                // Inicializar el checkbox por defecto si existe un grupo por defecto
                if (window.defaultEntity) {
                    $('#group-default-modal').prop('checked', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error al cargar el formulario:', {
                    url: options.url + '/groups/addGroup',
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseText: xhr.responseText,
                    error: error
                });
                modalBody.html('<div class="alert alert-danger">Error al cargar el formulario de agregar. Ver consola para más detalles.</div>');
            }
        });
    });

    // Cuando se cierra el modal, limpiar el contenido
    $('#modal_add_group').on('hidden.bs.modal', function () {
        $('#modal_add_group_body').html('<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Cargando...</span></div></div>');
        // Limpiar el formulario
        const form = document.getElementById('form_add_group_modal');
        if (form) {
            form.reset();
            $('#group-default-modal').prop('checked', false);
        }
    });

        // Manejar el envío del formulario desde el modal
    $(document).on('submit', '#form_add_group_modal', function(e) {
        e.preventDefault();
        const form = document.getElementById('form_add_group_modal');
        const submitButton = $('#boton_enviar_add_group_modal');
        const originalText = submitButton.html();

        // Validar que el nombre no esté vacío
        const nameInput = $('#group_name_add_modal');
        if (!nameInput.val().trim()) {
            swal({
                title: (window.fvUiText && window.fvUiText('error_occurred')) || '',
                text: groupFormLang('group_name_required'),
                icon: 'error'
            });
            return false;
        }

        const loadingText = (window.validationLang && window.validationLang.loading) || (window.fvUiText && window.fvUiText('loading')) || '';

        // Deshabilitar el botón y mostrar loading
        submitButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + loadingText);

        const formData = new FormData(form);
        
        $.ajax({
            url: form.getAttribute('action'),
            type: 'POST',
            dataType: 'json',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                // Restaurar el botón
                submitButton.prop('disabled', false).html(originalText);

                let successMsg = response.message || response.msg || groupFormLang('group_create_success') || groupFormLang('group_saved');

                if (typeof alert_func !== 'undefined') {
                    alert_func([successMsg, 'success']);
                    setTimeout(function() {
                        $('#modal_add_group').modal('hide');
                        window.location.reload();
                    }, 800);
                } else if (typeof swal !== 'undefined') {
                    swal({
                        title: successMsg,
                        icon: 'success',
                        button: {
                            text: groupFormLang('ok') || (window.fvUiText && window.fvUiText('ok')) || '',
                            value: true,
                            visible: true,
                            className: "btn btn-primary"
                        }
                    }).then(() => {
                        $('#modal_add_group').modal('hide');
                        window.location.reload();
                    });
                } else {
                    alert(successMsg);
                    $('#modal_add_group').modal('hide');
                    window.location.reload();
                }
            },
            error: function(response) {
                // Rehabilitar el botón
                submitButton.prop('disabled', false).html(originalText);

                const groupName = $('#group_name_add_modal').val().trim();
                const localizedResponse = localizeGroupDuplicateResponse(response, groupName);

                // Usar alerta genérica de validación con mensaje específico de grupo duplicado
                if (typeof alert_validation !== 'undefined') {
                    alert_validation(
                        localizedResponse,
                        formatGroupNameExistsMessage(groupName)
                    );
                } else if (typeof alert_func !== 'undefined') {
                    let errorMsg = localizedResponse.responseJSON?.message
                        || response.responseJSON?.message
                        || response.responseJSON?.msg
                        || groupFormLang('group_save_error_generic');
                    const errors = response.responseJSON?.errors;
                    const errorsMessages = Object.values(errors || {})
                        .reduce((acc, curr) => acc.concat(curr), [])
                        .join('\n') || null;
                    alert_func([errorMsg + (errorsMessages ? '\n' + errorsMessages : ''), 'error']);
                } else if (typeof swal !== 'undefined') {
                    let errorMsg = localizedResponse.responseJSON?.message
                        || response.responseJSON?.message
                        || response.responseJSON?.msg
                        || groupFormLang('group_save_error_generic');
                    const errors = response.responseJSON?.errors;
                    const errorsMessages = Object.values(errors || {})
                        .reduce((acc, curr) => acc.concat(curr), [])
                        .join('\n') || null;
                    swal({
                        title: errorMsg,
                        text: errorsMessages,
                        icon: 'error',
                        button: {
                            text: groupFormLang('ok') || (window.fvUiText && window.fvUiText('ok')) || '',
                            value: true,
                            visible: true,
                            className: "btn btn-primary"
                        }
                    });
                } else {
                    alert(groupFormLang('group_save_error_generic'));
                }
            }
        });
    });
});

