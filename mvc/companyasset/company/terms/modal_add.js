$(document).ready(function() {
    // Cuando se abre el modal de agregar
    $('#modal_add_term').on('show.bs.modal', function (event) {
        const modalBody = $('#modal_add_term_body');
        
        // Mostrar spinner mientras carga
        modalBody.html('<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Cargando...</span></div></div>');
        
        // Cargar el formulario de agregar
        $.ajax({
            url: options.url + '/terms/addTerm',
            type: 'GET',
            dataType: 'html',
            success: function(response) {
                modalBody.html(response);
                // Inicializar el checkbox por defecto si existe un término por defecto
                if (window.defaultEntity) {
                    $('#term-default-modal').prop('checked', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error al cargar el formulario:', {
                    url: options.url + '/terms/addTerm',
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
    $('#modal_add_term').on('hidden.bs.modal', function () {
        $('#modal_add_term_body').html('<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Cargando...</span></div></div>');
        // Limpiar el formulario
        const form = document.getElementById('form_add_term_modal');
        if (form) {
            form.reset();
            $('#term-default-modal').prop('checked', false);
        }
    });

    // Manejar el envío del formulario desde el modal
    $(document).on('submit', '#form_add_term_modal', function(e) {
        e.preventDefault();
        const form = document.getElementById('form_add_term_modal');
        const submitButton = $('#boton_enviar_add_term_modal');
        const originalText = submitButton.html();

        // Obtener traducción de loading desde validationLang (mismo comportamiento que Units)
        const loadingText = (window.validationLang && window.validationLang.loading) || 'Loading...';

        // Deshabilitar el botón y mostrar loading con spinner y texto traducido
        submitButton.prop('disabled', true).html(
            '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + loadingText
        );
        const formData = new FormData(form);
        
        $.ajax({
            url: form.getAttribute('action'),
            type: 'POST',
            dataType: 'json',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                let title = response.message ?? 'Término de pago creado exitosamente!';
                swal({
                    title: title,
                    icon: 'success'
                }).then(() => {
                    // Cerrar el modal
                    $('#modal_add_term').modal('hide');
                    // Recargar la página para actualizar la lista
                    window.location.reload();
                });
            },
            error: function(response) {
                // Rehabilitar el botón
                submitButton.prop('disabled', false).html(originalText);
                
                // Usar alerta genérica de validación
                if (typeof alert_validation !== 'undefined') {
                    alert_validation(response);
                } else if (typeof alert_func !== 'undefined') {
                    // Fallback a alert_func si alert_validation no está disponible
                    let errorMsg = response.responseJSON?.message || response.responseJSON?.msg || 'No se pudo guardar el término de venta. Por favor, verifica los datos ingresados e intenta nuevamente.';
                    const errors = response.responseJSON?.errors;
                    const errorsMessages = Object.values(errors || {})
                        .reduce((acc, curr) => acc.concat(curr), [])
                        .join('\n') || null;
                    alert_func([errorMsg + (errorsMessages ? '\n' + errorsMessages : ''), 'error']);
                } else if (typeof swal !== 'undefined') {
                    // Fallback: usar swal directamente
                    let errorMsg = response.responseJSON?.message || response.responseJSON?.msg || 'No se pudo guardar el término de venta. Por favor, verifica los datos ingresados e intenta nuevamente.';
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
                    alert('No se pudo guardar el término de venta. Por favor, verifica los datos ingresados e intenta nuevamente.');
                }
            }
        });
    });
});

