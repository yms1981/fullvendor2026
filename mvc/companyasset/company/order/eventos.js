/**
 * Eventos de la lista de órdenes (company/orders).
 * La exportación Excel vive en export.js.
 * Los datepickers de filtro los inicializa fv-company-datepicker.js (formato del perfil).
 */
$(function () {
    $(document).on('click', '#list .sort', function (e) {
        e.preventDefault();

        var orderSort = $(this).data('sort') || 'updated';

        $('#order_sort').val(orderSort);
        $('#sort_direction').val($(this).data('sort_direction') || 'desc');
        $('#form-buscar-filtro').submit();
    });
});
