<?php
/**
 * Botones de barra CRUD con icono + título.
 * @var array $buttons
 * @var string $defaultButtonClass
 * @var bool $defaultDisabled
 * @var string $extraButtonClass
 */
if (!isset($defaultButtonClass)) {
    $defaultButtonClass = 'btn-primary';
}
$extraButtonClass = trim((string) ($extraButtonClass ?? ''));
$extraButtonClassAttr = $extraButtonClass !== '' ? ' ' . htmlspecialchars($extraButtonClass, ENT_QUOTES, 'UTF-8') : '';
$toolbarClass = !empty($configCrudToolbar) ? ' fv-config-crud-toolbar' : '';
?>
<div class="fv-crud-button-bar<?= $toolbarClass ?>">
<?php foreach ($buttons as $barButtonOrLink) :
    $extraClass = '';
    $extraAttrs = '';
    if (!empty($barButtonOrLink['attrs']) && is_array($barButtonOrLink['attrs'])) {
        foreach ($barButtonOrLink['attrs'] as $attrKey => $attrValue) {
            if ($attrKey === 'class') {
                $extraClass = ' ' . $attrValue;
                continue;
            }
            $extraAttrs .= ' ' . htmlspecialchars((string) $attrKey, ENT_QUOTES, 'UTF-8');
            if ($attrValue !== '' && $attrValue !== null) {
                $extraAttrs .= '="' . htmlspecialchars((string) $attrValue, ENT_QUOTES, 'UTF-8') . '"';
            }
        }
    }
    $label = (string) ($barButtonOrLink['label'] ?? '');
    $icon = (string) ($barButtonOrLink['iconClass'] ?? '');
    $titleAttr = $label !== '' ? ' title="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '"' : '';
    $btnSpacingClass = !empty($configCrudToolbar) ? '' : ' mb-1';

    if (($barButtonOrLink['type'] ?? '') === 'link') : ?>
        <a href="<?= htmlspecialchars((string) ($barButtonOrLink['link'] ?? '#'), ENT_QUOTES, 'UTF-8') ?>"
           class="btn fv-crud-btn fv-config-crud-btn <?= $defaultButtonClass ?> btn-icon-text<?= $btnSpacingClass ?><?= $extraClass ?><?= $extraButtonClassAttr ?>"<?= $titleAttr ?><?= $extraAttrs ?>>
            <?php if ($icon !== '') : ?><i class="<?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i><?php endif; ?>
            <span class="fv-config-crud-btn__label"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
        </a>
    <?php elseif (($barButtonOrLink['type'] ?? '') === 'button') :
        $onclick = '';
        $dataAttrs = '';
        if (isset($barButtonOrLink['action'])) {
            $onclick = $barButtonOrLink['action'];
        } elseif (isset($barButtonOrLink['toggle']) && $barButtonOrLink['toggle'] === 'modal' && isset($barButtonOrLink['target'])) {
            $target = $barButtonOrLink['target'];
            $onclick = "event.preventDefault(); var el=document.getElementById('tipo_d_addorder');if(el)el.value='D'; if(typeof jQuery !== 'undefined' && jQuery('" . $target . "').length) { jQuery('" . $target . "').modal('show'); }";
            $dataAttrs = 'data-toggle="modal" data-target="' . htmlspecialchars($target, ENT_QUOTES, 'UTF-8') . '" data-bs-toggle="modal" data-bs-target="' . htmlspecialchars($target, ENT_QUOTES, 'UTF-8') . '"';
        }
        if (isset($barButtonOrLink['dataAttrs']) && is_array($barButtonOrLink['dataAttrs'])) {
            foreach ($barButtonOrLink['dataAttrs'] as $dk => $dv) {
                $dataAttrs .= ' data-' . htmlspecialchars($dk, ENT_QUOTES, 'UTF-8') . '="' . htmlspecialchars((string) $dv, ENT_QUOTES, 'UTF-8') . '"';
            }
        }
        ?>
        <button type="button"
                class="btn fv-crud-btn fv-config-crud-btn <?= $defaultButtonClass ?> btn-icon-text<?= $btnSpacingClass ?><?= $extraClass ?><?= $extraButtonClassAttr ?>"<?= $titleAttr ?>
                <?php if ($defaultDisabled) : ?>disabled data-initially-disabled="1"<?php endif; ?>
                <?php if (!empty($onclick)) : ?>onclick="<?= htmlspecialchars($onclick, ENT_QUOTES, 'UTF-8') ?>"<?php endif; ?>
                <?= $dataAttrs ?>>
            <?php if ($icon !== '') : ?><i class="<?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i><?php endif; ?>
            <span class="fv-config-crud-btn__label"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
        </button>
    <?php endif;
endforeach; ?>
</div>
