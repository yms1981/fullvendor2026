<?php
defined('BASEPATH') or exit('No direct script access allowed');
$invPanelTitle = trim((string) ($lang_inventory['inventory_add_panel_title'] ?? ''));
$invProductHeading = trim((string) ($lang_inventory['inventory_add_product_heading'] ?? ''));
?>
<style>
  .inventory-order-add-scope .inventory-order-add__panel {
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 1.25rem 1.5rem 1.5rem;
    background: #fff;
  }
  .inventory-order-add-scope .inventory-order-add__panel-title {
    font-size: 1rem;
    font-weight: 700;
    color: #374151;
    margin: 0 0 1rem 0;
  }
  .inventory-order-add-scope .inventory-order-add__filters label {
    font-weight: 700;
    color: #374151;
    font-size: 0.875rem;
  }
  .inventory-order-add-scope .inventory-order-add__dual-wrap {
    margin-top: 0.5rem;
    padding-top: 0.5rem;
  }
  .inventory-order-add-scope .inventory-order-add__dual-wrap .dual-listbox .dual-listbox__container {
    width: 100%;
    flex-wrap: nowrap;
    align-items: stretch;
    justify-content: space-between;
  }
  .inventory-order-add-scope .inventory-order-add__dual-wrap .dual-listbox .dual-listbox__available,
  .inventory-order-add-scope .inventory-order-add__dual-wrap .dual-listbox .dual-listbox__selected {
    flex: 1 1 0;
    width: auto !important;
    min-width: 0;
    height: 320px;
    background: #fff;
    border: 1px solid #d1d5db !important;
    border-radius: 4px;
  }
  .inventory-order-add-scope .inventory-order-add__dual-wrap .dual-listbox .dual-listbox__title {
    margin-top: 0;
    -webkit-margin-before: 0;
    font-size: 0.95rem;
    font-weight: 700;
    color: #374151;
    background: #f9fafb;
    border-color: #d1d5db !important;
  }
  .inventory-order-add-scope .inventory-order-add__dual-wrap .dual-listbox .dual-listbox__buttons {
    flex: 0 0 auto;
    width: auto;
    min-width: 9.5rem;
    max-width: 12rem;
    margin: 0 12px;
    align-self: center;
    display: flex;
    flex-direction: column;
    align-items: stretch;
    gap: 0.4rem;
  }
  .inventory-order-add-scope .inventory-order-add__dual-wrap .dual-listbox .dual-listbox__button,
  .inventory-order-add-scope .inventory-order-add__dual-wrap .dual-listbox .dual-listbox__button.btn,
  .inventory-order-add-scope .inventory-order-add__dual-wrap .dual-listbox .dual-listbox__button.btn-sm {
    box-sizing: border-box !important;
    width: 100% !important;
    min-width: 9.5rem !important;
    max-width: none !important;
    height: auto !important;
    min-height: 2.35rem !important;
    padding: 0.5rem 0.85rem !important;
    border-radius: 0.35rem !important;
    overflow: visible !important;
    white-space: nowrap !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 0.35rem !important;
    font-size: 0.875rem !important;
    font-weight: 600 !important;
    line-height: 1.2 !important;
    text-align: center;
  }
  .inventory-order-add-scope .inventory-order-add__dual-wrap .dual-listbox .dual-listbox__button .text {
    display: inline-flex !important;
    align-items: center;
    gap: 0.3rem;
    white-space: nowrap;
  }
  .inventory-order-add-scope .inventory-order-add__dual-wrap .dual-listbox .dual-listbox__button .text[hidden],
  .inventory-order-add-scope .inventory-order-add__dual-wrap .dual-listbox .dual-listbox__button .spinner-border,
  .inventory-order-add-scope .inventory-order-add__dual-wrap .dual-listbox .dual-listbox__button .spinner-border[hidden] {
    display: none !important;
  }
  .inventory-order-add-scope .inventory-order-add__dual-wrap .dual-listbox .dual-listbox__button.add-all,
  .inventory-order-add-scope .inventory-order-add__dual-wrap .dual-listbox .dual-listbox__button.add-one {
    background-color: #28a745 !important;
    border-color: #28a745 !important;
    color: #fff !important;
  }
  .inventory-order-add-scope .inventory-order-add__dual-wrap .dual-listbox .dual-listbox__button.add-all:hover,
  .inventory-order-add-scope .inventory-order-add__dual-wrap .dual-listbox .dual-listbox__button.add-one:hover {
    background-color: #218838 !important;
    border-color: #218838 !important;
  }
  .inventory-order-add-scope .inventory-order-add__dual-wrap .dual-listbox .dual-listbox__button.remove-all,
  .inventory-order-add-scope .inventory-order-add__dual-wrap .dual-listbox .dual-listbox__button.remove-one {
    background-color: #dc3545 !important;
    border-color: #dc3545 !important;
    color: #fff !important;
  }
  .inventory-order-add-scope .inventory-order-add__dual-wrap .dual-listbox .dual-listbox__button.remove-all:hover,
  .inventory-order-add-scope .inventory-order-add__dual-wrap .dual-listbox .dual-listbox__button.remove-one:hover {
    background-color: #c82333 !important;
    border-color: #c82333 !important;
  }
  .inventory-order-add-scope .inventory-order-add__products {
    margin-top: 1.75rem;
    padding-top: 1.25rem;
    border-top: 1px solid #e5e7eb;
  }
  .inventory-order-add-scope .inventory-order-add__product-title {
    font-size: 1rem;
    font-weight: 700;
    color: #374151;
    margin: 0 0 0.75rem 0;
  }
  .inventory-order-add-scope .inventory-order-add__product-row label {
    font-weight: 700;
    color: #374151;
    font-size: 0.875rem;
  }
  .inventory-order-add-scope .inventory-all-products-label {
    cursor: pointer;
    color: #007bff !important;
    font-weight: 700;
    margin: 0;
    padding-top: 1.75rem;
  }
  .inventory-order-add-scope .inventory-order-add__grid-wrap {
    margin-top: 1.25rem;
  }
  .inventory-order-add-scope .fv-inventory-product-thumb {
    width: 4em;
    height: 4em;
    margin: 0 auto;
    overflow: visible;
    position: relative;
  }
  .inventory-order-add-scope .fv-inventory-product-thumb__trigger {
    display: block;
    width: 100%;
    height: 100%;
    cursor: zoom-in;
    border-radius: 0.25rem;
    outline: none;
  }
  .inventory-order-add-scope .fv-inventory-product-thumb__img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: contain;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 0.25rem;
  }
</style>
<script>
(function () {
    window._inventoryPaginationPrev = <?= json_encode($pagination_prev_label ?? 'Previous', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
    window._inventoryPaginationNext = <?= json_encode($pagination_next_label ?? 'Next', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
})();
</script>
<div class="container-fluid page-body-wrapper inventory-order-add-scope">

    <div class="main-panel">
        <div class="container-fluid pt-3">

            <div class="row">
                <div class="col-md-6">
                    <div class="page-header">
                        <h3 class="page-title"><?= strtoupper($lang_inventory['add_neworder']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <div class="row">
                                <div class="col-12 px-3 px-md-4 py-3">
                                    <div class="inventory-order-add__panel">
                                        <h4 class="inventory-order-add__panel-title"><?= htmlspecialchars($invPanelTitle, ENT_QUOTES, 'UTF-8') ?></h4>
                                        <form id="form-listproducto" novalidate class="w-100"
                                            action="<?= company_url('inventory/order/create') ?>" method="post"
                                            enctype="multipart/form-data">
                                            <div class="row inventory-order-add__filters">
                                                <div id="myselect2" class="col-md-6 form-group mb-3">
                                                    <label for="warehouse_user"><?= $lang_order['warehouse_user'] ?></label>
                                                    <select id="warehouse_user" class="form-control js-example"
                                                        name="warehouse_user" data-width="100%">
                                                        <option value="">
                                                            <?= strtoupper($lang_clientes['todo']) ?></option>
                                                        <?php foreach ($warelist as $wr) { ?>
                                                        <option value="<?=$wr->user_id?>">
                                                            <?= strtoupper(trim($wr->first_name.' '.$wr->last_name)) ?>
                                                        </option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-6 form-group mb-3">
                                                    <label for="search_category"><?= $lang_prods['search_category'] ?></label>
                                                    <input id="search_category" class="form-control" type="search"
                                                        placeholder="..." autocomplete="off">
                                                </div>
                                            </div>

                                            <div class="inventory-order-add__dual-wrap">
                                                <select id="kt_orden_categories" name="categories[]"
                                                    class="dual-listbox" multiple="multiple">
                                                    <?php foreach ($category as $categ) { ?>
                                                    <option value="<?=$categ->cat_id?>">
                                                        <?= strtoupper($categ->category_name)?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>

                                            <div class="inventory-order-add__products">
                                                <h4 class="inventory-order-add__product-title"><?= htmlspecialchars($invProductHeading, ENT_QUOTES, 'UTF-8') ?></h4>
                                                <div class="row inventory-order-add__product-row align-items-end">
                                                    <div class="col-lg-6 col-md-12 form-group mb-3 mb-lg-0">
                                                        <label class="d-none d-lg-block" for="seller_inv_product_select"><?= $lang_order['buscar_por_skuandName'] ?></label>
                                                        <select id="seller_inv_product_select"
                                                            data-url="<?= company_url('inventory/order/addone') ?>"
                                                            name="aditional_product"
                                                            class="js-data-example-ajax w-100"
                                                            data-placeholder="<?= htmlspecialchars($lang_order['buscar_por_skuandName'], ENT_QUOTES, 'UTF-8') ?>"
                                                            data-width="100%">
                                                        </select>
                                                    </div>
                                                    <div class="col-lg-3 col-md-6 col-sm-12 d-flex align-items-center">
                                                        <label class="inventory-all-products-label mb-0 mt-lg-0 w-100" for="all_products">
                                                            <input id="all_products" type="checkbox" value="1"
                                                                name="show_information" class="mr-1">
                                                            <?= $lang_order['all_products']?>
                                                        </label>
                                                    </div>
                                                    <div class="col-lg-3 col-md-6 col-sm-12 form-group mb-0">
                                                        <label for="search-input" class="d-block"><?= $lang_order['buscar'] ?></label>
                                                        <input class="form-control" type="search" id="search-input"
                                                            placeholder="<?= htmlspecialchars($lang_order['buscar'], ENT_QUOTES, 'UTF-8') ?>"
                                                            autocomplete="off">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="inventory-order-add__grid-wrap">
                                                <div class="table-responsive">
                                                    <table class="table style-adicional-table">
                                                        <thead>
                                                            <tr>
                                                                <th style="width:8%;">
                                                                    <?= htmlspecialchars((string) ($lang_order['image'] ?? ''), ENT_QUOTES, 'UTF-8') ?></th>
                                                                <th style="width:38%;">
                                                                    <?= htmlspecialchars((string) ($lang_order['product_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></th>
                                                                <th style="width:14%;"><?= htmlspecialchars((string) ($lang_order['sku'] ?? 'SKU'), ENT_QUOTES, 'UTF-8') ?></th>
                                                                <th style="width:25%;"><?= htmlspecialchars((string) ($lang_order['categoria'] ?? ''), ENT_QUOTES, 'UTF-8') ?></th>
                                                                <th style="width:15%;text-align: center;">
                                                                    <?= htmlspecialchars((string) ($lang_order['action'] ?? ''), ENT_QUOTES, 'UTF-8') ?></th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="product-list">
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <div class="paginate">
                                                    <ul id="pagination" class="pagination pagination-sm"></ul>
                                                </div>
                                            </div>
                                            <div id="vacio" class="w-100">
                                                <p class="text-center pt-5 pb-5">
                                                    <?= $lang_order['sin_informacion'] ?></p>
                                            </div>
                                            <div hidden id="loader" class="w-100 mt-5 mb-5 text-center">
                                                <span style="height: 60px; width: 60px;"
                                                    class="spinner-border spinner-border-sm" role="status"
                                                    aria-hidden="true"></span>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $this->load->view('components/crud/form_action_fab', [
    'formId' => 'form-listproducto',
    'saveLabel' => (string) ($lang_order['agregar_add'] ?? ''),
    'cancelLabel' => (string) ($lang_order['cancel'] ?? ''),
    'cancelUrl' => company_url('inventory/order'),
    'saveButtonExtraClass' => 'btn-aceptar',
]); ?>

<script type="text/javascript">
var form = document.querySelector('#form-listproducto')
var inpursearch = document.querySelector('#search_category')
var dualListbox
const url_viewproduct = "<?= company_url('products/view/') ?>"
const url_createorder = "<?= company_url('inventory/order/create') ?>"
const url_deleteproduct = "<?= company_url('inventory/order/delete/') ?>"
const url_image = "<?= base_url('uploads/products/'.$company_info->company_id . '/') ?>"
const image_default = "<?= htmlspecialchars(function_exists('catalog_public_no_image_url') ? catalog_public_no_image_url() : base_url('uploads/noimg.png'), ENT_QUOTES, 'UTF-8') ?>"
const _cargando = "<?= $lang_order['cargando'] ?>"
const _aceptar = "<?= $lang_order['agregar_add'] ?>"
const btn_aceptar = $('.btn-aceptar')
const select2search = document.querySelector('.js-data-example-ajax')
const contain_butoncreate = document.querySelectorAll('.contain-boton-create')
const option_catalog = document.getElementById('catalog')
const option_grid = document.getElementById('grid')
const option_from = document.querySelectorAll('input[name="from"]')
const panel_grid = document.getElementById('grid-product')
const btn_createorder = document.querySelectorAll('.btn-aceptar')
const warehouse_user = document.getElementById("warehouse_user")
const checked_allProductos = document.getElementById("all_products")

var tbl
var table_simple = document.querySelector('#infoTable')
var cont_addoneproduct = 0
var previous_category = []
var products = [];
let new_filtrado = []

function inventoryNormalizeProduct(p) {
    var c = p.category_id
    if (!Array.isArray(c)) {
        c = (c === null || c === undefined || c === '') ? [] : String(c).split(',')
    }
    var img = String(p.image || '').trim()
    if (img === '' && p.image_uuid) {
        var uuid = String(p.image_uuid || '').trim()
        if (uuid !== '') {
            img = window.location.origin + '/api/v2/images/' + encodeURIComponent(uuid)
        }
    }
    if (img === '') {
        var principal = String(p.imagen_principal || p.image_name || '').trim()
        if (principal !== '' && !/^https?:\/\//i.test(principal) && principal.indexOf('/') === -1) {
            img = String(url_image).replace(/\/?$/, '/') + principal
        } else if (principal !== '') {
            img = principal
        } else {
            img = image_default
        }
    }
    return {
        pro_id: String(p.pro_id),
        sku: p.sku,
        name: p.name,
        category_id: c.map(function (x) { return String(x) }),
        categories_name: p.categories_name || '',
        categories: Array.isArray(p.categories) ? p.categories : [],
        image: img,
        image_preview: String(p.image_preview || img || '').trim() || img,
        imagen_principal: p.imagen_principal || p.image_name || '',
        image_uuid: p.image_uuid || '',
        aditional: p.aditional || 0
    }
}

function inventoryProductCategoryIds(p) {
    var c = p && p.category_id
    if (Array.isArray(c)) {
        return c.map(function (x) { return String(x).trim() }).filter(Boolean)
    }
    if (c === null || c === undefined || c === '') {
        return []
    }
    return String(c).split(',').map(function (x) { return x.trim() }).filter(Boolean)
}

function syncInventoryGridFromCategories() {
    if (typeof dualListbox === 'undefined' || !dualListbox) {
        return
    }
    var pool = window._inventoryAllProducts || []
    var selected = typeof get_ordercategory === 'function'
        ? get_ordercategory(dualListbox.selected).map(function (c) { return String(c.category_id) })
        : []
    function inSelected(cid) {
        return selected.indexOf(String(cid)) !== -1
    }
    function productMatchesSelected(p) {
        return inventoryProductCategoryIds(p).some(function (cid) { return inSelected(cid) })
    }
    if (selected.length === 0) {
        new_filtrado = []
        products = pool.map(inventoryNormalizeProduct)
    } else {
        var matched = pool.filter(productMatchesSelected).map(inventoryNormalizeProduct)
        var capped = typeof inventoryCapProductList === 'function'
            ? inventoryCapProductList(matched, true)
            : matched.slice(0, 500)
        new_filtrado = capped
        var kept = {}
        capped.forEach(function (p) { kept[String(p.pro_id)] = true })
        products = pool.filter(function (p) {
            return !productMatchesSelected(p) || !kept[String(p.pro_id)]
        }).map(inventoryNormalizeProduct)
    }
    state.setState({ filteredProducts: new_filtrado, currentPage: 1, searchTerm: state.getState().searchTerm || '' })
    if (typeof filterProducts === 'function') {
        filterProducts()
    } else if (typeof renderProductList === 'function') {
        renderProductList()
        if (typeof renderPagination === 'function') {
            renderPagination()
        }
    }
}

const createState = (initialState) => {
    let state = initialState;
    const listeners = [];

    function getState() {
        return state;
    }

    function setState(newState) {
        state = {
            ...state,
            ...newState
        };
        listeners.forEach(listener => listener(state));
    }

    function subscribe(listener) {
        listeners.push(listener);
        return function unsubscribe() {
            const index = listeners.indexOf(listener);
            if (index !== -1) {
                listeners.splice(index, 1);
            }
        };
    }

    return {
        getState,
        setState,
        subscribe
    };
}

const state = createState({
    products: [],
    itemsPerPage: 25,
    currentPage: 1,
    searchTerm: '',
    filteredProducts: []
});

;(function inventoryOrderAddWaitForLibs() {
    if (typeof jQuery === 'undefined' || typeof DualListbox === 'undefined') {
        if (!window.__invAddInitAttempts) window.__invAddInitAttempts = 0
        window.__invAddInitAttempts++
        if (window.__invAddInitAttempts > 120) {
            console.error('inventory order add: jQuery o DualListbox no cargaron a tiempo')
            return
        }
        setTimeout(inventoryOrderAddWaitForLibs, 50)
        return
    }
    jQuery(function () {
    ;(async function () {

    $(".js-example").select2({
        dropdownParent: $("#myselect2")
    });

    dualListbox = new DualListbox("#kt_orden_categories", {
        searchPlaceholder: "<?= htmlspecialchars($lang_prods['search_category'], ENT_QUOTES, 'UTF-8') ?>...",
        availableTitle: "<?= htmlspecialchars($lang_prods['category_list'], ENT_QUOTES, 'UTF-8') ?>",
        selectedTitle: "<?= htmlspecialchars($lang_prods['order_categories'], ENT_QUOTES, 'UTF-8') ?>",
        addButtonText: "<span class='text'><i class='fa-solid fa-plus' aria-hidden='true'></i> <?= htmlspecialchars($lang_prods['add_one'], ENT_QUOTES, 'UTF-8') ?></span>",
        removeButtonText: "<span class='text'><i class='fa-solid fa-trash' aria-hidden='true'></i> <?= htmlspecialchars($lang_prods['btn_remove'], ENT_QUOTES, 'UTF-8') ?></span>",
        addAllButtonText: "<span class='text'><i class='fa-solid fa-calendar-plus' aria-hidden='true'></i> <?= htmlspecialchars($lang_prods['btn_addall'], ENT_QUOTES, 'UTF-8') ?></span>",
        removeAllButtonText: "<span class='text'><i class='fa-solid fa-broom' aria-hidden='true'></i> <?= htmlspecialchars($lang_prods['btn_clean'], ENT_QUOTES, 'UTF-8') ?></span>",
        showSortButtons: true,
        draggable: true,
    });

    function onDualChange() {
        syncInventoryGridFromCategories()
    }
    dualListbox.addEventListener("added-all", onDualChange);
    dualListbox.addEventListener("added", onDualChange);
    dualListbox.addEventListener("removed", onDualChange);
    dualListbox.addEventListener("reorder", onDualChange);

    dualListbox.search.hidden = true
    const buttons_select = {
        button_all: document.querySelector('.inventory-order-add-scope button.add-all'),
        button_addOne: document.querySelector('.inventory-order-add-scope button.add-one'),
        button_removeAll: document.querySelector('.inventory-order-add-scope button.remove-all'),
        button_removeOne: document.querySelector('.inventory-order-add-scope button.remove-one'),
        check_allProducts: document.getElementById('all_products')
    }
    ;['button_all', 'button_addOne', 'button_removeAll', 'button_removeOne'].forEach(function (k) {
        var b = buttons_select[k]
        if (b) {
            b.classList.add('btn', 'fv-btn-with-label')
            if (k === 'button_all' || k === 'button_addOne') {
                b.classList.add('btn-success')
            }
            if (k === 'button_removeAll' || k === 'button_removeOne') {
                b.classList.add('btn-danger')
            }
        }
    })

    buttons_ofFilter(buttons_select, true)

    syncInventoryGridFromCategories()

    var phSku = $('.js-data-example-ajax').data('placeholder') || "<?= htmlspecialchars($lang_order['buscar_por_skuandName'], ENT_QUOTES, 'UTF-8') ?>"
    $(".js-data-example-ajax").select2({
        placeholder: phSku,
        dropdownParent: $('.inventory-order-add-scope'),
        ajax: {
            url: '<?= company_url('products/searchProductSelect2') ?>',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    search: params.term,
                    page: params.page
                }
            },
            processResults: function(data, params) {
                params.page = params.page || 1;
                return {
                    results: data.items,
                    pagination: {
                        more: (params.page * 5) < data.total_count
                    }
                };
            },
            cache: true
        },
        minimumInputLength: 1,
        templateResult: formatRepo,
        templateSelection: formatRepoSelection
    });

    var adSel = document.querySelector("select[name='aditional_product']")
    if (adSel && adSel.nextSibling) {
        adSel.nextSibling.style.width = '100%'
    }

    $('#infoTable').on('page.dt', function() {
        setTimeout(() => {
            if (typeof window.fvInitOrderImagePopovers === 'function') {
                window.fvInitOrderImagePopovers(document);
            } else if (typeof activar_popover_imagenes === 'function') {
                activar_popover_imagenes();
            }
        }, 200);
    });
    $(document).on("select2:select", ".js-data-example-ajax", addoneaditionalproduct);
    form.addEventListener('submit', send_form_tosave)

    btn_createorder.forEach(function(element) {
        element.addEventListener("click", create_inventoryorder)
    })
    inpursearch.addEventListener("keyup", copytextsearchdroplist)

    if (option_grid) {
        option_grid.click()
    }

    checked_allProductos.addEventListener("click", checked_allProducts)

    var loadUrl = (typeof options !== 'undefined' && options.url)
        ? (options.url + "/inventory/order/load_products")
        : "<?= rtrim(company_url(), '/') ?>/inventory/order/load_products"
    try {
        const response = await fetch(loadUrl, { credentials: 'same-origin' })
        const data_response = await response.json()
        window._inventoryAllProducts = (Array.isArray(data_response) ? data_response : []).map(inventoryNormalizeProduct)
    } catch (loadErr) {
        console.error('inventory order add: load_products failed', loadErr)
        window._inventoryAllProducts = []
    }
    products = window._inventoryAllProducts.map(function (p) { return inventoryNormalizeProduct(p) })
    new_filtrado = []
    syncInventoryGridFromCategories()
    if (typeof initInventoryStateProductsBindings === 'function') {
        initInventoryStateProductsBindings()
    }
    if (typeof initInventoryImagePopovers === 'function') {
        initInventoryImagePopovers(document.getElementById('product-list') || document)
    }

    buttons_ofFilter(buttons_select, true)
    })()
    })
})()
</script>

<script src="<?= htmlspecialchars(dated_file_url('companyasset/company/order/ImagePopover.js'), ENT_QUOTES, 'UTF-8') ?>"></script>

<?php $this->load->view('company/inventario/scripts-functions'); ?>
