/**
 * Select2 AJAX para selector de productos en modales de catálogo (agregar / editar).
 * Al escribir nombre o SKU muestra coincidencias desde products/searchProductSelect2.
 */
(function (window, $) {
    'use strict';

    if (!$) {
        return;
    }

    function mapAjaxItems(data, params) {
        params.page = params.page || 1;
        var items = Array.isArray(data && data.items) ? data.items : [];
        var results = items.map(function (item) {
            var id = item.id != null ? item.id : item.pro_id;
            var text = item.text || item.name_completo || item.name || String(id);
            return {
                id: id,
                text: text
            };
        }).filter(function (row) {
            return row.id != null && String(row.id) !== '' && Number(row.id) > 0;
        });

        return {
            results: results,
            pagination: {
                more: (params.page * 15) < (data.total_count || 0)
            }
        };
    }

    function ensureSelectedOptions($select, selectedIds) {
        if (!Array.isArray(selectedIds) || selectedIds.length === 0) {
            return;
        }

        selectedIds.forEach(function (rawId) {
            var id = String(rawId);
            if (!id) {
                return;
            }
            var exists = false;
            $select.find('option').each(function () {
                if (String(this.value) === id) {
                    exists = true;
                    return false;
                }
            });
            if (exists) {
                return;
            }
            $select.append(new Option(id, id, true, true));
        });
    }

    window.initCatalogProductsSelect2 = function ($select, $modal, selectedIds) {
        if (!$select || !$select.length || typeof $.fn.select2 === 'undefined') {
            return;
        }

        if ($select.prop('disabled')) {
            return;
        }

        var searchUrl = String($select.data('search-url') || '');
        if (searchUrl === '') {
            return;
        }

        if ($select.hasClass('select2-hidden-accessible')) {
            $select.select2('destroy');
        }

        ensureSelectedOptions($select, selectedIds);

        $select.select2({
            dropdownParent: $modal && $modal.length ? $modal : $(document.body),
            width: '100%',
            placeholder: $select.data('placeholder') || '',
            allowClear: true,
            ajax: {
                url: searchUrl,
                dataType: 'json',
                delay: 300,
                data: function (params) {
                    return {
                        search: params.term || '',
                        q: params.term || '',
                        page: params.page || 1
                    };
                },
                processResults: mapAjaxItems,
                cache: true,
                error: function () {
                    // Select2 muestra "No results" si processResults no se llama; evitar spinner eterno.
                }
            },
            minimumInputLength: 1,
            language: {
                inputTooShort: function () {
                    return String($select.data('msg-input-too-short') || '');
                },
                searching: function () {
                    return String($select.data('msg-searching') || '...');
                },
                noResults: function () {
                    return String($select.data('msg-no-results') || '');
                }
            }
        });

        if (Array.isArray(selectedIds) && selectedIds.length) {
            $select.val(selectedIds.map(String)).trigger('change');
        }
    };
})(window, window.jQuery);
