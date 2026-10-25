<?php
/**
 * Modal de exportación de categorías (Excel).
 *
 * @var array $pageTexts
 */
?>
<div class="modal fade" id="modal_export" tabindex="-1" role="dialog" aria-labelledby="modal_export_label"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal_export_label">
                    <?= strtoupper(htmlspecialchars((string) ($pageTexts['titulo_exportacion'] ?? ''), ENT_QUOTES, 'UTF-8')) ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="<?= htmlspecialchars((string) ($pageTexts['boton_cancelar'] ?? 'Close'), ENT_QUOTES, 'UTF-8') ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="form-exportar">
                    <input type="hidden" name="type_file" value="excel">
                    <p class="mb-0">
                        <?= htmlspecialchars((string) ($pageTexts['export_excel_hint'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    </p>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" id="btn-export" class="btn btn-primary mt-2 mt-sm-0 btn-icon-text">
                    <i class="mdi mdi-file-excel" aria-hidden="true"></i>
                    <?= htmlspecialchars((string) ($pageTexts['bnExport'] ?? $pageTexts['exportar'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <?= htmlspecialchars((string) ($pageTexts['boton_cancelar'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </button>
            </div>
        </div>
    </div>
</div>
