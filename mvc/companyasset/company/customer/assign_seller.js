const get_selected_customer = () => {
    return d.querySelectorAll("input.checkBoxClass[type=checkbox]:checked")    
}

const asign_seller = async () => {
    
    const {lang} = {...options.lang.cmp_cliente}
    
    if (get_selected_customer().length<=0) {
        alert_func([lang.validate_oncustomer, "error", "#DD6B55"]);        
        return;
    }
    comp.modalseller_asign.modal("show")
}

const remove_seller = async () => {
    
    const {lang} = {...options.lang.cmp_cliente}
    
    if (get_selected_customer().length<=0) {
        alert_func([lang.validate_oncustomer, "error", "#DD6B55"]);
        return;
    }
    comp.modalseller_remove.modal("show")
}

const subtmit_asignseller = async (e) => {
    e.preventDefault()
    
    console.log(options)
    const formdata_filter = new FormData(d.getElementById("form-buscar-filtro"))
    const form_datasellerasign = new FormData(e.srcElement)

    const {lang_categorias} = {...options.lang.mdl}

    console.log(options.lang)
    disable_button()
    
    // union of two form data
    for (const [key, value] of form_datasellerasign.entries()) {
        formdata_filter.append(key, value);
    }
    
    get_selected_customer().forEach(function (element, key, parent) {
        formdata_filter.append('customers_id[]', element.value);
       
    })

    fetch(e.srcElement.action, {
        method: 'POST',
        body: formdata_filter
    })
    .then(response => response.json())
    .then(data => {
        
        if (!data.success) {
            
            alert_func([data.msg, 'error'])
            enable_button()
            return
        }
       
        location.reload()
    })
}

comp.form_asignRemoveseller.forEach(function (element, key, parent) {

    element.addEventListener("submit",subtmit_asignseller)

})








