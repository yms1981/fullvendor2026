// Esperar a que jQuery esté disponible antes de ejecutar
(function() {
    function waitForJQuery(callback, maxAttempts) {
        maxAttempts = maxAttempts || 100; // Aumentar a 100 intentos (10 segundos)
        var attempts = 0;
        
        function check() {
            attempts++;
            // Verificar solo jQuery, $ puede no estar disponible inmediatamente
            if (typeof jQuery !== 'undefined' && typeof window.jQuery !== 'undefined') {
                // Asegurar que $ también esté disponible
                if (typeof window.$ === 'undefined') {
                    window.$ = jQuery;
                }
                callback();
            } else if (attempts < maxAttempts) {
                setTimeout(check, 100);
            } else {
                console.error('jQuery not loaded after ' + (maxAttempts * 100) + 'ms');
                // Continuar de todos modos si jQuery no se carga
                callback();
            }
        }
        check();
    }
    
    waitForJQuery(function() {
        // Verificar nuevamente que jQuery esté disponible
        if (typeof jQuery === 'undefined') {
            console.warn('jQuery no está disponible, saltando inicialización');
            return;
        }
        
        // Ahora jQuery está disponible, usar $ de forma segura
        var $ = jQuery;
        
        // $('.modal-send-catalog').on('show.bs.modal', function (event) {
        //     console.log("abriendo modal")
        //     $(this).modal('hide')
        //     const id_modal = $(this).attr('id')
        //     $(`#${id_modal}`).modal('hide')
        // })

        $(document).ready(function() {
            // Abrir modal Send Catalog
            $(document).on('click', '.btn-send-catalog', function (event) {
                if ($(this).hasClass('disabled') || $(this).prop('disabled')) {
                    event.preventDefault();
                    event.stopPropagation();
                    var msg_error = $(this).data('msg-error');
                    if (msg_error && typeof alert_func === 'function') {
                        alert_func([msg_error, 'error']);
                    } else if (typeof swal !== 'undefined') {
                        swal({ title: '', text: msg_error || 'Error', icon: 'error' });
                    } else {
                        alert(msg_error || 'Error');
                    }
                    return false;
                }
                var id_modal = $(this).data('modal');
                var msg_error = $(this).data('msg-error');
                if (msg_error) {
                    if (typeof alert_func === 'function') alert_func([msg_error, 'error']);
                    else if (typeof swal !== 'undefined') swal({ title: '', text: msg_error, icon: 'error' });
                    else alert(msg_error);
                    return false;
                }
                $(id_modal).modal('show');
            });

            // Catalog Text y Email Text son opcionales (no validar vacío)

            // Envío por AJAX del formulario Send Catalog (evitar POST completo y mostrar resultado en modal)
            $(document).on('submit', '.form_send_catalog_modal', function(e) {
                e.preventDefault();
                var $form = $(this);
                var $modal = $form.closest('.modal');
                var $btn = $modal.find('.btn-submit-send-catalog');
                var url = $form.attr('action');
                var data = $form.serialize();
                // Debug: qué se envía (quitar en producción)
                var formDataObj = {};
                $.each($form.serializeArray(), function() {
                    if (formDataObj[this.name] !== undefined) {
                        if (!Array.isArray(formDataObj[this.name])) formDataObj[this.name] = [formDataObj[this.name]];
                        formDataObj[this.name].push(this.value);
                    } else {
                        formDataObj[this.name] = this.value;
                    }
                });
                console.log('[Send Catalog] URL:', url);
                console.log('[Send Catalog] Serialized:', data);
                console.log('[Send Catalog] Form data (object):', formDataObj);
                if ($btn.length) {
                    $btn.prop('disabled', true).data('original-html', $btn.html());
                    $btn.html('<span class="spinner-border spinner-border-sm" role="status"></span>');
                }
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: data,
                    dataType: 'json',
                    success: function(res) {
                        if ($btn.length) {
                            $btn.prop('disabled', false).html($btn.data('original-html') || 'Send Catalog');
                        }
                        var msg = (res && res.message) ? res.message : (res && res.msg) ? res.msg : 'Mail sent successfully!';
                        if ($modal.length) $modal.modal('hide');
                        if (typeof swal !== 'undefined') {
                            swal({ title: '', text: msg, icon: 'success' });
                        } else {
                            alert(msg);
                        }
                    },
                    error: function(xhr) {
                        if ($btn.length) {
                            $btn.prop('disabled', false).html($btn.data('original-html') || 'Send Catalog');
                        }
                        var msg = 'An error occurred.';
                        try {
                            if (xhr.responseJSON && (xhr.responseJSON.msg || xhr.responseJSON.message)) {
                                msg = xhr.responseJSON.msg || xhr.responseJSON.message;
                            } else if (xhr.responseText) {
                                var d = typeof xhr.responseText === 'string' && xhr.responseText.trim().indexOf('{') === 0 ? JSON.parse(xhr.responseText) : null;
                                if (d && (d.msg || d.message)) msg = d.msg || d.message;
                            }
                        } catch (err) {}
                        if (typeof swal !== 'undefined') {
                            swal({ title: '', text: msg, icon: 'error' });
                        } else {
                            alert(msg);
                        }
                    }
                });
                return false;
            });

            // Manejar botón de performance (abre modal de performance)
            $(document).on('click', '.btn-performance', function (event) {
                event.preventDefault();
                event.stopPropagation();
                var target = $(this).attr('data-target') || $(this).attr('data-bs-target') || $(this).data('target') || $(this).data('bs-target');
                if (!target) return false;
                var $modal = $(target);
                if ($modal.length === 0) return false;
                $modal.modal('show');
            });
        });
    });
})();