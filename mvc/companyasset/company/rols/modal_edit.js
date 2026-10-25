$(document).ready(function() {
    // Cuando se abre el modal de edición
    $('#modal_edit_rol').on('show.bs.modal', function (event) {
        const button = $(event.relatedTarget);
        const rolId = button.data('rol-id');
        const modalBody = $('#modal_edit_rol_body');
        
        // Mostrar spinner mientras carga
        modalBody.html('<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Cargando...</span></div></div>');
        
        // Cargar el formulario de edición
        $.ajax({
            url: options.url + '/rols/getEditForm/' + rolId,
            type: 'GET',
            dataType: 'html',
            success: function(response) {
                modalBody.html(response);
            },
            error: function(xhr, status, error) {
                console.error('Error al cargar el formulario:', {
                    url: options.url + '/rols/getEditForm/' + rolId,
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseText: xhr.responseText,
                    error: error
                });
                modalBody.html('<div class="alert alert-danger">Error al cargar el formulario de edición. Ver consola para más detalles.</div>');
            }
        });
    });

    // Cuando se cierra el modal, limpiar el contenido
    $('#modal_edit_rol').on('hidden.bs.modal', function () {
        $('#modal_edit_rol_body').html('<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Cargando...</span></div></div>');
    });

    // Manejar el envío del formulario desde el modal
    $(document).on('submit', '#form_edit_rol_modal', function(e) {
        e.preventDefault();
        const form = document.getElementById('form_edit_rol_modal');
        const submitButton = $('#boton_enviar_edit_rol_modal');
        const originalText = submitButton.html();

        // Validar que los campos requeridos no estén vacíos
        const typeInput = $('#rol_type_edit_modal');
        const descEsInput = $('#rol_desc_es_edit_modal');
        const descEnInput = $('#rol_desc_en_edit_modal');
        
        if (!typeInput.val().trim()) {
            swal({
                title: (window.fvUiText && window.fvUiText('error_occurred')) || '',
                text: 'El tipo de rol es requerido',
                icon: 'error'
            });
            return false;
        }
        
        if (!descEsInput.val().trim()) {
            swal({
                title: (window.fvUiText && window.fvUiText('error_occurred')) || '',
                text: 'La descripción en español es requerida',
                icon: 'error'
            });
            return false;
        }
        
        if (!descEnInput.val().trim()) {
            swal({
                title: (window.fvUiText && window.fvUiText('error_occurred')) || '',
                text: 'La descripción en inglés es requerida',
                icon: 'error'
            });
            return false;
        }

        // Deshabilitar el botón y mostrar loading
        submitButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Actualizando...');

        const formData = new FormData(form);
        
        $.ajax({
            url: form.getAttribute('action'),
            type: 'POST',
            dataType: 'json',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                let title = response.message ?? 'Rol actualizado exitosamente!';
                swal({
                    title: title,
                    icon: 'success'
                }).then(() => {
                    // Cerrar el modal
                    $('#modal_edit_rol').modal('hide');
                    // Recargar la página para actualizar la lista
                    window.location.reload();
                });
            },
            error: function(response) {
                let title = response.responseJSON?.message ?? 'Ocurrió un error';
                const errors = response.responseJSON?.errors;

                const errorsMessages = Object.values(errors || {})
                    .reduce((acc, curr) => acc.concat(curr), [])
                    .join('\n') || null;

                swal({
                    title: title,
                    text: errorsMessages,
                    icon: 'error'
                });

                // Rehabilitar el botón
                submitButton.prop('disabled', false).html(originalText);
            }
        });
    });
});

