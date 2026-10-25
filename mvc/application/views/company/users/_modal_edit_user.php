<?php
/**
 * Shell modal edición de usuario (cuerpo vía AJAX GET users/edit/{id}).
 * Estilos visuales: misma clase .fv-user-form-modal que el modal de alta.
 *
 * @var array $pageTexts
 */
$pageTexts = is_array($pageTexts ?? null) ? $pageTexts : [];
$t = static function (string $key, string $fallback = '') use ($pageTexts): string {
    $v = trim((string) ($pageTexts[$key] ?? ''));

    return htmlspecialchars($v !== '' ? $v : $fallback, ENT_QUOTES, 'UTF-8');
};
?>
<script>
window.fvUserEditModalLang = Object.assign({}, window.fvUserEditModalLang || {}, <?= json_encode([
    'loading' => $pageTexts['loading'] ?? '',
    'load_form_error' => $pageTexts['user_edit_load_error'] ?? ($pageTexts['load_form_error'] ?? ''),
    'edit_user' => $pageTexts['edit_user'] ?? '',
    'ok' => $pageTexts['ok'] ?? '',
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
window.fvUserEditFormUrlBase = <?= json_encode(rtrim(company_url('users/edit'), '/'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<div class="modal fade fv-user-form-modal" id="modalEditUser" tabindex="-1" role="dialog"
     aria-labelledby="modalEditUserLabel" aria-hidden="true"
     data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-add-user-compact modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditUserLabel">
                    <span class="modal-header__avatar" aria-hidden="true"><i class="mdi mdi-account-edit-outline"></i></span>
                    <span><?= $t('edit_user', '') ?></span>
                </h5>
                <button type="button"
                        class="close fv-modal-close-x"
                        data-dismiss="modal"
                        aria-label="<?= $t('close', $t('cerrar', $t('back', ''))) ?>">
                    <i class="mdi mdi-close" aria-hidden="true"></i>
                </button>
            </div>
            <div class="modal-body" id="modal_edit_user_body">
                <div class="text-center py-4">
                    <div class="spinner-border text-danger" role="status" aria-hidden="true"></div>
                    <?php if (trim((string) ($pageTexts['loading'] ?? '')) !== ''): ?>
                        <div class="mt-2 text-muted"><?= $t('loading', '') ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
