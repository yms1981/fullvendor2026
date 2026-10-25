<?php
$pt = is_array($pageTexts ?? null) ? $pageTexts : [];
$h = static function (string $key, string $fallback = '') use ($pt): string {
	$v = trim((string) ($pt[$key] ?? ''));

	return $v !== '' ? $v : $fallback;
};
?>
<div class="content-fluid container-fluid pt-3">
  <div class="page-header">
    <h3 class="page-title"><?= htmlspecialchars((string) (@$title ?? ''), ENT_QUOTES, 'UTF-8') ?></h3>
  </div>
  <div class="card">
    <div class="card-body">
      <form class="forms-sample" action="<?= admin_url('settings/save') ?>" method="post" enctype="multipart/form-data">
        <div class="row">
          <div class="col-md-12">
            <div class="form-group">
              <label><?= htmlspecialchars($h('label_address', 'Address'), ENT_QUOTES, 'UTF-8') ?></label>
               <input type="text" class="form-control" name="address" id="address" value="<?= htmlspecialchars((string) ($data->address ?? ''), ENT_QUOTES, 'UTF-8') ?>" autocomplete="off" required="">
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label><?= htmlspecialchars($h('label_email', 'Email'), ENT_QUOTES, 'UTF-8') ?></label>
              <input type="email" class="form-control" name="email" id="email" value="<?= htmlspecialchars((string) ($data->email ?? ''), ENT_QUOTES, 'UTF-8') ?>" autocomplete="off" required="">
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label><?= htmlspecialchars($h('label_phone', 'Phone'), ENT_QUOTES, 'UTF-8') ?></label>
              <input type="text" class="form-control" name="phone" id="phone" value="<?= htmlspecialchars((string) ($data->phone ?? ''), ENT_QUOTES, 'UTF-8') ?>" autocomplete="off" required="">
            </div>
          </div>
        </div>
        <hr>
        <div class="form-group">
          <h3 class="page-title"><?= htmlspecialchars($h('section_social_media', 'Social media settings'), ENT_QUOTES, 'UTF-8') ?></h3>
        </div>
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label><?= htmlspecialchars($h('label_facebook', 'Facebook'), ENT_QUOTES, 'UTF-8') ?></label>
              <input type="text" class="form-control" name="facebook" id="facebook" value="<?= htmlspecialchars((string) ($data->facebook ?? ''), ENT_QUOTES, 'UTF-8') ?>" autocomplete="off">
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label><?= htmlspecialchars($h('label_twitter', 'Twitter'), ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" class="form-control" name="twitter" id="twitter" value="<?= htmlspecialchars((string) ($data->twitter ?? ''), ENT_QUOTES, 'UTF-8') ?>" autocomplete="off">
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label><?= htmlspecialchars($h('label_linkedin', 'LinkedIn'), ENT_QUOTES, 'UTF-8') ?></label>
               <input type="text" class="form-control" name="linkedin" id="linkedin" value="<?= htmlspecialchars((string) ($data->linkedin ?? ''), ENT_QUOTES, 'UTF-8') ?>" autocomplete="off">
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label><?= htmlspecialchars($h('label_instagram', 'Instagram'), ENT_QUOTES, 'UTF-8') ?></label>
              <input type="text" class="form-control" name="instagram" id="instagram" value="<?= htmlspecialchars((string) ($data->instagram ?? ''), ENT_QUOTES, 'UTF-8') ?>" autocomplete="off">
            </div>
          </div>
        </div>

        <div class="form-group">
          <input type="submit" class="btn btn-success mr-2" name="settings" id="settings" value="<?= htmlspecialchars($h('btn_update', 'Update'), ENT_QUOTES, 'UTF-8') ?>"/>
        </div>
      </form>
    </div>
  </div>
</div>
