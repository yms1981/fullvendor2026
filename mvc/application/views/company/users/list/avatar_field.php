<?php
/**
 * Avatar clickable: MinIO vía company/image/{uuid}; click abre modal de subida.
 *
 * @var object $listItem
 * @var array|null $pageTexts
 */
if (! isset($listItem) || ! is_object($listItem)) {
    return;
}

$h = static function ($v): string {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};

$rawName = (string) ($listItem->name ?? '');
$userId = (int) ($listItem->id ?? 0);
$userEmail = (string) ($listItem->email ?? '');
$image = trim((string) ($listItem->image ?? ''));

$imgUrl = '';
$imageUuid = '';
$imgFallbacks = [];
if (function_exists('fv_user_avatar_image_urls')) {
    $avatarUrls = fv_user_avatar_image_urls($listItem);
    $imgUrl = (string) ($avatarUrls['primary'] ?? '');
    $imgFallbacks = is_array($avatarUrls['fallbacks'] ?? null) ? $avatarUrls['fallbacks'] : [];
} elseif (function_exists('fv_user_avatar_image_url')) {
    $imgUrl = fv_user_avatar_image_url($listItem);
}
if ($imgUrl === '' && $image !== '' && function_exists('company_url')
    && preg_match('/^([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})(?:\.([a-z0-9]+))?$/i', $image, $m)
) {
    $imageUuid = $m[1];
    $imgUrl = function_exists('fv_api_image_url')
        ? fv_api_image_url($imageUuid)
        : company_url('image/' . $imageUuid);
    if (function_exists('company_url')) {
        $proxy = company_url('image/' . $imageUuid);
        if ($proxy !== '' && $proxy !== $imgUrl) {
            $imgFallbacks[] = $proxy;
        }
    }
}
if ($imageUuid === '' && preg_match('/^([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/i', $image, $m)) {
    $imageUuid = $m[1];
}
$hasFile = $imgUrl !== '';

$initials = '?';
$trimName = trim(preg_replace('/\s+/u', ' ', $rawName));
if ($trimName !== '') {
    $parts = preg_split('/\s+/u', $trimName, -1, PREG_SPLIT_NO_EMPTY);
    if (count($parts) >= 2) {
        $a = function_exists('mb_substr') ? mb_substr($parts[0], 0, 1) : substr($parts[0], 0, 1);
        $b = function_exists('mb_substr') ? mb_substr($parts[1], 0, 1) : substr($parts[1], 0, 1);
        $initials = function_exists('mb_strtoupper') ? mb_strtoupper($a . $b) : strtoupper($a . $b);
    } else {
        $len = function_exists('mb_strlen') ? mb_strlen($parts[0]) : strlen($parts[0]);
        $take = min(2, max(1, $len));
        $slice = function_exists('mb_substr') ? mb_substr($parts[0], 0, $take) : substr($parts[0], 0, $take);
        $initials = function_exists('mb_strtoupper') ? mb_strtoupper($slice) : strtoupper($slice);
    }
} elseif ($userEmail !== '') {
    $local = (string) (explode('@', $userEmail, 2)[0] ?? '');
    $local = preg_replace('/[^a-zA-Z0-9]/', '', $local);
    if ($local !== '') {
        $initials = strtoupper(strlen($local) >= 2 ? substr($local, 0, 2) : $local);
    }
}

$seed = (string) ($userEmail !== '' ? $userEmail : ($rawName !== '' ? $rawName : (string) $userId));
$hue = abs(crc32($seed)) % 360;
$bg = 'hsl(' . $hue . ', 42%, 46%)';

$changeLabel = (string) (($pageTexts['avatar_change'] ?? '') ?: '');
$canUpload = $userId > 0;
?>
<button type="button"
        class="user-list-avatar-btn<?= $canUpload ? ' js-user-avatar-upload' : '' ?>"
        <?php if ($canUpload) : ?>
        data-user-id="<?= (int) $userId ?>"
        data-user-name="<?= $h($rawName) ?>"
        data-user-email="<?= $h($userEmail) ?>"
        data-image-uuid="<?= $h($imageUuid) ?>"
        data-image-url="<?= $h($imgUrl) ?>"
        data-initials="<?= $h($initials) ?>"
        data-bg="<?= $h($bg) ?>"
        title="<?= $h($changeLabel) ?>"
        aria-label="<?= $h($changeLabel !== '' ? $changeLabel : $rawName) ?>"
        <?php else : ?>
        disabled
        <?php endif; ?>
>
    <span class="user-list-avatar-inner js-user-avatar-inner" style="width:44px;height:44px;">
    <?php if ($hasFile) : ?>
        <?php
        $avatarFallbackJson = htmlspecialchars(
            json_encode(array_values($imgFallbacks ?? []), JSON_UNESCAPED_SLASHES),
            ENT_QUOTES,
            'UTF-8'
        );
        ?>
        <img src="<?= $h($imgUrl) ?>"
             alt=""
             width="44"
             height="44"
             class="user-list-avatar-img rounded-circle border border-light shadow-sm"
             style="object-fit:cover;display:block;"
             data-fv-img-fallbacks="<?= $avatarFallbackJson ?>"
             onerror="(function(img){var list=[];try{list=JSON.parse(img.getAttribute('data-fv-img-fallbacks')||'[]');}catch(e){}var i=parseInt(img.getAttribute('data-fv-img-i')||'0',10);if(i<list.length){img.setAttribute('data-fv-img-i',String(i+1));img.src=list[i];return;}img.style.display='none';var n=img.nextElementSibling;if(n){n.classList.remove('d-none');n.classList.add('d-flex');}})(this);">
        <span class="user-list-avatar-initials rounded-circle border border-light shadow-sm text-white font-weight-bold align-items-center justify-content-center d-none"
              style="width:44px;height:44px;font-size:13px;line-height:1;background:<?= $h($bg) ?>;"><?= $h($initials) ?></span>
    <?php else : ?>
        <span class="user-list-avatar-initials rounded-circle border border-light shadow-sm text-white font-weight-bold d-flex align-items-center justify-content-center"
              style="width:44px;height:44px;font-size:13px;line-height:1;background:<?= $h($bg) ?>;"><?= $h($initials) ?></span>
    <?php endif; ?>
    </span>
    <?php if ($canUpload) : ?>
    <span class="user-list-avatar-edit" aria-hidden="true"><i class="mdi mdi-camera"></i></span>
    <?php endif; ?>
</button>
