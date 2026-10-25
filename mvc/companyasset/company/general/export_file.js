/**
 * Exportación Excel/PDF (órdenes de inventario y pantallas que reutilizan el modal).
 */
async function export_file(frm_buscarFiltro, url_send) {
    if (!frm_buscarFiltro || !url_send) {
        throw new Error('export_file: missing params');
    }

    var exportLang = {};
    try {
        if (typeof options !== 'undefined' && options.lang) {
            var block = options.lang.cmp_modal_export_layout || options.lang['cmp-modal-export-layout'];
            if (block && block.lang && typeof block.lang === 'object') {
                exportLang = block.lang;
            } else if (block && typeof block === 'object') {
                exportLang = block;
            }
        }
    } catch (e) {
        exportLang = {};
    }

    var formulario_filtro = document.getElementById(frm_buscarFiltro);
    var form_export = document.getElementById('form-exportar');
    var btn_export = document.getElementById('btn-export');
    if (!formulario_filtro || !form_export || !btn_export) {
        window.alert(exportLang.invalid_request || exportLang.error_export || 'Error');
        return;
    }

    var formData = new FormData(formulario_filtro);
    var exportData = new FormData(form_export);
    exportData.forEach(function (value, key) {
        formData.append(key, value);
    });

    if (typeof window.jQuery !== 'undefined') {
        window.jQuery('.inventory-orders-list-table tbody tr .checkBoxClass:checked, #infoTable tbody tr .checkBoxClass:checked, .table tbody tr .checkBoxClass:checked').each(function (index, element) {
            var value = window.jQuery(element).val();
            var nameAttr = window.jQuery(element).attr('name') || 'orderIds[]';
            var name_input = String(nameAttr).replace(/\[.*\]/, '') || 'orderIds';
            formData.append(name_input + '[' + index + ']', value);
        });
    }

    var innerBtn = btn_export.innerHTML;
    var labelExport = exportLang.bnExport || '';
    btn_export.disabled = true;
    btn_export.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + labelExport;

    var base = (typeof options !== 'undefined' && options.url) ? String(options.url).replace(/\/$/, '') : '';

    try {
        var response = await fetch(base + url_send, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        var contentType = response.headers.get('Content-Type') || '';
        if (contentType.indexOf('application/json') !== -1) {
            var jsonData = await response.json();
            var msg = jsonData.msg || jsonData.message || exportLang.invalid_request || 'Error';
            if (typeof alert_func === 'function') {
                alert_func([msg, 'error']);
            } else if (typeof Swal !== 'undefined' && Swal.fire) {
                Swal.fire({ icon: 'error', title: msg });
            } else {
                window.alert(msg);
            }
            return;
        }

        if (!response.ok) {
            throw new Error('export_http_' + response.status);
        }

        var contentDisposition = response.headers.get('Content-Disposition') || '';
        var nombreArchivo = 'export.xlsx';
        var matches = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/.exec(contentDisposition);
        if (matches != null && matches[1]) {
            nombreArchivo = matches[1].replace(/['"]/g, '');
        }

        var blob = await response.blob();
        var url = window.URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = nombreArchivo;
        a.style.display = 'none';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);

        if (typeof window.jQuery !== 'undefined') {
            window.jQuery('#modal_export').modal('hide');
        }
    } catch (err) {
        var failMsg = exportLang.error_export || exportLang.invalid_request || 'Error';
        if (typeof alert_func === 'function') {
            alert_func([failMsg, 'error']);
        } else if (typeof Swal !== 'undefined' && Swal.fire) {
            Swal.fire({ icon: 'error', title: failMsg });
        } else {
            window.alert(failMsg);
        }
    } finally {
        btn_export.disabled = false;
        btn_export.innerHTML = innerBtn;
    }
}
