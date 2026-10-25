<?php
/**
 * Modal legacy de exportación de clientes (Excel).
 * Se conserva por si queda referenciado; el flujo actual exporta al hacer clic en Exportar.
 *
 * @var array $pageTexts
 */
?>
<div class="modal fade" id="modal_export" tabindex="-1" role="dialog" aria-labelledby="modalExportLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalExportLabel">
                    <?= htmlspecialchars((string) ($pageTexts['titulo_exportacion'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="<?= htmlspecialchars((string) ($pageTexts['boton_cancelar'] ?? $pageTexts['cerrar'] ?? 'Close'), ENT_QUOTES, 'UTF-8') ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="mb-0">
                    <?= htmlspecialchars((string) ($pageTexts['export_excel_hint'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </p>
                <input type="hidden" name="type_file" value="excel">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <?= htmlspecialchars((string) ($pageTexts['boton_cancelar'] ?? $pageTexts['cerrar'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </button>
                <a href="javascript:void(0)" id="btn-export" class="btn btn-success btn-icon-text">
                    <i class="mdi mdi-file-excel"></i>
                    <?= htmlspecialchars((string) ($pageTexts['bnExport'] ?? $pageTexts['exportar'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </a>
            </div>
        </div>
    </div>
</div>
