$(document).ready(function() {
    // Cuando se abre el modal de edición
    $('#modal_edit_term').on('show.bs.modal', function (event) {
        const button = $(event.relatedTarget);
        const termId = button.data('term-id');
        const modalBody = $('#modal_edit_term_body');
        
        // Mostrar spinner mientras carga
        modalBody.html('<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Cargando...</span></div></div>');
        
        // Cargar el formulario de edición
        $.ajax({
            url: options.url + '/terms/getEditForm/' + termId,
            type: 'GET',
            dataType: 'html',
            success: function(response) {
                modalBody.html(response);
            },
            error: function(xhr, status, error) {
                console.error('Error al cargar el formulario:', {
                    url: options.url + '/terms/getEditForm/' + termId,
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseText: xhr.responseText,
                    error: error
                });
                modalBody.html('<div class="alert alert-danger">Error al cargar el formulario de edición</div>');
            }
        });
    });

    // Cuando se cierra el modal, limpiar el contenido
    $('#modal_edit_term').on('hidden.bs.modal', function () {
        $('#modal_edit_term_body').html('<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Cargando...</span></div></div>');
    });

    // Manejar el envío del formulario desde el modal
    $(document).on('submit', '#form_edit_term_modal', function(e) {
        e.preventDefault();
        const form = document.getElementById('form_edit_term_modal');
        const submitButton = $('#boton_enviar_edit_term_modal');
        const originalText = submitButton.html();

        // Validar que el nombre no esté vacío
        const nameInput = $('#term_name_edit_modal');
        if (!nameInput.val().trim()) {
            swal({
                title: (window.fvUiText && window.fvUiText('error_occurred')) || '',
                text: 'El nombre del término es requerido',
                icon: 'error'
            });
            return false;
        }

        // Obtener traducción de loading desde validationLang (mismo comportamiento que Units)
        const loadingText = (window.validationLang && window.validationLang.loading) || 'Loading...';

        // Deshabilitar el botón y mostrar loading con spinner y texto traducido
        submitButton.prop('disabled', true).html(
            '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + loadingText
        );

        const formData = new FormData(form);
        formData.set('default', form.querySelector('[name="checkBoxDefault"]').checked ? 1 : 0);
        
        $.ajax({
            url: form.getAttribute('action'),
            type: 'POST',
            dataType: 'json',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                let title = response.message ?? 'Término de pago actualizado exitosamente!';
                swal({
                    title: title,
                    icon: 'success'
                }).then(() => {
                    // Cerrar el modal
                    $('#modal_edit_term').modal('hide');
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

