/**
 * DataGrid cliente para CRUD del menú Configuración (orden, búsqueda, paginación, edición inline).
 */
(function (window, $) {
    'use strict';

    if (!$) {
        return;
    }

    function collectNonSortableTargets($table) {
        var targets = [];
        $table.find('thead tr:last-child th').each(function (index) {
            var $th = $(this);
            if (
                $th.hasClass('fv-crud-select-all-column')
                || $th.hasClass('fv-crud-actions-column')
                || $th.hasClass('crud-list-actions-column')
                || $th.hasClass('crud-list-status-column')
                || $th.hasClass('fv-config-crud-order-column')
                || $th.hasClass('fv-dt-no-sort')
                || $th.data('field') === 'actions'
                || $th.data('field') === 'status'
                || $th.data('field') === 'isDefault'
                || $th.data('field') === 'order_discount'
                || $th.data('field') === 'order_net_discount'
                || $th.data('field') === 'can_change_price'
                || $th.data('field') === 'can_send_catalog'
                || $th.data('field') === 'can_create_customer'
            ) {
                targets.push(index);
            }
        });
        return targets;
    }

    function styleControls($wrapper) {
        var $filterInput = $wrapper.find('div.dataTables_filter input');
        if ($filterInput.length) {
            $filterInput.addClass('form-control form-control-sm');
            $filterInput.attr('placeholder', $filterInput.attr('placeholder') || '');
        }
        var $lengthSelect = $wrapper.find('div.dataTables_length select');
        if ($lengthSelect.length) {
            $lengthSelect.addClass('custom-select custom-select-sm form-control form-control-sm');
        }
    }

    function computeScrollHeight() {
        var offset = 320;
        var minHeight = 420;
        var available = window.innerHeight - offset;
        return Math.max(minHeight, available) + 'px';
    }

    function applyGridLayout($table) {
        var $wrap = $table.closest('.fv-config-crud-datagrid-wrap--active');
        if (!$wrap.length) {
            return;
        }
        var height = computeScrollHeight();
        $wrap.find('.fv-modern-grid__scroll').css({
            'max-height': height,
            'min-height': height,
        });
    }

    function notifyInline(message, icon) {
        if (typeof swal === 'function') {
            swal({ title: message, icon: icon || 'info', timer: icon === 'success' ? 1800 : undefined });
            return;
        }
        if (message) {
            window.alert(message);
        }
    }

    function bindInlineEdit($table, inlineEdit) {
        if (!inlineEdit || !inlineEdit.updateUrlBase || !inlineEdit.editableFields || !inlineEdit.editableFields.length) {
            return;
        }

        var messages = inlineEdit.messages || {};
        var activeEditor = null;

        function finishEditor($cell, save) {
            if (!activeEditor || !activeEditor.$cell || !activeEditor.$cell.is($cell)) {
                return;
            }
            var $input = activeEditor.$input;
            var previous = activeEditor.previous;
            var field = activeEditor.field;
            var itemId = activeEditor.itemId;
            activeEditor = null;

            if (!save) {
                $cell.text(previous);
                return;
            }

            var nextValue = $.trim($input.val());
            if (nextValue === '') {
                notifyInline(messages.empty || messages.error, 'warning');
                $cell.text(previous);
                return;
            }
            if (nextValue === previous) {
                $cell.text(previous);
                return;
            }

            $cell.addClass('fv-dt-editable--saving').text(nextValue);
            var payload = {};
            payload[field] = nextValue;

            $.ajax({
                url: inlineEdit.updateUrlBase + encodeURIComponent(itemId),
                type: 'POST',
                dataType: 'json',
                data: payload,
            }).done(function (response) {
                $cell.removeClass('fv-dt-editable--saving');
                var msg = (response && response.message) ? response.message : (messages.saved || '');
                if (msg) {
                    notifyInline(msg, 'success');
                }
            }).fail(function (xhr) {
                $cell.removeClass('fv-dt-editable--saving').text(previous);
                var msg = messages.error || '';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                notifyInline(msg, 'error');
            });
        }

        function openEditor($cell) {
            if ($cell.hasClass('fv-dt-editable--saving') || $cell.find('input.fv-dt-inline-input').length) {
                return;
            }
            if (activeEditor) {
                finishEditor(activeEditor.$cell, false);
            }

            var field = $cell.data('inline-field') || $cell.data('field');
            var itemId = $cell.data('item-id');
            if (!field || !itemId) {
                return;
            }

            var previous = $.trim($cell.text());
            var $input = $('<input type="text" class="form-control form-control-sm fv-dt-inline-input">').val(previous);
            $cell.empty().append($input);
            $input.focus().select();

            activeEditor = {
                $cell: $cell,
                $input: $input,
                previous: previous,
                field: field,
                itemId: itemId,
            };

            $input.on('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    finishEditor($cell, true);
                } else if (event.key === 'Escape') {
                    event.preventDefault();
                    finishEditor($cell, false);
                }
            });
            $input.on('blur', function () {
                window.setTimeout(function () {
                    if (activeEditor && activeEditor.$cell.is($cell)) {
                        finishEditor($cell, true);
                    }
                }, 120);
            });
        }

        $table.on('dblclick', 'td.fv-dt-editable', function () {
            openEditor($(this));
        });
    }

    window.FvConfigCrudDatagrid = {
        init: function (selector, options) {
            options = options || {};
            var $table = $(selector);
            if (!$table.length || typeof $.fn.DataTable !== 'function') {
                return null;
            }
            if ($.fn.DataTable.isDataTable($table)) {
                return $table.DataTable();
            }

            var lang = options.language || {};
            var allLabel = options.allLabel || 'All';
            var nonSortable = collectNonSortableTargets($table);

            $('#products-loader').removeClass('show hiding').hide();

            var dt = $table.DataTable({
                order: [],
                pageLength: parseInt(options.pageLength, 10) || 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, allLabel]],
                columnDefs: [
                    { orderable: false, targets: nonSortable },
                    { className: 'fv-dt-no-sort', targets: nonSortable },
                ],
                language: lang,
                dom: "<'row fv-dt-toolbar align-items-center'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                    "rt" +
                    "<'row align-items-center'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                responsive: false,
                processing: false,
                autoWidth: true,
                stateSave: false,
                deferRender: true,
            });

            var $wrapper = $table.closest('.dataTables_wrapper');
            styleControls($wrapper);
            applyGridLayout($table);
            bindInlineEdit($table, options.inlineEdit || null);

            // Refuerzo: algunos módulos no filtraban con el input nativo de DataTables.
            var $filterInput = $wrapper.find('div.dataTables_filter input');
            if ($filterInput.length) {
                $filterInput.off('input.fvDtSearch keyup.fvDtSearch').on('input.fvDtSearch keyup.fvDtSearch', function () {
                    dt.search(String(this.value || '')).draw();
                });
            }

            $(window).on('resize.fvConfigCrudDatagrid', function () {
                applyGridLayout($table);
            });

            $table.on('draw.dt', function () {
                var api = $table.DataTable();
                var info = api.page.info();
                var $counter = $('#countprd');
                if ($counter.length) {
                    $counter.text(info.recordsDisplay);
                }
                if (window.FvConfigCrudDefaultSwitch && typeof window.FvConfigCrudDefaultSwitch.renumberOrderAvatars === 'function') {
                    window.FvConfigCrudDefaultSwitch.renumberOrderAvatars($table);
                }
            });

            $wrapper.find('.dataTables_processing').hide();

            return dt;
        },

        reload: function () {
            window.location.reload();
        },
    };

    window.fvReloadConfigCrudGrid = function () {
        if (window.FvConfigCrudDatagrid && typeof window.FvConfigCrudDatagrid.reload === 'function') {
            window.FvConfigCrudDatagrid.reload();
            return;
        }
        window.location.reload();
    };
})(window, window.jQuery);
