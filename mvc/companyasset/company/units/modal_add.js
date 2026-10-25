$(document).ready(function() {
    // Cuando se abre el modal de agregar
    $('#modal_add_unit').on('show.bs.modal', function (event) {
        const modalBody = $('#modal_add_unit_body');
        
        // Mostrar spinner mientras carga
        modalBody.html('<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Cargando...</span></div></div>');
        
        // Cargar el formulario de agregar
        $.ajax({
            url: options.url + '/units/addUnit',
            type: 'GET',
            dataType: 'html',
            success: function(response) {
                modalBody.html(response);
            },
            error: function() {
                modalBody.html('<div class="alert alert-danger">Error al cargar el formulario de agregar</div>');
            }
        });
    });

    // Cuando se cierra el modal, limpiar el contenido
    $('#modal_add_unit').on('hidden.bs.modal', function () {
        $('#modal_add_unit_body').html('<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Cargando...</span></div></div>');
        // Limpiar el formulario
        const form = document.getElementById('form_add_unit_modal');
        if (form) {
            form.reset();
            // Resetear los radio buttons
            $('#entero_modal').prop('checked', true);
            $('#decimal_modal').prop('checked', false);
            // Resetear el checkbox
            $('#unit-default-modal').prop('checked', false);
        }
    });

    // Manejar el envío del formulario desde el modal
    $(document).on('submit', '#form_add_unit_modal', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const form = document.getElementById('form_add_unit_modal');
        if (!form) {
            console.error('Formulario no encontrado');
            return false;
        }
        
        const submitButton = $('#boton_enviar_add_unit_modal');
        if (submitButton.length === 0) {
            console.error('Botón de envío no encontrado');
            return false;
        }
        
        const originalText = submitButton.html();

        // Validar campos requeridos
        const unitName = $('#unit_name_modal').val();
        const unitDescription = $('#unit_description_modal').val();
        
        if (!unitName || !unitDescription) {
            const errorMsg = 'Por favor complete todos los campos requeridos';
            if (typeof alert_func !== 'undefined') {
                alert_func([errorMsg, 'error']);
            } else if (typeof swal !== 'undefined') {
                swal({
                    title: errorMsg,
                    icon: 'error',
                    button: {
                        text: "OK",
                        value: true,
                        visible: true,
                        className: "btn btn-primary"
                    }
                });
            } else {
                alert(errorMsg);
            }
            return false;
        }

        // Preparar los datos del formulario
        const isDefault = $('#unit-default-modal').is(':checked') ? 1 : 0;
        const formData = {
            unit: unitName,
            description: unitDescription,
            formato: $('input[name="formato"]:checked').val(),
            decimales: $('input[name="formato"]:checked').val() === 'decimal'
                ? parseInt($('#unit_decimales_modal').val(), 10)
                : 0,
            default: isDefault
        };

        // Validar si hay una unidad por defecto y se está intentando crear otra
        if (isDefault == 1 && window.defaultEntity) {
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
        
        return false;
    });

    function submitForm(url, formData, submitButton, originalText) {
        // Obtener traducción de loading desde validationLang
        const loadingText = (window.validationLang && window.validationLang.loading) || 'Loading...';
        
        // Deshabilitar el botón y mostrar loading con spinner y texto traducido
        submitButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + loadingText);

        $.ajax({
            url: url,
            type: 'POST',
            dataType: 'json',
            data: formData,
            success: function(response) {
                // Restaurar el botón
                submitButton.prop('disabled', false).html(originalText);
                
                // Obtener el mensaje de éxito
                let successMsg = response.message || response.msg || 'Unidad creada exitosamente!';
                
                // Usar alerta genérica
                if (typeof alert_func !== 'undefined') {
                    alert_func([successMsg, 'success']);
                    // Cerrar el modal y recargar después de un breve delay para que se vea la alerta
                    setTimeout(function() {
                        $('#modal_add_unit').modal('hide');
                        window.location.reload();
                    }, 800);
                } else if (typeof swal !== 'undefined') {
                    // Fallback: usar swal directamente si alert_func no está disponible
                    swal({
                        title: successMsg,
                        icon: 'success',
                        button: {
                            text: "OK",
                            value: true,
                            visible: true,
                            className: "btn btn-primary"
                        }
                    }).then(() => {
                        // Cerrar el modal
                        $('#modal_add_unit').modal('hide');
                        // Recargar la página para actualizar la lista
                        window.location.reload();
                    });
                } else {
                    // Fallback básico
                    alert(successMsg);
                    $('#modal_add_unit').modal('hide');
                    window.location.reload();
                }
            },
            error: function(response) {
                // Restaurar el botón
                submitButton.prop('disabled', false).html(originalText);
                
                // Usar alerta genérica de validación
                if (typeof alert_validation !== 'undefined') {
                    alert_validation(response);
                } else if (typeof alert_func !== 'undefined') {
                    // Fallback a alert_func si alert_validation no está disponible
                    let errorMsg = response.responseJSON?.message || response.responseJSON?.msg || 'No se pudo guardar la unidad. Por favor, verifica los datos ingresados e intenta nuevamente.';
                    const errors = response.responseJSON?.errors;
                    const errorsMessages = Object.values(errors || {})
                        .reduce((acc, curr) => acc.concat(curr), [])
                        .join('\n') || null;
                    alert_func([errorMsg + (errorsMessages ? '\n' + errorsMessages : ''), 'error']);
                } else if (typeof swal !== 'undefined') {
                    // Fallback: usar swal directamente
                    let errorMsg = response.responseJSON?.message || response.responseJSON?.msg || 'No se pudo guardar la unidad. Por favor, verifica los datos ingresados e intenta nuevamente.';
                    const errors = response.responseJSON?.errors;
                    const errorsMessages = Object.values(errors || {})
                        .reduce((acc, curr) => acc.concat(curr), [])
                        .join('\n') || null;
                    swal({
                        title: errorMsg,
                        text: errorsMessages,
                        icon: 'error',
                        button: {
                            text: "OK",
                            value: true,
                            visible: true,
                            className: "btn btn-primary"
                        }
                    });
                } else {
                    // Fallback básico
                    alert('No se pudo guardar la unidad. Por favor, verifica los datos ingresados e intenta nuevamente.');
                }
            }
        });
    }
});

