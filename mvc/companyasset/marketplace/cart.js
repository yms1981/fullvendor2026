const renderCart = () => {


}

const addToCart = (product,increase = false) => {
	
	if (product) {
		const productFound = searchProdutoinTheCart(product)	
		
		if (productFound) return updateCart(product, increase)
		if (product.qty == null) return delete_productCart(product)
		if (product.qty <= 0) return delete_productCart(product)

		cart.product_chosed = {...product}
        cart.products.push({...product});
		openToats_message('Product Agregado!', 'success')
        renderCart();



		//
    }
}

const searchProdutoinTheCart = (product) => {
		
	const productFound = cart.products.find(function(productFind) {
		return productFind.id == product.id;
	});
	console.log(productFound)
	return productFound
}

const searchProdutoinTheCart_byIndex = (product_id) => {
		
	const productFound = cart.products.find(function(productFind) {
		return productFind.id == product_id;
	});	
	return productFound
}

const searchProdutoinTheCartbyIndex = (product_toSearch) => {
	console.log(product_toSearch.id)
    const productIndex = cart.products.findIndex(function(product) {
        return product.id == product_toSearch.id;
    });
    return productIndex;
};

const updateCart = (product, increase = false) => {	

	const index = searchProdutoinTheCartbyIndex(product)	
	// eliminamos el producto si es menor o igual a 0
	if (product.qty <= 0) return delete_productCart(product)
	
	// actualizamos el producto 
	cart.product_chosed = {...product}

	if (increase) {
		//cart.products[index].qty = cart.products[index].qty + product.qty;
		cart.products[index].qty = parseInt(cart.products[index].qty) + parseInt(product.qty);
		cart.product_chosed.qty = cart.products[index].qty
	}else{
		cart.products[index] = {...product};
	}
	openToats_message('Product Actualizado!', 'success')	
}

const delete_productCart = (product) => {
	
	const index = searchProdutoinTheCartbyIndex(product)
	if (index !== -1) cart.products.splice(index, 1);

	cart.product_chosed = null
	openToats_message('Product Eliminado!', 'success')
}

const openToats_message = (text, icon) => {
	$.toast({
		text: text,
		hideAfter : 1500,
		icon:  icon,
		position : 'top-center',
	})
}

const update_stateAddProducto = (product) => {    
	const index = searchProdutoinTheCartbyIndex(product)	
	cart.products[index].add = false;
}

const delete_product = (idProduct) => {
	
	const product = searchProdutoinTheCart_byIndex(idProduct)
	delete_productCart(product)
}

// const update_localStorage = () => {
//     localStorage.setItem("cart", JSON.stringify(cart));
// }