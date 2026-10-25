<?php
/**
 * @var array $pageTexts The page texts/translations (search, search_placeholder, seleccionar_por)
 * @var array $filterData The current filter data (search term, etc.)
 */
?>
<label
    style="position: relative;top: -0.7em;display: inline;background-color:white;font-weight: 600;padding-right: 0.5em;padding-left: 0.5em;margin-left: 0.5em;font-size:1.1em !important;"><?= $pageTexts['seleccionar_por'] ?? 'Filter by' ?></label>
<div class="col-12">
    <div class="row">
        <form method="get" id="form-buscar-filtro"
              class="col-sm-12 pt-2"
              action="<?= company_url('countries/lists') ?>">
            <div class="row">
                <div class="col-12 col-sm-8 col-md-6 col-lg-5">
                    <div class="form-group mb-0">
                        <label class="mb-1 small"><?= $pageTexts['search'] ?? 'Search' ?></label>
                        <div class="input-group input-group-sm mb-0">
                            <input type="text" name="prsearch" id="search-countries"
                                   class="form-control"
                                   placeholder="<?= $pageTexts['search_placeholder'] ?? 'Search by name or code' ?>"
                                   value="<?= isset($filterData['search']) && $filterData['search'] !== '' ? htmlspecialchars($filterData['search']) : '' ?>">
                            <div class="input-group-append">
                                <button id="btn-submit-textsearch" class="btn btn-light" type="button">
                                    <span id="basic-addon1"><i class="fa-sharp fa-solid fa-magnifying-glass"></i></span>
                                </button>
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
    document.getElementById('form-buscar-filtro').submit();
}

document.addEventListener('DOMContentLoaded', function() {
    var btn = document.getElementById('btn-submit-textsearch');
    var searchInput = document.getElementById('search-countries');
    if (btn) {
        btn.addEventListener('click', buscar_porfiltro);
    }
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                buscar_porfiltro();
            }
        });
    }
});
</script>
