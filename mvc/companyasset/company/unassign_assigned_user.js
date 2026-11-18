(function ($) {
    'use strict';

    function readConfirmMessage($btn) {
        var fromBtn = String($btn.attr('data-confirm-msg') || '').trim();
        if (fromBtn !== '') {
            return fromBtn;
        }

        var fromCard = String($btn.closest('.fv-assigned-users-card').attr('data-confirm-unassign') || '').trim();
        if (fromCard !== '') {
            return fromCard;
        }

        if (window.FV_ASSIGNED_USERS_I18N && window.FV_ASSIGNED_USERS_I18N.confirm_unassign_user) {
            return String(window.FV_ASSIGNED_USERS_I18N.confirm_unassign_user).trim();
        }

        return '';
    }

    function i18n(key, fallback) {
        if (window.FV_ASSIGNED_USERS_I18N && window.FV_ASSIGNED_USERS_I18N[key]) {
            return String(window.FV_ASSIGNED_USERS_I18N[key]);
        }
        if (window.FV_PORTAL_PASSWORD && window.FV_PORTAL_PASSWORD.i18n && window.FV_PORTAL_PASSWORD.i18n[key]) {
            return String(window.FV_PORTAL_PASSWORD.i18n[key]);
        }
        return fallback || '';
    }

    function swalFire(options) {
        if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
            return Swal.fire(options);
        }
        return Promise.resolve({ isConfirmed: false });
    }

    $(function () {
        $(document).on('click', '.js-unassign-assigned-user', function (e) {
            e.preventDefault();

            var $btn = $(this);
            var userId = parseInt($btn.attr('data-user-id') || $btn.data('userId') || 0, 10) || 0;
            var warehouseId = parseInt($btn.attr('data-warehouse-id') || $btn.data('warehouseId') || 0, 10) || 0;
            var customerId = parseInt($btn.attr('data-customer-id') || $btn.data('customerId') || 0, 10) || 0;
            var url = String($btn.attr('data-unassign-url') || $btn.data('unassignUrl') || '').trim();
            var confirmMsg = readConfirmMessage($btn);
            var entityId = customerId > 0 ? customerId : warehouseId;

            if (!userId || !entityId || url === '') {
                return;
            }

            var proceed = function () {
                var payload = { user_id: userId };
                if (customerId > 0) {
                    payload.customer_id = customerId;
                } else {
                    payload.warehouse_id = warehouseId;
                }

                $.ajax({
                    url: url,
                    type: 'POST',
                    dataType: 'json',
                    data: payload,
                    success: function (response) {
                        var title = (response && response.message) ? response.message : 'OK';
                        swalFire({
                            title: title,
                            icon: 'success',
                            confirmButtonText: i18n('btn_accept', 'OK'),
                        }).then(function () {
                            location.reload();
                        });
                    },
                    error: function (xhr) {
                        var body = xhr.responseJSON || {};
                        var title = body.message || ((window.fvUiText && window.fvUiText('error_occurred')) || i18n('error', ''));
                        var errors = body.errors;
                        var errorsMessages = Object.values(errors || {})
                            .reduce(function (acc, curr) { return acc.concat(curr); }, [])
                            .join('\n') || null;

                        swalFire({
                            title: title,
                            text: errorsMessages || '',
                            icon: 'error',
                            confirmButtonText: i18n('btn_accept', 'OK'),
                        });
                    },
                });
            };

            swalFire({
                icon: 'warning',
                text: confirmMsg,
                showCancelButton: true,
                confirmButtonText: i18n('btn_accept', 'OK'),
                cancelButtonText: i18n('cancel', i18n('cancelar', 'Cancel')),
                confirmButtonColor: '#dc2626',
                reverseButtons: true,
            }).then(function (result) {
                if (result && result.isConfirmed) {
                    proceed();
                }
            });
        });
    });
}(jQuery));
