const d = document
const comp = {
    modalseller_asign: $("#modalasignSeller"),
    modalseller_remove: $("#modalremoveSeller"),
    form_filter : d.getElementById("form-buscar-filtro"),
    button_removeseller:d.getElementById("btn-removeseller"),
    button_asignseller:d.getElementById("btn-asignseller"),
    form_asignRemoveseller : d.getElementsByClassName("form-asign-remove-seller"),    
    // select_sellerstoasign: $('#select_multpleseller').select2({    
    //     placeholder: 'Select Seller',
    //     width : '100%',
    //     dropdownParent: $("#select_multpleseller").parent()
    // }),
    // select_sellerstoremove: $('#select_toremoveseller').select2({            
    //     width : '100%',
    //     dropdownParent: $("#select_toremoveseller").parent()
    // })
    
}

const disable_button = () => {

    comp.button_removeseller.disabled = true;
    comp.button_removeseller.querySelector(".spinner-border").classList.remove("d-none");

    comp.button_asignseller.disabled = true;
    comp.button_asignseller.querySelector(".spinner-border").classList.remove("d-none");
}

const enable_button = () => {

    comp.button_removeseller.disabled = false;
    comp.button_removeseller.querySelector(".spinner-border").classList.add("d-none");

    comp.button_asignseller.disabled = false;
    comp.button_asignseller.querySelector(".spinner-border").classList.add("d-none");
}