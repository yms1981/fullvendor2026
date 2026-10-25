	const swalWithBootstrapButtons = Swal.mixin({
	customClass: {
		confirmButton: 'btn btn-success',
		cancelButton: 'btn btn-danger'
	},
	buttonsStyling: false
})

var PO_exampleEl = document.querySelectorAll('.buttonpop'),
card_empty = $('#card-empty'),
button_clean_cart = $('#button-clean-cart'),
order_complete = $('#order-complete')

let PO_total_monto = $('.total-monto')
let PO_total_qtypro = $('#total_qtypro')
let sub_total = $('.sub_total')
let discount = $('.discount')

var cart = JSON.parse(localStorage.getItem("cart")) || {products:[],
	product_chosed: null ,
	total:0,
	subtotal:0,
	discount:0,
	moneda: '',
	sending: {
		new_address: false,
		city: "",
		comment_order: "",
		delivery_address: "",
		shipping_notes: "",
		state: "",
		zip_code: "",
		zone: "",
		country: ""
	},
};

var cart_price = document.querySelector('#cart_price')
var PO_total_qtyproHtml = document.querySelector('#PO_total_qtypro')



let PO_activar_popover = (element)=>{

	PO_exampleEl = document.querySelectorAll('.buttonpop')
	const lang = (typeof env !== 'undefined' && env && env.lang && env.lang.popover_catalog && env.lang.popover_catalog.lang)
		? env.lang.popover_catalog.lang
		: { choose_qty: 'Quantity', qty: 'Qty', save: 'Save', comment: 'Comment', remove_fromcart: 'Remove from cart' };
	const baseUrl = (typeof url_base !== 'undefined' ? url_base : (env && env.url ? env.url : ''));
	const saveQtyUrl = baseUrl + 'catalogo/producto/saveqty';
	PO_exampleEl.forEach(function (element, key, parent) {
		let id = element.getAttribute('data-id'),
		comment = element.getAttribute('data-comment') || '';
		let qtyEl = document.getElementById('qty-' + id);
		let currentQty = (qtyEl && (qtyEl.getAttribute('data-qty-raw') || qtyEl.textContent)) ? (qtyEl.getAttribute('data-qty-raw') || qtyEl.textContent).replace(/\s/g, '') : '';

		let popover = new bootstrap.Popover(element, {

			html: true,
			sanitize: false,
			container: '.Checkout_section',
			placement: 'top',
			title : `<h3 class="popover-header border-0"><div>${lang.choose_qty} <button type="button" class="btn-close float-end close-popover"><span class="visually-hidden">Close</span></button></h3>`,
			content : `
			<div style="margin-top: 10px;" class="chooseQuantityContainer" data-reload-on-change-quantity="0">
				<div class="row">
					<div class="col-md-12">
						<form data-product-id="${id}" class="form-save-qty" method="post" action="${saveQtyUrl}">
							<input type="hidden" name="token" value="${typeof token !== 'undefined' ? token : ''}">
							<input type="hidden" name="product_id" value="${id}">
							<input type="hidden" name="origen" value="order">
							<div class="input-group">
							<input id="inpput-${id}" type="number" tabindex="0" min="0" step="1" name="qty" class="form-control quantity" data-product-id="${id}" value="${currentQty}" placeholder="${lang.qty}">
							<button class="btn btn-success addQuantityToCart" type="submit" data-loading-text="Loading..." data-product-id="${id}">${lang.save}</button>
							</div>
							<br>
							<textarea name="comment" class="form-control border" style="height: auto;" placeholder="${lang.comment}">${comment}</textarea>
						</form>
						<div style="text-align: center;">
							<a href="#" class="btn btn-sm btn-link removeproductocart" data-product-id="${id}" data-loading-text="Removing...">${lang.remove_fromcart}</a>
						</div>
					</div>
				</div>
			</div>`
		})
	});
}

document.addEventListener("DOMContentLoaded", async (event) => {

    // await get_language()
	//await update_cartfromsession()
	PO_activar_popover(PO_exampleEl)
	// show_data_inproducts(cart)

	// Carrito con productos: hay filas en la tabla de YOUR ORDER (o variable global qty_car)
	var hasOrderItems = (typeof shopping_order !== 'undefined' && Array.isArray(shopping_order) && shopping_order.length > 0)
		|| (document.querySelectorAll('#order-complete .table tbody tr[id^="tr-"]').length > 0)
		|| (typeof qty_car !== 'undefined' && qty_car > 0);

    if (!hasOrderItems) {

		card_empty.removeClass('d-none')
		card_empty.addClass('animate__animated')
		card_empty.addClass('animate__backInLeft')

	}else{
		order_complete.removeClass('d-none')
		order_complete.addClass('animate__animated')
		order_complete.addClass('animate__fadeIn')

		button_clean_cart.removeClass('d-none')
		button_clean_cart.addClass('animate__animated')
		button_clean_cart.addClass('animate__fadeIn')
	}

})


$(document).on("click", ".popover .close-popover" , function(){
	$(".buttonpop").popover('hide');
	$(".buttonpop-error").popover('hide');

});

$(document).on("show.bs.popover", ".buttonpop" , function(e){
	$(".buttonpop").popover('hide');
	$(e.relatedTarget).popover('show');
	$(".buttonpop-error").popover('hide');

});

$(document).on("shown.bs.popover", ".buttonpop" , function(e){

	let id =  $(this).data("id")
	$('.popover .form-save-qty #inpput-' + id).focus()

});

const popfoto = document.querySelectorAll('[data-toggle="popover-hover"]')
popfoto.forEach(function (element, key, parent) {

	let popover = new bootstrap.Popover(element, {
		html: true,
		trigger: 'hover',
		placement: 'left',
		content: function() {
			return '<img src="' + $(this).data('img') + '" class="img-thumbnail" />';
		}
	})
});


$(document).on("click", ".removeproductocart" , function(e){
    e.preventDefault();

    let id = $(this).data('product-id')
    const baseUrl = (typeof url_base !== 'undefined' ? url_base : (env && env.url ? env.url : ''));
    clase_fila = '#tr-' + id
    let tr = $(clase_fila)

    $.ajax({
        url: baseUrl + 'catalogo/producto/eliminar',
        type: 'POST',
        data: { token: typeof token !== 'undefined' ? token : '', id: id },
        dataType: 'json',
        success: function(response){
            if (response.success) {
                PO_total_monto.text(response.detalle.total_orden)
                PO_total_qtypro.text(response.detalle.cantidad_productos)

                sub_total.text(response.detalle.sub_total)
                discount.text(response.detalle.discount_total)

                if (response.detalle.cantidad_productos <= 0) {
                    PO_total_qtypro.addClass('d-none')
                    card_empty.removeClass('d-none')
                    order_complete.addClass('d-none')
                }
                $.toast({
                    text: response.msg,
                    hideAfter : 1500,
                    icon: 'success',
                    position : 'top-center'
                })
                $(".buttonpop").popover('hide');
                tr.remove()
            }
        }
    })
});


$(document).on("submit", ".form-save-qty" , function(e){
    e.preventDefault();

    let id = $(this).data('product-id'),
        clase_fila = '#tr-' + id,
        tr = $(clase_fila)

    $.ajax({
        url: $(this).attr('action'),
        type: 'post',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response){
            if (response.success) {
                $(".buttonpop").popover('hide');
                $.toast({
                    text: response.mesagge || response.msg || 'OK',
                    hideAfter : 1500,
                    icon: 'success',
                    position : 'top-center'
                })
                if (response.detalle) {
                    PO_total_monto.text(response.detalle.total_orden)
                    PO_total_qtypro.text(response.detalle.cantidad_productos)
                    sub_total.text(response.detalle.sub_total)
                    discount.text(response.detalle.discount_total)
                }
                PO_total_qtypro.removeClass('d-none')
                var qtyDisplay = response.cantidad_actual_formatted != null ? response.cantidad_actual_formatted : response.cantidad_actual;
                tr.find('.p-qty').text(qtyDisplay).attr('data-qty-raw', response.cantidad_actual);
                tr.find('span.price').text(response.importe_total != null ? response.importe_total : '');
                tr.find('.buttonpop').attr('data-comment', response.comment != null ? response.comment : '');

                if (response.detalle && response.detalle.cantidad_productos <= 0) {
                    PO_total_qtypro.addClass('d-none')
                    card_empty.removeClass('d-none')
                    order_complete.addClass('d-none')
                }
                $(".buttonpop").popover('dispose');
                PO_activar_popover(PO_exampleEl)
            } else {
                swalalert.fire('', (response.mesagge || (response.message && response.message.message) || 'Error'), 'error')
            }
        },
        error: function(xhr) {
            var msg = 'Error de conexión';
            try {
                var j = xhr.responseJSON || (xhr.responseText ? JSON.parse(xhr.responseText) : null);
                if (j && (j.mesagge || j.message)) msg = j.mesagge || j.message;
            } catch (e) {}
            swalalert.fire('', msg, 'error')
        }
    })
});


/**
 * Tras crear el pedido: si el checkout va dentro del iframe del portal (/customer/catalog),
 * no cargar la URL del shell del portal dentro del iframe (duplicaría header/pestañas).
 * Si close_window: cierra la pestaña/popup (el token ya no sirve para volver al catálogo).
 */
function placeorderNavigateAfterSuccess(url, options) {
	options = options || {};
	var closeWindow = !!options.close_window;
	var openerRedirect = options.opener_redirect || '';

	if (closeWindow) {
		try {
			if (window.opener && !window.opener.closed) {
				if (openerRedirect) {
					try {
						window.opener.location.href = openerRedirect;
					} catch (e1) {}
				} else {
					try {
						window.opener.focus();
					} catch (e2) {}
				}
			}
		} catch (e3) {}

		try {
			window.close();
		} catch (e4) {}

		// Si el navegador no permite cerrar (no es popup), no ir al catálogo con token muerto.
		setTimeout(function () {
			if (!window.closed) {
				if (url) {
					placeorderNavigateAfterSuccess(url, { close_window: false });
					return;
				}
				try {
					if (typeof order_complete !== 'undefined' && order_complete && order_complete.length) {
						order_complete.addClass('d-none');
					}
					if (typeof card_empty !== 'undefined' && card_empty && card_empty.length) {
						card_empty.removeClass('d-none');
					}
					if (typeof updateCartTotals === 'function') {
						updateCartTotals({
							total_orden: '0.00',
							cantidad_productos: 0,
							sub_total: '0.00',
							discount_total: '0.00'
						}, { skipMiniCartRefresh: true });
					} else {
						$('.total-monto').text('0.00');
						$('.cart-count-total').text('0').addClass('d-none');
					}
					if (typeof button_clean_cart !== 'undefined' && button_clean_cart && button_clean_cart.length) {
						button_clean_cart.addClass('d-none');
					}
				} catch (e5) {}
			}
		}, 250);
		return;
	}

	if (!url) {
		return;
	}
	try {
		if (window.self !== window.top) {
			window.top.location.href = url;
		} else {
			window.location.href = url;
		}
	} catch (e) {
		window.location.href = url;
	}
}

let btn_order = $('.btn-placeorder')

$(document).on("submit", "#form-order" , function(e){
	e.preventDefault();
	const form = $(this);
	const formEl = this;
	const fd = new FormData(formEl);
	const checked_address = formEl.querySelector('input[name="sending[new_address]"], input[name="new_address"]');

	const pickField = function (names) {
		var i, v, el;
		for (i = 0; i < names.length; i++) {
			v = fd.get(names[i]);
			if (v != null && String(v).trim() !== '') {
				return String(v).trim();
			}
		}
		for (i = 0; i < names.length; i++) {
			el = formEl.querySelector('[name="' + names[i] + '"]');
			if (el && el.value != null && String(el.value).trim() !== '') {
				return String(el.value).trim();
			}
		}
		return '';
	};

	const tokenVal = pickField(['token', 'sending[token]']);
	const data = {
		token: tokenVal,
		// Enviar "1"/"0" (no boolean) para que PHP lo reconozca igual que un form clásico.
		new_address: (checked_address && checked_address.checked) ? '1' : '0',
		delivery_address: pickField(['delivery_address', 'sending[delivery_address]']),
		zip_code: pickField(['zip_code', 'sending[zip_code]']),
		city: pickField(['city', 'sending[city]', 'city_id', 'city_name']),
		state: pickField(['state', 'sending[state]', 'state_id', 'state_name']),
		country: pickField(['country', 'sending[country]', 'country_id', 'country_name']),
		zone: pickField(['zone', 'zona', 'sending[zone]', 'sending[zona]']),
		shipping_notes: pickField(['shipping_notes', 'sending[shipping_notes]']),
		comment_order: pickField(['comment_order', 'sending[comment_order]']),
		proforma: form.find('input[name="proforma"]').is(':checked') ? '1' : '0'
	};

	$.ajax({
		url: (typeof url_base !== 'undefined' ? url_base : '') + 'catalogo/producto/place_order',
		type: 'post',
        contentType: 'application/json; charset=UTF-8',
        dataType: 'json',
		data: JSON.stringify(data),
		beforeSend: function(){
			btn_order.find('.spinner-border').removeClass('d-none')
			btn_order.prop('disabled',true)
		},
		success: function(response){
			btn_order.find('.spinner-border').addClass('d-none')
			btn_order.prop('disabled',false)

			if (response.success && (response.url || response.back || response.close_window)) {
				var successMsg = (response.msg && response.msg !== '') ? response.msg : (typeof env !== 'undefined' && env && env.lang && env.lang.order_created ? env.lang.order_created : 'Order created');
				var navOpts = {
					close_window: !!response.close_window,
					opener_redirect: response.opener_redirect || ''
				};
				var goNext = function () {
					if (response.close_window) {
						placeorderNavigateAfterSuccess(response.url || '', navOpts);
					} else if (response.url) {
						placeorderNavigateAfterSuccess(response.url, navOpts);
					} else if (response.back) {
						window.history.back();
					}
				};
				if (typeof swalalert !== 'undefined' && swalalert.fire) {
					swalalert.fire('', successMsg, 'success').then(function() {
						goNext();
					});
				} else {
					alert(successMsg);
					goNext();
				}
				return;
			}
			if (!response.success) {
				var msgObj = response.message;
				var errList = (msgObj && typeof msgObj === 'object' && msgObj.errors) ? msgObj.errors : null;
				var msgText = (msgObj && typeof msgObj === 'object' && msgObj.message) ? msgObj.message : (typeof msgObj === 'string' ? msgObj : (response.mesagge || response.message || 'Error'));
				if ((!errList || typeof errList !== 'object') && response.errores_list && typeof response.errores_list === 'object') {
					errList = {};
					$.each(response.errores_list, function (_idx, row) {
						if (row && row.input) {
							errList[row.input] = row.msg_error || msgText;
						}
					});
				}
				if (errList && typeof errList === 'object') {
                    $.each(errList, function (index, val) {
                        const input = document.querySelector(`#form-order .form-control[name="sending[${index}]"]`)
							|| document.querySelector(`#form-order .form-control[name="${index}"]`);
                        if (input && input.nextElementSibling) {
                            input.classList.add('is-invalid');
                            input.nextElementSibling.innerText = val;
                            input.nextElementSibling.style.display = 'block';
                        }
                    });
                    if (typeof swalalert !== 'undefined' && swalalert.fire) swalalert.fire('', msgText, 'error');
                } else {
                    if (msgText) {
                        if (typeof swalalert !== 'undefined' && swalalert.fire) swalalert.fire('', msgText, 'error');
                        return;
                    }
					if (typeof swalalert !== 'undefined' && swalalert.fire) swalalert.fire('', response.mesagge || response.message || 'Error', 'error');
				}
			}
		},
		error: function(xhr) {
			btn_order.find('.spinner-border').addClass('d-none');
			btn_order.prop('disabled', false);
			var msg = 'Error de conexión';
			try {
				var j = xhr.responseJSON || (xhr.responseText ? JSON.parse(xhr.responseText) : null);
				if (j && (j.mesagge || j.message)) msg = j.mesagge || (typeof j.message === 'string' ? j.message : msg);
			} catch (err) {}
			if (typeof swalalert !== 'undefined' && swalalert.fire) swalalert.fire('', msg, 'error');
		}
	});
});
