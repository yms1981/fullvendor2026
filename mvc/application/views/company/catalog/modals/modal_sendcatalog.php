<?php
$lang_optionbasic = isset($lang_optionbasic) && is_array($lang_optionbasic) ? $lang_optionbasic : [];
$langcatalog = isset($langcatalog) && is_array($langcatalog) ? $langcatalog : [];
$group_list = isset($group_list) && is_array($group_list) ? $group_list : [];
$customerlist = isset($customerlist) && is_array($customerlist) ? $customerlist : [];
$company = $company ?? null;
$defaultExpiration = ($company && isset($company->catalogsend_time) && $company->catalogsend_time !== '' && $company->catalogsend_time !== null)
    ? (int) $company->catalogsend_time
    : 15;
$catalogId = (int) ($v->catalog_id ?? 0);
$sendTitle = (string) ($langcatalog['modal_sendcatalog_title'] ?? $lang_optionbasic['modal_sendcatalog_title'] ?? $langcatalog['send_catalog'] ?? '');
$emailLabel = (string) ($langcatalog['email'] ?? $lang_optionbasic['email'] ?? '');
$smsLabel = (string) ($langcatalog['sms'] ?? $lang_optionbasic['sms'] ?? '');
$cancelLabel = (string) ($langcatalog['cancel'] ?? '');
?>
<div class="modal fade modal-send-catalog fv-catalog-modal" id="sendcatalogModal<?= $catalogId ?>" tabindex="-1"
     aria-labelledby="sendCatalogLabel<?= $catalogId ?>" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sendCatalogLabel<?= $catalogId ?>">
                    <span class="modal-header__avatar" aria-hidden="true"><i class="mdi mdi-send"></i></span>
                    <span><?= htmlspecialchars($sendTitle, ENT_QUOTES, 'UTF-8') ?></span>
                </h5>
                <button type="button" class="close fv-modal-close-x" data-dismiss="modal"
                        aria-label="<?= htmlspecialchars($cancelLabel, ENT_QUOTES, 'UTF-8') ?>">
                    <i class="mdi mdi-close" aria-hidden="true"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="form_sendcatalogbyemail-<?= $catalogId ?>"
                      action="<?= company_url('catalog/send_by/' . $catalogId) ?>" method="post"
                      class="form_send form_send_catalog_modal">
                    <div class="form-group">
                        <label><?= htmlspecialchars($lang_optionbasic['select_group'] ?? $langcatalog['select_group'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                        <select class="form-control selector_group">
                            <option selected value="todo"><?= htmlspecialchars($lang_optionbasic['all'] ?? '', ENT_QUOTES, 'UTF-8') ?></option>
                            <?php foreach ($group_list as $group) : ?>
                                <option value="<?= isset($group->id) ? (int) $group->id : (int) ($group->group_id ?? 0) ?>">
                                    <?= htmlspecialchars(ucwords($group->name ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="d-block d-flex justify-content-between">
                            <span><?= htmlspecialchars($lang_optionbasic['select_customer'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="spinner-border spinner-border-sm text-right d-none" role="status" aria-hidden="true"></span>
                        </label>
                        <select id="select_<?= $catalogId ?>" name="customerIds[]" class="selectpicker"
                                data-live-search="true" multiple
                                data-actions-box="true"
                                data-size="7"
                                data-container="modal-body"
                                data-select-all-text="<?= htmlspecialchars($lang_optionbasic['select_todoestatdos'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                data-deselect-all-text="<?= htmlspecialchars($lang_optionbasic['deselect_todos'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                data-none-selected-text="<?= htmlspecialchars($lang_optionbasic['nothing_selected'] ?? $langcatalog['nothing_selected'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                data-width="100%">
                            <?php if (count($customerlist) > 0) : foreach ($customerlist as $cus) : ?>
                                <option value="<?= (int) ($cus->customer_id ?? 0) ?>">
                                    <?= htmlspecialchars(strtoupper(($cus->business_name ?? '') . ' (' . ($cus->name ?? '') . ')'), ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; endif; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?= htmlspecialchars($lang_optionbasic['time_expiration'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="number" value="<?= $defaultExpiration ?>" name="expiration_days"
                               class="form-control text-center" min="1">
                    </div>
                    <div class="form-group">
                        <label><?= htmlspecialchars($lang_optionbasic['by_emailorsms'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                        <div class="btn-group w-100 fv-catalog-channel-toggle">
                            <input id="email_<?= $catalogId ?>" hidden type="radio"
                                   name="type_<?= $catalogId ?>" class="btn-check" value="email" checked>
                            <label for="email_<?= $catalogId ?>" class="especial_label border w-50 text-center">
                                <i class="fa-solid fa-envelope"></i>
                                <?= htmlspecialchars($emailLabel, ENT_QUOTES, 'UTF-8') ?>
                            </label>
                            <input id="sms_<?= $catalogId ?>" hidden type="radio"
                                   name="type_<?= $catalogId ?>" class="btn-check" value="sms">
                            <label for="sms_<?= $catalogId ?>" class="especial_label border w-50 text-center">
                                <i class="fa-solid fa-mobile"></i>
                                <?= htmlspecialchars($smsLabel, ENT_QUOTES, 'UTF-8') ?>
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?= htmlspecialchars($lang_optionbasic['tittle_catalogtext'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                        <textarea class="form-control" rows="3" name="catalog_text"></textarea>
                    </div>
                    <div class="form-group mb-0">
                        <label><?= htmlspecialchars($lang_optionbasic['email_text'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                        <textarea class="form-control" rows="3" name="email_text"></textarea>
                    </div>
                    <input type="hidden" value="<?= $catalogId ?>" name="catalogIds">
                </form>
            </div>
            <div class="modal-footer">
                <button form="form_sendcatalogbyemail-<?= $catalogId ?>" type="submit"
                        class="btn btn-success btn-submit-send-catalog">
                    <?= htmlspecialchars($langcatalog['send_catalog'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                </button>
                <button type="button" class="btn btn-danger" data-dismiss="modal">
                    <?= htmlspecialchars($cancelLabel, ENT_QUOTES, 'UTF-8') ?>
                </button>
            </div>
        </div>
    </div>
</div>
