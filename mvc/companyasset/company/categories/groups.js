(function ($) {
    'use strict';

    function handleSuccess(resp) {
        if (resp && resp.message && typeof toastr !== 'undefined') {
            toastr.success(resp.message);
        }
        window.location.reload();
    }

    function handleError(xhr) {
        var msg = '';
        try {
            var json = xhr.responseJSON || JSON.parse(xhr.responseText || '{}');
            msg = json.message || (json.error && json.error.message) || '';
            if (typeof msg !== 'string') {
                msg = JSON.stringify(msg);
            }
        } catch (e) {
            msg = '';
        }
        if (!msg) {
            msg = 'Error ' + (xhr.status || '');
        }
        if (typeof toastr !== 'undefined') {
            toastr.error(msg);
        } else {
            window.alert(msg);
        }
    }

    function postMultipart($form) {
        var formData = new FormData($form[0]);
        return $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json'
        });
    }

    $(document).on('change', '[data-fv-cg-preview]', function () {
        var input = this;
        var $picker = $(input).closest('.fv-cg__photo-picker');
        var file = input.files && input.files[0];
        $picker.find('img.fv-cg-preview-img').remove();
        if (!file) {
            $picker.removeClass('has-preview');
            return;
        }
        var url = URL.createObjectURL(file);
        var $img = $('<img class="fv-cg-preview-img" alt="">').attr('src', url);
        $picker.append($img).addClass('has-preview');
    });

    $(document).on('submit', '#form-add-group, .form-add-subgroup', function (e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('button[type="submit"]').prop('disabled', true);
        postMultipart($form)
            .done(handleSuccess)
            .fail(handleError)
            .always(function () {
                $btn.prop('disabled', false);
            });
    });

    $(document).on('change', '[data-fv-cg-upload]', function () {
        var input = this;
        var file = input.files && input.files[0];
        var url = $(input).data('fv-cg-upload');
        if (!file || !url) {
            return;
        }
        var formData = new FormData();
        formData.append('image', file);
        $.ajax({
            url: url,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json'
        }).done(handleSuccess).fail(handleError);
    });

    $(document).on('click', '.fv-delete-group, .fv-delete-subgroup', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var confirmMsg = $btn.data('confirm') || '';
        if (confirmMsg && !window.confirm(confirmMsg)) {
            return;
        }
        $.ajax({
            url: $btn.data('url'),
            method: 'POST',
            dataType: 'json'
        }).done(handleSuccess).fail(handleError);
    });
})(jQuery);
