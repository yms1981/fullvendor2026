$(document).ready(function() {
    function categoryFormLang(key) {
        const lang = window.fvCategoryFormLang || {};
        const value = lang[key];
        if (typeof value === 'string' && value.trim() !== '') {
            return value;
        }
        return (window.fvUiText && window.fvUiText(key)) || '';
    }

    function formatCategoryNameExistsMessage(name) {
        const template = categoryFormLang('category_name_already_exists');
        const trimmed = String(name || '').trim();
        if (template && template.indexOf('%') !== -1 && trimmed !== '') {
            return template.replace('%s', trimmed);
        }
        if (template) {
            return template;
        }
        return categoryFormLang('category_exists') || categoryFormLang('category_duplicate');
    }

    function isCategoryNameDuplicateError(response) {
        const body = response && response.responseJSON ? response.responseJSON : {};
        let msg = body.message || body.msg || '';
        const errors = body.errors || {};
        if (errors.category_name) {
            const nameErr = Array.isArray(errors.category_name) ? errors.category_name.join(' ') : errors.category_name;
            msg = msg + ' ' + nameErr;
        }
        msg = String(msg).toLowerCase();
        return msg.indexOf('already exists') !== -1
            || msg.indexOf('ya existe') !== -1
            || msg.indexOf('ya ha sido tomado') !== -1
            || msg.indexOf('has been taken') !== -1
            || msg.indexOf('category name') !== -1
            || msg.indexOf('nombre de categoria') !== -1
            || msg.indexOf('nombre de categoría') !== -1
            || !!errors.category_name;
    }

    function localizeCategoryDuplicateResponse(response, name) {
        if (!isCategoryNameDuplicateError(response)) {
            return response;
        }
        const localized = formatCategoryNameExistsMessage(name);
        if (!localized) {
            return response;
        }
        return {
            responseJSON: {
                message: localized,
                errors: { category_name: [localized] }
            }
        };
    }

    // Cuando se abre el modal de agregar
    $('#modal_add_category').on('show.bs.modal', function (event) {
        const modalBody = $('#modal_add_category_body');
        
        // Mostrar spinner mientras carga
        modalBody.html('<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Cargando...</span></div></div>');
        
        // Cargar el formulario de agregar
        $.ajax({
            url: options.url + '/categories/addCategory',
            type: 'GET',
            dataType: 'html',
            success: function(response) {
                modalBody.html(response);
                initializeImagePreview();
            },
            error: function() {
                modalBody.html('<div class="alert alert-danger">Error al cargar el formulario de agregar</div>');
            }
        });
    });

    // Función para inicializar la vista previa de la imagen
    function initializeImagePreview() {
        const imageInput = document.getElementById('category_image_input_add_modal');
        const errorMessage = document.getElementById('image_error_message');
        const maxSize = 10 * 1024 * 1024; // 10 MB en bytes
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];

        if (imageInput) {
            imageInput.addEventListener('change', function(event) {
                const file = event.target.files[0];
                errorMessage.style.display = 'none';
                errorMessage.textContent = '';

                if (file) {
                    // Validar tipo de archivo
                    if (!allowedTypes.includes(file.type)) {
                        errorMessage.textContent = 'Formato no permitido. Solo se permiten archivos JPEG, JPG o PNG.';
                        errorMessage.style.display = 'block';
                        imageInput.value = '';
                        resetImagePreview();
                        return;
                    }

                    // Validar tamaño
                    if (file.size > maxSize) {
                        errorMessage.textContent = 'El archivo es demasiado grande. El tamaño máximo permitido es 10 MB.';
                        errorMessage.style.display = 'block';
                        imageInput.value = '';
                        resetImagePreview();
                        return;
                    }

                    // Mostrar vista previa
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#main-category-image-add-modal').attr('src', e.target.result);
                        $('.fileinput-preview').html('<img src="' + e.target.result + '" style="max-width:100%; max-height:200px; object-fit: contain;">');
                        $('.fileinput').addClass('fileinput-exists').removeClass('fileinput-new');
                    };
                    reader.readAsDataURL(file);
                } else {
                    resetImagePreview();
                }
            });
        }

        // Manejar el botón de eliminar imagen
        $(document).on('click', '[data-dismiss="fileinput"]', function() {
            resetImagePreview();
            if (imageInput) {
                imageInput.value = '';
            }
        });
    }

    // Función para resetear la vista previa
    function resetImagePreview() {
        const defaultImageUrl = options.url + '/image';
        $('#main-category-image-add-modal').attr('src', defaultImageUrl);
        $('.fileinput-preview').html('');
        $('.fileinput').removeClass('fileinput-exists').addClass('fileinput-new');
    }

    // Cuando se cierra el modal, limpiar el contenido
    $('#modal_add_category').on('hidden.bs.modal', function () {
        $('#modal_add_category_body').html('<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Cargando...</span></div></div>');
        // Limpiar el formulario
        const form = document.getElementById('form_add_category_modal');
        if (form) {
            form.reset();
        }
        // Resetear la imagen a la imagen por defecto
        resetImagePreview();
    });

    // Manejar el envío del formulario desde el modal
    $(document).on('submit', '#form_add_category_modal', function(e) {
        e.preventDefault();
        const form = document.getElementById('form_add_category_modal');
        const imageInput = document.getElementById('category_image_input_add_modal');
        const errorMessage = document.getElementById('image_error_message');
        const maxSize = 10 * 1024 * 1024; // 10 MB en bytes
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];

        // Validar imagen si existe
        if (imageInput && imageInput.files && imageInput.files.length > 0) {
            const file = imageInput.files[0];
            
            // Validar tipo
            if (!allowedTypes.includes(file.type)) {
                errorMessage.textContent = 'Formato no permitido. Solo se permiten archivos JPEG, JPG o PNG.';
                errorMessage.style.display = 'block';
                return false;
            }

            // Validar tamaño
            if (file.size > maxSize) {
                errorMessage.textContent = 'El archivo es demasiado grande. El tamaño máximo permitido es 10 MB.';
                errorMessage.style.display = 'block';
                return false;
            }
        }

        // Ocultar mensaje de error si todo está bien
        errorMessage.style.display = 'none';
        errorMessage.textContent = '';

        const formData = new FormData(form);
        
        // Asegurar que el archivo de imagen se capture correctamente
        if (imageInput && imageInput.files && imageInput.files.length > 0) {
            formData.append('category_image', imageInput.files[0]);
        }
        
        const submitButton = $('#boton_enviar_add_modal');
        const originalText = submitButton.html();

        // Deshabilitar el botón y mostrar loading
        const loadingText = (window.validationLang && window.validationLang.loading)
            || categoryFormLang('loading')
            || (window.fvUiText && window.fvUiText('loading'))
            || '';

        submitButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + loadingText);

        $.ajax({
            url: form.getAttribute('action'),
            type: 'POST',
            dataType: 'json',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                let title = response.message ?? categoryFormLang('category_saved') ?? '';
                swal({
                    title: title,
                    icon: 'success',
                    button: {
                        text: categoryFormLang('ok') || (window.fvUiText && window.fvUiText('ok')) || '',
                        value: true,
                        visible: true,
                        className: 'btn btn-primary'
                    }
                }).then(() => {
                    // Cerrar el modal
                    $('#modal_add_category').modal('hide');
                    // Recargar la página para actualizar la lista
                    window.location.reload();
                });
            },
            error: function(response) {
                const categoryName = $('#category_name_add_modal').val().trim();
                const localizedResponse = localizeCategoryDuplicateResponse(response, categoryName);

                if (typeof alert_validation !== 'undefined') {
                    alert_validation(
                        localizedResponse,
                        formatCategoryNameExistsMessage(categoryName)
                    );
                } else if (typeof swal !== 'undefined') {
                    let title = localizedResponse.responseJSON?.message
                        || response.responseJSON?.message
                        || categoryFormLang('category_save_error_generic');
                    const errors = (localizedResponse.responseJSON || response.responseJSON || {}).errors;
                    const errorsMessages = Object.values(errors || {})
                        .reduce((acc, curr) => acc.concat(curr), [])
                        .join('\n') || '';

                    swal({
                        title: title,
                        text: errorsMessages === title ? '' : errorsMessages,
                        icon: 'error',
                        button: {
                            text: categoryFormLang('ok') || (window.fvUiText && window.fvUiText('ok')) || '',
                            value: true,
                            visible: true,
                            className: 'btn btn-primary'
                        }
                    });
                }

                submitButton.prop('disabled', false).html(originalText);
            }
        });
    });
});

