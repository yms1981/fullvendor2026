<?php
/**
 * @var array $sellerList The list of sellers
 * @var array $pageTexts
 */
?>
<div class="modal fade fv-seller-assign-modal" id="modalremoveSeller" tabindex="-1" role="dialog" aria-labelledby="modalRemoveSellerLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalRemoveSellerLabel"><?= htmlspecialchars((string) ($pageTexts["remove_seller"] ?? ''), ENT_QUOTES, 'UTF-8') ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="form-asign-remove-seller" id="form-remove-seller"
                    action="<?= company_url('customers/unassignSeller') ?>">
                    <div class="form-group">
                        <label for="select_toremoveseller"><?= htmlspecialchars((string) ($pageTexts["select_sellers"] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>
                        <select name="sellers_tounassign[]" id="select_toremoveseller"
                            class="selectpicker"
                            data-select-all-text="<?= htmlspecialchars((string) ($pageTexts['select_todoestatdos'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                            data-deselect-all-text="<?= htmlspecialchars((string) ($pageTexts['deselect'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                            data-live-search="true" multiple
                            data-actions-box="true"
                            data-size="4"
                            data-container="body"
                            data-dropup-auto="false"
                            data-none-selected-text="<?= htmlspecialchars((string) ($pageTexts["select_sellers"] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                            data-width="100%"
                        >
                            <?php foreach ($sellerList as $seller) { ?>
                                <option value="<?= (int) $seller->id ?>"><?= htmlspecialchars(function_exists('mb_strtoupper') ? mb_strtoupper((string) ($seller->user->name ?? ''), 'UTF-8') : strtoupper((string) ($seller->user->name ?? '')), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal"><?= htmlspecialchars((string) ($pageTexts['cancelar'] ?? ''), ENT_QUOTES, 'UTF-8') ?></button>
                <button id="btn-removeseller" type="submit" form="form-remove-seller" class="btn btn-primary">
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    <?= htmlspecialchars((string) ($pageTexts['aceptar'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </button>
            </div>
        </div>
    </div>
</div>
