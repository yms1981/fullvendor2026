<?php
/**
 * Botón genérico para acciones de la lista (editar catálogo → Studio)
 *
 * @var object $listItem
 * @var array $itemAction
 * @var string $itemIdField
 * @var string $actionName
 */
$itemIdField = $itemIdField ?? 'catalog_id';
$catalogId = $listItem->{$itemIdField} ?? '';
$rowBtnClass = fv_crud_row_btn_class($itemAction ?? []);
$iconClass = (string) ($itemAction['iconClass'] ?? '');
$label = (string) ($itemAction['label'] ?? '');
$action = (string) ($itemAction['action'] ?? 'javascript:void(0);');

if ($actionName === 'edit') {
    $href = rtrim($action, '/') . '/' . (int) $catalogId;
    ?>
    <a href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>"
       class="<?= htmlspecialchars($rowBtnClass, ENT_QUOTES, 'UTF-8') ?>"
       title="<?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>">
        <i class="<?= htmlspecialchars($iconClass, ENT_QUOTES, 'UTF-8') ?>"></i>
    </a>
    <?php
    return;
}
?>
<button type="button"
        class="<?= htmlspecialchars($rowBtnClass, ENT_QUOTES, 'UTF-8') ?>"
        title="<?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>">
    <i class="<?= htmlspecialchars($iconClass, ENT_QUOTES, 'UTF-8') ?>"></i>
</button>
