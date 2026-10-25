<script>
const formatRepo = (repo) => {
    if (repo.loading) {
        return repo.text;
    }

    var $container = $(

        "<div style='" + repo.css +
        "' class='container-producto p-2'><div class='d-inline p-2'><img width = '30px' src='" + repo.imagen +
        "' /></div>" +
        "<div class='d-inline p-2'>" + repo.name_completo + ' ' + repo.span + "</div><div>"
    );

    return $container

}

const formatRepoSelection = (repo) => {

    return repo.name_completo || repo.text;

}

const asign_ordernumner = (array) => {
    array.forEach(function(element, key, parent) {
        let cont = element.order + 1
        let clase = `.dual-listbox__item[data-id="${element.category_id}"]`
        let li = document.querySelector(clase)
        if (!li) {
            clase = `.dual-listbox__item.dual-listbox__item--selected[data-id="${element.category_id}"]`
            li = document.querySelector(clase)
        }

        if (li) {

            if (li.querySelectorAll('span.css-order').length >= 1) {
                li.querySelectorAll('span.css-order')[0].innerText = cont
            } else {

                const spanItem = document.createElement("span");
                spanItem.classList.add('css-order')
                spanItem.innerText = cont;
                li.prepend(spanItem)
            }
        } else {

        }

    })
}

const get_ordercategory = (listelement) => {
    let array = []
    listelement.forEach(function(element, key, parent) {
        array.push({
            "order": key,
            "category_id": element.dataset.id
        })
    })
    return array
}

const copytextsearchdroplist = (event) => {

    dualListbox.search.value = event.srcElement.value
    dualListbox.searchLists(event.srcElement.value, dualListbox.dualListbox)

}

const add_rowgeneral = (element, cont) => {

    const view_product = url_viewproduct + element.pro_id
    const delete_product = url_deleteproduct + element.pro_id
    const tmpversion = new Date().getTime()

    //let image_product = element.imagen_principal ? element.imagen_principal : 'noimg.png'
    //image_product = url_image + image_product + '?v=' + tmpversion

    let image_product = element.imagen_principal ?url_image + element.imagen_principal : image_default    
    image_product += '?v=' + tmpversion


    tbl.row.add([
        element.aditional,
        element.pro_id,
        cont++,
        `${element.sku}`,

        `<figure style="width: 4em; height: 4em; overflow:hidden; margin:auto;">
				<span data-toggle="popover-hover" title="" data-img="${image_product}" data-original-title="${element.sku}">
				<img src="${image_product}" style="display: block; width: 100%; height: 100%;" class="rounded-0" alt="">
				</span>
				</figure>`,
        `<a href="${view_product}">${element.name}</a>`,
        `${element.category_name}`,
        `<a href="javascript:void(0)" onclick="delete_row(event)" class="btn btn-danger btn-go-delete"><i class="mdi mdi-delete"></i>
				</a>`
    ]).draw()

}

const getformulario = (formulario, dualListbox) => {

    order_category = get_ordercategory(dualListbox.selected)
    const datos = new FormData(formulario);
    order_category.forEach(function(element, key, parent) {
        datos.append('order_categories[' + key + ']', key)
        datos.append('order_categories[' + key + '][order]', element.order)
        datos.append('order_categories[' + key + '][id]', element.category_id)
        console.log(order_category)
    })

    const result = getobjectentries(tbl)
    let cont = 0
    for (let data in result) {

        if (cont >= tbl.column().data().length) break

        // result[data].values[0]	= pro_id
        datos.append('product_list[' + cont + ']', result[data].values[1])
        cont++
    }

    return datos
}

const getobjectentries = (tbl) => {
    const result = Object.entries(tbl.rows().data()).map(([name, values]) => ({
        values
    }));
    return result
}

const checked_allProducts = (e) => {

    const element = e.currentTarget
    
    if(element.checked){
        new_filtrado = inventoryCapProductList([...new_filtrado, ...products], true)
        var kept = {}
        new_filtrado.forEach(function (p) { kept[String(p.pro_id)] = true })
        products = products.filter(function (p) { return !kept[String(p.pro_id)] })
        state.setState({ filteredProducts:new_filtrado });
    }    
}


const addoneaditionalproduct = (e) => {
    
    console.log("addone")
    const {lang} = {...options.lang.cmp_orders}
    const url = $(e.currentTarget).data('url')
    order_category = get_ordercategory(dualListbox.selected)
    //const datos = getformulario(form, dualListbox);
    const product_id = e.currentTarget.value


    const product_found_in_filtered = new_filtrado.filter(product => {
        return product.pro_id.includes(product_id)
    });
        
    if (product_found_in_filtered.length > 0) {
        
        let message = lang.product_is_already.replace("%s", product_found_in_filtered[0].name);
        alert_func([message, 'error'])                
        return
    }
    
    // sino
    
    const product_found_in_list = products.filter(product => {
        return product.pro_id.includes(product_id)
    });
    
    // si esta en la lista de productos  se agregar a la grilla en la primera fila
    if (product_found_in_list.length > 0) {
        if (typeof inventoryCapProductList === 'function' && new_filtrado.length >= 500) {
            var maxMsg = typeof inventoryProductsMaxMessage === 'function' ? inventoryProductsMaxMessage() : ''
            if (maxMsg && typeof alert_func === 'function') {
                alert_func([maxMsg, 'warning'])
            }
            $(e.currentTarget).val(null).trigger("change");
            return
        }
        
        console.log("product_found_in_list " ,product_found_in_list)

        const productosNoFiltrados = products.filter(producto => !producto.pro_id.includes(product_id));
        
        // si hay mas de uno entonces solo debe tomar uno
        new_filtrado = product_found_in_list.length>1
                        ?[...[product_found_in_list[0]],...new_filtrado]
                        :[...product_found_in_list,...new_filtrado]

        state.setState({ filteredProducts:new_filtrado });
        products = productosNoFiltrados
        
        let message = lang.product_added.replace("%s", product_found_in_list[0].name);

        alert_func([message,'success'])
        
    }
    $(e.currentTarget).val(null).trigger("change");
}

const addrowtotable = (data) => {
    data.products.forEach((element, key, value) => {

        const view_product = url_viewproduct + element.pro_id
        const delete_product = url_deleteproduct + element.pro_id
        const tmpversion = new Date().getTime()
        let image_product = element.imagen_principal ?url_image + element.imagen_principal : image_default
        
        image_product += '?v=' + tmpversion
        console.log(image_product)

        tbl.row.add([
            element.aditional,
            element.pro_id,
            cont_addoneproduct--,
            `${element.sku}`,
            `<figure style="width: 4em; height: 4em; overflow:hidden; margin:auto;">
				<span data-toggle="popover-hover" title="" data-img="${image_product}" data-original-title="${element.sku}">
				<img src="${image_product}" style="display: block; width: 100%; height: 100%;" class="rounded-0" alt="">
				</span>
				</figure>`,
            `<a href="${view_product}">${element.name}</a>`,
            `${element.category_name}`,
            `<a href="javascript:void(0)" onclick="delete_row(event)" class="btn btn-danger btn-go-delete"><i class="mdi mdi-delete"></i>
				</a>`
        ]).draw()

    })

}

const activar_tablacreate = () => {
    tbl = $('#infoTable').DataTable({

        "order": [
            [2, "asc"]
        ],
        "pageLength": 50,
        "language": language
    });

}

const click_accept = () => {
    const _typeorder = document.querySelector("input[name='from']:checked")

    if (_typeorder.getAttribute("data-type") == "grid") {

        if (dualListbox.selected.length > 0) {
            btn_aceptar.click()
        }

    }
}

const button_changestatus = (condition) => {

    const disabled = !condition ? false : true
    const display = condition ? "static" : "none"
    
    btn_createorder.forEach(function(element, key, parent) {
        element.disabled = disabled
        var spinner = element.querySelector(".spinner-border")
        if (spinner) {
            spinner.style = `display:${display};`
        }
    })

}

const delete_row = (pro_id,name) => {
    
    const {lang} = {...options.lang.cmp_orders}        
    Swal.fire({
        title: lang.deleted_product_question + ' '+ name + '?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#1EA910',
        cancelButtonColor: '#CF1C00',
        cancelButtonText: 'No',
        confirmButtonText: lang.order_yes,
        width: '29.5em'

        }).then((result) => {
        if (result.isConfirmed) {
            
            const product_found = new_filtrado.filter(producto => producto.pro_id.includes(pro_id));             
            const productosNoFiltrados = new_filtrado.filter(producto => !producto.pro_id.includes(pro_id));
            
            new_filtrado = productosNoFiltrados
            products = [...products,...product_found]
            console.log(product_found)
            state.setState({ filteredProducts:new_filtrado });

            let message = lang.deleted_product.replace("%s", name);
            alert_func([message, 'success'])            
        }
    })
    

}
</script>