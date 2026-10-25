(function (window) {
    'use strict';

    var config = window.fvCatImgImport || {};
    var lang = config.lang || {};
    var categories = Array.isArray(config.categories) ? config.categories : [];
    var pendingFiles = [];
    var myDropzone = null;
    var $ = null;

    function t(key, fallback) {
        var value = lang[key];
        return (typeof value === 'string' && value !== '') ? value : (fallback || '');
    }

    function formatCount(value) {
        var n = parseInt(value, 10) || 0;
        var sep = config.groupSep || ',';
        return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, sep);
    }

    function escapeHtml(value) {
        var div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    }

    function parseJsonResponse(response) {
        if (typeof response === 'string') {
            try {
                return JSON.parse(response);
            } catch (e) {
                return null;
            }
        }
        return response;
    }

    function normalizeCategoryKey(name) {
        return String(name || '').trim().toLowerCase().replace(/\s+/g, ' ');
    }

    function categoryNameFromFilename(fileName) {
        var base = String(fileName || '').replace(/\.(png|jpe?g)$/i, '');
        base = base.replace(/_/g, ' ').trim();
        return base === '' ? null : base;
    }

    function findCategoryByName(name) {
        var keys = [normalizeCategoryKey(name)];
        if (keys[0].indexOf(' & ') !== -1) {
            keys.push(keys[0].replace(/ & /g, ' and '));
        }
        if (keys[0].indexOf(' and ') !== -1) {
            keys.push(keys[0].replace(/ and /g, ' & '));
        }

        for (var i = 0; i < keys.length; i++) {
            for (var j = 0; j < categories.length; j++) {
                if (normalizeCategoryKey(categories[j].category_name) === keys[i]) {
                    return categories[j];
                }
            }
        }
        return null;
    }

    function findPendingByFileName(fileName) {
        for (var i = 0; i < pendingFiles.length; i++) {
            if (pendingFiles[i].fileName === fileName) {
                return pendingFiles[i];
            }
        }
        return null;
    }

    function buildCategorySelectHtml(fileName, selectedId) {
        var html = '<select class="form-control form-control-sm fv-cat-img-category-select" data-file-name="' + escapeHtml(fileName) + '">';
        html += '<option value="">' + escapeHtml(t('import_category_select_category', '')) + '</option>';
        categories.forEach(function (cat) {
            var id = parseInt(cat.cat_id, 10);
            var selected = id === selectedId ? ' selected' : '';
            html += '<option value="' + id + '"' + selected + '>' + escapeHtml(cat.category_name) + '</option>';
        });
        html += '</select>';
        return html;
    }

    function updateQueueCounts() {
        var linked = pendingFiles.filter(function (item) { return item.catId > 0; }).length;
        var unlinked = pendingFiles.length - linked;
        $('#fv-cat-img-count-linked').text(formatCount(linked));
        $('#fv-cat-img-count-unlinked').text(formatCount(unlinked));
    }

    function updateDropzonePreview(fileName) {
        if (!myDropzone) {
            return;
        }
        var item = findPendingByFileName(fileName);
        if (!item) {
            return;
        }

        var preview = null;
        myDropzone.files.forEach(function (file) {
            if (file.name === fileName && file.previewElement) {
                preview = file.previewElement;
            }
        });
        if (!preview) {
            return;
        }

        var existing = preview.querySelector('.fv-cat-img-preview-link');
        if (existing) {
            existing.remove();
        }

        var box = document.createElement('div');
        box.className = 'fv-cat-img-preview-link';

        if (item.catId > 0) {
            var badgeClass = item.linkType === 'auto'
                ? 'fv-category-images-import__queue-badge--auto'
                : 'fv-category-images-import__queue-badge--manual';
            var badgeLabel = item.linkType === 'auto'
                ? t('import_category_linked_auto', '')
                : t('import_category_linked_manual', '');
            box.innerHTML =
                '<span class="fv-category-images-import__queue-badge ' + badgeClass + '">' + escapeHtml(badgeLabel) + '</span>' +
                '<span class="fv-category-images-import__queue-category">' + escapeHtml(item.catName) + '</span>';
        } else {
            box.innerHTML = buildCategorySelectHtml(fileName, 0);
        }

        preview.appendChild(box);
    }

    function renderQueue() {
        var $linked = $('#queue_linked');
        var $unlinked = $('#queue_unlinked');
        $linked.empty();
        $unlinked.empty();

        if (pendingFiles.length === 0) {
            $linked.append('<p class="fv-category-images-import__queue-empty text-muted mb-0">' + escapeHtml(t('import_category_queue_empty', '')) + '</p>');
            updateQueueCounts();
            return;
        }

        pendingFiles.forEach(function (item) {
            var row =
                '<div class="fv-category-images-import__queue-item" data-file-name="' + escapeHtml(item.fileName) + '">' +
                '<div class="fv-category-images-import__queue-file">' + escapeHtml(item.fileName) + '</div>';

            if (item.catId > 0) {
                var badgeClass = item.linkType === 'auto'
                    ? 'fv-category-images-import__queue-badge--auto'
                    : 'fv-category-images-import__queue-badge--manual';
                var badgeLabel = item.linkType === 'auto'
                    ? t('import_category_linked_auto', '')
                    : t('import_category_linked_manual', '');
                row +=
                    '<div class="fv-category-images-import__queue-meta">' +
                    '<span class="fv-category-images-import__queue-badge ' + badgeClass + '">' + escapeHtml(badgeLabel) + '</span>' +
                    '<span class="fv-category-images-import__queue-category">' + escapeHtml(item.catName) + '</span>' +
                    '</div>';
                $linked.append(row + '</div>');
            } else {
                row += '<div class="fv-category-images-import__queue-meta">' + buildCategorySelectHtml(item.fileName, 0) + '</div>';
                $unlinked.append(row + '</div>');
            }

            updateDropzonePreview(item.fileName);
        });

        updateQueueCounts();
    }

    function addPendingFile(fileName, uuid) {
        if (findPendingByFileName(fileName)) {
            return;
        }

        var suggestedName = categoryNameFromFilename(fileName);
        var matched = suggestedName ? findCategoryByName(suggestedName) : null;
        pendingFiles.push({
            fileName: fileName,
            uuid: uuid,
            catId: matched ? parseInt(matched.cat_id, 10) : 0,
            catName: matched ? matched.category_name : '',
            linkType: matched ? 'auto' : null,
            suggestedName: suggestedName || ''
        });
        renderQueue();
    }

    function removePendingFile(fileName) {
        pendingFiles = pendingFiles.filter(function (item) {
            return item.fileName !== fileName;
        });
        renderQueue();
    }

    function assignCategory(fileName, catId, catName) {
        var item = findPendingByFileName(fileName);
        if (!item) {
            return;
        }
        if (catId > 0) {
            item.catId = catId;
            item.catName = catName;
            item.linkType = 'manual';
        } else {
            item.catId = 0;
            item.catName = '';
            item.linkType = null;
        }
        renderQueue();
    }

    function getLinkedPayload() {
        return pendingFiles
            .filter(function (item) { return item.catId > 0; })
            .map(function (item) {
                return [item.fileName, item.uuid, item.catId];
            });
    }

    function initDropzone() {
        var dropzoneEl = document.getElementById('product-images-dropzone');
        if (!dropzoneEl || typeof Dropzone === 'undefined') {
            return;
        }

        Dropzone.autoDiscover = false;
        myDropzone = new Dropzone(dropzoneEl, {
            paramName: 'image',
            parallelUploads: 1,
            acceptedFiles: 'image/*,.png,.jpg,.jpeg,.gif,.webp,.bmp,.avif,.tif,.tiff,.heic,.heif,.svg,.ico',
            maxFiles: 9999,
            maxFilesize: 100,
            addRemoveLinks: true,
            autoProcessQueue: true,
            dictDefaultMessage: config.dropzoneDefaultMessage || '',
            dictFallbackMessage: config.dictFallbackMessage || '',
            dictFallbackText: config.dictFallbackText || '',
            dictFileTooBig: config.dictFileTooBig || '',
            dictInvalidFileType: config.dictInvalidFileType || '',
            dictResponseError: config.dictResponseError || '',
            dictCancelUpload: config.dictCancelUpload || '',
            dictCancelUploadConfirmation: config.dictCancelUploadConfirmation || '',
            dictRemoveFile: config.dictRemoveFile || '',
            dictMaxFilesExceeded: config.dictMaxFilesExceeded || '',
            init: function () {
                this.on('removedfile', function (file) {
                    removePendingFile(file.name);
                });
                this.on('success', function (file, response) {
                    response = parseJsonResponse(response);
                    if (!response || typeof response.uuid === 'undefined') {
                        this.removeFile(file);
                        return;
                    }
                    file.serverID = response.uuid;
                    addPendingFile(file.name, response.uuid);
                });
                this.on('error', function (file, response) {
                    parseJsonResponse(response);
                });
            }
        });
    }

    function setImportButtonLoading(isLoading) {
        var $btn = $('#btn-export');
        if (!$btn.length) {
            return;
        }
        if (isLoading) {
            $btn.prop('disabled', true).data('fv-original-html', $btn.html());
            $btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
        } else {
            $btn.prop('disabled', false);
            var original = $btn.data('fv-original-html');
            if (original) {
                $btn.html(original);
            }
        }
    }

    function appendResultItem($container, message, isOk) {
        var iconClass = isOk ? 'mdi-check-circle text-success' : 'mdi-close-circle text-danger';
        $container.append(
            '<div class="fv-category-images-import__result-item">' +
            '<i class="mdi ' + iconClass + '" aria-hidden="true"></i>' +
            '<span>' + escapeHtml(message) + '</span>' +
            '</div>'
        );
    }

    function updateImportResultCounts() {
        var okCount = document.querySelectorAll('#resultado_subidos .fv-category-images-import__result-item').length;
        var errCount = document.querySelectorAll('#resultado_nosubidos .fv-category-images-import__result-item').length;
        $('#fv-cat-img-count-import-ok').text(formatCount(okCount));
        $('#fv-cat-img-count-import-error').text(formatCount(errCount));
    }

    function runImport(linkedPayload) {
        var progress = window.FvImportProgress;
        var importUrl = config.importUrl || '';
        if (!importUrl || !progress) {
            return;
        }

        setImportButtonLoading(true);
        progress.configure({ groupSep: config.groupSep || ',' });
        progress.show({
            title: config.progressTitle || '',
            groupSep: config.groupSep || ',',
            steps: [{ key: 'images', label: config.progressStepImages || '' }]
        });
        progress.setStep('images', 'active');

        progress.runBatches({
            items: linkedPayload,
            batchSize: 5,
            onProgress: function (pct, current, total) {
                progress.setProgress(
                    pct,
                    progress.replaceTokens(config.progressDetail || '', {
                        current: progress.formatCount(current),
                        total: progress.formatCount(total)
                    }),
                    config.progressStepImages || ''
                );
            },
            processBatch: function (chunk) {
                return new Promise(function (resolve, reject) {
                    $.ajax({
                        url: importUrl,
                        type: 'POST',
                        data: { category_images: chunk },
                        dataType: 'json'
                    }).done(function (response) {
                        if (response.results && response.results.length) {
                            response.results.forEach(function (item) {
                                if (item.success) {
                                    appendResultItem($('#resultado_subidos'), item.mensaje || item.imagen, true);
                                } else {
                                    appendResultItem($('#resultado_nosubidos'), item.mensaje || item.imagen, false);
                                }
                            });
                        } else {
                            (response.subidos || []).forEach(function (name) {
                                appendResultItem($('#resultado_subidos'), name, true);
                            });
                            (response.nosubidos || []).forEach(function (name) {
                                appendResultItem($('#resultado_nosubidos'), name, false);
                            });
                        }
                        updateImportResultCounts();
                        resolve();
                    }).fail(reject);
                });
            },
            onComplete: function () {
                progress.setStep('images', 'done');
                progress.setProgress(100, config.progressComplete || '', config.progressComplete || '');
                setImportButtonLoading(false);
                pendingFiles = [];
                if (myDropzone) {
                    myDropzone.removeAllFiles(true);
                }
                renderQueue();
                setTimeout(function () {
                    progress.hide();
                }, 500);
            },
            onError: function (xhr) {
                setImportButtonLoading(false);
                var errorMsg = config.progressError || '';
                if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                appendResultItem($('#resultado_nosubidos'), errorMsg, false);
                updateImportResultCounts();
                progress.setProgress(0, errorMsg, errorMsg);
                setTimeout(function () {
                    progress.hide();
                }, 1800);
            }
        });
    }

    function handleImportClick() {
        $('#resultado_subidos').html('');
        $('#resultado_nosubidos').html('');
        updateImportResultCounts();

        if (pendingFiles.length === 0) {
            var noImagesMsg = t('no_images_to_import', '');
            if (typeof swal === 'function') {
                swal({ title: noImagesMsg, icon: 'warning' });
            } else {
                window.alert(noImagesMsg);
            }
            return;
        }

        var linkedPayload = getLinkedPayload();
        var unlinkedCount = pendingFiles.length - linkedPayload.length;

        if (linkedPayload.length === 0) {
            var noLinkedMsg = t('import_category_no_linked_to_import', '');
            if (typeof swal === 'function') {
                swal({ title: noLinkedMsg, icon: 'warning' });
            } else {
                window.alert(noLinkedMsg);
            }
            return;
        }

        function startImport() {
            runImport(linkedPayload);
        }

        if (unlinkedCount > 0 && typeof swal === 'function') {
            swal({
                title: t('import_category_confirm_partial_title', ''),
                text: t('import_category_confirm_partial_text', '').replace('{count}', formatCount(unlinkedCount)),
                icon: 'warning',
                buttons: true
            }).then(function (confirmed) {
                if (confirmed) {
                    startImport();
                }
            });
            return;
        }

        startImport();
    }

    function bindEvents() {
        $(document).on('change', '.fv-cat-img-category-select', function () {
            var fileName = $(this).data('file-name');
            var catId = parseInt($(this).val(), 10) || 0;
            var catName = catId > 0 ? $(this).find('option:selected').text() : '';
            assignCategory(fileName, catId, catName);
        });

        $(document).on('click', '#btn-export', function (e) {
            e.preventDefault();
            handleImportClick();
        });

        $('#form-guardar-imagenes').on('submit', function (e) {
            e.preventDefault();
            handleImportClick();
        });
    }

    function init() {
        $ = window.jQuery;
        renderQueue();
        initDropzone();
        bindEvents();
    }

    function boot() {
        if (typeof window.jQuery === 'undefined') {
            if (typeof window.CoreLoader !== 'undefined' && typeof window.CoreLoader.waitForJQuery === 'function') {
                window.CoreLoader.waitForJQuery(init);
            } else {
                window.setTimeout(boot, 100);
            }
            return;
        }
        init();
    }

    boot();
}(window));
