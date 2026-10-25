<?php
/** @var array<string,string> $subscriptionsListLang */
$sl = isset($subscriptionsListLang) && is_array($subscriptionsListLang) ? $subscriptionsListLang : [];
$slGet = static function (array $lang, string $key, string $fb): string {
	$v = $lang[$key] ?? '';

	return is_string($v) && $v !== '' ? $v : $fb;
};
$subscriptionsStatusUrlJs = isset($subscriptionsStatusUrl) ? (string) $subscriptionsStatusUrl : admin_url('subscriptions/changeStatus');
?>
<style>
  .subscription-list-status-switch {
    padding-top: 0.15rem;
    min-height: 2rem;
  }
  .subscription-list-status-switch .custom-control-label::before {
    border-radius: 1rem;
  }
  .subscription-list-status-switch .custom-control-input:checked ~ .custom-control-label::before {
    background-color: #dc2626;
    border-color: #b91c1c;
  }
  .subscription-list-status-switch .custom-control-input:focus ~ .custom-control-label::before {
    box-shadow: 0 0 0 0.2rem rgba(220, 38, 38, 0.25);
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
      <button type="button" class="btn btn-outline-secondary mt-2 mt-sm-0 mr-1" id="btnStripeSyncAllSubs">
        <i class="mdi mdi-sync" aria-hidden="true"></i> <?= htmlspecialchars($slGet($sl, 'stripeSyncAll', ''), ENT_QUOTES, 'UTF-8') ?>
      </button>
      <a href="<?= admin_url('subscriptions/add') ?>" class="btn admin-grid-page-cta mt-2 mt-sm-0">
        <i class="mdi mdi-plus-circle" aria-hidden="true"></i> <?= htmlspecialchars($slGet($sl, 'addNew', 'Add subscription'), ENT_QUOTES, 'UTF-8') ?>
      </a>
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
                  <th width="20"><?= htmlspecialchars($slGet($sl, 'colNum', '#'), ENT_QUOTES, 'UTF-8') ?></th>
                  <th><?= htmlspecialchars($slGet($sl, 'colCompany', 'Company'), ENT_QUOTES, 'UTF-8') ?></th>
                  <th><?= htmlspecialchars($slGet($sl, 'colPlan', 'Plan'), ENT_QUOTES, 'UTF-8') ?></th>
                  <th width="110"><?= htmlspecialchars($slGet($sl, 'colStarts', 'Start'), ENT_QUOTES, 'UTF-8') ?></th>
                  <th width="110"><?= htmlspecialchars($slGet($sl, 'colEnds', 'End'), ENT_QUOTES, 'UTF-8') ?></th>
                  <th><?= htmlspecialchars($slGet($sl, 'colNotes', 'Notes'), ENT_QUOTES, 'UTF-8') ?></th>
                  <th width="120"><?= htmlspecialchars($slGet($sl, 'colStatus', 'Status'), ENT_QUOTES, 'UTF-8') ?></th>
                  <th width="140"><?= htmlspecialchars($slGet($sl, 'colActions', 'Actions'), ENT_QUOTES, 'UTF-8') ?></th>
                </tr>
              </thead>
              <tbody>
                <?php $i = 1; ?>
                <?php foreach ($list as $v) : ?>
                  <?php
                  $row = is_object($v) ? (array) $v : (array) $v;
                  $rid = (int) ($row['subscription_row_id'] ?? $row['id'] ?? $row['subscription_id'] ?? 0);
                  $rawStatus = $row['status'] ?? 0;
                  $isActive = (string) $rawStatus === '1' || (int) $rawStatus === 1;
                  $cname = trim((string) ($row['company_name'] ?? ''));
                  $ptitle = trim((string) ($row['plan_title'] ?? ''));
                  $sStart = $row['start_period_date'] ?? $row['starts_at'] ?? null;
                  $sEnd = $row['end_period_date'] ?? $row['ends_at'] ?? null;
                  $sa = $sStart !== null && (string) $sStart !== ''
                    ? date('d-M-Y', strtotime((string) $sStart))
                    : '—';
                  $ea = $sEnd !== null && (string) $sEnd !== ''
                    ? date('d-M-Y', strtotime((string) $sEnd))
                    : '—';
                  $notesRaw = isset($row['notes']) ? trim((string) $row['notes']) : '';
                  $hasStripe = trim((string) ($row['stripe_subscription_id'] ?? '')) !== '';
                  $notesDisp = '—';
                  if ($notesRaw !== '') {
                    $notesDisp = $notesRaw;
                    if (function_exists('mb_strlen') && mb_strlen($notesDisp, 'UTF-8') > 80) {
                      $notesDisp = mb_substr($notesDisp, 0, 80, 'UTF-8') . '…';
                    } elseif (strlen($notesDisp) > 80) {
                      $notesDisp = substr($notesDisp, 0, 80) . '...';
                    }
                  }
                  ?>
                  <tr>
                    <td><?= $i ?></td>
                    <td><?= htmlspecialchars($cname !== '' ? $cname : ('#' . (int) ($row['company_id'] ?? 0)), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($ptitle !== '' ? $ptitle : ('#' . (int) ($row['plan_id'] ?? 0)), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($sa, ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($ea, ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($notesDisp, ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="align-middle">
                      <div class="custom-control custom-switch subscription-list-status-switch">
                        <input
                          type="checkbox"
                          class="custom-control-input subscription-status-toggle"
                          id="subscriptionStatus<?= $rid ?>"
                          data-subscription-row-id="<?= $rid ?>"
                          <?= $isActive ? 'checked' : '' ?>
                          aria-label="<?= htmlspecialchars($slGet($sl, 'statusSwitchAria', ''), ENT_QUOTES, 'UTF-8') ?>"
                        >
                        <label class="custom-control-label" for="subscriptionStatus<?= $rid ?>">
                          <span class="sr-only"><?= htmlspecialchars($slGet($sl, 'statusSwitchAria', ''), ENT_QUOTES, 'UTF-8') ?></span>
                        </label>
                      </div>
                    </td>
                    <td>
                      <div class="admin-grid-actions">
                        <a href="<?= admin_url('subscriptions/edit/' . $rid) ?>" class="btn admin-grid-btn admin-grid-btn--edit" data-toggle="tooltip" title="<?= htmlspecialchars($slGet($sl, 'actionEdit', 'Edit'), ENT_QUOTES, 'UTF-8') ?>">
                          <i class="mdi mdi-pencil" aria-hidden="true"></i>
                        </a>
                        <button type="button" class="btn admin-grid-btn admin-grid-btn--edit" data-toggle="tooltip" title="<?= htmlspecialchars($slGet($sl, 'stripeCreate', ''), ENT_QUOTES, 'UTF-8') ?>" onclick="stripeCreateSubscription(<?= $rid ?>)">
                          <i class="mdi mdi-credit-card-plus" aria-hidden="true"></i>
                        </button>
                        <?php if ($hasStripe) : ?>
                        <button type="button" class="btn admin-grid-btn admin-grid-btn--edit" data-toggle="tooltip" title="<?= htmlspecialchars($slGet($sl, 'stripeSync', ''), ENT_QUOTES, 'UTF-8') ?>" onclick="stripeSyncSubscription(<?= $rid ?>)">
                          <i class="mdi mdi-sync" aria-hidden="true"></i>
                        </button>
                        <?php endif; ?>
                        <button type="button" class="btn admin-grid-btn admin-grid-btn--delete" data-toggle="tooltip" title="<?= htmlspecialchars($slGet($sl, 'actionDelete', 'Delete'), ENT_QUOTES, 'UTF-8') ?>" onclick="deleteSubscription(<?= $rid ?>)">
                          <i class="mdi mdi-delete" aria-hidden="true"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                  <?php $i++; ?>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
  const subscriptionDeleteUi = <?= json_encode($subscriptionsDeleteUi ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
  const subscriptionListLang = <?= json_encode([
  	'statusError' => $slGet($sl, 'statusError', 'The status could not be updated. Please try again.'),
  ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
  const subscriptionStatusUrl = <?= json_encode($subscriptionsStatusUrlJs, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

  function deleteSubscription(rowId) {
    swal.fire({
      title: subscriptionDeleteUi.confirmTitle || '',
      text: subscriptionDeleteUi.confirmText || '',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc2626',
      cancelButtonColor: '#6b7280',
      confirmButtonText: subscriptionDeleteUi.confirmYes || '',
      cancelButtonText: subscriptionDeleteUi.confirmNo || '',
      closeOnConfirm: true,
      closeOnCancel: true
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = '<?= admin_url('subscriptions/delete/') ?>' + rowId;
      }
    });
  }

  const subscriptionStripeUi = <?= json_encode($sl, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
  const stripeSubCreateBase = <?= json_encode(admin_url('subscriptions/stripe_create/'), JSON_UNESCAPED_UNICODE) ?>;
  const stripeSubSyncBase = <?= json_encode(admin_url('subscriptions/stripe_sync/'), JSON_UNESCAPED_UNICODE) ?>;
  const stripeSubSyncAllUrl = <?= json_encode(admin_url('subscriptions/stripe_sync_all'), JSON_UNESCAPED_UNICODE) ?>;

  function stripeCreateSubscription(rowId) {
    swal.fire({
      title: subscriptionStripeUi.confirmStripeCreateTitle || '',
      text: subscriptionStripeUi.confirmStripeCreateText || '',
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#16a34a',
      cancelButtonColor: '#6b7280',
      confirmButtonText: subscriptionStripeUi.confirmYes || '',
      cancelButtonText: subscriptionStripeUi.confirmNo || '',
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = stripeSubCreateBase + rowId;
      }
    });
  }

  function stripeSyncSubscription(rowId) {
    swal.fire({
      title: subscriptionStripeUi.confirmStripeSyncTitle || '',
      text: subscriptionStripeUi.confirmStripeSyncText || '',
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#16a34a',
      cancelButtonColor: '#6b7280',
      confirmButtonText: subscriptionStripeUi.confirmYes || '',
      cancelButtonText: subscriptionStripeUi.confirmNo || '',
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = stripeSubSyncBase + rowId;
      }
    });
  }

  document.getElementById('btnStripeSyncAllSubs')?.addEventListener('click', function () {
    swal.fire({
      title: subscriptionStripeUi.confirmStripeSyncAllTitle || '',
      text: subscriptionStripeUi.confirmStripeSyncAllText || '',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#16a34a',
      cancelButtonColor: '#6b7280',
      confirmButtonText: subscriptionStripeUi.confirmYes || '',
      cancelButtonText: subscriptionStripeUi.confirmNo || '',
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = stripeSubSyncAllUrl;
      }
    });
  });

  $(function () {
    $('.subscription-status-toggle').on('change', function () {
      var $cb = $(this);
      var id = parseInt($cb.data('subscription-row-id'), 10);
      var enabled = $cb.is(':checked') ? 1 : 0;
      var previous = enabled ? 0 : 1;
      $.ajax({
        url: subscriptionStatusUrl,
        method: 'POST',
        dataType: 'json',
        data: { subscription_row_id: id, subscription_enabled: enabled }
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
          var msg = (Array.isArray(arr) && arr[0]) ? arr[0] : (subscriptionListLang.statusError || '');
          if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'error', title: msg });
          }
        }
      }).fail(function () {
        $cb.prop('checked', !!previous);
        if (typeof Swal !== 'undefined') {
          Swal.fire({ icon: 'error', title: subscriptionListLang.statusError || '' });
        }
      });
    });
  });
</script>
