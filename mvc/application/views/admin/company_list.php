<?php
/** @var array<string,string> $companyListLang */
/** @var array<int, array{phonecode: int, phonemask: string}> $adminCountryPhoneMeta */
/** @var array<string, mixed> $stripeBulkSyncUi */
$cl = isset($companyListLang) && is_array($companyListLang) ? $companyListLang : [];
$adminCountryPhoneMeta = isset($adminCountryPhoneMeta) && is_array($adminCountryPhoneMeta) ? $adminCountryPhoneMeta : [];
$stripeBulkSyncUi = isset($stripeBulkSyncUi) && is_array($stripeBulkSyncUi) ? $stripeBulkSyncUi : [];
$stripeBulkSyncEnabled = ! empty($stripeBulkSyncUi['enabled']);
$stripeBulkSyncPending = isset($stripeBulkSyncUi['pendingCount']) ? (int) $stripeBulkSyncUi['pendingCount'] : 0;
$clGet = static function (array $lang, string $key, string $fb): string {
	$v = $lang[$key] ?? '';

	return is_string($v) && $v !== '' ? $v : $fb;
};
$companyStatusUrlJs = isset($companyStatusUrl) ? (string) $companyStatusUrl : admin_url('company/changeStatus');
?>
<style>
  .company-list-status-switch {
    padding-top: 0.15rem;
    min-height: 2rem;
  }
  .company-list-status-switch .custom-control-label::before {
    border-radius: 1rem;
  }
  .company-list-status-switch .custom-control-input:checked ~ .custom-control-label::before {
    background-color: #dc2626;
    border-color: #b91c1c;
  }
  .company-list-status-switch .custom-control-input:focus ~ .custom-control-label::before {
    box-shadow: 0 0 0 0.2rem rgba(220, 38, 38, 0.25);
  }
  .admin-grid-scope .admin-stripe-bulk-btn,
  .admin-stripe-bulk-btn {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    min-height: 2.5rem;
    padding: 0.55rem 1.25rem !important;
    background: #ffffff !important;
    background-color: #ffffff !important;
    background-image: none !important;
    border: 1px solid #6b7280 !important;
    border-radius: 0.45rem !important;
    color: #4b5563 !important;
    -webkit-text-fill-color: #4b5563 !important;
    font-weight: 500;
    font-size: 0.9rem;
    letter-spacing: 0;
    text-transform: none;
    text-decoration: none !important;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04) !important;
    transition: border-color 0.15s ease, background-color 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
  }
  .admin-grid-scope .admin-stripe-bulk-btn:hover:not(:disabled),
  .admin-grid-scope .admin-stripe-bulk-btn:focus:not(:disabled),
  .admin-stripe-bulk-btn:hover:not(:disabled),
  .admin-stripe-bulk-btn:focus:not(:disabled) {
    background-color: #f9fafb !important;
    border-color: #4b5563 !important;
    color: #1f2937 !important;
    -webkit-text-fill-color: #1f2937 !important;
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.08) !important;
    filter: none !important;
  }
  .admin-grid-scope .admin-stripe-bulk-btn:disabled,
  .admin-stripe-bulk-btn:disabled {
    background-color: #f3f4f6 !important;
    border-color: #d1d5db !important;
    color: #9ca3af !important;
    -webkit-text-fill-color: #9ca3af !important;
    cursor: not-allowed;
    opacity: 1;
  }
  .admin-grid-scope .admin-stripe-bulk-btn .mdi,
  .admin-stripe-bulk-btn .mdi {
    font-size: 1.15rem;
    line-height: 1;
    color: #6b7280;
    opacity: 1;
  }
  .admin-grid-scope .admin-stripe-bulk-btn:hover:not(:disabled) .mdi,
  .admin-stripe-bulk-btn:hover:not(:disabled) .mdi {
    color: #374151;
  }
  .admin-grid-scope .admin-stripe-bulk-btn:disabled .mdi,
  .admin-stripe-bulk-btn:disabled .mdi {
    color: #9ca3af;
  }
  .admin-grid-scope .admin-stripe-bulk-btn .admin-stripe-bulk-btn-label,
  .admin-stripe-bulk-btn .admin-stripe-bulk-btn-label {
    font-size: 0.9rem;
    font-weight: 500;
    letter-spacing: 0;
  }
  .admin-grid-scope .admin-stripe-bulk-btn .badge,
  .admin-stripe-bulk-btn .badge {
    margin-left: 0.35rem;
    padding: 0.28em 0.55em;
    font-size: 0.72rem;
    font-weight: 600;
    color: #374151 !important;
    background-color: #e5e7eb !important;
    border-radius: 999px;
    text-transform: none;
    letter-spacing: 0;
  }
</style>
<?php $this->load->view('admin/admin_grid_scope_styles'); ?>
<div class="container-fluid pt-3 admin-grid-scope">
  <div class="row">
    <div class="col-md-6">
      <div class="page-header">
        <h3 class="page-title"><?= htmlspecialchars((string) ($title ?? ''), ENT_QUOTES, 'UTF-8') ?></h3>
      </div>
    </div>
    <div class="col-md-6 text-right">
      <?php if ($stripeBulkSyncEnabled): ?>
        <button type="button" id="btnStripeBulkSync"
          class="btn mt-2 mt-sm-0 mr-sm-2 admin-stripe-bulk-btn"
          data-pending="<?= $stripeBulkSyncPending ?>"
          <?= $stripeBulkSyncPending < 1 ? 'disabled' : '' ?>>
          <i class="mdi mdi-refresh" aria-hidden="true"></i>
          <span class="admin-stripe-bulk-btn-label"><?= htmlspecialchars((string) ($stripeBulkSyncUi['buttonLabel'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
          <?php if ($stripeBulkSyncPending > 0): ?>
            <span class="badge badge-light ml-1 admin-stripe-bulk-pending-badge"><?= $stripeBulkSyncPending ?></span>
          <?php endif; ?>
        </button>
      <?php endif; ?>
      <a href="<?= admin_url('company/add') ?>" class="btn admin-grid-page-cta mt-2 mt-sm-0"><i class="mdi mdi-plus-circle" aria-hidden="true"></i> <?= htmlspecialchars($clGet($cl, 'addCompany', 'Add company'), ENT_QUOTES, 'UTF-8') ?></a>
    </div>
  </div>
  <div class="card">
    <div class="card-body">
      <div class="row">
        <div class="col-12">
          <div class="table-responsive">
            <table id="order-listing" class="table table-striped">
              <thead>
                <tr>
                  <th width="20">#</th>
                  <th><?= htmlspecialchars($clGet($cl, 'colImage', 'Image'), ENT_QUOTES, 'UTF-8') ?></th>
                  <th><?= htmlspecialchars($clGet($cl, 'colName', 'Company name'), ENT_QUOTES, 'UTF-8') ?></th>
                  <th><?= htmlspecialchars($clGet($cl, 'colEmail', 'Email'), ENT_QUOTES, 'UTF-8') ?></th>
                  <th><?= htmlspecialchars($clGet($cl, 'colPhone', 'Phone'), ENT_QUOTES, 'UTF-8') ?></th>
                  <th><?= htmlspecialchars($clGet($cl, 'colStripe', 'Stripe'), ENT_QUOTES, 'UTF-8') ?></th>
                  <th width="110"><?= htmlspecialchars($clGet($cl, 'colWhatsapp', 'WhatsApp'), ENT_QUOTES, 'UTF-8') ?></th>
                  <th><?= htmlspecialchars($clGet($cl, 'colWhatsappInstanceName', 'Instance name'), ENT_QUOTES, 'UTF-8') ?></th>
                  <th><?= htmlspecialchars($clGet($cl, 'colWhatsappApikey', 'WhatsApp API key'), ENT_QUOTES, 'UTF-8') ?></th>
                  <th width="100"><?= htmlspecialchars($clGet($cl, 'colCreated', 'Created'), ENT_QUOTES, 'UTF-8') ?></th>
                  <th width="120"><?= htmlspecialchars($clGet($cl, 'colStatus', 'Status'), ENT_QUOTES, 'UTF-8') ?></th>
                  <th width="140"><?= htmlspecialchars($clGet($cl, 'colActions', 'Actions'), ENT_QUOTES, 'UTF-8') ?></th>
                </tr>
              </thead>
              <tbody>
                <?php $i = 1; ?>
                <?php foreach ($list as $key => $v): ?>
                  <?php
                  $row = is_object($v) ? (array) $v : (array) $v;
                  $cid = (int) ($row['company_id'] ?? 0);
                  $rawStatus = $row['status'] ?? 0;
                  $isActive = (string) $rawStatus === '1' || (int) $rawStatus === 1;
                  $img = isset($row['image']) ? (string) $row['image'] : '';
                  // Mismo origen que el admin: el navegador no puede cargar //API_SERVER (MinIO vía API) en muchos despliegues.
                  $companyListLogoSrc = ($cid > 0 && $img !== '')
                    ? admin_url('company/serve_company_logo/' . $cid) . '?v=' . rawurlencode($img)
                    : '';
                  ?>
                  <tr>
                    <td><?= $i ?></td>
                    <td>
                      <?php if ($companyListLogoSrc !== '') { ?>
                        <img src="<?= htmlspecialchars($companyListLogoSrc, ENT_QUOTES, 'UTF-8') ?>" alt="">
                      <?php } else { ?>
                        <img src="<?= base_url('uploads/noimg.png') ?>" alt="">
                      <?php } ?>
                    </td>
                    <td><?= htmlspecialchars((string) ($row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($row['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?php
                      $phoneDisplay = admin_format_company_phone_display(
                          (string) ($row['phone_number'] ?? ''),
                          $row['country'] ?? '',
                          $adminCountryPhoneMeta
                      );
                      echo htmlspecialchars($phoneDisplay, ENT_QUOTES, 'UTF-8');
                    ?></td>
                    <td>
                      <?php $sid = trim((string) ($row['stripe_id'] ?? '')); ?>
                      <?php if ($sid !== '') : ?>
                        <span class="badge badge-info" title="<?= htmlspecialchars($sid, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($sid, ENT_QUOTES, 'UTF-8') ?></span>
                      <?php else : ?>
                        <span class="text-muted">—</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php
                      $waOn = function_exists('admin_company_whatsapp_flag_from_row')
                          ? admin_company_whatsapp_flag_from_row($row)
                          : false;
                      ?>
                      <?php if ($waOn) : ?>
                        <span class="badge badge-success"><i class="mdi mdi-whatsapp mr-1" aria-hidden="true"></i>ON</span>
                      <?php else : ?>
                        <span class="badge badge-secondary">OFF</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php $waInstName = trim((string) ($row['whatsapp_instance_name'] ?? '')); ?>
                      <?php if ($waInstName !== '') : ?>
                        <span><?= htmlspecialchars($waInstName, ENT_QUOTES, 'UTF-8') ?></span>
                      <?php else : ?>
                        <span class="text-muted">—</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php $waKey = trim((string) ($row['whatsapp_apikey'] ?? '')); ?>
                      <?php if ($waKey !== '') : ?>
                        <span title="<?= htmlspecialchars($waKey, ENT_QUOTES, 'UTF-8') ?>" style="font-family: monospace; font-size: 0.82rem;">
                          <?= htmlspecialchars(strlen($waKey) > 18 ? substr($waKey, 0, 8) . '…' . substr($waKey, -6) : $waKey, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                      <?php else : ?>
                        <span class="text-muted">—</span>
                      <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars(date('d-M-Y', strtotime((string) ($row['created'] ?? ''))), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="align-middle">
                      <div class="custom-control custom-switch company-list-status-switch">
                        <input
                          type="checkbox"
                          class="custom-control-input company-status-toggle"
                          id="companyStatus<?= $cid ?>"
                          data-company-id="<?= $cid ?>"
                          <?= $isActive ? 'checked' : '' ?>
                          aria-label="<?= htmlspecialchars($clGet($cl, 'statusSwitchAria', 'Enable or disable this company'), ENT_QUOTES, 'UTF-8') ?>"
                        >
                        <label class="custom-control-label" for="companyStatus<?= $cid ?>">
                          <span class="sr-only"><?= htmlspecialchars($clGet($cl, 'statusSwitchAria', ''), ENT_QUOTES, 'UTF-8') ?></span>
                        </label>
                      </div>
                    </td>
                    <td>
                      <div class="admin-grid-actions">
                        <a href="<?= admin_url('company/view/'.$cid) ?>" class="btn admin-grid-btn admin-grid-btn--view" data-toggle="tooltip" title="<?= htmlspecialchars($clGet($cl, 'actionView', 'View'), ENT_QUOTES, 'UTF-8') ?>">
                          <i class="mdi mdi-eye" aria-hidden="true"></i>
                        </a>
                        <a href="<?= admin_url('company/edit/'.$cid) ?>" class="btn admin-grid-btn admin-grid-btn--edit" data-toggle="tooltip" title="<?= htmlspecialchars($clGet($cl, 'actionEdit', 'Edit'), ENT_QUOTES, 'UTF-8') ?>">
                          <i class="mdi mdi-pencil" aria-hidden="true"></i>
                        </a>
                        <button type="button" class="btn admin-grid-btn admin-grid-btn--delete" data-toggle="tooltip" title="<?= htmlspecialchars($clGet($cl, 'actionDelete', 'Delete'), ENT_QUOTES, 'UTF-8') ?>" onclick="deleteCompany(<?= $cid ?>)">
                          <i class="mdi mdi-delete" aria-hidden="true"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                  <?php $i++; ?>
                <?php endforeach ?>

              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
    const companyDeleteUi = <?= json_encode($companyDeleteUi ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    const stripeBulkSyncUi = <?= json_encode($stripeBulkSyncUi ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    const companyListLang = <?= json_encode([
    	'statusError' => $clGet($cl, 'statusError', 'The status could not be updated. Please try again.'),
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    const companyStatusUrl = <?= json_encode($companyStatusUrlJs, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

    function deleteCompany(cId) {
      swal.fire({
        title: companyDeleteUi.confirmTitle || '',
        text: companyDeleteUi.confirmText || '',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: companyDeleteUi.confirmYes || '',
        cancelButtonText: companyDeleteUi.confirmNo || '',
        closeOnConfirm: true,
        closeOnCancel: true
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = '<?= admin_url('company/delete/') ?>'+cId
        }
      });
    }

    function escapeHtmlAdmin(value) {
      return String(value == null ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
    }

    function buildStripeBulkResultHtml(data, message) {
      var processed = (data && typeof data.processed !== 'undefined') ? Number(data.processed) || 0 : 0;
      var updated = (data && typeof data.updated !== 'undefined') ? Number(data.updated) || 0 : 0;
      var errors = (data && Array.isArray(data.errors)) ? data.errors : [];
      var html = '<div class="text-left">';
      if (message) {
        html += '<p class="mb-2">' + escapeHtmlAdmin(message) + '</p>';
      }
      html += '<ul class="list-unstyled mb-2" style="font-size:0.95rem;">';
      html += '<li><strong>' + escapeHtmlAdmin(stripeBulkSyncUi.processedLabel || 'Processed') + ':</strong> ' + processed + '</li>';
      html += '<li><strong>' + escapeHtmlAdmin(stripeBulkSyncUi.updatedLabel || 'Updated') + ':</strong> ' + updated + '</li>';
      html += '<li><strong>' + escapeHtmlAdmin(stripeBulkSyncUi.errorsLabel || 'Errors') + ':</strong> ' + errors.length + '</li>';
      html += '</ul>';
      if (errors.length) {
        html += '<details><summary>' + escapeHtmlAdmin(stripeBulkSyncUi.errorsLabel || 'Errors') + '</summary>';
        html += '<ul class="mt-2" style="max-height:200px;overflow:auto;font-size:0.85rem;">';
        errors.slice(0, 50).forEach(function (er) {
          var cid = er && er.company_id ? er.company_id : '?';
          var msg = er && er.message ? er.message : '';
          html += '<li>#' + escapeHtmlAdmin(cid) + ': ' + escapeHtmlAdmin(msg) + '</li>';
        });
        html += '</ul></details>';
      }
      html += '</div>';
      return html;
    }

    function runStripeBulkSync() {
      if (!stripeBulkSyncUi || !stripeBulkSyncUi.endpointUrl) {
        return;
      }
      var $btn = $('#btnStripeBulkSync');
      $btn.prop('disabled', true);
      var originalLabel = $btn.find('.admin-stripe-bulk-btn-label').text();
      $btn.find('.admin-stripe-bulk-btn-label').text(stripeBulkSyncUi.runningText || originalLabel);
      Swal.fire({
        title: stripeBulkSyncUi.runningText || '',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: function () { Swal.showLoading(); }
      });
      $.ajax({
        url: stripeBulkSyncUi.endpointUrl,
        method: 'POST',
        dataType: 'json'
      }).done(function (resp) {
        var data = (resp && resp.data) ? resp.data : { processed: 0, updated: 0, errors: [] };
        var hadError = !!(resp && resp.error);
        Swal.fire({
          icon: hadError ? 'error' : 'success',
          title: hadError ? (stripeBulkSyncUi.errorTitle || '') : (stripeBulkSyncUi.doneTitle || ''),
          html: buildStripeBulkResultHtml(data, resp && resp.message ? resp.message : ''),
          confirmButtonColor: '#dc2626'
        }).then(function () { window.location.reload(); });
      }).fail(function (xhr) {
        var resp = xhr && xhr.responseJSON ? xhr.responseJSON : null;
        var data = (resp && resp.data) ? resp.data : { processed: 0, updated: 0, errors: [] };
        var msg = resp && resp.message ? resp.message : (stripeBulkSyncUi.errorTitle || '');
        Swal.fire({
          icon: 'error',
          title: stripeBulkSyncUi.errorTitle || '',
          html: buildStripeBulkResultHtml(data, msg),
          confirmButtonColor: '#dc2626'
        });
      }).always(function () {
        $btn.prop('disabled', false);
        $btn.find('.admin-stripe-bulk-btn-label').text(originalLabel);
      });
    }

    $(function () {
      $('#btnStripeBulkSync').on('click', function () {
        var $btn = $(this);
        var pending = parseInt($btn.data('pending'), 10);
        if (!isNaN(pending) && pending < 1) {
          Swal.fire({
            icon: 'info',
            title: stripeBulkSyncUi.noneTitle || '',
            confirmButtonColor: '#dc2626'
          });
          return;
        }
        Swal.fire({
          title: stripeBulkSyncUi.confirmTitle || '',
          text: stripeBulkSyncUi.confirmText || '',
          icon: 'question',
          showCancelButton: true,
          confirmButtonColor: '#dc2626',
          cancelButtonColor: '#6b7280',
          confirmButtonText: stripeBulkSyncUi.confirmYes || '',
          cancelButtonText: stripeBulkSyncUi.confirmNo || ''
        }).then(function (result) {
          if (result.isConfirmed) {
            runStripeBulkSync();
          }
        });
      });

      $('.company-status-toggle').on('change', function () {
        var $cb = $(this);
        var id = parseInt($cb.data('company-id'), 10);
        var enabled = $cb.is(':checked') ? 1 : 0;
        var previous = enabled ? 0 : 1;
        $.ajax({
          url: companyStatusUrl,
          method: 'POST',
          dataType: 'json',
          data: { company_id: id, status: enabled }
        }).done(function (arr) {
          if (Array.isArray(arr) && arr[1] === 'success') {
            if (typeof Swal !== 'undefined') {
              Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: arr[0],
                showConfirmButton: false,
                timer: 2000
              });
            }
          } else {
            $cb.prop('checked', !!previous);
            var msg = (Array.isArray(arr) && arr[0]) ? arr[0] : (companyListLang.statusError || '');
            if (typeof Swal !== 'undefined') {
              Swal.fire({ icon: 'error', title: msg });
            }
          }
        }).fail(function () {
          $cb.prop('checked', !!previous);
          if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'error', title: companyListLang.statusError || '' });
          }
        });
      });
    });
  </script>
