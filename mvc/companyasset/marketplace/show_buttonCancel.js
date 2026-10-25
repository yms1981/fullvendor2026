const show_button_cancel = () => {

    if(cart.products.length <= 0) {
		
		card_empty.removeClass('d-none')
		card_empty.addClass('animate__animated')
		card_empty.addClass('animate__backInLeft')
		
		btn_cancel.addClass('d-none')

	}else{
		order_complete.removeClass('d-none')
		order_complete.addClass('animate__animated')
		order_complete.addClass('animate__fadeIn')

		btn_cancel.removeClass('d-none')
		btn_cancel.addClass('animate__animated')
		btn_cancel.addClass('animate__zoomIn')
	}
}