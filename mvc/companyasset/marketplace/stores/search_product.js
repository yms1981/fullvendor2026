const {
    createApp,
    ref,
    onBeforeMount,
    computed
} = Vue;

createApp({
    setup() {

        // Obtener la URL actual
        const url = new URL(window.location.href);
        // Obtener el parámetro 'search' de la URL
        const searchParam = url.searchParams.get('search');

        const searchProduct = ref(searchParam?searchParam:'');
        const product = ref({
            id: null
        });

        onBeforeMount(async () => {
            // await store.dispatch('Products/fetchProducts');
        });

        const accept_product = (event) => {
            //console.log("click")
            // event.preventDefault()
            add_product()
            product.value.id = null
            searchProduct.value = ''

        }

        const select_product = (event, pro) => {
            // console.log(cus.customer_name)
            product.value.id = pro.pro_id
            searchProduct.value = pro.name.toUpperCase()
            // obtenerformulario(pro.pro_id)
            setTimeout(function() {
                document.getElementById('btn_search_producto').click();
            }, 500);
        }

        const handleSearch = async (event) => {

            if (searchProduct.value.length > 0) {
                const formData = new FormData();
                formData.append('search_value', searchProduct.value);
                await store.dispatch('Products/fetchProducts', formData)
            }


            // Aquí puedes realizar la búsqueda o cualquier acción que desees
            // En este ejemplo, simplemente imprimimos el valor de la búsqueda
            const productBySearch = store.getters['Products/getProductByName'](searchProduct.value);

            if (productBySearch && productBySearch.length === 1) {
                return product.value.id = productBySearch[0].pro_id
                // console.log("Se encontró un único registro:", customerBySearch[0]);
            }else{
                product.value.id = null
            }
            // if(searchCustomer.value.length === 0) customer.value.id = null
            const input_search = event.srcElement
            const menu = input_search.nextElementSibling


        };

        return {
            searchProduct,
            product,
            select_product,
            handleSearch,
            accept_product,
            getProductbySearch: computed( () => {
                return store.getters['Products/getProductSearch'](searchProduct.value)}),
        }
   }
}).use(store).mount('#search_product');
