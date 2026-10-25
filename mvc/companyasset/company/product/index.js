const d = document
var comp = {}
$(function() {
    const productLang = (typeof options !== 'undefined' && options.lang && (options.lang['cmp-products'] || options.lang.cmp_products))
        ? ((options.lang['cmp-products'] && options.lang['cmp-products'].lang) || (options.lang.cmp_products && options.lang.cmp_products.lang) || {})
        : {};
    const selectCategoryPlaceholder = productLang.select_category || 'Select Category';

    comp = {
        modalasigncategory: $("#modalasigncategory"),
        modalremovecategory: $("#modalremovecategory"),
        modalchangestatus: $("#modalchangestatus"),
        modaladdcatalog: $("#modal_addToCatalog"),
        form_filter : d.getElementById("form-buscar-filtro"),
        button_removecategory:d.getElementById("btn-removecategory"),
        button_asigncategory:d.getElementById("btn-asigncategory"),
        button_changes:d.getElementById("btn-changestatus-category"),

        form_asignRemoveseller : d.getElementsByClassName("form-asign-remove-seller"),

    select_categorytoasign: $('#selectmultple_asign').select2({
        placeholder: selectCategoryPlaceholder,
        width : '100%',
        dropdownParent: $("#contain_selectmultple_asign").parent()
    }),

    select_categorytoremove: $('#selectmultple_remove').select2({
        placeholder: selectCategoryPlaceholder,
        width : '100%',
        dropdownParent: $("#contain_selectmultple_remove").parent()
    }),

    }

    const disable_button = () => {

        comp.button_removecategory.disabled = true;
        comp.button_removecategory.querySelector(".spinner-border").classList.remove("d-none");

        comp.button_changes.disabled = true;
        comp.button_changes.querySelector(".spinner-border").classList.remove("d-none");

        comp.button_asigncategory.disabled = true;
        comp.button_asigncategory.querySelector(".spinner-border").classList.remove("d-none");
    }

    const enable_button = () => {

        comp.button_removeseller.disabled = false;
        comp.button_removeseller.querySelector(".spinner-border").classList.add("d-none");

        comp.button_changes.disabled = false;
        comp.button_changes.querySelector(".spinner-border").classList.add("d-none");

        comp.button_asignseller.disabled = false;
        comp.button_asignseller.querySelector(".spinner-border").classList.add("d-none");
    }
})

