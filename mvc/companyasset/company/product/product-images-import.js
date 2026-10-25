(function ($) {
    'use strict';

    var config = window.fvProdImagesImport || {};
    var lang = config.lang || {};
    var uploadAuth = {
        token: String(config.uploadToken || ''),
        companyId: String(config.companyId || '')
    };
    var BULK_THRESHOLD = parseInt(config.bulkThreshold, 10) || 100;
    var INGEST_CHUNK = parseInt(config.ingestChunkSize, 10) || 300;
    var LIST_RENDER_CHUNK = parseInt(config.listRenderChunk, 10) || 200;
    var UPLOAD_CONCURRENCY = parseInt(config.uploadConcurrency, 10) || 1;
    var PROGRESS_THROTTLE_MS = 120;
    var IMAGE_EXT_PATTERN = 'png|jpg|jpeg|jfif|jpe|gif|webp|bmp|avif|tif|tiff|heic|heif|svg|ico';

    function syncConfigFromWindow() {
        var next = window.fvProdImagesImport || {};
        config = next;
        lang = next.lang || {};
        if (!uploadAuth.token && next.uploadToken) {
            uploadAuth.token = String(next.uploadToken);
        }
        if ((!uploadAuth.companyId || uploadAuth.companyId === '0') && next.companyId) {
            uploadAuth.companyId = String(next.companyId);
        }
        BULK_THRESHOLD = parseInt(next.bulkThreshold, 10) || BULK_THRESHOLD || 100;
        INGEST_CHUNK = parseInt(next.ingestChunkSize, 10) || INGEST_CHUNK || 300;
        LIST_RENDER_CHUNK = parseInt(next.listRenderChunk, 10) || LIST_RENDER_CHUNK || 200;
        UPLOAD_CONCURRENCY = parseInt(next.uploadConcurrency, 10) || UPLOAD_CONCURRENCY || 1;
    }

    var queue = [];
    var queueIndex = new Set();
    var uploadSeq = 0;
    var ingestSeq = 0;
    var activeUploadChain = null;
    var bulkMode = false;
    var skuIndexPromise = null;
    var idCounter = 0;
    var progressPaintTimer = null;

    var counters = {
        total: 0,
        checked: 0,
        done: 0,
        error: 0,
        uploading: 0
    };
    var errorSamples = [];
    var MAX_ERROR_SAMPLES = 8;
    var failedListPaintTimer = null;
    var FAILED_LIST_CHUNK = 40;

    function t(key, fallback) {
        if (lang[key] != null && lang[key] !== '') {
            return lang[key];
        }
        if (fallback != null && fallback !== '') {
            return fallback;
        }
        return '';
    }

    function resolveText(key, fallbacks) {
        var i;
        var candidates = [t(key, '')];
        if (fallbacks && fallbacks.length) {
            for (i = 0; i < fallbacks.length; i++) {
                candidates.push(String(fallbacks[i] || ''));
            }
        }
        for (i = 0; i < candidates.length; i++) {
            if (candidates[i]) {
                return candidates[i];
            }
        }
        return '';
    }

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function buildUploadRequestHeaders() {
        return {
            'X-Requested-With': 'XMLHttpRequest',
            'X-FV-Upload-Token': uploadAuth.token,
            'X-FV-Company-Id': uploadAuth.companyId
        };
    }

    function appendUploadAuthFields(formData) {
        formData.append('fv_upload_token', uploadAuth.token);
        formData.append('fv_company_id', uploadAuth.companyId);
    }

    function hasUploadAuthCredentials() {
        return uploadAuth.token.indexOf('|') !== -1 && parseInt(uploadAuth.companyId, 10) > 0;
    }

    function notifyMissingUploadAuth() {
        var title = config.uploadAuthMissingTitle
            || resolveText('import_images_upload_auth_missing', [])
            || resolveText('import_images_error_unauthenticated', []);
        var detail = config.uploadAuthMissingDetail
            || resolveText('import_images_upload_auth_missing_detail', []);
        notifyAlert(title, detail, 'warning');
    }

    function ensureUploadAuth() {
        if (hasUploadAuthCredentials()) {
            return $.Deferred().resolve(true).promise();
        }
        if (!config.uploadCredentialsUrl) {
            return $.Deferred().resolve(false).promise();
        }
        return $.ajax({
            url: config.uploadCredentialsUrl,
            type: 'GET',
            dataType: 'json',
            xhrFields: { withCredentials: true },
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (data) {
            if (data && data.token) {
                uploadAuth.token = String(data.token);
            }
            if (data && data.companyId) {
                uploadAuth.companyId = String(data.companyId);
            }
            return hasUploadAuthCredentials();
        }).catch(function () {
            return false;
        });
    }

    function formatCount(value) {
        var n = parseInt(value, 10) || 0;
        var sep = config.groupSep || ',';
        return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, sep);
    }

    function sanitizeErrorText(text) {
        var s = String(text || '').trim();
        if (!s) {
            return '';
        }
        if (/^\s*<!DOCTYPE/i.test(s) || /^\s*<html[\s>]/i.test(s) || /<meta\s+charset/i.test(s)) {
            return t('import_images_error_html_response', '');
        }
        if (typeof document !== 'undefined') {
            var div = document.createElement('div');
            div.innerHTML = s;
            s = (div.textContent || div.innerText || '').replace(/\s+/g, ' ').trim();
        } else {
            s = s.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
        }
        if (/^\s*<!DOCTYPE/i.test(s) || /^\s*<html[\s>]/i.test(s)) {
            return t('import_images_error_html_response', '');
        }
        return s;
    }

    window.fvProdImgSanitizeErrorText = sanitizeErrorText;

    function humanizeUploadError(rawMessage, response) {
        var message = sanitizeErrorText(rawMessage);
        if (response && response.code) {
            var codeKey = 'import_images_error_upload_code_' + response.code;
            var codeMsg = t(codeKey, '');
            if (codeMsg) {
                return codeMsg;
            }
        }
        if (message === 'no_file_received') {
            return t('import_images_error_no_file_received', message);
        }
        if (message === 'upload_error') {
            return t('import_images_error_upload_error', message);
        }
        if (message === 'missing_uuid') {
            return t('import_images_upload_failed_missing_uuid', message);
        }
        if (message === 'unauthenticated' || /Unauthenticated/i.test(message)) {
            return t('import_images_error_unauthenticated', message);
        }
        if (/^\s*<!DOCTYPE/i.test(message) || /^\s*<html/i.test(message) || /<meta charset/i.test(message)) {
            return t('import_images_error_html_response', message);
        }
        if (/timed?\s*out|timeout/i.test(message) || message === 'timeout') {
            return t('import_images_error_timeout', message);
        }
        if (/cURL error 28/i.test(message)) {
            return t('import_images_error_api_timeout', message);
        }
        if (/Unauthenticated|401/i.test(message)) {
            return t('import_images_error_unauthenticated', message);
        }
        if (/parsererror/i.test(message) || /unexpected token/i.test(message)) {
            return t('import_images_error_html_response', message);
        }
        return message || t('import_images_upload_failed', '');
    }

    function extractAjaxError(xhr, response) {
        var parts = [];
        if (response && typeof response === 'object') {
            if (response.error) {
                parts.push(String(response.error));
            }
            if (response.message) {
                if (typeof response.message === 'string') {
                    parts.push(response.message);
                } else if (response.message && typeof response.message === 'object' && response.message.message) {
                    parts.push(String(response.message.message));
                } else {
                    try {
                        parts.push(JSON.stringify(response.message));
                    } catch (ignore) {
                        parts.push(String(response.message));
                    }
                }
            }
            if (response.body) {
                parts.push(String(response.body).substring(0, 180));
            }
            if (response.response && typeof response.response === 'object') {
                if (response.response.error) {
                    parts.push(String(response.response.error));
                }
                if (response.response.message) {
                    parts.push(typeof response.response.message === 'string'
                        ? response.response.message
                        : JSON.stringify(response.response.message));
                }
            }
        }
        if (xhr && xhr.responseJSON) {
            if (xhr.responseJSON.error && parts.indexOf(String(xhr.responseJSON.error)) === -1) {
                parts.push(String(xhr.responseJSON.error));
            }
            if (xhr.responseJSON.message) {
                var msg = typeof xhr.responseJSON.message === 'string'
                    ? xhr.responseJSON.message
                    : JSON.stringify(xhr.responseJSON.message);
                if (parts.indexOf(msg) === -1) {
                    parts.push(msg);
                }
            }
        }
        if (!parts.length && xhr && xhr.responseText) {
            var raw = String(xhr.responseText).trim();
            if (/^\s*<!DOCTYPE/i.test(raw) || /^\s*<html/i.test(raw)) {
                parts.push(t('import_images_error_html_response', ''));
            } else {
                parts.push(raw.substring(0, 240));
            }
        }
        if (!parts.length && xhr && xhr.status) {
            parts.push('HTTP ' + xhr.status);
        }
        return humanizeUploadError(parts.join(' — '), response || (xhr && xhr.responseJSON ? xhr.responseJSON : null));
    }

    function formatFailureDetail(item) {
        return {
            fileName: item.fileName,
            title: t('import_images_error_title_upload', ''),
            detail: sanitizeErrorText(item.errorMessage || t('import_images_upload_failed', ''))
        };
    }

    function notifyUploadFailure(item) {
        if (!item || item.failureRendered) {
            return;
        }
        var fallback = resolveText('import_images_upload_failed', [
            resolveText('import_images_error_title_upload', ['Upload failed'])
        ]);
        if (!item.errorMessage) {
            item.errorMessage = fallback;
        }
        item.failureRendered = true;
        item.errorMessage = sanitizeErrorText(item.errorMessage) || fallback;
        rememberErrorSample(item.fileName, item.errorMessage);
        if (typeof window.fvProdImgAppendResultItem === 'function') {
            var payload = formatFailureDetail(item);
            payload.isOk = false;
            window.fvProdImgAppendResultItem($('#resultado_nosubidos'), payload);
        }
        if (typeof window.fvProdImgUpdateResultCounts === 'function') {
            window.fvProdImgUpdateResultCounts();
        }
        scheduleFailedListPaint();
    }

    function flushFailedUploadsToResultList() {
        queue.forEach(function (item) {
            if (item.status === 'error') {
                notifyUploadFailure(item);
            }
        });
    }

    function scheduleFailedListPaint() {
        if (failedListPaintTimer) {
            return;
        }
        failedListPaintTimer = window.setTimeout(function () {
            failedListPaintTimer = null;
            renderBulkFailedDetails();
            renderErrorGroups();
        }, PROGRESS_THROTTLE_MS);
    }

    function renderBulkFailedDetails() {
        if (!bulkMode) {
            return;
        }
        var failed = queue.filter(function (item) {
            return item.status === 'error' && item.errorMessage;
        });
        var $wrap = $('#fv-prod-images-bulk-failed-list-wrap');
        var $list = $('#fv-prod-images-bulk-failed-list');
        var $count = $('#fv-prod-images-bulk-failed-list-count');
        if (!$wrap.length || !$list.length) {
            return;
        }
        if (!failed.length) {
            $wrap.addClass('d-none');
            $list.empty();
            if ($count.length) {
                $count.text(formatCount(0));
            }
            return;
        }
        $wrap.removeClass('d-none');
        if ($count.length) {
            $count.text(formatCount(failed.length));
        }
        $list.empty();
        failed.slice(0, FAILED_LIST_CHUNK).forEach(function (item) {
            var reason = sanitizeErrorText(item.errorMessage);
            $list.append(
                '<li class="fv-prod-images-import__bulk-failed-item">' +
                '<div class="fv-prod-images-import__bulk-failed-item-inner">' +
                '<span class="fv-prod-images-import__bulk-failed-name" title="' + escapeHtml(item.fileName) + '">' +
                escapeHtml(item.fileName) +
                '</span>' +
                '<span class="fv-prod-images-import__bulk-failed-reason-title">' + escapeHtml(t('import_images_error_title_upload', '')) + '</span>' +
                '<span class="fv-prod-images-import__bulk-failed-reason">' + escapeHtml(reason) + '</span>' +
                '</div></li>'
            );
        });
        if (failed.length > FAILED_LIST_CHUNK) {
            $list.append(
                '<li class="fv-prod-images-import__bulk-failed-more text-muted">' +
                escapeHtml(replaceTokens(t('import_images_bulk_failed_more', ''), {
                    count: formatCount(failed.length - FAILED_LIST_CHUNK)
                })) +
                '</li>'
            );
        }
    }

    function renderErrorGroups() {
        var groups = {};
        queue.forEach(function (item) {
            if (item.status !== 'error' || !item.errorMessage) {
                return;
            }
            groups[item.errorMessage] = (groups[item.errorMessage] || 0) + 1;
        });
        var keys = Object.keys(groups);
        var $box = $('#fv-prod-images-bulk-error-groups');
        var $list = $('#fv-prod-images-bulk-error-groups-list');
        if (!$box.length || !$list.length) {
            return;
        }
        if (!keys.length) {
            $box.addClass('d-none');
            $list.empty();
            return;
        }
        $box.removeClass('d-none');
        $list.empty();
        keys.sort(function (a, b) {
            return groups[b] - groups[a];
        }).forEach(function (reason) {
            $list.append(
                '<li>' +
                escapeHtml(replaceTokens(t('import_images_bulk_error_summary', ''), {
                    count: formatCount(groups[reason]),
                    error: reason
                })) +
                '</li>'
            );
        });
    }

    function rememberErrorSample(fileName, message) {
        var text = String(message || '').trim();
        if (!text) {
            return;
        }
        var entry = fileName ? (fileName + ': ' + text) : text;
        if (errorSamples.indexOf(entry) !== -1) {
            return;
        }
        errorSamples.push(entry);
        if (errorSamples.length > MAX_ERROR_SAMPLES) {
            errorSamples = errorSamples.slice(0, MAX_ERROR_SAMPLES);
        }
        renderErrorSamples();
    }

    function renderErrorSamples() {
        var $box = $('#fv-prod-images-bulk-errors');
        var $list = $('#fv-prod-images-bulk-errors-list');
        if (!$box.length || !$list.length) {
            return;
        }
        if (!errorSamples.length) {
            $box.addClass('d-none');
            $list.empty();
            return;
        }
        $box.removeClass('d-none');
        $list.empty();
        errorSamples.forEach(function (sample) {
            $list.append('<li>' + escapeHtml(sample) + '</li>');
        });
    }

    function replaceTokens(template, data) {
        if (!template) {
            return '';
        }
        return String(template).replace(/\{(\w+)\}/g, function (_, key) {
            if (!data || data[key] === undefined || data[key] === null) {
                return '';
            }
            return String(data[key]);
        });
    }

    function allowedImageExtensions() {
        return ['png', 'jpg', 'jpeg', 'jfif', 'jpe', 'gif', 'webp', 'bmp', 'avif', 'tif', 'tiff', 'heic', 'heif', 'svg', 'ico'];
    }

    function stripImageExtension(fileName) {
        return String(fileName || '').replace(new RegExp('\\.(' + IMAGE_EXT_PATTERN + ')$', 'i'), '');
    }

    /** Corrige nombres tipo sku..jpg → sku.jpg */
    function normalizeImageFileName(fileName) {
        var name = String(fileName || '').trim();
        if (!name) {
            return name;
        }
        return name.replace(new RegExp('\\.+(' + IMAGE_EXT_PATTERN + ')$', 'i'), '.$1');
    }

    function skuFilenamePrefixes(sku) {
        var value = String(sku || '').trim().replace(/\.+$/, '');
        if (!value) {
            return [];
        }
        var variants = [
            value,
            value.replace(/\//g, '-'),
            value.replace(/-/g, '/'),
            value.replace(/\//g, '')
        ];
        var seen = {};
        var out = [];
        variants.forEach(function (entry) {
            if (entry && !seen[entry]) {
                seen[entry] = true;
                out.push(entry);
            }
        });
        return out;
    }

    function escapeRegex(value) {
        return String(value).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function isFileNameSkuFormatted(sku, fileName) {
        var normalizedName = normalizeImageFileName(fileName);
        var prefixes = skuFilenamePrefixes(sku);
        var i;
        var prefix;
        var re;
        for (i = 0; i < prefixes.length; i++) {
            prefix = escapeRegex(prefixes[i]);
            re = new RegExp('^' + prefix + '\\.(' + IMAGE_EXT_PATTERN + ')$', 'i');
            if (re.test(normalizedName)) {
                return true;
            }
            re = new RegExp('^' + prefix + '_pic([2-5])\\.(' + IMAGE_EXT_PATTERN + ')$', 'i');
            if (re.test(normalizedName)) {
                return true;
            }
        }
        return false;
    }

    function getSkuFromFilename(fileName) {
        var normalized = normalizeImageFileName(fileName);
        var parts = String(normalized || '').split('_');
        if (parts.length >= 2) {
            return parts[0];
        }
        return stripImageExtension(parts[0]).replace(/\.+$/, '');
    }

    function buildSkuLookup(skus) {
        var lookup = {};
        (skus || []).forEach(function (sku) {
            skuFilenamePrefixes(sku).forEach(function (prefix) {
                lookup[String(prefix).toUpperCase()] = sku;
            });
        });
        return lookup;
    }

    function findSkuForFilename(fileName, skuLookup) {
        var candidate = getSkuFromFilename(fileName);
        var prefixes = skuFilenamePrefixes(candidate);
        var actualSku = null;
        var i;
        for (i = 0; i < prefixes.length; i++) {
            if (skuLookup[String(prefixes[i]).toUpperCase()]) {
                actualSku = skuLookup[String(prefixes[i]).toUpperCase()];
                break;
            }
        }
        if (!actualSku) {
            return null;
        }
        return isFileNameSkuFormatted(actualSku, fileName) ? actualSku : null;
    }

    function notifyAlert(title, detail, icon) {
        var safeTitle = String(title || '').trim();
        var safeDetail = String(detail || '').trim();
        var safeIcon = icon || 'info';
        if (!safeTitle && !safeDetail) {
            safeTitle = resolveText('import_images_error_title_unknown', ['Error']);
        }
        if (!safeTitle) {
            safeTitle = safeDetail;
            safeDetail = '';
        }
        if (typeof swal === 'function') {
            swal({
                title: safeTitle,
                text: safeDetail && safeDetail !== safeTitle ? safeDetail : (safeDetail || undefined),
                icon: safeIcon,
                button: true
            });
            return;
        }
        window.alert([safeTitle, safeDetail].filter(Boolean).join('\n\n'));
    }

    function loadSkuIndex(forceReload) {
        if (!config.skuIndexUrl) {
            return $.Deferred().reject({ reason: 'missing_url' }).promise();
        }
        if (!forceReload && skuIndexPromise) {
            return skuIndexPromise;
        }
        var headers = { 'X-Requested-With': 'XMLHttpRequest' };
        if (uploadAuth.token) {
            headers['X-FV-Upload-Token'] = uploadAuth.token;
        }
        if (uploadAuth.companyId) {
            headers['X-FV-Company-Id'] = uploadAuth.companyId;
        }
        skuIndexPromise = $.ajax({
            url: config.skuIndexUrl,
            type: 'GET',
            dataType: 'json',
            timeout: 120000,
            xhrFields: { withCredentials: true },
            headers: headers
        }).then(function (data) {
            var skus = (data && data.skus) || [];
            if (data && data.sku_filter === false) {
                return {
                    lookup: buildSkuLookup([]),
                    filterEnabled: false,
                    softError: String((data && data.error) || 'sku_index_failed')
                };
            }
            return {
                lookup: buildSkuLookup(skus),
                filterEnabled: true,
                softError: null
            };
        }, function (xhr) {
            skuIndexPromise = null;
            var status = xhr && xhr.status ? Number(xhr.status) : 0;
            var reason = 'request_failed';
            if (status === 401 || status === 403) {
                reason = 'unauthenticated';
            } else if (status === 404) {
                reason = 'not_found';
            } else if (status === 0 || (xhr && xhr.statusText === 'timeout')) {
                reason = 'timeout';
            }
            return $.Deferred().reject({ reason: reason, status: status }).promise();
        });
        return skuIndexPromise;
    }

    function notifyNoSkuMatches(skippedNoSku, totalImages) {
        var title = resolveText('import_images_no_sku_matches', [
            resolveText('import_images_error_title_no_sku', [])
        ]);
        var detail = replaceTokens(resolveText('import_images_folder_skipped_no_sku', []), {
            count: formatCount(skippedNoSku),
            total: formatCount(totalImages)
        });
        notifyAlert(title, detail, 'info');
    }

    function notifySkuIndexError(err) {
        // Ya no bloqueamos con modal rojo: el fallback lista archivos.
        showSkuFilterFallbackNotice(err && err.reason ? err.reason : 'request_failed');
    }

    function showSkuFilterFallbackNotice(softError) {
        var detail = resolveText('import_images_sku_filter_fallback', [
            'No se pudo filtrar por SKU ahora. Se listaron las imágenes; al importar se validará cada archivo en el servidor.'
        ]);
        if (softError === 'unauthenticated') {
            detail = resolveText('import_images_error_unauthenticated', [
                config.uploadAuthMissingDetail || detail
            ]);
        }
        $('#fv-prod-images-folder-match-summary')
            .text(detail)
            .removeClass('d-none');
    }

    function notifySkuFilterFallback(softError) {
        showSkuFilterFallbackNotice(softError);
    }

    /**
     * Encola archivos (con o sin filtro SKU) y actualiza la UI.
     */
    function finishIngestMatched(files, matchedFiles, skippedCount, skippedNoSku, options) {
        if (!matchedFiles.length) {
            if (options.replaceQueue) {
                updateFolderLabel(files);
                showSkippedFilesNotice(skippedCount);
            }
            if (skippedNoSku > 0) {
                notifyNoSkuMatches(skippedNoSku, matchedFiles.length + skippedNoSku);
            }
            updateQueueEmptyState();
            return;
        }

        if (matchedFiles.length > BULK_THRESHOLD) {
            setBulkMode(true);
        }

        var listItems = enqueueMatchedFiles(matchedFiles);
        renderQueueItemsChunked(listItems, function () {
            updateQueueEmptyState();
        });

        if (options.replaceQueue) {
            updateFolderLabel(files);
            if (skippedCount > 0) {
                showSkippedFilesNotice(skippedCount);
            }
            if (skippedNoSku > 0) {
                showSkippedNoSkuNotice(skippedNoSku);
            }
            showMatchedSummaryNotice(matchedFiles.length, skippedNoSku);
        }
    }

    function showSkippedNoSkuNotice(skippedCount) {
        if (skippedCount < 1) {
            return;
        }
        var template = t('import_images_folder_skipped_no_sku', '');
        if (!template) {
            return;
        }
        var text = replaceTokens(template, { count: formatCount(skippedCount) });
        if (typeof swal === 'function') {
            swal({ title: text, icon: 'info' });
        }
    }

    function showMatchedSummaryNotice(matchedCount, skippedNoSku) {
        var template = t('import_images_folder_matched_summary', '');
        if (!template) {
            return;
        }
        var text = replaceTokens(template, {
            matched: formatCount(matchedCount),
            skipped: formatCount(skippedNoSku)
        });
        $('#fv-prod-images-folder-match-summary')
            .text(text)
            .toggleClass('d-none', !text);
    }

    function acceptedMimeTypes() {
        return 'image/*,.png,.jpg,.jpeg,.gif,.webp,.bmp,.avif,.tif,.tiff,.heic,.heif,.svg,.ico';
    }

    function isFolderMode() {
        return $('#fv-prod-images-folder-mode').is(':checked');
    }

    function isImportableImageFile(file) {
        if (!file || !file.name) {
            return false;
        }
        if (file.name.charAt(0) === '.') {
            return false;
        }
        var relativePath = String(file.webkitRelativePath || file.name);
        if (/__MACOSX|Thumbs\.db|desktop\.ini/i.test(relativePath)) {
            return false;
        }
        var parts = file.name.split('.');
        if (parts.length < 2) {
            return false;
        }
        var ext = parts.pop().toLowerCase();
        return allowedImageExtensions().indexOf(ext) !== -1;
    }

    function fileKey(file) {
        return String(file.name) + '|' + String(file.size) + '|' + String(file.lastModified);
    }

    function recalcCounters() {
        counters.total = queue.length;
        counters.checked = 0;
        counters.done = 0;
        counters.error = 0;
        counters.uploading = 0;
        queue.forEach(function (item) {
            if (item.checked) {
                counters.checked += 1;
            }
            if (item.status === 'done') {
                counters.done += 1;
            } else if (item.status === 'error') {
                counters.error += 1;
            } else if (item.status === 'uploading') {
                counters.uploading += 1;
            }
        });
    }

    function scheduleProgressPaint() {
        if (progressPaintTimer) {
            return;
        }
        progressPaintTimer = window.setTimeout(function () {
            progressPaintTimer = null;
            paintProgressUi();
        }, PROGRESS_THROTTLE_MS);
    }

    function paintProgressUi() {
        recalcCounters();
        var total = counters.checked;
        var finished = counters.done + counters.error;
        var current = Math.min(total, finished + counters.uploading);
        var percent = total > 0 ? Math.round((finished / total) * 100) : 0;
        var pending = total > finished || counters.uploading > 0;

        var $panel = $('#fv-prod-images-upload-progress');
        var $track = $('.fv-prod-images-import__upload-progress-track');

        if (bulkMode) {
            $('#fv-prod-images-bulk-uploaded').text(formatCount(counters.done));
            $('#fv-prod-images-bulk-pending').text(formatCount(Math.max(0, total - finished - counters.uploading)));
            $('#fv-prod-images-bulk-failed').text(formatCount(counters.error));
            $('#fv-prod-images-bulk-summary-count').text(formatCount(counters.total));
        }

        $('#fv-prod-images-queue-count').text(formatCount(counters.total));

        // No mostrar barra hasta que haya empezado alguna subida real.
        var uploadStarted = counters.uploading > 0 || counters.done > 0 || counters.error > 0;
        if (total < 1 || !uploadStarted) {
            $panel.addClass('d-none');
            return;
        }

        if (!pending && finished >= total) {
            $panel.removeClass('d-none');
            $('#fv-prod-images-upload-progress-count').text(formatCount(total) + ' / ' + formatCount(total));
            $('#fv-prod-images-upload-progress-bar').css('width', '100%');
            $('#fv-prod-images-upload-progress-detail').text(
                replaceTokens(t('import_images_upload_progress_detail', ''), {
                    current: formatCount(total),
                    total: formatCount(total)
                })
            );
            $track.attr('aria-valuenow', 100);
            return;
        }

        if (pending || finished > 0) {
            $panel.removeClass('d-none');
            $('#fv-prod-images-upload-progress-count').text(formatCount(current) + ' / ' + formatCount(total));
            $('#fv-prod-images-upload-progress-bar').css('width', percent + '%');
            $('#fv-prod-images-upload-progress-detail').text(
                replaceTokens(t('import_images_upload_progress_detail', ''), {
                    current: formatCount(current),
                    total: formatCount(total)
                })
            );
            $track.attr('aria-valuenow', percent);
        }
    }

    function hideUploadProgressPanel() {
        $('#fv-prod-images-upload-progress').addClass('d-none');
        $('#fv-prod-images-upload-progress-bar').css('width', '0%');
        $('#fv-prod-images-upload-progress-detail').text('');
        $('.fv-prod-images-import__upload-progress-track').attr('aria-valuenow', 0);
    }

    function setBulkMode(enabled) {
        bulkMode = !!enabled;
        $('#fv-prod-images-bulk-summary').toggleClass('d-none', !bulkMode);
        $('#fv-prod-images-select-all-wrap').toggleClass('d-none', false);
        $('.fv-prod-images-import__bulk-note').addClass('d-none');
        if (bulkMode) {
            $('#fv-prod-images-queue-empty').addClass('d-none');
        }
    }

    function updateIngestProgress(current, total) {
        $('#fv-prod-images-ingest-progress').removeClass('d-none');
        $('#fv-prod-images-ingest-progress-detail').text(
            replaceTokens(t('import_images_bulk_ingesting', ''), {
                current: formatCount(current),
                total: formatCount(total)
            })
        );
        var pct = total > 0 ? Math.round((current / total) * 100) : 0;
        $('#fv-prod-images-ingest-progress-bar').css('width', pct + '%');
    }

    function hideIngestProgress() {
        $('#fv-prod-images-ingest-progress').addClass('d-none');
        $('#fv-prod-images-ingest-progress-bar').css('width', '0%');
        $('#fv-prod-images-ingest-progress-detail').text('');
    }

    function applyPickerMode() {
        var $input = $('#fv-prod-images-file-input');
        var folderMode = isFolderMode();

        if (folderMode) {
            $input.attr('webkitdirectory', 'webkitdirectory');
            $input.attr('directory', 'directory');
            $input.removeAttr('accept');
        } else {
            $input.removeAttr('webkitdirectory');
            $input.removeAttr('directory');
            $input.attr('accept', acceptedMimeTypes());
        }

        $('#fv-prod-images-picker-title').text(
            folderMode ? t('import_images_upload_click_folder', '') : t('import_images_upload_click_files', '')
        );
        $('#fv-prod-images-picker-hint').text(
            folderMode ? t('import_images_upload_hint_folder', '') : t('import_images_upload_hint_files', '')
        );
        $('#fv-prod-images-picker-cta').html(
            '<i class="mdi ' + (folderMode ? 'mdi-folder-open' : 'mdi-upload') + '" aria-hidden="true"></i> ' +
            escapeHtml(folderMode ? t('import_images_upload_click_folder', '') : t('import_images_upload_click_files', ''))
        );
        $('.fv-prod-images-import__picker .fv-config-import__dropzone-icon i')
            .removeClass('mdi-folder-image mdi-image-plus')
            .addClass(folderMode ? 'mdi-folder-image' : 'mdi-image-plus');
    }

    function updateFolderLabel(fileList) {
        var $label = $('#fv-prod-images-folder-name');
        if (!isFolderMode() || !fileList || !fileList.length) {
            $label.addClass('d-none').text('');
            return;
        }
        var firstPath = String(fileList[0].webkitRelativePath || '');
        var folderName = firstPath.indexOf('/') !== -1
            ? firstPath.split('/')[0]
            : t('import_images_upload_click_folder', '');
        var template = String($label.data('template') || t('import_images_folder_name', ''));
        var text = template.indexOf('{folder}') !== -1
            ? template.replace('{folder}', folderName)
            : (template + ' ' + folderName).trim();
        $label.text(text).removeClass('d-none');
    }

    function showSkippedFilesNotice(skippedCount) {
        if (skippedCount < 1) {
            return;
        }
        var template = t('import_images_folder_skipped', '');
        if (!template) {
            return;
        }
        var text = template.indexOf('{count}') !== -1
            ? template.replace('{count}', formatCount(skippedCount))
            : template;
        if (typeof swal === 'function') {
            swal({ title: text, icon: 'info' });
        }
    }

    function getImportableFiles() {
        var out = [];
        queue.forEach(function (item) {
            if (item.checked && item.uuid) {
                out.push([item.fileName, item.uuid]);
            }
        });
        return out;
    }

    function hasActiveUploadWork() {
        recalcCounters();
        if (counters.uploading > 0) {
            return true;
        }
        for (var i = 0; i < queue.length; i++) {
            var item = queue[i];
            if (item.checked && !item.uuid && item.status === 'pending') {
                return true;
            }
        }
        return false;
    }

    function getUploadSummary() {
        recalcCounters();
        return {
            total: counters.total,
            checked: counters.checked,
            done: counters.done,
            error: counters.error,
            uploading: counters.uploading,
            pending: Math.max(0, counters.checked - counters.done - counters.error - counters.uploading)
        };
    }

    function waitForUploadsComplete() {
        function step() {
            if (!hasActiveUploadWork()) {
                if (activeUploadChain && typeof activeUploadChain.then === 'function') {
                    return activeUploadChain.then(function () {
                        if (hasActiveUploadWork()) {
                            return processUploadQueue().then(step);
                        }
                        return undefined;
                    });
                }
                return Promise.resolve();
            }
            var chain = processUploadQueue();
            if (chain && typeof chain.then === 'function') {
                return chain.then(step);
            }
            return Promise.resolve();
        }
        return step();
    }

    function syncSelectAllState() {
        if (bulkMode) {
            $('#fv-prod-images-select-all').prop('checked', counters.checked === counters.total && counters.total > 0);
            $('#fv-prod-images-select-all').prop('indeterminate', counters.checked > 0 && counters.checked < counters.total);
            return;
        }
        var $checks = $('.fv-prod-images-file-check');
        var $selectAll = $('#fv-prod-images-select-all');
        if (!$checks.length) {
            $selectAll.prop('checked', false).prop('indeterminate', false);
            return;
        }
        var checkedCount = $checks.filter(':checked').length;
        $selectAll.prop('checked', checkedCount === $checks.length);
        $selectAll.prop('indeterminate', checkedCount > 0 && checkedCount < $checks.length);
    }

    function updateQueueEmptyState() {
        var hasItems = queue.length > 0;
        $('#fv-prod-images-queue-empty').toggleClass('d-none', hasItems);
        $('#fv-prod-images-queue-list').toggleClass('d-none', !hasItems);
        if (hasItems && bulkMode) {
            $('#fv-prod-images-bulk-summary-text').text(
                replaceTokens(t('import_images_bulk_summary', ''), {
                    count: formatCount(queue.length)
                })
            );
        }
        syncSelectAllState();
        scheduleProgressPaint();
    }

    function setItemStatusDom(item, status, label) {
        var $row = $('.fv-prod-images-import__queue-item[data-queue-id="' + item.id + '"]');
        if (!$row.length) {
            return;
        }
        $row.find('.fv-prod-images-import__queue-status')
            .attr('data-status', status)
            .text(status === 'error' && item.errorMessage
                ? String(item.errorMessage).substring(0, 120)
                : label);
        $row.toggleClass('fv-prod-images-import__queue-item--error', status === 'error');
        $row.toggleClass('fv-prod-images-import__queue-item--done', status === 'done');
    }

    function uploadItem(item) {
        if (!item.checked || item.uuid || item.status === 'uploading') {
            return Promise.resolve();
        }

        if (!hasUploadAuthCredentials()) {
            var authMsg = resolveText('import_images_upload_auth_missing', [
                config.uploadAuthMissingTitle
            ]);
            item.status = 'error';
            item.errorMessage = authMsg;
            notifyUploadFailure(item);
            setItemStatusDom(item, 'error', authMsg);
            scheduleProgressPaint();
            return Promise.resolve();
        }

        if (!item.file) {
            var noFileMsg = humanizeUploadError('no_file_received', null);
            item.status = 'error';
            item.errorMessage = noFileMsg;
            notifyUploadFailure(item);
            setItemStatusDom(item, 'error', t('import_images_upload_failed', ''));
            scheduleProgressPaint();
            return Promise.resolve();
        }

        item.status = 'uploading';
        setItemStatusDom(item, 'uploading', t('import_images_uploading', '…'));
        scheduleProgressPaint();

        var formData = new FormData();
        appendUploadAuthFields(formData);
        formData.append('image', item.file, item.fileName);

        return $.ajax({
            url: config.uploadUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            timeout: 120000,
            xhrFields: { withCredentials: true },
            headers: buildUploadRequestHeaders()
        }).done(function (response) {
            if (response && response.uuid) {
                item.uuid = response.uuid;
                item.status = 'done';
                item.file = null;
                item.errorMessage = null;
                setItemStatusDom(item, 'done', t('import_images_upload_done', ''));
                return;
            }
            var message = extractAjaxError(null, response);
            item.errorMessage = message || resolveText('import_images_upload_failed_missing_uuid', [
                resolveText('import_images_upload_failed', ['Upload failed'])
            ]);
            item.status = 'error';
            item.uuid = null;
            notifyUploadFailure(item);
            setItemStatusDom(item, 'error', t('import_images_upload_failed', ''));
        }).fail(function (jqXHR, textStatus, errorThrown) {
            var message = extractAjaxError(jqXHR, jqXHR && jqXHR.responseJSON ? jqXHR.responseJSON : null);
            if (!message && textStatus === 'parsererror') {
                message = resolveText('import_images_error_html_response', [
                    'Invalid server response'
                ]);
            }
            if (!message && errorThrown) {
                message = humanizeUploadError(String(errorThrown), null);
            }
            if (!message && textStatus) {
                message = humanizeUploadError(String(textStatus), null);
            }
            if (!message) {
                message = resolveText('import_images_upload_failed', ['Upload failed']);
            }
            item.status = 'error';
            item.uuid = null;
            item.errorMessage = message || resolveText('import_images_upload_failed', ['Upload failed']);
            notifyUploadFailure(item);
            setItemStatusDom(item, 'error', t('import_images_upload_failed', ''));
        }).always(function () {
            scheduleProgressPaint();
        });
    }

    function runUploadPool(seq) {
        var pending = [];
        queue.forEach(function (item) {
            if (item.checked && !item.uuid && item.status !== 'uploading') {
                pending.push(item);
            }
        });

        if (!pending.length) {
            activeUploadChain = Promise.resolve();
            scheduleProgressPaint();
            return activeUploadChain;
        }

        scheduleProgressPaint();
        var cursor = 0;

        function worker() {
            return new Promise(function (resolve) {
                function next() {
                    if (seq !== uploadSeq) {
                        resolve();
                        return;
                    }
                    if (cursor >= pending.length) {
                        resolve();
                        return;
                    }
                    var item = pending[cursor++];
                    uploadItem(item).always(next);
                }
                next();
            });
        }

        var workers = [];
        var n = Math.min(UPLOAD_CONCURRENCY, pending.length);
        for (var i = 0; i < n; i++) {
            workers.push(worker());
        }

        activeUploadChain = Promise.all(workers).then(function () {
            flushFailedUploadsToResultList();
            renderBulkFailedDetails();
            renderErrorGroups();
            scheduleProgressPaint();
        });

        return activeUploadChain;
    }

    function processUploadQueue() {
        syncConfigFromWindow();
        if (!config.uploadUrl) {
            queue.forEach(function (item) {
                if (item.checked && !item.uuid) {
                    item.status = 'error';
                    item.errorMessage = resolveText('import_images_error_invalid_response', [
                        'Missing upload URL'
                    ]);
                    notifyUploadFailure(item);
                    setItemStatusDom(item, 'error', t('import_images_upload_failed', ''));
                }
            });
            scheduleProgressPaint();
            return $.Deferred().reject({ reason: 'missing_upload_url' }).promise();
        }
        return ensureUploadAuth().then(function (ready) {
            if (!ready) {
                notifyMissingUploadAuth();
                queue.forEach(function (item) {
                    if (item.checked && !item.uuid && item.status !== 'done') {
                        item.status = 'error';
                        item.errorMessage = resolveText('import_images_upload_auth_missing_detail', [
                            config.uploadAuthMissingDetail,
                            resolveText('import_images_error_unauthenticated', [])
                        ]);
                        notifyUploadFailure(item);
                        setItemStatusDom(item, 'error', t('import_images_upload_failed', ''));
                    }
                });
                scheduleProgressPaint();
                return $.Deferred().reject({ reason: 'unauthenticated' }).promise();
            }
            uploadSeq += 1;
            return runUploadPool(uploadSeq);
        });
    }

    function renderQueueItem(item) {
        var statusLabel = t('import_images_upload_pending', '');
        var $row = $(
            '<li class="fv-prod-images-import__queue-item fv-prod-images-import__queue-item--list-only" data-queue-id="' + item.id + '" data-file-name="' + escapeHtml(item.fileName) + '">' +
            '<label class="fv-prod-images-import__queue-check">' +
            '<input type="checkbox" class="fv-prod-images-file-check" checked aria-label="' + escapeHtml(item.fileName) + '">' +
            '</label>' +
            '<span class="fv-prod-images-import__queue-name" title="' + escapeHtml(item.fileName) + '">' + escapeHtml(item.fileName) + '</span>' +
            '<span class="fv-prod-images-import__queue-status" data-status="pending">' + escapeHtml(statusLabel) + '</span>' +
            '<button type="button" class="fv-prod-images-import__queue-remove" aria-label="' + escapeHtml(t('dictRemoveFile', '')) + '">' +
            '<i class="mdi mdi-close" aria-hidden="true"></i>' +
            '</button>' +
            '</li>'
        );
        $('#fv-prod-images-queue-list').append($row);
    }

    function renderQueueItemsChunked(items, onComplete) {
        var index = 0;
        var total = items.length;

        function step() {
            var end = Math.min(index + LIST_RENDER_CHUNK, total);
            for (; index < end; index++) {
                renderQueueItem(items[index]);
            }
            if (index < total) {
                window.setTimeout(step, 0);
                return;
            }
            if (typeof onComplete === 'function') {
                onComplete();
            }
        }

        step();
    }

    function resetQueueState() {
        uploadSeq += 1;
        ingestSeq += 1;
        queue = [];
        queueIndex = new Set();
        activeUploadChain = null;
        idCounter = 0;
        errorSamples = [];
        $('#fv-prod-images-queue-list').empty();
        renderErrorSamples();
        renderBulkFailedDetails();
        renderErrorGroups();
        if (typeof window.fvProdImgClearUploadResults === 'function') {
            window.fvProdImgClearUploadResults();
        }
        hideUploadProgressPanel();
        hideIngestProgress();
        setBulkMode(false);
        $('#fv-prod-images-folder-match-summary').addClass('d-none').text('');
    }

    function enqueueMatchedFiles(matchedFiles) {
        var items = [];
        matchedFiles.forEach(function (file) {
            var key = fileKey(file);
            if (queueIndex.has(key)) {
                return;
            }
            queueIndex.add(key);
            var item = {
                id: 'q' + (++idCounter),
                file: file,
                fileName: normalizeImageFileName(file.name),
                checked: true,
                uuid: null,
                status: 'pending'
            };
            queue.push(item);
            items.push(item);
        });
        return items;
    }

    function ingestFiles(fileList, options) {
        syncConfigFromWindow();
        options = options || {};
        if (!fileList || !fileList.length) {
            return;
        }

        if (options.replaceQueue) {
            resetQueueState();
            skuIndexPromise = null;
        }

        var currentIngestSeq = ++ingestSeq;
        var files = Array.prototype.slice.call(fileList);
        var skippedCount = 0;
        var importableFiles = [];

        files.forEach(function (file) {
            if (!isImportableImageFile(file)) {
                skippedCount += 1;
                return;
            }
            importableFiles.push(file);
        });

        if (!importableFiles.length) {
            if (options.replaceQueue) {
                updateFolderLabel(files);
                showSkippedFilesNotice(skippedCount);
            }
            updateQueueEmptyState();
            return;
        }

        $('#fv-prod-images-ingest-progress').removeClass('d-none');
        $('#fv-prod-images-ingest-progress-detail').text(
            resolveText('import_images_validating_skus', [])
        );

        function enqueueWithoutSkuFilter(softError) {
            if (currentIngestSeq !== ingestSeq) {
                return;
            }
            hideIngestProgress();
            if (softError) {
                notifySkuFilterFallback(softError);
            } else {
                notifySkuFilterFallback('request_failed');
            }
            finishIngestMatched(files, importableFiles, skippedCount, 0, options);
        }

        loadSkuIndex(options.replaceQueue).then(function (result) {
            if (currentIngestSeq !== ingestSeq) {
                return;
            }

            var skuLookup = result && result.lookup ? result.lookup : {};
            var filterEnabled = !!(result && result.filterEnabled);
            var softError = result && result.softError ? result.softError : null;

            if (!filterEnabled) {
                enqueueWithoutSkuFilter(softError || 'sku_index_failed');
                return;
            }

            var matchedFiles = [];
            var skippedNoSku = 0;
            var i;
            var total = importableFiles.length;

            for (i = 0; i < total; i++) {
                if (findSkuForFilename(importableFiles[i].name, skuLookup)) {
                    matchedFiles.push(importableFiles[i]);
                } else {
                    skippedNoSku += 1;
                }
                if (i > 0 && i % INGEST_CHUNK === 0) {
                    updateIngestProgress(i, total);
                }
            }

            updateIngestProgress(total, total);
            hideIngestProgress();
            finishIngestMatched(files, matchedFiles, skippedCount, skippedNoSku, options);
        }).fail(function (err) {
            enqueueWithoutSkuFilter(err && err.reason ? err.reason : 'request_failed');
        });
    }

    function removeItem(itemId) {
        var index = -1;
        queue.forEach(function (item, idx) {
            if (item.id === itemId) {
                index = idx;
            }
        });
        if (index === -1) {
            return;
        }
        var removed = queue.splice(index, 1)[0];
        if (removed && removed.file) {
            queueIndex.delete(fileKey(removed.file));
        }
        $('.fv-prod-images-import__queue-item[data-queue-id="' + itemId + '"]').remove();
        updateQueueEmptyState();
    }

    window.fvProdImagesImportApi = {
        getImportableFiles: getImportableFiles,
        getUploadSummary: getUploadSummary,
        startUploads: function () {
            return processUploadQueue();
        },
        clearQueue: function () {
            resetQueueState();
            $('#fv-prod-images-folder-name').addClass('d-none').text('');
            updateQueueEmptyState();
        },
        waitForUploadsComplete: waitForUploadsComplete
    };

    $(document).ready(function () {
        syncConfigFromWindow();
        applyPickerMode();
        // Precargar credenciales de subida para no fallar al pulsar Importar.
        ensureUploadAuth();
        if (config.skuIndexUrl) {
            loadSkuIndex(false);
        }

        $('#fv-prod-images-folder-mode').on('change', function () {
            applyPickerMode();
            if (!isFolderMode()) {
                $('#fv-prod-images-folder-name').addClass('d-none').text('');
            }
        });

        $('#fv-prod-images-file-input').on('change', function () {
            ingestFiles(this.files, { replaceQueue: true });
            this.value = '';
        });

        $(document).on('change', '#fv-prod-images-select-all', function () {
            var checked = $(this).is(':checked');
            queue.forEach(function (item) {
                item.checked = checked;
                if (checked && !item.uuid && item.status !== 'uploading') {
                    item.status = 'pending';
                    setItemStatusDom(item, 'pending', t('import_images_upload_pending', ''));
                }
            });
            if (!bulkMode) {
                $('.fv-prod-images-file-check').prop('checked', checked);
            }
            syncSelectAllState();
        });

        $(document).on('change', '.fv-prod-images-file-check', function () {
            var $row = $(this).closest('.fv-prod-images-import__queue-item');
            var itemId = $row.data('queue-id');
            var item = null;
            queue.forEach(function (entry) {
                if (entry.id === itemId) {
                    item = entry;
                }
            });
            if (!item) {
                return;
            }
            item.checked = $(this).is(':checked');
            if (item.checked && !item.uuid && item.status !== 'uploading') {
                item.status = 'pending';
                setItemStatusDom(item, 'pending', t('import_images_upload_pending', ''));
            }
            syncSelectAllState();
        });

        $(document).on('click', '.fv-prod-images-import__queue-remove', function () {
            removeItem($(this).closest('.fv-prod-images-import__queue-item').data('queue-id'));
        });

        $(document).on('mouseover', '.link-result', function () {
            if (bulkMode) {
                return;
            }
            var imagen = $(this).data('imagen');
            $('.fv-prod-images-import__queue-item[data-file-name="' + imagen + '"]').addClass('fv-prod-images-import__queue-item--highlight');
        });

        $(document).on('mouseout', '.link-result', function () {
            $('.fv-prod-images-import__queue-item').removeClass('fv-prod-images-import__queue-item--highlight');
        });

        updateQueueEmptyState();
    });
}(window.jQuery));
