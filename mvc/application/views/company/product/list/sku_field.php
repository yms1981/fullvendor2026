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
<?php if($listItem->barcode != null || $listItem->barcode != ''){?>
    <a title="Barcode: <?= $listItem->barcode ?>"><img
            src="<?= base_url('images/codbarra.png')?>"
            alt="<?= $listItem->barcode ?>" class="img-fluid img-thumbnail"
            style="width: 20px; height: 20px;"></a>
<?php }?>
