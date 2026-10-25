<?php

/**
 * @var $company object the current company information
 * @var $pageTexts array the translated string conglomerated for this view
 * @var $unit_type array the available units of the company
 * @var $format_numeric object the format numeric of the company or the default if company doesn't have one configured
 * @var array $categorylist The list of categories of the company (for the select options)
 * @var array $currencyList The list of currencies of the company (for the select options)
 * @var array $groups The customer groups/ price lists available for the company
 * @var object $product The customer groups/ price lists available for the company
 */
    $currencySymbol = $format_currency ?? '';
?>

<form accept-charset="UTF-8" id="form_edit" class="forms-sample fv-product-form-page"
    action="<?= company_url('products/update/'.$product->pro_id) ?>" method="post" enctype="multipart/form-data" data-product-id="<?= $product->pro_id?>">
    <?php $this->load->view('company/product/_product_form_styles'); ?>
    <div class="container-fluid page-body-wrapper pt-3">
        <div class="main-panel">
            <div class="container-fluid">
                <div class="page-header mb-3">
                    <h3 class="page-title"><?= htmlspecialchars(strtoupper((string) ($pageTexts['ventana_editar_producto'] ?? '')), ENT_QUOTES, 'UTF-8') ?></h3>
                </div>

                <div class="fv-product-meta">
                    <span class="fv-product-meta__pill fv-product-meta__pill--id">ID <?= (int) $product->pro_id ?></span>
                    <span class="fv-product-meta__name"><?= htmlspecialchars((string) $product->name, ENT_QUOTES, 'UTF-8') ?></span>
                </div>

                <div class="fv-product-sheet">
                    <div class="fv-product-sheet__grid fv-product-sheet__grid--4col">
                        <div class="fv-product-col">
                            <div class="fv-product-field">
                                <label><?= $pageTexts['f_prodname']?> <span class="color-red">*</span></label>
                                <input type="hidden" name="pro_id" id="pro_id" value="<?= $product->pro_id ?>">
                                <input type="hidden" id="cambio_orden" name="cambio_orden" value="si">
                                <input id="nombre" onkeyup="return validar_campo(event,'#nombre')"
                                    class="form-control" type="text" value="<?= htmlspecialchars((string) $product->name, ENT_QUOTES, 'UTF-8') ?>" name="name">
                                <div class="container_alert"></div>
                            </div>
                            <div class="fv-product-field">
                                <label><?= $pageTexts['f_categlist']?> <span class="color-red">*</span></label>
                                <?php $cats = isset($product->categories) && is_array($product->categories) ? array_map(fn($cat) => is_object($cat) ? $cat->cat_id : $cat['cat_id'], $product->categories) : []; ?>
                                <select class="form-control w-100 product-edit-category-multi"
                                    data-info="<?= htmlspecialchars(json_encode($product), ENT_QUOTES, 'UTF-8') ?>" name="category_id[]" id="category"
                                    multiple="multiple">
                                    <?php if (count($categorylist) > 0): ?>
                                    <?php foreach ($categorylist as $key => $vc): ?>
                                    <?php $str_flag = in_array($vc->cat_id, $cats) ? 'selected' : ''; ?>
                                    <option value="<?= $vc->cat_id ?>" <?= $str_flag ?>>
                                        <?= htmlspecialchars((string) ($vc->category_name ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                    <?php endforeach ?>
                                    <?php endif ?>
                                </select>
                            </div>
                            <?php
                            $selectedGroupIds = [];
                            $selectedSubgroupIds = [];
                            $cgList = $product->categoryGroups ?? $product->category_groups ?? [];
                            $csgList = $product->categorySubgroups ?? $product->category_subgroups ?? [];
                            foreach (fv_normalize_relation_list($cgList) as $gRow) {
                                if (! empty($gRow->group_id)) {
                                    $selectedGroupIds[] = (int) $gRow->group_id;
                                }
                            }
                            foreach (fv_normalize_relation_list($csgList) as $sgRow) {
                                if (! empty($sgRow->subgroup_id)) {
                                    $selectedSubgroupIds[] = (int) $sgRow->subgroup_id;
                                }
                            }
                            if ($selectedGroupIds === [] && ! empty($product->category_group_id)) {
                                $selectedGroupIds[] = (int) $product->category_group_id;
                            }
                            if ($selectedSubgroupIds === [] && ! empty($product->category_subgroup_id)) {
                                $selectedSubgroupIds[] = (int) $product->category_subgroup_id;
                            }
                            $selectedGroupIds = array_values(array_unique($selectedGroupIds));
                            $selectedSubgroupIds = array_values(array_unique($selectedSubgroupIds));
                            $groupDisplayLabels = fv_product_category_group_labels($cgList, $csgList);
                            $groupNamesById = [];
                            foreach (fv_normalize_relation_list($cgList) as $gRow) {
                                $gidTmp = (int) ($gRow->group_id ?? 0);
                                $gNameTmp = trim((string) ($gRow->group_name ?? ''));
                                if ($gidTmp > 0 && $gNameTmp !== '') {
                                    $groupNamesById[$gidTmp] = $gNameTmp;
                                }
                            }
                            $subgroupMetaById = [];
                            foreach (fv_normalize_relation_list($csgList) as $sgRow) {
                                $sidTmp = (int) ($sgRow->subgroup_id ?? 0);
                                if ($sidTmp <= 0) {
                                    continue;
                                }
                                $parentIdTmp = (int) ($sgRow->group_id ?? 0);
                                $subNameTmp = trim((string) ($sgRow->subgroup_name ?? ''));
                                $parentNameTmp = $groupNamesById[$parentIdTmp] ?? '';
                                $subgroupMetaById[$sidTmp] = [
                                    'parent_id' => $parentIdTmp,
                                    'label' => ($parentNameTmp !== '' && $subNameTmp !== '')
                                        ? ($parentNameTmp . ' / ' . $subNameTmp)
                                        : $subNameTmp,
                                ];
                            }
                            ?>
                            <div class="fv-product-field fv-product-cat-groups-field">
                                <label><?= htmlspecialchars((string) ($pageTexts['f_category_groups'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>
                                <div class="d-flex flex-wrap align-items-start" style="gap:0.5rem;">
                                    <button type="button"
                                            class="btn btn-outline-primary btn-sm fv-product-cat-groups-search-btn"
                                            id="btn_open_category_groups"
                                            data-toggle="modal"
                                            data-target="#modal_product_category_groups"
                                            title="<?= htmlspecialchars((string) ($pageTexts['btn_select_category_groups'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            aria-label="<?= htmlspecialchars((string) ($pageTexts['btn_select_category_groups'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                        <i class="mdi mdi-magnify" aria-hidden="true"></i>
                                    </button>
                                    <div class="<?= $groupDisplayLabels !== [] ? 'fv-cat-groups-summary' : 'text-muted small' ?>"
                                         id="fv_cat_groups_summary"
                                         data-none="<?= htmlspecialchars((string) ($pageTexts['category_groups_none'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                        <?php if ($groupDisplayLabels !== []): ?>
                                            <?php foreach ($groupDisplayLabels as $label): ?>
                                                <span class="fv-cat-groups-summary__chip"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <?= htmlspecialchars((string) ($pageTexts['category_groups_none'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div id="fv_cat_groups_hidden_inputs">
                                    <?php foreach ($selectedGroupIds as $gid): ?>
                                        <input type="hidden"
                                               name="category_group_ids[]"
                                               value="<?= (int) $gid ?>"
                                               data-label="<?= htmlspecialchars((string) ($groupNamesById[$gid] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <?php endforeach; ?>
                                    <?php foreach ($selectedSubgroupIds as $sgid): ?>
                                        <?php $sgMeta = $subgroupMetaById[$sgid] ?? ['parent_id' => 0, 'label' => '']; ?>
                                        <input type="hidden"
                                               name="category_subgroup_ids[]"
                                               value="<?= (int) $sgid ?>"
                                               data-label="<?= htmlspecialchars((string) ($sgMeta['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                               data-parent-group-id="<?= (int) ($sgMeta['parent_id'] ?? 0) ?>">
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="fv-product-field fv-product-field--sku">
                                <label><?= $pageTexts['f_sku']?> <span class="color-red">*</span></label>
                                <div class="input-group">
                                    <input id="sku_display"
                                           type="text"
                                           class="form-control"
                                           value="<?= htmlspecialchars((string) $product->sku, ENT_QUOTES, 'UTF-8') ?>"
                                           readonly
                                           tabindex="-1"
                                           aria-readonly="true">
                                    <div class="input-group-append">
                                        <button type="button"
                                                class="btn btn-outline-secondary"
                                                id="btn_open_change_sku"
                                                data-toggle="modal"
                                                data-target="#modal_change_sku">
                                            <?= htmlspecialchars((string) ($pageTexts['change_sku_btn'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                        </button>
                                    </div>
                                </div>
                                <div class="container_alert"></div>
                            </div>
                        </div>

                        <div class="fv-product-col">
                            <div class="fv-product-field">
                                <label><?= $pageTexts['f_saleprice']?></label>
                                <?php $this->load->view('company/product/_product_currency_input', [
                                    'name' => 'sale_price',
                                    'id' => 'precio-ajax',
                                    'value' => format_currency($product->sale_price, $product->currency_type),
                                    'currencySymbol' => $currencySymbol,
                                ]); ?>
                            </div>
                            <div class="fv-product-field">
                                <label><?= $pageTexts['f_barcode']?></label>
                                <input id="barcode" onkeyup="return validar_campo(event,'#barcode')" type="text"
                                    class="form-control" value="<?= htmlspecialchars((string) $product->barcode, ENT_QUOTES, 'UTF-8') ?>" name="barcode">
                                <div class="container_alert"></div>
                            </div>
                            <div class="fv-product-field">
                                <label><?= $pageTexts['f_stock']?></label>
                                <?php
                                $unitQtyPlaces = function_exists('fv_product_unit_decimal_places')
                                    ? fv_product_unit_decimal_places($product)
                                    : 0;
                                $unitQtyRadix = (string) ($format_numeric->radixPoint ?? '.');
                                $unitQtyGroup = (string) ($format_numeric->groupSeparator ?? ',');
                                ?>
                                <input class="form-control fv-unit-qty text-left" type="text" name="stock"
                                    value="<?= htmlspecialchars(fv_format_unit_quantity($product->stock ?? 0, $product, $unitQtyRadix, $unitQtyGroup), ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                        </div>

                        <div class="fv-product-col fv-product-col--groups">
                            <?php $this->load->view('company/product/_product_price_groups_panel', [
                                'pageTexts' => $pageTexts,
                                'groups' => $groups ?? [],
                                'product' => $product,
                                'format_currency' => $format_currency ?? '',
                            ]); ?>
                        </div>

                        <div class="fv-product-col fv-product-col--image">
                            <div class="fv-product-image" id="imagen_principal">
                                <?php
                                $imagen_defecto = function_exists('company_product_image_url')
                                    ? company_product_image_url($product, 0)
                                    : company_url('image');
                                $imagen_fallback = function_exists('catalog_public_no_image_url')
                                    ? catalog_public_no_image_url()
                                    : company_url('image/no-image');
                                $editFallbacks = [];
                                $editUuid = function_exists('company_product_image_uuid')
                                    ? company_product_image_uuid($product)
                                    : '';
                                if ($editUuid !== '' && function_exists('fv_list_image_urls')) {
                                    $urls = fv_list_image_urls($editUuid, $product->default_image ?? null);
                                    if (! empty($urls['primary'])) {
                                        $imagen_defecto = $urls['primary'];
                                    }
                                    $editFallbacks = is_array($urls['fallbacks'] ?? null) ? $urls['fallbacks'] : [];
                                }
                                $editFallbackJson = htmlspecialchars(
                                    json_encode(array_values(array_merge($editFallbacks, [$imagen_fallback])), JSON_UNESCAPED_SLASHES),
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>
                                <figure class="fv-product-image__figure" data-toggle="modal" data-target="#visorImagen">
                                    <img id="image_preview"
                                         src="<?= htmlspecialchars((string) $imagen_defecto, ENT_QUOTES, 'UTF-8') ?>"
                                         alt="<?= htmlspecialchars((string) ($pageTexts['default_image'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                         loading="lazy"
                                         decoding="async"
                                         data-fv-img-fallbacks="<?= $editFallbackJson ?>"
                                         onerror="(function(img){var list=[];try{list=JSON.parse(img.getAttribute('data-fv-img-fallbacks')||'[]');}catch(e){}var i=parseInt(img.getAttribute('data-fv-img-i')||'0',10);if(i<list.length){img.setAttribute('data-fv-img-i',String(i+1));img.src=list[i];return;}})(this);">
                                </figure>
                                <button title="<?= htmlspecialchars((string) ($pageTexts['editar_imagen'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        id="agregar_imagen" type="button"
                                        class="fv-product-image__btn" data-toggle="modal" data-target="#modal_imagenes">
                                    <i class="fa-solid fa-edit" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="fv-product-sheet">
                    <div class="fv-product-sheet__grid fv-product-sheet__grid--3col">
                        <div class="fv-product-field">
                            <label><?= $pageTexts['f_fobprice']?></label>
                            <?php $this->load->view('company/product/_product_currency_input', [
                                'name' => 'fob_price',
                                'value' => number_format($product->fob_price, $format_numeric->digits, $format_numeric->radixPoint, $format_numeric->groupSeparator),
                                'currencySymbol' => $currencySymbol,
                            ]); ?>
                        </div>
                        <div class="fv-product-field">
                            <label><?= $pageTexts['f_purchaseprice']?></label>
                            <?php $this->load->view('company/product/_product_currency_input', [
                                'name' => 'purchase_price',
                                'value' => number_format($product->purchase_price, $format_numeric->digits, $format_numeric->radixPoint, $format_numeric->groupSeparator),
                                'currencySymbol' => $currencySymbol,
                            ]); ?>
                        </div>
                        <div class="fv-product-field">
                            <label><?= $pageTexts['price_reduced']?></label>
                            <?php $this->load->view('company/product/_product_currency_input', [
                                'name' => 'price_reduced',
                                'value' => number_format($product->price_reduced, $format_numeric->digits, $format_numeric->radixPoint, $format_numeric->groupSeparator),
                                'currencySymbol' => $currencySymbol,
                            ]); ?>
                        </div>
                    </div>
                </div>

                <div class="fv-product-sheet">
                    <div class="fv-product-sheet__grid fv-product-sheet__grid--3col">
                        <div class="fv-product-col">
                            <div class="fv-product-field">
                                <label><?= $pageTexts['f_unittype']?> <span class="color-red">*</span></label>
                                <select class="form-control" name="unit_type">
                                    <option value="" selected><?= $pageTexts['option_select'] ?></option>
                                    <?php foreach ($unit_type as $unit): ?>
                                    <option value="<?= $unit->unit_id ?>"
                                        data-decimals="<?= (int) (function_exists('fv_unit_decimal_places') ? fv_unit_decimal_places($unit) : 0) ?>"
                                        <?= ($product->unit->unit_id === $unit->unit_id) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars((string) ($unit->unit ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                            <div class="fv-product-field">
                                <label><?= $pageTexts['f_tags']?></label>
                                <input id="tag" type="text" class="form-control taginput" value="<?= htmlspecialchars((string) $product->tags, ENT_QUOTES, 'UTF-8') ?>"
                                    name="tags" data-role="tagsinput">
                                <div class="container_alert"></div>
                            </div>
                        </div>
                        <div class="fv-product-col">
                            <div class="fv-product-field">
                                <label><?= $pageTexts['f_moq']?></label>
                                <input name="minimum_stock" type="text"
                                    class="text-center form-control fv-unit-qty evento-calculo"
                                    value="<?= htmlspecialchars(fv_format_unit_quantity($product->minimum_stock ?? 0, $product, (string) ($format_numeric->radixPoint ?? '.'), (string) ($format_numeric->groupSeparator ?? ',')), ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="fv-product-field">
                                <label><?= $pageTexts['enabled_disabled'] ?> <span class="color-red">*</span></label>
                                <select class="form-control" name="status" required>
                                    <option value="1" <?= ((int) $product->status === 1) ? 'selected' : ''; ?>>
                                        <?= $pageTexts['select-activo-singular'] ?></option>
                                    <option value="0" <?= ((int) $product->status === 0) ? 'selected' : ''; ?>>
                                        <?= $pageTexts['select-desactivo-singular'] ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="fv-product-col">
                            <div class="fv-product-field">
                                <label><?= $pageTexts['f_notimoq']?> <span class="color-red">*</span></label>
                                <select class="form-control" name="notify_minimum_stock">
                                    <option value="1" <?= ($product->notify_minimum_stock == 1)? 'selected' : ''; ?>>
                                        <?= $pageTexts['f_si']?></option>
                                    <option value="0" <?= ($product->notify_minimum_stock == 0)? 'selected' : ''; ?>>No</option>
                                </select>
                            </div>
                            <div class="fv-product-field">
                                <label><?= $pageTexts['f_location']?></label>
                                <input id="location" onkeyup="return validar_campo(event,'#location')" type="text"
                                    class="form-control" value="<?= htmlspecialchars(trim((string) $product->locations), ENT_QUOTES, 'UTF-8') ?>" name="locations">
                                <div class="container_alert"></div>
                            </div>
                        </div>
                    </div>
                    <div class="fv-product-sheet__grid fv-product-sheet__grid--full mt-3">
                        <div class="fv-product-field">
                            <label><?= $pageTexts['f_descrips']?> <span class="color-red">*</span></label>
                            <textarea rows="5" id="descripcion"
                                onkeyup="return validar_campo(event,'#descripcion')" class="form-control"
                                name="descriptions"><?= htmlspecialchars((string) $product->descriptions, ENT_QUOTES, 'UTF-8') ?></textarea>
                            <div class="container_alert"></div>
                        </div>
                    </div>
                </div>

                <?php $this->load->view('company/product/semaforo_stock/semaforo_edit') ?>
            </div>
            <?php $this->load->view('components/crud/form_action_fab', [
                'formId' => 'form_edit',
                'saveLabel' => (string) ($pageTexts['guardar'] ?? $pageTexts['save'] ?? $pageTexts['f_submit'] ?? ''),
                'cancelLabel' => (string) ($pageTexts['f_cancelar'] ?? ''),
                'cancelUseHistory' => true,
                'saveButtonId' => 'boton_enviar_first',
                'saveButtonType' => 'button',
            ]); ?>
            <div class="modal fade fv-product-images-modal" id="modal_imagenes" role="dialog" aria-labelledby="imagenesModalLabel"
                 aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel"><?= htmlspecialchars((string) ($pageTexts['f_images'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <!-- modal content should be loaded here -->
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="fv-product-images-modal__btn fv-product-images-modal__btn--cancel"
                                    data-dismiss="modal"><?= htmlspecialchars((string) ($pageTexts['regresar'] ?? ''), ENT_QUOTES, 'UTF-8') ?></button>
                            <button type="button" id="save_image_order" class="fv-product-images-modal__btn fv-product-images-modal__btn--save">
                                <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                                <?= htmlspecialchars((string) ($pageTexts['save_changes'] ?? $pageTexts['guardar'] ?? $pageTexts['save'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</form>

<!-- Modal Visor de imagen -->
<div class="modal fade" id="visorImagen" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
     aria-hidden="true">
    <?php $this->load->view('company/product/modals/image_preview') ?>
</div>
<?php
$changeSkuImageUrl = function_exists('company_product_image_url')
    ? company_product_image_url($product, 175)
    : company_url('image');
$changeSkuCategoryNames = [];
if (!empty($product->categories) && is_array($product->categories)) {
    foreach ($product->categories as $catRow) {
        $catRow = is_object($catRow) ? $catRow : (object) (array) $catRow;
        $catName = trim((string) ($catRow->category_name ?? ''));
        if ($catName !== '') {
            $changeSkuCategoryNames[] = $catName;
        }
    }
}
$this->load->view('company/product/modals/change_sku', [
    'product' => $product,
    'pageTexts' => $pageTexts,
    'productImageUrl' => $changeSkuImageUrl,
    'productPriceFormatted' => trim(($format_currency ?? '') . ' ' . format_currency($product->sale_price, $product->currency_type)),
    'productCategoriesLabel' => $changeSkuCategoryNames !== [] ? implode(', ', $changeSkuCategoryNames) : '—',
]);
$this->load->view('company/product/modals/category_groups', [
    'pageTexts' => $pageTexts,
]);
?>
<script>
window.fvChangeSkuLang = <?= json_encode([
    'change_sku_required' => $pageTexts['change_sku_required'] ?? '',
    'change_sku_same' => $pageTexts['change_sku_same'] ?? '',
    'change_sku_success' => $pageTexts['change_sku_success'] ?? '',
    'error_sku' => $pageTexts['error_sku'] ?? '',
    'error_occurred' => $pageTexts['error_occurred'] ?? '',
    'ok' => $pageTexts['ok'] ?? '',
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
window.fvImageOrderLang = <?= json_encode([
    'orden_cambiado' => $pageTexts['orden_cambiado'] ?? '',
    'mover_imagenes' => $pageTexts['mover_imagenes'] ?? '',
    'error_occurred' => $pageTexts['error_occurred'] ?? '',
    'ok' => $pageTexts['ok'] ?? '',
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<?php $this->load->view('company/product/price_groups/price_groups_javascript') ?>
<script src="<?= base_url('companyasset/js/sortable.min.js') ?>"></script>

<script>
    function fvRefreshProductMainImage(uuidOrUrl) {
        var mainImg = document.getElementById('image_preview') || document.querySelector('#imagen_principal img');
        if (!mainImg || !uuidOrUrl) {
            return;
        }
        var src = String(uuidOrUrl);
        if (!/^https?:\/\//i.test(src) && src.indexOf('/') === -1) {
            src = '/api/v2/images/' + encodeURIComponent(src);
        }
        src += (src.indexOf('?') === -1 ? '?' : '&') + 'v=' + Date.now();
        mainImg.dataset.err = '';
        mainImg.removeAttribute('data-err');
        mainImg.onerror = function () {
            if (mainImg.dataset.err === '1') {
                return;
            }
            if (mainImg.dataset.retry !== '1' && /_175(\?|$)/.test(src)) {
                mainImg.dataset.retry = '1';
                mainImg.src = src.replace('_175', '');
                return;
            }
            mainImg.dataset.err = '1';
            mainImg.src = mainImg.getAttribute('data-fallback') || '/api/v2/images/no-image';
        };
        mainImg.dataset.retry = '';
        mainImg.src = src;
    }

    function fvUploadProductImageFromInput($fileInput) {
        var fileInputEl = $fileInput && $fileInput[0] ? $fileInput[0] : null;
        var file = fileInputEl && fileInputEl.files ? fileInputEl.files[0] : null;
        if (!file) {
            return;
        }

        var thisFileInput = $fileInput.closest('.fileinput');
        if (!thisFileInput.length) {
            thisFileInput = $fileInput.parent();
        }
        var fileInputButton = thisFileInput.find('.btn-file');
        var fileNameDiv = thisFileInput.find('.fileinput-filename');
        if (thisFileInput.data('fvUploading')) {
            return;
        }
        thisFileInput.data('fvUploading', true);

        var formData = new FormData();
        formData.append('file', file);

        var slot = parseInt($fileInput.attr('data-slot') || thisFileInput.closest('[data-slot]').attr('data-slot') || '0', 10);
        if (slot > 0) {
            formData.append('slot', String(slot));
        }
        var replaceUuid = String(thisFileInput.find('input[name^="images_id"]').val() || '').trim();
        if (replaceUuid) {
            formData.append('replace_uuid', replaceUuid);
        }

        fileNameDiv.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> <?= htmlspecialchars((string) ($pageTexts['subiendo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>').addClass('bg-warning').removeClass('bg-success bg-danger');
        fileInputButton.prop('disabled', true);

        $.ajax({
            url: `${options.url}/products/addImage/<?= (int) $product->pro_id ?>`,
            type: 'post',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (data) {
                if (typeof data === 'string') {
                    try { data = JSON.parse(data); } catch (err) { data = {}; }
                }
                thisFileInput.data('fvUploading', false);
                if (data && data.success) {
                    fileNameDiv.html(data.image_name || '<?= htmlspecialchars((string) ($pageTexts['product_image_upload_success'] ?? ''), ENT_QUOTES, 'UTF-8') ?>').addClass('bg-success').removeClass('bg-warning');
                    var newFileInputButton = thisFileInput.find('.btn-file .fileinput-new');
                    newFileInputButton.addClass('fileinput-exists');
                    newFileInputButton.find('.fa-upload').removeClass('fa-upload').addClass('fa-pen-to-square');
                    newFileInputButton.removeClass('fileinput-new');
                    if (data.uuid) {
                        thisFileInput.find('input[name^="images_id"]').val(data.uuid);
                        thisFileInput.closest('[data-id]').attr('data-id', data.uuid);
                    }
                    var previewSrc = data.preview_url || data.uuid;
                    fvRefreshProductMainImage(previewSrc);
                    var $slotImg = thisFileInput.find('.fv-product-img-slot__preview img, figure.thumbnail img').first();
                    if ($slotImg.length && previewSrc) {
                        var slotSrc = String(previewSrc);
                        if (slotSrc.indexOf('/') === -1) {
                            slotSrc = '/api/v2/images/' + encodeURIComponent(slotSrc);
                        }
                        $slotImg.attr('src', slotSrc + (slotSrc.indexOf('?') === -1 ? '?' : '&') + 'v=' + Date.now());
                    }
                    imagenesModalHash = null;
                    if (typeof updateImagesModal === 'function') {
                        updateImagesModal(<?= (int) $product->pro_id ?>);
                    }
                } else {
                    var failMsg = (data && data.message) ? data.message : '<?= htmlspecialchars((string) ($pageTexts['product_image_upload_error'] ?? $pageTexts['imagen_error'] ?? ''), ENT_QUOTES, 'UTF-8') ?>';
                    fileNameDiv.html(failMsg).addClass('bg-danger').removeClass('bg-warning bg-success');
                    if (typeof swal !== 'undefined') {
                        swal({
                            title: failMsg,
                            icon: 'error',
                            button: { text: '<?= htmlspecialchars((string) ($pageTexts['ok'] ?? ''), ENT_QUOTES, 'UTF-8') ?>', className: 'btn btn-primary' }
                        });
                    }
                }
                fileInputButton.prop('disabled', false);
                console.log('[product-image-upload]', data);
            },
            error: function (xhr, status, error) {
                thisFileInput.data('fvUploading', false);
                var failMsg = '<?= htmlspecialchars((string) ($pageTexts['product_image_upload_error'] ?? $pageTexts['imagen_error'] ?? ''), ENT_QUOTES, 'UTF-8') ?>';
                try {
                    var parsed = xhr.responseJSON || JSON.parse(xhr.responseText || '{}');
                    if (parsed && parsed.message) {
                        failMsg = parsed.message;
                    }
                } catch (e) {}
                fileNameDiv.html(failMsg).addClass('bg-danger').removeClass('bg-warning bg-success');
                fileInputButton.prop('disabled', false);
                if (typeof swal !== 'undefined') {
                    swal({
                        title: failMsg,
                        icon: 'error',
                        button: { text: '<?= htmlspecialchars((string) ($pageTexts['ok'] ?? ''), ENT_QUOTES, 'UTF-8') ?>', className: 'btn btn-primary' }
                    });
                }
                console.error('Error uploading image:', error, xhr && xhr.responseText);
            }
        });
    }

    // Jasny fileinput (cuando está inicializado)
    $('body').on('change.bs.fileinput', '.fileinput', function () {
        var $file = $(this).find('input[type=file]').first();
        fvUploadProductImageFromInput($file);
    });

    // Fallback nativo: el modal se carga por AJAX y a veces Jasny no dispara change.bs.fileinput
    $('body').on('change', '#modal_imagenes input[type=file][name^="images"]', function () {
        fvUploadProductImageFromInput($(this));
    });

    function ordenar_imagenes() {
        //Cambiar el orden
        //Debe mover el orden de las imagenes
        //Refrescar

        let lista_sortable = document.getElementById('lista_sortable')
        // console.log(lista_sortable)
        let producto_id = lista_sortable.getAttribute('data-producto')
        url_peticion = '<?= company_url("products/images/ordenar_imagenes") ?>'

        let producto = {
            id: producto_id,
            orden_solicitado: orden_global
        }

        swal({
            title: '<?= $pageTexts['cambiar_orden'] ?>',
            icon: 'warning',
            buttons: ["No", '<?= $pageTexts['si'] ?>'],
        }).then((willDelete) => {

            if (willDelete) {
                if (orden_global == null || (Array.isArray(orden_global) && orden_global.length === 0)) {
                    $('#modal_imagenes').modal('hide');
                } else {

                    $.ajax({

                        url: url_peticion,
                        type: 'post',
                        dataType: 'json',
                        data: producto,
                        success: function (response) {

                            console.log(response)
                            location.reload()
                        }

                    })
                }


            }


        });

    }

    // Este código se ejecuta solo cuando el modal se carga directamente (no vía AJAX)
    // Para el modal cargado vía AJAX, se usa initImageSortable() en updateImagesModal
    if ($('#lista_sortable').length && $('#lista_sortable').closest('#modal_imagenes').length === 0) {
        let lista_sortable = document.getElementById('lista_sortable');
        if (lista_sortable && typeof Sortable !== 'undefined') {
            imageSortable = Sortable.create(lista_sortable, {
                animation: 150,
                draggable: '.fv-product-images-slot',
                chosenClass: 'sele',
                handle: '.fv-img-drag-handle',
                dataIdAttr: 'data-id',
                filter: '.fv-product-images-slot--empty',
                preventOnFilter: false,
                onEnd: function() {
                    updateImageOrder();
                },
                group: 'lista'
            });
            updateImageOrder();
        }
    }

    function deleteProductImage (imageName, imageId, thisBtn, id_producto) {

        let texto = '<?= $pageTexts['pregunta_eliminarfoto'] ?>'
        let si = '<?= $pageTexts['si'] ?>'

        swal({
            title: texto,
            icon: 'warning',
            buttons: ["No", si],
        }).then((willDelete) => {
            if (willDelete) {
                // Mostrar indicador de carga
                thisBtn.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin"></i>');
                
                $.ajax({
                    url: `${options.url}/products/images/deleteProductImage`,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        imageId: imageId,
                        productImageName: imageName,
                        productId: id_producto,
                    },
                })
                    .done(function(data) {
                        // Verificar si la respuesta es un array (formato antiguo) o un objeto
                        let responseData = Array.isArray(data) ? data : data;
                        let message = Array.isArray(responseData) ? responseData[0] : (responseData.message || 'Imagen eliminada exitosamente');
                        let type = Array.isArray(responseData) ? responseData[1] : (responseData.success ? 'success' : 'error');
                        
                        if (type === 'success' || (Array.isArray(responseData) && responseData[1] === 'success')) {
                            // Mostrar mensaje de éxito
                            if (typeof swal !== 'undefined') {
                                swal({
                                    title: "¡Éxito!",
                                    text: message,
                                    icon: "success",
                                    button: {
                                        text: "OK",
                                        value: true,
                                        visible: true,
                                        className: "btn btn-primary"
                                    }
                                }).then(function() {
                                    // Actualizar el modal de imágenes
                                    updateImagesModal(id_producto);
                                });
                            } else if (typeof alert_func !== 'undefined') {
                                alert_func([message, 'success', '#A5DC86']);
                                updateImagesModal(id_producto);
                            } else {
                                alert(message);
                                updateImagesModal(id_producto);
                            }
                        } else {
                            // Mostrar mensaje de error
                            if (typeof swal !== 'undefined') {
                                swal({
                                    title: "Error",
                                    text: message,
                                    icon: "error",
                                    button: {
                                        text: "OK",
                                        value: true,
                                        visible: true,
                                        className: "btn btn-primary"
                                    }
                                });
                            } else {
                                alert('Error: ' + message);
                            }
                            thisBtn.prop('disabled', false).html('<i class="mdi mdi-delete"></i>');
                        }
                    })
                    .fail(function(xhr, status, error) {
                        console.error('Error al eliminar imagen:', error);
                        let errorMsg = 'Error al eliminar la imagen. Por favor, intente nuevamente.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        
                        if (typeof swal !== 'undefined') {
                            swal({
                                title: "Error",
                                text: errorMsg,
                                icon: "error",
                                button: {
                                    text: "OK",
                                    value: true,
                                    visible: true,
                                    className: "btn btn-primary"
                                }
                            });
                        } else {
                            alert(errorMsg);
                        }
                        thisBtn.prop('disabled', false).html('<i class="mdi mdi-delete"></i>');
                    });
            }

        });
    }
    var imagenesModalHash=null;
    $(document).on('show.bs.modal', '#modal_imagenes', function(e) {
        updateImagesModal(<?= $product->pro_id ?>)
    })

    function updateImagesModal(id_producto) {
        $.ajax({
            url: `${options.url}/products/images/getProductImagesModal/${id_producto}`,
            type: 'get',
            data: {images_hash: imagenesModalHash},
            dataType: 'html',
            beforeSend: function() {
                $('#agregar_imagen').html(
                    '<i class="fa-solid fa-edit"></i><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>'
                )
            }
        }).done( function(response, textStatus, jqXHR) {
            if(jqXHR.status === 200) {
                let $modalImagenesContent = $('#modal_imagenes .modal-body');
                $modalImagenesContent.html(response)
                imagenesModalHash = jqXHR.getResponseHeader('X-Images-Hash')
                
                // Inicializar Sortable después de cargar el contenido del modal
                initImageSortable();
                // Re-inicializar Jasny fileinput en HTML inyectado por AJAX
                if ($.fn.fileinput) {
                    $modalImagenesContent.find('.fileinput').each(function () {
                        var $el = $(this);
                        if (!$el.data('fileinput')) {
                            $el.fileinput();
                        }
                    });
                }
            } else if(jqXHR.status === 204) {
                // No hay cambios, no hacer nada
                console.log('No hay cambios en las imágenes');
            }
            $('#agregar_imagen').html('<i class="fa-solid fa-edit"></i>')
        }).fail(function(xhr, status, error) {
            console.error('Error al cargar el modal de imágenes:', error);
            console.error('Response:', xhr.responseText);
            $('#agregar_imagen').html('<i class="fa-solid fa-edit"></i>')
            
            // Mostrar mensaje de error al usuario
            if (typeof swal !== 'undefined') {
                swal({
                    title: "Error",
                    text: 'Error al cargar las imágenes. Por favor, intente nuevamente.',
                    icon: "error",
                    button: {
                        text: "OK",
                        value: true,
                        visible: true,
                        className: "btn btn-primary"
                    }
                });
            } else {
                alert('Error al cargar las imágenes. Por favor, intente nuevamente.');
            }
        });
    }
    
    // Variable global para almacenar el orden de las imágenes
    var imageOrder = [];
    var imageOrderInitial = [];
    var imageSortable = null;

    function collectImageOrderSnapshot() {
        let lista_sortable = document.getElementById('lista_sortable');
        if (!lista_sortable) {
            return { uuids: [], slots: [] };
        }

        let uuids = [];
        let slots = [];

        lista_sortable.querySelectorAll('.fv-product-images-slot').forEach(function(slot, index) {
            let uuid = slot.getAttribute('data-id') || '';
            let name = slot.getAttribute('data-name-pic') || '';
            if (!name && slot.querySelector('.fv-product-img-slot__filename')) {
                name = (slot.querySelector('.fv-product-img-slot__filename').textContent || '').trim();
            }
            slots.push({
                column: index + 1,
                uuid: uuid,
                name: name,
                empty: slot.classList.contains('fv-product-images-slot--empty')
            });
            if (uuid) {
                uuids.push(uuid);
            }
        });

        if (imageSortable && typeof imageSortable.toArray === 'function') {
            let fromSortable = imageSortable.toArray().filter(function(id) {
                return id && id !== '';
            });
            if (fromSortable.length > 0) {
                uuids = fromSortable;
            }
        }

        return { uuids: uuids, slots: slots };
    }

    function logImageOrderDebug(label, compareInitial) {
        let snapshot = collectImageOrderSnapshot();
        console.group('[Imágenes producto] ' + label);
        console.log('UUIDs (orden):', snapshot.uuids.slice());
        console.log('Detalle por columna:', snapshot.slots);
        if (compareInitial && imageOrderInitial.length) {
            console.log('Orden inicial UUIDs:', imageOrderInitial.slice());
            console.log('¿Cambió respecto al inicial?', JSON.stringify(imageOrderInitial) !== JSON.stringify(snapshot.uuids));
        }
        console.groupEnd();
        return snapshot;
    }
    
    function initImageSortable() {
        let lista_sortable = document.getElementById('lista_sortable');
        if (lista_sortable && typeof Sortable !== 'undefined') {
            if (imageSortable) {
                imageSortable.destroy();
            }

            imageSortable = Sortable.create(lista_sortable, {
                animation: 150,
                draggable: '.fv-product-images-slot',
                chosenClass: 'sele',
                handle: '.fv-img-drag-handle',
                dataIdAttr: 'data-id',
                filter: '.fv-product-images-slot--empty',
                preventOnFilter: false,
                onEnd: function() {
                    updateImageOrder();
                    logImageOrderDebug('Orden tras arrastrar', true);
                }
            });

            updateImageOrder();
            imageOrderInitial = imageOrder.slice();
            logImageOrderDebug('Orden inicial (al abrir/cargar modal)', false);
        }
    }

    function updateImageOrder() {
        let lista_sortable = document.getElementById('lista_sortable');
        if (!lista_sortable) {
            return;
        }

        imageOrder = [];

        if (imageSortable && typeof imageSortable.toArray === 'function') {
            imageOrder = imageSortable.toArray().filter(function(id) {
                return id && id !== '';
            });
        }

        if (imageOrder.length === 0) {
            lista_sortable.querySelectorAll('.fv-product-images-slot').forEach(function(slot) {
                let imageId = slot.getAttribute('data-id');
                if (imageId && imageId !== '') {
                    imageOrder.push(imageId);
                }
            });
        }

        orden_global = imageOrder.slice();
    }

    function buildImageOrderFormData(productoId, uuids) {
        let formData = new FormData();
        formData.append('id', productoId);
        uuids.forEach(function(uuid) {
            formData.append('orden[]', uuid);
        });
        return formData;
    }

    function closeProductImagesModal($btn, originalText) {
        $('#modal_imagenes').modal('hide');
        if ($btn && $btn.length) {
            $btn.prop('disabled', false).html(originalText);
        }
    }

    function showImageOrderSuccessAlert(message, $btn, originalText) {
        if (typeof swal !== 'undefined') {
            swal({
                title: message,
                text: '',
                icon: 'success',
                button: {
                    text: fvImageOrderLang.ok || 'OK',
                    value: true,
                    visible: true,
                    className: 'btn btn-primary'
                }
            }).then(function() {
                closeProductImagesModal($btn, originalText);
            });
            return;
        }

        if (typeof alert_func !== 'undefined') {
            alert_func([message, 'success', '#A5DC86']);
        } else {
            alert(message);
        }
        closeProductImagesModal($btn, originalText);
    }
    
    // Guardar el orden de las imágenes
    $(document).on('click', '#save_image_order', function(e) {
        e.preventDefault();
        
        let lista_sortable = document.getElementById('lista_sortable');
        if (!lista_sortable) {
            alert('No se encontró la lista de imágenes');
            return;
        }
        
        let producto_id = lista_sortable.getAttribute('data-producto');
        if (!producto_id) {
            alert('No se encontró el ID del producto');
            return;
        }
        
        // Actualizar el orden antes de enviar (asegurarse de tener el orden más reciente)
        updateImageOrder();
        let saveSnapshot = logImageOrderDebug('Orden al guardar', true);
        imageOrder = saveSnapshot.uuids.slice();
        
        let $btn = $(this);
        let originalText = $btn.html();

        // Sin imágenes aún: cerrar (la subida se guarda al seleccionar el archivo).
        if (imageOrder.length === 0) {
            closeProductImagesModal($btn, originalText);
            return;
        }

        // Orden igual al actual: no hay nada que reordenar; cerrar sin falso error.
        // (La carga/edición de cada imagen ya persiste por AJAX al elegir el archivo.)
        if (JSON.stringify(imageOrderInitial || []) === JSON.stringify(imageOrder || [])) {
            closeProductImagesModal($btn, originalText);
            return;
        }
        
        // Datos a enviar
        let formData = buildImageOrderFormData(producto_id, imageOrder);

        // Mostrar indicador de carga
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status"></span>');

        $.ajax({
            url: `${options.url}/products/images/orderImages`,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                console.log('[Imágenes producto] Respuesta guardar orden:', response);
                if (response.success) {
                    imagenesModalHash = null;
                    imageOrderInitial = imageOrder.slice();
                    let successMsg = (response.message && String(response.message).trim())
                        ? response.message
                        : (fvImageOrderLang.orden_cambiado || '');
                    showImageOrderSuccessAlert(successMsg, $btn, originalText);
                } else {
                    // Mostrar mensaje de error
                    console.error('Error en la respuesta:', response);
                    let errorMsg = response.message || fvImageOrderLang.error_occurred || '';
                    if (typeof alert_func !== 'undefined') {
                        alert_func([errorMsg, 'error', '#DD6B55']);
                    } else if (typeof swal !== 'undefined') {
                        swal({
                            title: errorMsg,
                            text: '',
                            icon: 'error',
                            button: {
                                text: fvImageOrderLang.ok || 'OK',
                                value: true,
                                visible: true,
                                className: 'btn btn-primary'
                            }
                        });
                    } else {
                        alert(errorMsg);
                    }
                    $btn.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr, status, error) {
                console.error('========================================');
                console.error('ERROR AL GUARDAR ORDEN');
                console.error('========================================');
                console.error('Status:', status);
                console.error('Error:', error);
                console.error('Response Text:', xhr.responseText);
                console.error('Status Code:', xhr.status);
                console.error('========================================');
                
                let errorMsg = fvImageOrderLang.error_occurred || '';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                
                if (typeof alert_func !== 'undefined') {
                    alert_func([errorMsg, 'error', '#DD6B55']);
                } else if (typeof swal !== 'undefined') {
                    swal({
                        title: errorMsg,
                        text: '',
                        icon: 'error',
                        button: {
                            text: fvImageOrderLang.ok || 'OK',
                            value: true,
                            visible: true,
                            className: 'btn btn-primary'
                        }
                    });
                } else {
                    alert(errorMsg);
                }
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });
</script>

<script type="text/javascript">
var orden_global = null

const _groupSeparator = "<?= $format_numeric->groupSeparator ?>"
const _radixPoint = "<?= $format_numeric->radixPoint ?>"
const _digits = "<?= $format_numeric->digits ?>"
<?php $this->load->view('company/product/_unit_qty_mask_script'); ?>

$(document).on('click', '#boton_enviar_first', function(e) {

    let form = document.getElementById('form_edit')
    console.log(form)
    let formData = new FormData(form)
    $.each(orden_global, function(index, el) {
        formData.append(`orden_solicitado[${index}]`, el)
    });
    update(formData, 'lista')

})

$(document).on('click', '#boton_enviar_second', function(e) {

    $('#cambio_orden').val('no')
    let form = document.getElementById('form_edit')
    let formData = new FormData(form)

    $.each(orden_global, function(index, el) {

        formData.append(`orden_solicitado[${index}]`, el)

    });
    update(formData, 'edit')

})

function update(formData, redirect) {

    let url = '<?= company_url('product/update') ?>'
    let form = document.getElementById('form_edit')

    $.ajax({
        url: form.getAttribute('action'),
        type: 'post',
        dataType: 'json',
        data: formData,
        contentType: false,
        processData: false,
        beforeSend: function() {
            $('#boton_enviar_second').prop('disabled', true)
            $('#boton_enviar_first').prop('disabled', true)
        },
        success: function(response) {
            $('#boton_enviar_second').prop('disabled', false)
            $('#boton_enviar_first').prop('disabled', false)

            if (!response.success) {
                var title = response.message || '<?= isset($pageTexts["error_occurred"]) ? addslashes($pageTexts["error_occurred"]) : "Error" ?>'
                var errors = response.errors || {}
                var detail = Object.values(errors)
                    .reduce(function(acc, curr) {
                        return acc.concat(Array.isArray(curr) ? curr : [curr])
                    }, [])
                    .join('\n')
                if (!detail) {
                    detail = (response.msg || '').replace(/<[^>]+>/g, '').trim()
                }
                if (typeof swal !== 'undefined') {
                    swal({ title: title, text: detail || undefined, icon: 'error' })
                } else if (typeof alert_func !== 'undefined') {
                    alert_func([detail || title, 'error'])
                } else {
                    alert(detail || title)
                }
                return
            }
            const successMsg = response.message || '<?= isset($pageTexts["success"]) ? addslashes($pageTexts["success"]) : "Record saved successfully." ?>'
            const redirectUrl = response.urlredirect || '<?= company_url('products') ?>'
            if (typeof swal !== 'undefined') {
                swal({ title: successMsg, icon: 'success' }).then(function() {
                    if (redirect == "lista") {
                        window.location.href = redirectUrl
                    } else {
                        location.reload()
                    }
                })
            } else {
                if (typeof alert_func !== 'undefined') {
                    alert_func([successMsg, 'success', '#A5DC86'])
                }
                if (redirect == "lista") {
                    window.location.href = redirectUrl
                } else {
                    location.reload()
                }
            }
        }

    })
}

$(document).ready(function() {

    //$(".mask").maskMoney({thousands:_groupSeparator, decimal:_radixPoint,allowZero:true});

    // Esperar a que Inputmask esté disponible
    function initInputmask() {
        if (typeof Inputmask !== 'undefined') {
            Inputmask("currency", {
                radixPoint: _radixPoint,
                groupSeparator: _groupSeparator,
                digits: _digits
            }).mask(".mask");
            if (typeof window.fvApplyUnitQtyMask === 'function') {
                window.fvApplyUnitQtyMask();
            }
        } else {
            // Reintentar después de un breve delay
            setTimeout(initInputmask, 100);
        }
    }
    
    initInputmask();

    // Categorías: multiselect. Select2 se carga en footer (CoreLoader async); esperar a que esté disponible.
    function initCategoryMultiselect() {
        var $el = $('#form_edit #category');
        if (!$el.length || typeof $el.select2 !== 'function') return false;
        try { $el.select2('destroy'); } catch (e) {}
        $el.select2({
            placeholder: '<?= isset($pageTexts['option_select']) ? addslashes($pageTexts['option_select']) : 'Seleccione una o más categorías' ?>',
            allowClear: true,
            width: '100%'
        });
        return true;
    }
    if (!initCategoryMultiselect()) {
        var attempts = 0, maxAttempts = 50;
        var iv = setInterval(function() {
            if (initCategoryMultiselect() || ++attempts >= maxAttempts) clearInterval(iv);
        }, 100);
    }

    $('#image_preview').on('click', function(e) {
        $('#visorImagen').modal('show')
    })
    //formatCurrency($('.currency'), "blur",grupo, groupSeparator, separatorDecimal)
    $('#visorImagen').on('show.bs.modal', function(e) {

        var button = $(e.relatedTarget) // Button that triggered the modal
        let imagen = button.find('img').attr('src')
        let modal_imagen = $(this).find('img')
        modal_imagen.attr('src', imagen)

    })

    // popover de imagenes
    var popoverImgTpl = '<div class="popover fv-popover-img-preview" role="tooltip"><div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>';
    $('[data-toggle="popover-hover"]').popover({
        html: true,
        trigger: 'hover',
        placement: 'left',
        template: popoverImgTpl,
        content: function() {
            return '<img src="' + $(this).data('img') + '" class="img-thumbnail" style="max-width: 100%; width: 360px; height: auto;" />';
        }
    });

    let tipo_moneda = $('#currency_type').find(":selected").text() + '  '

    //$("#minimum_stock").maskMoney();
    $(".taginput").tagsinput('items');

    $('.taginput').on('beforeItemAdd', function(event) {
        let nombre = event.item
        if (nombre.indexOf(',') > -1 || nombre.indexOf(';') > -1) {

            $('#tag').next().html(
                '<div class="error_msgbox show_msgbox">No se permiten caracteres " , "  y  " ; ".</div>'
            )
            setTimeout(() => {
                $('#tag').next().html('')
            }, "2000")
            event.cancel(true)

        }

    })

    $('.bootstrap-tagsinput input').on('click keyup', function(e) {

        let nombre = $(this).val()

        if (nombre.indexOf(',') > -1 || nombre.indexOf(';') > -1) {

            nombre = nombre.replace(",", "");
            nombre = nombre.replace(";", "");
            $(this).val(nombre)
            $('#tag').next().html(
                '<div class="error_msgbox show_msgbox">No se permiten caracteres " , "  y  " ; ".</div>'
            )
            setTimeout(() => {
                $('#tag').next().html('')
            }, "2000")

        }
    })

});

$(document).on('keyup', ".currency", function(e) {

    if (e.keyCode != 9) {

        let num = $(this).val().length;
        $(this)[0].setSelectionRange(num, num);
    }

})
</script>
