<?php
/**
 * @var Object $company company info
 * @var array $catalogList catalog list
 * @var array $pageTexts
 */
$labelEmail = (string) ($pageTexts['channel_email'] ?? 'Email');
$labelSms = (string) ($pageTexts['channel_sms'] ?? 'SMS');
$labelWhatsapp = (string) ($pageTexts['channel_whatsapp'] ?? 'WhatsApp');
$labelMessage = (string) ($pageTexts['message_text'] ?? ($pageTexts['email_text'] ?? ''));
$labelSelectCatalog = (string) ($pageTexts['select_catalog'] ?? 'Select Catalog');
?>
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header pt-2 pb-2">
                <h5 class="modal-title " id="exampleModalLabel"><b><?= $pageTexts['all_catalogs'] ?></b></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="form-send"
                  action="<?=company_url('customers/sendCatalog')?>"
                  method="post"
                  data-api-base-url="<?= htmlspecialchars($apiBaseUrl ?? '/api', ENT_QUOTES, 'UTF-8') ?>"
                  data-api-token="<?= htmlspecialchars($apiToken ?? '', ENT_QUOTES, 'UTF-8') ?>"
                  data-company-id="<?= (int)($company->company_id ?? 0) ?>">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="selectCatalogs"><?= htmlspecialchars($labelSelectCatalog, ENT_QUOTES, 'UTF-8') ?></label>
                        <select name="catlogIds[]" id="selectCatalogs" class="selectpicker w-100" data-live-search="true" multiple
                            data-actions-box="true" data-size="7" data-container="modal-body">
                            <?php foreach ($catalogList as $catalog) {?>
                                <?php if($catalog->default_catalog): ?>
                                    <option selected
                                            data-content='<img src="<?= base_url("images/logos/logo.png") ?>" class="thumbnail rounded image-select">&nbsp;<?=  strtoupper($catalog->catalog_name)?>'
                                            value="<?= $catalog->catalog_id?>">
                                        <?=  strtoupper($catalog->catalog_name)?>
                                    </option>
                                <?php else:?>
                                    <option value="<?= $catalog->catalog_id?>">
                                        <?=  strtoupper($catalog->catalog_name)?>
                                    </option>
                                <?php endif;?>
                            <?php }?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?= $pageTexts['catalog_expiration'] ?></label>
                        <input type="number" value="<?= $company->catalogsend_time ?>" name="expiration_days"
                            class="form-control text-center">
                    </div>
                    <div class="form-group">
                        <label for=""><?= htmlspecialchars((string) ($pageTexts['by_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>
                        <div class="btn-group w-100 fv-catalog-channel-group">
                            <input id="email" hidden type="radio" name="type" class="btn-check" value="email" checked>
                            <label for="email" class="especial_label border text-center fv-catalog-channel-btn">
                                <i class="fa-solid fa-envelope" aria-hidden="true"></i>
                                <?= htmlspecialchars($labelEmail, ENT_QUOTES, 'UTF-8') ?>
                            </label>
                            <input id="sms" hidden type="radio" name="type" class="btn-check" value="sms">
                            <label for="sms" class="especial_label border text-center fv-catalog-channel-btn">
                                <i class="fa-solid fa-mobile" aria-hidden="true"></i>
                                <?= htmlspecialchars($labelSms, ENT_QUOTES, 'UTF-8') ?>
                            </label>
                            <input id="whatsapp" hidden type="radio" name="type" class="btn-check" value="whatsapp">
                            <label for="whatsapp" class="especial_label border text-center fv-catalog-channel-btn">
                                <i class="fa-brands fa-whatsapp" aria-hidden="true"></i>
                                <?= htmlspecialchars($labelWhatsapp, ENT_QUOTES, 'UTF-8') ?>
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?= $pageTexts['catalog_text'] ?></label>
                        <textarea class="form-control" rows="3" name="catalog_title"></textarea>
                    </div>
                    <div class="form-group">
                        <label id="fv-catalog-message-label"><?= htmlspecialchars($labelMessage, ENT_QUOTES, 'UTF-8') ?></label>
                        <textarea class="form-control" rows="3" name="email_text"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="btn-sendemail" type="submit" class="btn btn-success"><span
                            class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        <?= $pageTexts['send_catalog'] ?></button>
                    <button type="button" class="btn btn-danger"
                        data-dismiss="modal"><?= $pageTexts['cancelar'] ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<style>
#exampleModal .fv-catalog-channel-group {
    display: flex;
    width: 100%;
}
#exampleModal .fv-catalog-channel-btn {
    flex: 1 1 0;
    width: auto !important;
    min-width: 0;
    margin: 0;
    padding: 0.55rem 0.35rem;
    font-size: 0.82rem;
    white-space: nowrap;
}
#exampleModal .fv-catalog-channel-group input.btn-check:checked + .fv-catalog-channel-btn {
    background: #e5e7eb;
}
</style>
