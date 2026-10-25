/**
 * @deprecated Este archivo ha sido movido a common/delete-alert.js
 * Se mantiene por compatibilidad hacia atrás
 * 
 * Para usar la nueva versión, reemplaza:
 * 'companyasset/company/delete.js'
 * por:
 * 'companyasset/company/common/delete-alert.js'
 */

/**
 * Alerta genérica para eliminar registros
 * Usa traducciones pasadas desde PHP a través de window.deleteLang
 */
(function() {
    'use strict';
    
    // Función para obtener las traducciones
    function getDeleteTranslations() {
        const toltipLang = window.deleteLang || {};
        const uiLang = window.fvUiLang || {};
        const merged = Object.assign({}, toltipLang, uiLang);

        const pick = function (key) {
            const value = merged[key];
            return (typeof value === 'string' && value.trim() !== '') ? value : '';
        };

        return {
            desea_eliminar_registro: pick('desea_eliminar_registro'),
            esta_seguro: pick('esta_seguro'),
            cancelar: pick('cancelar'),
            eliminar: pick('eliminar'),
            success_deleted: pick('success_deleted'),
            error_occurred: pick('error_occurred'),
            error_connection: pick('error_connection'),
            error_connection_message: pick('error_connection_message'),
            error_not_found: pick('error_not_found'),
            error_not_found_message: pick('error_not_found_message'),
            error_server: pick('error_server'),
            error_server_message: pick('error_server_message'),
            error_timeout: pick('error_timeout'),
            error_timeout_message: pick('error_timeout_message'),
            error_no_url: pick('error_no_url'),
            error_no_url_message: pick('error_no_url_message')
        };
    }
    
    // Función para mostrar la alerta de confirmación
    function showDeleteConfirmation(url, deleteButton, options) {
        options = options || {};
        const lang = getDeleteTranslations();
        const customTitle = options.title || lang.desea_eliminar_registro;
        const customText = options.text || lang.esta_seguro;
        const customConfirmText = options.confirmText || lang.eliminar;
        const customCancelText = options.cancelText || lang.cancelar;
        const onSuccess = options.onSuccess || function() { location.reload(); };
        const onError = options.onError || null;
        const method = options.method || 'GET';
        const data = options.data || null;
        
        if (!url) {
            console.error('No se encontró URL para eliminar');
            if (typeof swal !== 'undefined') {
                swal({
                    title: lang.error_no_url,
                    text: lang.error_no_url_message,
                    icon: 'error'
                });
            } else {
                alert(lang.error_no_url_message);
            }
            return;
        }
        
        swal({
            title: customTitle,
            text: customText,
            icon: 'warning',
            buttons: {
                cancel: {
                    text: customCancelText,
                    value: false,
                    visible: true,
                    className: "sweet-danager",
                    closeModal: true,
                },
                confirm: {
                    text: customConfirmText,
                    value: true,
                    visible: true,
                    className: "sweet-warning",
                    closeModal: true
                },
            },
        }).then((willDelete) => {
            if (willDelete) {
                // Deshabilitar el botón mientras se procesa
                if (deleteButton) {
                    deleteButton.prop('disabled', true);
                }
                
                const ajaxOptions = {
                    url: url,
                    type: method,
                    dataType: 'json',
                    timeout: 30000, // 30 segundos timeout
                    success: function(response) {
                        console.log('Delete success:', response);
                        const lang = getDeleteTranslations();
                        if (typeof swal !== 'undefined') {
                            swal({
                                title: response.message || lang.success_deleted,
                                icon: 'success'
                            }).then(() => {
                                if (onSuccess) {
                                    onSuccess(response);
                                }
                            });
                        } else {
                            alert(response.message || lang.success_deleted);
                            if (onSuccess) {
                                onSuccess(response);
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Delete error:', {
                            status: xhr.status,
                            statusText: xhr.statusText,
                            responseText: xhr.responseText,
                            error: error,
                            url: url
                        });
                        
                        // Rehabilitar el botón en caso de error
                        if (deleteButton) {
                            deleteButton.prop('disabled', false);
                        }
                        
                        const lang = getDeleteTranslations();
                        let title = lang.error_occurred;
                        let errorsMessages = null;
                        
                        // Intentar parsear la respuesta
                        let responseData = null;
                        try {
                            if (xhr.responseText) {
                                responseData = JSON.parse(xhr.responseText);
                            }
                        } catch (e) {
                            console.error('Error parsing response:', e);
                            // Si no es JSON, usar el texto de respuesta
                            if (xhr.responseText) {
                                errorsMessages = xhr.responseText.substring(0, 200);
                            }
                        }
                        
                        if (responseData) {
                            title = responseData.message || title;
                            const errors = responseData.errors;
                            
                            if (errors) {
                                errorsMessages = Object.values(errors)
                                    .reduce((acc, curr) => {
                                        if (Array.isArray(curr)) {
                                            return acc.concat(curr);
                                        }
                                        return acc.concat([curr]);
                                    }, [])
                                    .join('\n');
                            }
                        } else if (xhr.status === 0) {
                            title = lang.error_connection;
                            errorsMessages = lang.error_connection_message;
                        } else if (xhr.status === 404) {
                            title = lang.error_not_found;
                            errorsMessages = lang.error_not_found_message;
                        } else if (xhr.status === 500) {
                            title = lang.error_server;
                            errorsMessages = lang.error_server_message;
                        } else if (status === 'timeout') {
                            title = lang.error_timeout;
                            errorsMessages = lang.error_timeout_message;
                        }
                        
                        if (typeof swal !== 'undefined') {
                            swal({
                                title: title,
                                text: errorsMessages,
                                icon: 'error'
                            });
                        } else {
                            alert(title + (errorsMessages ? '\n\n' + errorsMessages : ''));
                        }
                        
                        // Ejecutar callback de error si existe
                        if (onError) {
                            onError(xhr, status, error);
                        }
                    }
                };
                
                // Agregar data si se proporciona
                if (data) {
                    if (method === 'POST' || method === 'PUT' || method === 'PATCH') {
                        ajaxOptions.data = data;
                        ajaxOptions.contentType = 'application/json';
                    } else {
                        ajaxOptions.data = data;
                    }
                }
                
                $.ajax(ajaxOptions);
            }
        });
    }
    
    // Inicializar cuando jQuery esté disponible
    if (typeof jQuery !== 'undefined') {
        $(function () {
            $(document).on('click', '.btn-go-delete, .go-delete', function(e) {
                e.preventDefault();
                
                const deleteButton = $(this);
                // Obtener URL del href (si es <a>) o del data-delete-url (si es <button>)
                const url = deleteButton.attr('href') || deleteButton.data('delete-url');
                
                // Obtener opciones personalizadas desde data attributes
                const options = {
                    title: deleteButton.data('delete-title') || null,
                    text: deleteButton.data('delete-text') || null,
                    confirmText: deleteButton.data('delete-confirm-text') || null,
                    cancelText: deleteButton.data('delete-cancel-text') || null,
                    method: deleteButton.data('delete-method') || 'GET',
                    onSuccess: deleteButton.data('delete-on-success') || null
                };
                
                showDeleteConfirmation(url, deleteButton, options);
            });
        });
    } else {
        // Esperar a que jQuery esté disponible
        if (typeof window.CoreLoader !== 'undefined' && CoreLoader.waitForJQuery) {
            CoreLoader.waitForJQuery(function() {
                $(function () {
                    $(document).on('click', '.btn-go-delete, .go-delete', function(e) {
                        e.preventDefault();
                        
                        const deleteButton = $(this);
                        const url = deleteButton.attr('href') || deleteButton.data('delete-url');
                        
                        const options = {
                            title: deleteButton.data('delete-title') || null,
                            text: deleteButton.data('delete-text') || null,
                            confirmText: deleteButton.data('delete-confirm-text') || null,
                            cancelText: deleteButton.data('delete-cancel-text') || null,
                            method: deleteButton.data('delete-method') || 'GET',
                            onSuccess: deleteButton.data('delete-on-success') || null
                        };
                        
                        showDeleteConfirmation(url, deleteButton, options);
                    });
                });
            });
        }
    }
    
    // Exportar función para uso manual
    window.showDeleteConfirmation = showDeleteConfirmation;
    window.getDeleteTranslations = getDeleteTranslations;
})();
aplica el boton