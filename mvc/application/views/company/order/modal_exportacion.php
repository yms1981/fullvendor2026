<?php
/**
 * Modal de exportación de órdenes (Excel).
 *
 * @var array $pageTexts
 */
$pageTexts = is_array($pageTexts ?? null) ? $pageTexts : [];
?>
<div class="modal fade" id="modal_export" tabindex="-1" role="dialog" aria-labelledby="modal_export_title"
    aria-hidden="true" data-fv-modal-icon="mdi-microsoft-excel">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal_export_title"><?= htmlspecialchars(strtoupper((string) ($pageTexts['titulo_exportacion'] ?? '')), ENT_QUOTES, 'UTF-8') ?></h5>
                <button type="button"
                        class="close fv-modal-close-x"
                        data-dismiss="modal"
                        aria-label="<?= htmlspecialchars((string) ($pageTexts['cerrar'] ?? $pageTexts['close'] ?? $pageTexts['cancel'] ?? $pageTexts['btn_cancelar'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <i class="fa fa-times" aria-hidden="true"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group mb-0">
                    <label><?= htmlspecialchars((string) ($pageTexts['excel_file'] ?? $pageTexts['csv_file'] ?? ''), ENT_QUOTES, 'UTF-8') ?>: <span class="color-red">*</span></label>
                    <div class="clearfix margin-top-10 m-b-10 f12" style="display: block;">
                        <span class="label label-danger"><?= htmlspecialchars((string) ($pageTexts['format_excel'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <p class="text-muted mb-0 small">
                        <?= htmlspecialchars((string) ($pageTexts['export_excel_hint'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= htmlspecialchars((string) ($pageTexts['cancel'] ?? $pageTexts['btn_cancelar'] ?? ''), ENT_QUOTES, 'UTF-8') ?></button>
                <button type="button" id="btn-export" class="btn btn-danger btn-icon-text">
                    <i class="mdi mdi-download" aria-hidden="true"></i>
                    <?= htmlspecialchars((string) ($pageTexts['bnExport'] ?? $pageTexts['bnExport_modal'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </button>
            </div>
        </div>
    </div>
</div>
