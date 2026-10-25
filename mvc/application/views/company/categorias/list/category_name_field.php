<?php
/**
 * @var object $listItem the category item from the list for a given record
 * @var array $filterData The current filter data for each item of this filter form
 * @var mixed $value the field value for the listItem
 * @var array $pageTexts
 */
$highligthed = $value;
if (isset($filterData['terms']) && is_array($filterData['terms'])) {
    foreach ($filterData['terms'] as $term) {
        $highligthed = highlight_phrase($highligthed, $term);
    }

    if (substr_count($highligthed, '<mark>') < count($filterData['terms'])) {
        $highligthed = $value;
    }
}

$isDefaultCategory = (int) ($listItem->category_default ?? 0) === 1;
$lockTitle = (string) (($pageTexts['error_edit'] ?? '') ?: '');
?>

<?php if ($isDefaultCategory): ?>
<span class="text-muted" title="<?= htmlspecialchars($lockTitle, ENT_QUOTES, 'UTF-8') ?>"><?= $highligthed ?></span>
<?php else: ?>
<a href="javascript:void(0);"
   class="category-edit-link"
   data-category-id="<?= (int) ($listItem->cat_id ?? 0) ?>"
   data-toggle="modal"
   data-target="#modal_edit_category"
   title="<?= htmlspecialchars((string) ($pageTexts['edit'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= $highligthed ?></a>
<?php endif; ?>
