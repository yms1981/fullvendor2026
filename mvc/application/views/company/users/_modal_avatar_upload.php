<?php
/**
 * Modal para subir/cambiar avatar de usuario desde el listado.
 *
 * @var array $pageTexts
 */
$pageTexts = is_array($pageTexts ?? null) ? $pageTexts : [];
if (function_exists('fv_lang_json_flat_section') && function_exists('fv_resolve_company_language_id')) {
    $langId = (int) fv_resolve_company_language_id();
    $fromJson = fv_lang_json_flat_section($langId, 'cmp-user');
    if (is_array($fromJson) && $fromJson !== []) {
        // JSON fresco gana sobre pageTexts vacíos/desfasados (langmdl).
        foreach ($fromJson as $k => $v) {
            if (! is_string($v) && ! is_numeric($v)) {
                continue;
            }
            $cur = isset($pageTexts[$k]) ? trim((string) $pageTexts[$k]) : '';
            if ($cur === '' || strpos($cur, 'avatar_') === 0) {
                $pageTexts[$k] = (string) $v;
            }
        }
    }
}
$h = static function ($v): string {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};
?>
<div class="modal fade" id="fvUserAvatarModal" tabindex="-1" role="dialog" aria-labelledby="fvUserAvatarModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="fvUserAvatarModalTitle"><?= $h($pageTexts['avatar_modal_title'] ?? '') ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="<?= $h($pageTexts['avatar_cancel'] ?? '') ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <div id="fvUserAvatarPreview" class="fv-user-avatar-preview mx-auto mb-2">
                        <img id="fvUserAvatarPreviewImg" src="" alt="" class="rounded-circle d-none" width="96" height="96">
                        <span id="fvUserAvatarPreviewInitials" class="rounded-circle text-white font-weight-bold d-flex align-items-center justify-content-center"></span>
                    </div>
                    <div id="fvUserAvatarUserName" class="font-weight-bold"></div>
                    <div id="fvUserAvatarUserEmail" class="text-muted small"></div>
                </div>
                <div class="form-group mb-0">
                    <label for="fvUserAvatarFile" class="font-weight-bold"><?= $h($pageTexts['avatar_select_file'] ?? '') ?></label>
                    <input type="file" id="fvUserAvatarFile" class="form-control-file" accept="image/jpeg,image/png,image/gif,image/webp,.jpg,.jpeg,.png,.gif,.webp">
                    <small class="form-text text-muted"><?= $h($pageTexts['avatar_hint'] ?? '') ?></small>
                </div>
                <div id="fvUserAvatarError" class="alert alert-danger mt-3 mb-0 d-none" role="alert"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= $h($pageTexts['avatar_cancel'] ?? '') ?></button>
                <button type="button" class="btn btn-danger" id="fvUserAvatarSave" disabled>
                    <i class="mdi mdi-content-save" aria-hidden="true"></i>
                    <span id="fvUserAvatarSaveLabel"><?= $h($pageTexts['avatar_save'] ?? '') ?></span>
                </button>
            </div>
        </div>
    </div>
</div>
