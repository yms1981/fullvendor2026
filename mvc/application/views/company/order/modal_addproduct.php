<?php
/**
 * Modal agregar producto a orden (crear / editar).
 *
 * @var array $pageTexts
 * @var string $moneda
 * @var object $format_numeric
 */
$pageTexts = is_array($pageTexts ?? null) ? $pageTexts : [];
$moneda = ($moneda ?? '') !== '' ? $moneda : '$';
$fmt = $format_numeric ?? null;
if (!$fmt || !isset($fmt->digits)) {
    $fmt = (object) ['digits' => 2, 'radixPoint' => ',', 'groupSeparator' => '.'];
}
$fmtGroup = function_exists('fv_resolve_numeric_group_separator')
    ? fv_resolve_numeric_group_separator($fmt)
    : (string) (($fmt->groupSeparator ?? '') !== '' ? $fmt->groupSeparator : (($fmt->radixPoint ?? '.') === ',' ? '.' : ','));
?>
<link rel="stylesheet" href="<?= dated_file_url('companyasset/css/fv-order-form.css') ?>" />
<div class="modal fade fv-order-add-product-modal" id="add-new" tabindex="-1" role="dialog"
     data-backdrop="static" data-keyboard="false"
     aria-labelledby="fvOrderAddProductTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="fvOrderAddProductTitle"><?= htmlspecialchars((string) ($pageTexts['nuevo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="<?= htmlspecialchars((string) ($pageTexts['cancelar_add'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="fv-order-product-search">
                    <label for="input-search-product" class="fv-order-product-search__label"><?= htmlspecialchars((string) ($pageTexts['product'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>
                    <div class="fv-order-product-search__wrap dropdown">
                        <i class="fa-solid fa-magnifying-glass fv-order-product-search__icon" aria-hidden="true"></i>
                        <input id="input-search-product"
                               autocomplete="off"
                               class="form-control fv-order-product-search__input"
                               type="search"
                               aria-expanded="false"
                               placeholder="<?= htmlspecialchars((string) ($pageTexts['buscar_por_skuandName'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <div id="product-results" class="dropdown-menu fv-order-product-results" role="listbox"></div>
                    </div>
                </div>

                <div class="fv-order-product-preview row">
                    <div class="col-lg-4 mb-3 mb-lg-0">
                        <div class="fv-order-product-preview__card">
                            <span class="fv-order-product-preview__badge"><?= htmlspecialchars((string) ($pageTexts['foto'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                            <div id="imagen_add" class="fv-order-product-preview__body">
                                <div class="fv-order-product-preview__empty">
                                    <i class="mdi mdi-image-outline" aria-hidden="true"></i>
                                    <span><?= htmlspecialchars((string) ($pageTexts['order_add_product_empty_image'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-8">
                        <div class="fv-order-product-preview__card">
                            <span class="fv-order-product-preview__badge"><?= htmlspecialchars((string) ($pageTexts['datos_producto'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                            <div id="datos_productadd" class="fv-order-product-preview__body">
                                <div class="fv-order-product-preview__empty">
                                    <i class="mdi mdi-package-variant-closed" aria-hidden="true"></i>
                                    <span><?= htmlspecialchars((string) ($pageTexts['order_add_product_empty_data'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light fv-crud-btn" data-dismiss="modal">
                    <?= htmlspecialchars((string) ($pageTexts['cancelar_add'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </button>
                <button id="btn-add" type="button" class="btn btn-success fv-crud-btn" style="display:none;">
                    <i class="fa-solid fa-cart-plus" aria-hidden="true"></i>
                    <?= htmlspecialchars((string) ($pageTexts['add_product'] ?? $pageTexts['agregar_add'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
window.fvOrderAddProductLang = <?= json_encode([
    'loading' => $pageTexts['cargando'] ?? '',
    'empty' => $pageTexts['sin_resultados'] ?? '',
    'searchError' => $pageTexts['order_add_product_search_error'] ?? '',
    'selectError' => $pageTexts['error_seleccionar_producto'] ?? '',
    'customerRequired' => $pageTexts['debe_seleccionar_cliente'] ?? ($pageTexts['debe_elegir_cliente'] ?? ''),
    'loadError' => $pageTexts['order_add_product_load_error'] ?? '',
    'qtyError' => $pageTexts['debes_ingresar_cantidad_valida'] ?? ($pageTexts['error_cantidadminima'] ?? ''),
    'stockInsufficient' => $pageTexts['stock_insuficiente'] ?? ($pageTexts['mensaje_stock_insuficiente'] ?? ''),
    'duplicate' => $pageTexts['producto_duplicado'] ?? '',
    'deleteTitle' => $pageTexts['tol-delete'] ?? '',
    'emptyImageHtml' => '<div class="fv-order-product-preview__empty"><i class="mdi mdi-image-outline" aria-hidden="true"></i><span>' . htmlspecialchars((string) ($pageTexts['order_add_product_empty_image'] ?? ''), ENT_QUOTES, 'UTF-8') . '</span></div>',
    'emptyDataHtml' => '<div class="fv-order-product-preview__empty"><i class="mdi mdi-package-variant-closed" aria-hidden="true"></i><span>' . htmlspecialchars((string) ($pageTexts['order_add_product_empty_data'] ?? ''), ENT_QUOTES, 'UTF-8') . '</span></div>',
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
window.fvOrderProductCache = window.fvOrderProductCache || {};
const PRODUCT_IMAGE_FALLBACK = <?= json_encode(company_product_image_url(null, 90)) ?>;
const PRODUCT_IMAGE_API_BASE = <?= json_encode(rtrim(base_url(), '/') . '/api/v2/images/') ?>;
const FORMAT_NUMERIC = <?= json_encode([
    'digits' => (int) $fmt->digits,
    'radixPoint' => $fmt->radixPoint ?? ',',
    'groupSeparator' => $fmtGroup,
], JSON_UNESCAPED_UNICODE) ?>;
const monedaSymbol = <?= json_encode($moneda) ?>;

function fvAddProductLang(key, fallback) {
    var L = window.fvOrderAddProductLang || {};
    return (L[key] && String(L[key]).trim() !== '') ? L[key] : (fallback || '');
}

function resolveOrderProductImgSrc(product) {
    if (!product) return PRODUCT_IMAGE_FALLBACK;
    var direct = product.images || product.imageurl;
    if (typeof direct === 'string' && direct.length > 0) return direct;
    var uuid = product.product_image || product.Img || '';
    if (!uuid && product.default_image) {
        if (typeof product.default_image === 'object') {
            uuid = product.default_image.uuid || product.default_image.image_id || '';
        } else if (typeof product.default_image === 'string') {
            uuid = product.default_image;
        }
    }
    if (!uuid && Array.isArray(product.images) && product.images.length > 0) {
        var first = product.images[0];
        if (first && typeof first === 'object') uuid = first.uuid || first.image_id || '';
    }
    if (typeof uuid === 'string' && /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i.test(uuid)) {
        return PRODUCT_IMAGE_API_BASE + encodeURIComponent(uuid);
    }
    return PRODUCT_IMAGE_FALLBACK;
}

function orderEditSwal(icon, title, text) {
    if (typeof Swal !== 'undefined' && Swal.fire) {
        Swal.fire({ icon: icon || 'info', title: title || '', text: text || '' });
    } else if (typeof swal === 'function') {
        swal(title, text || '', icon || 'info');
    } else {
        alert(title + (text ? '\n' + text : ''));
    }
}

function fvResetAddProductModal() {
    $('#input-search-product').val('');
    $('#btn-add').hide();
    window.fvOrderProductCache = {};
    window.fvOrderAddProductState = { selectedProId: null, loadingProduct: false };
    if (window.fvOrderAddProductSearchXhr) {
        window.fvOrderAddProductSearchXhr.abort();
        window.fvOrderAddProductSearchXhr = null;
    }
    if (window.fvOrderAddProductDetailXhr) {
        window.fvOrderAddProductDetailXhr.abort();
        window.fvOrderAddProductDetailXhr = null;
    }
    $('#imagen_add').html(fvAddProductLang('emptyImageHtml', ''));
    $('#datos_productadd').html(fvAddProductLang('emptyDataHtml', ''));
    var $dropdown = $('#product-results');
    $dropdown.removeClass('show').empty();
    $('#input-search-product').closest('.dropdown').removeClass('show');
}

function fvCloseProductDropdown() {
    $('#product-results').removeClass('show');
    $('#input-search-product').closest('.dropdown').removeClass('show');
}

$(document).ready(function () {
    var $input = $('#input-search-product');
    var $dropdown = $('#product-results');
    var searchTimeout = null;
    window.fvOrderAddProductState = window.fvOrderAddProductState || { selectedProId: null, loadingProduct: false };

    function canSearchProducts() {
        var st = window.fvOrderAddProductState || {};
        return !st.selectedProId && !st.loadingProduct;
    }

    function doSearch(terms) {
        if (!canSearchProducts()) {
            return;
        }
        terms = (terms || '').trim();
        clearTimeout(searchTimeout);
        if (window.fvOrderAddProductSearchXhr) {
            window.fvOrderAddProductSearchXhr.abort();
            window.fvOrderAddProductSearchXhr = null;
        }
        window.fvOrderAddProductSearchXhr = $.ajax({
            url: '<?= company_url('products/searchProduct') ?>',
            type: 'GET',
            dataType: 'json',
            data: { terms: terms },
            timeout: 20000,
            beforeSend: function () {
                if (!canSearchProducts()) {
                    return false;
                }
                $dropdown.html('<div class="fv-order-product-results__status"><i class="fa-solid fa-spinner fa-spin"></i><span>' + fvAddProductLang('loading', '') + '</span></div>').addClass('show');
                $input.closest('.dropdown').addClass('show');
            },
            success: function (products) {
                if (!canSearchProducts()) {
                    return;
                }
                if (!Array.isArray(products)) {
                    products = products && products.data ? products.data : [];
                }
                window.fvOrderProductCache = {};
                if (!products.length) {
                    $dropdown.html('<div class="fv-order-product-results__status"><span>' + fvAddProductLang('empty', '') + '</span></div>');
                    return;
                }
                var html = products.map(function (product) {
                    var proId = product.pro_id || product.product_id || 0;
                    window.fvOrderProductCache[proId] = product;
                    var imgSrc = resolveOrderProductImgSrc(product);
                    var label = ((product.sku || '') + ' | ' + (product.name || '')).toUpperCase();
                    var rating = parseFloat(product.rating != null ? product.rating : 3.5);
                    if (isNaN(rating)) { rating = 3.5; }
                    var ratingLabel = rating.toFixed(1);
                    return '<button type="button" class="dropdown-item fv-order-product-results__item product-item" data-pro-id="' + proId + '">' +
                        '<figure class="figure-fullvendor-product-search"><img class="img-full-vendor-product-search" src="' + imgSrc + '" data-fallback="' + PRODUCT_IMAGE_FALLBACK + '" onerror="this.onerror=null;this.src=this.getAttribute(\'data-fallback\');" alt=""></figure>' +
                        '<span class="fv-order-product-results__label">' + $('<div>').text(label).html() +
                        ' <span class="fv-order-product-results__rating" title="' + ratingLabel + '">★ ' + ratingLabel + '</span></span>' +
                        '</button>';
                }).join('');
                $dropdown.html(html).addClass('show');
                $input.closest('.dropdown').addClass('show');
            },
            error: function (_xhr, status) {
                if (status === 'abort' || !canSearchProducts()) {
                    return;
                }
                $dropdown.html('<div class="fv-order-product-results__status fv-order-product-results__status--error"><span>' + fvAddProductLang('searchError', '') + '</span></div>');
            },
            complete: function () {
                window.fvOrderAddProductSearchXhr = null;
            }
        });
    }

    $input.on('input', function () {
        window.fvOrderAddProductState.selectedProId = null;
        window.fvOrderAddProductState.loadingProduct = false;
        $('#btn-add').hide();
        $('#imagen_add').html(fvAddProductLang('emptyImageHtml', ''));
        $('#datos_productadd').html(fvAddProductLang('emptyDataHtml', ''));
        clearTimeout(searchTimeout);
        var terms = $(this).val().trim();
        searchTimeout = setTimeout(function () { doSearch(terms); }, 280);
    });

    $input.on('focus', function () {
        if (!canSearchProducts()) {
            fvCloseProductDropdown();
            return;
        }
        if ($dropdown.find('.product-item').length === 0) {
            doSearch($(this).val().trim());
        } else {
            $dropdown.addClass('show');
            $input.closest('.dropdown').addClass('show');
        }
    });

    $('#add-new').on('show.bs.modal', fvResetAddProductModal);

    $('#add-new').on('click', function (e) {
        if (!$(e.target).closest('.fv-order-product-search__wrap').length) {
            fvCloseProductDropdown();
        }
    });

    $(document).on('mousedown', '.fv-order-add-product-modal .product-item', function (e) {
        e.preventDefault();
        e.stopPropagation();
    });

    $(document).on('click', '.fv-order-add-product-modal .product-item', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var proId = $(this).data('pro-id');
        var product = window.fvOrderProductCache[proId];
        var customer_id = $('#selectCustomer').val();
        var label = product ? ((product.sku || '') + ' | ' + (product.name || '')).toUpperCase() : '';

        window.fvOrderAddProductState.selectedProId = proId;
        window.fvOrderAddProductState.loadingProduct = true;
        if (window.fvOrderAddProductSearchXhr) {
            window.fvOrderAddProductSearchXhr.abort();
            window.fvOrderAddProductSearchXhr = null;
        }
        fvCloseProductDropdown();
        if (label) {
            $input.val(label);
        }

        $('#imagen_add, #datos_productadd').html('<div class="fv-order-product-preview__loading"><i class="fa-solid fa-spinner fa-spin"></i></div>');

        if (!product || !proId) {
            window.fvOrderAddProductState.loadingProduct = false;
            window.fvOrderAddProductState.selectedProId = null;
            orderEditSwal('error', fvAddProductLang('selectError', ''));
            fvResetAddProductModal();
            return;
        }
        if (!customer_id) {
            window.fvOrderAddProductState.loadingProduct = false;
            window.fvOrderAddProductState.selectedProId = null;
            orderEditSwal('error', fvAddProductLang('customerRequired', ''));
            fvResetAddProductModal();
            return;
        }

        if (window.fvOrderAddProductDetailXhr) {
            window.fvOrderAddProductDetailXhr.abort();
        }
        window.fvOrderAddProductDetailXhr = $.ajax({
            url: '<?= company_url('orders/productDetails') ?>',
            type: 'GET',
            data: { pro_id: proId, customer_id: customer_id },
            dataType: 'json',
            success: function (res) {
                window.fvOrderAddProductDetailXhr = null;
                window.fvOrderAddProductState.loadingProduct = false;
                if (!res || res.error) {
                    window.fvOrderAddProductState.selectedProId = null;
                    orderEditSwal('error', fvAddProductLang('loadError', ''));
                    fvResetAddProductModal();
                    return;
                }
                $('#imagen_add').html(res.html_imagen);
                $('#datos_productadd').html(res.html_datos);
                $('#btn-add').show();
                fvUpdateAddProductTotal();
                setTimeout(function () { $('#qty_add').trigger('focus'); }, 100);
            },
            error: function (_xhr, status) {
                window.fvOrderAddProductDetailXhr = null;
                window.fvOrderAddProductState.loadingProduct = false;
                if (status === 'abort') {
                    return;
                }
                window.fvOrderAddProductState.selectedProId = null;
                orderEditSwal('error', fvAddProductLang('loadError', ''));
                fvResetAddProductModal();
            }
        });
    });

    $(document).on('click', '#btn-add', function (e) {
        e.preventDefault();
        var valorInputPrice = $('#sale_price_add').val() || '0';
        var pro_id = $('input[name="id_producto"]').val();
        var name = ($('textarea[name="name_product"]').val() || '').trim();
        var sku = $('input[name="sku"]').val() || '';
        var cachedProduct = (window.fvOrderProductCache && window.fvOrderProductCache[pro_id]) ? window.fvOrderProductCache[pro_id] : null;
        var unitLabel = '';
        if (cachedProduct && cachedProduct.unit) {
            unitLabel = (typeof cachedProduct.unit === 'string')
                ? cachedProduct.unit
                : (cachedProduct.unit.unit || cachedProduct.unit.description || '');
        }
        unitLabel = $('<div>').text(unitLabel || '—').html();
        var qty = parseFloat($('#qty_add').val()) || 0;
        var price = parseNumeroLocal(valorInputPrice);
        // Recalcular siempre desde qty × price (no re-parsear el total formateado del modal).
        var total = qty * price;
        var comentarios = $('textarea[name="comentarios"]').val() || '';
        var imageSrc = $('#imagen_add img.active').attr('src') || $('#imagen_add img').first().attr('src') || PRODUCT_IMAGE_FALLBACK;
        var moneda = monedaSymbol;
        var stock = parseFloat($('input[name="stock"]').val()) || 0;

        if (!qty || qty <= 0) {
            orderEditSwal('error', fvAddProductLang('qtyError', ''));
            return;
        }
        if (stock && qty > stock) {
            orderEditSwal('warning', fvAddProductLang('stockInsufficient', '') + ' (' + qty + ' / ' + stock + ')');
            return;
        }
        if ($(`.table tbody tr.item[data-product-id="${pro_id}"]`).length > 0) {
            orderEditSwal('info', fvAddProductLang('duplicate', ''));
            return;
        }

        var i = $('.table tbody tr.item').length + 1;
        var deleteTitle = fvAddProductLang('deleteTitle', '');
        var newRow = `
            <tr class="notranslate item" data-product-id="${pro_id}">
                <td class="align-middle">
                    <span class="row-index">${i}</span>
                    <input type="hidden" class="input-id" name="detalle[${i}][id]" value="">
                    <input type="hidden" name="detalle[${i}][row]" value="${i}">
                    <input type="hidden" class="input-product-id" name="detalle[${i}][id_producto]" value="${pro_id}">
                </td>
                <td class="proimg text-center align-middle">
                    <figure class="fv-product-list-image">
                        <div class="fv-product-sku-avatar">
                            <span class="fv-product-sku-avatar__media">
                                <img src="${imageSrc}" class="fv-product-sku-avatar__img rounded-0" alt="">
                            </span>
                        </div>
                    </figure>
                </td>
                <td class="align-middle">${name}</td>
                <td class="align-middle">${sku}</td>
                <td class="align-middle text-center notranslate">${unitLabel}</td>
                <td class="align-middle text-center">
                    <input type="number" class="form-control text-center input-qty order-edit-qty" name="detalle[${i}][qty]" min="0" value="${qty}" style="min-width:9rem;">
                </td>
                <td class="align-middle">
                    <div class="d-flex flex-nowrap align-items-center">
                        <div class="input-group input-group-sm" style="min-width:12rem;">
                            <div class="input-group-prepend"><span class="input-group-text">${moneda}</span></div>
                            <input class="form-control text-right input-price order-edit-price" name="detalle[${i}][precio]" type="text" value="${formatoNumeroLocal(price)}">
                        </div>
                        <input type="hidden" class="check-fixed-price" name="detalle[${i}][precio_fijo]" value="0">
                    </div>
                </td>
                <td class="align-middle text-center">
                    <strong>${moneda}<span class="product-total">${formatoNumeroLocal(total)}</span></strong>
                </td>
                <td class="align-middle">
                    <textarea rows="2" class="form-control input-comment order-edit-comment" name="detalle[${i}][comentarios]" style="min-height:4rem;resize:vertical;">${comentarios}</textarea>
                </td>
                <td class="align-middle text-center">
                    <button type="button" class="btn btn-danger btn-sm btn-remove-product" title="${deleteTitle}" data-detail-id="" data-row-index="${i}">
                        <i class="fa fa-trash" aria-hidden="true"></i>
                    </button>
                </td>
            </tr>`;

        $('.table tbody tr').eq(-3).before(newRow);
        calcularTotales();
        $('#add-new').modal('hide');
    });

    function parseNumeroLocal(numStr) {
        if (typeof window.fvParseLocaleNumber === 'function') {
            return window.fvParseLocaleNumber(numStr, FORMAT_NUMERIC);
        }
        if (!numStr) return 0;
        numStr = numStr.toString().replace(/[^\d.,-]/g, '');
        var dec = FORMAT_NUMERIC.radixPoint || ',';
        var sep = (typeof window.fvResolveNumericGroupSeparator === 'function')
            ? window.fvResolveNumericGroupSeparator(FORMAT_NUMERIC)
            : (FORMAT_NUMERIC.groupSeparator || (dec === ',' ? '.' : ','));
        if (sep && sep !== dec) {
            numStr = numStr.split(sep).join('');
        }
        if (dec && dec !== '.') {
            numStr = numStr.replace(dec, '.');
        }
        return parseFloat(numStr) || 0;
    }

    function formatoNumeroLocal(num) {
        if (typeof window.fvFormatLocaleNumber === 'function') {
            return window.fvFormatLocaleNumber(num, FORMAT_NUMERIC);
        }
        var digits = FORMAT_NUMERIC.digits != null ? FORMAT_NUMERIC.digits : 2;
        var dec = FORMAT_NUMERIC.radixPoint || ',';
        var sep = (typeof window.fvResolveNumericGroupSeparator === 'function')
            ? window.fvResolveNumericGroupSeparator(FORMAT_NUMERIC)
            : (FORMAT_NUMERIC.groupSeparator || (dec === ',' ? '.' : ','));
        var parts = Number(num).toFixed(digits).split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, sep);
        return parts.join(dec);
    }

    function fvUpdateAddProductTotal() {
        var qty = parseFloat($('#qty_add').val()) || 0;
        var price = parseNumeroLocal($('#sale_price_add').val());
        if (qty <= 0 || price <= 0) {
            $('#total_add').val('');
            return;
        }
        $('#total_add').val(formatoNumeroLocal(qty * price));
    }

    $(document).on('input change', '#qty_add, #sale_price_add', fvUpdateAddProductTotal);

    function actualizarTotalFila($fila) {
        var qty = parseNumeroLocal($fila.find('.input-qty').val());
        var price = parseNumeroLocal($fila.find('.input-price').val());
        $fila.find('.product-total').text(formatoNumeroLocal(qty * price));
    }

    function calcularTotales() {
        var subtotal = 0;
        $('.table tbody tr.item').each(function () {
            var $row = $(this);
            var qty = parseNumeroLocal($row.find('.input-qty').val());
            var price = parseNumeroLocal($row.find('.input-price').val());
            var lineTotal = qty * price;
            $row.find('.product-total').text(formatoNumeroLocal(lineTotal));
            subtotal += lineTotal;
        });
        var discountType = $('#discountTypeSelect').val();
        var discountValue = parseNumeroLocal($('#input_discount').val());
        var totalDiscount = 0;
        if (discountType === '1') {
            totalDiscount = subtotal * (discountValue / 100);
            $('#span_discount').text(formatoNumeroLocal(discountValue) + '%');
        } else if (discountType === '2') {
            totalDiscount = discountValue;
            $('#span_discount').text(monedaSymbol + formatoNumeroLocal(discountValue));
        } else {
            $('#span_discount').text('0');
        }
        totalDiscount = Math.min(totalDiscount, subtotal);
        $('#sub_total').text(formatoNumeroLocal(subtotal));
        $('#total_descuento').text(formatoNumeroLocal(totalDiscount));
        $('#total_orden').text(formatoNumeroLocal(subtotal - totalDiscount));
    }
    window.calcularTotales = calcularTotales;

    $(document).on('input change', '.input-qty, .input-price', function () {
        actualizarTotalFila($(this).closest('tr.item'));
        calcularTotales();
    });
    $(document).on('input change', '#discountTypeSelect, #input_discount', calcularTotales);
});
</script>
