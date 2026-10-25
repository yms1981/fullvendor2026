<?php
/**
 * Modal: especificaciones del producto (pares nombre / valor).
 *
 * @var array $pageTexts
 */
$pageTexts = is_array($pageTexts ?? null) ? $pageTexts : [];
?>
<div class="modal fade" id="modal_product_specs"
     tabindex="-1"
     role="dialog"
     aria-labelledby="modalProductSpecsLabel"
     aria-hidden="true"
     data-load-url="<?= htmlspecialchars(company_url('products/specs/'), ENT_QUOTES, 'UTF-8') ?>"
     data-save-url="<?= htmlspecialchars(company_url('products/specsSave/'), ENT_QUOTES, 'UTF-8') ?>"
     data-msg-loading="<?= htmlspecialchars((string) ($pageTexts['specs_loading'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
     data-msg-saved="<?= htmlspecialchars((string) ($pageTexts['specs_saved'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
     data-msg-error="<?= htmlspecialchars((string) ($pageTexts['specs_error_save'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
     data-msg-load-error="<?= htmlspecialchars((string) ($pageTexts['specs_error_load'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
     data-msg-name-required="<?= htmlspecialchars((string) ($pageTexts['specs_name_required'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
     data-label-name="<?= htmlspecialchars((string) ($pageTexts['specs_col_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
     data-label-value="<?= htmlspecialchars((string) ($pageTexts['specs_col_value'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
     data-label-remove="<?= htmlspecialchars((string) ($pageTexts['specs_remove_row'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
     data-label-sku="<?= htmlspecialchars((string) ($pageTexts['field_sku'] ?? 'SKU'), ENT_QUOTES, 'UTF-8') ?>">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalProductSpecsLabel">
                    <?= htmlspecialchars((string) ($pageTexts['specs_modal_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </h5>
                <button type="button"
                        class="close"
                        data-dismiss="modal"
                        aria-label="<?= htmlspecialchars((string) ($pageTexts['cancelar'] ?? $pageTexts['boton_cancelar'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="fv-product-specs-head mb-3">
                    <div class="fv-product-specs-head__name" id="fv_specs_product_name"></div>
                    <div class="fv-product-specs-head__sku text-muted small" id="fv_specs_product_sku"></div>
                </div>
                <div id="fv_specs_alert" class="alert alert-danger d-none" role="alert"></div>
                <div class="table-responsive">
                    <table class="table table-sm fv-product-specs-table mb-2">
                        <thead>
                        <tr>
                            <th><?= htmlspecialchars((string) ($pageTexts['specs_col_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></th>
                            <th><?= htmlspecialchars((string) ($pageTexts['specs_col_value'] ?? ''), ENT_QUOTES, 'UTF-8') ?></th>
                            <th style="width:3rem;"></th>
                        </tr>
                        </thead>
                        <tbody id="fv_specs_rows"></tbody>
                    </table>
                </div>
                <button type="button"
                        class="btn btn-outline-secondary btn-sm fv-product-specs-add-btn"
                        id="fv_specs_add_row"
                        title="<?= htmlspecialchars((string) ($pageTexts['specs_add_row'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                        aria-label="<?= htmlspecialchars((string) ($pageTexts['specs_add_row'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <i class="mdi mdi-plus-circle-outline" aria-hidden="true"></i>
                    <span><?= htmlspecialchars((string) ($pageTexts['specs_add_row'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                </button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal">
                    <?= htmlspecialchars((string) ($pageTexts['boton_cancelar'] ?? $pageTexts['cancelar'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </button>
                <button type="button" class="btn btn-success" id="fv_specs_save">
                    <?= htmlspecialchars((string) ($pageTexts['specs_save'] ?? $pageTexts['f_guardar'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </button>
            </div>
        </div>
    </div>
</div>
<div id="fv_product_ai_image_cfg"
     class="d-none"
     data-url="<?= htmlspecialchars(company_url('products/assignAiImages'), ENT_QUOTES, 'UTF-8') ?>"
     aria-hidden="true"></div>
