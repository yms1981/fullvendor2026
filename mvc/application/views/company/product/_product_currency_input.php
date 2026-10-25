<?php
/**
 * Input monetario con prefijo de símbolo.
 *
 * @var string $name
 * @var string|null $id
 * @var string|null $value
 * @var string|null $currencySymbol
 * @var bool|null $readonly
 * @var string|null $placeholder
 * @var string|null $extraClass
 * @var string|null $onkeyup
 */
$name = (string) ($name ?? '');
$id = trim((string) ($id ?? ''));
$value = $value ?? '';
$currencySymbol = (string) ($currencySymbol ?? ($format_currency ?? ''));
$readonly = !empty($readonly);
$placeholder = (string) ($placeholder ?? '');
$extraClass = trim((string) ($extraClass ?? ''));
$onkeyup = trim((string) ($onkeyup ?? ''));
?>
<div class="fv-product-currency<?= $readonly ? ' fv-product-currency--readonly' : '' ?>">
    <span class="fv-product-currency__symbol"><?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?></span>
    <input type="text"
           class="form-control fv-product-currency__input mask text-left<?= $extraClass !== '' ? ' ' . htmlspecialchars($extraClass, ENT_QUOTES, 'UTF-8') : '' ?>"
           name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
           <?php if ($id !== '') : ?>id="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>"<?php endif; ?>
           value="<?= htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') ?>"
           <?php if ($placeholder !== '') : ?>placeholder="<?= htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8') ?>"<?php endif; ?>
           <?php if ($readonly) : ?>readonly<?php endif; ?>
           <?php if ($onkeyup !== '') : ?>onkeyup="<?= htmlspecialchars($onkeyup, ENT_QUOTES, 'UTF-8') ?>"<?php endif; ?>>
</div>
