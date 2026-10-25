const {
    createApp,
    ref,
    onBeforeMount,
    computed
} = Vue;

createApp({
    setup() {

        const searchProduct = ref('');
        const product = ref({
            id: null
        });
        
        // evento load
        onBeforeMount(async () => {
            // llama a la funcion fetchProducts que esta en mi store de productos que he creado y llena los registros al store
            await store.dispatch('CompProducts/fetchProducts');
        });

        const accept_product = (event) => {
            
            add_product()
            product.value.id = null
            searchProduct.value = ''

        }

        const select_product = (event, pro) => {
            
            product.value.id = pro.pro_id
            searchProduct.value = pro.completed_name.toUpperCase()
            obtenerformulario(pro.pro_id)
        }

        const handleSearch = (event) => {
                                    
            // Aquí puedes realizar la búsqueda o cualquier acción que desees
            // En este ejemplo, simplemente imprimimos el valor de la búsqueda
            const productBySearch = store.getters['CompProducts/getProductByName'](searchProduct.value);
            if (productBySearch && productBySearch.length === 1) {       
                return product.value.id = productBySearch[0].pro_id
                
            }else{
                product.value.id = null
            }
            
            const input_search = event.srcElement
            const menu = input_search.nextElementSibling

            if (input_search.value.length > 0 && 
                input_search === document.activeElement &&
                !menu.classList.contains('show')) {
                // Realizar la acción que deseas aquí
                event.srcElement.click()
            }
            
        };

        return {
            searchProduct,
            product,
            select_product,
            handleSearch,
            accept_product,
            getProductbySearch: computed(() => store.getters['CompProducts/getProductSearch'](searchProduct.value)),// llama a la funcion de store
        }
   }
}).use(store).mount('#app');  // Le pasamos el store que creamos y el id del componente HTML donde deseas que apunte todoa estas funcioens