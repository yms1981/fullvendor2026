$(function () {
    function appendCustomerAddressFieldsToFormData(form, formData) {
        if (!form || !formData) {
            return;
        }
        var names = [
            'commercial_address_id',
            'commercial_address',
            'commercial_delivery_address',
            'commercial_country',
            'commercial_state',
            'commercial_state_id',
            'commercial_city',
            'commercial_city_id',
            'commercial_zip_code',
            'commercial_zone',
            'dispatch_address_id',
            'dispatch_delivery_address',
            'dispatch_address',
            'dispatch_country',
            'dispatch_state',
            'dispatch_state_id',
            'dispatch_city',
            'dispatch_city_id',
            'dispatch_zip_code',
            'dispatch_zone',
            'dispatch_shipping_notes'
        ];
        names.forEach(function (name) {
            var el = form.querySelector('[name="' + name + '"]');
            if (el) {
                formData.set(name, el.value || '');
            }
        });
    }

    $(document).on('submit', '#form_edit', function(e) {
        e.preventDefault();
        
        try {
            const form = document.getElementById('form_edit');
            
            if (!form) {
                console.error('Formulario #form_edit no encontrado');
                swal({
                    title: (window.fvUiText && window.fvUiText('error_occurred')) || '',
                    text: 'No se pudo encontrar el formulario.',
                    icon: 'error'
                });
                return false;
            }
            
            const formData = new FormData(form);
            const phoneEl = form.querySelector('#customer_phone');
            const cellEl = form.querySelector('#customer_cell_phone');
            if (phoneEl) {
                formData.set('phone', (phoneEl.value || '').replace(/\D/g, ''));
            }
            if (cellEl) {
                formData.set('cell_phone', (cellEl.value || '').replace(/\D/g, ''));
            }
            const warehouseStreetNum = form.querySelector('#warehouse_street_number');
            if (warehouseStreetNum) {
                formData.set('street_number', (warehouseStreetNum.value || '').replace(/\D/g, ''));
            }

            if (form.querySelector('[name="commercial_state"]') || form.querySelector('[name="dispatch_state"]')) {
                appendCustomerAddressFieldsToFormData(form, formData);
            }

            // Log para depuración
            console.log('Enviando datos del formulario:');
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }

        $.ajax({
            url: form.getAttribute('action'),
            type: 'post',
            dataType: 'json',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                let title = response.message ?? 'Actualización exitosa!';
                swal({
                    title: title,
                    icon: 'success'
                }).then(() => {
                    window.history.go(-1);
                });
            },
            error: function(xhr, status, error) {
                console.error('Error en actualización:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    response: xhr.responseJSON,
                    error: error
                });
                
                let title = 'Ocurrió un error';
                let errorsMessages = null;
                
                if (xhr.responseJSON) {
                    title = xhr.responseJSON.message || title;
                    const errors = xhr.responseJSON.errors;
                    
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
                } else if (xhr.status === 400) {
                    title = 'Error de validación';
                    errorsMessages = 'Por favor, verifica que todos los campos requeridos estén completos.';
                } else if (xhr.status === 0) {
                    title = 'Error de conexión';
                    errorsMessages = 'No se pudo conectar con el servidor. Verifica tu conexión.';
                } else {
                    title = `Error ${xhr.status}`;
                    errorsMessages = xhr.statusText || 'Ocurrió un error desconocido';
                }

                swal({
                    title: title,
                    text: errorsMessages,
                    icon: 'error'
                });
            }

        });
        } catch (error) {
            console.error('Error al procesar el formulario:', error);
            if (typeof swal !== 'undefined') {
                swal({
                    title: (window.fvUiText && window.fvUiText('error_occurred')) || '',
                    text: 'Ocurrió un error al procesar el formulario: ' + error.message,
                    icon: 'error'
                });
            } else {
                alert('Error al procesar el formulario: ' + error.message);
            }
        }
    });
});
