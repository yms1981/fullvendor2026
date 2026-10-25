<?php
/**
 * @var object $listItem the rol item from the list
 * @var array $itemAction the action configuration
 * @var string $itemIdField the field name that contains the item ID
 */
$rolId = isset($listItem->$itemIdField) ? $listItem->$itemIdField : (isset($listItem->rol_id) ? $listItem->rol_id : '');
?>
<button type="button"
        class="btn btn-<?=$itemAction['buttonClass']??'primary' ?> btn-sm"
        title="<?= $itemAction['label'] ?>"
        data-toggle="modal"
        data-target="#modal_edit_rol"
        data-rol-id="<?= htmlspecialchars($rolId) ?>">
    <i class="<?=$itemAction['iconClass']?>"></i>
</button>

