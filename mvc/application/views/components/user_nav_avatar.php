<?php
/**
 * Avatar del usuario en navbar (imagen o iniciales personalizadas).
 *
 * @var object|array<string, mixed>|null $user
 * @var int $size Tamaño en px (default 36)
 */
$size = isset($size) ? max(28, min(56, (int) $size)) : 36;
$meta = fv_user_avatar_meta($user ?? null);
$fontSize = $size <= 32 ? 12 : 13;
$fallbackJson = htmlspecialchars(
    json_encode(array_values($meta['img_fallbacks'] ?? []), JSON_UNESCAPED_SLASHES),
    ENT_QUOTES,
    'UTF-8'
);
?>
<span class="fv-nav-user-avatar" style="width:<?= $size ?>px;height:<?= $size ?>px;">
    <?php if ($meta['has_image']) : ?>
        <img src="<?= htmlspecialchars($meta['img_url'], ENT_QUOTES, 'UTF-8') ?>"
             alt="<?= htmlspecialchars($meta['name'], ENT_QUOTES, 'UTF-8') ?>"
             width="<?= $size ?>"
             height="<?= $size ?>"
             class="fv-nav-user-avatar__img"
             data-fv-img-fallbacks="<?= $fallbackJson ?>"
             onerror="(function(img){var list=[];try{list=JSON.parse(img.getAttribute('data-fv-img-fallbacks')||'[]');}catch(e){}var i=parseInt(img.getAttribute('data-fv-img-i')||'0',10);if(i<list.length){img.setAttribute('data-fv-img-i',String(i+1));img.src=list[i];return;}img.style.display='none';var n=img.nextElementSibling;if(n){n.classList.add('fv-nav-user-avatar__initials--visible');}})(this);">
        <span class="fv-nav-user-avatar__initials"
              style="width:<?= $size ?>px;height:<?= $size ?>px;font-size:<?= $fontSize ?>px;background:<?= htmlspecialchars($meta['bg'], ENT_QUOTES, 'UTF-8') ?>;"
              aria-hidden="true"><?= htmlspecialchars($meta['initials'], ENT_QUOTES, 'UTF-8') ?></span>
    <?php else : ?>
        <span class="fv-nav-user-avatar__initials fv-nav-user-avatar__initials--visible"
              style="width:<?= $size ?>px;height:<?= $size ?>px;font-size:<?= $fontSize ?>px;background:<?= htmlspecialchars($meta['bg'], ENT_QUOTES, 'UTF-8') ?>;"
              title="<?= htmlspecialchars($meta['name'], ENT_QUOTES, 'UTF-8') ?>"
              aria-hidden="true"><?= htmlspecialchars($meta['initials'], ENT_QUOTES, 'UTF-8') ?></span>
    <?php endif; ?>
</span>
