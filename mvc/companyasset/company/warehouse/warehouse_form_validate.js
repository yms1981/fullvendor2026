(function ($) {
    'use strict';

    function warehouseFormMessages(form) {
        return {
            nameRequired: form.getAttribute('data-msg-name-required') || '',
            countryRequired: form.getAttribute('data-msg-country-required') || '',
        };
    }

    function validateWarehouseForm(form) {
        var msgs = warehouseFormMessages(form);
        var nameEl = form.querySelector('[name="name"]');
        var countryEl = form.querySelector('[name="country_id"]');
        var name = nameEl ? String(nameEl.value || '').trim() : '';
        var countryId = countryEl ? String(countryEl.value || '').trim() : '';

        if (name === '' && msgs.nameRequired) {
            return msgs.nameRequired;
        }
        if (countryId === '' && msgs.countryRequired) {
            return msgs.countryRequired;
        }

        return '';
    }

    function bindWarehouseValidation(formId) {
        $(document).on('submit', formId, function (e) {
            var form = document.getElementById(formId.replace('#', ''));
            if (!form || form.getAttribute('data-fv-warehouse-form') !== '1') {
                return;
            }

            var errorMsg = validateWarehouseForm(form);
            if (errorMsg === '') {
                return;
            }

            e.preventDefault();
            e.stopImmediatePropagation();

            if (typeof swal === 'function') {
                swal({ title: errorMsg, icon: 'error' });
            }
        });
    }

    $(function () {
        bindWarehouseValidation('#form_add');
        bindWarehouseValidation('#form_edit');
    });
}(jQuery));
