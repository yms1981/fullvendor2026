<?php

/**
 * @var $company object the current company information
 * @var $pageTexts array the translated string conglomerated for this view
 * @var $unit_type array the available units of the company
 * @var $format_numeric object the format numeric of the company or the default if company doesn't have one configured
 * @var array $categorylist The list of categories of the company (for the select options)
 * @var array $currencyList The list of currencies of the company (for the select options)
 * @var array $groups The customer groups/ price lists available for the company
 * @var object $product The product data
 */
    $currencySymbol = $format_currency ?? '';
?>

<form accept-charset="UTF-8" id="form_view" class="forms-sample fv-product-form-page fv-product-form-page--view" data-product-id="<?= $product->pro_id?>">
    <?php $this->load->view('company/product/_product_form_styles'); ?>
    <div class="container-fluid page-body-wrapper pt-3">
        <div class="main-panel">
            <div class="container-fluid">
                <div class="page-header mb-3">
                    <h3 class="page-title"><?= htmlspecialchars(strtoupper((string) ($pageTexts['ventana_vista_producto'] ?? '')), ENT_QUOTES, 'UTF-8') ?></h3>
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
                                <input id="nombre" class="form-control" type="text" value="<?= htmlspecialchars((string) $product->name, ENT_QUOTES, 'UTF-8') ?>" name="name" readonly>
                            </div>
                            <div class="fv-product-field">
                                <label><?= $pageTexts['f_categlist']?> <span class="color-red">*</span></label>
                                <?php
                                $cats = [];
                                if (!empty($product->categories) && is_array($product->categories)) {
                                    $cats = array_filter(array_map(function ($cat) {
                                        return is_object($cat) ? ($cat->cat_id ?? null) : ($cat['cat_id'] ?? null);
                                    }, $product->categories));
                                }
                                ?>
                                <select class="form-control w-100 product-view-category-multi"
                                    data-info="<?= htmlspecialchars(json_encode($product), ENT_QUOTES, 'UTF-8') ?>" name="category_id[]" id="category"
                                    multiple="multiple" disabled>
                                    <?php if (!empty($categorylist) && count($categorylist) > 0): ?>
                                    <?php foreach ($categorylist as $key => $vc): ?>
                                    <?php $str_flag = in_array($vc->cat_id, $cats) ? 'selected' : ''; ?>
                                    <option value="<?= (int)$vc->cat_id ?>" <?= $str_flag ?>>
                                        <?= htmlspecialchars($vc->category_name ?? '', ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                    <?php endforeach ?>
                                    <?php endif ?>
                                </select>
                            </div>
                            <div class="fv-product-field">
                                <label><?= $pageTexts['f_sku']?> <span class="color-red">*</span></label>
                                <input id="sku" type="text"
                                    class="form-control" value="<?= htmlspecialchars((string) $product->sku, ENT_QUOTES, 'UTF-8') ?>" name="sku" readonly>
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
                                    'readonly' => true,
                                ]); ?>
                            </div>
                            <div class="fv-product-field">
                                <label><?= $pageTexts['f_barcode']?></label>
                                <input id="barcode" type="text"
                                    class="form-control" value="<?= htmlspecialchars((string) $product->barcode, ENT_QUOTES, 'UTF-8') ?>" name="barcode" readonly>
                            </div>
                            <div class="fv-product-field">
                                <label><?= $pageTexts['f_stock']?></label>
                                <input class="form-control fv-unit-qty text-left" type="text" name="stock"
                                    value="<?= htmlspecialchars(fv_format_unit_quantity($product->stock ?? 0, $product, (string) ($format_numeric->radixPoint ?? '.'), (string) ($format_numeric->groupSeparator ?? ',')), ENT_QUOTES, 'UTF-8') ?>" readonly>
                            </div>
                        </div>

                        <div class="fv-product-col fv-product-col--groups">
                            <?php $this->load->view('company/product/_product_price_groups_panel', [
                                'pageTexts' => $pageTexts,
                                'groups' => $groups ?? [],
                                'product' => $product ?? null,
                                'format_currency' => $format_currency ?? '',
                            ]); ?>
                        </div>

                        <div class="fv-product-col fv-product-col--image">
                            <div class="fv-product-image" id="imagen_principal">
                                <?php
                                // Sin forzar escalado _90/_175: estático → API (company_product_image_url).
                                $imagen_defecto = function_exists('company_product_image_url')
                                    ? company_product_image_url($product, 0)
                                    : company_url('image');
                                $imagen_preview = $imagen_defecto;
                                $imagen_fallbacks = [];
                                $previewUuid = function_exists('company_product_image_uuid')
                                    ? company_product_image_uuid($product)
                                    : '';
                                if ($previewUuid !== '' && function_exists('fv_list_image_urls')) {
                                    $record = $product->default_image ?? null;
                                    $urls = fv_list_image_urls($previewUuid, $record);
                                    $imagen_defecto = (string) ($urls['primary'] ?? $imagen_defecto);
                                    $imagen_preview = $imagen_defecto;
                                    $imagen_fallbacks = is_array($urls['fallbacks'] ?? null) ? $urls['fallbacks'] : [];
                                }
                                $viewFallbackJson = htmlspecialchars(
                                    json_encode(array_values($imagen_fallbacks), JSON_UNESCAPED_SLASHES),
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>
                                <figure class="fv-product-image__figure" data-toggle="modal" data-target="#visorImagen"
                                    data-preview="<?= htmlspecialchars((string) $imagen_preview, ENT_QUOTES, 'UTF-8') ?>">
                                    <img id="image_preview"
                                         src="<?= htmlspecialchars((string) $imagen_defecto, ENT_QUOTES, 'UTF-8') ?>"
                                         alt="<?= htmlspecialchars((string) ($pageTexts['default_image'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                         loading="lazy"
                                         decoding="async"
                                         data-fv-img-fallbacks="<?= $viewFallbackJson ?>"
                                         onerror="(function(img){var list=[];try{list=JSON.parse(img.getAttribute('data-fv-img-fallbacks')||'[]');}catch(e){}var i=parseInt(img.getAttribute('data-fv-img-i')||'0',10);if(i<list.length){img.setAttribute('data-fv-img-i',String(i+1));img.src=list[i];return;}img.onerror=null;img.src='<?= htmlspecialchars(catalog_public_no_image_url(), ENT_QUOTES, 'UTF-8') ?>';})(this);">
                                </figure>
                                <button title="<?= htmlspecialchars((string) ($pageTexts['ver_imagenes'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        id="agregar_imagen" type="button"
                                        class="fv-product-image__btn" data-toggle="modal" data-target="#modal_imagenes">
                                    <i class="fa-solid fa-eye" aria-hidden="true"></i>
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
                                'readonly' => true,
                            ]); ?>
                        </div>
                        <div class="fv-product-field">
                            <label><?= $pageTexts['f_purchaseprice']?></label>
                            <?php $this->load->view('company/product/_product_currency_input', [
                                'name' => 'purchase_price',
                                'value' => number_format($product->purchase_price, $format_numeric->digits, $format_numeric->radixPoint, $format_numeric->groupSeparator),
                                'currencySymbol' => $currencySymbol,
                                'readonly' => true,
                            ]); ?>
                        </div>
                        <div class="fv-product-field">
                            <label><?= $pageTexts['price_reduced']?></label>
                            <?php $this->load->view('company/product/_product_currency_input', [
                                'name' => 'price_reduced',
                                'value' => number_format($product->price_reduced, $format_numeric->digits, $format_numeric->radixPoint, $format_numeric->groupSeparator),
                                'currencySymbol' => $currencySymbol,
                                'readonly' => true,
                            ]); ?>
                        </div>
                    </div>
                </div>

                <div class="fv-product-sheet">
                    <div class="fv-product-sheet__grid fv-product-sheet__grid--3col">
                        <div class="fv-product-col">
                            <div class="fv-product-field">
                                <label><?= $pageTexts['f_unittype']?> <span class="color-red">*</span></label>
                                <select class="form-control" name="unit_type" disabled>
                                    <option value="" selected><?= $pageTexts['option_select'] ?></option>
                                    <?php foreach ($unit_type as $unit): ?>
                                    <option value="<?= $unit->unit_id ?>"
                                        data-decimals="<?= (int) (function_exists('fv_unit_decimal_places') ? fv_unit_decimal_places($unit) : 0) ?>"
                                        <?= (isset($product->unit) && $product->unit->unit_id === $unit->unit_id) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars((string) ($unit->unit ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                            <div class="fv-product-field">
                                <label><?= $pageTexts['f_tags']?></label>
                                <input id="tag" type="text" class="form-control taginput" value="<?= htmlspecialchars((string) $product->tags, ENT_QUOTES, 'UTF-8') ?>"
                                    name="tags" data-role="tagsinput" readonly>
                            </div>
                        </div>
                        <div class="fv-product-col">
                            <div class="fv-product-field">
                                <label><?= $pageTexts['f_moq']?></label>
                                <input name="minimum_stock" type="text"
                                    class="text-center form-control fv-unit-qty evento-calculo"
                                    value="<?= htmlspecialchars(fv_format_unit_quantity($product->minimum_stock ?? 0, $product, (string) ($format_numeric->radixPoint ?? '.'), (string) ($format_numeric->groupSeparator ?? ',')), ENT_QUOTES, 'UTF-8') ?>" readonly>
                            </div>
                            <div class="fv-product-field">
                                <label><?= $pageTexts['enabled_disabled'] ?> <span class="color-red">*</span></label>
                                <select class="form-control" name="status" disabled>
                                    <option value=""><?= $pageTexts['option_select'] ?></option>
                                    <option value="1" <?= ($product->status == 1)? 'selected' : ''; ?>>
                                        <?= $pageTexts['select-activo-singular'] ?></option>
                                    <option value="0" <?= ($product->status == 0)? 'selected' : ''; ?>>
                                        <?= $pageTexts['select-desactivo-singular'] ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="fv-product-col">
                            <div class="fv-product-field">
                                <label><?= $pageTexts['f_notimoq']?> <span class="color-red">*</span></label>
                                <select class="form-control" name="notify_minimum_stock" disabled>
                                    <option value="1" <?= ($product->notify_minimum_stock == 1)? 'selected' : ''; ?>>
                                        <?= $pageTexts['f_si']?></option>
                                    <option value="0" <?= ($product->notify_minimum_stock == 0)? 'selected' : ''; ?>>No</option>
                                </select>
                            </div>
                            <div class="fv-product-field">
                                <label><?= $pageTexts['f_location']?></label>
                                <input id="location" type="text"
                                    class="form-control" value="<?= htmlspecialchars(trim((string) $product->locations), ENT_QUOTES, 'UTF-8') ?>" name="locations" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="fv-product-sheet__grid fv-product-sheet__grid--full mt-3">
                        <div class="fv-product-field">
                            <label><?= $pageTexts['f_descrips']?> <span class="color-red">*</span></label>
                            <textarea rows="5" id="descripcion" class="form-control"
                                name="descriptions" readonly><?= htmlspecialchars((string) $product->descriptions, ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                    </div>
                </div>

                <section class="fv-product-sheet mb-2" id="stock_traffic_light"<?php if($product->show_semaforo_stock == 0 && isset($show_leyend)): ?> style="display: none"<?php endif; ?> >
                    <div class="card-header">
                        <h6><?= $pageTexts['stock_traffic_light'] ?></h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-4">
                                <div class="form-group">
                                    <div class="d-flex">
                                        <label for="">
                                            <div class="cuadro" style="background-color: #CF1C00;"></div>
                                        </label>
                                        <h6 class="text-bold">&nbsp; <?= $pageTexts['stock_traffic_light_red'] ?> </h6>
                                    </div>
                                    <input type="number" class="form-control text-center" name="sem_red_hasta" value="<?= $product->sem_red_hasta ?>" readonly>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-group">
                                    <div class="d-flex">
                                        <label for="">
                                            <div class="cuadro" style="background-color: #ffc107;"></div>
                                        </label>
                                        <h6 class="text-bold">&nbsp; <?= $pageTexts['stock_traffic_light_yellow'] ?> </h6>
                                    </div>
                                    <div class="row">
                                        <div class="col-6">
                                            <input readonly tabindex="-1" type="number" class="form-control text-center" value="<?= $product->sem_amarillo_desde ?>" name="sem_amarillo_desde">
                                        </div>
                                        <div class="col-6">
                                            <input type="number" class="form-control text-center" value="<?= $product->sem_amarillo_hasta ?>" name="sem_amarillo_hasta" readonly>
                                            <div class="invalid-feedback"><?= $pageTexts['validation_semaforo'] ?>.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-group">
                                    <div class="d-flex">
                                        <label for="">
                                            <div class="cuadro" style="background-color: #29b768;"></div>
                                        </label>
                                        <h6 class="text-bold">&nbsp; <?= $pageTexts['stock_traffic_light_green'] ?> </h6>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <input readonly tabindex="-1" type="number" class="form-control text-center" name="sem_verde_desde" value="<?= $product->sem_verde_desde ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if(@$show_leyend): ?>
                                <?php $this->load->view("company/profile/leyenda-semaforo"); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
            </div>
            <?php $this->load->view('components/crud/form_action_fab', [
                'formId' => 'form_view',
                'saveLabel' => (string) ($pageTexts['f_editar'] ?? ''),
                'saveUrl' => company_url('products/edit/'.$product->pro_id),
                'saveAsLink' => true,
                'saveIconClass' => 'fa-solid fa-pen-to-square',
                'saveButtonExtraClass' => 'fv-crud-form-fab__btn--edit',
                'cancelLabel' => (string) ($pageTexts['f_atras'] ?? ''),
                'cancelUseHistory' => true,
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
<?php $this->load->view('company/product/price_groups/price_groups_javascript') ?>
<script src="<?= base_url('companyasset/js/sortable.min.js') ?>"></script>

<script>
    // Funciones de edición removidas - esta es una vista de solo lectura
    // Solo mantenemos la función para actualizar el modal de imágenes (solo visualización)
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
                    '<i class="fa-solid fa-eye"></i><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>'
                )
            }
        }).done( function(response, textStatus, jqXHR) {
            if(jqXHR.status === 200) {
                let $modalImagenesContent = $('#modal_imagenes .modal-body');
                $modalImagenesContent.html(response)
                imagenesModalHash = jqXHR.getResponseHeader('X-Images-Hash')
            }
            $('#agregar_imagen').html('<i class="fa-solid fa-eye"></i>')
        });
    }
</script>

<script type="text/javascript">
var orden_global = null

const _groupSeparator = "<?= $format_numeric->groupSeparator ?>"
const _radixPoint = "<?= $format_numeric->radixPoint ?>"
const _digits = "<?= $format_numeric->digits ?>"
<?php $this->load->view('company/product/_unit_qty_mask_script'); ?>

// Funciones de edición removidas - esta es una vista de solo lectura

$(document).ready(function() {

    //$(".mask").maskMoney({thousands:_groupSeparator, decimal:_radixPoint,allowZero:true});

    Inputmask("currency", {
        radixPoint: _radixPoint,
        groupSeparator: _groupSeparator,
        digits: _digits
    }).mask(".mask");
    if (typeof window.fvApplyUnitQtyMask === 'function') {
        window.fvApplyUnitQtyMask();
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
    // Inicializar tagsinput en modo readonly
    $(".taginput").tagsinput({
        readonly: true
    });
    $(".taginput").tagsinput('items');

    // Category list: multiselect solo lectura (misma UX que edit/add, con Select2)
    function initViewCategoryMultiselect() {
        var $el = $('#form_view #category');
        if (!$el.length || typeof $el.select2 !== 'function') return false;
        try { $el.select2('destroy'); } catch (e) {}
        $el.select2({
            placeholder: '<?= isset($pageTexts['option_select']) ? addslashes($pageTexts['option_select']) : 'Seleccione una o más categorías' ?>',
            allowClear: true,
            width: '100%'
        });
        return true;
    }
    if (!initViewCategoryMultiselect()) {
        var attempts = 0, maxAttempts = 50;
        var iv = setInterval(function() {
            if (initViewCategoryMultiselect() || ++attempts >= maxAttempts) clearInterval(iv);
        }, 100);
    }

});

$(document).on('keyup', ".currency", function(e) {

    if (e.keyCode != 9) {

        let num = $(this).val().length;
        $(this)[0].setSelectionRange(num, num);
    }

})
</script>
