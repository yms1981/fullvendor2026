$(function () {
    'use strict';

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function getPendingUserIds() {
        var ids = [];
        $('#form_add input[name="pending_user_ids[]"]').each(function () {
            var id = parseInt($(this).val(), 10) || 0;
            if (id > 0) {
                ids.push(id);
            }
        });
        return ids;
    }

    function refreshAssignedUsersEmptyState() {
        var $tbody = $('#fv-assigned-users-tbody');
        if (!$tbody.length) {
            return;
        }
        var hasRows = $tbody.find('tr.fv-assigned-users-data-row').length > 0;
        $tbody.find('.fv-assigned-users-empty-row').toggleClass('d-none', hasRows);
    }

    function refreshAddUserButtonState() {
        var $card = $('.fv-assigned-users-card');
        if (!$card.length || $card.attr('data-assign-pending') !== '1') {
            return;
        }
        var available = parseInt($card.attr('data-available-users') || '0', 10) || 0;
        var pendingCount = getPendingUserIds().length;
        var $btn = $card.find('.fv-assigned-users-card__add-btn');
        $btn.prop('disabled', available <= 0 || pendingCount >= available);
    }

    function addPendingAssignedUser(userId, name, email) {
        userId = parseInt(userId, 10) || 0;
        if (userId < 1) {
            return false;
        }
        if (getPendingUserIds().indexOf(userId) !== -1) {
            return false;
        }

        var $formAdd = $('#form_add');
        if (!$formAdd.length) {
            return false;
        }

        $formAdd.append(
            '<input type="hidden" name="pending_user_ids[]" value="' + userId + '" id="pending_user_' + userId + '">'
        );

        var deleteLabel = escapeHtml($('.fv-assigned-users-card').attr('data-label-delete') || '');
        var $row = $(
            '<tr class="fv-assigned-users-data-row fv-assigned-users-pending-row" data-user-id="' + userId + '">' +
                '<td>' + escapeHtml(name) + '</td>' +
                '<td>' + escapeHtml(email) + '</td>' +
                '<td class="fv-assigned-users-table__actions-col text-center">' +
                    '<div class="fv-assigned-users-table__actions fv-crud-row-actions">' +
                        '<button type="button" class="btn fv-crud-btn fv-crud-btn--row btn-danger btn-sm js-remove-pending-assigned-user" ' +
                            'title="' + deleteLabel + '" aria-label="' + deleteLabel + '" data-user-id="' + userId + '">' +
                            '<i class="fa-solid fa-trash-can" aria-hidden="true"></i>' +
                        '</button>' +
                    '</div>' +
                '</td>' +
            '</tr>'
        );

        $('#fv-assigned-users-tbody').append($row);
        $('#warehouseUserPicker .fv-warehouse-user-picker__row[data-user-id="' + userId + '"]').addClass('d-none');
        refreshAssignedUsersEmptyState();
        refreshAddUserButtonState();
        return true;
    }

    $('#addUserForm').on('submit', function (e) {
        e.preventDefault();

        var $form = $(this);
        if ($form.attr('data-assign-pending') === '1') {
            var userId = parseInt(String($('#user_id').val() || ''), 10) || 0;
            if (userId < 1) {
                var msg = String($form.attr('data-msg-select-user-required') || '').trim();
                if (typeof swal === 'function' && msg !== '') {
                    swal({ title: msg, icon: 'warning' });
                }
                return;
            }

            var $pickerRow = $('#warehouseUserPicker .fv-warehouse-user-picker__row[data-user-id="' + userId + '"]');
            var name = $.trim($pickerRow.find('td').eq(1).text());
            var email = $.trim($pickerRow.find('td').eq(2).text());

            if (!addPendingAssignedUser(userId, name, email)) {
                return;
            }

            $('#addUserModal').modal('hide');
            return;
        }

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: $form.serialize(),
            success: function (response) {
                var title = response.message ?? 'Creación exitosa!';
                swal({
                    title: title,
                    icon: 'success'
                }).then(function () {
                    location.reload();
                });
            },
            error: function (response) {
                var title = response.responseJSON?.message ?? 'Ocurrió un error';
                var errors = response.responseJSON?.errors;
                var errorsMessages = Object.values(errors || {})
                    .reduce(function (acc, curr) { return acc.concat(curr); }, [])
                    .join('\n') || null;

                swal({
                    title: title,
                    text: errorsMessages,
                    icon: 'error'
                });
            }
        });
    });

    $(document).on('click', '.js-remove-pending-assigned-user', function (e) {
        e.preventDefault();
        var userId = parseInt($(this).attr('data-user-id') || 0, 10) || 0;
        if (userId < 1) {
            return;
        }
        $('#pending_user_' + userId).remove();
        $(this).closest('tr.fv-assigned-users-pending-row').remove();
        $('#warehouseUserPicker .fv-warehouse-user-picker__row[data-user-id="' + userId + '"]').removeClass('d-none');
        refreshAssignedUsersEmptyState();
        refreshAddUserButtonState();
    });
});
