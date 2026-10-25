const {
    createApp,
    ref,
    onBeforeMount,
    computed
} = Vue;

var orderVueApp = createApp({
    setup() {
        
        const searchCustomer = ref('');
        const customer = ref({
            id: null
        });

        onBeforeMount(async () => {            
            await store.dispatch('CompCustomers/fetchClients');
        });

        const customerDisplayName = (cus) => {
            if (!cus) return '';
            const name = cus.customer_name || cus.customerName || (cus.business_name && cus.name ? cus.business_name + ' (' + cus.name + ')' : '');
            return String(name).toUpperCase();
        };

        const select_customer = (event, cus) => {
            customer.value.id = cus.customer_id != null ? cus.customer_id : cus.customerId;
            searchCustomer.value = customerDisplayName(cus);
        }
        const handleFocus = (event) => {

            if (event.srcElement.value.length > 0) {
                // Realizar la acción que deseas aquí
                // event.srcElement.click()
                // console.log("Valor del campo de búsqueda:", event.srcElement.value);
            }
        }

        const clickSearch = (event) => {
            //console.log("click")
            // event.preventDefault()
        }

        const handleSearch = (event) => {
            // console.log(event.srcElement.value)
                        
            // Aquí puedes realizar la búsqueda o cualquier acción que desees
            // En este ejemplo, simplemente imprimimos el valor de la búsqueda
            const customerBySearch = store.getters['CompCustomers/getCustomerByName'](searchCustomer.value);
            if (customerBySearch && customerBySearch.length === 1) {       
                return customer.value.id = customerBySearch[0].customer_id
                // console.log("Se encontró un único registro:", customerBySearch[0]);
            }else{
                customer.value.id = null
            }
            // if(searchCustomer.value.length === 0) customer.value.id = null
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
            searchCustomer,
            select_customer,
            customer,
            customerDisplayName,
            handleSearch,
            handleFocus,
            clickSearch,
            getCustomerbySearch: computed(() => store.getters['CompCustomers/getCustomerbySearch'](searchCustomer.value)),
        };
    }
});
var appEl = document.querySelector('#app');
if (appEl) {
    orderVueApp.use(store).mount('#app');
} else {
    console.warn('order_vue: #app no encontrado, no se monta la app Vue.');
}
