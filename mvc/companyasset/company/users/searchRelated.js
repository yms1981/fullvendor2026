$(document).ready(function () {
    const $roleSelect = $('#role');
    const $searchContainer = $('#role-search-container');
    const managerProfile = $('#manager-profile');
    const $searchLabel = $('#role-search-label');
    const $searchInput = $('#role-search-input');
    const $searchResults = $('#role-search-results');
    const $relatedId = $('#related_id');
    const $sellerFields = $('#seller-fields');
    const $warehouseWrap = $('#form-warehouse-select-wrap');
    const $warehouseSelect = $('#form-warehouse-select');
    const $customerSearchWrap = $('#form-customer-search-wrap');
    const L = window.companyUserFormLang || {};

    let currentRole = '';
    let searchUrl = '';

    function t(key, fallback) {
        const value = L[key];
        if (value === undefined || value === null || String(value).trim() === '') {
            return fallback || '';
        }
        return String(value);
    }

    function clearRelated() {
        $relatedId.val('');
        $searchInput.val('');
        $searchResults.empty();
        if ($warehouseSelect.length) {
            $warehouseSelect.val('');
        }
    }

    $roleSelect.on('change', function () {
        currentRole = String($(this).val() || '');

        clearRelated();

        $searchContainer.hide();
        $warehouseWrap.addClass('d-none');
        $customerSearchWrap.addClass('d-none');
        managerProfile.addClass('d-none');
        $sellerFields.hide();

        if (currentRole === 'customer') {
            searchUrl = options.url + '/customers/searchCustomer';
            $searchLabel.text(t('user_modal_search_customer', 'Buscar Cliente') + ':');
            $customerSearchWrap.removeClass('d-none');
            $searchContainer.show();
        } else if (currentRole === 'warehouse') {
            $warehouseWrap.removeClass('d-none');
            $searchContainer.show();
        } else if (currentRole === 'seller') {
            $sellerFields.show();
        } else if (currentRole === 'manager') {
            managerProfile.removeClass('d-none');
        }
    });

    $warehouseSelect.on('change', function () {
        $relatedId.val(String($(this).val() || ''));
    });

    let searchTimeout;
    $searchInput.on('keyup', function () {
        const term = $(this).val().trim();
        clearTimeout(searchTimeout);

        if (term.length < 2) {
            $searchResults.empty();
            return;
        }

        searchTimeout = setTimeout(() => {
            $.ajax({
                url: searchUrl,
                method: 'GET',
                data: { term: term },
                dataType: 'json',
                success: function (data) {
                    $searchResults.empty();

                    if (!Array.isArray(data) || data.length === 0) {
                        $searchResults.append(
                            '<div class="search-item no-results">' +
                            t('user_modal_search_no_results', 'No se encontraron resultados') +
                            '</div>'
                        );
                        return;
                    }

                    const itemIdField = currentRole === 'customer' ? 'customer_id' : 'id';

                    data.forEach(item => {
                        const id = item[itemIdField];
                        $searchResults.append(`
                            <div class="search-item" data-id="${id}">
                                ${item.name}
                            </div>
                        `);
                    });
                }
            });
        }, 300);
    });

    $searchResults.on('click', '.search-item', function () {
        if ($(this).hasClass('no-results')) return;

        const name = $(this).text().trim();
        const id = $(this).data('id');

        $searchInput.val(name);
        $relatedId.val(id);
        $searchResults.empty();
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('#role-search-container').length) {
            $searchResults.empty();
        }
    });

    if ($roleSelect.length && String($roleSelect.val() || '').length) {
        $roleSelect.trigger('change');
    }
});
