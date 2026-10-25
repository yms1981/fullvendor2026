<?php
/**
 * @var array $pageTexts The page texts/translations
 * @var array $filterData The current filter data for each item of this filter form
 * @var array $sellerList The list of sellers
 * @var array $orderStatuses The list of statuses
 * @var array $statusLabelMap id_status => translated label for each status
 * @var array $groupList The list of groups
 * @var string $search The search string
 */
$orderStatuses = isset($orderStatuses) ? $orderStatuses : [];
$statusLabelMap = isset($statusLabelMap) ? $statusLabelMap : [];
$sellerList = isset($sellerList) ? $sellerList : [];
$groupList = isset($groupList) ? $groupList : [];
$dateFromIso = (string) ($filterData['date_from'] ?? date('Y-m-d', strtotime('-30 days')));
$dateToIso = (string) ($filterData['date_to'] ?? date('Y-m-d'));
?>
<div class="col-12">
    <div class="row">
        <form id="form-buscar-filtro" class="col-12 pt-2"
              action="<?= company_url('orders') ?>">
            <div class="row">
                <div class="col-12">
                    <div class="row">

                        <!-- DESDE FECHA (formato de empresa) -->
                        <div class="col-lg-2 col-md-6 col-xl-2 col-sm-12">
                            <div class="form-group">
                                <label><?= $pageTexts['desde_fecha'] ?></label>
                                <?= fv_render_company_date_input('date_from', $dateFromIso, [
                                    'id' => 'date_from_display',
                                    'data-fv-autosubmit' => '1',
                                ]) ?>
                            </div>
                        </div>

                        <!-- HASTA FECHA (formato de empresa) -->
                        <div class="col-lg-2 col-md-6 col-xl-2 col-sm-12">
                            <div class="form-group">
                                <label><?= $pageTexts['hasta_fecha'] ?></label>
                                <?= fv_render_company_date_input('date_to', $dateToIso, [
                                    'id' => 'date_to_display',
                                    'data-fv-autosubmit' => '1',
                                ]) ?>
                            </div>
                        </div>

                        <!-- MULTISELECT VENDEDORES -->
                        <div class="col-lg-2 col-md-6 col-xl-2 col-sm-12">
                            <div id="multi_select_vendedor" class="form-group">
                                <label><?= $pageTexts['vendedores_order'] ?></label><br>

                                <?php $seller_id = $filterData['seller'] ?? []; ?>

                                <select tabindex="3"
                                        name="sellerId[]"
                                        class="form-control selectpicker"
                                        multiple
                                        data-select-all-text="<?= $pageTexts['select_todoestatdos'] ?>"
                                        data-deselect-all-text="<?= $pageTexts['deselect'] ?>"
                                        data-actions-box="true"
                                        data-size="5"
                                        data-none-selected-text="<?= $pageTexts['all'] ?>"
                                        onchange="buscar_porfiltro()">

                                    <?php foreach ($sellerList as $seller) {
                                        if (!is_object($seller)) {
                                            continue;
                                        }
                                        $sellerName = is_object($seller->user ?? null) ? ($seller->user->name ?? '') : '';
                                        if ($sellerName === '') {
                                            continue;
                                        }
                                    ?>
                                        <option value="<?= (int) $seller->id ?>"
                                            <?= in_array($seller->id, $seller_id) ? 'selected' : '' ?>>
                                            <?= strtoupper($sellerName) ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>

                        <!-- ORIGEN -->
                        <div class="col-lg-2 col-md-6 col-xl-2 col-sm-12">
                            <div class="form-group">
                                <label><?= $pageTexts['origen'] ?></label>

                                <select tabindex="4"
                                        name="origin"
                                        class="form-control selectpicker"
                                        data-size="5"
                                        data-show-content="true"
                                        data-none-selected-text="<?= htmlspecialchars((string) ($pageTexts['all'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        onchange="buscar_porfiltro()">

                                    <?php $origin = $filterData['source'] ?? 'todo'; ?>

                                    <option value="todo"
                                        data-content="<i class='fa-solid fa-filter text-muted me-2'></i><?= strtoupper($pageTexts['todo']) ?>"
                                        <?= $origin == 'todo' ? 'selected' : '' ?>>
                                        <?= strtoupper($pageTexts['todo']) ?>
                                    </option>

                                    <option value="app"
                                        data-content="<i class='mdi mdi-cellphone-android text-primary me-2'></i><?= htmlspecialchars((string) ($pageTexts['origin_app'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        <?= $origin == 'app' ? 'selected' : '' ?>>
                                        <?= htmlspecialchars((string) ($pageTexts['origin_app'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </option>

                                    <option value="web"
                                        data-content="<i class='mdi mdi-store text-info me-2'></i><?= htmlspecialchars((string) ($pageTexts['origin_web'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        <?= in_array(strtolower((string) $origin), ['web', 'catalog'], true) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars((string) ($pageTexts['origin_web'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </option>

                                    <option value="manual"
                                        data-content="<i class='mdi mdi-pencil-box-outline text-secondary me-2'></i><?= htmlspecialchars((string) ($pageTexts['origin_manual'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        <?= $origin == 'manual' ? 'selected' : '' ?>>
                                        <?= htmlspecialchars((string) ($pageTexts['origin_manual'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </option>

                                    <option value="Marketplace"
                                        data-content="<i class='mdi mdi-cart text-success me-2'></i><?= htmlspecialchars((string) ($pageTexts['origin_marketplace'] ?? $pageTexts['ecommerce'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        <?= strtolower((string) $origin) === 'marketplace' ? 'selected' : '' ?>>
                                        <?= htmlspecialchars((string) ($pageTexts['origin_marketplace'] ?? $pageTexts['ecommerce'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </option>

                                </select>
                            </div>
                        </div>

                        <!-- MULTISELECT STATUS (misma lógica que list/status_field y modal_change_status) -->
                        <div class="col-lg-2 col-md-6 col-xl-2 col-sm-12">
                            <div id="multi_select" class="form-group">
                                <label><?= $pageTexts['estado'] ?></label>

                                <?php
                                $status_get = array_map('intval', (array)($filterData['status_cod'] ?? []));
                                // Deduplicar por cod_status y ordenar igual que la grilla y el modal
                                $byCodStatus = [];
                                foreach ($orderStatuses as $status) {
                                    if (!is_object($status)) continue;
                                    $cod = (int)($status->cod_status ?? 0);
                                    if (!array_key_exists($cod, $byCodStatus)) {
                                        $byCodStatus[$cod] = $status;
                                    }
                                }
                                ksort($byCodStatus, SORT_NUMERIC);
                                $statusesForFilter = array_values($byCodStatus);
                                ?>

                                <select tabindex="5"
                                        id="multistatus"
                                        name="status[]"
                                        class="form-control selectpicker"
                                        multiple
                                        data-select-all-text="<?= $pageTexts['select_todoestatdos'] ?>"
                                        data-deselect-all-text="<?= $pageTexts['deselect'] ?>"
                                        data-actions-box="true"
                                        data-size="5"
                                        data-none-selected-text="<?= $pageTexts['all'] ?>"
                                        data-show-content="true"
                                        onchange="buscar_porfiltro()">

                                    <?php foreach ($statusesForFilter as $status):
                                        $codStatus = (int)($status->cod_status ?? 0);
                                        $label = $statusLabelMap[$codStatus] ?? $status->name_status_english ?? $status->name_status_spanish ?? $status->name_status ?? (string)$codStatus;
                                        $color = !empty($status->color) ? htmlspecialchars($status->color) : '#ccc';
                                        $content = "<span class='d-inline-block rounded-circle me-2' style='width:10px;height:10px;background-color:" . $color . ";vertical-align:middle'></span>" . htmlspecialchars($label);
                                        $contentAttr = str_replace('"', '&quot;', $content);
                                        $isSelected = in_array($codStatus, $status_get);
                                    ?>
                                        <option value="<?= $codStatus ?>"
                                            <?= ($status->disabled ?? 0) == 1 ? 'disabled' : '' ?>
                                            <?= $isSelected ? 'selected' : '' ?>
                                            data-content="<?= $contentAttr ?>">
                                            <?= htmlspecialchars($label) ?>
                                        </option>
                                    <?php endforeach; ?>

                                </select>
                            </div>
                        </div>

                        <!-- GRUPOS -->
                        <div class="col-lg-2 col-md-6 col-xl-2 col-sm-12">
                            <div class="form-group">
                                <label><?= htmlspecialchars((string) ($pageTexts['grupos'] ?? $pageTexts['grupo_cliente'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>

                                <?php $group = $filterData['group'] ?? 'todo'; ?>

                                <select tabindex="6"
                                        class="form-control selectpicker"
                                        name="groupId"
                                        data-size="5"
                                        data-none-selected-text="<?= htmlspecialchars((string) ($pageTexts['all'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        onchange="buscar_porfiltro()">

                                    <option value="todo"
                                        <?= $group == 'todo' ? 'selected' : '' ?>>
                                        <?= strtoupper($pageTexts['todo']) ?>
                                    </option>

                                    <option value="-1"
                                        <?= $group == '-1' ? 'selected' : '' ?>>
                                        <?= strtoupper($pageTexts['option_vacio']) ?>
                                    </option>

                                    <?php foreach ($groupList as $g) { ?>
                                        <option value="<?= $g->group_id ?>"
                                            <?= $group == $g->group_id ? 'selected' : '' ?>>
                                            <?= strtoupper($g->name) ?>
                                        </option>
                                    <?php } ?>

                                </select>
                            </div>
                        </div>

                        <!-- BUSCADOR -->
                        <div class="col-lg-2 col-md-12 col-xl-2 col-sm-12">
                            <div class="form-group">
                                <label><?= $pageTexts['caja_text_search'] ?></label>
                                <div class="input-group mb-3">
                                    <input tabindex="7"
                                           type="text"
                                           name="search"
                                           class="form-control text-search"
                                           placeholder="<?= $pageTexts['caja_text_search'] ?>"
                                           value="<?= $filterData['search'] ?? '' ?>">

                                    <button type="submit"
                                            form="form-buscar-filtro"
                                            class="btn btn-light">
                                        <i class="fa-sharp fa-solid fa-magnifying-glass"></i>
                                    </button>
                                </div>
                            </div>
                            <input id="input-todos" hidden name="input_todos"
                                   value="<?= ($filterData['input_todos']) ? 'todos' : 'pagina' ?>">
                            <input type="hidden" id="order_sort" name="sort"
                                   value="<?= htmlspecialchars((string) ($filterData['sort'] ?? 'updated'), ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" id="sort_direction" name="sort_direction"
                                   value="<?= htmlspecialchars((string) ($filterData['orderDirection'] ?? 'desc'), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
