$(function () {
//marcar todos  link-todos
    $(document).on('click', '#link-todos', function(e) {

        const {lang} = {...options.lang.cmp_products}

        let input_todos = $('#input-todos')
        let expanding = input_todos.val() === 'pagina';

        if (expanding === true) {
            input_todos.val('todos')
            let totalFilteredCount = $("#expand-selection-info").data('total-filtered-items');
            $(this).text(lang.desmarcar_todos);
            $("#contextual-actions-container").removeClass('alert-warning').addClass('alert-primary');
            $("#expansible-info").text(totalFilteredCount+' '+lang.bulk_will_be_used);
        } else {
            input_todos.val('pagina')
            $('#ckbCheckAll').attr('title', lang.toltip_paginas);
            $(this).text(lang.marcar_todos)
            $("#contextual-actions-container").removeClass('alert-primary').addClass('alert-warning');
            $("#expansible-info").text($('.checkBoxClass:checked').length+' '+lang.bulk_will_be_used);
        }

    })

// mostrar alerta todos
    $(document).on('change', '#ckbCheckAll', function(e) {
        const {lang} = {...options.lang.cmp_products}
        let checkedCheckboxCount = $('.checkBoxClass:checked').length;
        $('#contextual-actions button,#contextual-actions a').prop('disabled',checkedCheckboxCount === 0);
        let inputTodos = $("#input-todos");
        if ($(this).is(':checked')) {
            $('#expand-selection.alert').addClass('show').show()
            $("#contextual-actions-container").addClass('alert alert-warning')
            inputTodos.val('pagina')
            $("#link-todos").text(lang.marcar_todos)
            $('#expansible-info').text(checkedCheckboxCount+' '+lang.bulk_will_be_used);
        } else {
            inputTodos.val('pagina')
            $("#contextual-actions-container").removeClass('alert alert-warning alert-primary')
            $('#expand-selection.alert').hide()
            $('#expansible-info').text("");
        }
    })

    $(document).on('change', '.checkBoxClass', function(e) {
        const {lang} = {...options.lang.cmp_products}
        let checkedCheckboxesCount = $('.checkBoxClass:checked').length;

        $('#contextual-actions button,#contextual-actions a').prop('disabled',checkedCheckboxesCount === 0);
        $("#expansible-info").text(checkedCheckboxesCount+' '+lang.bulk_will_be_used);
        let inputTodos = $("#input-todos");
        inputTodos.val('pagina');
        if (!this.checked) {
            $('#ckbCheckAll').prop('checked', false)
            $("#contextual-actions-container").removeClass('alert alert-warning alert-primary')
            $('#expand-selection.alert').hide()
        }
        if($('.checkBoxClass:not(:checked)').length === 0) {
            $('#ckbCheckAll').prop('checked', true)
            $("#contextual-actions-container")
                .addClass('alert')
                .addClass((inputTodos.val()==='todos')?'alert-primary':'alert-warning');

            $('#expand-selection.alert').addClass('show').show()
        }
        if(checkedCheckboxesCount === 0) {
            $('#expansible-info').text("");
        }
    })

    $(document).on('click', '.boton_estado', function(e) {

        e.preventDefault();

        let icon = 'error'
        if ($(this).hasClass("btn-success")) {
            icon = 'success'
        }

        /*mostrar_toast(icon, mensaje)*/
        let url = $(this).attr('href');
        let loader_text = $(this).data('loader');
        let boton = $(this)

        let fila_padre = $(this).parent().parent()
        let fila_padre_fondo = $(this).parent().parent().hasClass('fondo-default')

        $.ajax({

            url: url,
            type: 'get',
            beforeSend: function() {

                let contenido_boton =
                    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>  ' +
                    loader_text

                boton.html(contenido_boton)
            },
            success: function(response) {

                let columna_boton = boton.parent()
                columna_boton.html(response)
                if (fila_padre_fondo) {
                    fila_padre.removeClass('fondo-default')
                } else {
                    fila_padre.addClass('fondo-default')
                }

            }
        })
    })

    function exportCategoriesExcel($btn) {
        $btn = $btn && $btn.length ? $btn : $('#btn-export-categories');
        if (!$btn.length || $btn.data('exporting')) {
            return;
        }
        $btn.data('exporting', true).addClass('disabled').attr('aria-disabled', 'true');

        const payload = buildBasePayload();
        payload.file_type = 'excel';
        payload.separator = 'coma';

        const baseUrl = (typeof options !== 'undefined' && options.url)
            ? options.url
            : '';
        const exportUrl = baseUrl.replace(/\/$/, '') + '/categories/export';
        const failMsg = (options && options.lang && options.lang.cmp_categorias && options.lang.cmp_categorias.lang && options.lang.cmp_categorias.lang.error_export)
            ? options.lang.cmp_categorias.lang.error_export
            : 'Export failed';

        fetch(exportUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': '*/*',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload),
        })
            .then(async res => {
                const contentType = (res.headers.get('Content-Type') || '').toLowerCase();

                if (!res.ok || contentType.indexOf('application/json') !== -1 || contentType.indexOf('text/html') !== -1) {
                    let message = failMsg;
                    try {
                        const error = await res.json();
                        if (error && error.message) {
                            message = error.message;
                        }
                    } catch (parseErr) {
                        // keep default
                    }
                    if (typeof swal === 'function') {
                        swal({ title: message, icon: 'error' });
                    } else {
                        window.alert(message);
                    }
                    throw new Error(message);
                }

                const blob = await res.blob();
                if (!blob || blob.size < 32) {
                    throw new Error(failMsg);
                }

                const url = window.URL.createObjectURL(blob);
                let fileName = 'categories_list.xlsx';
                const contentDisposition = res.headers.get('Content-Disposition');
                if (contentDisposition && contentDisposition.indexOf('filename=') !== -1) {
                    fileName = contentDisposition
                        .split('filename=')[1]
                        .replace(/["']/g, '')
                        .trim();
                }

                const a = document.createElement('a');
                a.href = url;
                a.download = fileName;
                document.body.appendChild(a);
                a.click();
                a.remove();
                window.setTimeout(function () {
                    window.URL.revokeObjectURL(url);
                }, 1000);
            })
            .catch(function (err) {
                console.log('Download error:', err && err.message ? err.message : err);
            })
            .finally(function () {
                $btn.data('exporting', false).removeClass('disabled').removeAttr('aria-disabled');
            });
    }

    window.fvExportCategories = function () {
        exportCategoriesExcel($('#btn-export-categories'));
    };

    $(document).on('click', '#btn-export-categories, .fv-categories-export-btn, #btn-export', function (e) {
        e.preventDefault();
        exportCategoriesExcel($(this));
    });

    function getSelectedCategoriesIds() {
        const checked = document.querySelectorAll('.checkBoxClass:checked');

        return Array.from(checked).map(function (cb) {
            return cb.value;
        });
    }

    function getFilters() {
        const statusEl = document.querySelector('[name="status"]');
        const searchEl = document.querySelector('[name="prsearch"]');
        return {
            status: statusEl ? statusEl.value : '',
            search: searchEl ? searchEl.value : '',
        };
    }

    function buildBasePayload() {
        const categoryIds = getSelectedCategoriesIds();
        const inputTodos = document.getElementById('input-todos');
        let allChecked = inputTodos ? inputTodos.value : 'todos';

        // Sin filas seleccionadas (o valor inválido): exportar filtros actuales.
        if (!allChecked || (allChecked === 'pagina' && categoryIds.length === 0)) {
            allChecked = 'todos';
        }

        return {
            category_ids: categoryIds,
            all_checked: allChecked,
            filters: getFilters(),
        };
    }

    function fvCategoriesLang() {
        var block = (window.options && options.lang && (
            options.lang['cmp-categorias'] || options.lang.cmp_categorias ||
            options.lang['cmp-categories'] || options.lang.cmp_categories ||
            options.lang['cmp-products'] || options.lang.cmp_products
        )) || {};
        return block.lang || block || {};
    }

    window.eliminarCategoriasSeleccionadas = function () {
        var lang = fvCategoriesLang();
        var categoryIds = getSelectedCategoriesIds();
        var inputTodos = document.getElementById('input-todos');
        var allChecked = inputTodos && inputTodos.value ? inputTodos.value : 'pagina';
        // Seguridad: sin selección explícita no borrar "todos" (eso es solo para export).
        if (allChecked !== 'todos' && categoryIds.length === 0) {
            if (typeof alert_func === 'function') {
                alert_func([lang.seleccione_categoria || '', 'error']);
            }
            return;
        }
        var payload = {
            category_ids: categoryIds,
            all_checked: allChecked === 'todos' ? 'todos' : 'pagina',
            filters: getFilters(),
        };
        if (window.options && options.company_id) {
            payload.company_id = options.company_id;
        }

        swal({
            title: lang.eliminar_categorias_seleccionadas || lang.desea_eliminar_registro || '',
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
                    text: lang.bnBulkDelete || lang.delete || lang.eliminar || 'Delete',
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
            fetch((options.url || '') + '/categories/bulkDelete', {
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
                            title: (res.data && res.data.message) || lang.categories_deleted_success || '',
                            icon: 'success'
                        }).then(function () {
                            window.location.reload();
                        });
                        return;
                    }
                    swal({
                        title: (res.data && res.data.message) || lang.categories_delete_error || '',
                        icon: 'error'
                    });
                })
                .catch(function (error) {
                    swal({
                        icon: 'error',
                        title: lang.categories_delete_error || 'Error',
                        text: error.message
                    });
                });
        });
    };

    // Pop-hover de imágenes de categoría (mismo motor que productos / órdenes).
    (function initCategoryImagePopovers(attempt) {
        attempt = attempt || 0;
        if (typeof window.fvInitOrderImagePopovers === 'function') {
            window.fvInitOrderImagePopovers(document);
            return;
        }
        if (typeof window.jQuery !== 'undefined'
            && window.jQuery.fn
            && typeof window.jQuery.fn.popover === 'function') {
            var $els = window.jQuery('[data-toggle="popover-hover"]');
            if (!$els.length) {
                return;
            }
            $els.each(function () {
                var $t = window.jQuery(this);
                if ($t.data('bs.popover')) {
                    try { $t.popover('dispose'); } catch (e) { /* ignore */ }
                }
            });
            var tpl = '<div class="popover fv-popover-img-preview" role="tooltip"><div class="arrow"></div><div class="popover-body"></div></div>';
            $els.popover({
                html: true,
                trigger: 'hover focus',
                placement: 'right',
                container: 'body',
                template: tpl,
                title: '',
                sanitize: false,
                content: function () {
                    var $el = window.jQuery(this);
                    var img = String($el.attr('data-img') || '').trim();
                    if (!img || img.indexOf('data:') === 0) {
                        var $thumb = $el.find('img').first();
                        if ($thumb.length) {
                            var liveSrc = String($thumb.attr('src') || '').trim();
                            if (liveSrc && liveSrc.indexOf('data:') !== 0) {
                                img = liveSrc;
                            }
                        }
                    }
                    if (!img) {
                        return '';
                    }
                    return window.jQuery('<div class="fv-popover-img-preview__inner"/>').append(
                        window.jQuery('<img>', {
                            src: img,
                            alt: '',
                            class: 'fv-popover-img-preview__photo',
                            draggable: false
                        })
                    );
                }
            }).on('inserted.bs.popover', function () {
                var inst = window.jQuery(this).data('bs.popover');
                if (inst && typeof inst.getTipElement === 'function') {
                    var tip = inst.getTipElement();
                    if (tip) {
                        tip.classList.add('fv-popover-img-preview');
                    }
                }
            });
            return;
        }
        if (attempt < 120) {
            setTimeout(function () { initCategoryImagePopovers(attempt + 1); }, 50);
        }
    })(0);

    // Re-activar popover tras recargas AJAX del listado CRUD.
    $(document).on('draw.dt ajaxComplete', function () {
        if (typeof window.fvInitOrderImagePopovers === 'function') {
            window.fvInitOrderImagePopovers(document);
        }
    });
});
