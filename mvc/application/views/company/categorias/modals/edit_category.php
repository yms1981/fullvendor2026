<?php
/**
 * @var array $pageTexts The translated string conglomerated for this view
 */
$pageTexts = is_array($pageTexts ?? null) ? $pageTexts : [];
?>
<script>
window.fvCategoryFormLang = Object.assign({}, window.fvCategoryFormLang || {}, <?= json_encode([
    'loading' => $pageTexts['loading'] ?? '',
    'load_form_error' => $pageTexts['load_form_error'] ?? '',
    'category_save_error_generic' => $pageTexts['category_save_error_generic'] ?? '',
    'ok' => $pageTexts['ok'] ?? '',
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
</script>
<div class="modal fade" id="modal_edit_category" tabindex="-1" role="dialog" aria-labelledby="modalEditCategoryLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditCategoryLabel">
                    <?= htmlspecialchars((string) strtoupper((string) ($pageTexts['titulo_editar'] ?? $pageTexts['edit'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="<?= htmlspecialchars((string) ($pageTexts['close'] ?? $pageTexts['cerrar'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="modal_edit_category_body">
                <div class="text-center py-4">
                    <div class="spinner-border text-danger" role="status" aria-hidden="true"></div>
                    <?php if (! empty($pageTexts['loading'])): ?>
                        <div class="mt-2 text-muted"><?= htmlspecialchars((string) $pageTexts['loading'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
