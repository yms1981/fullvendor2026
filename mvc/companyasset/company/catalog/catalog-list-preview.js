/**
 * Preview en vivo desde el listado de catálogos (botón ojo → modal).
 */
(function ($) {
    'use strict';

    var templateCache = {};

    function loadTemplate(base, key) {
        key = key || 'classic-grid';
        if (templateCache[key]) {
            return Promise.resolve(templateCache[key]);
        }
        var url = String(base || '').replace(/\/?$/, '/') + encodeURIComponent(key) + '.html';
        return fetch(url, { credentials: 'same-origin' })
            .then(function (res) {
                if (!res.ok) throw new Error('template missing');
                return res.text();
            })
            .then(function (html) {
                templateCache[key] = html;
                return html;
            });
    }

    function setStatus(modal, text) {
        var el = modal.querySelector('[data-preview-status]');
        if (el) el.textContent = text || '';
    }

    function renderModalPreview(modal) {
        if (!modal || !window.FvCatalogPreview) return;
        var frame = modal.querySelector('iframe[data-catalog-preview-frame]');
        var url = modal.getAttribute('data-preview-api') || '';
        var templatesBase = modal.getAttribute('data-templates-base') || '';
        var loading = modal.getAttribute('data-msg-loading') || '';
        var empty = modal.getAttribute('data-msg-empty') || '';
        var errorMsg = modal.getAttribute('data-msg-error') || '';
        if (!frame || !url) return;

        setStatus(modal, loading);
        frame.removeAttribute('srcdoc');
        frame.src = 'about:blank';

        fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
            .then(function (res) { return res.json(); })
            .then(function (resp) {
                if (!resp || !resp.success) {
                    throw new Error((resp && (resp.msg || resp.message)) || 'error');
                }
                var key = (resp.template_key || 'classic-grid');
                return loadTemplate(templatesBase, key).then(function (html) {
                    return window.FvCatalogPreview.renderToIframe(frame, {
                        templateHtml: html,
                        catalogName: resp.catalog_name || '',
                        companyName: resp.company_name || 'FullVendor',
                        message: resp.message || '',
                        logoUrl: resp.logo_url || '',
                        showPrices: resp.show_prices !== false,
                        sections: resp.sections || [],
                        emptyMsg: empty,
                        theme: resp.theme || {}
                    });
                });
            })
            .then(function () {
                setStatus(modal, '');
            })
            .catch(function () {
                setStatus(modal, errorMsg);
                frame.srcdoc = '<p style="padding:2rem;font-family:sans-serif;color:#64748b;">' +
                    (errorMsg || 'Preview unavailable') + '</p>';
            });
    }

    function bindModal(modal) {
        if (!modal || modal.getAttribute('data-preview-bound') === '1') return;
        modal.setAttribute('data-preview-bound', '1');
        var handler = function () { renderModalPreview(modal); };
        if (window.jQuery) {
            window.jQuery(modal).on('shown.bs.modal', handler);
        } else {
            modal.addEventListener('shown.bs.modal', handler);
        }
    }

    function init() {
        document.querySelectorAll('.previewModal[data-preview-api]').forEach(bindModal);
    }

    if (window.jQuery) {
        window.jQuery(init);
    } else {
        document.addEventListener('DOMContentLoaded', init);
    }
})(window.jQuery || null);
