let total_monto = $('.total-monto');
let total_qtypro = $('.cart-count-total');

function updateCartTotals(detalle, options) {
    if (!detalle) return;
    if (detalle.total_orden !== undefined && detalle.total_orden !== null) {
        total_monto.text(detalle.total_orden);
    }
    if (detalle.cantidad_productos !== undefined && detalle.cantidad_productos !== null) {
        total_qtypro.text(detalle.cantidad_productos);
        if (parseInt(detalle.cantidad_productos, 10) > 0) {
            total_qtypro.removeClass('d-none');
        } else {
            total_qtypro.addClass('d-none');
        }
    }
    if ((!options || !options.skipMiniCartRefresh) && typeof window.refreshMiniCart === 'function') {
        window.refreshMiniCart();
    }
}
var _short = $('#short').niceSelect();

function applyCatalogSort(shortVal) {
	var sortValue = String(shortVal || '1');
	$('.short-value').val(sortValue);

	var params = new URLSearchParams(window.location.search || '');
	var currentToken = params.get('token') || (typeof token !== 'undefined' ? token : '') || '';
	if (!currentToken) {
		return;
	}

	params.set('token', currentToken);
	params.set('short', sortValue);
	params.delete('page');

	var catalogPath = (typeof url_base !== 'undefined' ? url_base : '/') + 'catalogo/producto';
	window.location.href = catalogPath + '?' + params.toString();
}

$(document).on('change', '#short', function () {
	applyCatalogSort($(this).val());
});

const exampleEl = document.querySelectorAll('.buttonpop')


let activar_popover = (element)=>{
	const langFromEnv = (typeof env !== 'undefined' && env.lang && env.lang.popover_catalog && env.lang.popover_catalog.lang) ? env.lang.popover_catalog.lang : null;
	const lang = {
		choose_qty: (langFromEnv && langFromEnv.choose_qty) || (typeof popoverChooseQty !== 'undefined' ? popoverChooseQty : 'Quantity'),
		qty: (langFromEnv && langFromEnv.qty) || (typeof popoverQty !== 'undefined' ? popoverQty : 'Qty'),
		save: (langFromEnv && langFromEnv.save) || (typeof popoverSave !== 'undefined' ? popoverSave : 'Save'),
		comment: (langFromEnv && langFromEnv.comment) || (typeof popoverComment !== 'undefined' ? popoverComment : 'Comment'),
		remove_fromcart: (langFromEnv && langFromEnv.remove_fromcart) || (typeof popoverRemoveFromCart !== 'undefined' ? popoverRemoveFromCart : 'Remove from cart')
	};
	exampleEl.forEach(function (element, key, parent) {
		let id = element.getAttribute('data-id'),
		comment = element.getAttribute('data-comment')

		let clase = '#figure-'+id + '.single_product'
		let popover = new bootstrap.Popover(element, {

			html: true,
			sanitize: false,
			container: clase,
			placement: 'top',
			title : `<h3 class="popover-header border-0"><div>${lang.choose_qty} <button type="button" class="btn-close float-end close-popover"><span class="visually-hidden">Close</span></button></h3>`,
			content : `<div style="margin-top: 10px;" class="chooseQuantityContainer" data-reload-on-change-quantity="0">
			<div class="row">
			<div class="col-md-12">
			<form class="form-save-qty" data-figure-container="${clase}" method="post" action="${(typeof url_base !== 'undefined' ? url_base : (env && env.url ? env.url : ''))}catalogo/producto/saveqty">
			<input type="hidden" name="token" value="${typeof token !== 'undefined' ? token : ''}">
			<input type="hidden" name="origen" value="list">
			<input hidden type="text" name="product_id" value="${id}">
			<div class="input-group">
			<input id="inpput-${id}" type="number" tabindex="0" min="0" step="1" name="qty" class="form-control quantity" data-product-id="${id}" value="" placeholder="${lang.qty}...">
			<button class="btn btn-success addQuantityToCart" type="submit" data-loading-text="Loading..." data-product-id="${id}">${lang.save}</button>
			</div>
			<br>
			<textarea name="comment" class="form-control border" style="height: auto;" placeholder="${lang.comment}">${comment || ''}</textarea>
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
	activar_popover(exampleEl)
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


// var cerrado = null
// $(document).on("mousemove", ".single_product" , function(e){

// 	if ($(this).find('.add_to_cart').css('visibility') != 'visible') {

// 	}
// });

function animate_text(element) {

	element.removeClass('animate__fadeOutUp')
	element.addClass('animate__zoomIn animate_02s')
}



//AGREGAR cantidad mínima (botón +1, +24 al lado de ADD)
$(document).on("click", ".addqtyminium" , function(e){

    $.toast().reset('all');
    let id = $(this).data('id')
    let element = $(this)
    var baseUrl = (typeof url_base !== 'undefined' ? url_base : (typeof env !== 'undefined' && env && env.url ? env.url : ''))
    var quickAddUrl = baseUrl + 'catalogo/producto/quickAddProduct/' + id
    element.removeClass('animate__animated animate__zoomIn animate_02s')
    $.ajax({

        url: quickAddUrl,
        type: 'get',
        data: { token: typeof token !== 'undefined' ? token : '' },
        dataType: 'json',
        beforeSend: function () {
            element.find('.spinner-border').removeClass('d-none')
            element.prop('disabled', true)
            element.prev().prop('disabled', true)
            element.prev().find('.spinner-border').removeClass('d-none')


        },
        success: function (response) {

            if (response.success) {
                $.toast({
                    text: response.mesagge || response.message || '',
                    hideAfter: 1500,
                    icon: 'success',
                    position: 'top-center'
                })

                updateCartTotals(response.detalle);
                print_qty(id, response.cantidad_actual)
            } else {
                if (typeof swalalert !== 'undefined' && swalalert.fire) {
                    swalalert.fire({
                        icon: 'error',
                        html: (response.message && response.message.message) ? response.message.message : (response.message || 'Error')
                    });
                }
            }
            element.addClass('animate__animated animate__fadeOutUp animate_02s')
            setTimeout(animate_text, 400, element);
            element.find('.spinner-border').addClass('d-none')
            element.prop('disabled', false)

            element.prev().prop('disabled', false)
            element.prev().find('.spinner-border').addClass('d-none')

            $(".buttonpop").popover('hide');
        },
        error: function(xhr) {
            element.find('.spinner-border').addClass('d-none');
            element.prop('disabled', false);
            element.prev().prop('disabled', false);
            element.prev().find('.spinner-border').addClass('d-none');
            if (typeof swalalert !== 'undefined' && swalalert.fire) {
                var errMsg = (xhr && xhr.responseJSON && (xhr.responseJSON.message || xhr.responseJSON.mesagge)) ? (xhr.responseJSON.message.message || xhr.responseJSON.message || xhr.responseJSON.mesagge) : 'Error al agregar el producto';
                swalalert.fire({ icon: 'error', html: errMsg });
            }
        }
    })

});

//GUARDAR desde la tabla
//form-save-qty
$(document).on("submit", ".form-save-qty" , function(e){
	 e.preventDefault();
	$.toast().reset('all');
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
		data: $(this).serializeArray(),
		dataType: 'json',
		beforeSend: function(){

			element.find('.spinner-border').removeClass('d-none')
			element.prop('disabled',true)
			element.prev().prop('disabled',true)
			element.prev().find('.spinner-border').removeClass('d-none')

		},
		success: function(response){
			if (response.success) {
                $.toast({
                    text: response.mesagge || response.message || '',
                    hideAfter: 1500,
                    icon: 'success',
                    position: 'top-center'
                })

                updateCartTotals(response.detalle);
				if (response.cantidad_actual <= 0) {
					total_qtypro.addClass('d-none')
				}

				$(".buttonpop").popover('hide');

				button_pop.attr('data-comment',response.comment)
				print_qty(pro_id,response.cantidad_actual)

				$(".buttonpop").popover('disable');
				activar_popover(exampleEl)

                document.activeElement.blur();
                $(e.target).closest('.modal').removeClass('show');
                $('.modal-backdrop').removeClass('show');

            }else{
                swalalert.fire({
                    icon: 'error',
                    html: (response.message && response.message.message) ? response.message.message : (response.mesagge || response.message || 'Error')
                });
			}
			element.find('.spinner-border').addClass('d-none')
			element.prop('disabled',false)

			element.prev().prop('disabled',false)
			element.prev().find('.spinner-border').addClass('d-none')
		},
		error: function(xhr) {
			element.find('.spinner-border').addClass('d-none');
			element.prop('disabled', false);
			element.prev().prop('disabled', false);
			element.prev().find('.spinner-border').addClass('d-none');
			var errMsg = 'Error al agregar el producto';
			if (xhr && xhr.responseJSON) {
				if (xhr.responseJSON.mesagge) errMsg = xhr.responseJSON.mesagge;
				else if (xhr.responseJSON.message) errMsg = (typeof xhr.responseJSON.message === 'string') ? xhr.responseJSON.message : (xhr.responseJSON.message.message || errMsg);
			} else if (xhr && xhr.responseText) {
				try { var j = JSON.parse(xhr.responseText); if (j.mesagge || j.message) errMsg = j.mesagge || j.message; } catch (e) {}
			}
			if (typeof swalalert !== 'undefined' && swalalert.fire) swalalert.fire({ icon: 'error', html: errMsg });
		},
		complete: function() {
			element.find('.spinner-border').addClass('d-none');
			element.prop('disabled', false);
			if (element.prev().length) {
				element.prev().prop('disabled', false);
				element.prev().find('.spinner-border').addClass('d-none');
			}
		}
	})

});

//eliminar


$(document).on("click", ".removeproductocart" , function(e){

	e.preventDefault();

	let id = $(this).data('product-id')

	$.ajax({
		url: (typeof url_base !== 'undefined' ? url_base : (env && env.url ? env.url : '')) + 'catalogo/producto/eliminar',
		type: 'post',
		data: { token: typeof token !== 'undefined' ? token : '', id: id },
		dataType: 'json',
		success: function(response){
			if (response.success) {
				updateCartTotals(response.detalle);
				if (response.detalle && parseInt(response.detalle.cantidad_productos, 10) <= 0) {
					total_qtypro.addClass('d-none')
				}
				$.toast({
					text: response.msg,
					hideAfter : 1500,
					icon: 'success',
					position : 'top-center'
				})
				print_qty(id,response.cantidad_actual)
				$(".buttonpop").popover('hide');
			}
		}
	})
});


//GUARDAR desde el modal  m-btn-submit


$(document).on("submit", "#form-modal" , function(e){
	e.preventDefault();

	let modalbtn_submit = $('#m-btn-submit')
	let pro_id = $(this).find('input[name="product_id"]').val()


	$.ajax({

		url: $(this).attr('action'),
		type:'post',
		dataType: 'json',
		data: $(this).serializeArray(),
		dataType: 'json',
		beforeSend: function(){
			console.log(modalbtn_submit)
			modalbtn_submit.find('.spinner-border').removeClass('d-none')
			modalbtn_submit.prop('disabled',true)

		},
		success: function(response){

			console.log(response)
			if (response.success) {

				$.toast({

					text: response.mesagge,
					hideAfter : 1500,
					icon: 'success',
					position : 'top-center'
				})

				updateCartTotals(response.detalle);
				if (response.detalle && parseInt(response.detalle.cantidad_productos, 10) <= 0) {
					total_qtypro.addClass('d-none')
				}

				$(".buttonpop").popover('hide');
				$('#modal_box').modal('hide')

				print_qty(pro_id,response.cantidad_actual)
				//evaluar las cantidad total de productos
			}else{

				$.toast({

					text: response.mesagge,
					hideAfter : 1500,
					icon: 'error',
					position : 'top-center'
				})

				$('#modal_box').modal('hide')
			}

			modalbtn_submit.find('.spinner-border').addClass('d-none')
			modalbtn_submit.prop('disabled',false)


		}

	})

});


function print_qty(car_proid,qty_actual){

	let clase = '#card-' + car_proid + ' .buttonpop .text'



	if (qty_actual === undefined) {
		$(clase).text(' ' + add_product + ' ')
	}else{
		if (qty_actual>0) {
			$(clase).text(' '+qty_actual+' ')
		}else{
			$(clase).text(' ' + add_product + ' ')
		}


	}


}

const FV_CATALOG_CAROUSEL_INTERVAL_MS = 4000;

function escapeAttr(value) {
	return String(value || '')
		.replace(/&/g, '&amp;')
		.replace(/"/g, '&quot;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;');
}

/**
 * Pop-hover de imagen en cards del catálogo público (Bootstrap 5).
 * Mismo look que lista de productos / categorías del panel company.
 */
function initCatalogImagePopovers(root) {
	if (typeof bootstrap === 'undefined' || typeof bootstrap.Popover !== 'function') {
		return false;
	}

	const scope = root && root.querySelectorAll ? root : document;
	const els = scope.querySelectorAll('[data-toggle="popover-hover"]');
	if (!els.length) {
		return true;
	}

	const tpl = '<div class="popover fv-popover-img-preview" role="tooltip">'
		+ '<div class="popover-arrow"></div>'
		+ '<div class="popover-body"></div>'
		+ '</div>';

	els.forEach((el) => {
		const existing = bootstrap.Popover.getInstance(el);
		if (existing) {
			try {
				existing.dispose();
			} catch (e) { /* ignore */ }
		}

		const dataImg = String(el.getAttribute('data-img') || '').trim();
		if (!dataImg) {
			return;
		}

		new bootstrap.Popover(el, {
			html: true,
			trigger: 'hover focus',
			placement: 'right',
			fallbackPlacements: ['left', 'top', 'bottom'],
			container: 'body',
			sanitize: false,
			title: '',
			template: tpl,
			content: function () {
				const src = String(this.getAttribute('data-img') || '').trim();
				if (!src) {
					return '';
				}
				return '<div class="fv-popover-img-preview__inner">'
					+ '<img src="' + escapeAttr(src) + '" alt="" class="fv-popover-img-preview__photo" draggable="false">'
					+ '</div>';
			}
		});
	});

	return true;
}

function initCatalogImagePopoversWhenReady(attempt) {
	attempt = attempt || 0;
	if (initCatalogImagePopovers(document)) {
		return;
	}
	if (attempt < 120) {
		setTimeout(function () {
			initCatalogImagePopoversWhenReady(attempt + 1);
		}, 50);
	}
}

function initCatalogProductSlideshows() {
	document.querySelectorAll('.fv-catalog-card-slideshow').forEach((container) => {
		if (container.dataset.fvSlideshowInit === '1') {
			return;
		}
		container.dataset.fvSlideshowInit = '1';

		const slides = container.querySelectorAll('.fv-catalog-slide');
		if (slides.length < 2) {
			return;
		}

		let current = 0;
		let timer = null;
		const card = container.closest('.card-product');
		const counter = card ? card.querySelector('.label_product .label_sale span') : null;
		const hoverHost = container.closest('[data-toggle="popover-hover"]');

		const showSlide = (index) => {
			slides[current].classList.remove('is-active');
			current = index;
			slides[current].classList.add('is-active');
			if (counter) {
				counter.innerText = slides[current].getAttribute('data-index') || String(current + 1);
			}
			if (hoverHost) {
				const src = String(slides[current].getAttribute('src') || '').trim();
				if (src) {
					hoverHost.setAttribute('data-img', src);
				}
			}
		};

		const startTimer = () => {
			if (timer) {
				clearInterval(timer);
			}
			timer = setInterval(() => {
				showSlide((current + 1) % slides.length);
			}, FV_CATALOG_CAROUSEL_INTERVAL_MS);
		};

		container.addEventListener('mouseenter', () => {
			if (timer) {
				clearInterval(timer);
				timer = null;
			}
		});

		container.addEventListener('mouseleave', startTimer);

		startTimer();
	});
}

function initCatalogProductMedia() {
	initCatalogProductSlideshows();
	initCatalogImagePopoversWhenReady(0);
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initCatalogProductMedia);
} else {
	initCatalogProductMedia();
}

window.fvInitCatalogImagePopovers = initCatalogImagePopovers;

$(document).on("mouseover", ".over-category", function(e) {


	const cadenaNumeros = String($(this).data('categories'));
	const arrayNumeros = cadenaNumeros.split(",");

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

	// const cadenaNumeros = $(this).data('categories')
	const cadenaNumeros = String($(this).data('categories'));
	// console.log(cadenaNumeros)
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
