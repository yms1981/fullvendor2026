function cambiarSimboloDescuentoEnGrilla() {
    let val = $('.select_discount').val();
    let input_discount = $('#input_discount').val();
    let text = null;

    if (val == 1) {
        text = input_discount + '%'

    } else if (val == 2) {
        text = '$'+input_discount
    }

    $('#span_discount').text(text);
}
function actualizarCamposFormularioOrden(response, options) {
    options = options || {};
    $('#customer_email').val(response.email);
    if (!options.preserveDiscount) {
        $("#discountTypeSelect").val(response.discount_type);
        $("#input_discount").val(response.discount);
    }
    $("#group_price").val(response.group_price);
    if (!options.preserveSeller && response.seller_id) {
        $('#picker_seller').selectpicker('val', response.seller_id);
    }
    cambiarSimboloDescuentoEnGrilla();
}

function buildOrderPayload() {
    const parseNum = typeof window.fvParseLocaleNumber === 'function'
        ? window.fvParseLocaleNumber
        : function (val) { return parseFloat(String(val).replace(/\./g, '').replace(',', '.')) || 0; };
    const customer_id = $('#selectCustomer').val();
    const seller_id = $('#picker_seller').val();
    const discount_type = $('#discountTypeSelect').val();
    const discount = parseNum($('#input_discount').val());
    const proforma = $('#seller-proforma').is(':checked');
    const internal_comments = $('[name="internal_comments"]').val() || '';

    const products = [];

    $('.table tbody tr.item').each(function () {
        const row = $(this);
        const pro_id = row.data('product-id');
        const qty = parseFloat(row.find('.input-qty').val()) || 0;
        const price = parseNum(row.find('.input-price').val());
        const fixed_price = row.find('.check-fixed-price').is(':checked');
        const comment = (row.find('.input-comment').val() || '').trim();

        if (pro_id && qty > 0) {
            products.push({
                pro_id: pro_id,
                qty: qty,
                price: price,
                fixed_price: !!fixed_price,
                comment: comment
            });
        }
    });

    const tipoD = ($('#tipo_d_order').length && $('#tipo_d_order').val()) || 'D';
    const payload = {
        customer_id: customer_id,
        seller_id: seller_id !== undefined && seller_id !== '' ? parseInt(seller_id, 10) : 0,
        discount_type: discount_type !== undefined && discount_type !== '' ? String(discount_type) : '1',
        discount: discount,
        proforma: !!proforma,
        internal_comments: internal_comments,
        products: products,
        tipo_d: tipoD || 'D',
        source: 'manual'
    };
    const cartId = window.fvOpenCart && window.fvOpenCart.cart_id ? parseInt(window.fvOpenCart.cart_id, 10) : 0;
    const tokenKor = window.fvOpenCart && window.fvOpenCart.token_kor ? String(window.fvOpenCart.token_kor) : '';
    if (cartId > 0) {
        payload.cart_id = cartId;
    }
    if (tokenKor) {
        payload.token_kor = tokenKor;
    }
    return payload;
}

function syncOpenCartDraft() {
    if (typeof window.orderEditLang !== 'undefined') {
        return;
    }
    if (!$('#order_form').length) {
        return;
    }
    const payload = buildOrderPayload();
    if (!payload.customer_id) {
        return;
    }
    const urlBase = (typeof options !== 'undefined' && options && options.url) ? options.url : '';
    if (!urlBase) {
        return;
    }
    $.ajax({
        url: urlBase + '/orders/syncOpenCart',
        type: 'POST',
        data: { data: JSON.stringify(payload) },
        dataType: 'json'
    }).done(function (resp) {
        window.fvOpenCart = window.fvOpenCart || {};
        if (resp && resp.cart_id) {
            window.fvOpenCart.cart_id = resp.cart_id;
            $('#open_cart_id').val(resp.cart_id);
        }
        if (resp && resp.token_kor) {
            window.fvOpenCart.token_kor = resp.token_kor;
            $('#open_cart_token').val(resp.token_kor);
        }
    });
}

function scheduleOpenCartSync() {
    if (window.fvOpenCartSyncTimer) {
        clearTimeout(window.fvOpenCartSyncTimer);
    }
    window.fvOpenCartSyncTimer = setTimeout(syncOpenCartDraft, 400);
}

function submitOrderAjax() {
    if (window.planLimitBlock && window.planLimitBlock.can_create === false) {
        if (typeof swal !== 'undefined') {
            swal({ title: window.planLimitBlock.message || 'Limit reached', icon: 'warning' });
        }
        return;
    }

    const payload = buildOrderPayload();

    if (!payload.customer_id) {
        if (typeof swal !== 'undefined') {
            swal('Cliente requerido', 'Debe seleccionar un cliente.', 'error');
        }
        return;
    }
    if (!payload.products || payload.products.length === 0) {
        if (typeof swal !== 'undefined') {
            swal('Productos requeridos', 'Debe agregar al menos un producto a la orden.', 'error');
        }
        return;
    }

    $.ajax({
        url: (typeof options !== 'undefined' && options && options.url ? options.url : '') + '/orders/save',
        type: 'POST',
        data: { data: JSON.stringify(payload) },
        dataType: 'json',
        beforeSend: function () {
            var $btns = $('#btn-update, #send-form');
            $btns.each(function () {
                var $btn = $(this);
                var $label = $btn.find('span').first();
                if (!$btn.data('default-label')) {
                    $btn.data('default-label', $label.length ? $label.text() : $btn.text());
                }
                $btn.prop('disabled', true);
                if ($label.length) {
                    $label.text((window.orderAddLang && window.orderAddLang.updating) || $btn.data('default-label'));
                }
            });
        },
        success: function(response) {
            var title = response.message || ((window.orderAddLang && window.orderAddLang.mensaje_success) || '');
            if (typeof swal !== 'undefined') {
                swal({ title: title, icon: 'success' }).then(function() { window.history.go(-1); });
            } else {
                window.history.go(-1);
            }
        },
        error: function(response) {
            $('#btn-update, #send-form').each(function () {
                var $btn = $(this);
                var $label = $btn.find('span').first();
                $btn.prop('disabled', false);
                if ($label.length && $btn.data('default-label')) {
                    $label.text($btn.data('default-label'));
                }
            });
            var title = (response.responseJSON && response.responseJSON.message) ? response.responseJSON.message : ((window.orderAddLang && window.orderAddLang.error_orden) || '');
            var errors = response.responseJSON && response.responseJSON.errors;
            var errorsMessages = errors ? Object.values(errors).reduce(function(acc, curr) { return acc.concat(curr); }, []).join('\n') : null;
            if (typeof swal !== 'undefined') {
                swal({ title: title, text: errorsMessages || undefined, icon: 'error' });
            }
        }
    });
}

$(document).ready(function(e){
    $('#form-full-vendor, #order_form').on('submit', function (e) {
        if (this.id === 'order_form' && typeof window.orderEditLang !== 'undefined') {
            return;
        }
        e.preventDefault();
        submitOrderAjax();
    });

    $('#btn-update, #send-form').click(function (e) {
        if ($(this).closest('#order_form').length && typeof window.orderEditLang !== 'undefined') {
            return;
        }
        e.preventDefault();
        submitOrderAjax();
    });

    if (typeof window.orderEditLang === 'undefined') {
        $(document).on('click', '#btn-add', function () {
            scheduleOpenCartSync();
        });
        $(document).on('input change', '#order_form .input-qty, #order_form .input-price, #order_form .input-comment', function () {
            scheduleOpenCartSync();
        });
        $(document).on('click', '#order_form .btn-remove-product', function () {
            var $row = $(this).closest('tr.item');
            $row.remove();
            if (typeof window.calcularTotales === 'function') {
                window.calcularTotales();
            }
            scheduleOpenCartSync();
        });

        if ($('#order_form tbody tr.item').length > 0) {
            scheduleOpenCartSync();
        }
    }
})
