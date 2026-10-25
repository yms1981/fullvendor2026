<?php
/**
 * Vista en tarjetas del listado de productos (estilo catálogo premium).
 *
 * @var array $list
 * @var array $itemActions
 * @var string $itemIdField
 * @var string $itemStatusField
 * @var bool $changeStatus
 * @var bool $multipleSelection
 * @var array $pageTexts
 * @var string $controllerUrl
 * @var object|null $format_numeric
 * @var string|null $format_currency
 * @var bool $todos
 */
$list = is_array($list ?? null) ? $list : [];
$itemActions = is_array($itemActions ?? null) ? $itemActions : [];
$actionButtonTemplates = is_array($actionButtonTemplates ?? null) ? $actionButtonTemplates : [];
$radixPoint = $format_numeric->radixPoint ?? '.';
$groupSeparator = $format_numeric->groupSeparator ?? ',';
$digits = (int) ($format_numeric->digits ?? 2);
$currencySymbol = is_string($format_currency ?? null) ? $format_currency : '';

$resolveUnitLabel = static function ($listItem): string {
    if (empty($listItem->unit)) {
        return '';
    }
    $unit = is_object($listItem->unit) ? $listItem->unit : (object) (array) $listItem->unit;

    return trim((string) ($unit->unit ?? ''));
};

$resolveFirstCategory = static function ($listItem): string {
    $categories = $listItem->categories ?? [];
    if (! is_array($categories)) {
        return '';
    }
    foreach ($categories as $cat) {
        $cat = is_object($cat) ? $cat : (object) (array) $cat;
        $name = trim((string) ($cat->category_name ?? ''));
        if ($name !== '') {
            return $name;
        }
    }

    return '';
};

$resolveActionUrl = static function (array $itemActions, string $actionName, int $proId): string {
    if (empty($itemActions[$actionName]['action'])) {
        return '';
    }

    return (string) $itemActions[$actionName]['action'].$proId;
};

$upperText = static function (string $value): string {
    if ($value === '') {
        return '';
    }
    if (function_exists('mb_strtoupper')) {
        return mb_strtoupper($value, 'UTF-8');
    }

    return strtoupper($value);
};
?>
<div id="fv-products-cards-view" class="fv-products-cards-grid d-none" aria-hidden="true">
    <?php if ($list === []) : ?>
        <div class="fv-products-cards-empty text-center text-muted py-5 w-100">
            <?= htmlspecialchars((string) ($pageTexts['view_mode_empty'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php else : ?>
        <?php foreach ($list as $listItem) :
            $listItem = is_object($listItem) ? $listItem : (object) (array) $listItem;
            $proId = (int) ($listItem->{$itemIdField} ?? 0);
            $isDisabled = (int) ($listItem->{$itemStatusField} ?? 0) === 0;
            $price = number_format((float) ($listItem->sale_price ?? 0), $digits, $radixPoint, $groupSeparator);
            $sku = trim((string) ($listItem->sku ?? ''));
            $unitLabel = $resolveUnitLabel($listItem);
            $categoryName = $resolveFirstCategory($listItem);
            $tags = trim((string) ($listItem->tags ?? ''));
            $stock = (string) ($listItem->stock ?? '0');
            $editUrl = $resolveActionUrl($itemActions ?? [], 'edit', $proId);
            $viewUrl = $resolveActionUrl($itemActions ?? [], 'view', $proId);
            $primaryUrl = $editUrl !== '' ? $editUrl : $viewUrl;
            $statusLabel = $isDisabled
                ? (string) ($pageTexts['select-desactivo-singular'] ?? '')
                : (string) ($pageTexts['select-activo-singular'] ?? '');
            $perUnitSuffix = '';
            if ($unitLabel !== '') {
                $perUnitTemplate = (string) ($pageTexts['card_per_unit'] ?? '/%s');
                $perUnitSuffix = strpos($perUnitTemplate, '%s') !== false
                    ? sprintf($perUnitTemplate, $unitLabel)
                    : $perUnitTemplate.' '.$unitLabel;
            }
            ?>
            <article class="fv-product-card<?= $isDisabled ? ' fv-product-card--disabled' : '' ?>" data-pro-id="<?= $proId ?>">
                <?php if (! empty($multipleSelection)) : ?>
                    <label class="fv-product-card__check" title="<?= htmlspecialchars((string) ($pageTexts['option_select'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="checkbox"
                               name="<?= htmlspecialchars($pageTexts['entityName'] ?? 'product', ENT_QUOTES, 'UTF-8') ?>Ids[]"
                               value="<?= $proId ?>"
                               class="checkBoxClass"
                            <?= ! empty($todos) ? 'checked' : '' ?>>
                    </label>
                <?php endif; ?>

                <div class="fv-product-card__hero">
                    <div class="fv-product-card__status" aria-label="<?= htmlspecialchars((string) ($pageTexts['status-general'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <span class="fv-product-card__status-dot" aria-hidden="true"></span>
                        <span class="fv-product-card__status-label"><?= htmlspecialchars((string) ($pageTexts['status-general'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="fv-product-card__status-value"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>

                    <div class="fv-product-card__media">
                        <?php $this->load->view('company/product/partials/thumbnail', ['listItem' => $listItem, 'thumbPreset' => 'card']); ?>
                    </div>
                </div>

                <div class="fv-product-card__divider" aria-hidden="true"></div>

                <div class="fv-product-card__body">
                    <?php if ($sku !== '' || $unitLabel !== '') : ?>
                        <p class="fv-product-card__eyebrow">
                            <?php if ($sku !== '') : ?>
                                <span><?= htmlspecialchars($sku, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                            <?php if ($sku !== '' && $unitLabel !== '') : ?>
                                <span class="fv-product-card__eyebrow-sep" aria-hidden="true">·</span>
                            <?php endif; ?>
                            <?php if ($unitLabel !== '') : ?>
                                <span><?= htmlspecialchars($upperText($unitLabel), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>

                    <?php
                    $cardTaxonomy = fv_product_list_taxonomy_payload(
                        $listItem,
                        $format_numeric ?? null,
                        is_string($format_currency ?? null) ? $format_currency : null
                    );
                    $cardTaxonomyJson = htmlspecialchars(
                        json_encode($cardTaxonomy, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT),
                        ENT_QUOTES,
                        'UTF-8'
                    );
                    $cardCatEdit = (string) ($pageTexts['inline_taxonomy_edit_category'] ?? ($pageTexts['bnAssignCat'] ?? ''));
                    $cardGroupsEdit = (string) ($pageTexts['inline_taxonomy_edit_groups'] ?? ($pageTexts['btn_select_category_groups'] ?? ''));
                    $cardGroupsNone = (string) ($pageTexts['category_groups_none'] ?? '');
                    $cardWithoutCat = (string) ($pageTexts['without_category'] ?? '—');
                    ?>
                    <button type="button"
                            class="fv-list-taxonomy-trigger fv-product-card__category-btn"
                            data-fv-list-taxonomy="<?= $cardTaxonomyJson ?>"
                            data-fv-list-taxonomy-focus="category"
                            title="<?= htmlspecialchars($cardCatEdit, ENT_QUOTES, 'UTF-8') ?>"
                            aria-label="<?= htmlspecialchars($cardCatEdit, ENT_QUOTES, 'UTF-8') ?>">
                        <p class="fv-product-card__category mb-0">
                            <span aria-hidden="true">•</span>
                            <?= htmlspecialchars($upperText($categoryName !== '' ? $categoryName : $cardWithoutCat), ENT_QUOTES, 'UTF-8') ?>
                            <i class="mdi mdi-pencil fv-list-taxonomy-edit-hint" aria-hidden="true"></i>
                        </p>
                    </button>

                    <h3 class="fv-product-card__name">
                        <?php if ($primaryUrl !== '') : ?>
                            <a href="<?= htmlspecialchars($primaryUrl, ENT_QUOTES, 'UTF-8') ?>" class="fv-product-card__name-link">
                                <?= htmlspecialchars((string) ($listItem->name ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        <?php else : ?>
                            <?= htmlspecialchars((string) ($listItem->name ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        <?php endif; ?>
                    </h3>
                    <?php
                    $cardRating = function_exists('company_product_rating_value')
                        ? company_product_rating_value($listItem)
                        : 3.5;
                    $cardRatingAria = (string) ($pageTexts['product_rating_aria'] ?? '');
                    if ($cardRatingAria !== '' && strpos($cardRatingAria, '%s') !== false) {
                        $cardRatingAria = sprintf($cardRatingAria, number_format($cardRating, 1, '.', ''));
                    }
                    if (function_exists('company_product_rating_stars_html')) :
                        ?>
                        <div class="fv-product-card__rating">
                            <?= company_product_rating_stars_html($cardRating, $cardRatingAria) ?>
                        </div>
                    <?php endif; ?>
                    <?php
                    $cardGroupLabels = fv_product_category_group_labels(
                        $listItem->categoryGroups ?? $listItem->category_groups ?? [],
                        $listItem->categorySubgroups ?? $listItem->category_subgroups ?? []
                    );
                    ?>
                    <button type="button"
                            class="fv-list-taxonomy-trigger fv-product-card__groups-btn"
                            data-fv-list-taxonomy="<?= $cardTaxonomyJson ?>"
                            data-fv-list-taxonomy-focus="groups"
                            title="<?= htmlspecialchars($cardGroupsEdit, ENT_QUOTES, 'UTF-8') ?>"
                            aria-label="<?= htmlspecialchars($cardGroupsEdit, ENT_QUOTES, 'UTF-8') ?>">
                        <?php if ($cardGroupLabels !== []) : ?>
                            <div class="fv-product-card__groups">
                                <?php foreach ($cardGroupLabels as $gLabel) : ?>
                                    <span class="fv-product-list-groups__chip"><?= htmlspecialchars($gLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endforeach; ?>
                                <i class="mdi mdi-pencil fv-list-taxonomy-edit-hint" aria-hidden="true"></i>
                            </div>
                        <?php else : ?>
                            <span class="fv-list-taxonomy-empty">
                                <?= htmlspecialchars($cardGroupsNone !== '' ? $cardGroupsNone : $cardGroupsEdit, ENT_QUOTES, 'UTF-8') ?>
                                <i class="mdi mdi-pencil fv-list-taxonomy-edit-hint" aria-hidden="true"></i>
                            </span>
                        <?php endif; ?>
                    </button>

                    <?php if ($tags !== '' || $stock !== '') : ?>
                        <p class="fv-product-card__meta-line">
                            <?php if ($tags !== '') : ?>
                                <span class="fv-product-card__tags"><?= htmlspecialchars($tags, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                            <?php if ($tags !== '' && $stock !== '') : ?>
                                <span class="fv-product-card__meta-sep" aria-hidden="true">·</span>
                            <?php endif; ?>
                            <?php if ($stock !== '') : ?>
                                <span class="fv-product-card__stock">
                                    <?= htmlspecialchars((string) ($pageTexts['field_stock'] ?? ''), ENT_QUOTES, 'UTF-8') ?>:
                                    <strong><?= htmlspecialchars($stock, ENT_QUOTES, 'UTF-8') ?></strong>
                                </span>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>

                <div class="fv-product-card__foot">
                    <div class="fv-product-card__price-block">
                        <span class="fv-product-card__price-from"><?= htmlspecialchars((string) ($pageTexts['card_price_from'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="fv-product-card__price-value"><?= htmlspecialchars(trim($currencySymbol.' '.$price), ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if ($perUnitSuffix !== '') : ?>
                            <span class="fv-product-card__price-unit"><?= htmlspecialchars($perUnitSuffix, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="fv-product-card__foot-actions">
                        <?php if (! empty($changeStatus)) : ?>
                            <div class="fv-product-card__toggle">
                                <?php $this->load->view('components/crud/enable-disable_button', [
                                    'listItem' => $listItem,
                                    'itemStatusField' => $itemStatusField,
                                    'itemIdField' => $itemIdField,
                                    'controllerUrl' => $controllerUrl,
                                    'pageTexts' => $pageTexts,
                                ]); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (! empty($itemActions)) : ?>
                            <div class="fv-product-card__secondary-actions">
                                <?php foreach ($itemActions as $actionName => $itemAction) :
                                    $isDelete = ($actionName === 'delete'
                                        || strpos($itemAction['buttonClass'] ?? '', 'go-delete') !== false
                                        || strpos($itemAction['buttonClass'] ?? '', 'btn-go-delete') !== false);
                                    if ($isDelete || $actionName === 'edit') {
                                        continue;
                                    }
                                    if (!empty($actionButtonTemplates[$actionName])) {
                                        $this->load->view($actionButtonTemplates[$actionName], [
                                            'listItem' => $listItem,
                                            'itemAction' => $itemAction,
                                            'itemIdField' => $itemIdField,
                                            'actionName' => $actionName,
                                            'pageTexts' => $pageTexts,
                                        ]);
                                        continue;
                                    }
                                    $btnClass = (strpos($itemAction['buttonClass'] ?? '', 'btn-') === 0)
                                        ? $itemAction['buttonClass']
                                        : 'btn-'.($itemAction['buttonClass'] ?? 'primary');
                                    ?>
                                    <a href="<?= htmlspecialchars($itemAction['action'].$proId, ENT_QUOTES, 'UTF-8') ?>"
                                       class="fv-product-card__secondary-btn <?= htmlspecialchars($btnClass, ENT_QUOTES, 'UTF-8') ?>"
                                       title="<?= htmlspecialchars((string) ($itemAction['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                        <i class="<?= htmlspecialchars((string) ($itemAction['iconClass'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($editUrl !== '') : ?>
                            <a href="<?= htmlspecialchars($editUrl, ENT_QUOTES, 'UTF-8') ?>"
                               class="fv-product-card__cta fv-product-card__cta--edit"
                               title="<?= htmlspecialchars((string) ($itemActions['edit']['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <i class="mdi mdi-pencil" aria-hidden="true"></i>
                            </a>
                        <?php elseif ($primaryUrl !== '') : ?>
                            <a href="<?= htmlspecialchars($primaryUrl, ENT_QUOTES, 'UTF-8') ?>"
                               class="fv-product-card__cta"
                               title="<?= htmlspecialchars((string) ($itemActions['view']['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <i class="mdi mdi-eye" aria-hidden="true"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
