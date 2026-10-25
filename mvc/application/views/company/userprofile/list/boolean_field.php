<?php
/**
 * @var object $listItem item from the list for a given record
 * @var mixed $value the field value for the listItem
 * @var array|null $pageTexts
 */
$yesLabel = trim((string) (($pageTexts['si'] ?? '') ?: ($pageTexts['yes'] ?? '')));
$noLabel = trim((string) (($pageTexts['no'] ?? '') ?: ($pageTexts['No'] ?? '')));
$isTruthy = ($value == '1' || $value === 1 || $value === true);
?>
<?php if ($isTruthy) : ?>
    <span class="badge badge-success"><?= htmlspecialchars($yesLabel, ENT_QUOTES, 'UTF-8') ?></span>
<?php else : ?>
    <span class="badge badge-secondary"><?= htmlspecialchars($noLabel, ENT_QUOTES, 'UTF-8') ?></span>
<?php endif; ?>

