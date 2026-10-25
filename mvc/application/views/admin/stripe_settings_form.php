<?php
/** @var array<string,string>|null $pageTexts */
/** @var object|null $entidad */
/** @var object|null $stripeSecret */
/** @var object|null $stripePublish */
$pt = isset($pageTexts) && is_array($pageTexts) ? $pageTexts : [];
$h = static function (string $k) use ($pt): string {
	return htmlspecialchars((string) ($pt[$k] ?? ''), ENT_QUOTES, 'UTF-8');
};
$pv = static function ($obj, string $key, string $default = '') {
	if (! is_object($obj) || ! isset($obj->{$key}) || $obj->{$key} === null) {
		return $default;
	}

	return (string) $obj->{$key};
};
$entidadName = htmlspecialchars($pv($entidad ?? null, 'entity_name'), ENT_QUOTES, 'UTF-8');
$entidadDir = htmlspecialchars($pv($entidad ?? null, 'entity_address'), ENT_QUOTES, 'UTF-8');
$entidadPais = htmlspecialchars($pv($entidad ?? null, 'entity_country'), ENT_QUOTES, 'UTF-8');
$entidadEstado = htmlspecialchars($pv($entidad ?? null, 'entity_state'), ENT_QUOTES, 'UTF-8');
$entidadCiudad = htmlspecialchars($pv($entidad ?? null, 'entity_city'), ENT_QUOTES, 'UTF-8');
$secretVal = htmlspecialchars($pv($stripeSecret ?? null, 'secret'), ENT_QUOTES, 'UTF-8');
$publishVal = htmlspecialchars($pv($stripePublish ?? null, 'secretAPI'), ENT_QUOTES, 'UTF-8');
$this->load->view('admin/admin_grid_scope_styles');
?>
<div class="container-fluid page-body-wrapper admin-grid-scope">
  <div class="main-panel">
    <div class="p-4" style="min-height: 500px;">
      <div class="page-header">
        <h3 class="page-title"><?= $h('page_title') ?></h3>
      </div>
      <form class="forms-sample" action="<?= admin_url('stripesettings/update') ?>" method="post" autocomplete="off">
        <div class="card mb-3">
          <div class="card-body">
            <h5 class="mb-3"><i class="mdi mdi-domain mr-2" aria-hidden="true"></i><?= $h('section_issuing_entity') ?></h5>
            <div class="form-group row">
              <div class="col-md-12">
                <label><?= $h('label_entidad_emisora') ?> <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="entity_name" maxlength="100" required value="<?= $entidadName ?>">
              </div>
            </div>
            <div class="form-group row">
              <div class="col-md-12">
                <label><?= $h('label_entidad_direccion') ?></label>
                <textarea class="form-control" name="entity_address" rows="2"><?= $entidadDir ?></textarea>
              </div>
            </div>
            <div class="form-group row">
              <div class="col-md-4">
                <label><?= $h('label_entidad_pais') ?></label>
                <input type="text" class="form-control" name="entity_country" maxlength="50" value="<?= $entidadPais ?>">
              </div>
              <div class="col-md-4">
                <label><?= $h('label_entidad_estado') ?></label>
                <input type="text" class="form-control" name="entity_state" maxlength="50" value="<?= $entidadEstado ?>">
              </div>
              <div class="col-md-4">
                <label><?= $h('label_entidad_ciudad') ?></label>
                <input type="text" class="form-control" name="entity_city" maxlength="50" value="<?= $entidadCiudad ?>">
              </div>
            </div>
          </div>
        </div>

        <div class="card mb-3">
          <div class="card-body">
            <h5 class="mb-3"><i class="mdi mdi-key-variant mr-2" aria-hidden="true"></i><?= $h('section_stripe_secret') ?></h5>
            <div class="form-group row">
              <div class="col-md-12">
                <label><?= $h('label_stripe_secret') ?></label>
                <input type="password" class="form-control" name="stripe_secret" id="stripe_secret_input" value="<?= $secretVal ?>" autocomplete="new-password" spellcheck="false">
                <small class="form-text text-muted"><?= $h('help_stripe_secret') ?></small>
                <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="toggleStripeSecret"><i class="mdi mdi-eye" aria-hidden="true"></i></button>
              </div>
            </div>
          </div>
        </div>

        <div class="card mb-3">
          <div class="card-body">
            <h5 class="mb-3"><i class="mdi mdi-key-outline mr-2" aria-hidden="true"></i><?= $h('section_stripe_publish') ?></h5>
            <div class="form-group row">
              <div class="col-md-12">
                <label><?= $h('label_stripe_publish') ?></label>
                <input type="password" class="form-control" name="stripe_publish" id="stripe_publish_input" value="<?= $publishVal ?>" autocomplete="new-password" spellcheck="false">
                <small class="form-text text-muted"><?= $h('help_stripe_publish') ?></small>
                <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="toggleStripePublish"><i class="mdi mdi-eye" aria-hidden="true"></i></button>
              </div>
            </div>
          </div>
        </div>

        <div class="form-group mt-3">
          <button type="submit" class="btn btn-success mr-2"><i class="mdi mdi-content-save mr-1" aria-hidden="true"></i><?= $h('btn_submit') ?></button>
          <a class="btn btn-danger" href="<?= admin_url() ?>"><?= $h('btn_cancel') ?></a>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
  (function () {
    function bind(buttonId, inputId) {
      var btn = document.getElementById(buttonId);
      var input = document.getElementById(inputId);
      if (!btn || !input) return;
      btn.addEventListener('click', function () {
        if (input.type === 'password') {
          input.type = 'text';
          btn.innerHTML = '<i class="mdi mdi-eye-off" aria-hidden="true"></i>';
        } else {
          input.type = 'password';
          btn.innerHTML = '<i class="mdi mdi-eye" aria-hidden="true"></i>';
        }
      });
    }
    bind('toggleStripeSecret', 'stripe_secret_input');
    bind('toggleStripePublish', 'stripe_publish_input');
  })();
</script>
