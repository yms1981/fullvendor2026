<?php
/**
 * Modal listado: ficha del producto + editar categoría / grupos / subgrupos.
 *
 * @var array $pageTexts
 * @var array|null $categorylist
 */
$pt = is_array($pageTexts ?? null) ? $pageTexts : [];
$categorylist = is_array($categorylist ?? null) ? $categorylist : [];
$h = static function ($v): string {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};
$lbl = static function (string $key, string $fallback = '') use ($pt, $h): string {
    $v = $pt[$key] ?? $fallback;

    return $h($v);
};
?>
<div class="modal fade" id="modal_product_list_taxonomy" tabindex="-1" role="dialog"
     aria-labelledby="modal_product_list_taxonomy_label" aria-hidden="true"
     data-fv-modal-icon="mdi-tag-multiple-outline"
     data-fv-no-field-icons="1"
     data-save-url="<?= $h(company_url('products/inlineTaxonomy/')) ?>"
     data-msg-need-category="<?= $h($pt['must_select_category'] ?? ($pt['select_category'] ?? '')) ?>"
     data-msg-saving="<?= $h($pt['inline_taxonomy_saving'] ?? ($pt['procesando_accion'] ?? '')) ?>"
     data-msg-error="<?= $h($pt['error_general'] ?? '') ?>"
     data-label-enabled="<?= $h($pt['select-activo-singular'] ?? '') ?>"
     data-label-disabled="<?= $h($pt['select-desactivo-singular'] ?? '') ?>"
     data-label-sku="<?= $h($pt['field_sku'] ?? ($pt['f_sku'] ?? 'SKU')) ?>"
     data-label-no-image="<?= $h($pt['inline_taxonomy_no_photo'] ?? '') ?>">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal_product_list_taxonomy_label">
                    <span class="modal-header__avatar" aria-hidden="true">
                        <i class="mdi mdi-tag-multiple-outline"></i>
                    </span>
                    <?= $lbl('inline_taxonomy_title') ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal"
                        aria-label="<?= $lbl('f_cancelar', '×') ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <section class="fv-ltx-card" aria-labelledby="fv_list_taxonomy_product_name">
                    <div class="fv-ltx-card__media">
                        <div id="fv_ltx_photo" class="fv-ltx-photo" hidden>
                            <img id="fv_ltx_photo_img" src="" alt="" loading="lazy" decoding="async">
                        </div>
                        <div id="fv_ltx_avatar" class="fv-ltx-avatar" aria-hidden="true">
                            <span id="fv_ltx_avatar_label"></span>
                        </div>
                    </div>
                    <div class="fv-ltx-card__body">
                        <div class="fv-ltx-card__topline">
                            <span id="fv_ltx_pro_id" class="fv-ltx-id"></span>
                            <span id="fv_ltx_status" class="fv-ltx-status" hidden></span>
                        </div>
                        <h6 id="fv_list_taxonomy_product_name" class="fv-ltx-name"></h6>
                        <p id="fv_ltx_sku" class="fv-ltx-sku mb-2"></p>
                        <dl class="fv-ltx-facts">
                            <div class="fv-ltx-fact">
                                <dt><?= $lbl('field_sale_price') ?></dt>
                                <dd id="fv_ltx_price">—</dd>
                            </div>
                            <div class="fv-ltx-fact">
                                <dt><?= $lbl('field_stock') ?></dt>
                                <dd id="fv_ltx_stock">—</dd>
                            </div>
                            <div class="fv-ltx-fact">
                                <dt><?= $lbl('field_minimum_quantity') ?></dt>
                                <dd id="fv_ltx_moq">—</dd>
                            </div>
                            <div class="fv-ltx-fact">
                                <dt><?= $lbl('f_barcode') ?></dt>
                                <dd id="fv_ltx_barcode">—</dd>
                            </div>
                            <div class="fv-ltx-fact">
                                <dt><?= $lbl('field_tags') ?></dt>
                                <dd id="fv_ltx_tags">—</dd>
                            </div>
                            <div class="fv-ltx-fact">
                                <dt><?= $lbl('field_locations') ?></dt>
                                <dd id="fv_ltx_locations">—</dd>
                            </div>
                        </dl>
                        <p id="fv_ltx_descriptions" class="fv-ltx-desc d-none mb-0"></p>
                    </div>
                </section>

                <div class="fv-ltx-edit">
                    <h6 class="fv-ltx-edit__title"><?= $lbl('inline_taxonomy_edit_section') ?></h6>
                    <div class="form-group">
                        <label for="fv_list_taxonomy_category">
                            <?= $lbl('f_categlist', $pt['field_category_name'] ?? '') ?>
                            <span class="color-red">*</span>
                        </label>
                        <select id="fv_list_taxonomy_category"
                                class="form-control w-100"
                                name="category_id[]"
                                multiple="multiple"
                                data-placeholder="<?= $h($pt['select_category'] ?? '') ?>">
                            <?php foreach ($categorylist as $vc) :
                                $vc = is_object($vc) ? $vc : (object) (array) $vc;
                                $cid = (int) ($vc->cat_id ?? 0);
                                if ($cid < 1) {
                                    continue;
                                }
                                ?>
                                <option value="<?= $cid ?>"><?= $h($vc->category_name ?? '') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label><?= $lbl('f_category_groups') ?></label>
                        <div class="fv-ltx-groups-row">
                            <button type="button"
                                    class="btn btn-outline-primary btn-sm fv-product-cat-groups-search-btn"
                                    id="fv_list_taxonomy_open_groups"
                                    title="<?= $h($pt['btn_select_category_groups'] ?? '') ?>"
                                    aria-label="<?= $h($pt['btn_select_category_groups'] ?? '') ?>">
                                <i class="mdi mdi-magnify" aria-hidden="true"></i>
                            </button>
                            <div id="fv_cat_groups_summary"
                                 class="fv-ltx-groups-summary"
                                 data-none="<?= $h($pt['category_groups_none'] ?? '') ?>">
                                <?= $h($pt['category_groups_none'] ?? '') ?>
                            </div>
                        </div>
                        <div id="fv_cat_groups_hidden_inputs" class="d-none"></div>
                    </div>
                </div>

                <div id="fv_list_taxonomy_alert" class="alert alert-danger mt-3 mb-0 d-none" role="alert"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" id="fv_list_taxonomy_save">
                    <?= $lbl('inline_taxonomy_save', $pt['f_guardar'] ?? '') ?>
                </button>
            </div>
        </div>
    </div>
</div>
<style>
#modal_product_list_taxonomy .select2-container { width: 100% !important; }
#modal_product_list_taxonomy .modal-body { padding-top: 1rem; }
.fv-ltx-card {
    display: flex;
    gap: 1rem;
    align-items: stretch;
    padding: 1rem;
    margin-bottom: 1.15rem;
    background: #fff;
    border: 1px solid var(--fv-modal-border, #fecaca);
    border-radius: 0.75rem;
    box-shadow: 0 0.2rem 0.6rem rgba(127, 29, 29, 0.06);
}
.fv-ltx-card__media {
    flex: 0 0 7.5rem;
    width: 7.5rem;
    height: 7.5rem;
    position: relative;
    border-radius: 0.65rem;
    overflow: hidden;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
}
.fv-ltx-photo,
.fv-ltx-avatar {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}
.fv-ltx-photo[hidden],
.fv-ltx-avatar[hidden] { display: none !important; }
.fv-ltx-photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.fv-ltx-avatar span {
    color: #fff;
    font-weight: 700;
    letter-spacing: 0.02em;
    text-align: center;
    padding: 0.35rem;
    line-height: 1.15;
    word-break: break-word;
}
.fv-ltx-card__body { flex: 1 1 auto; min-width: 0; }
.fv-ltx-card__topline {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.45rem;
    margin-bottom: 0.25rem;
}
.fv-ltx-id {
    font-size: 0.75rem;
    font-weight: 700;
    color: #7f1d1d;
    background: #fee2e2;
    border-radius: 999px;
    padding: 0.15rem 0.55rem;
}
.fv-ltx-status {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    border-radius: 999px;
    padding: 0.15rem 0.55rem;
}
.fv-ltx-status--on { background: #dcfce7; color: #166534; }
.fv-ltx-status--off { background: #f1f5f9; color: #64748b; }
.fv-ltx-name {
    margin: 0 0 0.15rem;
    font-size: 1.05rem;
    font-weight: 700;
    color: #1e293b;
    line-height: 1.3;
}
.fv-ltx-sku {
    margin: 0;
    font-size: 0.82rem;
    color: #64748b;
    font-weight: 600;
}
.fv-ltx-facts {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.45rem 0.85rem;
    margin: 0.65rem 0 0;
}
.fv-ltx-fact { margin: 0; min-width: 0; }
.fv-ltx-fact dt {
    margin: 0;
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: #9f1239;
}
.fv-ltx-fact dd {
    margin: 0.05rem 0 0;
    font-size: 0.88rem;
    font-weight: 600;
    color: #334155;
    word-break: break-word;
}
.fv-ltx-desc {
    margin-top: 0.75rem;
    padding-top: 0.65rem;
    border-top: 1px dashed #fecaca;
    font-size: 0.82rem;
    color: #475569;
    line-height: 1.45;
    white-space: pre-wrap;
}
.fv-ltx-edit {
    padding: 1rem;
    background: #fff;
    border: 1px solid var(--fv-modal-border, #fecaca);
    border-radius: 0.75rem;
}
.fv-ltx-edit__title {
    margin: 0 0 0.85rem;
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #7f1d1d;
}
.fv-ltx-groups-row {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    gap: 0.65rem;
}
#modal_product_list_taxonomy .fv-product-cat-groups-search-btn {
    width: 2.25rem;
    height: 2.25rem;
    padding: 0 !important;
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
    border-radius: 0.5rem !important;
    flex-shrink: 0;
    color: #dc2626 !important;
    border-color: #dc2626 !important;
    background: #fff !important;
}
#modal_product_list_taxonomy .fv-product-cat-groups-search-btn .mdi {
    font-size: 1.15rem;
    line-height: 1;
}
#modal_product_list_taxonomy .fv-product-cat-groups-search-btn:hover {
    background: #fee2e2 !important;
    color: #b91c1c !important;
}
.fv-ltx-groups-summary {
    flex: 1 1 12rem;
    min-width: 0;
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    align-items: center;
    font-size: 0.86rem;
    color: #475569;
    line-height: 1.4;
}
#modal_product_list_taxonomy .fv-cat-groups-summary__chip {
    display: inline-flex;
    align-items: center;
    padding: 0.2rem 0.55rem;
    border-radius: 999px;
    background: #fff1f2;
    border: 1px solid #fecaca;
    color: #9f1239;
    font-size: 0.78rem;
    font-weight: 600;
}
.fv-list-taxonomy-trigger {
    cursor: pointer;
    border: 0;
    background: transparent;
    padding: 0;
    text-align: left;
    width: 100%;
}
.fv-list-taxonomy-trigger:hover .fv-category-avatars,
.fv-list-taxonomy-trigger:hover .fv-product-list-groups,
.fv-list-taxonomy-trigger:focus .fv-category-avatars,
.fv-list-taxonomy-trigger:focus .fv-product-list-groups {
    outline: 2px solid rgba(220, 53, 69, 0.35);
    outline-offset: 2px;
    border-radius: 8px;
}
.fv-list-taxonomy-empty {
    color: #6c757d;
    font-size: 0.85rem;
}
.fv-list-taxonomy-edit-hint {
    display: inline-flex;
    align-items: center;
    margin-left: 0.35rem;
    color: #dc3545;
    opacity: 0.75;
    font-size: 0.95rem;
    vertical-align: middle;
}
.fv-list-taxonomy-trigger:hover .fv-list-taxonomy-edit-hint { opacity: 1; }
@media (max-width: 575.98px) {
    .fv-ltx-card { flex-direction: column; align-items: center; text-align: center; }
    .fv-ltx-card__topline { justify-content: center; }
    .fv-ltx-facts { grid-template-columns: 1fr; text-align: left; }
}
</style>
