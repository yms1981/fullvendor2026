<?php
/**
 * @var array|null $langDatatable
 * @var array|null $configCrudInlineEdit
 * @var array|null $configCrudBooleanSwitch
 * @var array|null $configCrudStatusSwitch
 * @var bool|null $changeStatus
 */
$dtLang = is_array($langDatatable ?? null) ? $langDatatable : [];
$inlineEdit = is_array($configCrudInlineEdit ?? null) ? $configCrudInlineEdit : [];
$booleanSwitch = is_array($configCrudBooleanSwitch ?? null) ? $configCrudBooleanSwitch : [];
$booleanSwitchMessages = is_array($booleanSwitch['messages'] ?? null) ? $booleanSwitch['messages'] : [];
$statusSwitch = is_array($configCrudStatusSwitch ?? null) ? $configCrudStatusSwitch : [];
$statusSwitchMessages = is_array($statusSwitch['messages'] ?? null) ? $statusSwitch['messages'] : [];
$enableStatusSwitch = !empty($changeStatus) || !empty($statusSwitchMessages);
?>
<script>
<?php if (!empty($booleanSwitchMessages)) : ?>
window.fvConfigCrudBooleanSwitch = <?= json_encode($booleanSwitchMessages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
<?php endif; ?>
</script>
<?php if (!empty($booleanSwitchMessages)) : ?>
<script src="<?= dated_file_url('companyasset/company/config-crud-boolean-switch.js') ?>"></script>
<?php endif; ?>
<?php if ($enableStatusSwitch) : ?>
<script src="<?= dated_file_url('companyasset/company/config-crud-status-switch.js') ?>"></script>
<script>
window.fvConfigCrudStatusSwitch = <?= json_encode($statusSwitchMessages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>
<?php endif; ?>
<script src="<?= dated_file_url('companyasset/company/config-crud-datagrid.js') ?>"></script>
<script>
(function () {
    var dtLanguage = <?= json_encode([
        'info' => $dtLang['info'] ?? '',
        'infoEmpty' => $dtLang['infoEmpty'] ?? '',
        'infoFiltered' => $dtLang['infoFiltered'] ?? '',
        'lengthMenu' => $dtLang['lengthMenu'] ?? '',
        'loadingRecords' => $dtLang['loadingRecords'] ?? '',
        'processing' => $dtLang['processing'] ?? '',
        'search' => $dtLang['search'] ?? '',
        'zeroRecords' => $dtLang['zeroRecords'] ?? '',
        'paginate' => [
            'first' => $dtLang['first'] ?? '',
            'last' => $dtLang['last'] ?? '',
            'next' => $dtLang['next'] ?? '',
            'previous' => $dtLang['previous'] ?? '',
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    var allLabel = <?= json_encode((string) ($dtLang['all'] ?? 'All'), JSON_UNESCAPED_UNICODE) ?>;
    var inlineEdit = <?= json_encode($inlineEdit, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    var enableStatusSwitch = <?= $enableStatusSwitch ? 'true' : 'false' ?>;

    function initSwitchModules($) {
        if (window.FvConfigCrudBooleanSwitch && typeof window.FvConfigCrudBooleanSwitch.init === 'function') {
            window.FvConfigCrudBooleanSwitch.init($);
        }
        if (enableStatusSwitch && window.FvConfigCrudStatusSwitch && typeof window.FvConfigCrudStatusSwitch.init === 'function') {
            window.FvConfigCrudStatusSwitch.init($);
        }
    }

    function bootDatagrid($) {
        if (typeof $.fn.DataTable !== 'function') {
            setTimeout(function () { bootDatagrid($); }, 120);
            return;
        }
        if (typeof window.FvConfigCrudDatagrid === 'undefined') {
            setTimeout(function () { bootDatagrid($); }, 120);
            return;
        }

        $('#products-loader').removeClass('show hiding').hide();
        window.FvConfigCrudDatagrid.init('#infoTable', {
            language: dtLanguage,
            allLabel: allLabel,
            pageLength: 25,
            inlineEdit: inlineEdit,
        });
    }

    function boot() {
        var start = function () {
            if (typeof jQuery === 'undefined') {
                setTimeout(boot, 120);
                return;
            }
            jQuery(function ($) {
                initSwitchModules($);
                bootDatagrid($);
            });
        };

        if (typeof window.CoreLoader !== 'undefined' && typeof window.CoreLoader.waitForJQuery === 'function') {
            window.CoreLoader.waitForJQuery(start);
            return;
        }

        start();
    }

    boot();
})();
</script>
