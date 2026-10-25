const update_cartfromsession = async () => {

	const response = await fetch(env.url+'/shop/get_productsession',{
        method: 'GET'
    });
    
    const data_response = await response.json();
    console.log(data_response)
	cart.products = [...data_response]
}