<?php
/** @var array<string, string> $categoryListLang */
/** @var array<string, string> $categoryDeleteUi */
$cl = isset($categoryListLang) && is_array($categoryListLang) ? $categoryListLang : [];
$clGet = static function (array $lang, string $key, string $fb): string {
	$v = $lang[$key] ?? '';

	return is_string($v) && $v !== '' ? $v : $fb;
};
$catDeleteUi = isset($categoryDeleteUi) && is_array($categoryDeleteUi) ? $categoryDeleteUi : [];
$categoryStatusUrlJs = isset($categoryStatusUrl) ? (string) $categoryStatusUrl : admin_url('category/changeStatus');
?>
<style>
  .category-list-status-switch {
    padding-top: 0.15rem;
    min-height: 2rem;
  }
  .category-list-status-switch .custom-control-label::before {
    border-radius: 1rem;
  }
  .category-list-status-switch .custom-control-input:checked ~ .custom-control-label::before {
    background-color: #dc2626;
    border-color: #b91c1c;
  }
  .category-list-status-switch .custom-control-input:focus ~ .custom-control-label::before {
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
      <a href="<?= admin_url('category/add') ?>" class="btn admin-grid-page-cta mt-2 mt-sm-0">
        <i class="mdi mdi-plus-circle" aria-hidden="true"></i> <?= htmlspecialchars($clGet($cl, 'list_add_new', 'Add category'), ENT_QUOTES, 'UTF-8') ?>
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
                  <th width="20"><?= htmlspecialchars($clGet($cl, 'col_num', '#'), ENT_QUOTES, 'UTF-8') ?></th>
                  <th><?= htmlspecialchars($clGet($cl, 'col_category_name', 'Category name'), ENT_QUOTES, 'UTF-8') ?></th>
                  <th><?= htmlspecialchars($clGet($cl, 'col_created', 'Created'), ENT_QUOTES, 'UTF-8') ?></th>
                  <th><?= htmlspecialchars($clGet($cl, 'col_status', 'Status'), ENT_QUOTES, 'UTF-8') ?></th>
                  <th><?= htmlspecialchars($clGet($cl, 'col_actions', 'Actions'), ENT_QUOTES, 'UTF-8') ?></th>
                </tr>
              </thead>
              <tbody>
                <?php $i = 1; ?>
                <?php foreach ($list as $key => $v): ?>
                  <?php
                  $row = is_object($v) ? (array) $v : (array) $v;
                  $catId = (int) ($row['category_id'] ?? 0);
                  $rawStatus = $row['category_status'] ?? 0;
                  $isActive = (string) $rawStatus === '1' || (int) $rawStatus === 1;
                  ?>
                  <tr>
                    <td><?= (int) $i ?></td>
                    <td><?= htmlspecialchars((string) ($row['category_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars(date('d-M-Y', strtotime((string) ($row['category_created_at'] ?? ''))), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="align-middle">
                      <div class="custom-control custom-switch category-list-status-switch">
                        <input
                          type="checkbox"
                          class="custom-control-input category-status-toggle"
                          id="categoryStatus<?= $catId ?>"
                          data-category-id="<?= $catId ?>"
                          <?= $isActive ? 'checked' : '' ?>
                          aria-label="<?= htmlspecialchars($clGet($cl, 'statusSwitchAria', 'Enable or disable this category'), ENT_QUOTES, 'UTF-8') ?>"
                        >
                        <label class="custom-control-label" for="categoryStatus<?= $catId ?>">
                          <span class="sr-only"><?= htmlspecialchars($clGet($cl, 'statusSwitchAria', ''), ENT_QUOTES, 'UTF-8') ?></span>
                        </label>
                      </div>
                    </td>
                    <td>
                      <div class="admin-grid-actions">
                        <a href="<?= admin_url('category/edit/' . $catId) ?>" class="btn admin-grid-btn admin-grid-btn--edit" data-toggle="tooltip" title="<?= htmlspecialchars($clGet($cl, 'tooltip_edit', 'Edit'), ENT_QUOTES, 'UTF-8') ?>">
                          <i class="mdi mdi-pencil" aria-hidden="true"></i>
                        </a>
                        <button type="button" class="btn admin-grid-btn admin-grid-btn--delete" data-toggle="tooltip" title="<?= htmlspecialchars($clGet($cl, 'tooltip_delete', 'Delete'), ENT_QUOTES, 'UTF-8') ?>" onclick="deleteCategory(<?= $catId ?>)">
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
  const categoryDeleteUi = <?= json_encode([
	'title' => (string) ($catDeleteUi['title'] ?? ''),
	'text' => (string) ($catDeleteUi['text'] ?? ''),
	'yes' => (string) ($catDeleteUi['yes'] ?? ''),
	'no' => (string) ($catDeleteUi['no'] ?? ''),
  ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

  const categoryListLang = <?= json_encode([
  	'statusError' => $clGet($cl, 'statusError', 'The status could not be updated. Please try again.'),
  ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
  const categoryStatusUrl = <?= json_encode($categoryStatusUrlJs, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

  function deleteCategory(cId) {
    swal.fire({
      title: categoryDeleteUi.title || '',
      text: categoryDeleteUi.text || '',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc2626',
      cancelButtonColor: '#6b7280',
      confirmButtonText: categoryDeleteUi.yes || '',
      cancelButtonText: categoryDeleteUi.no || '',
      closeOnConfirm: true,
      closeOnCancel: true
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = '<?= admin_url('category/delete/') ?>' + cId;
      }
    });
  }

  $(function () {
    $('.category-status-toggle').on('change', function () {
      var $cb = $(this);
      var id = parseInt($cb.data('category-id'), 10);
      var enabled = $cb.is(':checked') ? 1 : 0;
      var previous = enabled ? 0 : 1;
      $.ajax({
        url: categoryStatusUrl,
        method: 'POST',
        dataType: 'json',
        data: { category_id: id, category_status: enabled }
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
          var msg = (Array.isArray(arr) && arr[0]) ? arr[0] : (categoryListLang.statusError || '');
          if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'error', title: msg });
          }
        }
      }).fail(function () {
        $cb.prop('checked', !!previous);
        if (typeof Swal !== 'undefined') {
          Swal.fire({ icon: 'error', title: categoryListLang.statusError || '' });
        }
      });
    });
  });
</script>
