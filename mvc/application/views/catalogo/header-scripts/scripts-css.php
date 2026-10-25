<?php
/**
 * @var $lang_json object The lang translations for every translatable string
 * @var $base_url string
 */
?>

<script>
    const companyId = <?= json_encode($company->company_id) ?>;
    const version = <?= json_encode($version) ?>;

    const env = {
        base_url: '<?= base_url() ?>',
        url: '<?= rtrim($base_url, '/') ?>',
        text_loader: '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ',
        lang: <?= json_encode($lang_json) ?>,
        // check_newadrress: document.getElementById("address"),
        // btn_createneworder: document.getElementById("btn_createneworder")
    };
</script>
<?php
if (isset($jsFiles)) {
    foreach ($jsFiles as $jsFile) {
        echo '<script type="text/javascript" src="' . $jsFile . '"></script>';
    }
}
?>
