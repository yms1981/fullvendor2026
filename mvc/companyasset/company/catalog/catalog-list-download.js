/**
 * Modal de descarga: preview en vivo + exportar PDF (misma vía que Catalog Studio).
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

    function setLoader(modal, on, text, pct) {
        var loader = modal.querySelector('[data-download-loader]');
        var label = modal.querySelector('[data-download-loader-text]');
        var bar = modal.querySelector('[data-download-loader-bar]');
        if (typeof pct === 'number' && !isNaN(pct)) {
            pct = Math.max(0, Math.min(100, Math.round(pct)));
            var tpl = modal.getAttribute('data-msg-generating-pct')
                || ((modal.getAttribute('data-msg-generating') || '') + ' {pct}%');
            text = String(tpl).replace(/\{pct\}/g, String(pct));
            if (bar) bar.style.width = pct + '%';
        }
        if (label && text) label.textContent = text;
        if (!loader) return;
        if (on) {
            loader.removeAttribute('hidden');
        } else {
            loader.setAttribute('hidden', 'hidden');
            if (bar) bar.style.width = '0%';
        }
    }

    function readOptions(modal) {
        var groupEl = modal.querySelector('select[name="group"]');
        var hideEl = modal.querySelector('input[name="hide_price"]');
        var group = '';
        if (groupEl) {
            group = groupEl.value || '';
            if ((!group || groupEl.selectedIndex < 0) && groupEl.options) {
                for (var i = 0; i < groupEl.options.length; i++) {
                    if (groupEl.options[i].selected && groupEl.options[i].value) {
                        group = groupEl.options[i].value;
                        break;
                    }
                }
            }
        }
        return {
            group: group,
            hide_price: hideEl && hideEl.checked ? '1' : '0'
        };
    }

    function urlToDataUri(url) {
        if (!url) return Promise.resolve('');
        if (String(url).indexOf('data:') === 0) return Promise.resolve(url);
        return fetch(url, { credentials: 'same-origin' })
            .then(function (r) {
                if (!r.ok) throw new Error('fetch');
                return r.blob();
            })
            .then(function (blob) {
                return new Promise(function (resolve, reject) {
                    var reader = new FileReader();
                    reader.onload = function () { resolve(String(reader.result || '')); };
                    reader.onerror = reject;
                    reader.readAsDataURL(blob);
                });
            })
            .catch(function () { return ''; });
    }

    /**
     * Convierte imágenes remotas del iframe a data-URI y devuelve HTML listo para Dompdf.
     */
    function prepareIframeHtmlForPdf(frame, onProgress) {
        var doc = frame && frame.contentDocument ? frame.contentDocument : null;
        if (!doc || !doc.documentElement) {
            return Promise.reject(new Error('empty'));
        }
        var products = Array.prototype.slice.call(doc.querySelectorAll('.product, .product--row'));
        products.forEach(function (el) {
            el.removeAttribute('data-fv-pdf-skip');
        });
        // Evitar base64 masivo en el POST (memoria PHP). Absolutizar URLs; el server embebe.
        var imgs = Array.prototype.slice.call(doc.querySelectorAll('img[src]'));
        var total = imgs.length || 1;
        var done = 0;
        var report = function () {
            if (typeof onProgress === 'function') {
                onProgress(5 + Math.round((done / total) * 45));
            }
        };
        report();
        var tasks = imgs.map(function (img) {
            var src = img.getAttribute('src') || '';
            if (!src || src.indexOf('data:') === 0) {
                done++;
                report();
                return Promise.resolve();
            }
            if (src.indexOf('blob:') === 0) {
                return urlToDataUri(src).then(function (dataUri) {
                    if (dataUri) img.setAttribute('src', dataUri);
                }).then(function () {
                    done++;
                    report();
                }, function () {
                    done++;
                    report();
                });
            }
            var abs = src;
            if (src.indexOf('//') === 0) {
                abs = window.location.protocol + src;
            } else if (src.charAt(0) === '/') {
                abs = window.location.origin + src;
            } else if (src.indexOf('http') !== 0) {
                abs = window.location.origin + '/' + src.replace(/^\.\//, '');
            }
            if (abs !== src) img.setAttribute('src', abs);
            done++;
            report();
            return Promise.resolve();
        });
        return Promise.all(tasks).then(function () {
            if (typeof onProgress === 'function') onProgress(55);
            var cloneRoot = doc.documentElement.cloneNode(true);
            cloneRoot.querySelectorAll('[data-fv-pdf-skip]').forEach(function (el) {
                if (el.parentNode) el.parentNode.removeChild(el);
            });
            cloneRoot.querySelectorAll('script, link[rel="stylesheet"]').forEach(function (el) {
                if (el.parentNode) el.parentNode.removeChild(el);
            });
            return '<!DOCTYPE html>\n' + cloneRoot.outerHTML;
        });
    }

    function renderDownloadPreview(modal) {
        if (!modal || !window.FvCatalogPreview) return;
        var frame = modal.querySelector('iframe[data-catalog-preview-frame]');
        var url = modal.getAttribute('data-preview-api') || '';
        var templatesBase = modal.getAttribute('data-templates-base') || '';
        var loading = modal.getAttribute('data-msg-loading') || '';
        var empty = modal.getAttribute('data-msg-empty') || '';
        var errorMsg = modal.getAttribute('data-msg-error') || '';
        if (!frame || !url) return;

        var opts = readOptions(modal);
        var qs = [];
        if (opts.group) qs.push('group_id=' + encodeURIComponent(opts.group));
        if (opts.hide_price === '1') qs.push('hide_price=1');
        var apiUrl = url + (qs.length ? ((url.indexOf('?') >= 0 ? '&' : '?') + qs.join('&')) : '');

        setStatus(modal, loading);
        setLoader(modal, false);
        frame.removeAttribute('srcdoc');
        frame.src = 'about:blank';

        fetch(apiUrl, {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
            .then(function (res) { return res.json(); })
            .then(function (resp) {
                if (!resp || !resp.success) {
                    throw new Error((resp && (resp.msg || resp.message)) || 'error');
                }
                var key = resp.template_key || 'classic-grid';
                return loadTemplate(templatesBase, key).then(function (html) {
                    return window.FvCatalogPreview.renderToIframe(frame, {
                        templateHtml: html,
                        catalogName: resp.catalog_name || modal.getAttribute('data-catalog-name') || '',
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

    function finishExportUi(modal, btn) {
        var $btn = $(btn);
        $btn.data('busy', false);
        $btn.prop('disabled', false).removeClass('is-busy');
        if ($btn.data('originalHtml') != null) {
            $btn.html($btn.data('originalHtml'));
        }
        setLoader(modal, false);
        setStatus(modal, '');
    }

    function downloadBlob(blob, fileName) {
        var url = window.URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = fileName;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        setTimeout(function () { window.URL.revokeObjectURL(url); }, 1500);
    }

    /**
     * Exporta el HTML del preview (rápido, misma apariencia). Fallback: endpoint download.
     */
    function exportPdf(modal, btn) {
        var exportUrl = modal.getAttribute('data-export-pdf-api') || '';
        var downloadUrl = modal.getAttribute('data-download-api') || '';
        var opts = readOptions(modal);
        var generating = modal.getAttribute('data-msg-generating') || '...';
        var pdfError = modal.getAttribute('data-msg-pdf-error') || 'Error';
        var emptyMsg = modal.getAttribute('data-msg-empty') || pdfError;
        var catalogName = modal.getAttribute('data-catalog-name') || 'catalog';
        var frame = modal.querySelector('iframe[data-catalog-preview-frame]');
        var $btn = $(btn);

        if ($btn.data('busy')) return;
        $btn.data('busy', true);
        if ($btn.data('originalHtml') == null) {
            $btn.data('originalHtml', $btn.html());
        }
        $btn.prop('disabled', true).addClass('is-busy');
        setLoader(modal, true, generating, 2);
        setStatus(modal, generating);

        var safeName = String(catalogName).replace(/[^\w\-]+/g, '_') + '.pdf';
        var pulseAt = 78;
        var pulseTimer = null;

        function exportViaServerRebuild() {
            if (!downloadUrl || !window.jQuery) {
                return Promise.reject(new Error('no download url'));
            }
            return new Promise(function (resolve, reject) {
                window.jQuery.ajax({
                    url: downloadUrl,
                    method: 'POST',
                    data: {
                        group: opts.group,
                        hide_price: opts.hide_price,
                        output: 'pdf'
                    },
                    xhrFields: { responseType: 'blob' },
                    timeout: 300000,
                    xhr: function () {
                        var xhr = new window.XMLHttpRequest();
                        xhr.upload.addEventListener('progress', function (e) {
                            if (e.lengthComputable) {
                                setLoader(modal, true, null, 58 + Math.round((e.loaded / Math.max(1, e.total)) * 18));
                            }
                        });
                        return xhr;
                    }
                }).done(function (blob, textStatus, xhr) {
                    var contentType = xhr.getResponseHeader('Content-Type') || 'application/pdf';
                    if (blob && blob.size > 0 && (contentType.indexOf('pdf') !== -1 || (blob.type && blob.type.indexOf('pdf') !== -1))) {
                        resolve(blob);
                    } else {
                        reject(new Error(pdfError));
                    }
                }).fail(function (xhr) {
                    var err = pdfError;
                    if (xhr.statusText === 'timeout') {
                        reject(new Error(pdfError));
                        return;
                    }
                    if (xhr.responseType === 'blob' && xhr.response && xhr.response.size > 0) {
                        var reader = new FileReader();
                        reader.onload = function () {
                            try {
                                var j = JSON.parse(reader.result);
                                if (j && j.msg) err = j.msg;
                            } catch (e) { /* ignore */ }
                            reject(new Error(err));
                        };
                        reader.onerror = function () { reject(new Error(err)); };
                        reader.readAsText(xhr.response);
                    } else {
                        try {
                            err = (xhr.responseJSON && xhr.responseJSON.msg) ? xhr.responseJSON.msg : err;
                        } catch (e) { /* ignore */ }
                        reject(new Error(err));
                    }
                });
            });
        }

        var chain;
        if (exportUrl && frame) {
            var doc = frame.contentDocument;
            var hasProducts = doc && doc.querySelector && !!doc.querySelector('.product, .product--row');
            if (!hasProducts) {
                finishExportUi(modal, btn);
                if (typeof alert_func === 'function') alert_func([emptyMsg, 'error']);
                else window.alert(emptyMsg);
                return;
            }
            chain = prepareIframeHtmlForPdf(frame, function (pct) {
                setLoader(modal, true, null, pct);
            }).then(function (html) {
                setLoader(modal, true, null, 58);
                var fd = new FormData();
                fd.append('html', html);
                fd.append('catalog_name', catalogName);
                pulseTimer = setInterval(function () {
                    pulseAt = Math.min(87, pulseAt + 1);
                    setLoader(modal, true, null, pulseAt);
                }, 900);
                return new Promise(function (resolve, reject) {
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', exportUrl);
                    xhr.responseType = 'blob';
                    xhr.withCredentials = true;
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                    xhr.upload.onprogress = function (e) {
                        if (e.lengthComputable) {
                            setLoader(modal, true, null, 58 + Math.round((e.loaded / Math.max(1, e.total)) * 18));
                        }
                    };
                    xhr.onload = function () {
                        var ctype = String(xhr.getResponseHeader('Content-Type') || '');
                        if (xhr.status >= 200 && xhr.status < 300
                            && (ctype.indexOf('pdf') !== -1 || ctype.indexOf('octet-stream') !== -1)) {
                            resolve(xhr.response);
                            return;
                        }
                        var reader = new FileReader();
                        reader.onload = function () {
                            var msg = pdfError;
                            try {
                                var json = JSON.parse(String(reader.result || ''));
                                if (json && json.msg) msg = json.msg;
                            } catch (e) { /* ignore */ }
                            reject(new Error(msg));
                        };
                        reader.onerror = function () { reject(new Error(pdfError)); };
                        if (xhr.response && xhr.response.size) reader.readAsText(xhr.response);
                        else reject(new Error(pdfError));
                    };
                    xhr.onerror = function () { reject(new Error(pdfError)); };
                    xhr.send(fd);
                });
            });
        } else {
            chain = exportViaServerRebuild();
        }

        chain
            .catch(function () {
                if (downloadUrl) {
                    setLoader(modal, true, null, 60);
                    return exportViaServerRebuild();
                }
                throw new Error(pdfError);
            })
            .then(function (blob) {
                if (pulseTimer) {
                    clearInterval(pulseTimer);
                    pulseTimer = null;
                }
                setLoader(modal, true, null, 96);
                if (!(blob instanceof Blob) || blob.size <= 0) {
                    throw new Error(pdfError);
                }
                downloadBlob(blob, safeName);
                setLoader(modal, true, null, 100);
            })
            .catch(function (err) {
                if (pulseTimer) {
                    clearInterval(pulseTimer);
                    pulseTimer = null;
                }
                var msg = (err && err.message) ? err.message : pdfError;
                if (typeof alert_func === 'function') alert_func([msg, 'error']);
                else window.alert(msg);
            })
            .then(function () {
                finishExportUi(modal, btn);
            });
    }

    function bindModal(modal) {
        if (!modal || modal.getAttribute('data-download-bound') === '1') return;
        modal.setAttribute('data-download-bound', '1');

        var refresh = function () { renderDownloadPreview(modal); };
        var onShown = function () {
            var group = modal.querySelector('select[name="group"]');
            if (group && window.jQuery && window.jQuery.fn && window.jQuery.fn.selectpicker) {
                try {
                    window.jQuery(group).selectpicker('refresh');
                } catch (e) { /* ignore */ }
            }
            refresh();
        };
        if (window.jQuery) {
            window.jQuery(modal).on('shown.bs.modal', onShown);
        } else {
            modal.addEventListener('shown.bs.modal', onShown);
        }

        var group = modal.querySelector('select[name="group"]');
        var hide = modal.querySelector('input[name="hide_price"]');
        if (group) {
            $(group).on('changed.bs.select change', refresh);
        }
        if (hide) {
            hide.addEventListener('change', refresh);
        }

        var exportBtn = modal.querySelector('[data-download-export]');
        if (exportBtn) {
            exportBtn.addEventListener('click', function (e) {
                e.preventDefault();
                exportPdf(modal, exportBtn);
            });
        }
    }

    function init() {
        document.querySelectorAll('.fv-catalog-download-modal').forEach(bindModal);
    }

    window.descargar_conpdfMake = function (event, catalogId) {
        if (event) event.preventDefault();
        var modal = document.getElementById('downloadModal_' + catalogId);
        if (!modal) return;
        var btn = modal.querySelector('[data-download-export]');
        if (btn) exportPdf(modal, btn);
    };

    if (window.jQuery) {
        window.jQuery(init);
    } else {
        document.addEventListener('DOMContentLoaded', init);
    }
})(window.jQuery || null);
