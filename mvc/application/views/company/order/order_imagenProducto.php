<?php
/**
 * @var object $product the product data
 */

$imagenes = $product->images ?? [];
if (empty($imagenes) && ! empty($product->product_image)) {
    $imagenes = [(object) ['uuid' => $product->product_image]];
}
if (empty($imagenes) && ! empty($product->default_image)) {
    $def = $product->default_image;
    $uuid = is_object($def)
        ? ($def->uuid ?? $def->image_id ?? ($def->image->uuid ?? null))
        : ($def['uuid'] ?? $def['image_id'] ?? ($def['image']['uuid'] ?? null));
    if ($uuid) {
        $imagenes = [(object) ['uuid' => $uuid]];
    }
}
$noImgUrl = company_product_image_url(null, 300);
?>
<div class="fv-order-add-product-image animate__animated animate__zoomIn" style="--animate-duration:0.2s;">
    <div id="carouselExampleCaptions" class="carousel slide" data-ride="carousel">
        <?php if (count($imagenes) > 1) { ?>
            <ol class="carousel-indicators">
                <li data-target="#carouselExampleCaptions" data-slide-to="0" class="active"></li>
                <li data-target="#carouselExampleCaptions" data-slide-to="1"></li>
                <li data-target="#carouselExampleCaptions" data-slide-to="2"></li>
            </ol>
        <?php } ?>
        <div class="carousel-inner">

            <?php if (count($imagenes) >= 1) { ?>
                <?php $i = 1; ?>
                <?php foreach ($imagenes as $imagen) { ?>
                    <?php
                    $imgObj = is_array($imagen) ? (object) $imagen : $imagen;
                    $imgUrl = company_product_image_url($imgObj, 300);
                    ?>
                    <div class="carousel-item <?= ($i == 1) ? 'active' : '' ?>">
                        <figure class="fv-order-add-product-image__figure">
                            <img src="<?= htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8') ?>"
                                 data-fallback="<?= htmlspecialchars($noImgUrl, ENT_QUOTES, 'UTF-8') ?>"
                                 onerror="this.onerror=null;this.src=this.getAttribute('data-fallback');"
                                 class="d-block fv-order-add-product-image__img active" alt="">
                        </figure>
                    </div>
                    <?php $i = $i + 1; ?>
                <?php } ?>
            <?php } else { ?>
                <div class="carousel-item active">
                    <figure class="fv-order-add-product-image__figure">
                        <img src="<?= htmlspecialchars($noImgUrl, ENT_QUOTES, 'UTF-8') ?>"
                             class="d-block fv-order-add-product-image__img active" alt="">
                    </figure>
                </div>
            <?php } ?>

            <?php if (count($imagenes) > 1) { ?>
                <a class="carousel-control-prev" href="#carouselExampleCaptions" role="button" data-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="sr-only">Previous</span>
                </a>
                <a class="carousel-control-next" href="#carouselExampleCaptions" role="button" data-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="sr-only">Next</span>
                </a>
            <?php } ?>
        </div>
    </div>
</div>
