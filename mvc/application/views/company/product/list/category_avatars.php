<?php
/**
 * Avatares de categorías con popover (nombre + imagen ampliada).
 *
 * @var array<int, object> $categories
 * @var string $layout compact|inline
 */
$categories = is_array($categories ?? null) ? $categories : [];
$layout = ($layout ?? 'inline') === 'compact' ? 'compact' : 'inline';

if ($categories === []) {
    echo '<span class="text-muted small">—</span>';
    return;
}
?>
<div class="fv-category-avatars fv-category-avatars--<?= htmlspecialchars($layout, ENT_QUOTES, 'UTF-8') ?>">
    <?php foreach ($categories as $cat) :
        $cat = is_object($cat) ? $cat : (object) (array) $cat;
        $name = trim((string) ($cat->category_name ?? ''));
        if ($name === '') {
            continue;
        }
        $urls = fv_category_image_urls($cat);
        $hasCustomImage = ! empty($urls['has_image']);
        $fallbackJson = htmlspecialchars(
            json_encode(array_values($urls['fallbacks'] ?? []), JSON_UNESCAPED_SLASHES),
            ENT_QUOTES,
            'UTF-8'
        );
        $previewUrl = $hasCustomImage ? (string) ($urls['preview'] ?? $urls['thumb'] ?? '') : '';
        ?>
        <span class="fv-category-avatar<?= $hasCustomImage ? ' fv-category-avatar--has-image' : '' ?>"
              <?php if ($hasCustomImage && $previewUrl !== '') : ?>
              data-toggle="popover-hover"
              data-img="<?= htmlspecialchars($previewUrl, ENT_QUOTES, 'UTF-8') ?>"
              data-popover-caption="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
              aria-label="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
              <?php else : ?>
              title="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
              <?php endif; ?>>
            <img src="<?= htmlspecialchars($urls['thumb'], ENT_QUOTES, 'UTF-8') ?>"
                 alt="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                 class="fv-category-avatar__img<?= $hasCustomImage ? '' : ' fv-category-avatar__img--placeholder' ?>"
                 loading="lazy"
                 decoding="async"
                 <?php if ($hasCustomImage) : ?>
                 data-fv-img-fallbacks="<?= $fallbackJson ?>"
                 onerror="(function(img){var list=[];try{list=JSON.parse(img.getAttribute('data-fv-img-fallbacks')||'[]');}catch(e){}var i=parseInt(img.getAttribute('data-fv-img-i')||'0',10);if(i<list.length){img.setAttribute('data-fv-img-i',String(i+1));img.src=list[i];var p=img.closest('[data-toggle=popover-hover]');if(p){p.setAttribute('data-img',list[i]);}return;}img.classList.add('fv-category-avatar__img--placeholder');})(this);"
                 <?php endif; ?>>
            <?php if ($layout === 'inline') : ?>
                <span class="fv-category-avatar__name"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </span>
    <?php endforeach; ?>
</div>
