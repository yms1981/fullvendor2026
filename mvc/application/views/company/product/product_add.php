<?php

/**
 * @var $pageTexts array the translated string conglomerated for this view
 * @var $unit_type array the available units of the company
 * @var $format_numeric object the format numeric of the company or the default if company doesn't have one configured
 * @var array $categorylist The list of categories of the company (for the select options)
 * @var array $groups The customer groups/ price lists available for the company
 */


$ahora = DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''));
$v_imagen = $ahora->format("YmdHisu");
?>

<form id="form_add" class="forms-sample fv-product-form-page" action="<?= company_url('products/save') ?>" method="post" enctype="multipart/form-data">
    <?php $this->load->view('company/product/_product_form_styles'); ?>
    <?php $currencySymbol = $pageTexts['currencySymbol'] ?? ($pageTexts['currrencySymbol'] ?? ''); ?>
    <div class="container-fluid page-body-wrapper pt-3">
        <div class="main-panel">
            <div class="container-fluid">
                <div class="page-header mb-3">
                    <h3 class="page-title"><?= htmlspecialchars(strtoupper((string) ($pageTexts['ventana_add_producto'] ?? '')), ENT_QUOTES, 'UTF-8') ?></h3>
                </div>

                <div class="fv-product-sheet">
                    <div class="fv-product-sheet__grid fv-product-sheet__grid--4col">
                        <div class="fv-product-col">
                            <div class="fv-product-field">
                                <label><?= $pageTexts['f_prodname']?> <span class="color-red">*</span></label>
                                <input id="nombre" onkeyup="return validar_campo(event,'#nombre')"
                                    class="form-control" type="text" name="name">
                                <div class="container_alert"></div>
                            </div>
                            <div class="fv-product-field">
                                <label><?= $pageTexts['f_categlist']?> <span class="color-red">*</span></label>
                                <select class="form-control w-100 product-add-category-multi" name="category_id[]" id="category"
                                    multiple="multiple">
                                    <?php if (count($categorylist) > 0): ?>
                                    <?php foreach ($categorylist as $key => $v): ?>
                                    <option value="<?= $v->cat_id ?>">
                                        <?= htmlspecialchars((string) ($v->category_name ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                    <?php endforeach ?>
                                    <?php endif ?>
                                </select>
                            </div>
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
                                    <div class="text-muted small" id="fv_cat_groups_summary"
                                         data-none="<?= htmlspecialchars((string) ($pageTexts['category_groups_none'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars((string) ($pageTexts['category_groups_none'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                </div>
                                <div id="fv_cat_groups_hidden_inputs"></div>
                            </div>
                            <div class="fv-product-field">
                                <label><?= $pageTexts['f_sku']?> <span class="color-red">*</span></label>
                                <input id="sku" onkeyup="return validar_campo(event,'#sku')" type="text"
                                    class="form-control" name="sku">
                                <div class="container_alert"></div>
                            </div>
                        </div>

                        <div class="fv-product-col">
                            <div class="fv-product-field">
                                <label><?= $pageTexts['f_saleprice']?> <span class="color-red">*</span></label>
                                <?php $this->load->view('company/product/_product_currency_input', [
                                    'name' => 'sale_price',
                                    'id' => 'precio-ajax',
                                    'currencySymbol' => $currencySymbol,
                                    'placeholder' => '0',
                                ]); ?>
                            </div>
                            <div class="fv-product-field">
                                <label><?= $pageTexts['f_barcode']?></label>
                                <input id="barcode" onkeyup="return validar_campo(event,'#barcode')"
                                    value="<?= (isset($session_barcode))?$session_barcode:'' ?>" type="text"
                                    class="form-control" name="barcode">
                                <div class="container_alert"></div>
                            </div>
                            <div class="fv-product-field">
                                <label><?= $pageTexts['f_stock']?></label>
                                <input class="form-control fv-unit-qty text-left" type="text" name="stock" placeholder="0" value="0">
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
                            <div class="fv-product-image">
                                <figure class="fv-product-image__figure">
                                    <img src="<?= base_url('uploads/noimg.png?v=' . $v_imagen) ?>" alt="">
                                </figure>
                                <button title="<?= htmlspecialchars((string) ($pageTexts['toltip_subir'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        type="button" class="fv-product-image__btn"
                                        data-toggle="modal" data-target="#modal_imagenes">
                                    <i class="fa-solid fa-edit" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="fv-product-sheet">
                    <div class="fv-product-sheet__grid fv-product-sheet__grid--3col">
                        <div class="fv-product-field">
                            <label><?= $pageTexts['f_fobprice']?> <span class="color-red">*</span></label>
                            <?php $this->load->view('company/product/_product_currency_input', [
                                'name' => 'fob_price',
                                'currencySymbol' => $currencySymbol,
                                'placeholder' => '0',
                            ]); ?>
                        </div>
                        <div class="fv-product-field">
                            <label><?= $pageTexts['f_purchaseprice']?></label>
                            <?php $this->load->view('company/product/_product_currency_input', [
                                'name' => 'purchase_price',
                                'currencySymbol' => $currencySymbol,
                                'placeholder' => '0',
                            ]); ?>
                        </div>
                        <div class="fv-product-field">
                            <label><?= $pageTexts['price_reduced']?></label>
                            <?php $this->load->view('company/product/_product_currency_input', [
                                'name' => 'price_reduced',
                                'currencySymbol' => $currencySymbol,
                                'placeholder' => '0',
                            ]); ?>
                        </div>
                    </div>
                </div>

                <div class="fv-product-sheet">
                    <div class="fv-product-sheet__grid fv-product-sheet__grid--3col">
                        <div class="fv-product-col">
                            <div class="fv-product-field">
                                <label><?= $pageTexts['f_unittype']?> <span class="color-red">*</span></label>
                                <select class="form-control notranslate" name="unit_type">
                                    <?php if (isset($session_unit_type)) { ?>
                                    <option value="" selected><?= $pageTexts['option_select'] ?></option>
                                    <?php foreach ($unit_type as $unit): ?>
                                    <option <?= ((string) $unit->unit_id === (string) $session_unit_type || (string) $unit->unit === (string) $session_unit_type) ? 'selected':'' ?>
                                        data-decimals="<?= (int) (function_exists('fv_unit_decimal_places') ? fv_unit_decimal_places($unit) : 0) ?>"
                                        value="<?= $unit->unit_id ?>"><?= htmlspecialchars((string) ($unit->unit ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach ?>
                                    <?php } else { ?>
                                    <option value="" selected><?= $pageTexts['option_select'] ?></option>
                                    <?php foreach ($unit_type as $unit): ?>
                                    <option <?= ($unit->default == 1)? 'selected':'' ?>
                                        data-decimals="<?= (int) (function_exists('fv_unit_decimal_places') ? fv_unit_decimal_places($unit) : 0) ?>"
                                        value="<?= $unit->unit_id ?>">
                                        <?= htmlspecialchars((string) ($unit->unit ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach ?>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="fv-product-field">
                                <label><?= $pageTexts['f_tags']?></label>
                                <input id="tag" type="text" class="form-control taginput"
                                    value="<?= (isset($session_tags))?$session_tags:''?>" name="tags"
                                    data-role="tagsinput">
                                <div class="container_alert"></div>
                            </div>
                        </div>
                        <div class="fv-product-col">
                            <div class="fv-product-field">
                                <label><?= $pageTexts['f_moq']?></label>
                                <input name="minimum_stock" type="text"
                                    class="text-center form-control fv-unit-qty evento-calculo"
                                    value="<?= htmlspecialchars((string) ($this->session->flashdata('minimum_stock') ?: '0'), ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="fv-product-field">
                                <label><?= $pageTexts['enabled_disabled'] ?> <span class="color-red">*</span></label>
                                <?php
                                $statusValue = '';
                                if (isset($session_status)) {
                                    $statusValue = ($session_status === 'si') ? '1' : (($session_status === 'no') ? '0' : '');
                                } else {
                                    $statusValue = '1';
                                }
                                ?>
                                <select class="form-control" name="status">
                                    <option value=""><?= $pageTexts['option_select'] ?></option>
                                    <option value="1" <?= ($statusValue === '1') ? 'selected' : '' ?>>
                                        <?= $pageTexts['select-activo-singular'] ?></option>
                                    <option value="0" <?= ($statusValue === '0') ? 'selected' : '' ?>>
                                        <?= $pageTexts['select-desactivo-singular'] ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="fv-product-col">
                            <div class="fv-product-field">
                                <label><?= $pageTexts['f_notimoq']?> <span class="color-red">*</span></label>
                                <select class="form-control notranslate" name="notify_minimum_stock">
                                    <option value="1"
                                        <?= isset($session_notify_minimum_stock)? ($session_notify_minimum_stock=='si')?'selected':'':''  ?>>
                                        <?= $pageTexts['f_si']?></option>
                                    <option selected value="0"
                                        <?= isset($session_notify_minimum_stock)? ($session_notify_minimum_stock=='no')?'selected':'':''  ?>>
                                        No</option>
                                </select>
                            </div>
                            <div class="fv-product-field">
                                <label><?= $pageTexts['f_location']?></label>
                                <input id="location" onkeyup="return validar_campo(event,'#location')" type="text"
                                    class="form-control" value="<?= $this->session->flashdata('locations') ?>"
                                    name="locations">
                                <div class="container_alert"></div>
                            </div>
                        </div>
                    </div>
                    <div class="fv-product-sheet__grid fv-product-sheet__grid--full mt-3">
                        <div class="fv-product-field">
                            <label><?= $pageTexts['f_descrips']?> <span class="color-red">*</span></label>
                            <textarea rows="5" class="form-control" name="descriptions"
                                id="description" onkeyup="return validar_campo(event,'#description')"><?= htmlspecialchars((string) ($this->session->flashdata('descriptions') ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                            <div class="container_alert"></div>
                        </div>
                    </div>
                </div>

                <?php $this->load->view('company/product/semaforo_stock/semaforo_add') ?>
            </div>
            <?php $this->load->view('components/crud/form_action_fab', [
                'formId' => 'form_add',
                'saveLabel' => (string) ($pageTexts['save'] ?? ''),
                'cancelLabel' => (string) ($pageTexts['f_cancelar'] ?? ''),
                'cancelUrl' => company_url('products'),
                'saveButtonId' => 'boton_enviar_first',
            ]); ?>
        </div>
    </div>
    <?php
    $modalProduct = (object) [
        'sku' => '',
        'pro_id' => 0,
        'images' => [null, null, null, null, null],
    ];
    ?>
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
                    <?php $this->load->view('company/product/modals/images', ['product' => $modalProduct, 'pageTexts' => $pageTexts]) ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="fv-product-images-modal__btn fv-product-images-modal__btn--cancel"
                            data-dismiss="modal"><?= htmlspecialchars((string) ($pageTexts['regresar'] ?? ''), ENT_QUOTES, 'UTF-8') ?></button>
                </div>
            </div>
        </div>
    </div>

</form>

<?php $this->load->view('company/product/modals/category_groups', [
    'pageTexts' => $pageTexts,
]); ?>

<?php $this->load->view('company/product/price_groups/price_groups_javascript') ?>
<script src="<?= base_url('companyasset/js/select2.min.js') ?>"></script>
<script src="<?= base_url('companyasset/js/sortable.min.js') ?>"></script>
<script type="text/javascript">
window.FORM_ADD_MESSAGES = {
    success: <?= json_encode($pageTexts['success_created'] ?? $pageTexts['success_saved'] ?? '') ?>,
    error: <?= json_encode($pageTexts['error_occurred'] ?? '') ?>
};
</script>
<script type="text/javascript">
var form = document.querySelector('#form-guardar')

const _groupSeparator = "<?= $format_numeric->groupSeparator ?>"
const _radixPoint = "<?= $format_numeric->radixPoint ?>"
const _digits = "<?= $format_numeric->digits ?>"
<?php $this->load->view('company/product/_unit_qty_mask_script'); ?>


document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('input[type=text]').forEach(node => node.addEventListener('keypress', e => {
        if (e.keyCode == 13) {
            e.preventDefault();
        }
    }))
});


$(document).ready(function() {
    function applyInputmask() {
        if (typeof Inputmask === 'undefined') return false;
        Inputmask("currency", {
            radixPoint: _radixPoint,
            groupSeparator: _groupSeparator,
            digits: _digits
        }).mask(".mask");
        if (typeof window.fvApplyUnitQtyMask === 'function') {
            window.fvApplyUnitQtyMask();
        }
        return true;
    }
    if (!applyInputmask()) {
        var attempts = 0, maxAttempts = 80;
        var iv = setInterval(function() {
            if (applyInputmask() || ++attempts >= maxAttempts) clearInterval(iv);
        }, 150);
    }

    // Categorías: multiselect (Select2 cargado arriba en esta misma vista).
    (function() {
        var $el = $('#form_add #category');
        if (!$el.length || typeof $el.select2 !== 'function') return;
        try { $el.select2('destroy'); } catch (e) {}
        $el.select2({
            placeholder: '<?= isset($pageTexts['option_select']) ? addslashes($pageTexts['option_select']) : 'Seleccione una o más categorías' ?>',
            allowClear: true,
            width: '100%'
        });
    })();
    $('#notify_minimum_stock').select2({
        placeholder: '<?= $pageTexts['option_select'] ?>'
    });
    function initTagsInputWithRetry() {
        if (typeof $.fn.tagsinput !== 'function') {
            return false;
        }
        $(".taginput").tagsinput({
            cancelConfirmKeysOnEmpty: true
        });
        return true;
    }
    if (!initTagsInputWithRetry()) {
        var tagAttempts = 0, tagMaxAttempts = 80;
        var tagIv = setInterval(function() {
            if (initTagsInputWithRetry() || ++tagAttempts >= tagMaxAttempts) {
                clearInterval(tagIv);
            }
        }, 150);
    }

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

    $(document).on('click keyup', '.bootstrap-tagsinput input', function() {
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

    // Mismo criterio que en producción (footer): Sortable sobre lista_sortable con getElementById,
    // animation '150', chosenClass 'sele', group 'lista', store.set → orden_global = toArray().
    // Diferencia necesaria: variable lista_sortable asignada al nodo; init al abrir el modal.
    var fvAddProductImageSortable = null;
    window.orden_global = null;
    function fvInitAddProductImagesSortable() {
        var lista_sortable = document.getElementById('lista_sortable');
        if (!lista_sortable || typeof Sortable === 'undefined') {
            return;
        }
        if (fvAddProductImageSortable) {
            fvAddProductImageSortable.destroy();
            fvAddProductImageSortable = null;
        }
        fvAddProductImageSortable = Sortable.create(lista_sortable, {
            animation: '150',
            draggable: '.fv-product-images-slot',
            chosenClass: 'sele',
            group: 'lista',
            dataIdAttr: 'data-id',
            handle: '.fv-img-drag-handle',
            filter: '.fv-product-images-slot--empty',
            preventOnFilter: false,
            onEnd: function () {
                if (fvAddProductImageSortable && typeof fvAddProductImageSortable.toArray === 'function') {
                    window.orden_global = fvAddProductImageSortable.toArray().filter(function (id) {
                        return id && id !== '';
                    });
                }
            },
            store: {
                set: function (sortable) {
                    window.orden_global = sortable.toArray().filter(function (id) {
                        return id && id !== '';
                    });
                }
            }
        });
        console.log('Sortable init - lista detectada', lista_sortable);
        console.log('Sortable init - orden inicial', fvAddProductImageSortable.toArray());
    }
    $(document).on('shown.bs.modal', '#modal_imagenes', function () {
        fvInitAddProductImagesSortable();
    });
    $(document).on('hidden.bs.modal', '#modal_imagenes', function () {
        if (fvAddProductImageSortable) {
            fvAddProductImageSortable.destroy();
            fvAddProductImageSortable = null;
        }
    });
})
</script>
