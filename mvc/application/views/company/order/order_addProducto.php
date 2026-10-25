<?php
/**
 * @var $product object the product data
 * @var $customer_product_price string the price of the product for the customer
 * @var $pageTexts array the translated string conglomerated for this view
 * @var string $moneda
 */
$pageTexts = is_array($pageTexts ?? null) ? $pageTexts : [];
$moneda = ($moneda ?? '') !== '' ? $moneda : '$';
?>
<div class="fv-order-add-product-form">
    <div class="row">
        <div class="col-md-7 mb-3">
            <label class="fv-order-add-product-form__label"><?= htmlspecialchars((string) ($pageTexts['name_product'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>
            <textarea rows="2" readonly name="name_product" class="form-control fv-order-add-product-form__readonly"><?= htmlspecialchars(strtoupper((string) ($product->name ?? '')), ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
        <div class="col-md-5 mb-3">
            <label class="fv-order-add-product-form__label"><?= htmlspecialchars((string) ($pageTexts['sku'] ?? 'SKU'), ENT_QUOTES, 'UTF-8') ?></label>
            <input readonly type="text" name="sku" class="form-control fv-order-add-product-form__readonly" value="<?= htmlspecialchars(strtoupper((string) ($product->sku ?? '')), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id_producto" value="<?= (int) ($product->pro_id ?? 0) ?>">
            <?php
            $orderRating = function_exists('company_product_rating_value')
                ? company_product_rating_value($product)
                : 3.5;
            $orderRatingAria = (string) ($pageTexts['product_rating_aria'] ?? '');
            if ($orderRatingAria !== '' && strpos($orderRatingAria, '%s') !== false) {
                $orderRatingAria = sprintf($orderRatingAria, number_format($orderRating, 1, '.', ''));
            }
            if (function_exists('company_product_rating_stars_html')) :
                ?>
                <div class="fv-order-add-product-form__rating mt-2">
                    <?= company_product_rating_stars_html($orderRating, $orderRatingAria) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="fv-order-add-product-form__label"><?= htmlspecialchars((string) ($pageTexts['price_cliente'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><?= htmlspecialchars($moneda, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <input id="sale_price_add" class="form-control text-right mask" type="text" name="sale_price" value="<?= htmlspecialchars((string) $customer_product_price, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <a href="javascript:void(0)" class="fixed-price-cell" style="display: none;">
                <input id="fixed_price" type="checkbox" value="1" name="fixed_price">
                <u><label for="fixed_price"><?= htmlspecialchars((string) ($pageTexts['fixed_price'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label></u>
            </a>
        </div>
        <div class="col-md-4 mb-3">
            <label class="fv-order-add-product-form__label"><?= htmlspecialchars((string) ($pageTexts['unit_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>
            <input readonly type="text" class="form-control fv-order-add-product-form__readonly" value="<?= htmlspecialchars(strtoupper((string) ($product->unit->unit ?? '-')), ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label class="fv-order-add-product-form__label"><?= htmlspecialchars((string) ($pageTexts['stock'] ?? 'Stock'), ENT_QUOTES, 'UTF-8') ?></label>
            <input name="stock" readonly type="text" class="form-control fv-order-add-product-form__readonly text-center" value="<?= htmlspecialchars((string) ($product->stock ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="fv-order-add-product-form__label text-center d-block"><?= htmlspecialchars((string) ($pageTexts['cantidad'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>
            <input id="qty_add" autofocus class="form-control text-center fv-order-add-product-form__qty" type="number" name="qty" min="0" step="any">
        </div>
        <div class="col-md-6 mb-3">
            <label class="fv-order-add-product-form__label text-center d-block"><?= htmlspecialchars((string) ($pageTexts['price_total'] ?? 'Total'), ENT_QUOTES, 'UTF-8') ?></label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><?= htmlspecialchars($moneda, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <input id="total_add" readonly class="form-control text-right fv-order-add-product-form__readonly" type="text" name="total" placeholder="0">
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <label class="fv-order-add-product-form__label"><?= htmlspecialchars((string) ($pageTexts['comentarios'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>
            <textarea rows="2" name="comentarios" class="form-control"></textarea>
        </div>
    </div>
</div>
