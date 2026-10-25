<div class="container-fluid page-body-wrapper pt-3">
    <div class="main-panel">
        <div class="container-fluid">
            <form id="companyprofile" class="forms-sample" action="<?= company_url('profile/save') ?>"
                  method="post" enctype="multipart/form-data">
                <div class="card">
                    <div class="card-header">
                        <div class="row">
                            <h3 class="page-title col"> <?= @$title ?> </h3>
                            <div class="form-group d-flex justify-content-end">
                                <button type="submit" class="btn btn-success mr-2"><?= $lang_perfil['save'] ?></button>
                            </div>
                        </div>

                    </div>
                    <div class="card-body">
                        <div class="row mb-2">
                            <div class="offset-2 col-md-10 text-right">
                                <a href="<?= company_url('profile/logo') ?>"
                                   class="btn btn-success mb-1"><?= $lang_unit['config_logo'] ?></a>
                            </div>
                        </div>
                        <?php
                        $profileCountryPlaceholder = htmlspecialchars(
                            (string) (
                                $lang_perfil['select_country']
                                ?? $lang_customer['placeholder_select_country']
                                ?? $lang_customer['label_select']
                                ?? ''
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        );
                        $profileCountryFlagsUrl = rtrim((string) base_url(), '/') . '/images/flags/';
                        ?>
                        <section class="card" id="general">
                            <div class="card-header">

                                <h4><?= $lang_perfil['general_settings'] ?></h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label><?= $lang_perfil['name'] ?>: <span class="color-red">*</span></label>
                                            <input type="text" class="form-control" name="name" id="name"
                                                   value="<?= htmlspecialchars((string) ($data->name ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label><?= $lang_perfil['short_name'] ?>: <span class="color-red">*</span></label>
                                            <input type="text" class="form-control" name="short_name" id="short_name"
                                                   value="<?= htmlspecialchars((string) ($data->short_name ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label><?= $lang_perfil['tax_id'] ?>: </label>
                                            <input type="text" class="form-control" name="tax_id" id="tax_id"
                                                   value="<?= htmlspecialchars((string) ($data->tax_id ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label><?= $lang_perfil['tax_condition'] ?>: </label>
                                            <input type="text" class="form-control" name="tax_condition" id="tax_condition"
                                                   value="<?= htmlspecialchars((string) ($data->tax_condition ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label><?= $lang_perfil['phone'] ?>: <span class="color-red">*</span></label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text px-2" id="profile_phone_prefix" style="min-width: 5.5rem;"></span>
                                                </div>
                                                <input type="text" class="form-control" name="phone_number" id="profile_phone_number" autocomplete="off"
                                                       value="<?= htmlspecialchars((string) ($data->phone_number ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label><?= $lang_perfil['cell_phone'] ?>: <span class="color-red">*</span></label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text px-2" id="profile_cell_prefix" style="min-width: 5.5rem;"></span>
                                                </div>
                                                <input type="text" class="form-control" name="cell_number" id="profile_cell_number" autocomplete="off"
                                                       value="<?= htmlspecialchars((string) ($data->cell_number ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label><?= $lang_perfil['email'] ?>: <span class="color-red">*</span></label>
                                            <input type="text" class="form-control" name="email" id="email"
                                                   value="<?= htmlspecialchars((string) ($data->email ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                   readonly
                                                   aria-readonly="true"
                                                   title="<?= htmlspecialchars((string) ($lang_perfil['email_readonly_hint'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                   style="background-color:#f1f5f9;cursor:not-allowed;">
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="form-group">
                                            <label><?= $lang_perfil['website'] ?>:</label>
                                            <input type="text" class="form-control" name="website" id="website"
                                                   value="<?= htmlspecialchars((string) ($data->website ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label><?= $lang_perfil['address'] ?>: <span class="color-red">*</span></label>
                                            <input type="text" class="form-control" name="address" id="address"
                                                   value="<?= htmlspecialchars((string) ($data->address ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label><?= $lang_perfil['country'] ?>: <span class="color-red">*</span></label>
                                            <select class="form-control notranslate" name="country" id="profile_country_id" data-flag-field="sortname" required>
                                                <option value=""><?= $profileCountryPlaceholder ?></option>
                                                <?php foreach ($countrylist as $clist) :
                                                    $cid = is_object($clist) ? (int) ($clist->id ?? 0) : (int) ($clist['id'] ?? 0);
                                                    if ($cid <= 0) {
                                                        continue;
                                                    }
                                                    $cname = is_object($clist) ? ($clist->name ?? '') : ($clist['name'] ?? '');
                                                    $sortname = strtolower((string) (is_object($clist) ? ($clist->sortname ?? '') : ($clist['sortname'] ?? '')));
                                                    $phonecode = (int) (is_object($clist) ? ($clist->phonecode ?? 0) : ($clist['phonecode'] ?? 0));
                                                    $phonemask = (string) (is_object($clist) ? ($clist->phonemask ?? '') : ($clist['phonemask'] ?? ''));
                                                    $countryLabel = function_exists('mb_strtoupper')
                                                        ? mb_strtoupper((string) $cname, 'UTF-8')
                                                        : strtoupper((string) $cname);
                                                    $sel = ((string) ($data->country ?? '') === (string) $cid) ? 'selected' : '';
                                                    ?>
                                                    <option value="<?= $cid ?>"
                                                            data-sortname="<?= htmlspecialchars($sortname, ENT_QUOTES, 'UTF-8') ?>"
                                                            data-phonecode="<?= $phonecode ?>"
                                                            data-phonemask="<?= htmlspecialchars($phonemask, ENT_QUOTES, 'UTF-8') ?>" <?= $sel ?>>
                                                        <?= htmlspecialchars($countryLabel, ENT_QUOTES, 'UTF-8') ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label><?= $lang_perfil['state'] ?>: <span class="color-red">*</span></label>
                                            <input type="text" class="form-control" name="state" id="state"
                                                   value="<?= htmlspecialchars((string) ($data->state ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label><?= $lang_perfil['city'] ?>: <span class="color-red">*</span></label>
                                            <input type="text" class="form-control" name="city" id="city"
                                                   value="<?= htmlspecialchars((string) ($data->city ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label><?= $lang_perfil['zipcode'] ?>: <span class="color-red">*</span></label>
                                            <input type="text" class="form-control" name="zip_code" id="zip_code"
                                                   value="<?= htmlspecialchars((string) ($data->zip_code ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label><?= $lang_perfil['language'] ?>: <span class="color-red">*</span></label>
                                            <select class="form-control notranslate" name="language_id" id="language_id">
                                                <?php foreach ($language as $langRow) :
                                                    $lid = (int) ($langRow->language_id ?? 0);
                                                    if ($lid <= 0) {
                                                        continue;
                                                    }
                                                    $lname = (string) ($langRow->name ?? '');
                                                    $lsel = ((int) ($data->language_id ?? 0) === $lid) ? 'selected' : '';
                                                    ?>
                                                    <option value="<?= $lid ?>" <?= $lsel ?>><?= htmlspecialchars($lname, ENT_QUOTES, 'UTF-8') ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label><?= htmlspecialchars((string) ($lang_labels['fax'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>
                                            <input type="text" class="form-control" name="fax" id="fax"
                                                   value="<?= htmlspecialchars((string) ($data->fax ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                        <section class="card" id="limits">
                            <div class="card-header">
                                <h4> <?= $lang_perfil['limits'] ?> </h4>
                            </div>
                            <div class="card-body row">
                                <div class="col-md-6 row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label><?= $lang_perfil['max_customers'] ?>: <span
                                                    class="color-red">*</span></label>
                                            <input type="text" class="form-control" name="max_number_of_clients"
                                                   value="<?= $data->max_number_of_clients ?>"
                                                   >
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label><?= $lang_perfil['max_orders'] ?>: <span
                                                    class="color-red">*</span></label>
                                            <input type="text" class="form-control" name="max_number_of_orders"
                                                   value="<?= $data->max_number_of_orders ?>"
                                                   >
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label><?= $lang_perfil['max_sellers'] ?>: <span
                                                    class="color-red">*</span></label>
                                            <input type="text" class="form-control" name="max_number_of_vendors"
                                                   value="<?= $data->max_number_of_vendors ?>"
                                                   >
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label><?= $lang_perfil['total_price'] ?>: <span
                                                    class="color-red">*</span></label>
                                            <input type="text" class="form-control" name="total_price" value="<?= $data->total_price ?>"
                                                   >
                                        </div>
                                    </div>

                                </div>
                                <div class="col-md-6">
                                    <section class="card">
                                        <div class="card-header">
                                            <h5><?= $lang_perfil['time_days'] ?></h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label><?= $lang_vendor['time_order_sendmail'] ?></label>
                                                        <input type="number" name="ordersend_time"
                                                               value="<?= $data->ordersend_time ?>"
                                                               class="form-control">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label><?= $lang_vendor['time_email_catalog'] ?></label>
                                                        <input type="number" name="catalogsend_time"
                                                               value="<?= $data->catalogsend_time ?>"
                                                               class="form-control">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </section>
                                </div>
                            </div>
                        </section>
                        <link rel="stylesheet" href="<?= base_url('frontend/css/company-profile-formats.css?v=' . date('YmdHis')) ?>">
                        <?php
                        $dateFormatTiles = is_array($date_format_tiles ?? null) ? $date_format_tiles : [];
                        $numericFormatTiles = is_array($numeric_format_tiles ?? null) ? $numeric_format_tiles : [];
                        ?>
                        <section class="card fv-fmt" id="format">
                            <div class="card-body">
                                <div class="fv-fmt-panel">
                                    <h4 class="fv-fmt-panel__title">
                                        <i class="mdi mdi-calendar-clock" aria-hidden="true"></i>
                                        <?= htmlspecialchars((string) ($lang_perfil['format_currency_date'] ?? $lang_perfil['format_currency'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </h4>

                                    <div class="fv-fmt-block">
                                        <p class="fv-fmt-block__label"><?= htmlspecialchars((string) ($lang_perfil['date_format_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                                        <p class="fv-fmt-block__hint"><?= htmlspecialchars((string) ($lang_perfil['date_format_hint'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                                        <div class="fv-fmt-grid" role="radiogroup" aria-label="<?= htmlspecialchars((string) ($lang_perfil['date_format_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                            <?php if (empty($dateFormatTiles)) : ?>
                                                <p class="fv-fmt-empty text-muted mb-0">
                                                    <?= htmlspecialchars((string) ($lang_perfil['date_format_empty'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                                </p>
                                            <?php endif; ?>
                                            <?php foreach ($dateFormatTiles as $tile): ?>
                                                <?php
                                                $tid = (int) ($tile['id'] ?? 0);
                                                $isSelected = ! empty($tile['selected']);
                                                $inputId = 'fv-fmt-date-' . $tid;
                                                ?>
                                                <label class="fv-fmt-tile<?= $isSelected ? ' is-selected' : '' ?>" for="<?= htmlspecialchars($inputId, ENT_QUOTES, 'UTF-8') ?>">
                                                    <input class="fv-fmt-tile__input" type="radio" name="format_date"
                                                           id="<?= htmlspecialchars($inputId, ENT_QUOTES, 'UTF-8') ?>"
                                                           value="<?= $tid ?>"
                                                           <?= $isSelected ? 'checked' : '' ?>>
                                                    <span class="fv-fmt-tile__check" aria-hidden="true"><i class="fa fa-check"></i></span>
                                                    <span class="fv-fmt-tile__sample"><?= htmlspecialchars((string) ($tile['sample'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                                    <span class="fv-fmt-tile__pattern"><?= htmlspecialchars((string) ($tile['pattern'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <div class="fv-fmt-block">
                                        <p class="fv-fmt-block__label"><?= htmlspecialchars((string) ($lang_perfil['number_format_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                                        <p class="fv-fmt-block__hint"><?= htmlspecialchars((string) ($lang_perfil['number_format_hint'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                                        <div class="fv-fmt-grid" role="radiogroup" aria-label="<?= htmlspecialchars((string) ($lang_perfil['number_format_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                            <?php foreach ($numericFormatTiles as $tile): ?>
                                                <?php
                                                $tid = (int) ($tile['id'] ?? 0);
                                                $isSelected = ! empty($tile['selected']);
                                                $inputId = 'fv-fmt-num-' . $tid;
                                                ?>
                                                <label class="fv-fmt-tile<?= $isSelected ? ' is-selected' : '' ?>" for="<?= htmlspecialchars($inputId, ENT_QUOTES, 'UTF-8') ?>">
                                                    <input class="fv-fmt-tile__input" type="radio" name="format"
                                                           id="<?= htmlspecialchars($inputId, ENT_QUOTES, 'UTF-8') ?>"
                                                           value="<?= $tid ?>"
                                                           <?= $isSelected ? 'checked' : '' ?>>
                                                    <span class="fv-fmt-tile__check" aria-hidden="true"><i class="fa fa-check"></i></span>
                                                    <span class="fv-fmt-tile__sample"><?= htmlspecialchars((string) ($tile['sample'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                                    <span class="fv-fmt-tile__pattern"><?= htmlspecialchars((string) ($tile['pattern'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <div class="fv-fmt-currency-row">
                                        <div class="form-group mb-0">
                                            <label><?= htmlspecialchars((string) ($lang_perfil['default_money'] ?? ''), ENT_QUOTES, 'UTF-8') ?>: <span class="color-red">*</span></label>
                                            <select class="form-control js-example-matcher-start" name="currency" disabled>
                                                <?php foreach ($currencies_pather as $pather) : ?>
                                                    <optgroup label="<?= htmlspecialchars((string) $pather->GroupID, ENT_QUOTES, 'UTF-8') ?>">
                                                        <?php foreach ($pather->currencies as $children) : ?>
                                                            <option
                                                                <?= ($data->currency && $data->currency == $children->id) ? 'selected' : $children->default ?>
                                                                value="<?= (int) $children->id ?>">
                                                                <?= htmlspecialchars((string) ($children->Symbol . $children->Code . '-' . $children->Name . '(' . $children->Contry . ')'), ENT_QUOTES, 'UTF-8') ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </optgroup>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                        <section class="card" id="salesman">
                            <div class="card-header">
                                <h4><?=$lang_order['seller'] ?></h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="email_client_on_salesman_order"><?=$lang_vendor['email_client_on_salesman_order']?></label>
                                            <div class="btn-group w-100">
                                                <input id="email_client_on_salesman_order_yes" hidden type="radio" name="email_client_on_salesman_order"
                                                       class="btn-check" value="1"
                                                    <?= ($data->email_client_on_salesman_order == 1) ? 'checked' : '' ?>>
                                                <label for="email_client_on_salesman_order_yes"
                                                       class="especial_label border w-50 text-center"><?= $lang_vendor['si'] ?></label>
                                                <input id="email_client_on_salesman_order_no" hidden type="radio" name="email_client_on_salesman_order"
                                                       class="btn-check" value="0"
                                                    <?= ($data->email_client_on_salesman_order == 0) ? 'checked' : '' ?>>
                                                <label for="email_client_on_salesman_order_no"
                                                       class="especial_label border w-50 text-center">No</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="email_on_salesman_order"><?=$lang_vendor['email_on_salesman_order']?></label>
                                            <div class="btn-group w-100">
                                                <input id="email_on_salesman_order_yes" hidden type="radio" name="email_on_salesman_order"
                                                       class="btn-check" value="1"
                                                    <?= ($data->email_on_salesman_order == 1) ? 'checked' : '' ?>>
                                                <label for="email_on_salesman_order_yes"
                                                       class="especial_label border w-50 text-center"><?= $lang_vendor['si'] ?></label>
                                                <input id="email_on_salesman_order_no" hidden type="radio" name="email_on_salesman_order"
                                                       class="btn-check" value="0"
                                                    <?= ($data->email_on_salesman_order == 0) ? 'checked' : '' ?>>
                                                <label for="email_on_salesman_order_no"
                                                       class="especial_label border w-50 text-center">No</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="card" id="inventory">
                            <div class="card-header">
                                <h4>Inventory</h4>
                            </div>
                            <div class="card-body">
                                <div class="row">

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><?= $lang_vendor['sell_with_inventory'] ?></label>
                                    <br>

                                            <div class="btn-group w-100">
                                                <input id="entero" hidden type="radio" name="sell_with_inventory"
                                                       class="btn-check" value="1"
                                                    <?= ($data->sell_with_inventory == 1) ? 'checked' : '' ?>>
                                                <label for="entero"
                                                       class="especial_label border w-50 text-center"><?= $lang_vendor['si'] ?></label>
                                                <input id="decimal" hidden type="radio" name="sell_with_inventory"
                                                       class="btn-check" value="0"
                                                    <?= ($data->sell_with_inventory == 0) ? 'checked' : '' ?>>
                                                <label for="decimal"
                                                       class="especial_label border w-50 text-center">No</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label><?= $lang_vendor['show_inventory'] ?></label>
                                            <br>
                                            <div class="btn-group w-100">
                                                <input id="show_inventory_si" hidden type="radio"
                                                       name="show_inventory"
                                                       class="btn-check" value="1"
                                                    <?= ($data->show_inventory == 1) ? 'checked' : '' ?>>
                                                <label for="show_inventory_si"
                                                       class="especial_label border w-50 text-center"><?= $lang_vendor['si'] ?></label>
                                                <input id="show_inventory_no" hidden type="radio"
                                                       name="show_inventory"
                                                       class="btn-check" value="0"
                                                    <?= ($data->show_inventory == 0) ? 'checked' : '' ?>>
                                                <label for="show_inventory_no"
                                                       class="especial_label border w-50 text-center">No</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label><?= $lang_vendor['show_semaforo_stock'] ?></label>
                                            <br>
                                            <div class="btn-group w-100">
                                                <input id="show_semaforo_stock_si" hidden type="radio"
                                                       name="show_semaforo_stock"
                                                       class="btn-check" value="1"
                                                    <?= ($data->show_semaforo_stock == 1) ? 'checked' : '' ?>>
                                                <label for="show_semaforo_stock_si"
                                                       class="especial_label border w-50 text-center"><?= $lang_vendor['si'] ?></label>
                                                <input id="show_semaforo_stock_no" hidden type="radio"
                                                       name="show_semaforo_stock"
                                                       class="btn-check" value="0"
                                                    <?= ($data->show_semaforo_stock == 0) ? 'checked' : '' ?>>
                                                <label for="show_semaforo_stock_no"
                                                       class="especial_label border w-50 text-center">No</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Semaforo -->
                                <?php
                                $this->load->view('company/product/semaforo_stock/semaforo_edit', [
                                    'show_leyend' => true,
                                    'product' => $data,
                                    'pageTexts' => is_array($semaforo_page_texts ?? null) ? $semaforo_page_texts : [],
                                ]);
                                ?>
                            </div>
                        </section>
                        <section class="card" id="contact">
                            <div class="card-header">
                                <h4><?= $lang_perfil['company_contacts'] ?></h4>
                            </div>
                            <div class="card-body">
                                <div class="col-12">
                                    <div class="listing-box">
                                        <div class="webfield-clone">
                                            <?php
                                            foreach ($contactlist as $conlist) { ?>
                                                <div class="boxInput">
                                                    <div class="boxform">
                                                        <div class="row">
                                                            <div class="col-lg-3 form-group mb-2">
                                                                <label><?= $lang_perfil['name'] ?></label>
                                                                <input type="text" value="<?= $conlist->name ?>"
                                                                       name="cname[]"
                                                                       class="form-control">
                                                            </div>
                                                            <div class="col-lg-3 form-group mb-2">
                                                                <label><?= $lang_perfil['email'] ?></label>
                                                                <input type="email" value="<?= $conlist->email ?>"
                                                                       class="form-control" name="cemail[]">
                                                            </div>
                                                            <div class="col-lg-2 form-group mb-2">
                                                                <label><?= $lang_perfil['website'] ?></label>
                                                                <input type="text" value="<?= $conlist->website ?>"
                                                                       class="form-control" name="cwebsite[]">
                                                            </div>
                                                            <div class="col-lg-2 form-group mb-2">
                                                                <label><?= $lang_perfil['phone'] ?></label>
                                                                <input type="text" value="<?= $conlist->phone ?>"
                                                                       class="form-control" name="cphone[]">
                                                            </div>
                                                            <div class="col-lg-2 form-group mb-2">
                                                                <label><?= $lang_perfil['cell_phone'] ?></label>
                                                                <input type="text" value="<?= $conlist->cell_phone ?>"
                                                                       class="form-control" name="ccphone[]">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <a href="javascript:void(0)"
                                                       onclick="deleteCompanycontact(<?= @$conlist->c_id ?>)"
                                                       class="remove-editweblink">
                                                        <span class="mdi mdi-delete"></span>
                                                    </a>
                                                </div>
                                                <?php
                                            } ?>
                                        </div>
                                    </div>
                                    <div class="listing-box" id="weblinks">
                                        <div class="webfield-clone">
                                            <div class="boxInput">
                                                <div class="boxform">
                                                    <div class="row">
                                                        <div class="col-lg-3 form-group mb-2">
                                                            <label><?= $lang_perfil['name'] ?></label>
                                                            <input type="text" value="" name="cname[]"
                                                                   class="form-control">
                                                        </div>
                                                        <div class="col-lg-3 form-group mb-2">
                                                            <label><?= $lang_perfil['email'] ?></label>
                                                            <input type="email" value="" class="form-control"
                                                                   name="cemail[]">
                                                        </div>
                                                        <div class="col-lg-2 form-group mb-2">
                                                            <label><?= $lang_perfil['website'] ?></label>
                                                            <input type="text" value="" class="form-control"
                                                                   name="cwebsite[]">
                                                        </div>
                                                        <div class="col-lg-2 form-group mb-2">
                                                            <label><?= $lang_perfil['phone'] ?></label>
                                                            <input type="text" value="" class="form-control"
                                                                   name="cphone[]">
                                                        </div>
                                                        <div class="col-lg-2 form-group mb-2">
                                                            <label><?= $lang_perfil['cell_phone'] ?></label>
                                                            <input type="text" value="" class="form-control"
                                                                   name="ccphone[]">
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
                                            <i class="mdi mdi-plus"></i> <span><?= $lang_perfil['add_more'] ?></span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </section>
                        <section class="card" id="ecommerce">
                            <div class="card-header">
                                <h4><?= $lang_perfil['ecommerce'] ?></h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label><?= $lang_perfil['publish_price_list'] ?></label>
                                            <br>

                                            <div class="btn-group w-100">
                                                <input id="publish" hidden type="radio" name="publish_price_list"
                                                       class="btn-check" value="1"
                                                    <?= ($data->publish_price_list == 1) ? 'checked' : '' ?>>
                                                <label for="publish"
                                                       class="especial_label border w-50 text-center"><?= $lang_vendor['si'] ?></label>
                                                <input id="nopublish" hidden type="radio" name="publish_price_list"
                                                       class="btn-check" value="0"
                                                    <?= ($data->publish_price_list == 0) ? 'checked' : '' ?>>
                                                <label for="nopublish"
                                                       class="especial_label border w-50 text-center">No</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label><?= $lang_perfil['show_plus_taxes_label'] ?></label>
                                            <br>

                                            <div class="btn-group w-100">
                                                <input id="show" hidden type="radio" name="show_plus_taxes_label"
                                                       class="btn-check" value="1"
                                                    <?= ($data->show_plus_taxes_label == 1) ? 'checked' : '' ?>>
                                                <label for="show"
                                                       class="especial_label border w-50 text-center"><?= $lang_vendor['si'] ?></label>
                                                <input id="noshow" hidden type="radio" name="show_plus_taxes_label"
                                                       class="btn-check" value="0"
                                                    <?= ($data->show_plus_taxes_label == 0) ? 'checked' : '' ?>>
                                                <label for="noshow"
                                                       class="especial_label border w-50 text-center">No</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label><?= $lang_perfil['plus_taxes_label'] ?></label>
                                            <br>
                                            <div class="btn-group w-100">
                                                <input type="text" name="plus_taxes_label" class="form-control" value="<?= $data->plus_taxes_label ?>" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                    <div class="card-footer">
                        <div class="form-group d-flex justify-content-end">
                            <a class="btn btn-danger mr-2" href="<?= company_url('dashboard') ?>">
                                <?= $lang_perfil['back'] ?>
                            </a>
                            <button type="submit" class="btn btn-success mr-2"><?= $lang_perfil['save'] ?></button>
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var selPlaceholder = <?= json_encode((string) ($lang_customer['label_select'] ?? $lang_perfil['select_country'] ?? '')) ?>;
    var phCountry = <?= json_encode((string) ($lang_perfil['select_country'] ?? $lang_customer['placeholder_select_country'] ?? $lang_customer['label_select'] ?? '')) ?>;
    var countryFlagsBaseUrl = <?= json_encode($profileCountryFlagsUrl) ?>;

    function countryFlagHtml(sortname) {
        var sn = String(sortname || '').toLowerCase().replace(/[^a-z-]/g, '');
        if (sn.length < 2) {
            return '';
        }
        var src = countryFlagsBaseUrl + sn + '.png';
        return '<img src="' + src.replace(/&/g, '&amp;').replace(/"/g, '&quot;') + '" alt="" style="display:inline-block;width:20px;height:15px;object-fit:cover;vertical-align:middle;border:1px solid #ddd;border-radius:2px;margin-right:8px" onerror="this.style.display=\'none\'" />';
    }

    function updateProfilePhonePrefix() {
        var cid = $('#profile_country_id').val();
        var $opt = cid ? $('#profile_country_id option[value="' + cid + '"]') : $();
        if (!$opt.length) {
            $('#profile_phone_prefix, #profile_cell_prefix').html('<span class="text-muted small">—</span>');
            return;
        }
        var sortname = String($opt.data('sortname') || '').toLowerCase();
        var pc = parseInt($opt.data('phonecode'), 10);
        if (isNaN(pc)) {
            pc = 0;
        }
        var flag = countryFlagHtml(sortname);
        var html = flag + '<span class="ml-1 font-weight-bold">+' + pc + '</span>';
        $('#profile_phone_prefix, #profile_cell_prefix').html(html);
    }

    function updateProfilePhoneInputMasks() {
        if (typeof Inputmask === 'undefined') {
            return false;
        }
        var cid = $('#profile_country_id').val();
        var $opt = cid ? $('#profile_country_id option[value="' + cid + '"]') : $();
        var maskPh = '';
        if ($opt.length) {
            maskPh = ($opt.attr('data-phonemask') || '').trim();
            if (!maskPh) {
                var pcMask = parseInt($opt.attr('data-phonecode'), 10);
                if (!isNaN(pcMask) && pcMask === 1) {
                    maskPh = '(###) ###-####';
                }
            }
        }
        var phoneEl = document.getElementById('profile_phone_number');
        var cellEl = document.getElementById('profile_cell_number');
        if (!phoneEl || !cellEl) {
            return true;
        }

        var rawPhone = (phoneEl.value || '').replace(/\D/g, '');
        var rawCell = (cellEl.value || '').replace(/\D/g, '');

        try { Inputmask.remove(phoneEl); } catch (e) {}
        try { Inputmask.remove(cellEl); } catch (e) {}

        if (!maskPh) {
            phoneEl.value = rawPhone;
            cellEl.value = rawCell;
            return true;
        }

        var imMask = maskPh.replace(/#/g, '9');
        var opts = {
            mask: imMask,
            placeholder: '_',
            showMaskOnHover: false,
            autoUnmask: true,
            clearIncomplete: false
        };
        new Inputmask(opts).mask(phoneEl);
        new Inputmask(opts).mask(cellEl);

        if (phoneEl.inputmask && typeof phoneEl.inputmask.setValue === 'function') {
            phoneEl.inputmask.setValue(rawPhone);
        } else {
            $(phoneEl).val(rawPhone).trigger('input');
        }
        if (cellEl.inputmask && typeof cellEl.inputmask.setValue === 'function') {
            cellEl.inputmask.setValue(rawCell);
        } else {
            $(cellEl).val(rawCell).trigger('input');
        }
        return true;
    }

    function initProfileCountrySelect() {
        var $el = $('#profile_country_id');
        if (!$el.length || typeof $.fn.select2 === 'undefined') {
            return false;
        }
        try { $el.select2('destroy'); } catch (e) {}
        $el.select2({
            placeholder: phCountry || selPlaceholder,
            allowClear: true,
            width: '100%',
            minimumResultsForSearch: 0,
            dropdownParent: $(document.body),
            matcher: function(params, data) {
                if ($.trim(params.term || '') === '') {
                    return data;
                }
                if (typeof data.text === 'undefined' || data.children) {
                    return null;
                }
                var term = params.term.toLowerCase();
                var raw = (typeof data.text === 'string') ? data.text : '';
                var txt = $('<div/>').html(raw).text().toLowerCase();
                var el = data.element;
                var sortname = el ? String($(el).data('sortname') || '').toLowerCase() : '';
                if (txt.indexOf(term) > -1 || sortname.indexOf(term) > -1) {
                    return data;
                }
                return null;
            },
            templateResult: function(country) {
                if (!country.id) {
                    return country.text;
                }
                var $opt = $(country.element);
                var sortname = $opt.data('sortname');
                var labelUp = $('<span/>').text(($.trim($opt.text()) || '').toUpperCase()).html();
                var fh = countryFlagHtml(sortname);
                if (fh) {
                    return $('<span class="d-inline-flex align-items-center"/>').html(fh + labelUp);
                }
                return $('<span/>').html(labelUp);
            },
            templateSelection: function(country) {
                if (!country.id) {
                    return country.text;
                }
                var $opt = $(country.element);
                var sortname = $opt.data('sortname');
                var labelUp = $('<span/>').text(($.trim($opt.text()) || '').toUpperCase()).html();
                var fh = countryFlagHtml(sortname);
                if (fh) {
                    return $('<span class="d-inline-flex align-items-center"/>').html(fh + labelUp);
                }
                return $('<span/>').html(labelUp);
            },
            escapeMarkup: function(m) { return m; }
        });
        return true;
    }

    function runProfilePhoneAfterCountry() {
        updateProfilePhonePrefix();
        if (!updateProfilePhoneInputMasks()) {
            var masAttempts = 0, masMax = 60;
            var imv = setInterval(function() {
                if (updateProfilePhoneInputMasks() || ++masAttempts >= masMax) {
                    clearInterval(imv);
                }
            }, 100);
        }
    }

    if (!initProfileCountrySelect()) {
        var ctryAttempts = 0;
        var ctryIv = setInterval(function() {
            if (initProfileCountrySelect() || ++ctryAttempts >= 60) {
                clearInterval(ctryIv);
                runProfilePhoneAfterCountry();
            }
        }, 100);
    } else {
        runProfilePhoneAfterCountry();
    }

    $(document).on('select2:open', '#profile_country_id', function() {
        setTimeout(function() {
            var $f = $('.select2-container--open .select2-search__field');
            if ($f.length) {
                $f[0].focus();
            }
        }, 0);
    });

    $('#profile_country_id').on('change', function() {
        updateProfilePhonePrefix();
        updateProfilePhoneInputMasks();
    });

    var $languageSelect = $('#language_id');
    if (typeof $.fn.select2 !== 'undefined') {
        try { $languageSelect.select2('destroy'); } catch (e) {}
        $languageSelect.select2({
            width: '100%',
            minimumResultsForSearch: Infinity
        });
    }
});
</script>
