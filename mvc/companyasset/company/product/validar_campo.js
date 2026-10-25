function validar_campo(event, input) {
    var $input = $(input);
    var nombre = $input.val() || '';

    // En ubicación se permiten comas/punto y coma (varias ubicaciones en un producto).
    var inputId = ($input.attr('id') || '').toLowerCase();
    var inputName = ($input.attr('name') || '').toLowerCase();
    if (inputId === 'location' || inputName === 'locations') {
        return;
    }

    if (nombre.indexOf(',') > -1 || nombre.indexOf(';') > -1) {
        nombre = nombre.replace(/,/g, '').replace(/;/g, '');
        $input.val(nombre);

        var msg = (window.fvLang && window.fvLang.no_comma_semicolon)
            ? window.fvLang.no_comma_semicolon
            : 'No se permiten caracteres " , "  y  " ; ".';
        $input.next('.container_alert').html(
            '<div class="error_msgbox show_msgbox">' + msg + '</div>'
        );

        setTimeout(function () {
            $input.next('.container_alert').html('');
        }, 2000);
    }
}
