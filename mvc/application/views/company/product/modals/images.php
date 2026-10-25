<?php

/**
 * @var $pageTexts array the translated string conglomerated for this view
 * @var object $product the product data
 */
$skuDisp = strtoupper(trim((string) ($product->sku ?? '')));
$slotLabelFmt = $pageTexts['product_image_slot_number'] ?? '%d';
$uploadLabel = (string) ($pageTexts['seleccionar_archivo'] ?? '');
$deleteLabel = (string) ($pageTexts['toltip_eliminar'] ?? '');
?>
<link rel="stylesheet" href="<?= dated_file_url('companyasset/css/fv-product-images-modal.css') ?>" />
<div class="table-responsive">
<div class="fv-product-images-grid">
    <div class="fv-product-images-grid__headers">
        <?php for ($hi = 0; $hi < 5; $hi++): ?>
        <div class="fv-product-images-grid__header">
            <?php if ($skuDisp !== ''): ?>
                <?= $hi === 0
                    ? htmlspecialchars($skuDisp, ENT_QUOTES, 'UTF-8')
                    : htmlspecialchars($skuDisp . '_PIC' . ($hi + 1), ENT_QUOTES, 'UTF-8') ?>
            <?php else: ?>
                <?= htmlspecialchars(sprintf($slotLabelFmt, $hi + 1), ENT_QUOTES, 'UTF-8') ?>
            <?php endif; ?>
        </div>
        <?php endfor; ?>
    </div>
    <div id="lista_sortable"
         class="fv-product-images-grid__row"
         data-producto="<?= htmlspecialchars((string) ($product->pro_id ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        <?php
        $images = isset($product->images) && is_array($product->images) ? $product->images : [];
        for ($i = 0; $i < 5; $i++):
            $v = isset($images[$i]) ? $images[$i] : null;
            if (isset($v) && $v !== null):
                ?>
                <div class="fv-product-images-slot sortable-item"
                     data-name-pic="<?= htmlspecialchars((string) ($v->name ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                     data-id="<?= htmlspecialchars((string) ($v->uuid ?? $v->image_id ?? $v->UUID ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                     data-slot="<?= (int) ($i + 1) ?>">
                    <div class="fv-product-img-slot">
                        <div class="fv-img-drag-handle" title="<?= htmlspecialchars($uploadLabel, ENT_QUOTES, 'UTF-8') ?>">
                            <i class="fa-solid fa-grip-vertical" aria-hidden="true"></i>
                        </div>
                        <div data-image="<?= htmlspecialchars((string) ($v->name ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                             class="fileinput fileinput-exists"
                             data-provides="fileinput">
                            <div class="fv-product-img-slot__filename fileinput-filename">
                                <?= htmlspecialchars((string) ($v->name ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <figure class="fv-product-img-slot__preview fileinput-exists thumbnail">
                                <?php
                                $slotUuid = (string) ($v->uuid ?? $v->image_id ?? '');
                                $slotExt = strtolower((string) ($v->extension ?? pathinfo((string) ($v->name ?? ''), PATHINFO_EXTENSION)));
                                $slotImgUrl = $slotUuid !== '' && function_exists('company_product_image_url')
                                    ? company_product_image_url($v, 0, $slotExt)
                                    : ($slotUuid !== '' && function_exists('fv_list_image_urls')
                                        ? (fv_list_image_urls($slotUuid, $v)['primary'] ?: catalog_public_no_image_url())
                                        : catalog_public_no_image_url());
                                $slotFallbacks = [];
                                if ($slotUuid !== '' && function_exists('fv_list_image_urls')) {
                                    $slotFallbacks = fv_list_image_urls($slotUuid, $v)['fallbacks'] ?? [];
                                }
                                $slotFallbackJson = htmlspecialchars(
                                    json_encode(array_values($slotFallbacks), JSON_UNESCAPED_SLASHES),
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>
                                <img src="<?= htmlspecialchars($slotImgUrl, ENT_QUOTES, 'UTF-8') ?>"
                                     alt="<?= htmlspecialchars((string) ($v->name ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                     loading="lazy"
                                     decoding="async"
                                     data-fv-img-fallbacks="<?= $slotFallbackJson ?>"
                                     onerror="(function(img){var list=[];try{list=JSON.parse(img.getAttribute('data-fv-img-fallbacks')||'[]');}catch(e){}var i=parseInt(img.getAttribute('data-fv-img-i')||'0',10);if(i<list.length){img.setAttribute('data-fv-img-i',String(i+1));img.src=list[i];return;}img.onerror=null;img.src=<?= json_encode(catalog_public_no_image_url(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;})(this);">
                            </figure>
                            <div class="fileinput-preview fileinput-exists thumbnail"></div>
                            <div class="fv-product-img-slot__actions text-center">
                                <span class="btn btn-default btn-file"
                                      title="<?= htmlspecialchars($uploadLabel, ENT_QUOTES, 'UTF-8') ?>">
                                    <span class="fileinput-new"><i class="fa-solid fa-upload" aria-hidden="true"></i></span>
                                    <span class="fileinput-exists"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i></span>
                                    <input type="file"
                                           accept="image/*"
                                           title="<?= htmlspecialchars($uploadLabel, ENT_QUOTES, 'UTF-8') ?>"
                                           name="images[<?= (int) $i ?>]"
                                           data-slot="<?= (int) ($i + 1) ?>">
                                    <input type="hidden" name="images_id[<?= (int) $i ?>]"
                                           value="<?= htmlspecialchars($slotUuid, ENT_QUOTES, 'UTF-8') ?>">
                                </span>
                                <?php if ($slotUuid !== '') : ?>
                                <a class="btn btn-danger eliminar"
                                   title="<?= htmlspecialchars($deleteLabel, ENT_QUOTES, 'UTF-8') ?>"
                                   href="javascript:void(0)"
                                   onclick='deleteProductImage(<?= json_encode((string) ($v->name ?? ''), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, <?= json_encode($slotUuid, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, $(this), <?= json_encode((string) ($product->pro_id ?? ''), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);'>
                                    <i class="mdi mdi-delete" aria-hidden="true"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else:
                $slotNum = $i + 1;
                ?>
                <div class="fv-product-images-slot fv-product-images-slot--empty"
                     data-id=""
                     data-slot="<?= (int) $slotNum ?>">
                    <div class="fv-product-img-slot fv-product-img-slot--empty fv-product-img-slot--slot-<?= (int) $slotNum ?>">
                        <div class="fileinput fileinput-new" data-provides="fileinput">
                            <div class="fv-product-img-slot__filename fileinput-filename"></div>
                            <div class="fv-product-img-slot__preview">
                                <div class="fv-product-img-slot__empty-avatar" aria-hidden="true">
                                    <span class="fv-product-img-slot__empty-avatar-num"><?= (int) $slotNum ?></span>
                                </div>
                            </div>
                            <div class="fileinput-preview fileinput-exists thumbnail"></div>
                            <div class="fv-product-img-slot__actions text-center">
                                <span class="btn btn-default btn-file"
                                      title="<?= htmlspecialchars($uploadLabel, ENT_QUOTES, 'UTF-8') ?>">
                                    <span class="fileinput-new"><i class="fa-solid fa-upload" aria-hidden="true"></i></span>
                                    <span class="fileinput-exists"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i></span>
                                    <input type="file"
                                           accept="image/*"
                                           title="<?= htmlspecialchars($uploadLabel, ENT_QUOTES, 'UTF-8') ?>"
                                           name="images[<?= (int) $i ?>]"
                                           data-slot="<?= (int) $slotNum ?>">
                                    <input type="hidden" name="images_id[<?= (int) $i ?>]" value="">
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php
            endif;
        endfor;
        ?>
    </div>
</div>
</div>
