<?php
/**
 * @var object $product
 * @var array $pageTexts
 * @var string $productImageUrl
 * @var string $productPriceFormatted
 * @var string $productCategoriesLabel
 */
?>
<div class="modal fade" id="modal_change_sku" tabindex="-1" role="dialog" aria-labelledby="modalChangeSkuLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalChangeSkuLabel">
                    <?= htmlspecialchars((string) ($pageTexts['change_sku_modal_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="<?= htmlspecialchars((string) ($pageTexts['cancelar'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="fv-change-sku-preview">
                    <div class="fv-change-sku-preview__media">
                        <img src="<?= htmlspecialchars((string) $productImageUrl, ENT_QUOTES, 'UTF-8') ?>"
                             alt="<?= htmlspecialchars((string) ($product->name ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="fv-change-sku-preview__info">
                        <div class="fv-change-sku-preview__name">
                            <?= htmlspecialchars((string) ($product->name ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <div class="fv-change-sku-preview__price">
                            <?= htmlspecialchars((string) ($pageTexts['f_saleprice'] ?? ''), ENT_QUOTES, 'UTF-8') ?>:
                            <strong><?= htmlspecialchars((string) $productPriceFormatted, ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                        <div class="fv-change-sku-preview__categories">
                            <?= htmlspecialchars((string) ($pageTexts['f_categlist'] ?? ''), ENT_QUOTES, 'UTF-8') ?>:
                            <span><?= htmlspecialchars((string) $productCategoriesLabel, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="fv-change-sku-preview__current">
                            <?= htmlspecialchars((string) ($pageTexts['f_sku'] ?? ''), ENT_QUOTES, 'UTF-8') ?>:
                            <strong id="change_sku_current"><?= htmlspecialchars((string) ($product->sku ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                    </div>
                </div>

                <div class="form-group mt-4 mb-0">
                    <label for="change_sku_new">
                        <?= htmlspecialchars((string) ($pageTexts['change_sku_new_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        <span class="color-red">*</span>
                    </label>
                    <input type="text"
                           class="form-control"
                           id="change_sku_new"
                           name="change_sku_new"
                           autocomplete="off"
                           value="">
                    <div id="change_sku_error" class="text-danger small mt-2" style="display:none;"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal">
                    <?= htmlspecialchars((string) ($pageTexts['cancelar'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </button>
                <button type="button" class="btn btn-success" id="btn_confirm_change_sku">
                    <?= htmlspecialchars((string) ($pageTexts['change_sku_save'] ?? $pageTexts['guardar'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </button>
            </div>
        </div>
    </div>
</div>
