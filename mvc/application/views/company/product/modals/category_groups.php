<?php
/**
 * Modal: árbol de grupos/subgrupos por categorías del producto.
 *
 * @var array $pageTexts
 */
$pt = is_array($pageTexts ?? null) ? $pageTexts : [];
?>
<div class="modal fade" id="modal_product_category_groups" tabindex="-1" role="dialog"
     aria-labelledby="modal_product_category_groups_label" aria-hidden="true"
     data-tree-url="<?= htmlspecialchars(company_url('categories/groups/tree'), ENT_QUOTES, 'UTF-8') ?>"
     data-save-group-url="<?= htmlspecialchars(company_url('categories/groups/saveGroup'), ENT_QUOTES, 'UTF-8') ?>"
     data-save-subgroup-url="<?= htmlspecialchars(company_url('categories/groups/saveSubgroup'), ENT_QUOTES, 'UTF-8') ?>"
     data-empty-groups="<?= htmlspecialchars((string) ($pt['modal_category_groups_empty'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
     data-label-add-group="<?= htmlspecialchars((string) ($pt['modal_add_group'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
     data-label-add-subgroup="<?= htmlspecialchars((string) ($pt['modal_add_subgroup'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
     data-ph-group="<?= htmlspecialchars((string) ($pt['modal_group_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
     data-ph-subgroup="<?= htmlspecialchars((string) ($pt['modal_subgroup_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
     data-label-create="<?= htmlspecialchars((string) ($pt['modal_create'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
     data-msg-name-required="<?= htmlspecialchars((string) ($pt['modal_name_required'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
     data-msg-save-error="<?= htmlspecialchars((string) ($pt['modal_save_error'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal_product_category_groups_label">
                    <?= htmlspecialchars((string) ($pt['modal_category_groups_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal"
                        aria-label="<?= htmlspecialchars((string) ($pt['cancelar'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3" id="fv_cat_groups_modal_hint">
                    <?= htmlspecialchars((string) ($pt['modal_category_groups_hint'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </p>
                <div id="fv_cat_groups_tree_empty" class="alert alert-info d-none mb-0">
                    <?= htmlspecialchars((string) ($pt['modal_category_groups_need_category'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </div>
                <div id="fv_cat_groups_tree_loading" class="text-center py-4 d-none">
                    <div class="spinner-border text-danger" role="status"></div>
                </div>
                <div id="fv_cat_groups_tree" class="fv-cat-groups-tree"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <?= htmlspecialchars((string) ($pt['cancelar'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </button>
                <button type="button" class="btn btn-primary" id="fv_cat_groups_apply">
                    <?= htmlspecialchars((string) ($pt['btn_apply_category_groups'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </button>
            </div>
        </div>
    </div>
</div>
<style>
.fv-cat-groups-tree { max-height: 60vh; overflow: auto; }
.fv-cat-groups-tree__cat {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    margin-bottom: 0.75rem;
    overflow: hidden;
    background: #fff;
}
.fv-cat-groups-tree__cat-head {
    padding: 0.65rem 0.9rem;
    background: #f8fafc;
    font-weight: 700;
    border-bottom: 1px solid #e5e7eb;
}
.fv-cat-groups-tree__group {
    padding: 0.45rem 0.9rem 0.45rem 1rem;
    border-bottom: 1px solid #f1f5f9;
}
.fv-cat-groups-tree__group:last-child { border-bottom: 0; }
.fv-cat-groups-tree__group label,
.fv-cat-groups-tree__sub label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0;
    font-weight: 600;
    cursor: pointer;
}
.fv-cat-groups-tree__subs {
    margin: 0.35rem 0 0.15rem 1.6rem;
    padding-left: 0.75rem;
    border-left: 2px solid #fee2e2;
}
.fv-cat-groups-tree__sub {
    padding: 0.25rem 0;
}
.fv-cat-groups-tree__sub label { font-weight: 500; color: #374151; }
.fv-cat-groups-tree__empty-groups {
    padding: 0.75rem 0.9rem;
    color: #6b7280;
    font-size: 0.9rem;
}
.fv-cat-groups-create {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.55rem 0.9rem 0.7rem;
}
.fv-cat-groups-create--sub {
    margin: 0.35rem 0 0.15rem;
    padding: 0.15rem 0 0.2rem;
}
.fv-cat-groups-create .modal-input-icon {
    flex: 1;
    min-width: 0;
    width: auto;
}
.fv-cat-groups-create .modal-input-icon > .mdi,
.fv-cat-groups-create .modal-input-icon > .fa,
.fv-cat-groups-create .modal-input-icon > .fas {
    display: none;
}
.fv-cat-groups-create input {
    flex: 1;
    min-width: 0;
    width: 100%;
    height: 2.15rem;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 0 0.65rem !important;
    font-size: 0.88rem;
}
.fv-cat-groups-create button {
    flex: 0 0 auto;
    white-space: nowrap;
}
</style>
