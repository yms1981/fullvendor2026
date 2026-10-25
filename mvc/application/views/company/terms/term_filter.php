<?php
/**
 * @var array $pageTexts The page texts/translations
 * @var array $filterData The current filter data (terms, etc.)
 * @var array|null $langDatatable company-datatable translations
 * @var int|null $currentPerPage active per_page value
 */
$dtLang = $langDatatable ?? [];
$lengthMenuTemplate = (string) ($dtLang['lengthMenu'] ?? ($pageTexts['datatable_length_menu'] ?? 'Show _MENU_ entries'));
$lengthParts = preg_split('/_MENU_/', $lengthMenuTemplate, 2);
$lengthBefore = $lengthParts[0] ?? '';
$lengthAfter = $lengthParts[1] ?? '';
$searchLabel = (string) ($dtLang['search'] ?? ($pageTexts['search'] ?? 'Search'));
$searchPlaceholder = (string) ($pageTexts['search_placeholder'] ?? '');
$perPage = (int) ($currentPerPage ?? 25);
$perPageOptions = [10, 15, 25, 50, 100];
$searchValue = !empty($filterData['terms']) ? implode(' ', $filterData['terms']) : '';
?>
<div class="dataTables_wrapper dt-bootstrap4 no-footer w-100 mb-2">
    <form method="get" id="form-buscar-filtro" action="<?= company_url('terms/lists') ?>">
        <div class="row align-items-center">
            <div class="col-sm-6">
                <div class="dataTables_length">
                    <label class="mb-0">
                        <?= htmlspecialchars($lengthBefore) ?>
                        <select name="per_page" class="custom-select custom-select-sm form-control form-control-sm d-inline-block w-auto ml-1 mr-1">
                            <?php foreach ($perPageOptions as $option): ?>
                                <option value="<?= (int) $option ?>"<?= $perPage === $option ? ' selected' : '' ?>><?= (int) $option ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= htmlspecialchars($lengthAfter) ?>
                    </label>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="dataTables_filter text-sm-right">
                    <label class="mb-0">
                        <?= htmlspecialchars($searchLabel) ?>:
                        <input type="search"
                               name="prsearch"
                               id="search-terms"
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
    function submitTermsFilter() {
        var form = document.getElementById('form-buscar-filtro');
        if (form) {
            form.submit();
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        var form = document.getElementById('form-buscar-filtro');
        if (!form) {
            return;
        }
        var perPage = form.querySelector('select[name="per_page"]');
        if (perPage) {
            perPage.addEventListener('change', submitTermsFilter);
        }
        var searchInput = document.getElementById('search-terms');
        if (searchInput) {
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    submitTermsFilter();
                }
            });
        }
        var loader = document.getElementById('products-loader');
        form.addEventListener('submit', function() {
            if (loader) {
                loader.classList.add('show');
            }
        });
    });
})();
</script>
