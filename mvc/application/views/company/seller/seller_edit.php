<?php
/**
 * @var $seller Object editing seller
 * @var $sellerProfiles array list of seller profiles
 * @var $pageTexts array the translated string conglomerated for this view
 */

$sellerProfile = $seller->user->seller_company_user->profileable;
?>

<form id="form_edit" class="forms-sample"
      action="<?= company_url('sellers/update/'. $seller->id) ?>"
      method="post">

    <input type="hidden" name="id" value="<?= $seller->id ?>">
    <div class="container-fluid page-body-wrapper pt-3">
        <div class="main-panel">
            <div class="container-fluid">

                <div class="row mb-3">
                    <div class="col-md-6">
                        <h3 class="page-title"><?= $pageTexts['titulo'] ?></h3>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $pageTexts['user'] ?? "User" ?> </label>
                                    <input type="text" class="form-control validate" id="user_name" placeholder="<?= $pageTexts['enter_user_name'] ?? 'Enter user name...' ?>"
                                    value="<?= $seller->user->name ?>">
                                    <div class="invalid-feedback text-white bg-danger border rounded p-1 fw-bold"></div>
                                    <div id="user_suggestions" class="suggestions-box"></div>
                                    <input type="hidden" id="user_id" name="user_id" value="<?= $seller->user->id ?>">
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $pageTexts['profile'] ?> </label>
                                    <select name="profile_id" class="form-control notranslate">
                                        <?php if ($sellerProfiles) { ?>
                                            <option selected disabled value=""><?= $pageTexts['select_profile'] ?? 'Select Profile' ?></option>
                                            <?php foreach ($sellerProfiles as $profile) : ?>
                                                <option
                                                    value="<?= $profile->profile_id ?>" <?= $profile->profile_id === $sellerProfile->profile_id ? 'selected' : '' ?>>
                                                    <?= $profile->profile_name ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php } else { ?>
                                            <option value=""><?= $pageTexts['no_profile_found'] ?? 'No Profile Found' ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label
                                        for="email_on_salesman_order"><?= $pageTexts['email_on_salesman_order'] ?? 'Send email to seller when making an order' ?></label>
                                    <div class="btn-group w-100">
                                        <input id="email_on_salesman_order_yes" hidden type="radio"
                                               name="email_on_salesman_order" class="btn-check" value="1"
                                            <?= $seller->email_on_salesman_order ? 'checked' : '' ?>>
                                        <label for="email_on_salesman_order_yes"
                                               class="especial_label border w-50 text-center"><?= $pageTexts['si'] ?? 'Yes' ?></label>

                                        <input id="email_on_salesman_order_no" hidden type="radio"
                                               name="email_on_salesman_order" class="btn-check" value="0"
                                            <?= !$seller->email_on_salesman_order ? 'checked' : '' ?>>
                                        <label for="email_on_salesman_order_no"
                                               class="especial_label border w-50 text-center">No</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $pageTexts['phone_number'] ?> </label>
                                    <input type="text" class="form-control" name="phone_number"
                                    value="<?= $seller->phone_number?>">
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $pageTexts['cell_number'] ?> </label>
                                    <input type="text" class="form-control" name="cell_number"
                                    value="<?= $seller->cell_number ?? $seller->user->cellphone ?>">
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $pageTexts['fax'] ?? 'Fax' ?> </label>
                                    <input type="text" class="form-control" name="fax" id="fax"
                                    value="<?= $seller->fax ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group row pl-3">
                                    <label><?= $pageTexts['all_customer'] ?></label>
                                    <span>&nbsp;&nbsp;&nbsp;</span>
                                    <input type="hidden" name="all_customers" value="0">
                                    <input type="checkbox" name="all_customers" value="1"
                                        <?= $seller->all_customers ? 'checked' : '' ?>>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group row pl-3">
                                            <label><?= $pageTexts['default'] ?> </label>
                                            <span>&nbsp;&nbsp;&nbsp;</span>
                                            <input type="checkbox" name="checkBoxDefault" value="1"
                                                <?= $seller->isDefault ? 'checked' : '' ?>>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-group row pl-3">
                                            <label><?= $pageTexts['proforma'] ?> </label>
                                            <span>&nbsp;&nbsp;&nbsp;</span>
                                            <input type="hidden" name="proforma" value="0">
                                            <input type="checkbox" name="proforma" value="1"
                                                <?= $seller->proforma ? 'checked' : '' ?>>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php $this->load->view('components/crud/form_action_fab', [
        'formId' => 'form_edit',
        'saveLabel' => $pageTexts['save'] ?? 'Save',
        'cancelLabel' => $pageTexts['back'] ?? 'Back',
        'cancelUrl' => company_url('sellers'),
    ]); ?>
</form>
