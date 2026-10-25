<?php
$pageLang = isset($logsListLang) && is_array($logsListLang) ? $logsListLang : [];
$t = static function (string $key, string $fallback = '') use ($pageLang): string {
	$v = trim((string) ($pageLang[$key] ?? ''));

	return $v !== '' ? $v : $fallback;
};
?>
<div class="container-fluid pt-3">
  <div class="row">
    <div class="col-md-6">
      <div class="page-header">
        <h3 class="page-title"> <?=@$title?></h3>
      </div>
    </div>

  </div>
  <div class="card contentHeight">
    <div class="card-body ">
      <div class="boxsrch">
        <div class="row">
          <div class="col-md-3">
            <div class="form-group">
              <label><?= htmlspecialchars($t('label_from_date', 'From date'), ENT_QUOTES, 'UTF-8') ?></label>
              <input type="text" id="fromdate" class="form-control" name="">
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-group">
              <label><?= htmlspecialchars($t('label_to_date', 'To date'), ENT_QUOTES, 'UTF-8') ?></label>
              <input type="text" id="todate" class="form-control" name="">
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label><?= htmlspecialchars($t('label_log_type', 'Log type'), ENT_QUOTES, 'UTF-8') ?></label>
              <select class="form-control" id="log_type">
                <option value="all"><?= htmlspecialchars($t('log_type_all', 'All'), ENT_QUOTES, 'UTF-8') ?></option>
                <option value="sync"><?= htmlspecialchars($t('log_type_sync', 'Sync'), ENT_QUOTES, 'UTF-8') ?></option>
                <option value="login"><?= htmlspecialchars($t('log_type_login', 'Login'), ENT_QUOTES, 'UTF-8') ?></option>
              </select>
            </div>
          </div>
          <div class="col-md-2">
            <div class="form-group">
              <button class="btn btn-primary mt-4" onclick="logtotalSearch()"><i class="mdi mdi-magnify"></i> <?= htmlspecialchars($t('btn_search', 'Search'), ENT_QUOTES, 'UTF-8') ?></button>
            </div>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-12">
          <div class="table-responsive">
            <table class="table table-bordered" id="order-listing">
              <thead>
                <tr>
                  <th width=20><?= htmlspecialchars($t('col_num', '#'), ENT_QUOTES, 'UTF-8') ?></th>
                  <th><?= htmlspecialchars($t('col_datetime', 'Date/Time'), ENT_QUOTES, 'UTF-8') ?></th>
                  <th><?= htmlspecialchars($t('col_user', 'User'), ENT_QUOTES, 'UTF-8') ?> </th>
                  <th><?= htmlspecialchars($t('col_log_type', 'Log type'), ENT_QUOTES, 'UTF-8') ?></th>
                  <th><?= htmlspecialchars($t('col_notes', 'Notes'), ENT_QUOTES, 'UTF-8') ?></th>
                </tr>
              </thead>
              <tbody class="filter_data">
                <?php $i = 1; ?>
                <?php foreach ($list as $key => $v): ?>
                  <tr>
                    <td><?=$i?></td>
                    <td><?= date('d-M-Y H:i A', strtotime(@$v->created_on)) ?></td>
                    <td><?= htmlspecialchars((string) (@$v->seller_email ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                       <?= htmlspecialchars((string) (@$v->log_type ?? ''), ENT_QUOTES, 'UTF-8') ?>
                      </td>
                      <td>
                       <?= htmlspecialchars((string) (@$v->notes ?? ''), ENT_QUOTES, 'UTF-8') ?>
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
     function logtotalSearch(){
      var fromdate = $("#fromdate").val();
      var todate = $("#todate").val();
      var logtype = $("#log_type").val();
      $.ajax({
        url: adminUrl+'logs/filterSearch',       
        type: 'POST',       
        dataType: 'json',       
        data: {fromdate: fromdate,todate:todate,logtype:logtype},
        success: function(response) 
      {
        $('.filter_data').html(response.log_list);
       
      }
      })
     
    }
 
  </script>
