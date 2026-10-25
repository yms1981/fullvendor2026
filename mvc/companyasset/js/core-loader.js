/**
 * Core Loader - Sistema centralizado para carga de dependencias JavaScript
 * 
 * Este archivo centraliza todas las funciones relacionadas con:
 * - Verificación y carga de jQuery
 * - Carga de scripts dependientes
 * - Inicialización de variables globales
 * 
 * @version 1.0.0
 * @date 2026-01-13
 */

(function(window) {
    'use strict';

    // ============================================================================
    // CONFIGURACIÓN
    // ============================================================================
    
    var CONFIG = {
        jQueryUrl: null, // Se establecerá dinámicamente desde PHP
        maxAttempts: 100, // 10 segundos máximo
        checkInterval: 100 // ms entre intentos
    };

    // ============================================================================
    // ESTADO GLOBAL
    // ============================================================================
    
    var jQueryLoaded = false;
    var jQueryCallbacks = [];
    var scriptsLoaded = {};

    // ============================================================================
    // FUNCIONES PRIVADAS
    // ============================================================================

    /**
     * Verifica si jQuery está disponible
     */
    function isJQueryAvailable() {
        return typeof jQuery !== 'undefined' && 
               typeof window.jQuery !== 'undefined' && 
               typeof jQuery === 'function';
    }

    /**
     * Asegura que $ esté disponible como alias de jQuery
     */
    function ensureDollarAlias() {
        if (typeof window.$ === 'undefined' && isJQueryAvailable()) {
            window.$ = jQuery;
        }
    }

    /**
     * Carga jQuery dinámicamente como fallback
     */
    function loadJQueryFallback(callback) {
        if (!CONFIG.jQueryUrl) {
            console.error('CoreLoader: jQuery URL no configurada');
            callback();
            return;
        }

        var script = document.createElement('script');
        script.src = CONFIG.jQueryUrl;
        script.async = false;
        
        script.onload = function() {
            if (isJQueryAvailable()) {
                ensureDollarAlias();
                jQueryLoaded = true;
                console.log('CoreLoader: jQuery cargado desde fallback');
                callback();
            } else {
                console.error('CoreLoader: jQuery no se pudo cargar desde fallback');
                callback();
            }
        };
        
        script.onerror = function() {
            console.error('CoreLoader: Error al cargar jQuery desde fallback');
            callback();
        };
        
        document.head.appendChild(script);
    }

    /**
     * Ejecuta todos los callbacks pendientes de jQuery
     */
    function executeJQueryCallbacks() {
        while (jQueryCallbacks.length > 0) {
            var callback = jQueryCallbacks.shift();
            try {
                callback();
            } catch (e) {
                console.error('CoreLoader: Error ejecutando callback de jQuery:', e);
            }
        }
    }

    // ============================================================================
    // API PÚBLICA
    // ============================================================================

    /**
     * Configura la URL de jQuery para el fallback
     */
    window.CoreLoader = window.CoreLoader || {};
    window.CoreLoader.config = function(config) {
        if (config.jQueryUrl) {
            CONFIG.jQueryUrl = config.jQueryUrl;
        }
        if (config.maxAttempts) {
            CONFIG.maxAttempts = config.maxAttempts;
        }
        if (config.checkInterval) {
            CONFIG.checkInterval = config.checkInterval;
        }
    };

    /**
     * Espera a que jQuery esté disponible y ejecuta el callback
     * 
     * @param {Function} callback - Función a ejecutar cuando jQuery esté disponible
     * @param {number} maxAttempts - Número máximo de intentos (opcional)
     */
    window.CoreLoader.waitForJQuery = function(callback, maxAttempts) {
        if (typeof callback !== 'function') {
            console.error('CoreLoader.waitForJQuery: callback debe ser una función');
            return;
        }

        maxAttempts = maxAttempts || CONFIG.maxAttempts;

        // Si jQuery ya está disponible, ejecutar inmediatamente
        if (isJQueryAvailable()) {
            ensureDollarAlias();
            jQueryLoaded = true;
            callback();
            return;
        }

        // Si ya se está esperando jQuery, agregar a la cola
        if (jQueryCallbacks.length > 0) {
            jQueryCallbacks.push(callback);
            return;
        }

        // Agregar callback a la cola
        jQueryCallbacks.push(callback);

        // Intentar verificar jQuery
        var attempts = 0;
        
        function check() {
            attempts++;
            
            if (isJQueryAvailable()) {
                ensureDollarAlias();
                jQueryLoaded = true;
                console.log('CoreLoader: jQuery detectado después de ' + attempts + ' intentos');
                executeJQueryCallbacks();
            } else if (attempts < maxAttempts) {
                setTimeout(check, CONFIG.checkInterval);
            } else {
                console.warn('CoreLoader: jQuery no disponible después de ' + (maxAttempts * CONFIG.checkInterval) + 'ms, intentando fallback...');
                loadJQueryFallback(function() {
                    executeJQueryCallbacks();
                });
            }
        }
        
        check();
    };

    /**
     * Asegura que jQuery esté disponible (versión síncrona)
     * Retorna true si jQuery está disponible, false en caso contrario
     */
    window.CoreLoader.ensureJQuery = function() {
        if (isJQueryAvailable()) {
            ensureDollarAlias();
            return true;
        }
        return false;
    };

    /**
     * Carga un script de forma secuencial
     * 
     * @param {string} src - URL del script
     * @param {Function} callback - Callback a ejecutar cuando el script se cargue
     */
    window.CoreLoader.loadScript = function(src, callback) {
        if (!src) {
            console.error('CoreLoader.loadScript: src es requerido');
            if (callback) callback();
            return;
        }

        // Normalizar la URL para evitar duplicados (remover query strings diferentes)
        var normalizedSrc = src.split('?')[0];
        
        // Verificar si el script ya fue cargado (por URL normalizada o exacta)
        if (scriptsLoaded[src] || scriptsLoaded[normalizedSrc]) {
            if (callback) callback();
            return;
        }
        
        // Verificar si ya existe un script con esa src en el DOM
        var existingScript = document.querySelector('script[src="' + src + '"]') || 
                            document.querySelector('script[src*="' + normalizedSrc + '"]');
        if (existingScript) {
            scriptsLoaded[src] = true;
            scriptsLoaded[normalizedSrc] = true;
            if (callback) callback();
            return;
        }

        var script = document.createElement('script');
        script.type = 'text/javascript';
        script.src = src;
        script.async = false;

        script.onload = function() {
            scriptsLoaded[src] = true;
            scriptsLoaded[normalizedSrc] = true;
            if (callback) callback();
        };

        script.onerror = function() {
            console.error('CoreLoader: Error al cargar script: ' + src);
            // Marcar como intentado para evitar loops infinitos
            scriptsLoaded[src] = true;
            scriptsLoaded[normalizedSrc] = true;
            if (callback) callback();
        };

        document.head.appendChild(script);
    };

    /**
     * Carga múltiples scripts de forma secuencial
     * 
     * @param {Array<string>} scripts - Array de URLs de scripts
     * @param {Function} callback - Callback a ejecutar cuando todos los scripts se carguen
     */
    window.CoreLoader.loadScripts = function(scripts, callback) {
        if (!Array.isArray(scripts) || scripts.length === 0) {
            if (callback) callback();
            return;
        }

        var index = 0;

        function loadNext() {
            if (index >= scripts.length) {
                if (callback) callback();
                return;
            }

            var scriptSrc = scripts[index];
            if (!scriptSrc || scriptSrc === 'undefined' || scriptSrc === 'null') {
                console.warn('CoreLoader: Script undefined en índice ' + index + ', saltando...');
                index++;
                loadNext();
                return;
            }

            window.CoreLoader.loadScript(scriptSrc, function() {
                index++;
                loadNext();
            });
        }

        loadNext();
    };

    /**
     * Inicializa jQuery y marca que está listo
     * Debe ser llamado después de que jQuery se cargue en el HTML
     */
    window.CoreLoader.initJQuery = function() {
        if (isJQueryAvailable()) {
            ensureDollarAlias();
            jQueryLoaded = true;
            window.jQueryReady = true;
            
            // Disparar evento personalizado
            if (typeof document.createEvent !== 'undefined') {
                var event = document.createEvent('Event');
                event.initEvent('jqueryReady', true, true);
                window.dispatchEvent(event);
            }
            
            console.log('CoreLoader: jQuery inicializado y listo');
            executeJQueryCallbacks();
        } else {
            console.warn('CoreLoader.initJQuery: jQuery no está disponible');
        }
    };

    // Auto-inicializar si jQuery ya está disponible
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(window.CoreLoader.initJQuery, 100);
        });
    } else {
        setTimeout(window.CoreLoader.initJQuery, 100);
    }

    // Timeout de seguridad
    setTimeout(function() {
        if (!jQueryLoaded && !isJQueryAvailable()) {
            console.warn('CoreLoader: jQuery no detectado después de 3 segundos');
        } else {
            window.CoreLoader.initJQuery();
        }
    }, 3000);

})(window);

