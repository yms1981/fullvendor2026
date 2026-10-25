const store = Vuex.createStore({
    modules: {
        CompCustomers: {
            namespaced:true,
            state: {
                customers: [],
            },
            mutations: {
                SET_CUSTOMERS(state, customers) {
                    state.customers = customers;
                },
            },
            actions: {
                async fetchClients({ commit }) {
                    try {
                        const baseUrl = (typeof options !== 'undefined' && options.url) ? options.url : '';
                        if (!baseUrl) {
                            console.error('options.url no está definido');
                            commit('SET_CUSTOMERS', []);
                            return;
                        }
                        const response = await fetch(`${baseUrl}/orders/getCustomer`);
                        if (!response.ok) {
                            commit('SET_CUSTOMERS', []);
                            return;
                        }
                        const data = await response.json();
                        const list = Array.isArray(data.data) ? data.data : (Array.isArray(data) ? data : []);
                        const normalized = list.map(c => ({
                            ...c,
                            customer_id: c.customer_id != null ? c.customer_id : c.customerId,
                            customer_name: c.customer_name != null && c.customer_name !== '' ? c.customer_name : (c.customerName || (c.business_name && c.name ? c.business_name + ' (' + c.name + ')' : '')),
                            cust_id_kor: c.cust_id_kor != null ? c.cust_id_kor : c.custIdKor,
                            tax_id: c.tax_id != null ? c.tax_id : c.taxId
                        }));
                        commit('SET_CUSTOMERS', normalized);
                    } catch (error) {
                        console.error('Error al obtener los clientes:', error);
                        commit('SET_CUSTOMERS', []);
                    }
                },
            },
            getters: {
                getClients: state => state.customers,
                getCustomerbySearch: (state) => (search_text = '') => {

                    if (search_text.length === 0)  return []

                    // Id ERP
                    let filteredCustomers = state.customers.filter(customer => {
                        const custIdKor = customer.cust_id_kor != null ? String(customer.cust_id_kor) : '';
                        return custIdKor !== '' && custIdKor === search_text;
                    });

                    // Tax Id
                    if (filteredCustomers.length <= 0) {
                        filteredCustomers = state.customers.filter(customer => {
                            const taxId = customer.tax_id != null ? String(customer.tax_id) : '';
                            return taxId.toLowerCase().includes(search_text.toLowerCase());
                        });
                    }

                    if (filteredCustomers.length <= 0) {
                        filteredCustomers = state.customers.filter(customer => {
                            const name = customer.customer_name != null ? String(customer.customer_name) : '';
                            return name.toLowerCase().includes(search_text.toLowerCase());
                        });
                    }
                    // Devolver solo los primeros 100 registros o menos si hay menos resultados
                    const limit = Math.min(filteredCustomers.length, 100);
                    return filteredCustomers.slice(0, limit);

                },
                // Funcion para Filtrar por nombre
                getCustomerByName: (state) => (exactName = '') => {
                    if (exactName.length === 0) return [];

                    return state.customers.filter(customer => {
                        const name = customer.customer_name != null ? String(customer.customer_name) : '';
                        return name.toLowerCase() === exactName.toLowerCase();
                    });
                }
            }
        },

        CompProducts: {
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
                async fetchProducts({ commit }) {
                    try {
                        const response = await fetch(`${options.url}/products/allproducts`);
                        const data = await response.json();
                        commit('SET_PRODUCTS', data);
                    } catch (error) {
                        console.error('Error al obtener los products:', error);
                    }
                },
            },
            getters: { //Los getters son funcionar para filtrar

                // Funcion para obtener
                getProducts: state => state.products,
                getProductSearch: (state) => (search_text = '') => {

                    if (search_text.length === 0)  return []

                    // Filtrar los clientes cuyo nombre coincide con el texto de búsqueda
                    const filteredProducts = state.products.filter(product =>
                        product.completed_name.toLowerCase().includes(search_text.toLowerCase())
                    );

                    // Devolver solo los primeros 100 registros o menos si hay menos resultados
                    const limit = Math.min(filteredProducts.length, 100);
                    return filteredProducts.slice(0, limit);

                },
                // Funciona para filtrar por nombre
                getProductByName: (state) => (exactName = '') => {
                    if (exactName.length === 0) return [];

                    return state.products.filter(product =>
                        product.completed_name.toLowerCase() === exactName.toLowerCase()
                    );
                }
            }
        },

        CompCategories: {
            namespaced:true,
            state: {
                categories: [],
            },
            mutations: {
                SET_CATEGORIES(state, categories) {
                    state.categories = categories;
                },
            },
            actions: {
                async fetchCategories({ commit }) {
                    try {
                        const response = await fetch(`${options.url}/categories/allcategories`);
                        const data = await response.json();
                        commit('SET_CATEGORIES', data);
                    } catch (error) {
                        console.error('Error al obtener las categorías:', error);
                    }
                },
            },
            getters: {
                // Función para obtener todas las categorías
                getCategories: state => state.categories,

                // Función para buscar categorías por texto
                getCategorySearch: (state) => (search_text = '') => {
                    if (search_text.length === 0) return []

                    // Filtrar las categorías cuyo nombre coincide con el texto de búsqueda
                    const filteredCategories = state.categories.filter(category =>
                        category.category_name.toLowerCase().includes(search_text.toLowerCase())
                    );

                    // Devolver solo los primeros 100 registros o menos si hay menos resultados
                    const limit = Math.min(filteredCategories.length, 100);
                    return filteredCategories.slice(0, limit);
                },

                // Función para filtrar por nombre exacto
                getCategoryByName: (state) => (exactName = '') => {
                    if (exactName.length === 0) return [];

                    return state.categories.filter(category =>
                        category.category_name.toLowerCase() === exactName.toLowerCase()
                    );
                }
            }
        },
    }
});
