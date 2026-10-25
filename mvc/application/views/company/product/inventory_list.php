<?php
/**
 * @var array $pageTexts
 * @var array $toltipLang
 * @var array $items
 * @var int $total
 * @var string $search
 * @var string $stockFilter
 * @var array $selectedCategoryIds
 * @var array $categories
 * @var int $perPage
 * @var string $paginate
 * @var string $apiError
 * @var string $movementsUrl
 * @var string $viewUrl
 * @var string $editUrl
 * @var string $listUrl
 * @var string $exportUrl
 * @var array $exportHeadings
 */
$t = static function (string $key, string $fallback = '') use ($pageTexts): string {
    $value = $pageTexts[$key] ?? $fallback;

    return is_string($value) ? $value : $fallback;
};
$selectedCategoryIds = is_array($selectedCategoryIds ?? null) ? $selectedCategoryIds : [];
$categories = is_array($categories ?? null) ? $categories : [];
$stockFilter = in_array(($stockFilter ?? 'all'), ['all', 'with_stock', 'without_stock'], true) ? $stockFilter : 'all';
?>
<link rel="stylesheet" href="<?= dated_file_url('companyasset/company/product/inventory-list.css') ?>" />
<script>
window.fvProductInventoryLang = <?= json_encode($pageTexts ?? [], JSON_UNESCAPED_UNICODE) ?>;
window.fvProductInventoryConfig = <?= json_encode([
    'movementsUrl' => $movementsUrl ?? '',
    'exportUrl' => $exportUrl ?? '',
    'exportHeadings' => $exportHeadings ?? [],
    'exportHeadingsPdf' => $exportHeadingsPdf ?? ($exportHeadings ?? []),
    'exportPdfLabels' => $exportPdfLabels ?? [],
    'currencySymbol' => $format_currency ?? '',
], JSON_UNESCAPED_UNICODE) ?>;
</script>

<div class="container-fluid page-body-wrapper pt-3">
    <div class="main-panel">
        <div class="container-fluid">
            <?php $this->load->view('components/crud/page_heading', [
                'pageTitle' => $t('inventory_page_title', 'Inventory'),
                'pageIcon' => 'fa-solid fa-clipboard-list',
            ]); ?>
            <p class="fv-page-subtitle text-muted small mb-3">
                <?= htmlspecialchars(sprintf($t('inventory_total_found', '%d products found'), (int) $total), ENT_QUOTES, 'UTF-8') ?>
            </p>

            <?php if (!empty($apiError)) : ?>
                <div class="alert alert-danger"><?= htmlspecialchars((string) $apiError, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <form method="get" action="<?= htmlspecialchars($listUrl, ENT_QUOTES, 'UTF-8') ?>" id="fv-inventory-filter-form">
                <div class="fv-inventory-layout">
                    <aside class="fv-inventory-sidebar">
                        <div class="fv-inventory-sidebar__card p-3">
                            <h2 class="fv-inventory-sidebar__title">
                                <?= htmlspecialchars($t('inventory_filter_categories_title', 'Categories'), ENT_QUOTES, 'UTF-8') ?>
                            </h2>
                            <div class="fv-inventory-sidebar__actions">
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="fv-inventory-cat-select-all">
                                    <?= htmlspecialchars($t('inventory_filter_categories_select_all', 'Select all'), ENT_QUOTES, 'UTF-8') ?>
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="fv-inventory-cat-clear">
                                    <?= htmlspecialchars($t('inventory_filter_categories_clear', 'Clear'), ENT_QUOTES, 'UTF-8') ?>
                                </button>
                            </div>
                            <?php if ($categories === []) : ?>
                                <p class="text-muted small mb-0">
                                    <?= htmlspecialchars($t('inventory_filter_categories_empty', 'No categories available'), ENT_QUOTES, 'UTF-8') ?>
                                </p>
                            <?php else : ?>
                                <ul class="fv-inventory-category-list">
                                    <?php foreach ($categories as $category) :
                                        $category = is_object($category) ? $category : (object) $category;
                                        $catId = (int) ($category->cat_id ?? 0);
                                        if ($catId < 1) {
                                            continue;
                                        }
                                        $checked = in_array($catId, $selectedCategoryIds, true);
                                        ?>
                                        <li class="fv-inventory-category-item">
                                            <?php
                                            $catUrls = fv_category_image_urls($category);
                                            $catName = (string) ($category->category_name ?? '');
                                            $hasCustomImage = ! empty($catUrls['has_image']);
                                            $fallbackJson = htmlspecialchars(
                                                json_encode(array_values($catUrls['fallbacks'] ?? []), JSON_UNESCAPED_SLASHES),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            );
                                            ?>
                                            <label>
                                                <input type="checkbox"
                                                       name="categories[]"
                                                       value="<?= $catId ?>"
                                                       <?= $checked ? 'checked' : '' ?>>
                                                <span class="fv-inventory-category-item__avatar">
                                                    <img src="<?= htmlspecialchars($catUrls['thumb'], ENT_QUOTES, 'UTF-8') ?>"
                                                         alt="<?= htmlspecialchars($catName, ENT_QUOTES, 'UTF-8') ?>"
                                                         class="fv-inventory-category-item__img<?= $hasCustomImage ? '' : ' fv-inventory-category-item__img--placeholder' ?>"
                                                         loading="lazy"
                                                         decoding="async"
                                                         <?php if ($hasCustomImage) : ?>
                                                         data-fv-img-fallbacks="<?= $fallbackJson ?>"
                                                         onerror="(function(img){var list=[];try{list=JSON.parse(img.getAttribute('data-fv-img-fallbacks')||'[]');}catch(e){}var i=parseInt(img.getAttribute('data-fv-img-i')||'0',10);if(i<list.length){img.setAttribute('data-fv-img-i',String(i+1));img.src=list[i];return;}img.classList.add('fv-inventory-category-item__img--placeholder');})(this);"
                                                         <?php endif; ?>>
                                                </span>
                                                <span class="fv-inventory-category-item__name"><?= htmlspecialchars($catName, ENT_QUOTES, 'UTF-8') ?></span>
                                                <span class="fv-inventory-category-item__count">(<?= (int) ($category->products_count ?? 0) ?>)</span>
                                            </label>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </aside>

                    <div class="fv-inventory-main">
                        <div class="card fv-inventory-filters__card mb-3">
                            <div class="card-body py-3">
                                <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                                    <div class="mb-2">
                                        <span class="small text-muted d-block mb-1">
                                            <?= htmlspecialchars($t('inventory_filter_stock_label', 'Stock'), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                        <div class="fv-inventory-stock-filter btn-group" role="group" aria-label="<?= htmlspecialchars($t('inventory_filter_stock_label', 'Stock'), ENT_QUOTES, 'UTF-8') ?>">
                                            <?php
                                            $stockOptions = [
                                                'all' => $t('inventory_filter_stock_all', 'All'),
                                                'with_stock' => $t('inventory_filter_stock_with', 'With stock'),
                                                'without_stock' => $t('inventory_filter_stock_without', 'Without stock'),
                                            ];
                                            foreach ($stockOptions as $value => $label) :
                                                ?>
                                                <button type="button"
                                                        class="btn btn-outline-danger fv-inventory-stock-btn <?= $stockFilter === $value ? 'active' : '' ?>"
                                                        data-stock-filter="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>">
                                                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                                                </button>
                                            <?php endforeach; ?>
                                        </div>
                                        <input type="hidden" name="stock_filter" id="fv-inventory-stock-filter" value="<?= htmlspecialchars($stockFilter, ENT_QUOTES, 'UTF-8') ?>">
                                    </div>
                                    <div class="fv-inventory-export-group mb-2">
                                        <button type="button" class="btn fv-crud-btn btn-danger" data-export-type="excel" id="fv-inventory-export-excel">
                                            <i class="mdi mdi-file-excel"></i>
                                            <?= htmlspecialchars($t('inventory_export_excel', 'Export Excel'), ENT_QUOTES, 'UTF-8') ?>
                                        </button>
                                        <button type="button" class="btn fv-crud-btn btn-danger" data-export-type="pdf" id="fv-inventory-export-pdf">
                                            <i class="mdi mdi-file-pdf-box"></i>
                                            <?= htmlspecialchars($t('inventory_export_pdf', 'Export PDF'), ENT_QUOTES, 'UTF-8') ?>
                                        </button>
                                    </div>
                                </div>

                                <div class="form-inline flex-wrap">
                                    <div class="form-group mr-2 mb-2">
                                        <label class="sr-only" for="fv-inventory-search"><?= htmlspecialchars($t('caja_text_search', 'Search'), ENT_QUOTES, 'UTF-8') ?></label>
                                        <input type="search"
                                               id="fv-inventory-search"
                                               name="q"
                                               class="form-control"
                                               value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
                                               placeholder="<?= htmlspecialchars($t('inventory_search_placeholder', 'Search by SKU or name'), ENT_QUOTES, 'UTF-8') ?>">
                                    </div>
                                    <div class="form-group mr-2 mb-2">
                                        <select name="per_page" class="form-control">
                                            <?php foreach ([10, 25, 50, 100] as $opt) : ?>
                                                <option value="<?= $opt ?>" <?= (int) $perPage === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn fv-crud-btn btn-primary mb-2 mr-2">
                                        <i class="mdi mdi-magnify"></i>
                                        <?= htmlspecialchars($t('btn_filter', 'Filter'), ENT_QUOTES, 'UTF-8') ?>
                                    </button>
                                    <?php if ($search !== '' || $stockFilter !== 'all' || $selectedCategoryIds !== []) : ?>
                                        <a href="<?= htmlspecialchars($listUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn fv-crud-btn btn-outline-secondary mb-2">
                                            <?= htmlspecialchars($t('btn_clear_filter', 'Clear'), ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="card fv-inventory-table-card">
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0 fv-crud-table fv-inventory-table" id="fv-product-inventory-table">
                                        <thead>
                                        <tr>
                                            <th class="fv-inventory-col-image" aria-label="<?= htmlspecialchars($t('field_productimage', 'Image'), ENT_QUOTES, 'UTF-8') ?>"></th>
                                            <th><?= htmlspecialchars($t('inventory_col_product', 'Product'), ENT_QUOTES, 'UTF-8') ?></th>
                                            <th class="text-right"><?= htmlspecialchars($t('field_sale_price', 'Sale price'), ENT_QUOTES, 'UTF-8') ?></th>
                                            <th class="text-right"><?= htmlspecialchars($t('inventory_col_stock', 'Inventory'), ENT_QUOTES, 'UTF-8') ?></th>
                                            <th class="text-center crud-list-actions-column fv-crud-actions-column" scope="col" aria-label="<?= htmlspecialchars($t('colmn_action', 'Actions'), ENT_QUOTES, 'UTF-8') ?>"></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php if ($items === []) : ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-5 fv-inventory-empty">
                                                    <i class="mdi mdi-package-variant-closed mb-2 d-block" style="font-size:2rem;opacity:.35;"></i>
                                                    <?= htmlspecialchars($t('inventory_no_results', 'No products found'), ENT_QUOTES, 'UTF-8') ?>
                                                </td>
                                            </tr>
                                        <?php else :
                                            $fmt = is_object($format_numeric ?? null) ? $format_numeric : null;
                                            $radixPoint = $fmt->radixPoint ?? '.';
                                            $groupSeparator = $fmt->groupSeparator ?? ',';
                                            $digits = (int) ($fmt->digits ?? 2);
                                            foreach ($items as $row) :
                                                $row = (object) $row;
                                                $proId = (int) ($row->pro_id ?? 0);
                                                $salePrice = number_format((float) ($row->sale_price ?? 0), $digits, $radixPoint, $groupSeparator);
                                                $inventoryQty = number_format((float) ($row->inventory_qty ?? 0), $digits, $radixPoint, $groupSeparator);
                                                $unitLabel = fv_product_unit_label($row);
                                                $sku = (string) ($row->sku ?? '');
                                                $name = (string) ($row->name ?? '');
                                                $qtyClass = (float) ($row->inventory_qty ?? 0) <= 0 ? ' fv-inventory-qty-pill--empty' : '';
                                                ?>
                                                <tr class="fv-inventory-row" data-pro-id="<?= $proId ?>"
                                                    data-sku="<?= htmlspecialchars($sku, ENT_QUOTES, 'UTF-8') ?>"
                                                    data-name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>">
                                                    <td class="fv-inventory-col-image">
                                                        <?php $this->load->view('company/product/inventory_thumb', ['listItem' => $row, 'size' => 52]); ?>
                                                    </td>
                                                    <td class="fv-inventory-col-product">
                                                        <div class="fv-inventory-product">
                                                            <div class="fv-inventory-product__name" title="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>">
                                                                <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>
                                                            </div>
                                                            <?php if ($sku !== '') : ?>
                                                                <div class="fv-inventory-product__sku"><?= htmlspecialchars($sku, ENT_QUOTES, 'UTF-8') ?></div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                    <td class="text-right fv-inventory-col-price">
                                                        <span class="fv-inventory-price"><?= htmlspecialchars($salePrice, ENT_QUOTES, 'UTF-8') ?></span>
                                                    </td>
                                                    <td class="text-right fv-inventory-col-qty">
                                                        <div class="fv-inventory-qty-pill<?= $qtyClass ?>">
                                                            <?php if ($unitLabel !== '') : ?>
                                                                <span class="fv-inventory-qty-pill__unit"><?= htmlspecialchars($unitLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                                            <?php endif; ?>
                                                            <span class="fv-inventory-qty-pill__value"><?= htmlspecialchars($inventoryQty, ENT_QUOTES, 'UTF-8') ?></span>
                                                        </div>
                                                    </td>
                                                    <td class="text-center text-nowrap fv-inventory-col-actions">
                                                        <div class="d-flex justify-content-center align-items-center fv-crud-row-actions">
                                                        <a href="<?= htmlspecialchars($viewUrl.$proId, ENT_QUOTES, 'UTF-8') ?>"
                                                           class="btn fv-crud-btn fv-crud-btn--row btn-sm btn-primary"
                                                           title="<?= htmlspecialchars((string) ($toltipLang['view'] ?? 'View'), ENT_QUOTES, 'UTF-8') ?>">
                                                            <i class="mdi mdi-eye"></i>
                                                        </a>
                                                        <a href="<?= htmlspecialchars($editUrl.$proId, ENT_QUOTES, 'UTF-8') ?>"
                                                           class="btn fv-crud-btn fv-crud-btn--row btn-sm btn-warning"
                                                           title="<?= htmlspecialchars((string) ($toltipLang['edit'] ?? 'Edit'), ENT_QUOTES, 'UTF-8') ?>">
                                                            <i class="mdi mdi-pencil"></i>
                                                        </a>
                                                        <button type="button"
                                                                class="btn fv-crud-btn fv-crud-btn--row btn-sm btn-info fv-inventory-submayor-btn"
                                                                data-pro-id="<?= $proId ?>"
                                                                title="<?= htmlspecialchars($t('action_submayor', 'Submayor'), ENT_QUOTES, 'UTF-8') ?>">
                                                            <i class="mdi mdi-book-open-page-variant"></i>
                                                        </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <?php if ($paginate !== '') : ?>
                                <div class="card-footer d-flex justify-content-end">
                                    <?= $paginate ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="fv-inventory-submayor-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="fv-inventory-submayor-title"><?= htmlspecialchars($t('submayor_modal_title', 'Inventory movements'), ENT_QUOTES, 'UTF-8') ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="<?= htmlspecialchars($t('btn-modal-cancelar', 'Close'), ENT_QUOTES, 'UTF-8') ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <div id="fv-inventory-submayor-loader" class="text-center py-5 d-none">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 mb-0 text-muted"><?= htmlspecialchars($t('submayor_loading', 'Loading movements...'), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div id="fv-inventory-submayor-error" class="alert alert-danger m-3 d-none"></div>
                <div id="fv-inventory-submayor-content" class="d-none">
                    <div class="px-3 py-2 border-bottom bg-light small" id="fv-inventory-submayor-summary"></div>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead class="thead-light">
                            <tr>
                                <th><?= htmlspecialchars($t('submayor_col_date', 'Date'), ENT_QUOTES, 'UTF-8') ?></th>
                                <th><?= htmlspecialchars($t('submayor_col_document', 'Document'), ENT_QUOTES, 'UTF-8') ?></th>
                                <th><?= htmlspecialchars($t('submayor_col_type', 'Type'), ENT_QUOTES, 'UTF-8') ?></th>
                                <th class="text-right"><?= htmlspecialchars($t('submayor_col_qty_in', 'In'), ENT_QUOTES, 'UTF-8') ?></th>
                                <th class="text-right"><?= htmlspecialchars($t('submayor_col_qty_out', 'Out'), ENT_QUOTES, 'UTF-8') ?></th>
                                <th class="text-right"><?= htmlspecialchars($t('submayor_col_balance', 'Balance'), ENT_QUOTES, 'UTF-8') ?></th>
                                <th class="text-right"><?= htmlspecialchars($t('submayor_col_price', 'Price'), ENT_QUOTES, 'UTF-8') ?></th>
                                <th><?= htmlspecialchars($t('submayor_col_comments', 'Comments'), ENT_QUOTES, 'UTF-8') ?></th>
                            </tr>
                            </thead>
                            <tbody id="fv-inventory-submayor-tbody"></tbody>
                        </table>
                    </div>
                    <p id="fv-inventory-submayor-empty" class="text-center text-muted py-4 d-none mb-0">
                        <?= htmlspecialchars($t('submayor_no_movements', 'No movements recorded for this product'), ENT_QUOTES, 'UTF-8') ?>
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <?= htmlspecialchars($t('btn-modal-cancelar', 'Close'), ENT_QUOTES, 'UTF-8') ?>
                </button>
            </div>
        </div>
    </div>
</div>
