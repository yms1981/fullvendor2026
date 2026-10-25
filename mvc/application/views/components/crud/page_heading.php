<?php
/**
 * Card de encabezado de formularios y listados.
 *
 * @var string $pageTitle
 * @var string|null $title
 * @var string|null $pageIcon
 * @var string|null $pageDescription
 */
$rawTitle = (string) ($pageTitle ?? $title ?? '');
$headingHtml = function_exists('fv_page_heading_markup')
    ? fv_page_heading_markup($rawTitle)
    : htmlspecialchars(fv_page_heading_text($rawTitle), ENT_QUOTES, 'UTF-8');
$pageIconClass = function_exists('fv_company_page_icon')
    ? fv_company_page_icon($pageIcon ?? null)
    : 'fa-solid fa-layer-group';
$pageDescription = trim((string) ($pageDescription ?? ''));
?>
<div class="page-header fv-page-header">
    <section class="fv-form-hero">
        <span class="fv-form-hero__watermark" aria-hidden="true">
            <i class="<?= htmlspecialchars($pageIconClass, ENT_QUOTES, 'UTF-8') ?>"></i>
        </span>
        <div class="fv-form-hero__main">
            <span class="fv-form-hero__icon" aria-hidden="true">
                <i class="<?= htmlspecialchars($pageIconClass, ENT_QUOTES, 'UTF-8') ?>"></i>
            </span>
            <div class="fv-form-hero__copy">
                <h1 class="fv-form-hero__title fv-page-heading__title page-title"><?= $headingHtml ?></h1>
                <?php if ($pageDescription !== '') : ?>
                    <p class="fv-form-hero__text"><?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($pageDescription !== '') : ?>
            <div class="fv-form-hero__actions">
                <button type="button"
                        class="fv-form-hero__info"
                        title="<?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') ?>"
                        aria-label="<?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') ?>">
                    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                </button>
            </div>
        <?php endif; ?>
    </section>
</div>
