<?php
$logoLang = isset($logoLang) && is_array($logoLang) ? $logoLang : [];
$t = static function (string $key, string $fallback = '') use ($logoLang): string {
    $v = trim((string) ($logoLang[$key] ?? ''));

    return $v !== '' ? $v : $fallback;
};
$hasImage = ! empty($data->image) && (company_logo_is_api_image((string) $data->image) || file_exists('./uploads/company/' . @$data->image));
$hasSmallImage = ! empty($data->small_image) && (company_logo_is_api_image((string) $data->small_image) || file_exists('./uploads/company/' . @$data->small_image));
$confirmDelete = htmlspecialchars($t('company_logo_delete_confirm', 'Remove this image?'), ENT_QUOTES, 'UTF-8');
?>
<div class="container-fluid page-body-wrapper pt-3">
  <div class="main-panel">
    <div class="container-fluid">
      <div class="page-header">
        <h3 class="page-title"><?= htmlspecialchars((string) ($title ?? ''), ENT_QUOTES, 'UTF-8') ?></h3>
      </div>
      <div class="card">
        <div class="card-body importImg">
          <div class="uploadForm">
              <form class="forms-sample" action="<?= company_url('profile/logosave') ?>" method="post" enctype="multipart/form-data">
                  <div class="row">
                      <div class="col-md-12">
                          <div class="form-group">
                              <label><?= htmlspecialchars($t('label_image', 'Image'), ENT_QUOTES, 'UTF-8') ?>: <span class="color-red">*</span></label><br/>
                              <div class="fileinput fileinput-new" data-provides="fileinput">
                                  <div class="fileinput-new thumbnail" style="width: 250px; height:150px;">
                                      <?php if ($hasImage) { ?>
                                          <img src="<?= htmlspecialchars(company_logo_url((string) $data->image), ENT_QUOTES, 'UTF-8') ?>" alt=""
                                               onerror="this.onerror=null;this.src='<?= htmlspecialchars(base_url('uploads/noimg.png'), ENT_QUOTES, 'UTF-8') ?>';">
                                      <?php } else { ?>
                                          <img src="<?= base_url('uploads/noimg.png') ?>" alt="">
                                      <?php } ?>
                                  </div>
                                  <div class="fileinput-preview fileinput-exists thumbnail" style="max-width: 250px; max-height: 150px;"></div>
                                  <div>
                                      <span class="btn btn-default btn-file">
                                        <span class="fileinput-new"><?= htmlspecialchars($t('btn_select_image', 'Select image'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="fileinput-exists"><?= htmlspecialchars($t('btn_change_image', 'Change'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <input type="file" name="logo" accept="image/*">
                                        <input type="hidden" name="oldLogo" value="<?= htmlspecialchars((string) ($data->image ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                      </span>
                                      <a href="javascript:void(0)" class="btn btn-danger fileinput-exists" data-dismiss="fileinput"><?= htmlspecialchars($t('btn_remove_image', 'Remove'), ENT_QUOTES, 'UTF-8') ?></a>
                                  </div>
                              </div>

                              <div class="clearfix margin-top-10 m-b-20 f12" style="display: block;">
                                  <span class="label label-danger"><?= htmlspecialchars($t('label_image_format', 'Format'), ENT_QUOTES, 'UTF-8') ?></span>&nbsp;<?= htmlspecialchars($t('label_image_formats', 'jpg, jpeg, png, gif'), ENT_QUOTES, 'UTF-8') ?>
                                  <span class="label label-danger"><?= htmlspecialchars($t('label_image_max_size', 'Max upload size'), ENT_QUOTES, 'UTF-8') ?></span>&nbsp;<?= htmlspecialchars($t('label_image_max_size_value', '10 MB'), ENT_QUOTES, 'UTF-8') ?>
                              </div>
                          </div>

                      </div>
                      <div class="col-md-6 offset-md-2">
                          <div class="form-group">
                              <button type="submit" class="btn btn-success mr-2"><?= htmlspecialchars($t('btn_upload', 'Upload'), ENT_QUOTES, 'UTF-8') ?></button>
                              <?php if ($hasImage) { ?>
                              <button type="submit"
                                      class="btn btn-danger mr-2"
                                      form="fv-logo-delete-image"
                                      onclick="return confirm('<?= $confirmDelete ?>');">
                                  <?= htmlspecialchars($t('btn_remove_image', 'Remove'), ENT_QUOTES, 'UTF-8') ?>
                              </button>
                              <?php } ?>
                              <a class="btn btn-danger mr-2" href="<?= company_url('profile') ?>">
                                  <?= htmlspecialchars($t('back', 'Back'), ENT_QUOTES, 'UTF-8') ?>
                              </a>
                          </div>
                      </div>
                  </div>
                  <input type="hidden" name="imgType" value="image" />
              </form>
              <?php if ($hasImage) { ?>
              <form id="fv-logo-delete-image" action="<?= company_url('profile/logodelete') ?>" method="post" class="d-none">
                  <input type="hidden" name="imgType" value="image" />
              </form>
              <?php } ?>
          </div>
            <div class="uploadForm">
                <form class="forms-sample" action="<?= company_url('profile/logosave') ?>" method="post" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label><?= htmlspecialchars($t('label_small_image', 'Small image'), ENT_QUOTES, 'UTF-8') ?>: <span class="color-red">*</span></label><br/>
                                <div class="fileinput fileinput-new" data-provides="fileinput">
                                    <div class="fileinput-new thumbnail" style="width: 250px; height:150px;">
                                        <?php if ($hasSmallImage) { ?>
                                            <img src="<?= htmlspecialchars(company_logo_url((string) $data->small_image), ENT_QUOTES, 'UTF-8') ?>" alt=""
                                                 onerror="this.onerror=null;this.src='<?= htmlspecialchars(base_url('uploads/noimg.png'), ENT_QUOTES, 'UTF-8') ?>';">
                                        <?php } else { ?>
                                            <img src="<?= base_url('uploads/noimg.png') ?>" alt="">
                                        <?php } ?>
                                    </div>
                                    <div class="fileinput-preview fileinput-exists thumbnail" style="max-width: 250px; max-height: 150px;"></div>
                                    <div>
                                      <span class="btn btn-default btn-file">
                                        <span class="fileinput-new"><?= htmlspecialchars($t('btn_select_image', 'Select image'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="fileinput-exists"><?= htmlspecialchars($t('btn_change_image', 'Change'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <input type="file" name="logo" accept="image/*">
                                        <input type="hidden" name="oldLogo" value="<?= htmlspecialchars((string) ($data->small_image ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                      </span>
                                        <a href="javascript:void(0)" class="btn btn-danger fileinput-exists" data-dismiss="fileinput"><?= htmlspecialchars($t('btn_remove_image', 'Remove'), ENT_QUOTES, 'UTF-8') ?></a>
                                    </div>
                                </div>

                                <div class="clearfix margin-top-10 m-b-20 f12" style="display: block;">
                                    <span class="label label-danger"><?= htmlspecialchars($t('label_image_format', 'Format'), ENT_QUOTES, 'UTF-8') ?></span>&nbsp;<?= htmlspecialchars($t('label_image_formats', 'jpg, jpeg, png, gif'), ENT_QUOTES, 'UTF-8') ?>
                                    <span class="label label-danger"><?= htmlspecialchars($t('label_image_max_size', 'Max upload size'), ENT_QUOTES, 'UTF-8') ?></span>&nbsp;<?= htmlspecialchars($t('label_image_max_size_value', '10 MB'), ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <div style="display: block;">
                                    <?= htmlspecialchars($t('hint_small_image_ratio', 'The image must have a ratio of 1:1 or 5:4'), ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </div>

                        </div>
                        <div class="col-md-6 offset-md-2">
                            <div class="form-group">
                                <button type="submit" class="btn btn-success mr-2"><?= htmlspecialchars($t('btn_upload', 'Upload'), ENT_QUOTES, 'UTF-8') ?></button>
                                <?php if ($hasSmallImage) { ?>
                                <button type="submit"
                                        class="btn btn-danger mr-2"
                                        form="fv-logo-delete-small-image"
                                        onclick="return confirm('<?= $confirmDelete ?>');">
                                    <?= htmlspecialchars($t('btn_remove_image', 'Remove'), ENT_QUOTES, 'UTF-8') ?>
                                </button>
                                <?php } ?>
                                <a class="btn btn-danger mr-2" href="<?= company_url('profile') ?>">
                                    <?= htmlspecialchars($t('back', 'Back'), ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="imgType" value="small_image" />
                </form>
                <?php if ($hasSmallImage) { ?>
                <form id="fv-logo-delete-small-image" action="<?= company_url('profile/logodelete') ?>" method="post" class="d-none">
                    <input type="hidden" name="imgType" value="small_image" />
                </form>
                <?php } ?>
            </div>
        </div>
      </div>
    </div>
  </div>
</div>
