$(document).on('click', '.toggle-password', function() {
    $(this).toggleClass("fa-eye fa-eye-slash");
    var input = $(this).next()
    input.attr('type') === 'password' ? input.attr('type', 'text') : input.attr('type', 'password')
});


