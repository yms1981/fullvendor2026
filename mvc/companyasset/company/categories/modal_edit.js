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

    // Función para cargar el formulario de edición en el modal
    function loadEditForm(categoryId) {
        const modalBody = $('#modal_edit_category_body');
        const loadingLabel = categoryFormLang('loading') || '';
        const errorLabel = categoryFormLang('load_form_error')
            || categoryFormLang('category_save_error_generic')
            || '';

        modalBody.html(
            '<div class="text-center py-4">' +
            '<div class="spinner-border text-danger" role="status" aria-hidden="true"></div>' +
            (loadingLabel ? '<div class="mt-2 text-muted">' + $('<div>').text(loadingLabel).html() + '</div>' : '') +
            '</div>'
        );

        const baseUrl = (typeof options !== 'undefined' && options.url)
            ? String(options.url).replace(/\/$/, '')
            : String((window.companyUrl || '/company')).replace(/\/$/, '');
        const editUrl = baseUrl + '/categories/edit/' + encodeURIComponent(categoryId);

        $.ajax({
            url: editUrl,
            type: 'GET',
            dataType: 'html',
            timeout: 30000,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            },
            success: function(response) {
                const html = (response == null) ? '' : String(response).trim();
                if (!html || html.indexOf('form_edit_category_modal') === -1) {
                    modalBody.html('<div class="alert alert-danger">' + $('<div>').text(errorLabel).html() + '</div>');
                    return;
                }
                modalBody.html(html);
            },
            error: function(xhr) {
                console.error('Error al cargar formulario de edición:', xhr && xhr.status, editUrl);
                modalBody.html('<div class="alert alert-danger">' + $('<div>').text(errorLabel).html() + '</div>');
            }
        });
    }

    // Cuando se abre el modal de edición desde el botón de editar
    $('#modal_edit_category').on('show.bs.modal', function (event) {
        const button = $(event.relatedTarget);
        let categoryId = button && button.length ? button.data('category-id') : null;
        if (!categoryId) {
            categoryId = $('#modal_edit_category').data('pending-category-id');
        }
        $('#modal_edit_category').removeData('pending-category-id');

        if (categoryId) {
            loadEditForm(categoryId);
        }
    });

    // Manejar clics en los enlaces del nombre de categoría
    $(document).on('click', '.category-edit-link', function(e) {
        e.preventDefault();
        const categoryId = $(this).data('category-id');

        if (categoryId) {
            $('#modal_edit_category').data('pending-category-id', categoryId);
            $('#modal_edit_category').modal('show');
        }
    });

    // Cuando se cierra el modal, limpiar el contenido
    $('#modal_edit_category').on('hidden.bs.modal', function () {
        const loadingLabel = categoryFormLang('loading') || '';
        $('#modal_edit_category_body').html(
            '<div class="text-center py-4">' +
            '<div class="spinner-border text-danger" role="status" aria-hidden="true"></div>' +
            (loadingLabel ? '<div class="mt-2 text-muted">' + $('<div>').text(loadingLabel).html() + '</div>' : '') +
            '</div>'
        );
    });

    // Manejar el envío del formulario desde el modal
    $(document).on('submit', '#form_edit_category_modal', function(e) {
        e.preventDefault();
        const form = document.getElementById('form_edit_category_modal');
        if (!form) {
            console.error('Formulario no encontrado');
            return;
        }
        
        const formData = new FormData(form);
        const submitButton = $('#boton_enviar_modal');
        const originalText = submitButton.html();


        // VALIDAR ARCHIVO ANTES DE ENVIAR
        const imageInput = document.getElementById('category_image_input_modal');
        const imageFile = imageInput ? imageInput.files[0] : null;
        
        if (imageFile) {
            console.log('✅ Archivo de imagen detectado:', imageFile.name, imageFile.size, 'bytes', 'tipo:', imageFile.type);
            
            // Validar tamaño (10MB)
            const maxSize = 10 * 1024 * 1024; // 10MB en bytes
            if (imageFile.size > maxSize) {
                swal({
                    title: 'Error de validación',
                    text: 'El tamaño máximo del archivo es 10MB',
                    icon: 'error'
                });
                submitButton.prop('disabled', false).html(originalText);
                return false;
            }
            
            // Validar tipo de archivo
            const validTypes = ['image/jpeg', 'image/jpg', 'image/png'];
            if (!validTypes.includes(imageFile.type)) {
                swal({
                    title: 'Error de validación',
                    text: 'El formato del archivo debe ser: jpg, jpeg o png',
                    icon: 'error'
                });
                submitButton.prop('disabled', false).html(originalText);
                return false;
            }
            
            // Validar extensión del nombre del archivo
            const validExtensions = ['jpg', 'jpeg', 'png'];
            const fileExtension = imageFile.name.split('.').pop().toLowerCase();
            if (!validExtensions.includes(fileExtension)) {
                swal({
                    title: 'Error de validación',
                    text: 'La extensión del archivo debe ser: .jpg, .jpeg o .png',
                    icon: 'error'
                });
                submitButton.prop('disabled', false).html(originalText);
                return false;
            }
            
            // Asegurarse de que el archivo esté en el FormData
            if (!formData.has('image') || formData.get('image').size === 0) {
                console.log('⚠️ Archivo no está en FormData, agregándolo manualmente');
                formData.append('image', imageFile);
            }
            
            console.log('✅ Archivo validado correctamente');
        } else {
            console.log('ℹ️ No se seleccionó archivo de imagen (esto es opcional)');
        }

        // Verificar qué se está enviando
        console.log('📦 FormData contents:');
        for (let pair of formData.entries()) {
            if (pair[1] instanceof File) {
                console.log(pair[0] + ': [File]', pair[1].name, pair[1].size, 'bytes');
            } else {
                console.log(pair[0] + ': ', pair[1]);
            }
        }

        // Deshabilitar el botón y mostrar loading
        const loadingText = categoryFormLang('loading')
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
                console.log('Respuesta del servidor:', response);
                
                // Verificar si la respuesta indica éxito
                if (response.success === false) {
                    swal({
                        title: response.msg || response.message || categoryFormLang('category_save_error_generic'),
                        icon: 'error',
                        button: {
                            text: categoryFormLang('ok') || (window.fvUiText && window.fvUiText('ok')) || '',
                            value: true,
                            visible: true,
                            className: 'btn btn-primary'
                        }
                    });
                    submitButton.prop('disabled', false).html(originalText);
                    return;
                }

                let title = response.message
                    ?? response.msg
                    ?? categoryFormLang('category_updated')
                    ?? categoryFormLang('category_success')
                    ?? '';
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
                    $('#modal_edit_category').modal('hide');
                    // Recargar la página para actualizar la lista
                    window.location.reload();
                });
            },
            error: function(xhr, status, error) {
                console.error('Error al enviar formulario:', error);

                const categoryName = $('#category_name_modal').val().trim();
                const localizedResponse = localizeCategoryDuplicateResponse(xhr, categoryName);

                if (typeof alert_validation !== 'undefined') {
                    alert_validation(
                        localizedResponse,
                        formatCategoryNameExistsMessage(categoryName)
                    );
                    submitButton.prop('disabled', false).html(originalText);
                    return;
                }

                let title = localizedResponse.responseJSON?.message
                    || xhr.responseJSON?.msg
                    || xhr.responseJSON?.message
                    || categoryFormLang('category_save_error_generic');
                let errorMessage = '';

                if (xhr.responseText && xhr.responseText.trim().startsWith('<!')) {
                    title = categoryFormLang('category_save_error_generic');
                    errorMessage = '';
                } else if (xhr.responseJSON) {
                    const errors = xhr.responseJSON.errors;
                    if (errors) {
                        errorMessage = Object.values(errors)
                            .reduce((acc, curr) => acc.concat(curr), [])
                            .join('\n');
                    }
                }

                swal({
                    title: title,
                    text: errorMessage === title ? '' : errorMessage,
                    icon: 'error',
                    button: {
                        text: categoryFormLang('ok') || (window.fvUiText && window.fvUiText('ok')) || '',
                        value: true,
                        visible: true,
                        className: 'btn btn-primary'
                    }
                });

                submitButton.prop('disabled', false).html(originalText);
            }
        });
    });
});

