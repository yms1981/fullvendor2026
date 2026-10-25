<?php
/**
 * @var array $pageTexts The page texts/translations
 * @var array $filterData The current filter data for each item of this filter form
 * @var array $locations The list of locations present on company products
 * @var array $categorylist The list of categories of the company (for the select options)
 * @var int $pag the page number
 * @var int|null $listCompanyId company id (listado productos) para persistencia de selección en cliente
 */

?>
<style>
    .filter-select {
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        padding: 0.5rem 0.75rem;
        background-color: #fff;
        transition: all 0.15s ease-in-out;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }
    .filter-select:hover {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.1);
    }
    .filter-select:focus {
        border-color: #86b7fe;
        outline: 0;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
    .search-input-group {
        display: flex;
        align-items: stretch;
    }
    .search-input-group .form-control {
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }
    .search-input-group .btn-search {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
        border-left: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0.5rem 1rem;
        background-color: #f8f9fa;
        border-color: #ced4da;
        transition: all 0.15s ease-in-out;
    }
    .search-input-group .btn-search:hover {
        background-color: #e9ecef;
        border-color: #adb5bd;
    }
    .search-input-group .btn-search:focus {
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
    /* Estilos para flechitas de despliegue en selects */
    .filter-select-wrapper {
        position: relative;
        display: block;
    }
    .filter-select-wrapper select.filter-select {
        padding-right: 30px;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
    }
    .filter-select-arrow {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        pointer-events: none;
        color: #6c757d;
        font-size: 1rem;
        z-index: 1;
    }
    .filter-select-wrapper:hover .filter-select-arrow {
        color: #495057;
    }
    .filter-select-wrapper select.filter-select:focus + .filter-select-arrow {
        color: #86b7fe;
    }
</style>
<div class="col-12 mb-4">

    <div class="row">
    
        <form method="get" id="form-buscar-filtro"
              class="col-12 pt-2"
              action="<?= company_url('products') ?>"
              data-fv-products-selection="1"
              data-list-company-id="<?= (int) ($listCompanyId ?? 0) ?>">
            <div class="form-group mb-3">
                <label class="fw-bold mb-2" style="font-size:1.1em;"><?= $pageTexts['seleccionar_por'] ?></label>
            </div>
            <div class="row g-3">
                <div class="col-lg-2 col-md-4 col-sm-6 col-12">
                    <div class="form-group mb-0">
                        <label class="mb-1"><?= $pageTexts['colmn_status_filtro'] ?></label>
                        <div class="filter-select-wrapper">
                            <select class="form-control filter-select notranslate"
                                    name="prdstatus" id="prdstatus"
                                    onchange="buscar_porfiltro()">
                                <?php
                                $prdStatusSelected = array_key_exists('prdstatus', $filterData) && $filterData['prdstatus'] !== null && $filterData['prdstatus'] !== ''
                                    ? (string) $filterData['prdstatus']
                                    : '1';
                                ?>
                                <option
                                    <?= $prdStatusSelected === '3' ? 'selected' : '' ?>
                                    value="3">
                                    <?= strtoupper($pageTexts['columnAll']) ?>
                                </option>
                                <option
                                    <?= $prdStatusSelected === '1' ? 'selected' : '' ?>
                                    value="1">
                                    <?= strtoupper(
                                        $pageTexts['select_actives']
                                    ) ?>
                                </option>
                                <option
                                    <?= $prdStatusSelected === '0' ? 'selected' : '' ?>
                                    value="0">
                                    <?= strtoupper(
                                        $pageTexts['select_desactives']
                                    ) ?>
                                </option>
                            </select>
                            <i class="mdi mdi-chevron-down filter-select-arrow"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6 col-12">
                    <div class="form-group mb-0">
                        <label class="mb-1"><?= $pageTexts['srchTip2'] ?></label>
                        <!-- filtro categoria -->
                        <div class="filter-select-wrapper fv-category-filter-wrap">
                            <select class="form-control filter-select notranslate fv-category-filter-select"
                                    name="categoryId" id="categoryId">

                                <option
                                    <?= ($filterData['category'] == 'todo' || empty($filterData['category'])) ? 'selected' : '' ?>
                                    value="todo">
                                    <?= strtoupper($pageTexts['columnAll']) ?>
                                </option>
                                <option
                                    <?= $filterData['category'] == '-1' ? 'selected' : '' ?>
                                    value="-1">
                                    <?= strtoupper($pageTexts['without_category']) ?>
                                </option>

                                <?php
                                foreach ($categorylist as $catlist) {
                                    $catlist = is_object($catlist) ? $catlist : (object) (array) $catlist;
                                    $catUrls = fv_category_image_urls($catlist);
                                    $catName = (string) ($catlist->category_name ?? '');
                                    $catFallbacks = htmlspecialchars(
                                        json_encode(array_values($catUrls['fallbacks'] ?? []), JSON_UNESCAPED_SLASHES),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                    ?>
                                    <option
                                        <?= ($filterData['category'] ?? null) == ($catlist->cat_id ?? null) ? 'selected' : '' ?>
                                        value="<?= (int) ($catlist->cat_id ?? 0) ?>"
                                        data-cat-thumb="<?= htmlspecialchars($catUrls['thumb'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-cat-preview="<?= htmlspecialchars($catUrls['preview'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-cat-fallbacks="<?= $catFallbacks ?>"
                                        data-cat-name="<?= htmlspecialchars($catName, ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars(strtoupper($catName), ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                    <?php
                                } ?>
                            </select>
                            <i class="mdi mdi-chevron-down filter-select-arrow"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6 col-12">
                    <div class="form-group mb-0">
                        <label class="mb-1"><?= $pageTexts['column_filtro_imagenes'] ?></label>
                        <div class="filter-select-wrapper">
                            <select type="text" class="form-control filter-select myselect"
                                    name="filtro_foto" id="filtro_foto"
                                    onchange="buscar_porfiltro()">
                                <option
                                    <?= (empty($filterData['photo']) || $filterData['photo'] != null && $filterData['photo'] == 'with_photo_whithout_photo') ? 'selected' : '' ?>
                                    value="with_photo_whithout_photo">
                                    <?= strtoupper(
                                        $pageTexts['option_con_sin_foto']
                                    ) ?>
                                </option>
                                <option
                                    <?= ($filterData['photo'] == 'whith_photo') ? 'selected' : '' ?>
                                    value="whith_photo">
                                    <?= strtoupper(
                                        $pageTexts['option_con_foto']
                                    ) ?>
                                </option>
                                <option
                                    <?= $filterData['photo'] == 'whithout_photo' ? 'selected' : '' ?>
                                    value="whithout_photo">
                                    <?= strtoupper(
                                        $pageTexts['option_sin_foto']
                                    ) ?>
                                </option>
                            </select>
                            <i class="mdi mdi-chevron-down filter-select-arrow"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6 col-12">
                    <!-- filtro tag -->
                    <div class="form-group mb-0">
                        <label class="mb-1"><?= $pageTexts['column_filtro_tag'] ?></label>
                        <input onkeyup="submit_filtro($(this).val(),event)"
                               type="text"
                               value="<?= $filterData['tag'] != null ? $filterData['tag'] : '' ?>"
                               placeholder="<?= $pageTexts['filter_tag_placeholder'] ?>"
                               class="form-control filter-select myselect"
                               name="filtro_tag">
                    </div>
                </div>
                <?php
                $locationOptions = array_values(array_filter(array_map(static function ($loc) {
                    return trim((string) $loc);
                }, is_array($locations ?? null) ? $locations : []), static function ($loc) {
                    return $loc !== '';
                }));
                if (count($locationOptions) > 0) : ?>
                <div class="col-lg-2 col-md-4 col-sm-6 col-12">
                    <!-- filtro ubicacion -->
                    <div class="form-group mb-0">
                        <label class="mb-1"><?= $pageTexts['column_filtro_ubicacion'] ?></label>
                        <div class="filter-select-wrapper">
                            <select name="filtro_ubicacion"
                                    class="form-control filter-select myselect notranslate"
                                    onchange="buscar_porfiltro()"
                                    id="filtro_ubicacion">
                                <option value=""
                                    <?= ($filterData['location'] ?? '') === '' || ($filterData['location'] ?? '') === null ? 'selected' : '' ?>>
                                    <?= strtoupper((string) ($pageTexts['columnAll'] ?? '')) ?>
                                </option>
                                <?php foreach ($locationOptions as $ubicacion) :
                                    $selected = ((string) ($filterData['location'] ?? '')) === $ubicacion;
                                    ?>
                                    <option value="<?= htmlspecialchars($ubicacion, ENT_QUOTES, 'UTF-8') ?>"
                                        <?= $selected ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($ubicacion, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <i class="mdi mdi-chevron-down filter-select-arrow"></i>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <div class="col-lg-2 col-md-4 col-sm-6 col-12">
                    <!-- filtro catalogo -->
                    <div class="form-group mb-0">
                        <label class="mb-1"><?= $pageTexts['column_filtro_catalog'] ?></label>
                        <div class="filter-select-wrapper">
                            <select type="text" class="form-control filter-select myselect"
                                    name="filtro_catalog"
                                    onchange="buscar_porfiltro()">
                                <option
                                    <?= (isset($pag) && $pag != '') ? ($filterData['catalog'] == 'todo' ? 'selected' : '') : 'selected' ?>
                                    value="todo">
                                    <?= strtoupper($pageTexts['columnAll']) ?>
                                </option>
                                <?php
                                foreach ($cataloglist as $catlist) { ?>
                                    <option
                                        <?= $filterData['catalog'] != null ? $filterData['catalog'] == $catlist->catalog_id ? 'selected' : '' : '' ?>
                                        value="<?= $catlist->catalog_id ?>">
                                        <?= strtoupper(
                                            $catlist->catalog_name
                                        ) ?>
                                    </option>
                                    <?php
                                } ?>
                            </select>
                            <i class="mdi mdi-chevron-down filter-select-arrow"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6 col-12">
                    <div class="form-group mb-0">
                        <label class="mb-1"><?= $pageTexts['caja_text_search'] ?></label>
                        <div class="search-input-group">
                            <input type="text" id="text_buscar_caracter"
                                   name="prsearch"
                                   class="form-control filter-select"
                                   placeholder="<?= $pageTexts['caja_text_search'] ?>"
                                   value="<?= !empty($filterData['terms']) ? htmlentities(implode(' ',$filterData['terms'])) : '' ?>"
                                   onkeydown="if (event.key === 'Enter') { event.preventDefault(); buscar_porfiltro(); }"
                                   style="flex: 1;">
                            <button id="btn-submit-textsearch" 
                                    class="btn btn-search" 
                                    type="submit"
                                    onclick="buscar_porfiltro(); return false;">
                                <i class="fa-sharp fa-solid fa-magnifying-glass"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <input hidden id="order_type" name="order_type"
                   value="<?= isset($order_type_general) && $order_type_general != '' ? htmlspecialchars($order_type_general) : '' ?>">
            <input hidden id="order_sort" name="sort"
                   value="<?= htmlspecialchars((string) (($filterData['sort'] ?? '') !== '' ? $filterData['sort'] : 'modified_on'), ENT_QUOTES, 'UTF-8') ?>">
            <input hidden id="sort_direction" name="sort_direction"
                   value="<?= htmlspecialchars((string) (($filterData['orderDirection'] ?? '') !== '' ? $filterData['orderDirection'] : 'desc'), ENT_QUOTES, 'UTF-8') ?>">
            <input id="input-todos" hidden name="input_todos"
                   value="<?= (isset($filterData['input_todos']) && $filterData['input_todos'] === 'todos') ? 'todos' : 'pagina' ?>">
        </form>
    </div>
</div>
