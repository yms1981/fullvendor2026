<?php
$dl = isset($dashboardLang) && is_array($dashboardLang) ? $dashboardLang : [];
$dt = function (string $key, string $fallback) use ($dl): string {
    $v = trim((string) ($dl[$key] ?? ''));

    return $v !== '' ? $v : $fallback;
};
?>
           <div class="container-fluid pb-0 pt-3">
            <div class="page-header">
              <h3 class="page-title"><?= htmlspecialchars($dt('dashboard_page_title', 'Dashboard'), ENT_QUOTES, 'UTF-8') ?></h3>
            </div>
            <div class="row">
              <div class="col-xl-8 stretch-card grid-margin">
                <div class="card">
                  <div class="card-body">
                    <div class="d-flex justify-content-between flex-wrap">
                      <div>
                        <div class="card-title mb-0"><?= htmlspecialchars($dt('dashboard_sales_revenue', 'Sales revenue'), ENT_QUOTES, 'UTF-8') ?></div>
                        <h3 class="font-weight-bold mb-0">$<?= number_format($totsalerevenue)?></h3>
                      </div>
                      <div>
                        <div class="d-flex flex-wrap pt-2 justify-content-between sales-header-right">
                          <div class="d-flex mr-5">
                            <button type="button" class="btn btn-social-icon btn-outline-sales"><i class="mdi mdi-inbox-arrow-down"></i></button>
                            <div class="pl-2">
                              <h4 class="mb-0 font-weight-semibold head-count"><?=number_format($totalsalesno)?></h4>
                              <span class="font-10 font-weight-semibold text-muted"><?= htmlspecialchars(strtoupper($dt('dashboard_total_sales', 'Total sales')), ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    <p class="text-muted font-13 mt-2 mt-sm-0"><?= htmlspecialchars($dt('dashboard_sales_monitoring', 'Your sales monitoring dashboard.'), ENT_QUOTES, 'UTF-8') ?></p>
                    <div class="flot-chart-wrapper">
                       <canvas id="myChart"></canvas>
                     
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-xl-4 grid-margin">
                <div class="card stretch-card mb-3">
                  <div class="card-body d-flex flex-wrap justify-content-between">
                    <div>
                      <h4 class="font-weight-semibold mb-1 text-black"><?= htmlspecialchars($dt('dashboard_revenue', 'Revenue'), ENT_QUOTES, 'UTF-8') ?></h4>
                      <h6 class="text-muted"><?= htmlspecialchars($dt('dashboard_avg_monthly_revenue', 'Average monthly revenue'), ENT_QUOTES, 'UTF-8') ?></h6>
                    </div>
                    <h3 class="text-success font-weight-bold">+<?= number_format($avgvaluepermonth)?></h3>
                  </div>
                </div>
              </div>
            </div>
          </div>

