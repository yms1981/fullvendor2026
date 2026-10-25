/**
 * Navigation Loader - Muestra el loader cuando se hace click en enlaces de navegación
 * Compatible con todos los enlaces del navbar de company
 */
(function() {
    'use strict';
    
    // Esperar a que jQuery y AppLoader estén disponibles
    function initNavigationLoader() {
        if (typeof jQuery === 'undefined' || typeof AppLoader === 'undefined') {
            setTimeout(initNavigationLoader, 100);
            return;
        }
        
        var $ = jQuery;
        
        // Descargas (CSV/PDF/plantilla): no mostrar loader de navegación
        function isDownloadLink(href, $link) {
            if ($link.attr('download') !== undefined) {
                return true;
            }
            if ($link.hasClass('fv-config-crud-btn--export')
                || $link.hasClass('fv-config-crud-btn--pdf')
                || $link.hasClass('fv-config-crud-btn--template')) {
                return true;
            }
            if (!href) {
                return false;
            }
            return /\/import_template\/download\//i.test(href)
                || /\/(export|listPdf|listpdf)(\/|$|\?)/i.test(href)
                || /\.csv(\?|$|#)/i.test(href)
                || /\.xlsx(\?|$|#)/i.test(href);
        }

        // Función para verificar si un enlace es interno de company
        function isCompanyInternalLink(href) {
            if (!href || href === '#' || href === 'javascript:void(0)' || href.startsWith('javascript:')) {
                return false;
            }
            
            // Verificar si es un enlace interno de company
            var companyUrlPattern = /\/company\//;
            var baseUrl = window.location.origin;
            var fullUrl = href.startsWith('http') ? href : baseUrl + href;
            
            return companyUrlPattern.test(fullUrl) || href.startsWith('/company/');
        }
        
        // Mostrar loader al hacer click en enlaces de navegación
        $(document).on('click', 'a[href]', function(e) {
            var $link = $(this);
            var href = $link.attr('href');
            
            // Ignorar enlaces especiales / acciones AJAX (no navegan de página)
            if ($link.hasClass('dropdown-toggle') ||
                $link.hasClass('btn-submit-cart-order') ||
                $link.hasClass('btn-delete-cart') ||
                $link.attr('id') === 'btn_submit_order' ||
                $link.attr('data-toggle') === 'dropdown' ||
                $link.attr('data-ajax') === '1' ||
                $link.attr('target') === '_blank' ||
                href === '#' ||
                href === 'javascript:void(0)' ||
                href.startsWith('javascript:') ||
                isDownloadLink(href, $link) ||
                /\/cart\/convert_carttoorder\//i.test(href || '')) {
                return;
            }
            
            // Solo mostrar loader para enlaces internos de company
            if (isCompanyInternalLink(href)) {
                // Pequeño delay para asegurar que el click se procese
                setTimeout(function() {
                    if (AppLoader && typeof AppLoader.show === 'function') {
                        AppLoader.show('Cargando...');
                    }
                }, 50);
            }
        });
        
        // Ocultar loader cuando la página termine de cargar
        $(window).on('load', function() {
            if (AppLoader && typeof AppLoader.hide === 'function') {
                setTimeout(function() {
                    AppLoader.hide();
                }, 300);
            }
        });
        
        // También ocultar loader después de un timeout de seguridad
        setTimeout(function() {
            if (AppLoader && AppLoader.isVisible && AppLoader.isVisible()) {
                AppLoader.hide();
            }
        }, 2000);
        
        // Restaurar desde caché del navegador (botón Atrás tras descarga)
        window.addEventListener('pageshow', function() {
            if (AppLoader && typeof AppLoader.hide === 'function') {
                AppLoader.hide();
            }
        });
    }
    
    // Iniciar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initNavigationLoader);
    } else {
        initNavigationLoader();
    }
})();

