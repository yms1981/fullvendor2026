<?php
$cfLang = isset($cfLang) && is_array($cfLang) ? $cfLang : [];
$cf = static function (string $key, string $fallback = '') use ($cfLang): string {
	$v = trim((string) ($cfLang[$key] ?? ''));

	return $v !== '' ? $v : $fallback;
};
$req = htmlspecialchars($cf('required_asterisk', '*'), ENT_QUOTES, 'UTF-8');
?>
<div class="container-fluid page-body-wrapper">
    <div class="main-panel">
        <div class="container-fluid pt-3">
            <div class="page-header">
                <h3 class="page-title"> <?=@$title?></h3>
            </div>
            <div class="card">
                <div class="card-body">
                    <form class="forms-sample" action="<?= admin_url('company/update') ?>" method="post"
                        enctype="multipart/form-data">
                        <div class="form-group row">
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= htmlspecialchars($cf('label_company_category', 'Company category'), ENT_QUOTES, 'UTF-8') ?>: <span class="color-red"><?= $req ?></span></label>
                                    <select class="form-control" name="category_id">
                                        <?php
                                        $selCompanyCat = isset($data->company_category_id)
                                            ? $data->company_category_id
                                            : ($data->category_id ?? null);
                                        ?>
                                        <?php foreach ($catlist as $cat) { ?>
                                        <option value="<?=$cat->category_id?>"
                                            <?php if ((string) $selCompanyCat === (string) $cat->category_id) { echo "selected";} ?>>
                                            <?=$cat->category_name?></option>
                                        <?php } ?>
                                    </select>
                                    <span class="error"><?php echo form_error('category_id'); ?></span>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label><?= htmlspecialchars($cf('label_company_name', 'Company name'), ENT_QUOTES, 'UTF-8') ?>: <span class="color-red"><?= $req ?></span></label>
                                <input type="text" class="form-control" name="name" id="name" value="<?= $data->name ?>"
                                    required="" placeholder="<?= htmlspecialchars($cf('placeholder_company_name', 'Company name'), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="company_id" id="company_id" value="<?= $data->company_id ?>">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label><?= htmlspecialchars($cf('label_short_name', 'Short name'), ENT_QUOTES, 'UTF-8') ?>: <span class="color-red"><?= $req ?></span></label>
                                <input type="text" class="form-control" name="short_name" id="short_name"
                                    autocomplete="off" value="<?= $data->short_name ?>" required=""
                                    placeholder="<?= htmlspecialchars($cf('placeholder_short_name', 'Short name'), ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= htmlspecialchars($cf('label_language', 'Language'), ENT_QUOTES, 'UTF-8') ?>: <span class="color-red"><?= $req ?></span></label>
                                    <select class="form-control" name="language_id">
                                        <?php foreach ($language as $lang) { ?>
                                        <option value="<?=$lang->language_id?>"
                                            <?php if(@$data->language_id==$lang->language_id) { echo "selected";} ?>>
                                            <?=$lang->name?></option>
                                        <?php } ?>
                                    </select>
                                    <span class="error"><?php echo form_error('language_id'); ?></span>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label><?= htmlspecialchars($cf('label_email', 'Email'), ENT_QUOTES, 'UTF-8') ?>: <span class="color-red"><?= $req ?></span></label>
                                <input type="text" class="form-control" name="email" id="email" autocomplete="off"
                                    value="<?= $data->email ?>" readonly required="" placeholder="<?= htmlspecialchars($cf('placeholder_email', 'Enter email'), ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="passwodeye">
                                    <label><?= htmlspecialchars($cf('label_password', 'Password'), ENT_QUOTES, 'UTF-8') ?>: <span class="color-red"><?= $req ?></span></label>
                                    <input type="password" class="form-control" name="password" id="password"
                                        autocomplete="off">
                                    <span toggle="#password-field"
                                        class="fa fa-fw fa-eye field_icon toggle-password"></span>
                                </div>
                            </div>
                        </div>

                        <?php $this->load->view('admin/company_address_fields', [
                            'cfLang' => isset($cfLang) && is_array($cfLang) ? $cfLang : [],
                            'countries' => isset($countries) && is_array($countries) ? $countries : [],
                            'companyAddressRow' => $data,
                        ]); ?>
                        <div class="form-group row">
                            <div class="col-md-6 col-lg-4">
                                <label><?= htmlspecialchars((string) ($cfLang['label_zone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>:</label>
                                <input type="text" class="form-control cust-admin-addr-upper" name="zone" id="admin_company_zone" value="<?= htmlspecialchars((string) ($data->zone ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label><?= htmlspecialchars($cf('label_phone_number', 'Phone number'), ENT_QUOTES, 'UTF-8') ?>: <span class="color-red"><?= $req ?></span></label>
                                <input type="hidden" name="phone_number" id="phone_number" value="<?= htmlspecialchars((string) ($data->phone_number ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text px-2 admin-phone-prefix-locked bg-light text-dark font-weight-bold border" id="phone_country_prefix" style="min-width: 5.5rem; user-select: none;" title=""></span>
                                    </div>
                                    <input type="text" class="form-control" id="phone_number_local" autocomplete="tel-national" required=""
                                        placeholder="<?= htmlspecialchars((string) ($cfLang['placeholder_phone_local'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        value="">
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label><?= htmlspecialchars($cf('label_cell_number', 'Cell number'), ENT_QUOTES, 'UTF-8') ?>: <span class="color-red"><?= $req ?></span></label>
                                <input type="hidden" name="cell_number" id="cell_number" value="<?= htmlspecialchars((string) ($data->cell_number ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text px-2 admin-phone-prefix-locked bg-light text-dark font-weight-bold border" id="cell_country_prefix" style="min-width: 5.5rem; user-select: none;" title=""></span>
                                    </div>
                                    <input type="text" class="form-control" id="cell_number_local" autocomplete="tel-national" required=""
                                        placeholder="<?= htmlspecialchars((string) ($cfLang['placeholder_phone_local'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        value="">
                                </div>
                            </div>
                        </div>
                        <?php
                        $plan = isset($companyActivePlan) && is_object($companyActivePlan) ? $companyActivePlan : null;
                        $planMaxClients = $plan && isset($plan->max_clients) && $plan->max_clients !== null
                            ? (int) $plan->max_clients
                            : (int) ($data->max_number_of_clients ?? 0);
                        $planMaxOrders = $plan && isset($plan->max_orders) && $plan->max_orders !== null
                            ? (int) $plan->max_orders
                            : (int) ($data->max_number_of_orders ?? 0);
                        $planMaxUsers = $plan && isset($plan->max_users) && $plan->max_users !== null
                            ? (int) $plan->max_users
                            : (int) ($data->max_number_of_vendors ?? 0);
                        $planNameForHelp = $plan
                            ? trim((string) ($plan->title ?? ($plan->plan_name_eng ?? ($plan->plan_name_spa ?? ''))))
                            : '';
                        $labelMaxClientsText = (string) ($cfLang['label_max_customers'] ?? 'Maximum number of Customers');
                        $labelMaxOrdersText = (string) ($cfLang['label_max_orders_month'] ?? 'Maximum number of orders per month');
                        $labelMaxSalesmanText = (string) ($cfLang['label_max_salesman'] ?? 'Maximum number of Salesman');
                        if (! $plan) {
                            $planHelpText = (string) ($cfLang['hint_field_no_active_plan'] ?? 'This company has no active subscription plan. Not editable.');
                        } elseif ($planNameForHelp !== '') {
                            $planHelpText = sprintf(
                                (string) ($cfLang['hint_field_from_plan_named'] ?? 'Provided by subscription plan "%s". Not editable.'),
                                $planNameForHelp
                            );
                        } else {
                            $planHelpText = (string) ($cfLang['hint_field_from_plan_anon'] ?? 'Provided by the active subscription plan. Not editable.');
                        }
                        ?>
                        <div class="form-group row">
                            <div class="col">
                                <label><?= htmlspecialchars($cf('label_website', 'Website'), ENT_QUOTES, 'UTF-8') ?>: <span class="color-red"><?= $req ?></span></label>
                                <input type="text" class="form-control" name="website" id="website" autocomplete="off"
                                    value="<?= $data->website ?>">
                            </div>
                            <div class="col">
                                <label><?= htmlspecialchars($labelMaxClientsText, ENT_QUOTES, 'UTF-8') ?>:</label>
                                <input type="text" class="form-control bg-light" name="max_number_of_clients"
                                    id="max_number_of_clients" autocomplete="off" readonly tabindex="-1"
                                    value="<?= htmlspecialchars((string) $planMaxClients, ENT_QUOTES, 'UTF-8') ?>">
                                <small class="form-text text-muted"><?= htmlspecialchars($planHelpText, ENT_QUOTES, 'UTF-8') ?></small>
                            </div>
                            <div class="col">
                                <label><?= htmlspecialchars((string) ($cfLang['label_currency_symbol'] ?? 'Currency'), ENT_QUOTES, 'UTF-8') ?>: <span class="color-red"><?= $req ?></span></label>
                                <?php $this->load->view('admin/company_currency_picker', [
                                    'cfLang' => isset($cfLang) && is_array($cfLang) ? $cfLang : [],
                                    'currencies_pather' => isset($currencies_pather) && is_array($currencies_pather) ? $currencies_pather : [],
                                    'adminCountrySortnameMap' => isset($adminCountrySortnameMap) && is_array($adminCountrySortnameMap) ? $adminCountrySortnameMap : [],
                                    'adminSelectedCurrencyId' => (int) ($adminSelectedCurrencyId ?? ($data->currency ?? 0)),
                                ]); ?>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col">
                                <label><?= htmlspecialchars($labelMaxOrdersText, ENT_QUOTES, 'UTF-8') ?>:</label>
                                <input type="text" class="form-control bg-light" name="max_number_of_orders"
                                    id="max_number_of_orders" autocomplete="off" readonly tabindex="-1"
                                    value="<?= htmlspecialchars((string) $planMaxOrders, ENT_QUOTES, 'UTF-8') ?>">
                                <small class="form-text text-muted"><?= htmlspecialchars($planHelpText, ENT_QUOTES, 'UTF-8') ?></small>
                            </div>
                            <div class="col">
                                <label><?= htmlspecialchars($labelMaxSalesmanText, ENT_QUOTES, 'UTF-8') ?>:</label>
                                <input type="text" class="form-control bg-light" name="max_number_of_vendors"
                                    id="max_number_of_vendors" autocomplete="off" readonly tabindex="-1"
                                    value="<?= htmlspecialchars((string) $planMaxUsers, ENT_QUOTES, 'UTF-8') ?>">
                                <small class="form-text text-muted"><?= htmlspecialchars($planHelpText, ENT_QUOTES, 'UTF-8') ?></small>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col">
                                <label><?= htmlspecialchars($cf('label_total_price', 'Total price'), ENT_QUOTES, 'UTF-8') ?>: <span class="color-red"><?= $req ?></span></label>
                                <input type="text" class="form-control" name="total_price" id="total_price"
                                    autocomplete="off" required="" value="<?= $data->total_price ?>">
                            </div>

                            <div class="col">
                                <div class="form-group">
                                    <?php
                                    $ionLabel = (string) ($cfLang['label_initial_order_number'] ?? 'Initial order number');
                                    $ionVal = (string) $initialOrderNumber;
                                    $ionHelp = '';
                                    if (isset($nextOrderNumber)) {
                                        if ($hasInitialOrderNumber) {
                                            $nxTpl = (string) ($cfLang['text_order_next_parenthetical'] ?? '(next %s)');
                                            $ionHelp = sprintf($nxTpl, (string) $nextOrderNumber);
                                        } else {
                                            $phTpl = (string) ($cfLang['placeholder_initial_order_next'] ?? 'The next order number would currently be %s');
                                            $ionHelp = sprintf($phTpl, (string) $nextOrderNumber);
                                        }
                                    }
                                    ?>
                                    <label>
                                        <?= htmlspecialchars($ionLabel, ENT_QUOTES, 'UTF-8') ?>:
                                        <?php if ($ionHelp !== ''): ?>
                                            <small class="text-muted ml-1"><?= htmlspecialchars($ionHelp, ENT_QUOTES, 'UTF-8') ?></small>
                                        <?php endif; ?>
                                    </label>
                                    <input
                                        name="initial_order_number"
                                        type="number"
                                        min="1"
                                        class="form-control"
                                        value="<?= htmlspecialchars($ionVal, ENT_QUOTES, 'UTF-8') ?>"
                                        <?php if (! $hasInitialOrderNumber && isset($nextOrderNumber)): ?>
                                            placeholder="<?= htmlspecialchars(sprintf((string) ($cfLang['placeholder_initial_order_next'] ?? 'The next order number would currently be %s'), (string) $nextOrderNumber), ENT_QUOTES, 'UTF-8') ?>"
                                        <?php endif; ?>
                                    >
                                </div>
                            </div>

                            <div class="col">
                                <label><?= htmlspecialchars($cf('label_status', 'Status'), ENT_QUOTES, 'UTF-8') ?>: <span class="color-red"><?= $req ?></span></label>
                                <select class="form-control" name="status" id="status" required="">
                                    <option value="1" <?= ($data->status == 1)? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cf('status_option_enable', 'Enable'), ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                    <option value="0" <?= ($data->status == 0)? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cf('status_option_disable', 'Disable'), ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col">
                                <label><?= htmlspecialchars($cf('label_sell_with_inventory', 'Sell with inventory'), ENT_QUOTES, 'UTF-8') ?></label>
                                <br>
                                <div class="btn-group w-100">
                                    <input id="entero" hidden type="radio" name="sell_with_inventory" class="btn-check"
                                        value="1" <?= ($data->sell_with_inventory == 1)?'checked':'' ?>>
                                    <label for="entero"
                                        class="especial_label label-especial border w-50 text-center"><?= htmlspecialchars($cf('option_yes', 'Yes'), ENT_QUOTES, 'UTF-8') ?></label>
                                    <input id="decimal" hidden type="radio" name="sell_with_inventory" class="btn-check"
                                        value="0" <?= ($data->sell_with_inventory == 0)?'checked':'' ?>>
                                    <label for="decimal"
                                        class="especial_label label-especial border w-50 text-center"><?= htmlspecialchars($cf('option_no', 'No'), ENT_QUOTES, 'UTF-8') ?></label>
                                </div>
                            </div>
                            <div class="form-group col">
                                <label><?= htmlspecialchars($cf('label_show_inventory', 'Show inventory'), ENT_QUOTES, 'UTF-8') ?></label>
                                <br>
                                <div class="btn-group w-100">
                                    <input id="show_inventory_si" hidden type="radio" name="show_inventory"
                                        class="btn-check" value="1" <?= ($data->show_inventory == 1)?'checked':'' ?>>
                                    <label for="show_inventory_si"
                                        class="especial_label label-especial border w-50 text-center"><?= htmlspecialchars($cf('option_yes', 'Yes'), ENT_QUOTES, 'UTF-8') ?></label>
                                    <input id="show_inventory_no" hidden type="radio" name="show_inventory"
                                        class="btn-check" value="0" <?= ($data->show_inventory == 0)?'checked':'' ?>>
                                    <label for="show_inventory_no"
                                        class="especial_label label-especial border w-50 text-center"><?= htmlspecialchars($cf('option_no', 'No'), ENT_QUOTES, 'UTF-8') ?></label>
                                </div>
                            </div>
                            <div class="form-group col">
                                <label><?= htmlspecialchars($cf('label_show_semaforo_stock', 'Show stock traffic light'), ENT_QUOTES, 'UTF-8') ?></label>
                                <br>
                                <div class="btn-group w-100">
                                    <input id="show_semaforo_stock_si" hidden type="radio" name="show_semaforo_stock"
                                        class="btn-check" value="1" <?= ($data->show_semaforo_stock == 1)?'checked':'' ?>>
                                    <label for="show_semaforo_stock_si"
                                        class="especial_label label-especial border w-50 text-center"><?= htmlspecialchars($cf('option_yes', 'Yes'), ENT_QUOTES, 'UTF-8') ?></label>
                                    <input id="show_semaforo_stock_no" hidden type="radio" name="show_semaforo_stock"
                                        class="btn-check" value="0" <?= ($data->show_semaforo_stock == 0)?'checked':'' ?>>
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
                        $waUseChecked = function_exists('admin_company_whatsapp_flag_from_row')
                            ? admin_company_whatsapp_flag_from_row($data)
                            : false;
                        $waInstanceNameVal = isset($data->whatsapp_instance_name) ? (string) $data->whatsapp_instance_name : '';
                        $waApikeyVal = isset($data->whatsapp_apikey) ? (string) $data->whatsapp_apikey : '';
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
                                    <label><?= htmlspecialchars($cf('label_image', 'Image'), ENT_QUOTES, 'UTF-8') ?>: <span class="color-red"><?= $req ?></span></label><br />
                                    <div class="fileinput fileinput-new" data-provides="fileinput">
                                        <div class="fileinput-new thumbnail" style="width: 250px; height:150px;">
                                            <?php
                                            // Mismo origen que el admin: el servidor MVC llama a la API (API_SERVER); el navegador no suele resolver ese host.
                                            $__companyLogoSrc = ! empty($data->image)
                                                ? admin_url('company/serve_company_logo/' . (int) $data->company_id)
                                                    . '?v=' . rawurlencode((string) $data->image)
                                                : '';
                                            ?>
                                            <?php if ($__companyLogoSrc !== '') { ?>
                                            <img src="<?= htmlspecialchars($__companyLogoSrc, ENT_QUOTES, 'UTF-8') ?>" alt=""
                                                onerror="this.onerror=null;this.src='<?= htmlspecialchars(base_url('uploads/noimg.png'), ENT_QUOTES, 'UTF-8') ?>';">
                                            <?php } else { ?>
                                            <img src="<?= base_url('uploads/noimg.png') ?>" alt="">
                                            <?php } ?>
                                        </div>
                                        <div class="fileinput-preview fileinput-exists thumbnail"
                                            style="max-width: 250px; max-height: 150px;"></div>
                                        <div>
                                            <span class="btn btn-default btn-file">
                                                <span class="fileinput-new"><?= htmlspecialchars($cf('btn_select_image', 'Select image'), ENT_QUOTES, 'UTF-8') ?></span>
                                                <span class="fileinput-exists"><?= htmlspecialchars($cf('btn_change_image', 'Change'), ENT_QUOTES, 'UTF-8') ?></span>
                                                <input type="file" name="image" accept="images/*">
                                                <input type="hidden" name="oldImage" value="<?= @$data->image ?>">
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
                                <hr>
                                <div class="listing-box" id="weblinks">
                                    <div class="webfield-clone">
                                        <div class="boxInput">
                                            <div class="boxform">
                                                <div class="row">
                                                    <div class="col-lg-3 form-group mb-2">
                                                        <label><?= htmlspecialchars($cf('label_contact_name', 'Name'), ENT_QUOTES, 'UTF-8') ?></label>
                                                        <input type="text" value="" name="cname[]" class="form-control">
                                                        <input type="hidden" name="ctype[]" value="manager">
                                                    </div>
                                                    <div class="col-lg-3 form-group mb-2">
                                                        <label><?= htmlspecialchars($cf('label_contact_email', 'Email'), ENT_QUOTES, 'UTF-8') ?></label>
                                                        <input type="email" value="" class="form-control"
                                                            name="cemail[]">
                                                    </div>
                                                    <div class="col-lg-2 form-group mb-2">
                                                        <label><?= htmlspecialchars($cf('label_contact_website', 'Website'), ENT_QUOTES, 'UTF-8') ?></label>
                                                        <input type="text" value="" class="form-control"
                                                            name="cwebsite[]">
                                                    </div>
                                                    <div class="col-lg-2 form-group mb-2">
                                                        <label><?= htmlspecialchars($cf('label_contact_phone', 'Phone'), ENT_QUOTES, 'UTF-8') ?></label>
                                                        <input type="text" value="" class="form-control"
                                                            name="cphone[]">
                                                    </div>
                                                    <div class="col-lg-2 form-group mb-2">
                                                        <label><?= htmlspecialchars($cf('label_contact_cellphone', 'Cellphone'), ENT_QUOTES, 'UTF-8') ?></label>
                                                        <input type="text" value="" class="form-control"
                                                            name="ccphone[]">
                                                    </div>
                                                </div>
                                            </div>
                                            <a href="javascript:void(0)" class="remove-weblink" onclick="removeCompanyContactForm(this); return false;">
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
                        <?php
                        $scl = isset($stripeCompanyLang) && is_array($stripeCompanyLang) ? $stripeCompanyLang : [];
                        $sclGet = static function (array $l, string $k, string $fallback = ''): string {
                            $v = isset($l[$k]) && is_string($l[$k]) ? trim($l[$k]) : '';

                            return $v !== '' ? $v : $fallback;
                        };
                        $stripeCustId = isset($data->stripe_id) ? trim((string) $data->stripe_id) : '';
                        $stripeSectionTitle = $sclGet($scl, 'section_stripe', 'Stripe');
                        $stripeCustomerLabel = $sclGet($scl, 'label_stripe_customer_id', 'Stripe customer ID');
                        $stripeSyncLabel = $sclGet($scl, 'btn_stripe_sync_customer', 'Sync Stripe customer');
                        ?>
                        <h5 class="mt-4 mb-2"><i class="mdi mdi-credit-card-outline mr-1" aria-hidden="true"></i><?= htmlspecialchars($stripeSectionTitle, ENT_QUOTES, 'UTF-8') ?></h5>
                        <hr>
                        <div class="form-group row">
                            <div class="col-md-6">
                                <label><?= htmlspecialchars($stripeCustomerLabel, ENT_QUOTES, 'UTF-8') ?></label>
                                <input type="text" class="form-control" readonly value="<?= htmlspecialchars($stripeCustId, ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= htmlspecialchars($cf('placeholder_stripe_customer', 'cus_…'), ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <a class="btn btn-outline-primary" href="<?= admin_url('company/stripe_sync_customer/' . (int) $data->company_id) ?>">
                                    <i class="mdi mdi-sync mr-1" aria-hidden="true"></i><?= htmlspecialchars($stripeSyncLabel, ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </div>
                        </div>
                        <div class="form-group mt-3">
                            <button type="submit" class="btn btn-success mr-2"><?= htmlspecialchars($cf('btn_submit', 'Save'), ENT_QUOTES, 'UTF-8') ?></button>
                            <a class="btn btn-danger mr-2" href="<?= admin_url('company/lists') ?>">
                                <?= htmlspecialchars($cf('btn_back', 'Back'), ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </div>
                    </form>
                    <h4 class="text-uppercase text-center text-success mb-4"><?= htmlspecialchars($cf('section_manager_users', 'Manager users'), ENT_QUOTES, 'UTF-8') ?></h4>
                    <table class="table">
                        <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th><?= htmlspecialchars($cf('company_list_col_image', 'Image'), ENT_QUOTES, 'UTF-8') ?></th>
                                <th><?= htmlspecialchars($cf('company_list_col_name', 'Company name'), ENT_QUOTES, 'UTF-8') ?></th>
                                <th><?= htmlspecialchars($cf('company_list_col_email', 'Email'), ENT_QUOTES, 'UTF-8') ?></th>
                                <th><?= htmlspecialchars($cf('company_list_col_phone', 'Phone'), ENT_QUOTES, 'UTF-8') ?></th>
                                <th><?= htmlspecialchars($cf('company_list_col_status', 'Status'), ENT_QUOTES, 'UTF-8') ?></th>
                                <th><?= htmlspecialchars($cf('company_list_col_created', 'Created'), ENT_QUOTES, 'UTF-8') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; ?>
                            <?php foreach ($userlist as $key => $v): ?>
                            <tr>
                                <td><?=$i?></td>
                                <td>
                                    <?php
                                        $img = (string) ($v->image ?? '');
                                        $imgSrc = function_exists('catalog_public_no_image_url')
                                            ? catalog_public_no_image_url()
                                            : base_url('uploads/noimg.png');
                                        if ($img !== '') {
                                            if (function_exists('fv_user_avatar_image_url')) {
                                                $resolved = fv_user_avatar_image_url($v);
                                                if ($resolved !== '') {
                                                    $imgSrc = $resolved;
                                                }
                                            } elseif (file_exists('./uploads/users/'.$img)) {
                                                $imgSrc = base_url('uploads/users/'.$img);
                                            } elseif (function_exists('company_logo_is_api_image') && company_logo_is_api_image($img)) {
                                                $imgSrc = company_logo_url($img);
                                            } elseif (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i', $img)
                                                && function_exists('fv_api_image_url')) {
                                                $imgSrc = fv_api_image_url($img);
                                            } elseif (file_exists('./uploads/company/'.$img)) {
                                                $imgSrc = company_logo_url($img);
                                            } elseif (file_exists('./uploads/'.$img)) {
                                                $imgSrc = base_url('uploads/'.$img);
                                            }
                                        }
                                    ?>
                                    <?php if ($img !== '' && $imgSrc !== '') { ?>
                                    <img src="<?= htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy" onerror="this.onerror=null;this.src='<?= htmlspecialchars(function_exists('catalog_public_no_image_url') ? catalog_public_no_image_url() : base_url('uploads/noimg.png'), ENT_QUOTES, 'UTF-8') ?>';">
                                    <?php } else { ?>
                                    <img src="<?= htmlspecialchars(function_exists('catalog_public_no_image_url') ? catalog_public_no_image_url() : base_url('uploads/noimg.png'), ENT_QUOTES, 'UTF-8') ?>" alt="">
                                    <?php } ?>
                                </td>
                                <td><?= @$v->name ?></td>
                                <td><?= @$v->email ?></td>
                                <td><?= htmlspecialchars((string) ($v->phone ?? ($v->cellphone ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= !empty($v->deleted_at) ? htmlspecialchars($cf('status_option_disable', 'Disable'), ENT_QUOTES, 'UTF-8') : htmlspecialchars($cf('status_option_enable', 'Enable'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?php $createdUser = (string) ($v->created_at ?? ''); ?>
                                    <?= $createdUser !== '' ? date('d-M-Y', strtotime($createdUser)) : '' ?>
                                </td>

                            </tr>
                            <?php $i++; ?>
                            <?php endforeach ?>


                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <script>
        $(document).ready(function() {
        })

        // Soporta borrado en filas iniciales y filas clonadas del mini-formulario.
        $(document).on('click', '#weblinks .remove-weblink', function(e) {
            e.preventDefault();
            $(this).closest('.boxInput').remove();
        });

        function removeCompanyContactForm(el) {
            $(el).closest('.boxInput').remove();
        }

        function deleteCompanycontact(sId) {
            swal.fire({
                title: <?= json_encode($cf('contact_delete_confirm_title', 'Delete this contact?'), JSON_UNESCAPED_UNICODE) ?>,
                text: <?= json_encode($cf('contact_delete_confirm_text', 'This action cannot be undone.'), JSON_UNESCAPED_UNICODE) ?>,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#A5DC86',
                cancelButtonColor: '#DD6B55',
                confirmButtonText: <?= json_encode($cf('delete_confirm_yes', 'Yes, delete'), JSON_UNESCAPED_UNICODE) ?>,
                cancelButtonText: <?= json_encode($cf('delete_confirm_no', 'Cancel'), JSON_UNESCAPED_UNICODE) ?>,
                closeOnConfirm: true,
                closeOnCancel: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '<?= admin_url("company/contactdelete/") ?>' + sId
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