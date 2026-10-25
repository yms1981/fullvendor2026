<?php
/**
 * @var object $listItem the category item from the list for a given record
 * @var array $filterData The current filter data for each item of this filter form
 * @var mixed $value the field value for the listItem
 */
$highligthed = $value;
if (isset($filterData['terms'])) {
    foreach ($filterData['terms'] as $term) {
        $highligthed = highlight_phrase($highligthed, $term);
    }
}
?>
<a href="javascript:void(0);" 
   class="category-edit-link" 
   data-category-id="<?= $listItem->cat_id ?>"
   data-toggle="modal"
   data-target="#modal_edit_category"
   title="Edit"><?= $highligthed ?></a>
