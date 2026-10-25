<?php
/**
 * Historial de registros (company).
 *
 * @var string $title
 * @var array $list
 * @var array $logsListLang
 */
$pageLang = isset($logsListLang) && is_array($logsListLang) ? $logsListLang : [];
$t = static function (string $key, string $fallback = '') use ($pageLang): string {
    $v = trim((string) ($pageLang[$key] ?? ''));

    return $v !== '' ? $v : $fallback;
};
$labelLogType = static function ($logType) use ($t): string {
    $logType = trim((string) $logType);
    if ($logType === 'login') {
        return $t('log_type_login', 'Login');
    }
    if ($logType === 'sync') {
        return $t('log_type_sync', 'Sync');
    }

    return $logType;
};
?>
<div class="container-fluid pt-3">
  <div class="row">
    <div class="col-md-6">
      <?php $this->load->view('components/crud/page_heading', [
          'pageTitle' => (string) ($title ?? $t('list_page_title', 'Log history')),
          'pageIcon' => 'mdi mdi-database',
      ]); ?>
    </div>
  </div>
  <div class="card contentHeight">
    <div class="card-body">
      <div class="boxsrch">
        <div class="row">
          <div class="col-md-3">
            <div class="form-group">
              <label for="fromdate"><?= htmlspecialchars($t('label_from_date', 'From date'), ENT_QUOTES, 'UTF-8') ?></label>
              <?= fv_render_company_date_input('fromdate', date('Y-m-d'), [
                  'id' => 'fromdate',
                  'data-fv-max' => date('Y-m-d'),
              ]) ?>
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-group">
              <label for="todate"><?= htmlspecialchars($t('label_to_date', 'To date'), ENT_QUOTES, 'UTF-8') ?></label>
              <?= fv_render_company_date_input('todate', date('Y-m-d'), [
                  'id' => 'todate',
                  'data-fv-max' => date('Y-m-d'),
              ]) ?>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label for="log_type"><?= htmlspecialchars($t('label_log_type', 'Log type'), ENT_QUOTES, 'UTF-8') ?></label>
              <select class="form-control notranslate" id="log_type">
                <option value="all"><?= htmlspecialchars($t('log_type_all', 'All'), ENT_QUOTES, 'UTF-8') ?></option>
                <option value="sync"><?= htmlspecialchars($t('log_type_sync', 'Sync'), ENT_QUOTES, 'UTF-8') ?></option>
                <option value="login"><?= htmlspecialchars($t('log_type_login', 'Login'), ENT_QUOTES, 'UTF-8') ?></option>
              </select>
            </div>
          </div>
          <div class="col-md-2">
            <div class="form-group">
              <button type="button" id="btn-log-search" class="btn btn-primary mt-4" onclick="logSearch()">
                <i class="mdi mdi-magnify" aria-hidden="true"></i>
                <span class="btn-log-search-label"><?= htmlspecialchars($t('btn_search', 'Search'), ENT_QUOTES, 'UTF-8') ?></span>
              </button>
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
                  <th width="20">#</th>
                  <th><?= htmlspecialchars($t('col_datetime', 'Date/Time'), ENT_QUOTES, 'UTF-8') ?></th>
                  <th><?= htmlspecialchars($t('col_user', 'User'), ENT_QUOTES, 'UTF-8') ?></th>
                  <th><?= htmlspecialchars($t('col_log_type', 'Log type'), ENT_QUOTES, 'UTF-8') ?></th>
                  <th><?= htmlspecialchars($t('col_notes', 'Notes'), ENT_QUOTES, 'UTF-8') ?></th>
                </tr>
              </thead>
              <tbody class="filter_data">
                <?php if (empty($list)) : ?>
                  <tr class="text-center">
                    <td colspan="5"><?= htmlspecialchars($t('empty_no_data', 'No data available'), ENT_QUOTES, 'UTF-8') ?></td>
                  </tr>
                <?php else : ?>
                  <?php $i = 1; ?>
                  <?php foreach ($list as $v) : ?>
                    <?php $v = is_object($v) ? $v : (object) $v; ?>
                    <tr class="notranslate">
                      <td><?= $i ?></td>
                      <td><?= htmlspecialchars(!empty($v->created_on) ? fv_format_display_date((string) $v->created_on, fv_resolve_display_date_format() . ' H:i A') : '', ENT_QUOTES, 'UTF-8') ?></td>
                      <td><?= htmlspecialchars((string) ($v->seller_email ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                      <td><?= htmlspecialchars($labelLogType($v->log_type ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                      <td><?= htmlspecialchars((string) ($v->notes ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                    <?php $i++; ?>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
(function () {
  var i18n = {
    searching: <?= json_encode($t('btn_searching', 'Searching...'), JSON_UNESCAPED_UNICODE) ?>,
    search: <?= json_encode($t('btn_search', 'Search'), JSON_UNESCAPED_UNICODE) ?>,
    searchError: <?= json_encode($t('search_error', 'Search could not be completed.'), JSON_UNESCAPED_UNICODE) ?>,
    empty: <?= json_encode($t('empty_no_data', 'No data available'), JSON_UNESCAPED_UNICODE) ?>
  };

  function setSearchBusy(busy) {
    var btn = document.getElementById('btn-log-search');
    if (!btn) {
      return;
    }
    btn.disabled = !!busy;
    var label = btn.querySelector('.btn-log-search-label');
    if (label) {
      label.textContent = busy ? i18n.searching : i18n.search;
    }
  }

  function logSearch() {
    if (typeof jQuery === 'undefined') {
      return;
    }
    var $fromIso = jQuery('#fromdate_iso');
    var $toIso = jQuery('#todate_iso');
    if (!$fromIso.length) { $fromIso = jQuery('#fromdate'); }
    if (!$toIso.length) { $toIso = jQuery('#todate'); }
    var fromdate = String($fromIso.val() || '').trim();
    var todate = String($toIso.val() || '').trim();

    if (fromdate && todate && fromdate > todate) {
      var tmp = fromdate;
      fromdate = todate;
      todate = tmp;
      $fromIso.val(fromdate);
      $toIso.val(todate);
      if (typeof jQuery.fn.datepicker === 'function') {
        var $fromDisp = jQuery('#fromdate');
        var $toDisp = jQuery('#todate');
        if ($fromDisp.hasClass('fv-company-datepicker')) {
          $fromDisp.datepicker('setDate', fromdate);
        }
        if ($toDisp.hasClass('fv-company-datepicker')) {
          $toDisp.datepicker('setDate', todate);
        }
      }
    }

    var base = (typeof companyUrl !== 'undefined' && companyUrl) ? companyUrl : '/company/';
    setSearchBusy(true);
    jQuery.ajax({
      url: base + 'logs/filterSearch',
      type: 'POST',
      dataType: 'json',
      data: {
        fromdate: fromdate,
        todate: todate,
        logtype: jQuery('#log_type').val()
      },
      success: function (response) {
        if (response && response.log_list) {
          jQuery('.filter_data').html(response.log_list);
        } else {
          jQuery('.filter_data').html(
            '<tr class="text-center"><td colspan="5">' + jQuery('<div>').text(i18n.empty).html() + '</td></tr>'
          );
        }
      },
      error: function () {
        jQuery('.filter_data').html(
          '<tr class="text-center"><td colspan="5">' + jQuery('<div>').text(i18n.searchError).html() + '</td></tr>'
        );
      },
      complete: function () {
        setSearchBusy(false);
      }
    });
  }
  window.logSearch = logSearch;

  function syncDateLimits() {
    var fromEl = document.getElementById('fromdate');
    var toEl = document.getElementById('todate');
    if (!fromEl || !toEl) {
      return;
    }
    fromEl.addEventListener('change', function () {
      if (fromEl.value) {
        toEl.min = fromEl.value;
      } else {
        toEl.removeAttribute('min');
      }
      if (fromEl.value && toEl.value && toEl.value < fromEl.value) {
        toEl.value = fromEl.value;
      }
    });
    toEl.addEventListener('change', function () {
      if (toEl.value) {
        fromEl.max = toEl.value;
      } else {
        fromEl.max = '<?= date('Y-m-d') ?>';
      }
      if (fromEl.value && toEl.value && fromEl.value > toEl.value) {
        fromEl.value = toEl.value;
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', syncDateLimits);
  } else {
    syncDateLimits();
  }
})();
</script>
