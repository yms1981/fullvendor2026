<?php
/**
 * @var array $pageTexts The page texts/translations
 * @var array $filterData The current filter data for each item of this filter form
 * @var array|null $langDatatable company-datatable translations
 */
$dtLang = $langDatatable ?? [];
$searchLabel = (string) ($dtLang['search'] ?? ($pageTexts['search'] ?? 'Search'));
$searchPlaceholder = (string) ($pageTexts['search_placeholder'] ?? '');
$searchValue = !empty($filterData['terms']) ? implode(' ', $filterData['terms']) : '';
$unitsFilterFrom = (string) $this->input->get('from');
?>
<div class="dataTables_wrapper dt-bootstrap4 no-footer w-100 mb-2 fv-units-filter-toolbar">
    <form method="get" id="form-buscar-filtro" action="<?= company_url('units/lists') ?>">
        <?php if ($unitsFilterFrom !== '') : ?>
            <input type="hidden" name="from" value="<?= htmlspecialchars($unitsFilterFrom, ENT_QUOTES, 'UTF-8') ?>">
        <?php endif; ?>
        <div class="row align-items-center justify-content-end">
            <div class="col-sm-6">
                <div class="dataTables_filter text-sm-right">
                    <label class="mb-0">
                        <?= htmlspecialchars($searchLabel) ?>:
                        <input type="search"
                               name="prsearch"
                               id="search-units"
                               class="form-control form-control-sm d-inline-block w-auto ml-1"
                               value="<?= htmlspecialchars($searchValue) ?>"
                               placeholder="<?= htmlspecialchars($searchPlaceholder) ?>"
                               aria-controls="infoTable">
                    </label>
                </div>
            </div>
        </div>
    </form>
</div>
<script>
(function() {
    function submitUnitsFilter() {
        var form = document.getElementById('form-buscar-filtro');
        if (form) {
            form.submit();
        }
    }

    function applyClientSearch(value) {
        if (typeof jQuery === 'undefined' || typeof jQuery.fn.DataTable !== 'function') {
            return false;
        }
        var $table = jQuery('#infoTable');
        if (!$table.length || !jQuery.fn.DataTable.isDataTable($table)) {
            return false;
        }
        $table.DataTable().search(value || '').draw();
        return true;
    }

    document.addEventListener('DOMContentLoaded', function() {
        var form = document.getElementById('form-buscar-filtro');
        var searchInput = document.getElementById('search-units');
        if (!form || !searchInput) {
            return;
        }

        var debounceTimer = null;
        searchInput.addEventListener('input', function() {
            var value = String(searchInput.value || '');
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function() {
                if (!applyClientSearch(value)) {
                    // DataGrid aún no listo: el submit en Enter cubre el path servidor.
                }
            }, 120);
        });

        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (!applyClientSearch(String(searchInput.value || ''))) {
                    submitUnitsFilter();
                }
            }
        });
    });
})();
</script>
