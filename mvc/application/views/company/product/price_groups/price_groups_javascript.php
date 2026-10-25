<?php
/**
 * @var string $priceInputSelector
 */

if(empty($priceInputSelector))
    $priceInputSelector = '#precio-ajax';
?>
<script type="text/javascript">
    var PriceGroup = {
        precesOfPrice: 0,
        init: function() {},

        updatePriceGroups: function (price) {
            PriceGroup.precesOfPrice = price;
            $.ajax({

                url: '<?= company_url('products/priceGroups') ?>',
                type: 'post',
                data: {
                    precio_venta: price
                },
                dataType: 'json',
                success: function(response) {
                    var rows = [];
                    if (Array.isArray(response)) {
                        rows = response;
                    } else if (response && Array.isArray(response.data)) {
                        rows = response.data;
                    } else if (response && typeof response === 'object') {
                        Object.keys(response).forEach(function(key) {
                            if (/^\d+$/.test(key) && response[key] && typeof response[key] === 'object') {
                                rows.push(response[key]);
                            }
                        });
                    }

                    $.each(rows, function(key, value) {
                        if (!value || typeof value !== 'object') {
                            return;
                        }
                        var selector = value.group_id
                            ? '.fv-product-price-group-item__amount[data-group-id="' + value.group_id + '"]'
                            : '.fv-product-price-group-item__amount[data-name="' + value.name + '"]';
                        var $target = $(selector);
                        if ($target.length) {
                            $target.text(value.sale_price_recalculado);
                        }
                    });
                }
            })
        }
    }
    $(document).on("keyup", "<?=$priceInputSelector?>", function(e) {

        let precio = $(this).val();
        setTimeout(() => { if(precio!==PriceGroup.precesOfPrice && precio===$(this).val() ) PriceGroup.updatePriceGroups(precio) }, 500);

    })
</script>
