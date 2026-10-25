<?php
/**
 * Panel de grupos de precios.
 *
 * @var array $pageTexts
 * @var array $groups
 * @var object|null $product
 */
?>
<div class="fv-product-price-groups">
    <div class="fv-product-price-groups__title">
        <?= htmlspecialchars((string) ($pageTexts['grupo_precios_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
    </div>
    <div class="fv-product-price-groups__body">
        <?php $this->load->view('company/product/price_groups/price_group_calculator', [
            'pageTexts' => $pageTexts,
            'groups' => $groups ?? [],
            'product' => $product ?? null,
            'format_currency' => $format_currency ?? '',
        ]); ?>
    </div>
</div>
