// La función activar_tabla() está definida en footer.php
// Solo se ejecuta si existe la tabla #infoTable y DataTables está disponible
// NOTA: El componente CRUD usa paginación del servidor, no DataTables
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

$(document).ready(function() {
            // Verificar si la tabla #infoTable existe y NO es del componente CRUD
            // El componente CRUD tiene paginación del servidor, no necesita DataTables
            var $table = $('#infoTable');
            var isCrudComponent = $table.attr('data-fv-server-list') === '1' ||
                ($table.closest('.table-responsive').length > 0 &&
                 $table.closest('.card-body').length > 0 &&
                 $('.paginate').length > 0);
            
            // Solo inicializar DataTables si NO es componente CRUD
            if (!isCrudComponent && typeof activar_tabla !== 'undefined' && $table.length > 0) {
                // Verificar que DataTables esté disponible
                if (typeof $.fn.DataTable !== 'undefined') {
                    try {
                        activar_tabla();
                    } catch (e) {
                        console.warn('Error al inicializar DataTables:', e);
                    }
                } else {
                    console.warn('DataTables no está disponible. La tabla no se inicializará.');
                }
            } else if (isCrudComponent) {
                // Paginación servidor: scroll horizontal CSS, sin DataTables básico.
            }
            
            // Inicializar select2 si existe
            if (typeof $.fn.select2 !== 'undefined' && $(".js-example").length > 0) {
    $(".js-example").select2({
                    width: '100%'
                });
            }
        });
    });
})();