const get_selected_customer = () => {
    return d.querySelectorAll("input.checkBoxClass[type=checkbox]:checked")
}

/**
 * Resuelve el payload de bulk (modo todos con exclusiones, o lista de IDs persistida).
 * Usa el helper expuesto por eventos.js si está disponible; si no, hace fallback al
 * comportamiento legacy basado en las filas visibles marcadas.
 */
function fvProductsBulkPayload() {
    if (typeof window.fvProductsCollectBulkData === 'function') {
        return window.fvProductsCollectBulkData();
    }
    var ids = $("input.checkBoxClass[type=checkbox]:checked").map(function () {
        return $(this).val();
    }).get();
    return { products: JSON.stringify(ids), excluded_ids: '' };
}

/**
 * Sincroniza los inputs ocultos products/excluded_ids del modal.
 */
function fvSyncBulkModalInputs($modal) {
    var payload = fvProductsBulkPayload();
    $modal.find('input[name=products]').val(payload.products);
    var $excluded = $modal.find('input[name=excluded_ids]');
    if (!$excluded.length) {
        $excluded = $('<input>', { type: 'hidden', name: 'excluded_ids' });
        $modal.find('form').first().append($excluded);
    }
    $excluded.val(payload.excluded_ids);
}

function fvBulkSelectionMissing() {
    return typeof window.fvProductsHasBulkSelection === 'function' && !window.fvProductsHasBulkSelection();
}

const asign_category = async () => {
    if (fvBulkSelectionMissing()) {
        return;
    }
    fvSyncBulkModalInputs(comp.modalasigncategory);
    comp.modalasigncategory.modal("show")
}

const remove_category = async () => {
    if (fvBulkSelectionMissing()) {
        return;
    }
    fvSyncBulkModalInputs(comp.modalremovecategory);
    comp.modalremovecategory.modal("show")
}

const change_status = async () => {
    if (fvBulkSelectionMissing()) {
        return;
    }
    fvSyncBulkModalInputs(comp.modalchangestatus);
    comp.modalchangestatus.modal("show")
}

function formDataMergeFiltroForm(form) {
    let formdata_filter = new FormData(d.getElementById("form-buscar-filtro"))
    let formdata = new FormData(form);
    // union of two form data
    for (const [key, value] of formdata_filter.entries()) {
        formdata.append(key, value);
    }
    return formdata;
}

function submit_bulk_form(form) {

    let submitBtn = $(form).parents('.modal-content').find("button[type=submit].btn-primary");
    let originalText = submitBtn.html();
    let loadingSpan =
        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>'

    submitBtn.html(loadingSpan+originalText);
    submitBtn.prop('disabled', true);
    let formdata = formDataMergeFiltroForm(form);
    return fetch(form.action, {
        method: 'POST',
        body: formdata
    })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                alert_func([data.msg, 'error'])
                submitBtn.html(originalText);
                submitBtn.prop('disabled', false);
                return;
            }
            return data
        })
}

$(function () {
    $("form#form-asign-category").submit(function (e) {
        e.preventDefault()

        let assignCategoryForm = d.getElementById("form-asign-category");

        submit_bulk_form(assignCategoryForm).then(data =>{
            if (data.success)
                location.reload()
        });

    });

    $("form#form-remove-category").submit(function (e) {
        e.preventDefault()

        let removeCategoryForm = d.getElementById("form-remove-category");
        submit_bulk_form(removeCategoryForm).then(data =>{
            if (data.success)
                location.reload()
        });
    });

    $("form#form-add-catalog").submit(function (e) {
        e.preventDefault()
        let addCatalogForm = d.getElementById("form-add-catalog");
        submit_bulk_form(addCatalogForm).then(data =>{
            if (data.success)
                location.reload()
        });


    });

    $("form#form-change-status").submit(function (e) {
        e.preventDefault()

        let changeStatusForm = d.getElementById("form-change-status");
        submit_bulk_form(changeStatusForm).then(data =>{
            if (data.success)
                location.reload()
        })
    })

    /**
     * Abre el modal "Agregar a catálogo" para uno o más productos concretos
     * (botón + del listado / vista tarjetas), sin perder la selección forzada
     * al sincronizar el bulk del toolbar.
     */
    function fvOpenAddToCatalogForProducts(productIds) {
        var ids = (Array.isArray(productIds) ? productIds : [productIds])
            .map(function (id) { return String(id); })
            .filter(function (id) { return id !== '' && id !== '0'; });
        if (!ids.length || !comp.modaladdcatalog || !comp.modaladdcatalog.length) {
            return false;
        }
        window.fvAddToCatalogForceProductIds = ids;
        comp.modaladdcatalog.modal('show');
        return true;
    }

    function fvExtractAddToCatalogProductId(href) {
        if (!href) {
            return null;
        }
        var match = String(href).match(/addToCatalog\/(\d+)/i)
            || String(href).match(/add-to-catalog\/(\d+)/i);
        return match ? match[1] : null;
    }

    $(document).on('click', 'a[href*="addToCatalog/"], a[href*="add-to-catalog/"]', function (e) {
        var productId = fvExtractAddToCatalogProductId($(this).attr('href'));
        if (!productId) {
            return;
        }
        e.preventDefault();
        if (!fvOpenAddToCatalogForProducts([productId])) {
            window.location.href = $(this).attr('href');
        }
    });

    comp.modaladdcatalog.on('shown.bs.modal', function () {
        var $modal = comp.modaladdcatalog;
        if (window.fvAddToCatalogForceProductIds && window.fvAddToCatalogForceProductIds.length) {
            $modal.find('input[name=products]').val(JSON.stringify(window.fvAddToCatalogForceProductIds));
            var $excluded = $modal.find('input[name=excluded_ids]');
            if ($excluded.length) {
                $excluded.val('');
            }
            window.fvAddToCatalogForceProductIds = null;
            return;
        }
        fvSyncBulkModalInputs($modal);
    });

    if (window.fvAddToCatalogProductId) {
        fvOpenAddToCatalogForProducts([window.fvAddToCatalogProductId]);
        try {
            delete window.fvAddToCatalogProductId;
        } catch (err) {
            window.fvAddToCatalogProductId = null;
        }
    }

    function fvProductsLang() {
        var block = (window.options && options.lang && (options.lang['cmp-products'] || options.lang.cmp_products)) || {};
        return block.lang || block || {};
    }

    function fvMergeFiltroIntoBulkPayload(payload) {
        var out = Object.assign({}, payload || {});
        var form = document.getElementById('form-buscar-filtro');
        if (form) {
            var fd = new FormData(form);
            fd.forEach(function (value, key) {
                if (out[key] === undefined || out[key] === null || out[key] === '') {
                    out[key] = value;
                }
            });
        }
        if (window.options && options.company_id) {
            out.company_id = options.company_id;
        }
        return out;
    }

    window.eliminarProductosSeleccionados = function () {
        var lang = fvProductsLang();
        var payload = fvMergeFiltroIntoBulkPayload(fvProductsBulkPayload());
        var hasIds = false;
        try {
            if (payload.products === 'todos') {
                hasIds = true;
            } else {
                var ids = typeof payload.products === 'string' ? JSON.parse(payload.products || '[]') : payload.products;
                hasIds = Array.isArray(ids) && ids.length > 0;
            }
        } catch (e) {
            hasIds = false;
        }
        if (!hasIds) {
            if (typeof alert_func === 'function') {
                alert_func([lang.seleccione_producto || '', 'error']);
            }
            return;
        }

        swal({
            title: lang.eliminar_productos_seleccionados || lang.desea_eliminar_registro || '',
            icon: 'warning',
            buttons: {
                cancel: {
                    text: lang.cancelar || 'Cancel',
                    value: false,
                    visible: true,
                    className: 'sweet-danager',
                    closeModal: true
                },
                confirm: {
                    text: lang.bnBulkDelete || lang.eliminar || lang.delete || 'Delete',
                    value: true,
                    visible: true,
                    className: 'sweet-warning',
                    closeModal: true
                }
            }
        }).then(function (willDelete) {
            if (!willDelete) {
                return;
            }
            fetch((options.url || '') + '/products/bulkDelete', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
                .then(function (response) {
                    return response.json().then(function (data) {
                        return { ok: response.ok, data: data };
                    });
                })
                .then(function (res) {
                    if (res.ok && res.data && res.data.success !== false) {
                        swal({
                            title: (res.data && res.data.message) || lang.products_deleted_success || '',
                            icon: 'success'
                        }).then(function () {
                            window.location.reload();
                        });
                        return;
                    }
                    swal({
                        title: (res.data && res.data.message) || lang.products_delete_error || '',
                        icon: 'error'
                    });
                })
                .catch(function (error) {
                    swal({
                        icon: 'error',
                        title: lang.products_delete_error || 'Error',
                        text: error.message
                    });
                });
        });
    };

})
