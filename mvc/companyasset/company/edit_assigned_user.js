$(function () {
    $(document).on('click', '.js-edit-assigned-user', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var userId = $btn.data('user-id');
        var updateUrl = $btn.data('update-url');
        if (!userId || !updateUrl) {
            return;
        }

        $('#edit_assigned_user_id').val(userId);
        $('#edit_assigned_user_email').val($btn.data('email') || '');
        $('#edit_assigned_user_phone').val($btn.data('phone') || '');
        $('#edit_assigned_user_password').val('');
        $('#editAssignedUserForm').attr('action', updateUrl);
        $('#editAssignedUserModal').modal('show');
    });

    $('#editAssignedUserForm').on('submit', function (e) {
        e.preventDefault();

        var $form = $(this);
        var email = ($('#edit_assigned_user_email').val() || '').trim();
        var payload = {
            name: email,
            email: email,
            phone: ($('#edit_assigned_user_phone').val() || '').trim()
        };
        var password = ($('#edit_assigned_user_password').val() || '').trim();
        if (password !== '') {
            payload.password = password;
        }

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: payload,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (response) {
                var title = (response && response.message) ? response.message : 'OK';
                swal({
                    title: title,
                    icon: 'success'
                }).then(function () {
                    location.reload();
                });
            },
            error: function (xhr) {
                var body = xhr.responseJSON || {};
                var title = body.message || 'Error';
                var errors = body.errors;
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
});
