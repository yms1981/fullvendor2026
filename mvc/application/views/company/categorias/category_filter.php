<?php
/**
 * @var array $pageTexts The page texts/translations
 * @var array $filterData The current filter data for each item of this filter form
 * @var array $status_options The status options for filtering
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
<label
    style="position: relative;top: -0.7em;display: inline;background-color:white;font-weight: 600;padding-right: 0.5em;padding-left: 0.5em;margin-left: 0.5em;font-size:1.1em !important;"><?= $pageTexts['seleccionar_por'] ?? 'Filtrar por' ?></label>
<div class="col-12">
    <div class="row">
        <form method="get" id="form-buscar-filtro"
              class="col-sm-12 pt-2"
              action="<?= company_url('categories') ?>">
            <div class="row">
                <div class="col-12 col-md-9">
                    <div class="row">
                        <div class="col-lg-4 col-md-6 col-xl-4 col-sm-12">
                            <div class="form-group">
                                <label><?= $pageTexts['active_inactive'] ?? 'Activos/Inactivos' ?></label>
                                <div class="filter-select-wrapper">
                                    <select class="form-control filter-select notranslate"
                                            name="status" id="status"
                                            onchange="buscar_porfiltro()"
                                            onfocus="this.selectedIndex = -1;">
                                        <?php foreach ($status_options as $option): ?>
                                            <option value="<?= $option['value'] ?>"
                                                <?= isset($filterData['status']) && $filterData['status'] == $option['value'] ? 'selected' : '' ?>>
                                                <?= strtoupper($option['text']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <i class="mdi mdi-chevron-down filter-select-arrow"></i>
                                </div>
                                <input type="hidden" id="order_type" name="order_type"
                                       value="<?= isset($filterData['order_type']) ? $filterData['order_type'] : '' ?>">
                                <input type="hidden" id="order_sort" name="sort"
                                       value="<?= isset($filterData['sort']) ? $filterData['sort'] : '' ?>">
                                <input type="hidden" id="input-todos" name="input_todos" value="pagina">
                            </div>
                        </div>

                        <div class="col-lg-4 col-md-6 col-xl-4 col-sm-12">
                            <div class="form-group">
                                <label><?= $pageTexts['search'] ?? 'Buscar' ?></label>
                                <div class="input-group mb-3">
                                    <input type="text" name="prsearch" id="search"
                                           class="form-control"
                                           placeholder="<?= $pageTexts['search'] ?? 'Buscar' ?>"
                                           value="<?= !empty($filterData['terms']) ? htmlentities(implode(' ',$filterData['terms'])) : '' ?>">
                                    <div class="input-group-append">
                                        <button id="btn-submit-textsearch" class="btn btn-light" type="button">
                                            <span id="basic-addon1"><i class="fa-sharp fa-solid fa-magnifying-glass"></i></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function buscar_porfiltro() {
    $('#form-buscar-filtro').submit();
}

$(document).ready(function() {
    // Handle search button click
    $('#btn-submit-textsearch').on('click', function() {
        buscar_porfiltro();
    });

    // Handle enter key in search field
    $('#search').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            buscar_porfiltro();
        }
    });

    // Handle sort links
    $(document).on('click', '.sort', function(e) {
        e.preventDefault();

        let order_type = $(this).data('order_type');
        let order_sort = $(this).data('sort');

        $('#order_type').val(order_type);
        $('#order_sort').val(order_sort);

        buscar_porfiltro();
    });
});
</script>
