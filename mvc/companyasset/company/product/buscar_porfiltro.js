function buscar_porfiltro() {
    // Mostrar loader
    if ($('#products-loader').length) {
        $('#products-loader').fadeIn(200);
    }
    $('#form-buscar-filtro').submit();
}