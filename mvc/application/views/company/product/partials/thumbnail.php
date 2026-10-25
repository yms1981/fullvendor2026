<?php
/**
 * Miniatura cuadrada de producto (listado, pedidos, carrito).
 *
 * @var object|null $listItem
 */
$listItem = is_object($listItem ?? null) ? $listItem : (object) (array) ($listItem ?? []);

$is_popover_image = false;
$imgResource = '';

$resolveUuid = static function ($value): string {
    if ($value === null || $value === '') {
        return '';
    }
    if (is_object($value) && isset($value->uuid) && $value->uuid !== '') {
        return (string) $value->uuid;
    }
    if (is_array($value) && ! empty($value['uuid'])) {
        return (string) $value['uuid'];
    }
    $s = trim((string) $value);
    if ($s === '') {
        return '';
    }
    if (preg_match('#/images/([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})#i', $s, $m)) {
        return $m[1];
    }
    if (preg_match('/^([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/i', $s, $m)) {
        return $m[1];
    }

    return '';
};

$applyImage = static function (string $uuid) use (&$imgResource, &$is_popover_image): void {
    if ($uuid === '') {
        return;
    }
    $imgResource = $uuid;
    $is_popover_image = true;
};

if (! empty($listItem->default_image)) {
    $applyImage($resolveUuid($listItem->default_image));
}
if ($imgResource === '' && ! empty($listItem->default_image_id)) {
    $applyImage($resolveUuid($listItem->default_image_id));
}
if ($imgResource === '' && ! empty($listItem->Img)) {
    $applyImage($resolveUuid($listItem->Img));
}
if ($imgResource === '' && ! empty($listItem->first_ordered_image)) {
    $applyImage($resolveUuid($listItem->first_ordered_image));
}
if ($imgResource === '' && ! empty($listItem->product_image)) {
    $applyImage($resolveUuid($listItem->product_image));
}
if ($imgResource === '' && ! empty($listItem->image_uuid)) {
    $applyImage($resolveUuid($listItem->image_uuid));
}
if ($imgResource === '' && ! empty($listItem->product_images)) {
    $rows = $listItem->product_images;
    if (is_object($rows) && isset($rows->data)) {
        $rows = $rows->data;
    }
    $list = [];
    foreach ((array) $rows as $pi) {
        $list[] = is_object($pi) ? $pi : (object) (array) $pi;
    }
    usort($list, static function ($a, $b) {
        return ((int) ($a->img_order ?? 999999)) <=> ((int) ($b->img_order ?? 999999));
    });
    foreach ($list as $pi) {
        if (! empty($pi->image)) {
            $img = is_object($pi->image) ? $pi->image : (object) (array) $pi->image;
            $uuid = $resolveUuid($img);
            if ($uuid !== '') {
                $applyImage($uuid);
                break;
            }
        }
        if (! empty($pi->image_id)) {
            $uuid = $resolveUuid($pi->image_id);
            if ($uuid !== '') {
                $applyImage($uuid);
                break;
            }
        }
    }
}
if ($imgResource === '' && ! empty($listItem->images)) {
    $images = is_array($listItem->images) ? $listItem->images : (is_object($listItem->images) ? (array) $listItem->images : []);
    if ($images instanceof Traversable) {
        $images = iterator_to_array($images);
    }
    if (! empty($images)) {
        foreach ($images as $image) {
            $uuid = $resolveUuid($image);
            if ($uuid !== '') {
                $applyImage($uuid);
                break;
            }
        }
    }
}

$imageUrl = '';
$imagePreviewUrl = '';
$fallbackCandidates = [];
if ($imgResource !== '') {
    if (function_exists('fv_list_image_urls')) {
        $record = $listItem->default_image ?? $listItem->defaultImage ?? null;
        $urls = fv_list_image_urls($imgResource, $record);
        $imageUrl = (string) ($urls['primary'] ?? '');
        $fallbackCandidates = is_array($urls['fallbacks'] ?? null) ? $urls['fallbacks'] : [];
        // El popover debe preferir API/proxy: uploads/… a menudo 404 en producción.
        $imagePreviewUrl = '';
        foreach (array_merge([$imageUrl], $fallbackCandidates) as $candidate) {
            $candidate = (string) $candidate;
            if ($candidate === '') {
                continue;
            }
            if (strpos($candidate, '/api/v2/images/') !== false || preg_match('#/image/[0-9a-f-]{36}#i', $candidate)) {
                $imagePreviewUrl = $candidate;
                break;
            }
        }
        if ($imagePreviewUrl === '') {
            $imagePreviewUrl = $imageUrl;
        }
    } else {
        $imageUrl = function_exists('fv_api_image_url')
            ? fv_api_image_url($imgResource)
            : company_url('image/' . $imgResource);
        $imagePreviewUrl = $imageUrl;
        $fallbackCandidates[] = company_url('image/' . $imgResource);
    }
}

$sku = trim((string) ($listItem->sku ?? ''));
$avatar = fv_product_sku_avatar_meta($sku);
$hasValidImage = $is_popover_image && $imageUrl !== '';
$fallbackJson = htmlspecialchars(json_encode(array_values($fallbackCandidates), JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');

?>
<figure class="fv-product-list-image"
        data-defeto="<?= htmlspecialchars((string) $imageUrl, ENT_QUOTES, 'UTF-8') ?>"
        data-pro-id="<?= (int) ($listItem->pro_id ?? 0) ?>"
        <?php if (! $hasValidImage && (int) ($listItem->status ?? 0) === 1) : ?>
        data-ai-image-pending="1"
        <?php endif; ?>>
    <div class="fv-product-sku-avatar">
        <?php if ($hasValidImage) : ?>
            <span class="fv-product-sku-avatar__media"
                  data-toggle="popover-hover"
                  title="<?= htmlspecialchars($sku, ENT_QUOTES, 'UTF-8') ?>"
                  data-img="<?= htmlspecialchars((string) $imagePreviewUrl, ENT_QUOTES, 'UTF-8') ?>">
                <img src="<?= htmlspecialchars((string) $imageUrl, ENT_QUOTES, 'UTF-8') ?>"
                     alt="<?= htmlspecialchars($sku, ENT_QUOTES, 'UTF-8') ?>"
                     class="fv-product-sku-avatar__img rounded-0"
                     loading="lazy"
                     decoding="async"
                     data-fv-img-fallbacks="<?= $fallbackJson ?>"
                     onerror="(function(img){var list=[];try{list=JSON.parse(img.getAttribute('data-fv-img-fallbacks')||'[]');}catch(e){}var i=parseInt(img.getAttribute('data-fv-img-i')||'0',10);if(i<list.length){img.setAttribute('data-fv-img-i',String(i+1));img.src=list[i];var p=img.closest('[data-toggle=popover-hover]');if(p){p.setAttribute('data-img',list[i]);}return;}img.style.display='none';var m=img.closest('.fv-product-sku-avatar__media');if(m){m.style.display='none';m.removeAttribute('data-toggle');m.removeAttribute('data-img');}var a=img.closest('.fv-product-sku-avatar');if(a){var n=a.querySelector('.fv-product-sku-avatar__label');if(n){n.classList.add('fv-product-sku-avatar__label--visible');}}})(this);">
            </span>
        <?php endif; ?>
        <span class="fv-product-sku-avatar__label<?= $hasValidImage ? '' : ' fv-product-sku-avatar__label--visible' ?>"
              style="background:<?= htmlspecialchars($avatar['bg'], ENT_QUOTES, 'UTF-8') ?>;font-size:<?= (int) $avatar['font_size'] ?>px;"
              title="<?= htmlspecialchars($sku !== '' ? $sku : ($avatar['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
              aria-hidden="<?= $hasValidImage ? 'true' : 'false' ?>"><?= htmlspecialchars((string) $avatar['label'], ENT_QUOTES, 'UTF-8') ?></span>
    </div>
</figure>
