
$(document).ready(function () {
    console.log($(".js-example-matcher-start"))
    $(".js-example-matcher-start").select2({
        dropdownParent: $("#myselect2"),
        width: '100%'
    });
})