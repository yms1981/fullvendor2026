/**
 * Alerta genérica para mostrar mensajes de éxito al guardar
 * Usa traducciones pasadas desde PHP a través de window.saveLang o window.deleteLang
 */
(function() {
    'use strict';
    
    // Función para obtener las traducciones
    function getSaveTranslations() {
        const saveLang = window.saveLang || window.deleteLang || {};
        const uiLang = window.fvUiLang || {};
        const merged = Object.assign({}, saveLang, uiLang);

        const pick = function (key) {
            const value = merged[key];
            return (typeof value === 'string' && value.trim() !== '') ? value : '';
        };

        return {
            success_saved: pick('success_saved') || pick('success_deleted'),
            success_updated: pick('success_updated'),
            success_created: pick('success_created'),
            success_message: pick('success_message'),
            ok: pick('ok') || pick('cancelar'),
            close: pick('close') || pick('cancelar')
        };
    }
    
    // Función para mostrar alerta de éxito
    function showSaveSuccess(message, options) {
        options = options || {};
        const lang = getSaveTranslations();
        const customMessage = message || options.message || lang.success_saved;
        const customTitle = options.title || lang.success_message;
        const onClose = options.onClose || null;
        const reload = options.reload !== undefined ? options.reload : false;
        const redirect = options.redirect || null;
        
        if (typeof swal !== 'undefined') {
            swal({
                title: customTitle,
                text: customMessage,
                icon: 'success',
                button: {
                    text: lang.ok,
                    className: "btn-success"
                }
            }).then(() => {
                if (onClose) {
                    onClose();
                }
                if (reload) {
                    location.reload();
                } else if (redirect) {
                    window.location.href = redirect;
                }
            });
        } else {
            alert(customTitle + '\n\n' + customMessage);
            if (onClose) {
                onClose();
            }
            if (reload) {
                location.reload();
            } else if (redirect) {
                window.location.href = redirect;
            }
        }
    }
    
    // Función para mostrar alerta de éxito con opciones personalizadas
    function showSaveSuccessCustom(title, message, options) {
        options = options || {};
        options.title = title;
        options.message = message;
        showSaveSuccess(message, options);
    }
    
    // Inicializar cuando jQuery esté disponible
    if (typeof jQuery !== 'undefined') {
        // Listo para usar
    } else {
        // Esperar a que jQuery esté disponible
        if (typeof window.CoreLoader !== 'undefined' && CoreLoader.waitForJQuery) {
            CoreLoader.waitForJQuery(function() {
                // Listo para usar
            });
        }
    }
    
    // Exportar funciones para uso manual
    window.showSaveSuccess = showSaveSuccess;
    window.showSaveSuccessCustom = showSaveSuccessCustom;
    window.getSaveTranslations = getSaveTranslations;
})();

