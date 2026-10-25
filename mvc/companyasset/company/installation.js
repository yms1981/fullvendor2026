/**
 * FullVendor PWA: registra service worker y muestra el botón Instalar.
 */
(function (window, document) {
    'use strict';

    var deferredPrompt = null;

    function isStandalone() {
        return window.matchMedia('(display-mode: standalone)').matches
            || window.navigator.standalone === true;
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

    function registerServiceWorker() {
        if (!('serviceWorker' in navigator)) {
            return Promise.resolve(null);
        }
        var swUrl = (typeof window.UrlBase === 'string' && window.UrlBase)
            ? String(window.UrlBase).replace(/\/?$/, '/') + 'sw.js'
            : '/sw.js';

        return navigator.serviceWorker.register(swUrl, { scope: './' })
            .then(function (reg) {
                return reg;
            })
            .catch(function (err) {
                console.warn('PWA: service worker registration failed', err);
                return null;
            });
    }

    function bindInstallButton() {
        var btn = getInstallButton();
        if (!btn || btn.getAttribute('data-fv-pwa-bound') === '1') {
            return;
        }
        btn.setAttribute('data-fv-pwa-bound', '1');
        btn.addEventListener('click', function (ev) {
            ev.preventDefault();
            if (!deferredPrompt) {
                return;
            }
            deferredPrompt.prompt();
            Promise.resolve(deferredPrompt.userChoice).then(function (choice) {
                deferredPrompt = null;
                showInstallButton(false);
                if (choice && choice.outcome === 'accepted') {
                    // La app queda instalada; el display-mode cambia al reabrir.
                }
            }).catch(function () {
                deferredPrompt = null;
                showInstallButton(false);
            });
        });
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

        window.addEventListener('beforeinstallprompt', function (e) {
            e.preventDefault();
            deferredPrompt = e;
            showInstallButton(true);
            bindInstallButton();
        });

        window.addEventListener('appinstalled', function () {
            deferredPrompt = null;
            showInstallButton(false);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})(window, document);
