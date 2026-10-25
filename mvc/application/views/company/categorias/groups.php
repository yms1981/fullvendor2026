<?php
/**
 * Gestión elegante de grupos y subgrupos (con foto).
 *
 * @var object $category
 * @var array $groups
 * @var array $pageTexts
 * @var string|null $pageTitle
 */

$pageTexts = is_array($pageTexts ?? null) ? $pageTexts : [];
$categoryName = (string) ($category->category_name ?? '');
$categoryId = (int) ($category->cat_id ?? 0);
$backLabel = (string) ($pageTexts['btn_back'] ?? $pageTexts['back_to_categories'] ?? '');
$backTitle = (string) ($pageTexts['back_to_categories'] ?? $backLabel);
$categoryLabel = (string) ($pageTexts['category_being_edited'] ?? $pageTexts['nombre_categoria'] ?? '');

$resolveEntityImage = static function ($entity) {
    $empty = ['url' => '', 'fallbacks' => [], 'has' => false, 'avatar' => ['label' => '?', 'bg' => '#64748b']];
    if ($entity === null) {
        return $empty;
    }
    $obj = is_array($entity) ? (object) $entity : $entity;
    $name = (string) ($obj->group_name ?? $obj->subgroup_name ?? $obj->category_name ?? '');
    $avatar = function_exists('fv_product_sku_avatar_meta')
        ? fv_product_sku_avatar_meta($name !== '' ? $name : 'X')
        : ['label' => 'X', 'bg' => '#64748b'];

    $uuid = '';
    $record = $obj->imageRecord ?? null;
    if (is_array($record)) {
        $record = (object) $record;
    }
    if (is_object($record) && ! empty($record->uuid)) {
        $uuid = (string) $record->uuid;
    }
    if ($uuid === '' && ! empty($obj->image_uuid)) {
        $uuid = (string) $obj->image_uuid;
    }
    if ($uuid === '' && is_string($obj->image ?? null)) {
        if (preg_match('/^([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/i', $obj->image, $m)) {
            $uuid = $m[1];
        }
    }

    if ($uuid === '' || ! function_exists('fv_list_image_urls')) {
        return ['url' => '', 'fallbacks' => [], 'has' => false, 'avatar' => $avatar];
    }

    $urls = fv_list_image_urls($uuid, $record);
    $primary = (string) ($urls['primary'] ?? '');
    $fallbacks = is_array($urls['fallbacks'] ?? null) ? $urls['fallbacks'] : [];

    return [
        'url' => $primary,
        'fallbacks' => $fallbacks,
        'has' => $primary !== '',
        'avatar' => $avatar,
    ];
};

$catImg = function_exists('fv_category_image_urls')
    ? fv_category_image_urls($category)
    : ['thumb' => '', 'fallbacks' => [], 'has_image' => false];
$catThumb = (string) ($catImg['thumb'] ?? '');
$catHas = ! empty($catImg['has_image']) && $catThumb !== '';
$catFallbacks = is_array($catImg['fallbacks'] ?? null) ? $catImg['fallbacks'] : [];
$catAvatar = function_exists('fv_product_sku_avatar_meta')
    ? fv_product_sku_avatar_meta($categoryName !== '' ? $categoryName : 'CAT')
    : ['label' => 'CAT', 'bg' => '#64748b'];

$imgOnError = "(function(img){var list=[];try{list=JSON.parse(img.getAttribute('data-fv-img-fallbacks')||'[]');}catch(e){}var i=parseInt(img.getAttribute('data-fv-img-i')||'0',10);if(i<list.length){img.setAttribute('data-fv-img-i',String(i+1));img.src=list[i];return;}img.style.display='none';var ph=img.parentElement&&img.parentElement.querySelector('.fv-cg__ph');if(ph){ph.style.display='flex';}})(this);";

$cssHref = base_url('companyasset/css/fv-category-groups.css');
$cssPath = FCPATH . 'companyasset/css/fv-category-groups.css';
if (is_file($cssPath)) {
    $cssHref .= '?v=' . filemtime($cssPath);
}
?>
<link rel="stylesheet" href="<?= htmlspecialchars($cssHref, ENT_QUOTES, 'UTF-8') ?>">

<div class="fv-cg">
    <div class="fv-cg__top">
        <a class="fv-cg__back"
           href="<?= htmlspecialchars(company_url('categories'), ENT_QUOTES, 'UTF-8') ?>"
           title="<?= htmlspecialchars($backTitle, ENT_QUOTES, 'UTF-8') ?>">
            <i class="mdi mdi-arrow-left" aria-hidden="true"></i>
            <span><?= htmlspecialchars($backLabel, ENT_QUOTES, 'UTF-8') ?></span>
        </a>
    </div>

    <header class="fv-cg__hero">
        <div class="fv-cg__hero-photo">
            <?php if ($catHas): ?>
                <img src="<?= htmlspecialchars($catThumb, ENT_QUOTES, 'UTF-8') ?>"
                     alt="<?= htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') ?>"
                     loading="lazy"
                     data-fv-img-fallbacks="<?= htmlspecialchars(json_encode(array_values($catFallbacks), JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?>"
                     onerror="<?= $imgOnError ?>">
                <span class="fv-cg__ph" style="display:none;background:<?= htmlspecialchars((string) $catAvatar['bg'], ENT_QUOTES, 'UTF-8') ?>;">
                    <?= htmlspecialchars((string) $catAvatar['label'], ENT_QUOTES, 'UTF-8') ?>
                </span>
            <?php else: ?>
                <span class="fv-cg__ph" style="background:<?= htmlspecialchars((string) $catAvatar['bg'], ENT_QUOTES, 'UTF-8') ?>;">
                    <?= htmlspecialchars((string) $catAvatar['label'], ENT_QUOTES, 'UTF-8') ?>
                </span>
            <?php endif; ?>
        </div>
        <div>
            <?php if ($categoryLabel !== ''): ?>
                <div class="fv-cg__hero-kicker"><?= htmlspecialchars($categoryLabel, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <h1 class="fv-cg__hero-title"><?= htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="fv-cg__hero-hint"><?= htmlspecialchars($pageTexts['groups_hint'] ?? ($pageTexts['manage_groups_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    </header>

    <section class="fv-cg__panel">
        <h2 class="fv-cg__panel-title"><?= htmlspecialchars($pageTexts['add_group'] ?? '', ENT_QUOTES, 'UTF-8') ?></h2>
        <form id="form-add-group" class="fv-cg-form" method="post" enctype="multipart/form-data"
              action="<?= htmlspecialchars(company_url('categories/groups/saveGroup'), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="category_id" value="<?= $categoryId ?>">
            <div class="fv-cg__form-row">
                <div class="fv-cg__photo-field">
                    <label class="fv-cg__photo-picker" title="<?= htmlspecialchars($pageTexts['add_photo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <input type="file" name="image" accept="image/*" data-fv-cg-preview>
                        <span class="fv-cg__photo-empty">
                            <i class="mdi mdi-camera" aria-hidden="true"></i>
                            <?= htmlspecialchars($pageTexts['photo'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <span class="fv-cg__photo-badge"><i class="mdi mdi-check" aria-hidden="true"></i></span>
                    </label>
                </div>
                <div class="fv-cg__field">
                    <label for="group_name"><?= htmlspecialchars($pageTexts['group_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                    <input type="text" class="form-control" id="group_name" name="group_name" required
                           placeholder="<?= htmlspecialchars($pageTexts['group_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="fv-cg__actions">
                    <button type="submit" class="btn btn-primary fv-cg__btn fv-cg__btn--primary">
                        <i class="mdi mdi-plus" aria-hidden="true"></i>
                        <?= htmlspecialchars($pageTexts['add_group'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                    </button>
                </div>
            </div>
        </form>
    </section>

    <?php if (empty($groups)): ?>
        <div class="fv-cg__empty"><?= htmlspecialchars($pageTexts['groups_empty'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
    <?php else: ?>
        <?php foreach ($groups as $group): ?>
            <?php
            $group = is_array($group) ? (object) $group : $group;
            $groupId = (int) ($group->group_id ?? 0);
            $groupName = (string) ($group->group_name ?? '');
            $gImg = $resolveEntityImage($group);
            $subgroups = $group->subgroups ?? [];
            if (is_object($subgroups)) {
                $subgroups = (array) $subgroups;
            }
            if (! is_array($subgroups)) {
                $subgroups = [];
            }
            ?>
            <article class="fv-cg__group" data-group-id="<?= $groupId ?>">
                <div class="fv-cg__group-head">
                    <label class="fv-cg__photo-inline"
                           title="<?= htmlspecialchars($pageTexts['change_photo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <input type="file" accept="image/*"
                               data-fv-cg-upload="<?= htmlspecialchars(company_url('categories/groups/updateGroupImage/' . $groupId), ENT_QUOTES, 'UTF-8') ?>">
                        <?php if ($gImg['has']): ?>
                            <img src="<?= htmlspecialchars($gImg['url'], ENT_QUOTES, 'UTF-8') ?>"
                                 alt="<?= htmlspecialchars($groupName, ENT_QUOTES, 'UTF-8') ?>"
                                 data-fv-img-fallbacks="<?= htmlspecialchars(json_encode(array_values($gImg['fallbacks']), JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?>"
                                 onerror="<?= $imgOnError ?>">
                            <span class="fv-cg__ph" style="display:none;background:<?= htmlspecialchars((string) $gImg['avatar']['bg'], ENT_QUOTES, 'UTF-8') ?>;">
                                <?= htmlspecialchars((string) $gImg['avatar']['label'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php else: ?>
                            <span class="fv-cg__ph" style="background:<?= htmlspecialchars((string) $gImg['avatar']['bg'], ENT_QUOTES, 'UTF-8') ?>;">
                                <?= htmlspecialchars((string) $gImg['avatar']['label'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php endif; ?>
                        <span class="fv-cg__cam"><i class="mdi mdi-camera" aria-hidden="true"></i></span>
                    </label>
                    <h3 class="fv-cg__group-name"><?= htmlspecialchars($groupName, ENT_QUOTES, 'UTF-8') ?></h3>
                    <button type="button"
                            class="btn fv-cg__btn fv-cg__btn--danger fv-cg__btn--icon fv-delete-group"
                            data-url="<?= htmlspecialchars(company_url('categories/groups/deleteGroup/' . $groupId), ENT_QUOTES, 'UTF-8') ?>"
                            data-confirm="<?= htmlspecialchars($pageTexts['confirm_delete_group'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            title="<?= htmlspecialchars($pageTexts['delete'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <i class="mdi mdi-delete" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="fv-cg__group-body">
                    <div class="fv-cg__sub-title"><?= htmlspecialchars($pageTexts['subgroups_of'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>

                    <?php if (empty($subgroups)): ?>
                        <p class="fv-cg__sub-empty"><?= htmlspecialchars($pageTexts['subgroups_empty'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                    <?php else: ?>
                        <ul class="fv-cg__sub-list">
                            <?php foreach ($subgroups as $subgroup): ?>
                                <?php
                                $subgroup = is_array($subgroup) ? (object) $subgroup : $subgroup;
                                $subgroupId = (int) ($subgroup->subgroup_id ?? 0);
                                $subgroupName = (string) ($subgroup->subgroup_name ?? '');
                                if ($subgroupId < 1) {
                                    continue;
                                }
                                $sImg = $resolveEntityImage($subgroup);
                                ?>
                                <li class="fv-cg__sub-item">
                                    <label class="fv-cg__photo-inline fv-cg__thumb-sm"
                                           title="<?= htmlspecialchars($pageTexts['change_photo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="file" accept="image/*"
                                               data-fv-cg-upload="<?= htmlspecialchars(company_url('categories/groups/updateSubgroupImage/' . $subgroupId), ENT_QUOTES, 'UTF-8') ?>">
                                        <?php if ($sImg['has']): ?>
                                            <img src="<?= htmlspecialchars($sImg['url'], ENT_QUOTES, 'UTF-8') ?>"
                                                 alt="<?= htmlspecialchars($subgroupName, ENT_QUOTES, 'UTF-8') ?>"
                                                 data-fv-img-fallbacks="<?= htmlspecialchars(json_encode(array_values($sImg['fallbacks']), JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?>"
                                                 onerror="<?= $imgOnError ?>">
                                            <span class="fv-cg__ph" style="display:none;background:<?= htmlspecialchars((string) $sImg['avatar']['bg'], ENT_QUOTES, 'UTF-8') ?>;">
                                                <?= htmlspecialchars((string) $sImg['avatar']['label'], ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="fv-cg__ph" style="background:<?= htmlspecialchars((string) $sImg['avatar']['bg'], ENT_QUOTES, 'UTF-8') ?>;">
                                                <?= htmlspecialchars((string) $sImg['avatar']['label'], ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        <?php endif; ?>
                                        <span class="fv-cg__cam"><i class="mdi mdi-camera" aria-hidden="true"></i></span>
                                    </label>
                                    <span class="fv-cg__sub-name"><?= htmlspecialchars($subgroupName, ENT_QUOTES, 'UTF-8') ?></span>
                                    <button type="button"
                                            class="btn fv-cg__btn fv-cg__btn--danger fv-cg__btn--icon fv-delete-subgroup"
                                            data-url="<?= htmlspecialchars(company_url('categories/groups/deleteSubgroup/' . $subgroupId), ENT_QUOTES, 'UTF-8') ?>"
                                            data-confirm="<?= htmlspecialchars($pageTexts['confirm_delete_subgroup'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                            title="<?= htmlspecialchars($pageTexts['delete'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                        <i class="mdi mdi-delete" aria-hidden="true"></i>
                                    </button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <form class="form-add-subgroup fv-cg-form" method="post" enctype="multipart/form-data"
                          action="<?= htmlspecialchars(company_url('categories/groups/saveSubgroup'), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="group_id" value="<?= $groupId ?>">
                        <div class="fv-cg__form-row">
                            <div class="fv-cg__photo-field">
                                <label class="fv-cg__photo-picker" title="<?= htmlspecialchars($pageTexts['add_photo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="file" name="image" accept="image/*" data-fv-cg-preview>
                                    <span class="fv-cg__photo-empty">
                                        <i class="mdi mdi-camera" aria-hidden="true"></i>
                                        <?= htmlspecialchars($pageTexts['photo'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                    <span class="fv-cg__photo-badge"><i class="mdi mdi-check" aria-hidden="true"></i></span>
                                </label>
                            </div>
                            <div class="fv-cg__field">
                                <label><?= htmlspecialchars($pageTexts['subgroup_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                                <input type="text" class="form-control" name="subgroup_name" required
                                       placeholder="<?= htmlspecialchars($pageTexts['subgroup_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="fv-cg__actions">
                                <button type="submit" class="btn btn-info fv-cg__btn">
                                    <i class="mdi mdi-plus" aria-hidden="true"></i>
                                    <?= htmlspecialchars($pageTexts['add_subgroup'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
