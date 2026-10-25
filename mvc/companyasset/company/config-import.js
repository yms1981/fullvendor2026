(function () {
    'use strict';

    var previewForm = document.getElementById('form_import_preview');
    var confirmForm = document.getElementById('form_import_confirm');
    var input = document.getElementById('fv_config_import_file');
    var label = document.querySelector('.fv-config-import__file-name');
    var dropzone = document.querySelector('.fv-config-import__dropzone');
    var lang = window.fvConfigImportLang || {};
    var progress = window.FvImportProgress;

    function t(key, fallback) {
        return lang[key] || fallback || '';
    }

    function asText(value) {
        if (value == null) {
            return '';
        }
        if (typeof value === 'string') {
            return value;
        }
        if (typeof value === 'number' || typeof value === 'boolean') {
            return String(value);
        }
        if (Array.isArray(value)) {
            return value.map(asText).filter(Boolean).join(' · ');
        }
        if (typeof value === 'object') {
            if (value.message != null) {
                return asText(value.message);
            }
            if (value.reason != null) {
                return asText(value.reason);
            }
            try {
                return JSON.stringify(value);
            } catch (e) {
                return '';
            }
        }
        return String(value);
    }

    function detailText(current, total, loaded, failed) {
        if (loaded !== undefined && failed !== undefined) {
            return progress.replaceTokens(t('importedDetail', ''), {
                current: progress.formatCount(current),
                total: progress.formatCount(total),
                loaded: progress.formatCount(loaded),
                failed: progress.formatCount(failed)
            });
        }

        return progress.replaceTokens(t('detail', ''), {
            current: progress.formatCount(current),
            total: progress.formatCount(total)
        });
    }

    function buildImportReportLines(data) {
        var lines = [];
        var rawMessage = asText(data && data.message);
        if (rawMessage) {
            rawMessage.split(/\r?\n/).forEach(function (line) {
                line = String(line).trim();
                if (line) {
                    lines.push(line);
                }
            });
        }

        var hasSummaryInMessage = lines.length > 1;
        if (!hasSummaryInMessage && data && Array.isArray(data.error_summary) && data.error_summary.length) {
            data.error_summary.slice(0, 10).forEach(function (item) {
                var count = item && (item.count != null ? item.count : 0);
                var reason = asText(item && (item.reason != null ? item.reason : item));
                if (reason) {
                    lines.push(count + ' × ' + reason);
                }
            });
        }

        if (!hasSummaryInMessage && data && Array.isArray(data.errors) && data.errors.length) {
            data.errors.slice(0, 12).forEach(function (err) {
                var lineNo = err && err.line != null ? err.line : '?';
                var sku = asText(err && err.sku);
                var msg = asText(err && (err.message != null ? err.message : err));
                lines.push('#' + lineNo + (sku ? ' ' + sku : '') + ': ' + msg);
            });
            var errorsTotal = parseInt(data.errors_total, 10) || data.errors.length;
            if (errorsTotal > 12) {
                lines.push('… +' + (errorsTotal - 12));
            }
        }

        return lines;
    }

    function showImportReport(data, redirectUrl) {
        var failedCount = parseInt(data && data.failed, 10) || 0;
        var loadedCount = parseInt(data && data.loaded, 10) || 0;
        var lines = buildImportReportLines(data);
        var title = lines[0] || (failedCount > 0 ? t('error', '') : t('complete', ''));
        var body = lines.length > 1 ? lines.slice(1).join('\n') : '';

        if (progress) {
            progress.hide();
        }

        var goNext = function () {
            if (failedCount > 0) {
                var errorsBtn = document.querySelector('.fv-config-import__stat-link[data-filter="errors"]');
                if (errorsBtn && typeof window.jQuery === 'function') {
                    window.jQuery(errorsBtn).trigger('click');
                    return;
                }
            }
            if (redirectUrl) {
                window.location.href = redirectUrl;
            }
        };

        if (typeof swal !== 'function') {
            window.alert(lines.join('\n') || title);
            goNext();
            return;
        }

        var contentNode = null;
        if (body) {
            contentNode = document.createElement('div');
            contentNode.className = 'fv-import-report';
            contentNode.style.cssText = 'max-height:340px;overflow:auto;text-align:left;white-space:pre-wrap;font-size:13px;line-height:1.45;color:#334155;';
            contentNode.textContent = body;
        }

        swal({
            title: title,
            text: contentNode ? undefined : body,
            content: contentNode || undefined,
            icon: failedCount > 0 ? (loadedCount > 0 ? 'warning' : 'error') : 'success',
            button: {
                text: t('ok', 'OK'),
                value: true,
                visible: true,
                className: 'btn btn-primary'
            }
        }).then(goNext, goNext);
    }

    function showPreviewProgress() {
        if (!progress) {
            return;
        }

        progress.configure({ groupSep: lang.groupSep || ',' });
        progress.show({
            title: t('previewTitle', t('loading', '')),
            groupSep: lang.groupSep || ',',
            steps: [
                { key: 'upload', label: t('stepUpload', '') },
                { key: 'validate', label: t('stepValidate', '') },
                { key: 'prepare', label: t('stepPrepare', '') }
            ]
        });
        progress.setStep('upload', 'active');
    }

    function submitPreview() {
        if (!previewForm || !input || !input.files || !input.files.length) {
            return;
        }

        if (!progress) {
            previewForm.submit();
            return;
        }

        if (dropzone) {
            dropzone.classList.add('is-loading');
        }

        showPreviewProgress();

        var formData = new FormData(previewForm);
        var xhr = new XMLHttpRequest();
        xhr.open('POST', previewForm.action, true);

        xhr.upload.addEventListener('progress', function (event) {
            if (!event.lengthComputable) {
                return;
            }
            var pct = Math.round((event.loaded / event.total) * 40);
            progress.setProgress(
                pct,
                detailText(event.loaded, event.total),
                t('stepUpload', '')
            );
        });

        xhr.addEventListener('readystatechange', function () {
            if (xhr.readyState === XMLHttpRequest.HEADERS_RECEIVED || xhr.readyState === XMLHttpRequest.LOADING) {
                progress.setStep('upload', 'done');
                progress.setStep('validate', 'active');
                progress.setProgress(55, detailText(0, 0), t('stepValidate', ''));
            }
        });

        xhr.onload = function () {
            if (xhr.status >= 200 && xhr.status < 400) {
                progress.setStep('validate', 'done');
                progress.setStep('prepare', 'active');
                progress.setProgress(92, detailText(0, 0), t('stepPrepare', ''));
                document.open();
                document.write(xhr.responseText);
                document.close();
                return;
            }

            progress.setProgress(0, t('error', ''), t('error', ''));
            if (dropzone) {
                dropzone.classList.remove('is-loading');
            }
            setTimeout(function () {
                progress.hide();
            }, 1800);
        };

        xhr.onerror = function () {
            progress.setProgress(0, t('error', ''), t('error', ''));
            if (dropzone) {
                dropzone.classList.remove('is-loading');
            }
            setTimeout(function () {
                progress.hide();
            }, 1800);
        };

        xhr.send(formData);
    }

    function runConfirmBatch(event) {
        if (!confirmForm) {
            return;
        }

        event.preventDefault();

        var tokenInput = confirmForm.querySelector('[name="import_token"]');
        var token = tokenInput ? tokenInput.value : '';
        if (!token || !progress) {
            confirmForm.submit();
            return;
        }

        progress.configure({ groupSep: lang.groupSep || ',' });
        progress.show({
            title: t('confirmTitle', t('confirmLoading', '')),
            groupSep: lang.groupSep || ',',
            steps: [
                { key: 'import', label: t('stepImport', '') },
                { key: 'finalize', label: t('stepFinalize', '') }
            ]
        });
        progress.setStep('import', 'active');

        var offset = 0;
        var total = parseInt(lang.importTotal, 10) || 0;
        var loaded = 0;
        var failed = 0;

        function requestBatch() {
            var body = new FormData();
            body.append('step', 'confirm_batch');
            body.append('import_token', token);
            body.append('offset', String(offset));

            // Respaldo si la sesión/memcached perdió la previsualización.
            var sigInput = document.getElementById('fv_import_sig');
            var rowsJsonEl = document.getElementById('fv_import_rows_json');
            var sig = sigInput ? String(sigInput.value || '') : '';
            var rowsJson = rowsJsonEl ? String(rowsJsonEl.value || rowsJsonEl.textContent || '').trim() : '';
            if (!rowsJson && window.fvConfigImportPreview && window.fvConfigImportPreview.rows) {
                rowsJson = JSON.stringify(window.fvConfigImportPreview.rows);
                sig = window.fvConfigImportPreview.sig || sig;
            }
            if (sig && rowsJson) {
                body.append('import_sig', sig);
                body.append('import_rows', rowsJson);
            }

            fetch(confirmForm.action, {
                method: 'POST',
                body: body,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
                .then(function (response) {
                    var contentType = (response.headers.get('content-type') || '').toLowerCase();
                    if (!contentType.includes('application/json')) {
                        throw new Error(t('error', ''));
                    }
                    return response.json();
                })
                .then(function (data) {
                    if (!data || !data.success) {
                        throw new Error(asText(data && data.message) || t('error', ''));
                    }

                    offset = parseInt(data.processed, 10) || 0;
                    total = parseInt(data.total, 10) || total;
                    loaded = parseInt(data.loaded, 10) || 0;
                    failed = parseInt(data.failed, 10) || 0;

                    var pct = total > 0 ? Math.min(95, Math.round((offset / total) * 95)) : 0;
                    progress.setProgress(
                        pct,
                        detailText(offset, total, loaded, failed),
                        t('stepImport', '')
                    );

                    if (data.done) {
                        progress.setStep('import', 'done');
                        progress.setStep('finalize', 'done');
                        var failedCount = parseInt(data.failed, 10) || 0;
                        var reportLines = buildImportReportLines(data);
                        var finalizeDetail = reportLines[0] || (failedCount > 0 ? t('error', '') : t('complete', ''));
                        progress.setProgress(100, finalizeDetail, t('stepFinalize', ''));

                        var redirectUrl = data.redirect || lang.listUrl || window.location.href;
                        // Pequeña pausa para mostrar 100% y luego el informe (sin tapar errores).
                        window.setTimeout(function () {
                            showImportReport(data, failedCount > 0 ? '' : redirectUrl);
                        }, 250);
                        return;
                    }

                    requestBatch();
                })
                .catch(function (error) {
                    var errText = asText(error && error.message) || t('error', '');
                    if (progress) {
                        progress.setProgress(0, errText, t('error', ''));
                    }
                    setTimeout(function () {
                        if (progress) {
                            progress.hide();
                        }
                        if (typeof swal === 'function') {
                            swal({
                                title: t('error', ''),
                                text: errText,
                                icon: 'error',
                                button: true
                            });
                        }
                    }, 400);
                });
        }

        requestBatch();
    }

    if (confirmForm) {
        confirmForm.addEventListener('submit', runConfirmBatch);
    }

    if (!input) {
        return;
    }

    input.addEventListener('change', function () {
        var name = (input.files && input.files[0]) ? input.files[0].name : '';
        if (label) {
            label.textContent = name;
            label.setAttribute('data-empty', name ? '0' : '1');
        }
        if (name) {
            submitPreview();
        }
    });
})();
