<?php
$formData = isset($formData) && is_array($formData) ? $formData : [];
$cfLang = isset($cfLang) && is_array($cfLang) ? $cfLang : [];
$cf = function (string $key, string $fallback = '') use ($cfLang): string {
    $v = trim((string) ($cfLang[$key] ?? ''));

    return $v !== '' ? $v : $fallback;
};
$val = function ($key, $default = '') use ($formData) {
    $raw = isset($formData[$key]) ? $formData[$key] : $default;
    if (is_array($raw)) {
        return $default;
    }
    return htmlspecialchars((string) $raw, ENT_QUOTES, 'UTF-8');
};
?>


<div class="container-fluid page-body-wrapper">
    <div class="main-panel">
        <div class="container-fluid pt-3">
            <div class="page-header">
                <h3 class="page-title"> <?=@$title?></h3>
            </div>
            <div class="card">
                <div class="card-body">
                    <form class="forms-sample" action="<?= admin_url('company/create') ?>" method="post"
                        enctype="multipart/form-data">
                        <div class="form-group row">
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= htmlspecialchars($cf('label_company_category', 'Company category'), ENT_QUOTES, 'UTF-8') ?>:</label>
                                    <select class="form-control" name="category_id">
                                        <?php foreach ($catlist as $cat) { ?>
                                        <option value="<?=$cat->category_id?>" <?= ((string)($formData['category_id'] ?? '') === (string)$cat->category_id) ? 'selected' : '' ?>><?=$cat->category_name?></option>
                                        <?php } ?>
                                    </select>
                                    <span class="error"><?php echo form_error('category_id'); ?></span>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label><?= htmlspecialchars($cf('label_company_name', 'Company name'), ENT_QUOTES, 'UTF-8') ?>: <span class="color-red" id="dname"><?= htmlspecialchars($cf('required_asterisk', '*'), ENT_QUOTES, 'UTF-8') ?></span></label>
                                <input type="text" class="form-control" name="name" id="name" autocomplete="off"
                                    value="<?= $val('name') ?>" required="" placeholder="<?= htmlspecialchars($cf('placeholder_company_name', 'Company name'), ENT_QUOTES, 'UTF-8') ?>" onblur="getDuplicateName();">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label><?= htmlspecialchars($cf('label_short_name', 'Short name'), ENT_QUOTES, 'UTF-8') ?>:</label>
                                <input type="text" class="form-control" name="short_name" id="short_name"
                                    autocomplete="off" value="<?= $val('short_name') ?>" placeholder="<?= htmlspecialchars($cf('placeholder_short_name', 'Short name'), ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= htmlspecialchars($cf('label_language', 'Language'), ENT_QUOTES, 'UTF-8') ?>:</label>
                                    <select class="form-control" name="language_id">
                                        <?php foreach ($language as $lang) { ?>
                                        <option value="<?=$lang->language_id?>" <?= ((string)($formData['language_id'] ?? '') === (string)$lang->language_id) ? 'selected' : '' ?>><?=$lang->name?></option>
                                        <?php } ?>
                                    </select>
                                    <span class="error"><?php echo form_error('language_id'); ?></span>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label><?= htmlspecialchars($cf('label_email', 'Email'), ENT_QUOTES, 'UTF-8') ?>: <span class="color-red" id="demail"><?= htmlspecialchars($cf('required_asterisk', '*'), ENT_QUOTES, 'UTF-8') ?></span></label>
                                <input type="text" class="form-control" name="email" id="email" autocomplete="off"
                                    value="<?= $val('email') ?>" required="" placeholder="<?= htmlspecialchars($cf('placeholder_email', 'Enter email'), ENT_QUOTES, 'UTF-8') ?>" onblur="getDuplicateEmail();">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="passwodeye">
                                    <label><?= htmlspecialchars($cf('label_password', 'Password'), ENT_QUOTES, 'UTF-8') ?>: <span class="color-red"><?= htmlspecialchars($cf('required_asterisk', '*'), ENT_QUOTES, 'UTF-8') ?></span></label>
                                    <input type="password" class="form-control" name="password" id="password"
                                        autocomplete="off" value="<?= $val('password') ?>" required="" minlength="8">
                                    <span toggle="#password-field"
                                        class="fa fa-fw fa-eye field_icon toggle-password"></span>
                                </div>
                            </div>
                        </div>

                        <?php $this->load->view('admin/company_address_fields', [
                            'cfLang' => isset($cfLang) && is_array($cfLang) ? $cfLang : [],
                            'countries' => isset($countries) && is_array($countries) ? $countries : [],
                            'companyAddressRow' => (object) (isset($formData) && is_array($formData) ? $formData : []),
                        ]); ?>
                        <div class="form-group row">
                            <div class="col-md-6 col-lg-4">
                                <label><?= htmlspecialchars($cf('label_zone', 'Zone'), ENT_QUOTES, 'UTF-8') ?>:</label>
                                <input type="text" class="form-control cust-admin-addr-upper" name="zone" id="admin_company_zone" value="<?= $val('zone') ?>">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label><?= htmlspecialchars($cf('label_phone_number', 'Phone number'), ENT_QUOTES, 'UTF-8') ?>: <span class="color-red" id="dphone"></span></label>
                                <input type="hidden" name="phone_number" id="phone_number" value="<?= $val('phone_number') ?>">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text px-2 admin-phone-prefix-locked bg-light text-dark font-weight-bold border" id="phone_country_prefix" style="min-width: 5.5rem; user-select: none;" title=""></span>
                                    </div>
                                    <input type="text" class="form-control" id="phone_number_local" autocomplete="tel-national"
                                        placeholder="<?= htmlspecialchars($cf('placeholder_phone_local', 'National number'), ENT_QUOTES, 'UTF-8') ?>"
                                        onblur="getDuplicatephone();">
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label><?= htmlspecialchars($cf('label_cell_number', 'Cell number'), ENT_QUOTES, 'UTF-8') ?>:</label>
                                <input type="hidden" name="cell_number" id="cell_number" value="<?= $val('cell_number') ?>">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text px-2 admin-phone-prefix-locked bg-light text-dark font-weight-bold border" id="cell_country_prefix" style="min-width: 5.5rem; user-select: none;" title=""></span>
                                    </div>
                                    <input type="text" class="form-control" id="cell_number_local" autocomplete="tel-national"
                                        placeholder="<?= htmlspecialchars($cf('placeholder_phone_local', 'National number'), ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col">
                                <label><?= htmlspecialchars($cf('label_website', 'Website'), ENT_QUOTES, 'UTF-8') ?>:</label>
                                <input type="text" class="form-control" name="website" id="website" autocomplete="off"
                                    value="<?= $val('website') ?>">
                            </div>
                            <div class="col">
                                <label><?= htmlspecialchars($cf('label_max_customers', 'Maximum number of customers'), ENT_QUOTES, 'UTF-8') ?>:</label>
                                <input type="text" class="form-control" name="max_number_of_clients"
                                    id="max_number_of_clients" autocomplete="off" value="<?= $val('max_number_of_clients') ?>">
                            </div>
                            <div class="col">
                                <label><?= htmlspecialchars($cf('label_currency_symbol', 'Currency'), ENT_QUOTES, 'UTF-8') ?>:</label>
                                <?php $this->load->view('admin/company_currency_picker', [
                                    'cfLang' => isset($cfLang) && is_array($cfLang) ? $cfLang : [],
                                    'currencies_pather' => isset($currencies_pather) && is_array($currencies_pather) ? $currencies_pather : [],
                                    'adminCountrySortnameMap' => isset($adminCountrySortnameMap) && is_array($adminCountrySortnameMap) ? $adminCountrySortnameMap : [],
                                    'adminSelectedCurrencyId' => (int) ($adminSelectedCurrencyId ?? ($formData['currency'] ?? 0)),
                                ]); ?>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col">
                                <label><?= htmlspecialchars($cf('label_max_orders_month', 'Maximum number of orders per month'), ENT_QUOTES, 'UTF-8') ?>:</label>
                                <input type="text" class="form-control" name="max_number_of_orders"
                                    id="max_number_of_orders" autocomplete="off" value="<?= $val('max_number_of_orders') ?>">
                            </div>
                            <div class="col">
                                <label><?= htmlspecialchars($cf('label_max_salesman', 'Maximum number of salespeople'), ENT_QUOTES, 'UTF-8') ?>:</label>
                                <input type="text" class="form-control" name="max_number_of_vendors"
                                    id="max_number_of_vendors" autocomplete="off" value="<?= $val('max_number_of_vendors') ?>">
                            </div>
                            <div class="col">
                                <label><?= htmlspecialchars($cf('label_total_price', 'Total price'), ENT_QUOTES, 'UTF-8') ?>:</label>
                                <input type="text" class="form-control" name="total_price" id="total_price"
                                    autocomplete="off" value="<?= $val('total_price') ?>">
                            </div>
                            <div class="col">
                                <div class="form-group">
                                    <label><?= htmlspecialchars($cf('label_initial_order_number', 'Initial order number'), ENT_QUOTES, 'UTF-8') ?>:</label>
                                    <input name="initial_order_number" type="number" class="form-control" value="<?= $val('initial_order_number', '1') ?>">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col">
                                <label><?= htmlspecialchars($cf('label_sell_with_inventory', 'Sell with inventory'), ENT_QUOTES, 'UTF-8') ?></label>
                                <br>
                                <div class="btn-group w-100">
                                    <input id="entero" hidden type="radio" name="sell_with_inventory" class="btn-check"
                                        value="1" <?= (($formData['sell_with_inventory'] ?? '0') === '1') ? 'checked' : '' ?>>
                                    <label for="entero"
                                        class="especial_label label-especial border w-50 text-center"><?= htmlspecialchars($cf('option_yes', 'Yes'), ENT_QUOTES, 'UTF-8') ?></label>
                                    <input id="decimal" hidden type="radio" name="sell_with_inventory" class="btn-check"
                                        value="0" <?= (($formData['sell_with_inventory'] ?? '0') !== '1') ? 'checked' : '' ?>>
                                    <label for="decimal"
                                        class="especial_label label-especial border w-50 text-center"><?= htmlspecialchars($cf('option_no', 'No'), ENT_QUOTES, 'UTF-8') ?></label>
                                </div>
                            </div>
                            <div class="form-group col">
                                <label><?= htmlspecialchars($cf('label_show_inventory', 'Show inventory'), ENT_QUOTES, 'UTF-8') ?></label>
                                <br>
                                <div class="btn-group w-100">
                                    <input id="show_inventory_si" hidden type="radio" name="show_inventory"
                                        class="btn-check" value="1" <?= (($formData['show_inventory'] ?? '0') === '1') ? 'checked' : '' ?>>
                                    <label for="show_inventory_si"
                                        class="especial_label label-especial border w-50 text-center"><?= htmlspecialchars($cf('option_yes', 'Yes'), ENT_QUOTES, 'UTF-8') ?></label>
                                    <input id="show_inventory_no" hidden type="radio" name="show_inventory"
                                        class="btn-check" value="0" <?= (($formData['show_inventory'] ?? '0') !== '1') ? 'checked' : '' ?>>
                                    <label for="show_inventory_no"
                                        class="especial_label label-especial border w-50 text-center"><?= htmlspecialchars($cf('option_no', 'No'), ENT_QUOTES, 'UTF-8') ?></label>
                                </div>
                            </div>

                            <div class="form-group col">
                                <label><?= htmlspecialchars($cf('label_show_semaforo_stock', 'Show stock traffic light'), ENT_QUOTES, 'UTF-8') ?></label>
                                <br>
                                <div class="btn-group w-100">
                                    <input id="show_semaforo_stock_si" hidden type="radio" name="show_semaforo_stock"
                                        class="btn-check" value="1" <?= (($formData['show_semaforo_stock'] ?? '0') === '1') ? 'checked' : '' ?>>
                                    <label for="show_semaforo_stock_si"
                                        class="especial_label label-especial border w-50 text-center"><?= htmlspecialchars($cf('option_yes', 'Yes'), ENT_QUOTES, 'UTF-8') ?></label>
                                    <input id="show_semaforo_stock_no" hidden type="radio" name="show_semaforo_stock"
                                        class="btn-check" value="0" <?= (($formData['show_semaforo_stock'] ?? '0') !== '1') ? 'checked' : '' ?>>
                                    <label for="show_semaforo_stock_no"
                                        class="especial_label label-especial border w-50 text-center"><?= htmlspecialchars($cf('option_no', 'No'), ENT_QUOTES, 'UTF-8') ?></label>
                                </div>
                            </div>

                        </div>
                        <?php
                        $waLabelUse = (string) ($cfLang['label_use_whatsapp'] ?? 'Use WhatsApp');
                        $waLabelInstanceName = (string) ($cfLang['label_whatsapp_instance_name'] ?? 'Instance name');
                        $waPlaceholderInstanceName = (string) ($cfLang['placeholder_whatsapp_instance_name'] ?? 'Enter the WhatsApp instance name');
                        $waLabelApikey = (string) ($cfLang['label_whatsapp_apikey'] ?? 'WhatsApp API key');
                        $waPlaceholderApikey = (string) ($cfLang['placeholder_whatsapp_apikey'] ?? 'Enter the WhatsApp API key');
                        $waUseChecked = function_exists('admin_company_whatsapp_flag_is_on')
                            ? admin_company_whatsapp_flag_is_on($formData['use_whatsapp'] ?? ($formData['whatsapp_instance'] ?? 0))
                            : ((string) ($formData['use_whatsapp'] ?? '0') === '1');
                        $waInstanceNameVal = (string) ($formData['whatsapp_instance_name'] ?? '');
                        $waApikeyVal = (string) ($formData['whatsapp_apikey'] ?? '');
                        ?>
                        <div class="form-group row">
                            <div class="col-md-12 col-lg-3">
                                <input type="hidden" name="use_whatsapp" value="0">
                                <div class="custom-control custom-checkbox mt-4">
                                    <input type="checkbox" class="custom-control-input"
                                           id="company_use_whatsapp" name="use_whatsapp" value="1"
                                           <?= $waUseChecked ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="company_use_whatsapp">
                                        <?= htmlspecialchars($waLabelUse, ENT_QUOTES, 'UTF-8') ?>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label><?= htmlspecialchars($waLabelInstanceName, ENT_QUOTES, 'UTF-8') ?></label>
                                <input type="text" class="form-control" name="whatsapp_instance_name"
                                       id="company_whatsapp_instance_name" maxlength="255" autocomplete="off"
                                       value="<?= htmlspecialchars($waInstanceNameVal, ENT_QUOTES, 'UTF-8') ?>"
                                       placeholder="<?= htmlspecialchars($waPlaceholderInstanceName, ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="col-md-6 col-lg-5">
                                <label><?= htmlspecialchars($waLabelApikey, ENT_QUOTES, 'UTF-8') ?></label>
                                <input type="text" class="form-control" name="whatsapp_apikey"
                                       id="company_whatsapp_apikey" maxlength="255" autocomplete="off"
                                       value="<?= htmlspecialchars($waApikeyVal, ENT_QUOTES, 'UTF-8') ?>"
                                       placeholder="<?= htmlspecialchars($waPlaceholderApikey, ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label><?= htmlspecialchars($cf('label_image', 'Image'), ENT_QUOTES, 'UTF-8') ?>:</label><br />
                                    <div class="fileinput fileinput-new" data-provides="fileinput">
                                        <div class="fileinput-new thumbnail" style="width: 250px; height:150px;">
                                            <img src="<?= base_url('uploads/noimg.png') ?>" alt="">
                                        </div>
                                        <div class="fileinput-preview fileinput-exists thumbnail"
                                            style="max-width: 250px; max-height: 150px;"></div>
                                        <div>
                                            <span class="btn btn-default btn-file">
                                                <span class="fileinput-new"><?= htmlspecialchars($cf('btn_select_image', 'Select image'), ENT_QUOTES, 'UTF-8') ?></span>
                                                <span class="fileinput-exists"><?= htmlspecialchars($cf('btn_change_image', 'Change'), ENT_QUOTES, 'UTF-8') ?></span>
                                                <input type="file" name="images" accept="images/*">
                                            </span>
                                            <a href="javascript:void(0)" class="btn btn-danger fileinput-exists"
                                                data-dismiss="fileinput"><?= htmlspecialchars($cf('btn_remove_image', 'Remove'), ENT_QUOTES, 'UTF-8') ?></a>
                                        </div>
                                    </div>
                                    <div class="clearfix margin-top-10 m-b-20 f12" style="display: block;">
                                        <span class="label label-danger"><?= htmlspecialchars($cf('label_image_format', 'Format'), ENT_QUOTES, 'UTF-8') ?></span>&nbsp;<?= htmlspecialchars($cf('label_image_formats', 'jpg, jpeg, png, gif'), ENT_QUOTES, 'UTF-8') ?>
                                        <span class="label label-danger"><?= htmlspecialchars($cf('label_image_max_size', 'Max upload size'), ENT_QUOTES, 'UTF-8') ?></span>&nbsp;<?= htmlspecialchars($cf('label_image_max_size_value', '10 MB'), ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <h4><?= htmlspecialchars($cf('section_company_contacts', 'Company contacts'), ENT_QUOTES, 'UTF-8') ?></h4>
                                <?php
                                $contactEmailHint = $cf('contact_email_required_if_name', 'If you enter a contact name, a valid email address is required for that row.');
                                if ($contactEmailHint !== '') { ?>
                                <p class="text-muted small mb-2"><?= htmlspecialchars($contactEmailHint, ENT_QUOTES, 'UTF-8') ?></p>
                                <?php } ?>
                                <hr>
                                <div class="listing-box" id="weblinks">
                                    <div class="webfield-clone">
                                        <div class="boxInput">
                                            <div class="boxform">
                                                <div class="row">
                                                    <div class="col-lg-3 form-group mb-2">
                                                        <label><?= htmlspecialchars($cf('label_contact_name', 'Name'), ENT_QUOTES, 'UTF-8') ?></label>
                                                        <input type="text" name="cname[]" class="form-control">
                                                    </div>
                                                    <div class="col-lg-3 form-group mb-2">
                                                        <label><?= htmlspecialchars($cf('label_contact_email', 'Email'), ENT_QUOTES, 'UTF-8') ?></label>
                                                        <input type="email" class="form-control" name="cemail[]">
                                                    </div>
                                                    <div class="col-lg-2 form-group mb-2">
                                                        <label><?= htmlspecialchars($cf('label_contact_website', 'Website'), ENT_QUOTES, 'UTF-8') ?></label>
                                                        <input type="text" class="form-control" name="cwebsite[]">
                                                    </div>
                                                    <div class="col-lg-2 form-group mb-2">
                                                        <label><?= htmlspecialchars($cf('label_contact_phone', 'Phone'), ENT_QUOTES, 'UTF-8') ?></label>
                                                        <input type="text" class="form-control" name="cphone[]">
                                                    </div>
                                                    <div class="col-lg-2 form-group mb-2">
                                                        <label><?= htmlspecialchars($cf('label_contact_cellphone', 'Cellphone'), ENT_QUOTES, 'UTF-8') ?></label>
                                                        <input type="text" class="form-control" name="ccphone[]">
                                                    </div>
                                                </div>
                                            </div>
                                            <a href="#" class="remove-weblink">
                                                <span class="mdi mdi-delete"></span>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="weblink-list"></div>
                                    <a href="#weblinks" class="add-web btn btn-success mb-4 float-right">
                                        <i class="mdi mdi-plus"></i> <span><?= htmlspecialchars($cf('btn_add_more', 'Add more'), ENT_QUOTES, 'UTF-8') ?></span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-success mr-2"><?= htmlspecialchars($cf('btn_submit', 'Save'), ENT_QUOTES, 'UTF-8') ?></button>
                            <a class="btn btn-danger mr-2" href="<?= admin_url('company/lists') ?>">
                                <?= htmlspecialchars($cf('btn_back', 'Back'), ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </div>
                    </form>


                </div>
            </div>
        </div>
        <script>
        var adminContactEmailInvalidMsg = <?= json_encode((string) $cf('contact_row_invalid_email', 'Each company contact with a name must have a valid email address.'), JSON_UNESCAPED_UNICODE) ?>;
        var adminDuplicateEmailMsg = <?= json_encode((string) $cf('duplicate_email_exists', 'This email already exists in the database'), JSON_UNESCAPED_UNICODE) ?>;
        var adminDuplicateNameMsg = <?= json_encode((string) $cf('duplicate_name_exists', 'This company name already exists in the database'), JSON_UNESCAPED_UNICODE) ?>;
        var adminDuplicatePhoneMsg = <?= json_encode((string) $cf('duplicate_phone_exists', 'This phone number already exists in the database'), JSON_UNESCAPED_UNICODE) ?>;
        var adminRequiredAsterisk = <?= json_encode((string) $cf('required_asterisk', '*'), JSON_UNESCAPED_UNICODE) ?>;

        $(document).ready(function() {
        })

        function adminIsValidContactEmail(value) {
            var s = String(value || '').trim();
            if (s === '') {
                return false;
            }
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(s);
        }

        function adminValidateCompanyContacts() {
            var ok = true;
            var firstBad = null;
            $('#weblinks .boxInput').each(function() {
                var $row = $(this);
                var name = String($row.find('input[name="cname[]"]').val() || '').trim();
                if (name === '') {
                    $row.find('input[name="cemail[]"]').removeClass('is-invalid');
                    return;
                }
                var $email = $row.find('input[name="cemail[]"]');
                if (!adminIsValidContactEmail($email.val())) {
                    ok = false;
                    $email.addClass('is-invalid');
                    if (!firstBad) {
                        firstBad = $email.get(0);
                    }
                } else {
                    $email.removeClass('is-invalid');
                }
            });
            if (!ok && adminContactEmailInvalidMsg) {
                if (typeof alert_func === 'function') {
                    alert_func([adminContactEmailInvalidMsg, 'error', '#DD6B55']);
                } else {
                    alert(adminContactEmailInvalidMsg);
                }
                if (firstBad && typeof firstBad.focus === 'function') {
                    firstBad.focus();
                }
            }
            return ok;
        }

        $('form.forms-sample').on('submit', function(e) {
            if (!adminValidateCompanyContacts()) {
                e.preventDefault();
                return false;
            }
        });

        function getDuplicateEmail() {

            var rdemail = $("#email").val();

            $.ajax({
                    url: '<?= admin_url("company/fetchduplicateEmail/") ?>',
                    type: 'POST',
                    dataType: 'html',
                    data: {
                        rdemail: String(rdemail),
                    },
                })
                .done(function(dataemail) {
                    if (dataemail == 1) {
                        $("#demail").html(adminDuplicateEmailMsg);
                        $("#email").val(' ');
                        return false;
                    } else {
                        $("#demail").html(adminRequiredAsterisk);
                    }

                });
        }

        function getDuplicateName() {
            var rdname = $("#name").val();

            $.ajax({
                    url: '<?= admin_url("company/fetchduplicateName/") ?>',
                    type: 'POST',
                    dataType: 'html',
                    data: {
                        rdname: String(rdname),
                    },
                })
                .done(function(dataname) {
                    if (dataname == 1) {
                        $("#dname").html(adminDuplicateNameMsg);
                        $("#name").val(' ');
                        return false;
                    } else {
                        $("#dname").html(adminRequiredAsterisk);
                    }
                });
        }

        function getDuplicatephone() {
            adminSyncPhoneHiddens();
            var rdphone = $("#phone_number").val();

            $.ajax({
                    url: '<?= admin_url("company/fetchduplicatePhone/") ?>',
                    type: 'POST',
                    dataType: 'html',
                    data: {
                        rdphone: String(rdphone),
                    },
                })
                .done(function(dataphone) {
                    if (dataphone == 1) {
                        $("#dphone").html(adminDuplicatePhoneMsg);
                        $("#phone_number").val('');
                        $("#phone_number_local").val('');
                        return false;
                    } else {
                        $("#dphone").html("");
                    }

                });
        }

        function adminComposeFullAddress() {
            var parts = [];
            var addr = String($('#admin_company_address').val() || '').trim();
            if (addr) parts.push(addr.toUpperCase());
            var $cs = $('#admin_company_country_id option:selected');
            if ($cs.val()) {
                var cn = String($cs.text() || '').trim();
                if (cn) parts.push(cn.toUpperCase());
            }
            var st = String($('#admin_company_state').val() || '').trim();
            if (st) parts.push(st.toUpperCase());
            var city = String($('#admin_company_city').val() || '').trim();
            if (city) parts.push(city.toUpperCase());
            var zip = String($('#admin_company_zip_code').val() || '').trim();
            if (zip) parts.push(zip.toUpperCase());
            var zone = String($('#admin_company_zone').val() || '').trim();
            if (zone) parts.push(zone.toUpperCase());
            $('#admin_company_full_address').val(parts.join(', '));
        }
        $(document).on('input change', '#admin_company_address, #admin_company_state, #admin_company_city, #admin_company_zip_code, #admin_company_zone', function() {
            adminComposeFullAddress();
        });
        $(document).on('input', '.cust-admin-addr-upper', function() {
            var el = this;
            var v = el.value;
            var nv = v.toUpperCase();
            if (v !== nv) {
                var pos = el.selectionStart;
                el.value = nv;
                if (el.setSelectionRange) try { el.setSelectionRange(pos, pos); } catch (e) {}
            }
        });
        adminComposeFullAddress();
        </script>
