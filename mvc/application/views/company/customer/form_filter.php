<?php
/**
 * @var array $pageTexts The page texts/translations
 * @var array $filterData The current filter data for each item of this filter form
 * @var int $pag the page number
 * @var array $groupList The list of groups
 * @var array $sellerList The list of sellers
 * @var array $termList The list of terms
 * @var array $countryList The list of countries
 * @var array $stateList The list of states
 * @var string $search The search string
 */
?>
<style>
.filter-form-container {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.filter-form-container .form-group label {
    font-weight: 600;
    font-size: 0.875rem;
    color: #495057;
    margin-bottom: 0.5rem;
    display: block;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.filter-form-container .form-control {
    border: 1px solid #ced4da;
    border-radius: 6px;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    transition: all 0.2s ease;
    background-color: #fff;
    height: calc(1.5em + 0.75rem + 2px);
}

.filter-form-container .form-control:focus {
    border-color: #80bdff;
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    background-color: #fff;
}

.filter-form-container select.form-control {
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 16px 12px;
    padding-right: 2.5rem;
}

.filter-form-container select.form-control option {
    padding: 0.5rem;
    background-color: #fff;
    color: #212529;
}

.filter-form-container select.form-control option:hover {
    background-color: #f8f9fa;
}

.filter-form-title {
    position: relative;
    top: -0.7em;
    display: inline-block;
    background-color: #f8f9fa;
    font-weight: 600;
    padding: 0.25rem 0.75rem;
    margin-left: 0.5em;
    font-size: 1.1em;
    color: #495057;
    border-radius: 4px;
}
</style>

<div class="col-12 p-4">
    <div class="row border filter-form-container">
        <label class="filter-form-title"><?= $pageTexts['seleccionar_por'] ?></label>
        <form class="col-12" id="form-buscar-filtro" method="get"
              action="<?= company_url('customers') ?>">
            <div class="row">
                <div class="col-lg-2 col-md-6 col-xl-2 col-sm-12">
                    <div class="form-group">
                        <label><?= $pageTexts['colmn_status_filtro'] ?? 'Enabled/Disabled' ?></label>
                        <!-- filtro status -->
                        <?php $customerStatusSelected = isset($filterData['status']) ? (string) $filterData['status'] : '1'; ?>
                        <select class="form-control notranslate" name="status"
                                onchange="buscar_porfiltro()">
                            <option value="todo" <?= in_array($customerStatusSelected, ['todo', 'all'], true) ? 'selected' : '' ?>>
                                ALL
                            </option>
                            <option value="1" <?= $customerStatusSelected === '1' ? 'selected' : '' ?>>
                                <?= strtoupper($pageTexts['select_actives'] ?? 'Enabled') ?>
                            </option>
                            <option value="0" <?= $customerStatusSelected === '0' ? 'selected' : '' ?>>
                                <?= strtoupper($pageTexts['select_desactives'] ?? 'Disabled') ?>
                            </option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6 col-xl-2 col-sm-12">
                    <div class="form-group">
                        <label><?= $pageTexts['grupos'] ?></label>
                        <!-- filtro grupo -->
                        <select class="form-control notranslate" name="grupoId"
                                onchange="buscar_porfiltro()">
                            <option
                                <?= $filterData['group'] != '' ? $filterData['group'] == 'todo' ? 'selected' : '' : 'selected' ?>
                                value="todo"><?= strtoupper($pageTexts['todo']) ?></option>
                            <option
                                <?= $filterData['group'] != '' ? $filterData['group'] == '-1' ? 'selected' : '' : '' ?>
                                value="-1"><?= strtoupper($pageTexts['option_vacio']) ?>
                            </option>
                            <?php foreach ($groupList as $group) { ?>
                                <option
                                    <?= $filterData['group'] != null ? $filterData['group'] == $group->group_id ? 'selected' : '' : '' ?>
                                    value="<?= $group->group_id ?>"><?= htmlspecialchars(function_exists('mb_strtoupper') ? mb_strtoupper((string) ($group->name ?? ''), 'UTF-8') : strtoupper((string) ($group->name ?? '')), ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php } ?>
                        </select>

                    </div>
                </div>
                <div class="col-lg-2 col-md-6 col-xl-2 col-sm-12">
                    <div class="form-group">
                        <label><?= $pageTexts['vendedores'] ?></label>
                        <!-- filtro vendedores -->
                        <select class="form-control notranslate" name="vendedorId"
                                onchange="buscar_porfiltro()">
                            <option
                                <?= $filterData['seller'] != '' ? $filterData['seller'] == 'todo' ? 'selected' : '' : 'selected' ?>
                                value="todo"><?= strtoupper($pageTexts['todo']) ?></option>
                            <option
                                <?= $filterData['seller'] != '' ? $filterData['seller'] == '-1' ? 'selected' : '' : '' ?>
                                value="-1"><?= strtoupper($pageTexts['option_vacio']) ?>
                            </option>
                            <?php foreach ($sellerList as $seller) { ?>
                                <option
                                    <?= $filterData['seller'] != null ? $filterData['seller'] == $seller->id ? 'selected' : '' : '' ?>
                                    value="<?= $seller->id ?>">
                                    <?= htmlspecialchars(function_exists('mb_strtoupper') ? mb_strtoupper((string) ($seller->user->name ?? ''), 'UTF-8') : strtoupper((string) ($seller->user->name ?? '')), ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6 col-xl-2 col-sm-12">
                    <div class="form-group">
                        <label><?= $pageTexts['Termino_Pago'] ?></label>
                        <!-- filtro termino de pago -->
                        <select class="form-control notranslate" name="termId"
                                onchange="buscar_porfiltro()">
                            <option
                                <?= $filterData['term'] != '' ? $filterData['term'] == 'todo' ? 'selected' : '' : 'selected' ?>
                                value="todo"><?= strtoupper($pageTexts['todo']) ?></option>
                            <option
                                <?= $filterData['term'] != '' ? $filterData['term'] == '-1' ? 'selected' : '' : '' ?>
                                value="-1"><?= strtoupper($pageTexts['option_vacio']) ?>
                            </option>
                            <?php foreach ($termList as $term) { ?>
                                <option
                                    <?= $filterData['term'] != null ? $filterData['term'] == $term->term_id ? 'selected' : '' : '' ?>
                                    value="<?= $term->term_id ?>"><?= strtoupper($term->name) ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="col-lg-2 col-md-12 col-xl-2 col-sm-12">

                    <div class="form-group">
                        <label>&nbsp;</label>
                        <div class="input-group mb-3">
                            <input type="text" name="prsearch"
                                   onkeydown="if (event.key === 'Enter') { event.preventDefault(); buscar_porfiltro(); }"
                                   class="form-control"
                                   placeholder="<?= htmlspecialchars($pageTexts['caja_text_search'] ?? ($pageTexts['search'] ?? 'Search'), ENT_QUOTES, 'UTF-8') ?>"
                                   value="<?= $filterData['search'] != null ? htmlspecialchars((string) $filterData['search'], ENT_QUOTES, 'UTF-8') : '' ?>">
                            <button type="submit" id="btn-submit-textsearch" class="btn btn-light"
                                    onclick="buscar_porfiltro(); return false;">
                                <span id="basic-addon1"><i class="fa-sharp fa-solid fa-magnifying-glass"></i></span>
                            </button>
                        </div>
                    </div>
                </div>
                <input id="input-todos" hidden name="input_todos"
                       value="<?= ($filterData['input_todos']) ? 'todos' : 'pagina' ?>">
                <input type="hidden" id="order_sort" name="sort"
                       value="<?= htmlspecialchars((string) ($filterData['sort'] ?? 'modified_at'), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" id="sort_direction" name="sort_direction"
                       value="<?= htmlspecialchars((string) ($filterData['orderDirection'] ?? 'desc'), ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </form>
    </div>
</div>

