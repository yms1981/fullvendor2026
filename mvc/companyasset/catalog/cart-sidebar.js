(function ($) {
    'use strict';

    var $miniCart = $('#catalog-mini-cart');
    var $items = $('#catalog-mini-cart-items');
    var $empty = $('#catalog-mini-cart-empty');
    var $subtotal = $('#catalog-mini-cart-subtotal');
    var loading = false;
    var updatingIds = {};
    var lang = {
        empty: '',
        remove: '',
        qty: '',
        increase: '',
        decrease: ''
    };

    function baseUrl() {
        if (typeof url_base !== 'undefined' && url_base) {
            return url_base;
        }
        if (typeof env !== 'undefined' && env && env.url) {
            return env.url;
        }
        return '/';
    }

    function cartToken() {
        return (typeof token !== 'undefined' && token) ? token : '';
    }

    function catalogLang() {
        var block = (typeof env !== 'undefined' && env.lang && (env.lang['cmp-catalog'] || env.lang.cmp_catalog))
            ? (env.lang['cmp-catalog'] || env.lang.cmp_catalog)
            : null;
        var dict = block && block.lang ? block.lang : (block || {});
        return dict;
    }

    function resolveLang() {
        var dict = catalogLang();
        lang.empty = $miniCart.data('label-empty') || dict.cart_empty || lang.empty || '';
        lang.remove = $miniCart.data('label-remove') || dict.cart_remove_item || (typeof popoverRemoveFromCart !== 'undefined' ? popoverRemoveFromCart : 'Remove');
        lang.qty = $miniCart.data('label-qty') || dict.cantidad || (typeof popoverQty !== 'undefined' ? popoverQty : 'Qty');
        lang.increase = $miniCart.data('label-increase') || dict.cart_qty_increase || lang.increase || '';
        lang.decrease = $miniCart.data('label-decrease') || dict.cart_qty_decrease || lang.decrease || '';
        if ($empty.find('p').length && lang.empty) {
            $empty.find('p').text(lang.empty);
        }
    }

    function noImageUrl() {
        return String($miniCart.data('no-image') || '');
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function qtyStep(minimumStock) {
        var min = parseFloat(minimumStock);
        return min > 0 ? min : 1;
    }

    function openCart() {
        $miniCart.addClass('active').attr('aria-hidden', 'false');
        $('.off_canvars_overlay').addClass('active');
        $('.catalog-cart-toggle').attr('aria-expanded', 'true');
        refreshMiniCart();
    }

    function closeCart() {
        $miniCart.removeClass('active').attr('aria-hidden', 'true');
        $('.off_canvars_overlay').removeClass('active');
        $('.catalog-cart-toggle').attr('aria-expanded', 'false');
    }

    function isOpen() {
        return $miniCart.hasClass('active');
    }

    function renderQtyStepper(item) {
        var proId = parseInt(item.pro_id, 10) || 0;
        var qtyRaw = parseFloat(item.qty);
        if (isNaN(qtyRaw)) {
            qtyRaw = 0;
        }
        var step = qtyStep(item.minimum_stock);
        var qtyLabel = escapeHtml(item.qty_label != null ? item.qty_label : qtyRaw);
        var unit = escapeHtml(item.price_label || '0.00');
        return ''
            + '<div class="catalog-mini-cart__qty-row">'
            + '  <div class="catalog-mini-cart__stepper" role="group" aria-label="' + escapeHtml(lang.qty) + '">'
            + '    <button type="button" class="catalog-mini-cart__qty-btn catalog-mini-cart__qty-btn--minus"'
            + '      data-product-id="' + proId + '"'
            + '      data-qty="' + qtyRaw + '"'
            + '      data-step="' + step + '"'
            + '      aria-label="' + escapeHtml(lang.decrease) + '"'
            + '      title="' + escapeHtml(lang.decrease) + '">'
            + '      <i class="fa fa-minus" aria-hidden="true"></i>'
            + '    </button>'
            + '    <span class="catalog-mini-cart__qty-value" data-product-id="' + proId + '">' + qtyLabel + '</span>'
            + '    <button type="button" class="catalog-mini-cart__qty-btn catalog-mini-cart__qty-btn--plus"'
            + '      data-product-id="' + proId + '"'
            + '      data-qty="' + qtyRaw + '"'
            + '      data-step="' + step + '"'
            + '      aria-label="' + escapeHtml(lang.increase) + '"'
            + '      title="' + escapeHtml(lang.increase) + '">'
            + '      <i class="fa fa-plus" aria-hidden="true"></i>'
            + '    </button>'
            + '  </div>'
            + '  <span class="catalog-mini-cart__unit-price">$' + unit + '</span>'
            + '</div>';
    }

    function renderItems(items) {
        if (!$items.length) {
            return;
        }
        $items.empty();
        if (!items || !items.length) {
            $empty.removeClass('d-none');
            return;
        }
        $empty.addClass('d-none');
        var fallback = escapeHtml(noImageUrl());
        items.forEach(function (item) {
            var img = escapeHtml(item.image || fallback);
            var name = escapeHtml(item.name || '');
            var sku = escapeHtml(item.sku || '');
            var total = escapeHtml(item.total_label || '0.00');
            var proId = parseInt(item.pro_id, 10) || 0;
            var html = ''
                + '<div class="cart_item catalog-mini-cart__item" data-pro-id="' + proId + '">'
                + '  <div class="cart_img fv-catalog-thumb">'
                + '    <a href="javascript:void(0)"><img src="' + img + '" alt="' + name + '" onerror="this.onerror=null;this.src=\'' + fallback + '\';"></a>'
                + '  </div>'
                + '  <div class="cart_info">'
                + '    <a href="javascript:void(0)">' + name + '</a>'
                + (sku ? ('    <p class="mb-1 text-muted small catalog-mini-cart__sku">SKU: ' + sku + '</p>') : '')
                + renderQtyStepper(item)
                + '    <p class="catalog-mini-cart__line-total">$' + total + '</p>'
                + '  </div>'
                + '  <div class="cart_remove">'
                + '    <a href="javascript:void(0)" class="catalog-mini-cart-remove" data-product-id="' + proId + '" title="' + escapeHtml(lang.remove) + '" aria-label="' + escapeHtml(lang.remove) + '">'
                + '      <i class="fa fa-times" aria-hidden="true"></i>'
                + '    </a>'
                + '  </div>'
                + '</div>';
            $items.append(html);
        });
    }

    function showCartMessage(message, icon) {
        if (!message) {
            return;
        }
        if (typeof $.toast === 'function') {
            $.toast({
                text: message,
                hideAfter: icon === 'error' ? 3500 : 1500,
                icon: icon || 'info',
                position: 'top-center'
            });
            return;
        }
        if (typeof swalalert !== 'undefined' && swalalert.fire) {
            swalalert.fire({
                icon: icon || 'info',
                html: message
            });
        }
    }

    function showCartError(response) {
        var message = '';
        if (response && response.message) {
            message = response.message.message || response.message;
        } else if (response && response.msg) {
            message = response.msg;
        }
        showCartMessage(message || '', 'error');
    }

    function applyQtySuccess(response, productId) {
        if (!response || !response.success) {
            showCartError(response);
            return false;
        }

        if (typeof updateCartTotals === 'function') {
            updateCartTotals(response.detalle, { skipMiniCartRefresh: true });
        } else if (response.detalle && response.detalle.total_orden != null) {
            $subtotal.text(response.detalle.total_orden);
            $('.total-monto').text(response.detalle.total_orden);
        }

        if (typeof print_qty === 'function') {
            print_qty(productId, response.cantidad_actual);
        }

        refreshMiniCart();
        return true;
    }

    function setRowUpdating(productId, isUpdating) {
        var id = parseInt(productId, 10) || 0;
        if (!id) {
            return;
        }
        if (isUpdating) {
            updatingIds[id] = true;
        } else {
            delete updatingIds[id];
        }
        var $row = $items.find('.catalog-mini-cart__item[data-pro-id="' + id + '"]');
        if ($row.length) {
            $row.toggleClass('is-updating', !!isUpdating);
        }
    }

    function isRowUpdating(productId) {
        return !!updatingIds[parseInt(productId, 10) || 0];
    }

    function incrementQty(productId) {
        if (isRowUpdating(productId)) {
            return;
        }
        setRowUpdating(productId, true);
        $.ajax({
            url: baseUrl() + 'catalogo/producto/quickAddProduct/' + productId,
            type: 'get',
            dataType: 'json',
            data: { token: cartToken() },
            success: function (response) {
                applyQtySuccess(response, productId);
            },
            error: function () {
                showCartMessage('', 'error');
            },
            complete: function () {
                setRowUpdating(productId, false);
            }
        });
    }

    function decrementQty(productId, currentQty, step) {
        if (isRowUpdating(productId)) {
            return;
        }
        var qty = parseFloat(currentQty);
        var pack = parseFloat(step);
        if (isNaN(qty)) {
            qty = 0;
        }
        if (isNaN(pack) || pack <= 0) {
            pack = 1;
        }
        var newQty = qty - pack;
        if (newQty > 0 && newQty < pack) {
            newQty = 0;
        }
        if (newQty < 0) {
            newQty = 0;
        }

        setRowUpdating(productId, true);
        $.ajax({
            url: baseUrl() + 'catalogo/producto/saveqty',
            type: 'post',
            dataType: 'json',
            data: {
                token: cartToken(),
                product_id: productId,
                qty: newQty,
                origen: 'sidebar'
            },
            success: function (response) {
                applyQtySuccess(response, productId);
            },
            error: function () {
                showCartMessage('', 'error');
            },
            complete: function () {
                setRowUpdating(productId, false);
            }
        });
    }

    function refreshMiniCart() {
        if (!$miniCart.length || loading) {
            return;
        }
        var t = cartToken();
        if (!t) {
            renderItems([]);
            return;
        }
        loading = true;
        $miniCart.addClass('is-loading');
        $.ajax({
            url: baseUrl() + 'catalogo/producto/mini_cart',
            type: 'get',
            dataType: 'json',
            data: { token: t },
            success: function (response) {
                if (response && response.success) {
                    renderItems(response.items || []);
                    if (response.detalle && typeof updateCartTotals === 'function') {
                        updateCartTotals(response.detalle, { skipMiniCartRefresh: true });
                    } else if (response.detalle && response.detalle.total_orden != null) {
                        $subtotal.text(response.detalle.total_orden);
                        $('.total-monto').text(response.detalle.total_orden);
                    }
                } else {
                    renderItems([]);
                }
            },
            error: function () {
                renderItems([]);
            },
            complete: function () {
                loading = false;
                $miniCart.removeClass('is-loading');
            }
        });
    }

    window.refreshMiniCart = refreshMiniCart;
    window.openCatalogMiniCart = openCart;
    window.closeCatalogMiniCart = closeCart;

    $(document).on('click', '.catalog-cart-toggle', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (isOpen()) {
            closeCart();
        } else {
            openCart();
        }
    });

    $(document).on('click', '.catalog-cart-close, .mini_cart_close a', function (e) {
        e.preventDefault();
        closeCart();
    });

    $(document).on('click', '.off_canvars_overlay', function () {
        if (isOpen()) {
            closeCart();
        }
    });

    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' && isOpen()) {
            closeCart();
        }
    });

    $(document).on('click', '.catalog-mini-cart__qty-btn--plus', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var id = $(this).data('product-id');
        incrementQty(id);
    });

    $(document).on('click', '.catalog-mini-cart__qty-btn--minus', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var $btn = $(this);
        decrementQty($btn.data('product-id'), $btn.data('qty'), $btn.data('step'));
    });

    $(document).on('click', '.catalog-mini-cart-remove', function (e) {
        e.preventDefault();
        var id = $(this).data('product-id');
        var $row = $(this).closest('.catalog-mini-cart__item');
        if (isRowUpdating(id)) {
            return;
        }
        setRowUpdating(id, true);
        $.ajax({
            url: baseUrl() + 'catalogo/producto/eliminar',
            type: 'post',
            dataType: 'json',
            data: { token: cartToken(), id: id },
            success: function (response) {
                if (response && response.success) {
                    if (typeof updateCartTotals === 'function') {
                        updateCartTotals(response.detalle, { skipMiniCartRefresh: true });
                    }
                    if (typeof print_qty === 'function') {
                        print_qty(id, 0);
                    }
                    $row.slideUp(160, function () {
                        refreshMiniCart();
                    });
                    if (typeof $.toast === 'function') {
                        $.toast({
                            text: response.msg || '',
                            hideAfter: 1500,
                            icon: 'success',
                            position: 'top-center'
                        });
                    }
                }
            },
            complete: function () {
                setRowUpdating(id, false);
            }
        });
    });

    $(function () {
        // Quitar el handler del theme (main.js) que abre el panel vacío
        $('.mini_cart_wrapper > a.catalog-cart-toggle').off('click');
        resolveLang();
        refreshMiniCart();
    });
})(jQuery);
