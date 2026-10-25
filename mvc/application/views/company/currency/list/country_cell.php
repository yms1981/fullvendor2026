<?php
/**
 * @var string $countryName
 * @var string $sortname ISO 3166-1 alpha-2 (lowercase)
 */
$countryName = trim((string) ($countryName ?? ''));
$sortname = strtolower(trim((string) ($sortname ?? '')));
$flagsBase = rtrim((string) base_url(), '/') . '/images/flags/';
?>
<div class="fv-currency-country-cell">
    <?php if ($sortname !== ''): ?>
        <span class="fv-currency-country-cell__flag" aria-hidden="true">
            <img src="<?= htmlspecialchars($flagsBase . $sortname . '.png', ENT_QUOTES, 'UTF-8') ?>"
                 alt=""
                 onerror="this.closest('.fv-currency-country-cell__flag').style.display='none'">
        </span>
    <?php endif; ?>
    <span class="fv-currency-country-cell__name"><?= htmlspecialchars($countryName, ENT_QUOTES, 'UTF-8') ?></span>
</div>
