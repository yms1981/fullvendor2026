<?php
$cartTokenStr = ! empty($cart_token) ? (string) $cart_token : '';
$noImgUrl = catalog_public_no_image_url();
$modalProductId = (int) ($producto_conformato->pro_id ?? 0);
$tabIdPrefix = 'catalog-modal-tab-' . $modalProductId . '-';

$imagenesList = is_array($imagenes ?? null) ? $imagenes : [];
if ($imagenesList === [] && ! empty($producto_conformato->imagen_js)) {
    $imagenesList = [(object) ['url' => $producto_conformato->imagen_js]];
}

$resolveModalImageUrl = static function ($imagen) use ($cartTokenStr, $noImgUrl): string {
    if (! is_object($imagen)) {
        return $noImgUrl;
    }

    $raw = trim((string) ($imagen->url ?? ''));
    if ($raw === '' && ! empty($imagen->images) && ! preg_match('#^https?://#i', (string) $imagen->images)) {
        $raw = trim((string) $imagen->images);
    }
    if ($raw === '') {
        return $noImgUrl;
    }

    if (preg_match('#^https?://#i', $raw)) {
        return $raw;
    }

    return catalog_public_image_url($raw, $cartTokenStr);
};
?>
<div class="tab-content product-details-large" style="height: 70%">
    <?php $cont = 0; ?>
    <?php if ($imagenesList === []) { ?>
        <div class="tab-pane fade shown active" id="<?= htmlspecialchars($tabIdPrefix . '1', ENT_QUOTES, 'UTF-8') ?>" role="tabpanel">
            <div class="modal_tab_img">
                <a href="#">
                    <img style="height: 400px;width: 100%;object-fit: scale-down;" src="<?= htmlspecialchars($noImgUrl, ENT_QUOTES, 'UTF-8') ?>" alt="">
                </a>
            </div>
        </div>
    <?php } ?>
    <?php foreach ($imagenesList as $imagen) {?>
        <?php
        $cont = $cont + 1;
        $imageUrl = $resolveModalImageUrl($imagen);
        $tabId = $tabIdPrefix . $cont;
        ?>
        <div class="tab-pane fade<?= ($cont === 1) ? ' shown active' : '' ?>" id="<?= htmlspecialchars($tabId, ENT_QUOTES, 'UTF-8') ?>" role="tabpanel">
            <div class="modal_tab_img">
                <a href="#">
                    <img style="height: 400px;width: 100%;object-fit: scale-down;" src="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') ?>" alt=""
                         onerror="this.onerror=null; this.src='<?= htmlspecialchars($noImgUrl, ENT_QUOTES, 'UTF-8') ?>';">
                </a>
            </div>
        </div>
    <?php } ?>
</div>
<div class="modal_tab_button" style="height: 30%">
    <ul class="nav product_navactive owl-carousel" role="tablist">
        <?php $cont = 0; ?>
        <?php if ($imagenesList === []) { ?>
            <li>
                <a class="nav-link active" href="#<?= htmlspecialchars($tabIdPrefix . '1', ENT_QUOTES, 'UTF-8') ?>" role="tab" aria-controls="<?= htmlspecialchars($tabIdPrefix . '1', ENT_QUOTES, 'UTF-8') ?>" aria-selected="true">
                    <img class="fv-catalog-modal-thumb" src="<?= htmlspecialchars($noImgUrl, ENT_QUOTES, 'UTF-8') ?>" alt="">
                </a>
            </li>
        <?php } ?>
        <?php foreach ($imagenesList as $imagen) {?>
            <?php
            $cont = $cont + 1;
            $thumbUrl = $resolveModalImageUrl($imagen);
            $tabId = $tabIdPrefix . $cont;
            ?>
            <li>
                <a class="nav-link<?= ($cont === 1) ? ' active' : '' ?>" href="#<?= htmlspecialchars($tabId, ENT_QUOTES, 'UTF-8') ?>" role="tab" aria-controls="<?= htmlspecialchars($tabId, ENT_QUOTES, 'UTF-8') ?>" aria-selected="<?= ($cont === 1) ? 'true' : 'false' ?>">
                    <img class="fv-catalog-modal-thumb" src="<?= htmlspecialchars($thumbUrl, ENT_QUOTES, 'UTF-8') ?>" alt=""
                         onerror="this.onerror=null; this.src='<?= htmlspecialchars($noImgUrl, ENT_QUOTES, 'UTF-8') ?>';">
                </a>
            </li>
        <?php } ?>
    </ul>
</div>
