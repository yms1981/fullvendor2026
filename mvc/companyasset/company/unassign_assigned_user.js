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

    $(function () {
        $(document).on('click', '.js-unassign-assigned-user', function (e) {
            e.preventDefault();

            var $btn = $(this);
            var userId = parseInt($btn.attr('data-user-id') || $btn.data('userId') || 0, 10) || 0;
            var warehouseId = parseInt($btn.attr('data-warehouse-id') || $btn.data('warehouseId') || 0, 10) || 0;
            var url = String($btn.attr('data-unassign-url') || $btn.data('unassignUrl') || '').trim();
            var confirmMsg = readConfirmMessage($btn);

            if (!userId || !warehouseId || url === '') {
                return;
            }

            var proceed = function () {
                $.ajax({
                    url: url,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        user_id: userId,
                        warehouse_id: warehouseId,
                    },
                    success: function (response) {
                        var title = (response && response.message) ? response.message : 'OK';
                        swal({
                            title: title,
                            icon: 'success',
                        }).then(function () {
                            location.reload();
                        });
                    },
                    error: function (xhr) {
                        var body = xhr.responseJSON || {};
                        var title = body.message || ((window.fvUiText && window.fvUiText('error_occurred')) || '');
                        var errors = body.errors;
                        var errorsMessages = Object.values(errors || {})
                            .reduce(function (acc, curr) { return acc.concat(curr); }, [])
                            .join('\n') || null;

                        swal({
                            title: title,
                            text: errorsMessages,
                            icon: 'error',
                        });
                    },
                });
            };

            if (typeof swal !== 'function') {
                if (window.confirm(confirmMsg || 'OK?')) {
                    proceed();
                }
                return;
            }

            swal({
                title: confirmMsg,
                icon: 'warning',
                buttons: true,
                dangerMode: true,
            }).then(function (ok) {
                if (ok) {
                    proceed();
                }
            });
        });
    });
}(jQuery));
