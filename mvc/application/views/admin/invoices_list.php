<?php
/** @var array<string,string> $invoicesListLang */
$il = isset($invoicesListLang) && is_array($invoicesListLang) ? $invoicesListLang : [];
$ilGet = static function (array $lang, string $key, string $fb): string {
	$v = $lang[$key] ?? '';

	return is_string($v) && $v !== '' ? $v : $fb;
};
?>
<?php $this->load->view('admin/admin_grid_scope_styles'); ?>
<div class="container-fluid pt-3 admin-grid-scope">
  <div class="row">
    <div class="col-md-6">
      <div class="page-header">
        <h3 class="page-title"><?= htmlspecialchars((string) ($title ?? ''), ENT_QUOTES, 'UTF-8') ?></h3>
      </div>
    </div>
    <div class="col-md-6 text-right">
      <button type="button" class="btn btn-outline-secondary mt-2 mt-sm-0 mr-1" id="btnStripeSyncAll">
        <i class="mdi mdi-sync" aria-hidden="true"></i> <?= htmlspecialchars($ilGet($il, 'stripeSyncAll', ''), ENT_QUOTES, 'UTF-8') ?>
      </button>
      <button type="button" class="btn btn-outline-secondary mt-2 mt-sm-0 mr-1" id="btnStripeCreateMissing">
        <i class="mdi mdi-credit-card-plus" aria-hidden="true"></i> <?= htmlspecialchars($ilGet($il, 'stripeCreateMissing', ''), ENT_QUOTES, 'UTF-8') ?>
      </button>
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
                  <th width="20"><?= htmlspecialchars($ilGet($il, 'colNum', '#'), ENT_QUOTES, 'UTF-8') ?></th>
                  <th><?= htmlspecialchars($ilGet($il, 'colDocNumber', ''), ENT_QUOTES, 'UTF-8') ?></th>
                  <th><?= htmlspecialchars($ilGet($il, 'colCompany', ''), ENT_QUOTES, 'UTF-8') ?></th>
                  <th><?= htmlspecialchars($ilGet($il, 'colSubscription', ''), ENT_QUOTES, 'UTF-8') ?></th>
                  <th width="130"><?= htmlspecialchars($ilGet($il, 'colDate', ''), ENT_QUOTES, 'UTF-8') ?></th>
                  <th width="90"><?= htmlspecialchars($ilGet($il, 'colPaid', ''), ENT_QUOTES, 'UTF-8') ?></th>
                  <th width="100"><?= htmlspecialchars($ilGet($il, 'colInvoicePaid', ''), ENT_QUOTES, 'UTF-8') ?></th>
                  <th width="100"><?= htmlspecialchars($ilGet($il, 'colDebt', ''), ENT_QUOTES, 'UTF-8') ?></th>
                  <th width="180"><?= htmlspecialchars($ilGet($il, 'colActions', ''), ENT_QUOTES, 'UTF-8') ?></th>
                </tr>
              </thead>
              <tbody>
                <?php $i = 1; ?>
                <?php foreach ($list as $v) : ?>
                  <?php
                  $row = is_object($v) ? (array) $v : (array) $v;
                  $iid = (int) ($row['invoice_id'] ?? 0);
                  $docNum = trim((string) ($row['doc_number'] ?? ''));
                  $cname = trim((string) ($row['company_name'] ?? ''));
                  $subId = (int) ($row['subscription_id'] ?? 0);
                  $planTitle = trim((string) ($row['plan_title'] ?? ''));
                  $subCell = $planTitle !== '' ? ($planTitle . ' (#' . $subId . ')') : ('#' . $subId);
                  $rawDate = $row['date'] ?? null;
                  $dateCell = ($rawDate !== null && (string) $rawDate !== '')
                    ? date('d-M-Y H:i', strtotime((string) $rawDate))
                    : '—';
                  $rawPaid = $row['paid'] ?? null;
                  $paidCell = $ilGet($il, 'paidDash', '—');
                  if ($rawPaid !== null && (string) $rawPaid !== '') {
                    if ((string) $rawPaid === '0' || (int) $rawPaid === 0) {
                      $paidCell = $ilGet($il, 'paidNo', '0');
                    } elseif ((string) $rawPaid === '1' || (int) $rawPaid === 1) {
                      $paidCell = $ilGet($il, 'paidYes', '1');
                    } else {
                      $paidCell = (string) $rawPaid;
                    }
                  }
                  $ip = $row['invoice_paid'] ?? null;
                  $debt = $row['debt'] ?? null;
                  $fmtMoney = static function ($x): string {
                    if ($x === null || (string) $x === '') {
                      return '—';
                    }

                    return number_format((float) $x, 2, '.', ' ');
                  };
                  $hasStripe = trim((string) ($row['stripe_id'] ?? '')) !== '';
                  ?>
                  <tr>
                    <td><?= $i ?></td>
                    <td><?= htmlspecialchars($docNum !== '' ? $docNum : '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($cname !== '' ? $cname : ('#' . (int) ($row['company_id'] ?? 0)), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($subCell, ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($dateCell, ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($paidCell, ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($fmtMoney($ip), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($fmtMoney($debt), ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                      <div class="admin-grid-actions">
                        <a href="<?= admin_url('invoices/view/' . $iid) ?>" class="btn admin-grid-btn admin-grid-btn--view" data-toggle="tooltip" title="<?= htmlspecialchars($ilGet($il, 'actionView', ''), ENT_QUOTES, 'UTF-8') ?>">
                          <i class="mdi mdi-eye" aria-hidden="true"></i>
                        </a>
                        <button type="button" class="btn admin-grid-btn admin-grid-btn--edit" data-toggle="tooltip" title="<?= htmlspecialchars($ilGet($il, 'stripeCreate', ''), ENT_QUOTES, 'UTF-8') ?>" onclick="stripeCreateInvoice(<?= $iid ?>)">
                          <i class="mdi mdi-credit-card-plus" aria-hidden="true"></i>
                        </button>
                        <?php if ($hasStripe) : ?>
                        <button type="button" class="btn admin-grid-btn admin-grid-btn--edit" data-toggle="tooltip" title="<?= htmlspecialchars($ilGet($il, 'stripeSync', ''), ENT_QUOTES, 'UTF-8') ?>" onclick="stripeSyncInvoice(<?= $iid ?>)">
                          <i class="mdi mdi-sync" aria-hidden="true"></i>
                        </button>
                        <?php endif; ?>
                        <button type="button" class="btn admin-grid-btn admin-grid-btn--delete" data-toggle="tooltip" title="<?= htmlspecialchars($ilGet($il, 'actionDelete', ''), ENT_QUOTES, 'UTF-8') ?>" onclick="deleteInvoice(<?= $iid ?>)">
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
  const invoiceDeleteUi = <?= json_encode($invoicesDeleteUi ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
  const invoiceStripeUi = <?= json_encode($il, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
  const stripeCreateBase = <?= json_encode(admin_url('invoices/stripe_create/'), JSON_UNESCAPED_UNICODE) ?>;
  const stripeSyncBase = <?= json_encode(admin_url('invoices/stripe_sync/'), JSON_UNESCAPED_UNICODE) ?>;
  const stripeSyncAllUrl = <?= json_encode(admin_url('invoices/stripe_sync_all'), JSON_UNESCAPED_UNICODE) ?>;
  const stripeCreateMissingUrl = <?= json_encode(admin_url('invoices/stripe_create_missing'), JSON_UNESCAPED_UNICODE) ?>;

  function deleteInvoice(rowId) {
    swal.fire({
      title: invoiceDeleteUi.confirmTitle || '',
      text: invoiceDeleteUi.confirmText || '',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc2626',
      cancelButtonColor: '#6b7280',
      confirmButtonText: invoiceDeleteUi.confirmYes || '',
      cancelButtonText: invoiceDeleteUi.confirmNo || '',
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = '<?= admin_url('invoices/delete/') ?>' + rowId;
      }
    });
  }

  function stripeCreateInvoice(rowId) {
    swal.fire({
      title: invoiceStripeUi.confirmStripeCreateTitle || '',
      text: invoiceStripeUi.confirmStripeCreateText || '',
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#16a34a',
      cancelButtonColor: '#6b7280',
      confirmButtonText: invoiceStripeUi.confirmYes || '',
      cancelButtonText: invoiceStripeUi.confirmNo || '',
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = stripeCreateBase + rowId;
      }
    });
  }

  function stripeSyncInvoice(rowId) {
    swal.fire({
      title: invoiceStripeUi.confirmStripeSyncTitle || '',
      text: invoiceStripeUi.confirmStripeSyncText || '',
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#16a34a',
      cancelButtonColor: '#6b7280',
      confirmButtonText: invoiceStripeUi.confirmYes || '',
      cancelButtonText: invoiceStripeUi.confirmNo || '',
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = stripeSyncBase + rowId;
      }
    });
  }

  document.getElementById('btnStripeSyncAll')?.addEventListener('click', function () {
    swal.fire({
      title: invoiceStripeUi.confirmStripeSyncAllTitle || '',
      text: invoiceStripeUi.confirmStripeSyncAllText || '',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#16a34a',
      cancelButtonColor: '#6b7280',
      confirmButtonText: invoiceStripeUi.confirmYes || '',
      cancelButtonText: invoiceStripeUi.confirmNo || '',
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = stripeSyncAllUrl;
      }
    });
  });

  document.getElementById('btnStripeCreateMissing')?.addEventListener('click', function () {
    swal.fire({
      title: invoiceStripeUi.confirmStripeCreateMissingTitle || '',
      text: invoiceStripeUi.confirmStripeCreateMissingText || '',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#16a34a',
      cancelButtonColor: '#6b7280',
      confirmButtonText: invoiceStripeUi.confirmYes || '',
      cancelButtonText: invoiceStripeUi.confirmNo || '',
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = stripeCreateMissingUrl;
      }
    });
  });
</script>
