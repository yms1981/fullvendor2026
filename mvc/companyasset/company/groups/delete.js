// JavaScript específico para eliminar grupos - solo para /company/groups
$(document).ready(function() {
    console.log('Groups delete.js loaded');
    
    // Verificar que estamos en la página de groups
    const currentPath = window.location.pathname;
    if (currentPath.indexOf('/groups') === -1 && currentPath.indexOf('/group/') === -1) {
        console.log('Not in groups page, skipping');
        return;
    }
    
    // Remover cualquier handler previo del delete.js genérico
    $(document).off('click', 'a.go-delete, a.btn-go-delete, button.go-delete, button.btn-go-delete');
    
    // Agregar handler específico para groups
    // Usar delegación de eventos para capturar clicks en botones y enlaces con clase go-delete
    $(document).on('click', 'a.go-delete, a.btn-go-delete, button.go-delete, button.btn-go-delete', function(e) {
        console.log('Delete button clicked');
        console.log('Event target:', e.target);
        console.log('Current element:', this);
        console.log('Element tag:', this.tagName);
        
        // Prevenir el comportamiento por defecto
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        
        const deleteButton = $(this);
        // Obtener URL del href (si es <a>) o del data-delete-url (si es <button>)
        const url = deleteButton.attr('href') || deleteButton.data('delete-url');
        
        console.log('Delete URL:', url);
        
        if (!url) {
            console.error('No URL found');
            if (typeof swal !== 'undefined') {
                swal({
                    title: 'Error',
                    text: 'No se encontró la URL para eliminar',
                    icon: 'error'
                });
            } else {
                alert('No se encontró la URL para eliminar');
            }
            return false;
        }
        
        console.log('About to show swal');
        console.log('swal type:', typeof swal);
        console.log('swal available:', typeof swal !== 'undefined');
        
        // Verificar que swal esté disponible y sea una función
        if (typeof swal === 'undefined' || typeof swal !== 'function') {
            console.error('swal no está disponible o no es una función');
            // Usar confirm nativo como fallback
            if (confirm('¿Desea eliminar este registro? Esta acción no se puede deshacer')) {
                $.ajax({
                    url: url,
                    type: 'GET',
                    dataType: 'json',
                    timeout: 30000,
                    success: function(response) {
                        alert(response.message || 'Eliminado con éxito');
                        location.reload();
                    },
                    error: function(xhr, status, error) {
                        alert('Ocurrió un error al eliminar');
                    }
                });
            }
            return false;
        }
        
        console.log('Calling swal...');
        
        // Mostrar alerta de confirmación usando la misma sintaxis que delete.js genérico
        try {
            const swalPromise = swal({
                title: '¿Desea eliminar este registro?',
                text: 'Esta acción no se puede deshacer',
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
                        text: 'Eliminar',
                        value: true,
                        visible: true,
                        className: "sweet-warning",
                        closeModal: true
                    },
                },
            });
            
            console.log('swal called, promise:', swalPromise);
            
            if (swalPromise && typeof swalPromise.then === 'function') {
                swalPromise.then((willDelete) => {
            console.log('User choice:', willDelete);
            if (willDelete) {
                console.log('Making AJAX request');
                // Hacer la petición AJAX
                $.ajax({
                    url: url,
                    type: 'GET',
                    dataType: 'json',
                    timeout: 30000,
                    success: function(response) {
                        console.log('Delete success');
                        swal({
                            title: response.message || 'Eliminado con éxito',
                            icon: 'success'
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error('Delete error:', xhr, status, error);
                        let title = 'Ocurrió un error';
                        let errorsMessages = null;
                        
                        let responseData = null;
                        try {
                            if (xhr.responseText) {
                                responseData = JSON.parse(xhr.responseText);
                            }
                        } catch (e) {
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
                            title = 'Error de conexión';
                            errorsMessages = 'No se pudo conectar con el servidor. Verifique su conexión.';
                        } else if (xhr.status === 404) {
                            title = 'No encontrado';
                            errorsMessages = 'La ruta de eliminación no fue encontrada.';
                        } else if (xhr.status === 500) {
                            title = 'Error del servidor';
                            errorsMessages = 'Ocurrió un error en el servidor. Por favor, intente nuevamente.';
                        } else if (status === 'timeout') {
                            title = 'Tiempo de espera agotado';
                            errorsMessages = 'La solicitud tardó demasiado tiempo. Por favor, intente nuevamente.';
                        }
                        
                        swal({
                            title: title,
                            text: errorsMessages,
                            icon: 'error'
                        });
                    }
                });
            } else {
                console.log('User cancelled');
            }
                }).catch(function(error) {
                    console.error('Error en promesa de swal:', error);
                });
            } else {
                console.error('swal no retornó una promesa válida');
                // Fallback a confirm
                if (confirm('¿Desea eliminar este registro? Esta acción no se puede deshacer')) {
                    $.ajax({
                        url: url,
                        type: 'GET',
                        dataType: 'json',
                        timeout: 30000,
                        success: function(response) {
                            alert(response.message || 'Eliminado con éxito');
                            location.reload();
                        },
                        error: function(xhr, status, error) {
                            alert('Ocurrió un error al eliminar');
                        }
                    });
                }
            }
        } catch (error) {
            console.error('Error al llamar swal:', error);
            // Fallback a confirm
            if (confirm('¿Desea eliminar este registro? Esta acción no se puede deshacer')) {
                $.ajax({
                    url: url,
                    type: 'GET',
                    dataType: 'json',
                    timeout: 30000,
                    success: function(response) {
                        alert(response.message || 'Eliminado con éxito');
                        location.reload();
                    },
                    error: function(xhr, status, error) {
                        alert('Ocurrió un error al eliminar');
                    }
                });
            }
        }
        
        return false;
    });
    
    console.log('Groups delete handler registered');
});
