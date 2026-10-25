$(document).ready(function () {
    var urlBase = (typeof options !== 'undefined' && options.url) ? options.url : '';

    $('#modal_add_profile').on('show.bs.modal', function () {
        var modalBody = $('#modal_add_profile_body');
        modalBody.html('<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only"></span></div></div>');

        $.ajax({
            url: urlBase + '/sellerprofile/getAddForm',
            type: 'GET',
            dataType: 'html',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (response) {
                modalBody.html(response);
            },
            error: function () {
                modalBody.html('<div class="alert alert-danger"></div>');
            }
        });
    });

    $('#modal_add_profile').on('hidden.bs.modal', function () {
        $('#modal_add_profile_body').html('<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only"></span></div></div>');
    });

    $(document).on('submit', '#addProfileForm', function (e) {
        e.preventDefault();

        var form = $(this);
        var submitButton = form.find('[type="submit"]').first();
        var originalText = submitButton.html();
        var loadingText = (window.validationLang && window.validationLang.loading) || '';

        submitButton.prop('disabled', true).html(
            '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + loadingText
        );

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function (response) {
                if (response && (response.success || response.redirect)) {
                    $('#modal_add_profile').modal('hide');
                    window.location.reload();
                    return;
                }
                submitButton.prop('disabled', false).html(originalText);
            },
            error: function (xhr) {
                if (xhr.responseText && xhr.responseText.indexOf('<!DOCTYPE') !== -1) {
                    $('#modal_add_profile').modal('hide');
                    window.location.reload();
                    return;
                }
                submitButton.prop('disabled', false).html(originalText);
            }
        });
    });
});
