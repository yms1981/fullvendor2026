<?php
/**
 * @var object $listItem the unit item from the list
 * @var array $itemAction the action configuration
 * @var string $itemIdField the field name that contains the item ID
 */
?>
<button type="button"
        class="btn btn-<?=$itemAction['buttonClass']??'primary' ?> btn-sm fv-crud-btn fv-crud-btn--row"
        title="<?= $itemAction['label'] ?>"
        data-toggle="modal"
        data-target="#modal_edit_unit"
        data-unit-id="<?= $listItem->$itemIdField ?>">
    <i class="<?=$itemAction['iconClass']?>"></i>
</button>
