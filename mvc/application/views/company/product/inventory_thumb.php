<?php
/**
 * Miniatura de producto para el listado de inventario.
 *
 * @var object $listItem
 * @var int $size
 */
$size = isset($size) ? max(40, min(72, (int) $size)) : 52;
$sku = trim((string) ($listItem->sku ?? ''));
$meta = fv_product_list_image_meta($listItem, $sku);
$avatar = $meta['avatar'];
$fontSize = max(9, min(12, (int) ($avatar['font_size'] ?? 10) - 1));
$fallbackJson = htmlspecialchars(
    json_encode(array_values($meta['fallbacks'] ?? []), JSON_UNESCAPED_SLASHES),
    ENT_QUOTES,
    'UTF-8'
);
?>
<div class="fv-inventory-thumb" style="width:<?= $size ?>px;height:<?= $size ?>px;">
    <?php if ($meta['has_image']) : ?>
        <span class="fv-inventory-thumb__media"
              data-toggle="popover-hover"
              title="<?= htmlspecialchars($sku, ENT_QUOTES, 'UTF-8') ?>"
              data-img="<?= htmlspecialchars($meta['preview'], ENT_QUOTES, 'UTF-8') ?>">
            <img src="<?= htmlspecialchars($meta['thumb'], ENT_QUOTES, 'UTF-8') ?>"
                 alt="<?= htmlspecialchars($sku, ENT_QUOTES, 'UTF-8') ?>"
                 width="<?= $size ?>"
                 height="<?= $size ?>"
                 class="fv-inventory-thumb__img"
                 loading="lazy"
                 decoding="async"
                 data-fv-img-fallbacks="<?= $fallbackJson ?>"
                 onerror="(function(img){var list=[];try{list=JSON.parse(img.getAttribute('data-fv-img-fallbacks')||'[]');}catch(e){}var i=parseInt(img.getAttribute('data-fv-img-i')||'0',10);if(i<list.length){img.setAttribute('data-fv-img-i',String(i+1));img.src=list[i];return;}img.style.display='none';var m=img.closest('.fv-inventory-thumb__media');if(m){m.style.display='none';m.removeAttribute('data-toggle');}var t=img.closest('.fv-inventory-thumb');if(t){var n=t.querySelector('.fv-inventory-thumb__sku');if(n){n.classList.add('fv-inventory-thumb__sku--visible');}}})(this);">
        </span>
    <?php endif; ?>
    <span class="fv-inventory-thumb__sku<?= $meta['has_image'] ? '' : ' fv-inventory-thumb__sku--visible' ?>"
          style="background:<?= htmlspecialchars((string) $avatar['bg'], ENT_QUOTES, 'UTF-8') ?>;font-size:<?= $fontSize ?>px;"
          title="<?= htmlspecialchars($sku !== '' ? $sku : (string) ($avatar['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $avatar['label'], ENT_QUOTES, 'UTF-8') ?></span>
</div>
