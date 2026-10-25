<?php
/**
 * @var object $listItem the product item form the list for a given record
 * @var array $filterData The current filter data for each item of this filter form
 * @var mixed $value the field value for the listItem
 */
$highligthed = $value;
foreach ($filterData['terms'] as $term) {
    $highligthed = highlight_phrase($highligthed, $term);
}
?>
<a href="<?= company_url('products/edit/'.$listItem->pro_id)?>" title="Edit"><?= $highligthed ?></a>
