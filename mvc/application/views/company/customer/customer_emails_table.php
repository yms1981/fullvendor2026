<?php
/**
 * Grilla de correos del cliente (varios emails vinculados).
 *
 * @var object $customer
 * @var array $pageTexts
 * @var string $apiToken
 * @var string $apiBaseUrl
 */
$pageTexts = is_array($pageTexts ?? null) ? $pageTexts : [];
if (function_exists('fv_lang_json_flat_section') && function_exists('fv_resolve_company_language_id')) {
    $langId = (int) fv_resolve_company_language_id();
    $moduleLang = fv_lang_json_flat_section($langId, 'cmp-cliente');
    if ($moduleLang !== []) {
        // JSON fresco gana sobre pageTexts (puede venir de caché de sesión desfasada).
        $pageTexts = array_merge($pageTexts, $moduleLang);
    }
}
// Respaldo: langmdl vía API si aún faltan claves nuevas (sesión/caché desfasada).
if ((! isset($pageTexts['emails_add']) || trim((string) $pageTexts['emails_add']) === '') && function_exists('get_instance')) {
    $CI = &get_instance();
    if (is_object($CI)) {
        if (! isset($CI->langmodel)) {
            $CI->load->model('langmodel');
        }
        if (isset($CI->langmodel) && method_exists($CI->langmodel, 'getMdl')) {
            $langId = function_exists('fv_resolve_company_language_id')
                ? (int) fv_resolve_company_language_id()
                : 2;
            $fresh = $CI->langmodel->getMdl('cmp-cliente', $langId);
            if (is_object($fresh)) {
                $fresh = json_decode(json_encode($fresh), true);
            }
            if (is_array($fresh) && $fresh !== []) {
                $pageTexts = array_merge($pageTexts, $fresh);
            }
        }
    }
}
$t = static function (string $key, string $fallback = '') use ($pageTexts): string {
    $value = $pageTexts[$key] ?? '';
    if (! is_string($value) || trim($value) === '') {
        $value = $fallback;
    }

    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
};

$customerId = (int) ($customer->customer_id ?? 0);
$apiToken = (string) ($apiToken ?? '');
$apiBaseUrl = rtrim((string) ($apiBaseUrl ?? '/api'), '/');
$emails = $customer->emails ?? [];
if (! is_array($emails)) {
    $emails = is_object($emails) ? (array) $emails : [];
}
$fvAppLocaleMap = [
    1 => 'en',
    2 => 'es',
    3 => 'pt',
    4 => 'it',
    5 => 'ru',
    6 => 'de',
    7 => 'zh',
];
$fvLanguageId = function_exists('fv_resolve_company_language_id')
    ? (int) fv_resolve_company_language_id()
    : 1;
$fvAppLocale = $fvAppLocaleMap[$fvLanguageId] ?? 'en';
?>
<link rel="stylesheet" href="<?= dated_file_url('companyasset/css/fv-customer-emails.css') ?>" />
<script>
window.FV_CUSTOMER_EMAILS = <?= json_encode([
    'customerId' => $customerId,
    'apiToken' => $apiToken,
    'apiBaseUrl' => $apiBaseUrl,
    'appLocale' => $fvAppLocale,
    'languageId' => $fvLanguageId,
    'i18n' => [
        'emails_title' => (string) ($pageTexts['emails_grid_title'] ?? ''),
        'add_email' => (string) ($pageTexts['emails_add'] ?? ''),
        'edit_email' => (string) ($pageTexts['emails_edit'] ?? ''),
        'col_email' => (string) ($pageTexts['label_email'] ?? ''),
        'col_primary' => (string) ($pageTexts['emails_col_primary'] ?? ''),
        'col_actions' => (string) ($pageTexts['acciones'] ?? ''),
        'primary_yes' => (string) ($pageTexts['emails_primary_yes'] ?? ''),
        'primary_no' => (string) ($pageTexts['emails_primary_no'] ?? ''),
        'set_primary' => (string) ($pageTexts['emails_set_primary'] ?? ''),
        'eliminar' => (string) ($pageTexts['eliminar'] ?? ''),
        'guardar' => (string) ($pageTexts['guardar'] ?? ''),
        'cancelar' => (string) ($pageTexts['cancelar'] ?? ''),
        'empty' => (string) ($pageTexts['emails_empty'] ?? ''),
        'confirm_delete' => (string) ($pageTexts['emails_confirm_delete'] ?? ''),
        'email_invalid' => (string) ($pageTexts['email_invalid_format'] ?? ''),
        'email_in_use' => (string) ($pageTexts['email_already_in_use'] ?? ''),
        'email_already_registered' => (string) ($pageTexts['emails_already_registered'] ?? ''),
        'error_generic' => (string) ($pageTexts['error_updating_email'] ?? ''),
        'saved' => (string) ($pageTexts['email_updated_success'] ?? ''),
        'saved_title' => (string) ($pageTexts['email_updated'] ?? ''),
        'btn_accept' => (string) ($pageTexts['btn_accept'] ?? ''),
        'mark_primary' => (string) ($pageTexts['emails_mark_primary'] ?? ''),
        'placeholder' => (string) ($pageTexts['placeholder_new_email'] ?? ''),
        'list_title' => (string) ($pageTexts['emails_list_title'] ?? ''),
        'list_hint' => (string) ($pageTexts['emails_list_hint'] ?? ''),
        'default_badge' => (string) ($pageTexts['emails_default_badge'] ?? ''),
    ],
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
</script>

<div class="card fv-customer-emails-card mt-4" id="fv_customer_emails_card">
    <div class="card-header fv-customer-emails-card__header d-flex justify-content-between align-items-center">
        <span class="fv-customer-emails-card__title"><?= $t('emails_grid_title') ?></span>
        <button type="button"
                class="btn btn-primary btn-sm fv-customer-emails-card__add-btn"
                id="fv_customer_emails_add_btn"
                title="<?= $t('emails_add') ?>"
                aria-label="<?= $t('emails_add') ?>"
            <?= $customerId > 0 ? '' : ' disabled' ?>>
            <i class="fa-solid fa-plus" aria-hidden="true"></i>
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive fv-customer-emails-table-wrap">
            <table class="table table-striped mb-0 fv-customer-emails-table" id="fv_customer_emails_table">
                <thead>
                <tr>
                    <th><?= $t('label_email') ?></th>
                    <th><?= $t('emails_col_primary') ?></th>
                    <th class="fv-customer-emails-table__actions-col text-center"><?= $t('acciones') ?></th>
                </tr>
                </thead>
                <tbody id="fv_customer_emails_tbody">
                <?php if ($emails === [] || count($emails) === 0) : ?>
                    <tr class="fv-customer-emails-empty-row">
                        <td colspan="3" class="text-muted text-center py-3"><?= $t('emails_empty', '') ?></td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($emails as $row) :
                        $row = is_object($row) ? $row : (object) $row;
                        $rowId = (int) ($row->id ?? 0);
                        $rowEmail = htmlspecialchars(trim((string) ($row->email ?? '')), ENT_QUOTES, 'UTF-8');
                        $isPrimary = !empty($row->is_primary);
                        ?>
                        <tr data-id="<?= $rowId ?>" data-email="<?= $rowEmail ?>" data-primary="<?= $isPrimary ? '1' : '0' ?>">
                            <td><?= $rowEmail ?></td>
                            <td>
                                <?php if ($isPrimary) : ?>
                                    <span class="badge badge-success"><?= $t('emails_primary_yes') ?></span>
                                <?php else : ?>
                                    <span class="badge badge-secondary"><?= $t('emails_primary_no') ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="fv-customer-emails-table__actions">
                                    <?php if (! $isPrimary) : ?>
                                        <button type="button" class="btn btn-outline-primary fv-crud-btn--row fv-ce-set-primary"
                                                title="<?= $t('emails_set_primary') ?>">
                                            <i class="fa-solid fa-star" aria-hidden="true"></i>
                                        </button>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-outline-secondary fv-crud-btn--row fv-ce-edit"
                                            title="<?= $t('emails_edit') ?>">
                                        <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger fv-crud-btn--row fv-ce-delete"
                                            title="<?= $t('eliminar') ?>"
                                        <?= $isPrimary ? ' disabled' : '' ?>>
                                        <i class="fa-solid fa-trash" aria-hidden="true"></i>
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
</div>

<div class="modal fade" id="fv_customer_email_modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="fv_customer_email_modal_title"><?= $t('emails_add') ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="fv_ce_edit_id" value="">
                <div class="form-group">
                    <label for="fv_ce_email_input"><?= $t('label_email') ?> <span class="color-red">*</span></label>
                    <input type="email" class="form-control" id="fv_ce_email_input"
                           placeholder="<?= $t('placeholder_new_email') ?>" autocomplete="email">
                    <small class="text-danger" id="fv_ce_email_error" style="display:none;"></small>
                </div>
                <div class="form-group form-check">
                    <input type="checkbox" class="form-check-input" id="fv_ce_is_primary">
                    <label class="form-check-label" for="fv_ce_is_primary"><?= $t('emails_mark_primary') ?></label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= $t('cancelar') ?></button>
                <button type="button" class="btn btn-primary" id="fv_ce_save_btn"><?= $t('guardar') ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: listado + cambiar principal + agregar email -->
<div class="modal fade" id="fv_customer_emails_list_modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= $t('emails_list_title', 'Correos del cliente') ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3" id="fv_ce_list_hint"><?= $t('emails_list_hint', '') ?></p>
                <ul class="list-group fv-ce-list mb-3" id="fv_ce_emails_list"></ul>
                <div class="fv-ce-list-add border-top pt-3">
                    <label class="font-weight-bold d-block mb-2" for="fv_ce_list_new_email"><?= $t('emails_add') ?></label>
                    <div class="input-group">
                        <input type="email"
                               class="form-control"
                               id="fv_ce_list_new_email"
                               placeholder="<?= $t('placeholder_new_email') ?>"
                               autocomplete="email">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-primary" id="fv_ce_list_add_btn"><?= $t('emails_add') ?></button>
                        </div>
                    </div>
                    <div class="form-check mt-2">
                        <input type="checkbox" class="form-check-input" id="fv_ce_list_new_primary">
                        <label class="form-check-label" for="fv_ce_list_new_primary"><?= $t('emails_mark_primary') ?></label>
                    </div>
                    <small class="text-danger d-block mt-1" id="fv_ce_list_new_error" style="display:none;"></small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= $t('cancelar') ?></button>
            </div>
        </div>
    </div>
</div>

<script src="<?= dated_file_url('companyasset/company/customer/customer-emails.js') ?>"></script>
