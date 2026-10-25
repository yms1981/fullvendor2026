
// Función para filtrar productos según el término de búsqueda
function filterProducts() {
    const { searchTerm } = state.getState();
    const term = String(searchTerm || '').trim().toLowerCase();
    let filteredProducts = [];

    if (term === '') {
        filteredProducts = [...new_filtrado];
    } else {
        filteredProducts = new_filtrado.filter(product => {
            const values = [
                String(product.pro_id || ''),
                String(product.name || ''),
                String(product.sku || ''),
                String(product.categories_name || ''),
            ];
            return values.some(v => v.toLowerCase().includes(term));
        });
    }

    state.setState({ filteredProducts });
}

// Función para renderizar la lista de productos en la página actual
const productList = document.getElementById('product-list');
const emtyList = document.getElementById('vacio');
const loader = document.getElementById('loader');

function resolveProductImage(product) {
    const directImage = String(product.image || '').trim();
    if (directImage !== '') {
        return directImage;
    }

    const uuid = String(product.image_uuid || '').trim();
    if (uuid !== '') {
        return window.location.origin + '/api/v2/images/' + encodeURIComponent(uuid);
    }

    const imageName = String(product.imagen_principal || product.image_name || '').trim();
    if (imageName !== '' && typeof url_image !== 'undefined') {
        if (/^https?:\/\//i.test(imageName) || imageName.indexOf('/') !== -1) {
            return imageName;
        }
        return String(url_image).replace(/\/?$/, '/') + imageName;
    }

    return (typeof image_default !== 'undefined' && image_default) ? image_default : '';
}

function escapeInventoryHtml(str) {
    return String(str == null ? '' : str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function initInventoryImagePopovers(root) {
    function tryInit(attempt) {
        attempt = attempt || 0;
        if (typeof window.fvInitOrderImagePopovers === 'function') {
            var ok = window.fvInitOrderImagePopovers(root || document);
            if (ok === false && attempt < 80) {
                setTimeout(function () { tryInit(attempt + 1); }, 50);
            }
            return;
        }
        if (typeof window.jQuery !== 'undefined'
            && window.jQuery.fn
            && typeof window.jQuery.fn.popover === 'function') {
            // Fallback local si ImagePopover aún no cargó.
            var $els = window.jQuery(root || document).find('[data-toggle="popover-hover"]');
            if (!$els.length) {
                return;
            }
            $els.each(function () {
                var $t = window.jQuery(this);
                if ($t.data('bs.popover')) {
                    try { $t.popover('dispose'); } catch (e) { /* ignore */ }
                }
            });
            var tpl = '<div class="popover fv-popover-img-preview" role="tooltip"><div class="arrow"></div><div class="popover-body"></div></div>';
            $els.popover({
                html: true,
                trigger: 'hover focus',
                placement: 'right',
                container: 'body',
                template: tpl,
                title: '',
                sanitize: false,
                content: function () {
                    var $el = window.jQuery(this);
                    var img = String($el.attr('data-img') || '').trim();
                    if (!img || img.indexOf('data:') === 0) {
                        var $thumb = $el.find('img').first();
                        if ($thumb.length) {
                            var liveSrc = String($thumb.attr('src') || '').trim();
                            if (liveSrc && liveSrc.indexOf('data:') !== 0) {
                                img = liveSrc;
                            }
                        }
                    }
                    if (!img) {
                        return '';
                    }
                    return window.jQuery('<div class="fv-popover-img-preview__inner"/>').append(
                        window.jQuery('<img>', {
                            src: img,
                            alt: '',
                            class: 'fv-popover-img-preview__photo',
                            draggable: false
                        })
                    );
                }
            }).on('inserted.bs.popover', function () {
                var inst = window.jQuery(this).data('bs.popover');
                if (inst && typeof inst.getTipElement === 'function') {
                    var tip = inst.getTipElement();
                    if (tip) {
                        tip.classList.add('fv-popover-img-preview');
                    }
                }
            });
            return;
        }
        if (attempt < 80) {
            setTimeout(function () { tryInit(attempt + 1); }, 50);
        }
    }
    tryInit(0);
}

function renderProductCategoriesHtml(product) {
    var cats = Array.isArray(product.categories) ? product.categories : [];
    if (cats.length === 0) {
        var fallbackName = String(product.categories_name || '').trim();
        return fallbackName !== '' ? escapeInventoryHtml(fallbackName) : '—';
    }
    var html = '<div class="fv-category-avatars fv-category-avatars--inline">';
    cats.forEach(function (cat) {
        var name = String(cat.category_name || '').trim();
        if (!name) {
            return;
        }
        var thumb = String(cat.image || '').trim();
        var preview = String(cat.image_preview || cat.image || '').trim();
        var hasImage = !!cat.has_image && preview !== '';
        var cls = 'fv-category-avatar' + (hasImage ? ' fv-category-avatar--has-image' : '');
        html += '<span class="' + cls + '"'
            + (hasImage
                ? ' data-toggle="popover-hover" data-img="' + escapeInventoryHtml(preview) + '" aria-label="' + escapeInventoryHtml(name) + '"'
                : ' title="' + escapeInventoryHtml(name) + '"')
            + '>';
        if (thumb !== '') {
            html += '<img src="' + escapeInventoryHtml(thumb) + '" alt="' + escapeInventoryHtml(name) + '"'
                + ' class="fv-category-avatar__img' + (hasImage ? '' : ' fv-category-avatar__img--placeholder') + '"'
                + ' loading="lazy" decoding="async">';
        }
        html += '<span class="fv-category-avatar__name">' + escapeInventoryHtml(name) + '</span></span>';
    });
    html += '</div>';
    return html;
}

function renderProductList() {
    if (!productList || !emtyList || !loader) {
        return
    }
    const { filteredProducts, itemsPerPage, currentPage } = state.getState();
    const start = (currentPage - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    const currentPageProducts = filteredProducts.slice(start, end);

    productList.innerHTML = '';
    loader.hidden = false;
    
    const fragment = document.createDocumentFragment()
    if (currentPageProducts.length > 0) {
        currentPageProducts.forEach((product) => {
            const productImage = resolveProductImage(product);
            const productPreview = String(product.image_preview || productImage || '').trim() || productImage;
            const skuLabel = escapeInventoryHtml(String(product.sku || ''));
            
            const productTr = document.createElement('tr');
            productTr.innerHTML = `
                <td>
                    <figure class="fv-inventory-product-thumb">
                        <span class="fv-inventory-product-thumb__trigger"
                              data-toggle="popover-hover"
                              data-img="${escapeInventoryHtml(productPreview)}"
                              tabindex="0"
                              aria-label="${skuLabel}">
                            <img src="${escapeInventoryHtml(productImage)}"
                                 class="fv-inventory-product-thumb__img"
                                 alt="${skuLabel}"
                                 loading="lazy"
                                 decoding="async">
                        </span>
                    </figure>
                </td>
                <td>${escapeInventoryHtml(product.name)}</td>
                <td>${escapeInventoryHtml(String(product.sku || '').toUpperCase())}</td>
                <td>${renderProductCategoriesHtml(product)}</td>
                <td>
                    <a href="javascript:void(0)" onclick="delete_row('${escapeInventoryHtml(product.pro_id)}','${escapeInventoryHtml(product.name).replace(/'/g, '&#39;')}')" class="btn btn-danger btn-go-delete"><i class="mdi mdi-delete"></i></a>
                </td>
            `;
            fragment.appendChild(productTr)        
        });
        
        productList.appendChild(fragment);
        emtyList.hidden = true;
        initInventoryImagePopovers(productList);
    }else{
        emtyList.hidden = false;
    }
    loader.hidden = true;    
}

 // Función para renderizar los botones de paginación
function renderPagination() {
    
    const { filteredProducts, itemsPerPage,currentPage } = state.getState();
    const pagination = document.getElementById('pagination');
    
    if (filteredProducts.length > 0) {
        const totalPages = Math.ceil(filteredProducts.length / itemsPerPage);

        // Definir el rango de botones a mostrar (en este caso, 3)
        const buttonsToShow = 5;
        const halfButtonsToShow = Math.floor(buttonsToShow / 2);

        pagination.innerHTML = '';
        const labelPrev = (typeof window._inventoryPaginationPrev !== 'undefined' && window._inventoryPaginationPrev !== '')
            ? window._inventoryPaginationPrev
            : 'Previous';
        const labelNext = (typeof window._inventoryPaginationNext !== 'undefined' && window._inventoryPaginationNext !== '')
            ? window._inventoryPaginationNext
            : 'Next';
        const prevButton = document.createElement('li');
        prevButton.classList.add('page-item');
        prevButton.innerHTML= `<a class="page-link" href="javascript:void(0)">${labelPrev}</a>`
        prevButton.addEventListener('click', () => {
            if (currentPage > 1) {
                state.setState({ currentPage: currentPage - 1 });
                const newcurrentPage = state.getState().currentPage;
                const page_actual = document.getElementById('page-'+ newcurrentPage)        
                page_actual.classList.add('active');            
            }
        });
            
        pagination.appendChild(prevButton);

        // Calcula el rango de botones a mostrar
        let startPage = currentPage - halfButtonsToShow;
        let endPage = currentPage + halfButtonsToShow;

        if (startPage < 1) {
            endPage += Math.abs(startPage) + 1;
            startPage = 1;
        }

        if (endPage > totalPages) {
            startPage -= endPage - totalPages;
            endPage = totalPages;
        }

        if (startPage < 1) {
            startPage = 1;
        }

        for (let i = startPage; i <= endPage; i++) {
            
            const pageButton = document.createElement('li');        
            pageButton.setAttribute('id', 'page-' + i);
            
            pageButton.innerHTML = `<a class="page-link" href="javascript:void(0)">${i}</a>`
            pageButton.addEventListener('click', () => {
                state.setState({ currentPage: i });
            });
            pagination.appendChild(pageButton);
        }

        const nextButton = document.createElement('li');
        nextButton.classList.add('page-item');
        nextButton.innerHTML= `<a class="page-link" href="javascript:void(0)">${labelNext}</a>`
        nextButton.addEventListener('click', () => {
            if (currentPage < totalPages) {
                state.setState({ currentPage: currentPage + 1 });

                const newcurrentPage = state.getState().currentPage;
                const page_actual = document.getElementById('page-'+ newcurrentPage)        
                page_actual.classList.add('active');
                //pagination.querySelectorAll('li')[1].classList.remove('active');
            }
        });
        
        const newcurrentPage = state.getState().currentPage;    
        pagination.appendChild(nextButton);
        if (newcurrentPage == 1 && filteredProducts.length > 0) {
            pagination.querySelectorAll('li')[1].classList.add('active');
        }
        initInventoryImagePopovers(productList || document);
    }else{
        pagination.innerHTML = '';
    }
}

var INVENTORY_MAX_SELECTED_PRODUCTS = 500

function inventoryProductsMaxMessage() {
    var invLang = (typeof options !== 'undefined' && options.lang && options.lang.cmp_inventory && options.lang.cmp_inventory.lang)
        ? options.lang.cmp_inventory.lang
        : {}
    return String(invLang.inventory_create_products_max || '').trim()
}

function inventoryCapProductList(list, notify) {
    if (!Array.isArray(list)) {
        return []
    }
    var seen = {}
    var unique = []
    list.forEach(function (item) {
        if (!item || item.pro_id == null) {
            return
        }
        var id = String(item.pro_id).trim()
        if (id === '' || seen[id]) {
            return
        }
        seen[id] = true
        unique.push(item)
    })
    if (unique.length <= INVENTORY_MAX_SELECTED_PRODUCTS) {
        return unique
    }
    if (notify) {
        var msg = inventoryProductsMaxMessage()
        if (msg !== '' && typeof window.alert_func === 'function') {
            window.alert_func([msg, 'warning'])
        }
    }
    return unique.slice(0, INVENTORY_MAX_SELECTED_PRODUCTS)
}

function initInventoryStateProductsBindings() {
    if (typeof state === 'undefined' || typeof state.subscribe !== 'function') {
        setTimeout(initInventoryStateProductsBindings, 50);
        return;
    }
    if (!window.__inventoryStateProductsInitialized) {
        window.__inventoryStateProductsInitialized = true;

        state.subscribe(renderProductList);
        state.subscribe(renderPagination);

        const searchInput = document.getElementById('search-input');
        if (searchInput && !searchInput.dataset.inventorySearchBound) {
            searchInput.dataset.inventorySearchBound = '1';
            searchInput.addEventListener('input', () => {
                const searchTerm = searchInput.value.trim().toLowerCase();
                state.setState({ searchTerm, currentPage: 1 });
                filterProducts();
            });
        }
    }

    if (typeof filterProducts === 'function') {
        filterProducts();
    }
}

initInventoryStateProductsBindings();


const filter_products = (listelement,filter_byone = true) =>{
  
  const categories_tofilter = get_ordercategory(listelement)
  console.log(categories_tofilter);
  categories_tofilter.forEach(category => {
    
    const productosFiltrados = products.filter(producto => producto.category_id.includes(category.category_id));
    console.log('productosFiltrados' , productosFiltrados);
    new_filtrado =  [...new_filtrado,...productosFiltrados]
    new_filtrado = inventoryCapProductList(new_filtrado, true)
    const productosNoFiltrados = products.filter(producto => !producto.category_id.includes(category.category_id));
    products = productosNoFiltrados
  });
}

const remove_products = (listelement) => {
    
    let filtrado_actual = []
    let filtrado_sobrante = []
    const categories_tofilter = get_ordercategory(listelement)
    
    if (categories_tofilter.length > 0) {
        categories_tofilter.forEach(category => {
            const productosFiltrados = new_filtrado.filter(producto => producto.category_id.includes(category.category_id));        
            filtrado_actual = [...filtrado_actual,...productosFiltrados]
            const productosNoFiltrados = new_filtrado.filter(producto => !producto.category_id.includes(category.category_id));
            filtrado_sobrante =[...filtrado_sobrante,...productosNoFiltrados]

            // new_filtrado = productosNoFiltrados            
        })
        
    }else{
        filtrado_sobrante = [...new_filtrado]        
    }

    console.log("filtrado_sobrante" , filtrado_sobrante)
    new_filtrado = filtrado_sobrante
    products = [...products,...filtrado_sobrante]
}

const buttons_ofFilter = (buttons_select,condition) => {
    var keys = ['button_all', 'button_addOne', 'button_removeAll', 'button_removeOne']
    if (condition) {
        keys.forEach(function (k) {
            var b = buttons_select[k]
            if (!b) return
            b.disabled = false
            var sp = b.querySelector('.spinner-border')
            var tx = b.querySelector('.text')
            if (sp) {
                sp.hidden = true
                sp.setAttribute('hidden', 'hidden')
            }
            if (tx) {
                tx.hidden = false
                tx.removeAttribute('hidden')
            }
        })
        if (buttons_select.check_allProducts) {
            buttons_select.check_allProducts.disabled = false
        }
        return
    }

    keys.forEach(function (k) {
        var b = buttons_select[k]
        if (!b) return
        b.disabled = true
        var sp = b.querySelector('.spinner-border')
        var tx = b.querySelector('.text')
        // Mantener el texto visible; el spinner solo se usa durante carga si hace falta.
        if (sp) {
            sp.hidden = true
            sp.setAttribute('hidden', 'hidden')
        }
        if (tx) {
            tx.hidden = false
            tx.removeAttribute('hidden')
        }
    })
    if (buttons_select.check_allProducts) {
        buttons_select.check_allProducts.disabled = true
    }
}

function collectInventoryProductIdsForSubmit() {
    var ids = []
    if (typeof new_filtrado !== 'undefined' && new_filtrado && new_filtrado.length) {
        new_filtrado.forEach(function(item) {
            if (item && item.pro_id != null && String(item.pro_id).trim() !== '') {
                ids.push(String(item.pro_id).trim())
            }
        })
        return ids
    }
    var tbody = document.getElementById('product-list')
    if (!tbody) {
        return ids
    }
    tbody.querySelectorAll('tr').forEach(function(tr) {
        var first = tr.querySelector('td')
        if (!first) {
            return
        }
        var id = String(first.textContent || '').trim()
        if (id !== '') {
            ids.push(id)
        }
    })
    return ids
}

function resolveWarehouseUserIdForSubmit() {
    if (typeof warehouse_user === 'undefined' || !warehouse_user) {
        return ''
    }
    if (typeof jQuery !== 'undefined' && jQuery.fn && typeof jQuery(warehouse_user).val === 'function') {
        var jqVal = jQuery(warehouse_user).val()
        if (jqVal != null && jqVal !== '') {
            return String(jqVal)
        }
    }
    return String(warehouse_user.value || '')
}

const send_form_tosave = async (e) => {
    e.preventDefault()

    const formEl = e.currentTarget || e.target
    if (!formEl || typeof formEl.action !== 'string') {
        return
    }

    var ordersLang = (typeof options !== 'undefined' && options.lang && options.lang.cmp_orders && options.lang.cmp_orders.lang)
        ? options.lang.cmp_orders.lang
        : {}
    var invLang = (typeof options !== 'undefined' && options.lang && options.lang.cmp_inventory && options.lang.cmp_inventory.lang)
        ? options.lang.cmp_inventory.lang
        : {}
    var docsLang = (typeof options !== 'undefined' && options.lang && options.lang.cmp_documents && options.lang.cmp_documents.lang)
        ? options.lang.cmp_documents.lang
        : {}

    var btnAlertOk = String(docsLang.ok || ordersLang.swal_confirm || ordersLang.confirmar || ordersLang.cerrar || '').trim()
    function inventoryFormShowError(message) {
        var text = String(message || '').trim() || String(ordersLang.invalid_request || '').trim()
        if (typeof window.alert_func === 'function') {
            window.alert_func([text, 'error'])
            return
        }
        if (typeof swal === 'function' && text !== '') {
            swal({
                title: text,
                text: '',
                icon: 'error',
                button: {
                    text: btnAlertOk,
                    value: true,
                    visible: true,
                    className: 'btn btn-primary'
                }
            })
        }
    }

    var selectedList = (typeof dualListbox !== 'undefined' && dualListbox && Array.isArray(dualListbox.selected))
        ? dualListbox.selected
        : []
    order_category = get_ordercategory(selectedList)
    const datos = new FormData(formEl)

    order_category.forEach(function(element, key, parent) {
        datos.append('order_categories[' + key + ']', key)
        datos.append('order_categories[' + key + '][order]', element.order)
        datos.append('order_categories[' + key + '][id]', element.category_id)
    })

    var productIds = collectInventoryProductIdsForSubmit()
    var warehouseId = resolveWarehouseUserIdForSubmit()

    if (!warehouseId) {
        inventoryFormShowError(invLang.inventory_create_warehouse_required || ordersLang.invalid_request || '')
        return
    }
    if (!productIds.length) {
        inventoryFormShowError(invLang.inventory_create_products_required || ordersLang.details_required || ordersLang.invalid_request || '')
        return
    }
    var seenProduct = {}
    productIds = productIds.filter(function (pid) {
        if (seenProduct[pid]) {
            return false
        }
        seenProduct[pid] = true
        return true
    })
    if (productIds.length > 500) {
        inventoryFormShowError(invLang.inventory_create_products_max || ordersLang.invalid_request || '')
        return
    }

    productIds.forEach(function(pid, index) {
        datos.append('product_list[' + index + ']', pid)
    })
    datos.set('warehouse_user', warehouseId)

    button_changestatus(true)
    try {
        const response = await fetch(formEl.action, {
            method: 'POST',
            body: datos,
            credentials: 'same-origin'
        })
        const raw = await response.text()
        var data
        try {
            data = JSON.parse(raw)
        } catch (parseErr) {
            inventoryFormShowError(ordersLang.invalid_request || '')
            return
        }

        if (!data.success) {
            inventoryFormShowError(data.msg || ordersLang.invalid_request || '')
            return
        }
        if (data.urlredirect) {
            var successText = (data.msg && String(data.msg).trim() !== '')
                ? String(data.msg).trim()
                : String(invLang.inventoryorder_saved || '').trim()
            var btnOk = String(docsLang.ok || ordersLang.swal_confirm || ordersLang.confirmar || ordersLang.cerrar || '').trim()
            if (typeof swal === 'function') {
                var done = function() {
                    window.location.href = data.urlredirect
                }
                var p = swal({
                    title: successText,
                    text: '',
                    icon: 'success',
                    button: {
                        text: btnOk,
                        value: true,
                        visible: true,
                        className: 'btn btn-primary'
                    }
                })
                if (p && typeof p.then === 'function') {
                    p.then(done)
                } else {
                    done()
                }
            } else {
                window.location.href = data.urlredirect
            }
        }
    } catch (err) {
        inventoryFormShowError(ordersLang.invalid_request || '')
    } finally {
        button_changestatus(false)
    }
}



const create_inventoryorder = (e) => {

  
}
