<?php
/**
 * Miniatura cuadrada de categoría (listado CRUD).
 * Prioriza archivo estático /uploads/... (rápido) y cae a API / proxy.
 * Incluye pop-hover ampliado (mismo patrón que listado de productos).
 *
 * @var object|null $listItem
 */
$listItem = is_object($listItem ?? null) ? $listItem : (object) (array) ($listItem ?? []);

$uuid = '';
$imageRecord = $listItem->imageRecord ?? $listItem->image_record ?? null;

$applyFromRef = static function ($ref) use (&$uuid): void {
    if ($uuid !== '' || $ref === null || $ref === '') {
        return;
    }

    if (is_array($ref)) {
        $ref = (object) $ref;
    }

    if (is_object($ref)) {
        $candidate = trim((string) ($ref->uuid ?? $ref->image_uuid ?? ''));
        if ($candidate === '' && isset($ref->image) && is_string($ref->image)) {
            $candidate = trim($ref->image);
        }
        if ($candidate !== '' && preg_match('/^([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/i', $candidate, $m)) {
            $uuid = $m[1];
        }

        return;
    }

    if (! is_string($ref) && ! is_numeric($ref)) {
        return;
    }

    $s = trim((string) $ref);
    if ($s === '') {
        return;
    }
    if (preg_match('#/images/([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})#i', $s, $m)) {
        $uuid = $m[1];

        return;
    }
    if (preg_match('/^([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/i', $s, $m)) {
        $uuid = $m[1];
    }
};

$applyFromRef($imageRecord);
$applyFromRef($listItem->image_uuid ?? null);
$applyFromRef($listItem->imageurl ?? null);
$applyFromRef($listItem->image ?? null);

$name = trim((string) ($listItem->category_name ?? ''));
$avatar = fv_product_sku_avatar_meta($name !== '' ? $name : 'CAT');

$imageUrl = '';
$imagePreviewUrl = '';
$fallbackCandidates = [];
if ($uuid !== '' && function_exists('fv_list_image_urls')) {
    $urls = fv_list_image_urls($uuid, $imageRecord);
    $imageUrl = (string) ($urls['primary'] ?? '');
    $fallbackCandidates = is_array($urls['fallbacks'] ?? null) ? $urls['fallbacks'] : [];
    $imagePreviewUrl = $imageUrl;
    if (function_exists('company_product_image_url')) {
        $large = trim((string) company_product_image_url($uuid, 400));
        if ($large !== '') {
            $imagePreviewUrl = $large;
        }
    }
} elseif ($uuid !== '') {
    $imageUrl = function_exists('fv_api_image_url') ? fv_api_image_url($uuid) : company_url('image/' . $uuid);
    $imagePreviewUrl = function_exists('company_product_image_url')
        ? (string) company_product_image_url($uuid, 400)
        : $imageUrl;
    if ($imagePreviewUrl === '') {
        $imagePreviewUrl = $imageUrl;
    }
    if (function_exists('company_url')) {
        $fallbackCandidates[] = company_url('image/' . $uuid);
    }
}

$hasValidImage = $imageUrl !== '';
$fallbackJson = htmlspecialchars(json_encode(array_values($fallbackCandidates), JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');

?>
<figure class="fv-product-list-image"
        data-defeto="<?= htmlspecialchars((string) $imageUrl, ENT_QUOTES, 'UTF-8') ?>">
    <div class="fv-product-sku-avatar">
        <?php if ($hasValidImage) : ?>
            <span class="fv-product-sku-avatar__media"
                  data-toggle="popover-hover"
                  data-img="<?= htmlspecialchars((string) $imagePreviewUrl, ENT_QUOTES, 'UTF-8') ?>"
                  aria-label="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>">
                <img src="<?= htmlspecialchars((string) $imageUrl, ENT_QUOTES, 'UTF-8') ?>"
                     alt="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                     class="fv-product-sku-avatar__img rounded-0"
                     loading="lazy"
                     decoding="async"
                     data-fv-img-fallbacks="<?= $fallbackJson ?>"
                     onerror="(function(img){var list=[];try{list=JSON.parse(img.getAttribute('data-fv-img-fallbacks')||'[]');}catch(e){}var i=parseInt(img.getAttribute('data-fv-img-i')||'0',10);if(i<list.length){img.setAttribute('data-fv-img-i',String(i+1));img.src=list[i];var p=img.closest('[data-toggle=popover-hover]');if(p){p.setAttribute('data-img',list[i]);}return;}img.style.display='none';var m=img.closest('.fv-product-sku-avatar__media');if(m){m.style.display='none';m.removeAttribute('data-toggle');m.removeAttribute('data-img');}var a=img.closest('.fv-product-sku-avatar');if(a){var n=a.querySelector('.fv-product-sku-avatar__label');if(n){n.classList.add('fv-product-sku-avatar__label--visible');}}})(this);">
            </span>
        <?php endif; ?>
        <span class="fv-product-sku-avatar__label<?= $hasValidImage ? '' : ' fv-product-sku-avatar__label--visible' ?>"
              style="background:<?= htmlspecialchars($avatar['bg'], ENT_QUOTES, 'UTF-8') ?>;font-size:<?= (int) $avatar['font_size'] ?>px;"
              title="<?= htmlspecialchars($name !== '' ? $name : ($avatar['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
              aria-hidden="<?= $hasValidImage ? 'true' : 'false' ?>"><?= htmlspecialchars((string) $avatar['label'], ENT_QUOTES, 'UTF-8') ?></span>
    </div>
</figure>
