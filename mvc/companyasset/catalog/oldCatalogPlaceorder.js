	const swalWithBootstrapButtons = Swal.mixin({
	customClass: {
		confirmButton: 'btn btn-success',
		cancelButton: 'btn btn-danger'
	},
	buttonsStyling: false
})

const exampleEl = document.querySelectorAll('.buttonpop'),
card_empty = $('#card-empty'),
order_complete = $('#order-complete')

let total_monto = $('.total-monto')
let total_qtypro = $('#total_qtypro')
let sub_total = $('.sub_total')
let discount = $('.discount')



let activar_popover = (element)=>{


	exampleEl.forEach(function (element, key, parent) {

		const {lang} = env.lang.popover_catalog
		let id = element.getAttribute('data-id'),
		comment = element.getAttribute('data-comment')

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
						<form data-product-id="${id}" class="form-save-qty" method="post" action="${url_base}'home/addProduct')">
							<input hidden type="text" name="product_id" value="${id}">
							<input hidden type="text" name="token" value="${token}">
							<input hidden type="text" name="origen" value="order">
							<div class="input-group">
							<input id="inpput-${id}" type="number" tabindex="0" min="0" step="1" name="qty" class="form-control quantity" data-product-id="17396" value="" placeholder="${lang.qty}">
							<button class="btn btn-success addQuantityToCart" type="submit" data-loading-text="Loading..." data-product-id="17396">${lang.save}</button>
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

    await get_language()
	activar_popover(exampleEl)

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

	let id = $(this).data('product-id'),

	clase_fila = '#tr-' + id
	let tr = $(clase_fila)


	$.ajax({

		url: url_base + 'catalogo/producto/eliminar',
		type:'post',
		dataType: 'json',
		data: {token,id},
		success: function(response){

			if (response.success) {


				total_monto.text(response.detalle.total_orden)
				total_qtypro.text(response.detalle.cantidad_productos)
				sub_total.text(response.detalle.sub_total)
				discount.text(response.detalle.discount_total)

				if (response.detalle.cantidad_productos <= 0) {
					total_qtypro.addClass('d-none')
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

	let figure_container = $(this).data('figure-container')
	//.boton-add-car
	let boton_addcar = figure_container +' .boton-add-car'
	let boton_add = $(boton_addcar)

	let element = boton_add.next()

	let id = $(this).data('product-id'),
	clase_fila = '#tr-' + id,
	tr = $(clase_fila),
	qty = $(this).find('input[name="qty"]').val()

	$.ajax({

		url: $(this).attr('action'),
		type: 'post',
		data: $(this).serializeArray(),
		dataType: 'json',

		success: function(response){

			//total-monto total_qtypro

			if (response.success) {
				$(".buttonpop").popover('hide');
				$.toast({

					text: response.mesagge,
					hideAfter : 1500,
					icon: 'success',
					position : 'top-center'
				})

				total_monto.text(response.detalle.total_orden)
				total_qtypro.text(response.detalle.cantidad_productos)
				total_qtypro.removeClass('d-none')
				sub_total.text(response.detalle.sub_total)
				discount.text(response.detalle.discount_total)

				tr.find('.p-qty').text(qty)
				tr.find('.price').text(response.importe_total)
				tr.find('.buttonpop').attr('data-comment',response.comment)

				if (qty <= 0) {

					tr.remove()
				}

				if (response.detalle.cantidad_productos <= 0) {
					total_qtypro.addClass('d-none')
					card_empty.removeClass('d-none')
					order_complete.addClass('d-none')
				}

				$(".buttonpop").popover('disable');
				activar_popover(exampleEl)
				//evaluar las cantidad total de productos
			}else{

				/*$.toast({

					text: response.mesagge,
					hideAfter : 1500,
					icon: 'error',
					position : 'top-center'
				})*/
				swalalert.fire('',response.mesagge,'error')
				//$(".buttonpop").popover('hide');

			}
		}
	})
});


let btn_order = $('.btn-placeorder')
$(document).on("submit", "#form-order" , function(e){

	e.preventDefault();
	$.ajax({

		url: $(this).attr('action'),
		type: 'post',
		data: $(this).serializeArray(),
		dataType: 'json',
		beforeSend: function(){
			btn_order.find('.spinner-border').removeClass('d-none')
			btn_order.prop('disabled',true)
		},
		success: function(response){

			btn_order.find('.spinner-border').addClass('d-none')
			btn_order.prop('disabled',false)

			if (!response.success) {

				if (response.reload) {
					return location.reload()
				}

				if (response.errores) {

					$.each(response.errores, function(index, val) {

						let element = document.querySelector('.table tbody tr #'+ val.name_input)
						let popover = new bootstrap.Popover(element, {

							html : true,
							sanitize: false,
							placement: 'bottom',
							title : `<div>${val.tittle} <button type="button" class="btn-close float-end close-popover bg-white"><span class="visually-hidden">Close</span></button></div>`,
							content : val.content

						});

						$('.table tbody tr #'+ val.name_input).popover('show');
						$('.popover-header').addClass('bg-danger text-white')
						//$('.table tbody tr input[name="'+ val.name_input +'"]').popover('show')
					})

				}else{
					if (response.errores_list) {

						const errores = Object.values(response.errores_list);
						for (const val of errores) {

							const input = document.querySelector(`form .form-control[name="${val.input}"]`)
							input.classList.add('is-invalid');
							input.nextElementSibling.innerText = val.msg_error
							input.nextElementSibling.style = 'display: block';

						}

						swalalert.fire('',response.mesagge,'error')
						return;
					}
					swalalert.fire('',response.mesagge,'error')
				}
			}else{

				if (response.back) {
					window.location.href = response.url
 				}else{
					window.location.href = $('#url_shop').attr('href')
				}
			}
		}
	})
})
