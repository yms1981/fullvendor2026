const go_order = () => {
    
    // Obtener la URL actual
    const urlActual = `${url_base}marketplace/shop/order`;
    // Convertir el objeto a una cadena JSON y codificarla para que sea parte de la URL
    const objetoCarritoCodificado = encodeURIComponent(JSON.stringify(cart));
    // Crear la nueva URL con el objeto carrito
    const nuevaURL = `${urlActual}?cart=${objetoCarritoCodificado}`;
    // Redirigir a la nueva URL
    window.location.href = nuevaURL;


}