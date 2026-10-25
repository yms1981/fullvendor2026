const update_LocalStorage = (data) => {

   cart.product_chosed = null
   cart.products = data.detalleProduct_modified?[...data.detalleProduct_modified]:[]
   cart.discount = data.detalle.discount_total
   cart.subtotal = data.detalle.sub_total
   cart.total = data.detalle.total_orden
   cart.moneda = data.moneda
   // localStorage.setItem("cart", JSON.stringify(cart));
}


const show_data_inproducts = (cart) => {

    cart_price.querySelector('.spinner-border').hidden = true

    if (cart.total){
        cart_price.querySelector('.total-monto').innerText = cart.total
        total_qtyproHtml.innerText = cart.products.length
        total_qtyproHtml.classList.remove('d-none')
    }

    cart.products.forEach(product => {
        const span_text = document.querySelector(`#card-${product.id} .buttonpop .text`)
        const span_loader = document.querySelector(`#card-${product.id} .buttonpop .spinner-border`)

        const popover_comment = document.querySelector(`#card-${product.id} .popover .form-save-qty`)
        console.log(popover_comment)
        //popover_comment.value =  product.comment

        // modal-product-23786

        if (span_text && span_loader) {
            span_loader.hidden = true
            span_text.classList.remove('d-none')

            print_qty(product.id,product.qty)
        }
    });

    // recorrer las siguientes tarjetas deproductos
    const all_cardproducts = document.querySelectorAll('.card-product')
    all_cardproducts.forEach(element => {

        const id = element.getAttribute('data-product-id')
        const productFound = searchProdutoinTheCart_byIndex(id)

        if (!productFound) {
            const span_text = element.querySelector(`.buttonpop .text`)
            const span_loader = element.querySelector(`.spinner-border`)
            span_loader.hidden = true
            span_text.classList.remove('d-none')
        }

    });

    // renderizar las filas
    const tableOrder = document.querySelector("#table_order")
    console.log(tableOrder)
    if (tableOrder) {
        const tbody = tableOrder.querySelector('tbody')

        const fragment_rows = document.createDocumentFragment()

        cart.products.forEach(product => {

            const row_product =  get_HtmlRowProduct(product)
            fragment_rows.appendChild(row_product.cloneNode(true));
        });

        tbody.appendChild(fragment_rows)
        /* ordertotalamount, order_subtotal, order_discount */
        const totalamount = document.querySelector('#order_totalamount'),
        subtotal = document.querySelector('#order_subtotal'),
        order_discount = document.querySelector('#order_discount')

        if (totalamount) totalamount.innerText = cart.total
        if (subtotal) subtotal.innerText = cart.subtotal
        if (order_discount) order_discount.innerText = cart.discount

        const footer_tableSpineers = document.querySelectorAll('#table_order tfoot .spinner-border')

        footer_tableSpineers.forEach(spinner => {
            spinner.hidden = true
        });

        if ( cart.products.length > 0)  activar_popover()
    }

}


const get_HtmlRowProduct = (product) => {
    const row = document.createElement('tr');
    row.setAttribute('id', `tr-${product.id}`)
    row.innerHTML = `
        <td>
            <figure class="figure-image-adpater">
                <span data-toggle="popover-hover" title="${product.sku}">
                    <img class="figure-img rounded-0" src="${product.images}">
                </span>
            </figure>
        </td>
        <td>${product.name}<br>SKU: ${product.sku}</td>
        <td class="text-center product_edit">
            <div class="d-flex justify-content-center">
               <p class="p-qty buttonpop-error" id="qty-${product.id}">
                ${product.qty}
               </p>&nbsp;
               <a href="javascript:void(0)" data-comment="${product.comments}"
                    data-id="${product.id}" class="buttonpop">
                    <i class="fa fa-pencil-square-o" aria-hidden="true"></i>
                </a>
            </div>
        </td>
        <td class="text-end">${cart.moneda + product.sale_price}</td>
        <td class="text-end">${cart.moneda}<span class="price">${product.total_amount}</span></td>
    `;
    return row
}
