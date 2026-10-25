$(document).ready(function() {
    let userSelected = false;

    if ($('#user_id').val()) {
        userSelected = true;
    }

    $('#user_name').on('input', function() {
        let term = $(this).val();

        if (term.trim() === '') {
            userSelected = false;
            $('#user_id').val(null);
            $('#user_suggestions').empty();
            return;
        }

        if (term.length < 2) {
            $('#user_suggestions').empty();
            return;
        }

        $.ajax({
            url: options.url + '/users/searchUser',
            method: "GET",
            data: {
                term: term
            },
            success: function(data) {
                let suggestions = JSON.parse(data);
                let box = $('#user_suggestions');
                box.empty();

                suggestions.forEach(function(item) {
                    box.append(`<div data-id="${item.id}" data-name="${item.name}">${item.name}</div>`);
                });

                box.show();
            }
        });
    });

    $('#user_suggestions').on('click', 'div', function(e) {
        e.stopPropagation();

        let name = $(this).data('name');
        let id = $(this).data('id');
        $('#user_name').val(name);
        $('#user_id').val(id);
        $('#user_suggestions').empty();
        userSelected = true;
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#user_name, #user_suggestions').length) {
            if (!userSelected) {
                let inputName = $('#user_name');
                inputName.val(null);
                inputName.removeClass('is-valid');
                $('#user_id').val(null);
            }

            $('#user_suggestions').empty();
        }
    });
});
