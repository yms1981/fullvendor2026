<?php
/**
 * @var object $product The customer groups/ price lists available for the company
 * @var $pageTexts array the translated string conglomerated for this view
 *
 */
?>
<?php
$imgResource = null;
if (!empty($product->default_image)) {
    $imgResource = $product->default_image;
} elseif (!empty($product->images)) {
    $image = $product->images[0];
    $imgResource = $image->uuid ?? $image;
}
$imagen_defecto = function_exists('company_product_image_url')
    ? company_product_image_url($imgResource, 0)
    : (function_exists('fv_api_image_url') && is_string($imgResource)
        ? fv_api_image_url((string) (is_object($imgResource) ? ($imgResource->uuid ?? '') : $imgResource))
        : company_url('image/' . (is_object($imgResource) ? ($imgResource->uuid ?? '') : $imgResource)));
if ($imagen_defecto === '' || $imagen_defecto === company_url('image/')) {
    $imagen_defecto = catalog_public_no_image_url();
}
?>
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel"><?= $pageTexts['Foto_principal']?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-3">
                <img src="<?= $imagen_defecto ?>" class="rounded-0"
                     style="display: block; width: 100%;"/>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger"
                        data-dismiss="modal"><?= $pageTexts['regresar']?></button>
            </div>
        </div>
    </div>

