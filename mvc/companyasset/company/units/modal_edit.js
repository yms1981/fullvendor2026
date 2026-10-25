$(document).ready(function() {
    // Cuando se abre el modal de edición
    $('#modal_edit_unit').on('show.bs.modal', function (event) {
        const button = $(event.relatedTarget);
        const unitId = button.data('unit-id');
        const modalBody = $('#modal_edit_unit_body');
        
        // Mostrar spinner mientras carga
        modalBody.html('<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Cargando...</span></div></div>');
        
        // Cargar el formulario de edición
        $.ajax({
            url: options.url + '/units/getEditForm/' + unitId,
            type: 'GET',
            dataType: 'html',
            success: function(response) {
                modalBody.html(response);
            },
            error: function() {
                modalBody.html('<div class="alert alert-danger">Error al cargar el formulario de edición</div>');
            }
        });
    });

    // Cuando se cierra el modal, limpiar el contenido
    $('#modal_edit_unit').on('hidden.bs.modal', function () {
        $('#modal_edit_unit_body').html('<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Cargando...</span></div></div>');
    });

    // Manejar el envío del formulario desde el modal
    $(document).on('submit', '#form_edit_unit_modal', function(e) {
        e.preventDefault();
        const form = document.getElementById('form_edit_unit_modal');
        const submitButton = $('#boton_enviar_edit_unit_modal');
        const originalText = submitButton.html();

        // Obtener el unit_id del input hidden o de la URL
        const unitIdInput = form.querySelector('input[name="unit_id"]');
        const unitId = unitIdInput ? unitIdInput.value : (window.currentUnitId || null);
        
        console.log('ID capturado:', unitId, 'desde input:', unitIdInput?.value, 'desde window:', window.currentUnitId);
        
        if (!unitId) {
            swal({
                title: (window.fvUiText && window.fvUiText('error_occurred')) || '',
                text: 'No se pudo obtener el ID de la unidad. Por favor, recarga la página e intenta nuevamente.',
                icon: 'error'
            });
            return;
        }

        // Preparar los datos del formulario
        const isDefault = $('#unit-default-edit-modal').is(':checked') ? 1 : 0;
        const currentIsDefault = window.currentUnitIsDefault;
        const formData = {
            unit_id: unitId,
            unit: $('#unit_name_edit_modal').val(),
            description: $('#unit_description_edit_modal').val(),
            formato: $('input[name="formato"]:checked').val(),
            decimales: $('input[name="formato"]:checked').val() === 'decimal'
                ? parseInt($('#unit_decimales_edit_modal').val(), 10)
                : 0,
            default: isDefault
        };

        // Validar si hay una unidad por defecto y se está intentando cambiar
        // Solo validar si la unidad actual NO es la por defecto y se está marcando como default
        if (isDefault == 1 && !currentIsDefault && window.defaultEntity) {
            swal({
                title: 'Ya existe una unidad por defecto',
                text: '¿Desea cambiar la unidad por defecto?',
                icon: 'warning',
                buttons: {
                    cancel: {
                        text: 'Cancelar',
                        value: false,
                        visible: true,
                        className: "sweet-danager",
                        closeModal: true,
                    },
                    confirm: {
                        text: 'Cambiar',
                        value: true,
                        visible: true,
                        className: "sweet-warning",
                        closeModal: true
                    },
                },
            }).then((confirmed) => {
                if (confirmed) {
                    submitForm(form.getAttribute('action'), formData, submitButton, originalText);
                }
            });
        } else {
            submitForm(form.getAttribute('action'), formData, submitButton, originalText);
        }
    });

    function submitForm(url, formData, submitButton, originalText) {
        // Obtener traducción de loading desde validationLang
        const loadingText = (window.validationLang && window.validationLang.loading) || (window.fvUiText && window.fvUiText('loading')) || '';
        
        // Deshabilitar el botón y mostrar loading con spinner y texto traducido
        submitButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + loadingText);

        // Asegurar que la URL tenga el ID correcto
        const unitId = formData.unit_id;
        
        // Construir la URL correctamente
        if (!url || !url.includes('/update/')) {
            // Si la URL no es válida, construirla desde cero
            url = options.url + '/units/update/' + unitId;
        } else {
            // Extraer la parte base de la URL y reconstruirla con el ID correcto
            const urlMatch = url.match(/^(.*\/units\/update\/)(\d+)?/);
            if (urlMatch) {
                url = urlMatch[1] + unitId;
            } else {
                // Si no coincide el patrón, construir desde cero
                url = options.url + '/units/update/' + unitId;
            }
        }

        console.log('Enviando actualización a:', url, 'con datos:', formData);

        $.ajax({
            url: url,
            type: 'POST',
            dataType: 'json',
            data: formData,
            success: function(response) {
                let title = response.message ?? 'Unidad actualizada exitosamente!';
                swal({
                    title: title,
                    icon: 'success'
                }).then(() => {
                    // Cerrar el modal
                    $('#modal_edit_unit').modal('hide');
                    // Recargar la página para actualizar la lista
                    window.location.reload();
                });
            },
            error: function(response) {
                // Restaurar el botón
                submitButton.prop('disabled', false).html(originalText);
                
                // Usar alerta genérica de validación
                if (typeof alert_validation !== 'undefined') {
                    alert_validation(response);
                } else if (typeof alert_func !== 'undefined') {
                    // Fallback a alert_func si alert_validation no está disponible
                    let errorMsg = response.responseJSON?.message || response.responseJSON?.msg || 'No se pudo actualizar la unidad. Por favor, verifica los datos ingresados e intenta nuevamente.';
                    const errors = response.responseJSON?.errors;
                    const errorsMessages = Object.values(errors || {})
                        .reduce((acc, curr) => acc.concat(curr), [])
                        .join('\n') || null;
                    alert_func([errorMsg + (errorsMessages ? '\n' + errorsMessages : ''), 'error']);
                } else if (typeof swal !== 'undefined') {
                    // Fallback: usar swal directamente
                    let errorMsg = response.responseJSON?.message || response.responseJSON?.msg || 'No se pudo actualizar la unidad. Por favor, verifica los datos ingresados e intenta nuevamente.';
                    const errors = response.responseJSON?.errors;
                    const errorsMessages = Object.values(errors || {})
                        .reduce((acc, curr) => acc.concat(curr), [])
                        .join('\n') || null;
                    swal({
                        title: errorMsg,
                        text: errorsMessages,
                        icon: 'error',
                        button: {
                            text: (window.fvUiText && window.fvUiText('ok')) || '',
                            value: true,
                            visible: true,
                            className: "btn btn-primary"
                        }
                    });
                } else {
                    // Fallback básico
                    alert('No se pudo actualizar la unidad. Por favor, verifica los datos ingresados e intenta nuevamente.');
                }
            }
        });
    }
});

