$(function () {
    function fvCustomerEmailLooksValid(value) {
        var v = String(value || '').trim();
        if (v === '') {
            return false;
        }
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
    }

    /**
     * Los campos de ubicación se deshabilitan en cascada (país → estado → ciudad).
     * Los inputs disabled no entran en FormData; forzamos el envío de sus valores.
     */
    function appendCustomerAddressFieldsToFormData(form, formData) {
        if (!form || !formData) {
            return;
        }
        var names = [
            'commercial_address',
            'commercial_delivery_address',
            'commercial_country',
            'commercial_state',
            'commercial_state_id',
            'commercial_city',
            'commercial_city_id',
            'commercial_zip_code',
            'commercial_zone',
            'dispatch_delivery_address',
            'dispatch_address',
            'dispatch_country',
            'dispatch_state',
            'dispatch_state_id',
            'dispatch_city',
            'dispatch_city_id',
            'dispatch_zip_code',
            'dispatch_zone',
            'dispatch_shipping_notes'
        ];
        names.forEach(function (name) {
            var el = form.querySelector('[name="' + name + '"]');
            if (el) {
                formData.set(name, el.value || '');
            }
        });
    }

    $(document).on('submit', '#form_add', function (e) {
        const form = document.getElementById('form_add');
        if (form && form.getAttribute('data-user-create') === '1') {
            return;
        }
        if (form && form.getAttribute('data-plan-limit-blocked') === '1') {
            e.preventDefault();
            if (typeof swal === 'function') {
                var blockedMsg = form.getAttribute('data-plan-limit-message') || '';
                swal({ title: blockedMsg || 'Limit reached', icon: 'warning' });
            }
            return;
        }
        const msgInvalid = form ? form.getAttribute('data-msg-email-invalid') : null;
        const msgRequired = form ? form.getAttribute('data-msg-email-required') : null;
        if (form && msgInvalid && msgRequired) {
            const emailEl = form.querySelector('input[name="email"]');
            if (emailEl) {
                const raw = String(emailEl.value || '').trim();
                if (raw === '') {
                    e.preventDefault();
                    if (typeof swal === 'function') {
                        swal({ title: msgRequired, icon: 'error' });
                    }
                    return;
                }
                if (!fvCustomerEmailLooksValid(raw)) {
                    e.preventDefault();
                    if (typeof swal === 'function') {
                        swal({ title: msgInvalid, icon: 'error' });
                    }
                    return;
                }
            }
        }

        e.preventDefault()

        const url = form.getAttribute('action');
        const formData = new FormData(form);
        const phoneEl = form.querySelector('#customer_phone');
        const cellEl = form.querySelector('#customer_cell_phone');
        if (phoneEl) {
            formData.set('phone', (phoneEl.value || '').replace(/\D/g, ''));
        }
        if (cellEl) {
            formData.set('cell_phone', (cellEl.value || '').replace(/\D/g, ''));
        }
        const warehouseStreetNum = form.querySelector('#warehouse_street_number');
        if (warehouseStreetNum) {
            formData.set('street_number', (warehouseStreetNum.value || '').replace(/\D/g, ''));
        }

        if (form.querySelector('[name="commercial_state"]') || form.querySelector('[name="dispatch_state"]')) {
            appendCustomerAddressFieldsToFormData(form, formData);
        }

        submitForm(url, formData);
    });

    function submitForm(url, formData) {
        $.ajax({
            url: url,
            type: 'post',
            dataType: 'json',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                const defaultSuccess = (typeof window.FORM_ADD_MESSAGES !== 'undefined' && window.FORM_ADD_MESSAGES.success)
                    ? window.FORM_ADD_MESSAGES.success
                    : ((window.fvUiText && window.fvUiText('success_created')) || (window.fvUiText && window.fvUiText('success_saved')) || '');
                let title = response.message ?? defaultSuccess;
                swal({
                    title: title,
                    icon: 'success'
                }).then(() => {
                    if (response && response.urlredirect) {
                        window.location.href = response.urlredirect;
                    } else {
                        window.history.go(-1);
                    }
                });
            },
            error: function(response) {
                const defaultError = (typeof window.FORM_ADD_MESSAGES !== 'undefined' && window.FORM_ADD_MESSAGES.error)
                    ? window.FORM_ADD_MESSAGES.error
                    : ((window.fvUiText && window.fvUiText('generic_error')) || (window.fvUiText && window.fvUiText('error_occurred')) || '');
                let title = response.responseJSON?.message ?? defaultError;
                const errors = response.responseJSON.errors;

                const errorsMessages = Object.values(errors || {})
                    .reduce((acc, curr) => acc.concat(curr), [])
                    .join('\n') || null;

                swal({
                    title: title,
                    text: errorsMessages,
                    icon: 'error'
                });
            }
        });
    }
});
