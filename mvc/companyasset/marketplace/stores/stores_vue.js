const store = Vuex.createStore({
    modules: {
         Products: {
             namespaced:true,
             state: {
                 products: [],
             },
             mutations: {
                SET_PRODUCTS(state, products) {
                    state.products = products;
                },
             },
             actions: {
                async fetchProducts({ commit }, filter_search) {


                    try {

                        const response = await fetch(`${UrlBase}marketplace/catalog/products`, {
                            method: 'POST',
                            body: { term:filter_search }
                        });

                        const data = await response.json();
                        commit('SET_PRODUCTS', data);

                    } catch (error) {
                        console.error('Error al obtener los products:', error);

                    }
                 },
             },
             getters: {
                 getProducts: state => state.products,
                 getProductSearch: (state) => (search_text = '') => {

                    if (search_text.length === 0)  return []
                    // Dividir el texto de búsqueda en palabras y filtrar las palabras vacías
                    // return state.products

                    const searchWords = search_text.toLowerCase().split(' ').filter(word => word.length > 0);
                    console.log('Palabras de búsqueda:', searchWords);

                    // Filtrar los productos cuyo nombre completo coincida con todas las palabras de búsqueda
                    const filteredProducts = state.products.filter(product => {
                        const productName = product.completed_name.toLowerCase();
                        // Verificar si al menos una de las palabras de búsqueda está presente en el nombre del producto
                        const atLeastOneWordMatch = searchWords.some(word => {
                            // console.log('Buscando palabra:', word);
                            // console.log('Nombre del producto:', productName);
                            return productName.includes(word.trim()); // Asegúrate de eliminar los espacios en blanco adicionales
                        });

                        if (atLeastOneWordMatch) {
                            // Resaltar las palabras coincidentes en el nombre del producto
                            const highlightedName = productName.replace(
                                new RegExp(`(${searchWords.join('|')})`, 'gi'),
                                '<mark>$1</mark>'
                            );
                            product.highlighted_name = highlightedName.toUpperCase();
                            return true; // Mantener este producto en los resultados filtrados
                        }
                        return false; // Descartar este producto de los resultados filtrados
                    });

                    // Devolver solo los primeros 100 registros o menos si hay menos resultados
                    const limit = Math.min(filteredProducts.length, 100);
                    return filteredProducts.slice(0, limit);

                 },
                 getProductByName: (state) => (exactName = '') => {
                    if (exactName.length === 0) return [];

                    return state.products.filter(product =>
                        product.completed_name.toLowerCase() === exactName.toLowerCase()
                    );
                 }
             }
         },
     }
 });
