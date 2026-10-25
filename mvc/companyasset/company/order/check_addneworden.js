(function () {
    var check_catalog = document.getElementById('type_catalog');
    var check_grid = document.getElementById('type_grid');
    var check_file = document.getElementById('type_file');
    if (!check_catalog || !check_grid || !check_file) return;

check_catalog.addEventListener("click",() => {
    console.log("check_catalog")
    
    check_grid.checked = false
    check_file.checked = false
})

check_grid.addEventListener("click",() => {    
    check_catalog.checked = false
    check_file.checked = false
})

check_file.addEventListener("click",() => {    
    console.log("check_file")
    check_catalog.checked = false
    check_grid.checked = false
})

$('#accordion_addneworder').on('show.bs.collapse', function () {
    const select_customer = $('#customer_search').val()
    // console.log(select_customer)
    // if (!select_customer) {
    //     console.log("ocultado")
    //     $('#accordion_addneworder').collapse('dispose')
    // }

});

})();