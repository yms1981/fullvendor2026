function buildCompletedName(product) {
    const name = product.name || '';
    const sku = product.sku || '';

    return `${name} | ${sku}`;
}
const store = Vuex.createStore({
    modules: {
         Products: {
             namespaced:true,
             state: {
                 products: [],
                 selectedProduct: null
             },
             mutations: {
                SET_PRODUCTS(state, products) {
                    state.products = products;
                },
                 SET_SELECTED_PRODUCT(state, product) {
                     state.selectedProduct = product;
                 }
             },
             actions: {
                async fetchProducts({ commit }, filter_search) {
                    try {
                        const response = await fetch(`${env.url}/searchProducts?term=${encodeURIComponent(filter_search)}`);
                        const data = await response.json();
                        commit('SET_PRODUCTS', data.data);

                    } catch (error) {
                        console.error('Error al obtener los products:', error);
                    }
                },
                 async fetchProduct({ commit }, product_id) {
                     try {
                         const response = await fetch(`${env.url}/getProductById?id=${encodeURIComponent(product_id)}`);
                         const data = await response.json();

                         commit('SET_SELECTED_PRODUCT', data);

                     } catch (error) {
                         console.error('Error al obtener el producto:', error);
                     }
                 }
             },
             getters: {
                 getSelectedProduct: state => state.selectedProduct,
                 getProducts: state => state.products,
                 getProductSearch: (state) => (search_text = '') => {

                    if (search_text.length === 0) return [];
                    const searchWords = search_text.toLowerCase().split(' ').filter(word => word.length > 0);

                    const filteredProducts = state.products.filter(product => {
                        const productName = buildCompletedName(product).toLowerCase();

                        const atLeastOneWordMatch = searchWords.some(word => {
                            return productName.includes(word.trim());
                        });

                        if (atLeastOneWordMatch) {
                            const highlightedName = productName.replace(
                                new RegExp(`(${searchWords.join('|')})`, 'gi'),
                                '<mark>$1</mark>'
                            );
                            product.highlighted_name = highlightedName.toUpperCase();
                            return true;
                        }

                        return false;
                    });

                    const limit = Math.min(filteredProducts.length, 100);
                    return filteredProducts.slice(0, limit);

                 },
                 getProductByName: (state) => (exactName = '') => {
                     if (exactName.length === 0) return [];

                     return state.products.filter(product =>
                         buildCompletedName(product).toLowerCase() === exactName.toLowerCase()
                     );
                 }
             }
         },
     }
 });
