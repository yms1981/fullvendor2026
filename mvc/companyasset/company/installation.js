/**
 * FullVendor PWA: limpia SW con scope roto, registra uno correcto y muestra Instalar.
 */
(function (window, document) {
    'use strict';

    var deferredPrompt = null;
    var registering = null;

    function isStandalone() {
        return window.matchMedia('(display-mode: standalone)').matches
            || window.navigator.standalone === true;
    }

    function isSecureContext() {
        if (window.isSecureContext === true) {
            return true;
        }
        var host = String(window.location.hostname || '').toLowerCase();
        return host === 'localhost' || host === '127.0.0.1' || host === '[::1]';
    }

    /** Base de la app: origin actual + path de UrlBase (ej. http://localhost/mvc/). */
    function appBase() {
        var path = '/';
        if (typeof window.UrlBase === 'string' && window.UrlBase) {
            try {
                path = new URL(window.UrlBase, window.location.href).pathname || '/';
            } catch (e) {
                path = '/';
            }
        }
        if (path.charAt(path.length - 1) !== '/') {
            path += '/';
        }
        return window.location.origin + path;
    }

    function getInstallButton() {
        return document.getElementById('install-button');
    }

    function showInstallButton(visible) {
        var btn = getInstallButton();
        if (!btn) {
            return;
        }
        if (visible) {
            btn.hidden = false;
            btn.style.display = 'inline-flex';
            btn.classList.add('animate__animated', 'animate__rubberBand');
            btn.setAttribute('aria-hidden', 'false');
        } else {
            btn.hidden = true;
            btn.style.display = 'none';
            btn.classList.remove('animate__animated', 'animate__rubberBand');
            btn.setAttribute('aria-hidden', 'true');
        }
    }

    /**
     * Quita registros viejos con scope incorrecto (p. ej. /mvc/company/orders/view/).
     */
    function cleanupStaleServiceWorkers(desiredScope) {
        if (!('serviceWorker' in navigator)) {
            return Promise.resolve();
        }
        var desired = String(desiredScope || '').replace(/\/?$/, '/');
        return navigator.serviceWorker.getRegistrations().then(function (regs) {
            return Promise.all(regs.map(function (reg) {
                var scope = String(reg.scope || '').replace(/\/?$/, '/');
                if (scope !== desired) {
                    return reg.unregister().catch(function () {
                        return false;
                    });
                }
                return Promise.resolve(false);
            }));
        });
    }

    function registerServiceWorker() {
        if (!('serviceWorker' in navigator)) {
            return Promise.resolve(null);
        }
        if (!isSecureContext()) {
            console.warn('PWA: contexto no seguro (usa http://localhost o HTTPS válido)');
            return Promise.resolve(null);
        }
        if (registering) {
            return registering;
        }

        var base = appBase();
        var swUrl = base + 'sw.js';
        var scope = base;

        registering = cleanupStaleServiceWorkers(scope)
            .then(function () {
                return navigator.serviceWorker.register(swUrl, { scope: scope });
            })
            .then(function (reg) {
                if (reg && typeof reg.update === 'function') {
                    try { reg.update(); } catch (e) {}
                }
                return reg;
            })
            .catch(function (err) {
                console.warn('PWA: service worker registration failed', err);
                return null;
            })
            .finally(function () {
                registering = null;
            });

        return registering;
    }

    function bindInstallButton() {
        var btn = getInstallButton();
        if (!btn || btn.getAttribute('data-fv-pwa-bound') === '1') {
            return;
        }
        btn.setAttribute('data-fv-pwa-bound', '1');
        btn.addEventListener('click', function (ev) {
            ev.preventDefault();
            var promptEvent = deferredPrompt || window.__fvPwaDeferredPrompt || null;
            if (!promptEvent || typeof promptEvent.prompt !== 'function') {
                return;
            }
            promptEvent.prompt();
            Promise.resolve(promptEvent.userChoice).then(function () {
                deferredPrompt = null;
                window.__fvPwaDeferredPrompt = null;
                showInstallButton(false);
            }).catch(function () {
                deferredPrompt = null;
                window.__fvPwaDeferredPrompt = null;
                showInstallButton(false);
            });
        });
    }

    function onInstallable(promptEvent) {
        if (promptEvent) {
            deferredPrompt = promptEvent;
            window.__fvPwaDeferredPrompt = promptEvent;
        } else if (window.__fvPwaDeferredPrompt) {
            deferredPrompt = window.__fvPwaDeferredPrompt;
        }
        if (!deferredPrompt) {
            return;
        }
        showInstallButton(true);
        bindInstallButton();
    }

    function init() {
        if (isStandalone()) {
            showInstallButton(false);
            document.documentElement.classList.add('fv-pwa-standalone');
            registerServiceWorker();
            return;
        }

        showInstallButton(false);
        bindInstallButton();
        registerServiceWorker();

        if (window.__fvPwaDeferredPrompt) {
            onInstallable(window.__fvPwaDeferredPrompt);
        }

        window.addEventListener('beforeinstallprompt', function (e) {
            e.preventDefault();
            onInstallable(e);
        });

        window.addEventListener('fv-pwa-installable', function () {
            onInstallable(window.__fvPwaDeferredPrompt || null);
        });

        window.addEventListener('appinstalled', function () {
            deferredPrompt = null;
            window.__fvPwaDeferredPrompt = null;
            showInstallButton(false);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})(window, document);
