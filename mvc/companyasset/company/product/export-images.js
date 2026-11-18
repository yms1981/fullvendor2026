(function () {
    function exportImagesLang() {
        return window.fvProductExportImagesLang || window.pageTexts || {};
    }

    function resolveExportImagesErrorMessage(payload) {
        var texts = exportImagesLang();
        var code = '';
        if (payload && typeof payload === 'object') {
            code = String(payload.error || '');
            if (!code && payload.message && typeof payload.message === 'object') {
                code = String(payload.message.error || '');
            }
        }
        var map = {
            product_images_export_no_selection: 'export_images_no_selection',
            product_images_export_empty: 'export_images_empty',
            product_images_export_too_many: 'export_images_too_many',
            product_images_export_failed: 'export_images_failed',
            zip_extension_missing: 'export_images_failed',
            zip_open_failed: 'export_images_failed',
            temp_file_failed: 'export_images_failed'
        };
        var key = map[code] || 'export_images_failed';
        return String(texts[key] || texts.export_images_failed || '');
    }

    function appendFilterForm(formdata) {
        var filterForm = document.getElementById('form-buscar-filtro');
        if (!filterForm) {
            return formdata;
        }
        var filterData = new FormData(filterForm);
        filterData.forEach(function (value, key) {
            formdata.append(key, value);
        });
        return formdata;
    }

    window.export_product_images = function export_product_images() {
        if (typeof window.fvProductsHasBulkSelection === 'function' && !window.fvProductsHasBulkSelection()) {
            return;
        }

        var payload = typeof fvProductsBulkPayload === 'function'
            ? fvProductsBulkPayload()
            : { products: '[]', excluded_ids: '' };

        var formdata = new FormData();
        formdata.append('products', payload.products || '[]');
        if (payload.products === 'todos') {
            formdata.append('input_todos', 'todos');
        }
        if (payload.excluded_ids) {
            formdata.append('excluded_ids', payload.excluded_ids);
        }
        formdata = appendFilterForm(formdata);

        var today = new Date();
        var dateStamp = String(today.getFullYear())
            + String(today.getMonth() + 1).padStart(2, '0')
            + String(today.getDate()).padStart(2, '0');
        var downloadFileName = 'product_images_' + dateStamp + '.zip';
        var texts = exportImagesLang();
        var loadingText = String(texts.export_images_loading || '');

        if (typeof Swal !== 'undefined' && loadingText) {
            Swal.fire({
                title: loadingText,
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: function () {
                    Swal.showLoading();
                }
            });
        }

        fetch('products/exportImages/', {
            method: 'POST',
            body: formdata,
            credentials: 'same-origin'
        })
            .then(function (response) {
                var contentType = (response.headers.get('Content-Type') || '').toLowerCase();
                if (!response.ok || contentType.indexOf('application/json') !== -1) {
                    return response.json().then(function (json) {
                        throw json || { error: 'product_images_export_failed' };
                    }).catch(function (err) {
                        if (err && err.error) {
                            throw err;
                        }
                        throw { error: 'product_images_export_failed' };
                    });
                }

                var contentDisposition = response.headers.get('Content-Disposition');
                if (contentDisposition) {
                    var filenameMatch = contentDisposition.match(/filename\*?=(?:UTF-8''|")?([^\";]+)/i);
                    if (filenameMatch && filenameMatch[1]) {
                        downloadFileName = decodeURIComponent(filenameMatch[1].replace(/"/g, '').trim());
                    }
                }
                return response.blob();
            })
            .then(function (blob) {
                if (typeof Swal !== 'undefined') {
                    Swal.close();
                }
                var file = window.URL.createObjectURL(blob);
                var a = document.createElement('a');
                a.href = file;
                a.download = downloadFileName;
                document.body.appendChild(a);
                a.click();
                a.remove();
                window.URL.revokeObjectURL(file);
            })
            .catch(function (err) {
                if (typeof Swal !== 'undefined') {
                    Swal.close();
                }
                var message = resolveExportImagesErrorMessage(err);
                if (typeof Swal !== 'undefined' && message) {
                    Swal.fire({
                        icon: 'error',
                        text: message
                    });
                } else if (message) {
                    window.alert(message);
                }
            });
    };
})();
