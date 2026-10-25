<?php
$cfLang = isset($cfLang) && is_array($cfLang) ? $cfLang : [];
$cf = static function (string $key, string $fallback = '') use ($cfLang): string {
	$v = trim((string) ($cfLang[$key] ?? ''));

	return $v !== '' ? $v : $fallback;
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
            <div class="form-group row">
              <div class="col">
                <label><?= htmlspecialchars($cf('label_company_name', 'Company name'), ENT_QUOTES, 'UTF-8') ?>:</label>
                <input type="text" class="form-control" name="name" id="name" value="<?= $data->name ?>" readonly placeholder="<?= htmlspecialchars($cf('placeholder_company_name', 'Company name'), ENT_QUOTES, 'UTF-8') ?>">
              
              </div>
                 <div class="col">
                <label><?= htmlspecialchars($cf('label_short_name', 'Short name'), ENT_QUOTES, 'UTF-8') ?>:</label>
                <input type="text" class="form-control" name="short_name" id="short_name" autocomplete="off" value="<?= $data->short_name ?>" readonly placeholder="<?= htmlspecialchars($cf('placeholder_short_name', 'Short name'), ENT_QUOTES, 'UTF-8') ?>">
              </div>             
            </div>
           
            <div class="form-group row">
              <div class="col">
                <label><?= htmlspecialchars($cf('label_phone_number', 'Phone number'), ENT_QUOTES, 'UTF-8') ?>:</label>
                <input type="text" class="form-control" name="phone_number" id="phone_number" autocomplete="off" value="<?= $data->phone_number ?>" readonly placeholder="<?= htmlspecialchars($cf('label_phone_number', 'Phone number'), ENT_QUOTES, 'UTF-8') ?>">
              </div>
                 <div class="col">
                <label><?= htmlspecialchars($cf('label_cell_number', 'Cell number'), ENT_QUOTES, 'UTF-8') ?>:</label>
                <input type="text" class="form-control" name="cell_number" id="cell_number" autocomplete="off" value="<?= $data->cell_number ?>"  readonly placeholder="<?= htmlspecialchars($cf('label_cell_number', 'Cell number'), ENT_QUOTES, 'UTF-8') ?>">
              </div>             
            </div>
            <div class="form-group row">
              <div class="col">
                <label><?= htmlspecialchars($cf('label_website', 'Website'), ENT_QUOTES, 'UTF-8') ?>:</label>
                <input type="text" class="form-control" name="website" id="website" autocomplete="off" readonly value="<?= $data->website ?>" >
              </div>
              <div class="col">
                <label><?= htmlspecialchars($cf('label_email', 'Email'), ENT_QUOTES, 'UTF-8') ?>:</label>
                <input type="text" class="form-control" name="email" id="email" autocomplete="off" value="<?= $data->email ?>"  readonly placeholder="<?= htmlspecialchars($cf('placeholder_email', 'Enter email'), ENT_QUOTES, 'UTF-8') ?>">
              </div>
                           
            </div>
            <div class="form-group">
              <label><?= htmlspecialchars($cf('label_image', 'Image'), ENT_QUOTES, 'UTF-8') ?>:</label><br/>
              <div class="fileinput fileinput-new" data-provides="fileinput">
                <div class="fileinput-new thumbnail" style="width: 250px; height:150px;">
                  <?php
                  $__cvCid = (int) ($data->company_id ?? 0);
                  $__cvImg = isset($data->image) ? (string) $data->image : '';
                  $__cvLogoSrc = ($__cvCid > 0 && $__cvImg !== '')
                    ? admin_url('company/serve_company_logo/' . $__cvCid) . '?v=' . rawurlencode($__cvImg)
                    : '';
                  ?>
                  <?php if ($__cvLogoSrc !== '') { ?>
                    <img src="<?= htmlspecialchars($__cvLogoSrc, ENT_QUOTES, 'UTF-8') ?>" alt="">
                  <?php } else { ?>
                    <img src="<?= base_url('uploads/noimg.png') ?>" alt="">
                  <?php } ?>
                </div>
              </div>                   
              
            </div>
            <div class="form-group">
             <a class="btn btn-danger mr-2" href="<?= admin_url('company/lists') ?>">
                    <?= htmlspecialchars($cf('btn_back', 'Back'), ENT_QUOTES, 'UTF-8') ?>
                  </a>
            </div>
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
                      <?php if (!empty($v->image) && (company_logo_is_api_image((string) $v->image) || file_exists('./uploads/company/'.@$v->image))) { ?>
                        <img src="<?= company_logo_url((string) $v->image) ?>" alt="">
                      <?php } else { ?>
                        <img src="<?= base_url('uploads/noimg.png') ?>" alt="">
                      <?php } ?>
                    </td>
                    <td><?= @$v->name ?></td>
                    <td><?= @$v->email ?></td>
                    <td><?= htmlspecialchars((string) ($v->phone ?? ($v->phone_number ?? ($v->cellphone ?? ''))), ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                      <?php if (@$v->status == '1') { ?>
                        <label class="badge badge-success"><?= htmlspecialchars($cf('status_option_enable', 'Enable'), ENT_QUOTES, 'UTF-8') ?></label>
                      <?php } else { ?>
                        <label class="badge badge-danger"><?= htmlspecialchars($cf('status_option_disable', 'Disable'), ENT_QUOTES, 'UTF-8') ?></label>
                      <?php } ?>
                    </td>
                    <td>
                      <?php
                      $createdUser = (string) ($v->created_at ?? ($v->created ?? ''));
                      echo $createdUser !== '' ? date('d-M-Y', strtotime($createdUser)) : '';
                      ?>
                    </td>
                   
                  </tr>
                  <?php $i++; ?>
                <?php endforeach ?>

              
            </tbody>
          </table>
        </div>
      </div>
    </div>
