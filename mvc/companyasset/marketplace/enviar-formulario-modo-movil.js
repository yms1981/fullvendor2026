
const select_modo_movil = $('.hover_category.movil .select_option')
const form_movil = document.querySelector("#form-movil-buscar")

select_modo_movil.on('change', function(e) {    
    form_movil.submit()
});



