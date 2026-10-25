const {
    createApp,
    ref,
    computed,
    watch,
    nextTick,
    onMounted
} = Vue;

createApp({
    setup() {

        // Obtener la URL actual
        const url = new URL(window.location.href);
        // Obtener el parámetro 'search' de la URL
        const searchParam = url.searchParams.get('search');
        const buttonsTooltip = document.querySelectorAll('[data-tippy-arrow="true"]');
        // const table_products = null
        const searchProduct = ref(searchParam?searchParam:'');
        const product = ref({
            id: null
        });

        const selectedProduct = ref(null);
        const productModal = ref(null);

        const isFocused = ref(false);
        const inputRef = ref(null);
        const dropdownRef = ref(null);

        let searchTimeout;


        const accept_product = (event) => {
            add_product()
            product.value.id = null
            searchProduct.value = ''

        }

        const closeModal = () => {
            if (!productModal.value) return;

            productModal.value.classList.remove('show');
            productModal.value.style.display = 'none';
            productModal.value.setAttribute('aria-hidden', 'true');
            productModal.value.removeAttribute('aria-modal');

            productModal.value.removeEventListener('click', handleBackdropClick);

            store.commit('Products/SET_SELECTED_PRODUCT', null);

        };

        const handleBackdropClick = (event) => {
            if (event.target === productModal.value) {
                closeModal();
            }
        };

        const select_product = async (product_id) => {
            await store.dispatch('Products/fetchProduct', product_id);
            selectedProduct.value = store.getters['Products/getSelectedProduct'];

            await nextTick();

            if (productModal.value) {
                productModal.value.classList.add('show');
                productModal.value.style.display = 'block';
                productModal.value.removeAttribute('aria-hidden');
                productModal.value.setAttribute('aria-modal', 'true');
                productModal.value.addEventListener('click', handleBackdropClick);
            }

            isFocused.value = false;

            initializeCarousel();
        };

        const handleBlur = () => {
            setTimeout(() => {
                isFocused.value = false;
            }, 150);
        };

        const handleFocus = () => {
            isFocused.value = true;
        };

        const handleSearch = (event) => {

            const term = event && event.target ? event.target.value : searchProduct.value;

            clearTimeout(searchTimeout);

            searchTimeout = setTimeout(() => {
                if (term.length > 0) {
                    store.dispatch('Products/fetchProducts', term);
                }
            }, 400);
        };

        onMounted(() => {
            const input = document.getElementById('input-search-product');
            if (input) {
                searchProduct.value = input.value || '';
                input.addEventListener('input', (e) => { searchProduct.value = e.target.value; });
                input.addEventListener('keyup', handleSearch);
            }
        });

        const sortedImages = computed(() => {
            if (!getSelectedProduct.value || !getSelectedProduct.value.images) return [];

            const images = getSelectedProduct.value.images.map(img => img);

            return images.sort((a, b) => {
                if (a === getSelectedProduct.value.images) return -1;
                if (b === getSelectedProduct.value.images) return 1;
                return 0;
            });
        });

        const noImageSrc = `${env.base_url}uploads/noimg.png`;//`${import.meta.env.BASE_URL}uploads/noimg.png?v=${version}`;

        const getImageSrc = (img) =>
            `${env.base_url}uploads/products/${companyId}/${img}?v=${version ?? ''}`;
        const handleImageError = (event) => {
            event.target.src = noImageSrc;
        };

        const getSelectedProduct = computed(() => store.getters['Products/getSelectedProduct']);

        const initializeCarousel = () => {

            const $carousel = $('#selectedProductCarousel');

            if ($carousel.hasClass('owl-loaded')) {
                $carousel.trigger('destroy.owl.carousel');
                $carousel.removeClass('owl-loaded owl-carousel owl-theme');
                $carousel.find('.owl-stage-outer').children().unwrap();
            }

            $carousel.owlCarousel({
                autoplay: true,
                loop: false,
                nav: true,
                items: 5,
                dots: false,
                navText: [
                    '<i class="fa fa-angle-left"></i>',
                    '<i class="fa fa-angle-right"></i>',
                ],
                responsiveClass: true,
                responsive: {
                    0: {
                        items: 1,
                    },
                    250: {
                        items: 2,
                    },
                    480: {
                        items: 3,
                    },
                    768: {
                        items: 4,
                    },
                }
            });
        };

        return {
            searchProduct,
            product,
            select_product,
            handleSearch,
            accept_product,
            getProductbySearch: computed(() => store.getters['Products/getProductSearch'](searchProduct.value)),
            selectedProduct,
            productModal,
            closeModal,
            getSelectedProduct,
            isFocused,
            handleBlur,
            handleFocus,
            inputRef,
            dropdownRef,
            sortedImages,
            handleImageError,
            noImageSrc,
            getImageSrc,
            initializeCarousel
        }
   }
}).use(store).mount('#app_vue');
