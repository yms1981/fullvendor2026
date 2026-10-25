let total_monto = $('.total-monto');
let total_qtypro = $('#total_qtypro');
var _short =  $('#short').niceSelect()

// hacer peticion y ver los carritos del cliente


// var cart = JSON.parse(localStorage.getItem("cart")) || {products:[],product_chosed: null ,total:0,subtotal:0,discount:0};
var cart = {products:[],product_chosed: null ,total:0,subtotal:0,discount:0};

var cart_price = document.querySelector('#cart_price')
var total_qtyproHtml = document.querySelector('#total_qtypro')


const exampleEl = document.querySelectorAll('.buttonpop')

let activar_popover = (element)=>{
	
	const {lang} = env.lang.popover_catalog
	exampleEl.forEach(function (element, key, parent) {
		let id = element.getAttribute('data-id')
		let sale_price = element.getAttribute('data-sale-price')
		let comment = element.getAttribute('data-comment')
		
		let clase = '#figure-'+id + '.single_product'

		const product = searchProdutoinTheCart_byIndex(id)
		let product_comment = ''
		if (product) {			
			product_comment = product.comments
		}

		let popover = new bootstrap.Popover(element, {

			html: true,
			sanitize: false,
			container: clase,
			placement: 'top',
			title : `<h3 class="popover-header border-0"><div>${lang.choose_qty} <button type="button" class="btn-close float-end close-popover"><span class="visually-hidden">Close</span></button></h3>`,
			content : `<div style="margin-top: 10px;" class="chooseQuantityContainer" data-reload-on-change-quantity="0">
			<div class="row">
			<div class="col-md-12">
			<form class="form-save-qty" data-figure-container="${clase}" method="post" action="${url_base}marketplace/shop/saveqty">
			<input hidden type="text" name="product_id" value="${id}">
			<input hidden type="text">
			<input hidden type="text" name="origen" value="list">
			<input hidden type="text" name="sale_price" value="${sale_price}">
			<div class="input-group">
			<input id="inpput-${id}" type="number" tabindex="0" min="0" step="1" name="qty" class="form-control quantity" data-product-id="17396" value="" placeholder="${lang.qty}...">
			<button class="btn btn-success addQuantityToCart"  type="submit" data-loading-text="Loading..." data-product-id="17396">${lang.save}</button>
			</div>
			<br>
			<textarea name="comment" class="form-control border" style="height: auto;" placeholder="${lang.comment}">${product_comment}</textarea>
			</form>
			<div style="text-align: center;">		    
			<a href="#" class="btn btn-sm btn-link removeproductocart" data-product-id="${id}" data-loading-text="Removing...">${lang.remove_fromcart}</a>
			</div>
			</div>
			</div>
			</div>`
		})

		//document.getElementById("#input-" + id).focus({ focusVisible: true });

	});
}



document.addEventListener("DOMContentLoaded", async (event) => {
		
    await get_language()
	await update_cartfromsession()
	activar_popover(exampleEl)

    // show_data_inproducts(cart)
})

// evento click imagen carousel del modal
$('.modal_box_product .product_navactive a').on('click',function(e){
	e.preventDefault();
	var $href = $(this).attr('href');
	$('.product_navactive a').removeClass('active');
	$(this).addClass('active');

	console.log("click")
	$('.product-details-large .tab-pane').removeClass('active show');
	$('.product-details-large '+ $href ).addClass('active show');

})

$(document).on("click", ".popover .close-popover" , function(){
	$(".buttonpop").popover('hide');
});

$(document).on("show.bs.popover", ".buttonpop" , function(e){
	
	//popover bs-popover-auto fade show
	
	$(".buttonpop").popover('hide');
	$(e.relatedTarget).popover('show');
});

$(document).on("shown.bs.popover", ".buttonpop" , function(e){

	let id =  $(this).data("id")
	$('.popover .form-save-qty #inpput-' + id).focus()
	
});

function animate_text(element) {
	
	element.removeClass('animate__fadeOutUp')
	element.addClass('animate__zoomIn animate_02s')
}

//AGREGAR
$(document).on("click", ".addqtyminium" , function(e){

	$.toast().reset('all');
	let id = $(this).data('id')
	let minium_qty = $(this).data('minium-qty')
	let element = $(this)
	element.removeClass('animate__animated animate__zoomIn animate_02s')
	console.log(minium_qty)
	
	const product = {
		id,
		qty:minium_qty,
		add: true
	}
	
	addToCart(product, increase = true)
	
	$.ajax({
		url: url_base + 'marketplace/shop/saveqty',
		type: 'post',
		data: cart,
		dataType: 'json',
		beforeSend: function(){
			element.find('.spinner-border').removeClass('d-none')
			element.prop('disabled',true)
			element.prev().prop('disabled',true)
			element.prev().find('.spinner-border').removeClass('d-none')

		},
		success: function(response){

			console.log(response)
			if (response.success) {
				// $.toast({
					
				// 	text: 'Producto Agregado!.',
				// 	hideAfter : 1500,  
				// 	icon: 'success',
				// 	position : 'top-center' 
				// })	

				total_monto.text(response.detalle.total_orden)
				total_qtypro.text(response.detalle.cantidad_productos)
				total_qtypro.removeClass('d-none')

				print_qty(id,response.cantidad_actual)
				update_LocalStorage(response)
			}else{
				swalalert.fire('',response.mesagge,'error')
			
			}

			element.addClass('animate__animated animate__fadeOutUp animate_02s')
			setTimeout(animate_text, 400,element);
			element.find('.spinner-border').addClass('d-none')
			element.prop('disabled',false)

			element.prev().prop('disabled',false)
			element.prev().find('.spinner-border').addClass('d-none')

			$(".buttonpop").popover('hide');			
		}
	})	
	
});

//GUARDAR desde la tabla
//form-save-qty 
$(document).on("submit", ".form-save-qty" , function(e){
	$.toast().reset('all');	
	e.preventDefault();
	const comment = $(this).find('textarea[name="comment"]').val()
	const id = Number($(this).find('input[name="product_id"]').val())
	const qty = Number($(this).find('input[name="qty"]').val())
	
	const product = {
		id,
		origen: $(this).find('input[name="origen"]').val(),
		qty,		
		comments: comment?comment:'',
		add: true
	}

	addToCart(product)
	
	let figure_container = $(this).data('figure-container')
	//.boton-add-car
	let boton_addcar = figure_container +' .boton-add-car'
	let boton_add = $(boton_addcar)
	let pro_id = $(this).find('input[name="product_id"]').val()
	let element = boton_add.next()
	let clase = '#card-' + pro_id + ' .buttonpop'
	let button_pop = $(clase)
		
	$.ajax({

		url: $(this).attr('action'),
		type: 'post',
		data: cart,
		dataType: 'json',
		beforeSend: function(){
			
			element.find('.spinner-border').removeClass('d-none')
			element.prop('disabled',true)
			element.prev().prop('disabled',true)
			element.prev().find('.spinner-border').removeClass('d-none')

		},
		success: function(response){
						
			if (response.success) {
				
				// update_stateAddProducto(product)				
				//  $.toast({					
				// 	text: response.mesagge,
				// 	hideAfter : 1500,  
				// 	icon: 'success',
				// 	position : 'top-center' 
				// })

				total_monto.text(response.detalle.total_orden)
				total_qtypro.text(response.detalle.cantidad_productos)
				total_qtypro.removeClass('d-none')

				if (response.detalle.cantidad_productos <= 0) {
					total_qtypro.addClass('d-none')
				}
				$(".buttonpop").popover('hide');

				button_pop.attr('data-comment',response.comment)
				print_qty(pro_id,response.cantidad_actual)

				$(".buttonpop").popover('disable');
				activar_popover(exampleEl)
				update_LocalStorage(response)
				//evaluar las cantidad total de productos
			}else{								
				swalalert.fire('',response.mesagge,'error')
			}

			element.find('.spinner-border').addClass('d-none')
			element.prop('disabled',false)

			element.prev().prop('disabled',false)
			element.prev().find('.spinner-border').addClass('d-none')
			
		}


	})

});

// eliminar
$(document).on("click", ".removeproductocart" , function(e){
	
	e.preventDefault();
	let id = $(this).data('product-id')	
	delete_product(id)

	$.ajax({

		url: `${url_base}marketplace/shop/saveqty`,
		type:'post',
		dataType: 'json',
		data: cart,
		success: function(response){

			if (response.success) {
				
				total_monto.text(response.detalle.total_orden)
				total_qtypro.text(response.detalle.cantidad_productos)

				if (response.detalle.cantidad_productos <= 0) {
					total_qtypro.addClass('d-none')
				}
				
				print_qty(id,response.cantidad_actual)
				update_LocalStorage(response)
				$(".buttonpop").popover('hide');
				
			}
		}	

	})
});	



$(document).on('change','#short',function(e){
	let short_val = $(this).val()
	$('.short-value').val(short_val)
	// $('.form-filtro-buscar').serializeArray()
	$('.form-filtro-buscar').submit()

})


function print_qty(car_proid,qty_actual){

	let clase = '#card-' + car_proid + ' .buttonpop .text'
	console.log($(clase), qty_actual)
	
	const modalInputQty = document.querySelector(`#modal-product-${car_proid} .modal_body .form-save-qty input[name="qty"]`)

	
	modalInputQty.value = qty_actual
	

	if (qty_actual === undefined) {

		$(clase).text(' ' + add_product + ' ')
	}else{
		if (qty_actual>0) {
			$(clase).text(' '+qty_actual+' ')	
		}else{
			$(clase).text(' ' + add_product + ' ')
		}
	}
	const span_text = document.querySelector(`#card-${car_proid} .buttonpop .text`)
	span_text.classList.remove('d-none')

}

const myCarousels = document.querySelectorAll('.carousel')

myCarousels.forEach((element, key, parent)=> {

	element.addEventListener('slide.bs.carousel', event => {
  	
  	const index = event.relatedTarget.getAttribute('data-index')
  	const carousel = event.target
  	const count = carousel.parentElement.parentElement.querySelector('.label_product a .label_sale span')
  	count.innerText = index

	})
})


$(document).on("mouseover", ".over-category", function(e) {
	
	const cadenaNumeros = String($(this).data('categories'));
	console.log(cadenaNumeros)
	const arrayNumeros = cadenaNumeros.split(",");

	// Imprimir el array resultante
	arrayNumeros.forEach(element => {
		
		const category_li = document.querySelector(`#cat-li-${element}`)
		const category_span = category_li.querySelector('span')
		
		category_li.classList.add('bg-secondary')
		category_li.classList.add('bg-opacity-25')

		category_span.classList.add('fw-bold')
		category_span.classList.add('text-danger')
	});

})

$(document).on("mouseout", ".over-category", function(e) {
	const cadenaNumeros = String($(this).data('categories'));
	const arrayNumeros = cadenaNumeros.split(",");
	
	
	arrayNumeros.forEach(element => {
		
		const category_li = document.querySelector(`#cat-li-${element}`)		
		const category_principal = category_li.getAttribute('data-principal')	
		
		if (!category_principal) {
			
			const category_span = category_li.querySelector('span')
			
			category_li.classList.remove('bg-secondary')
			category_li.classList.remove('bg-opacity-25')
	
			category_span.classList.remove('fw-bold')
			category_span.classList.remove('text-danger')
		}
	});
})