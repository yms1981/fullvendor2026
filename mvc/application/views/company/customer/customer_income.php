<?php
/**
 * @var $pageTexts array the translated string conglomerated for this view
 */
?>
<div class="container-fluid page-body-wrapper pt-3">
    <div class="main-panel">
        <div class="container-fluid">
            <!-- Header -->
            <div class="mb-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h3 class="page-title"><?= $pageTexts['title'] ?></h3>
                    </div>
                    <div class="col-md-4 text-right">
                        <a class="btn btn-danger" href="<?= company_url('customers') ?>">
                            <?= htmlspecialchars($pageTexts['back'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Card -->
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <!-- Customer info -->
                        <div class="col-md-6">
                            <?php
                            $customerData = isset($customerData) && is_array($customerData) ? $customerData : [];
                            $emptyLabel = $pageTexts['income_no_data'] ?? '';
                            $statusRaw = isset($customerData['customer_status']) ? trim((string) $customerData['customer_status']) : '';
                            $statusLabel = $statusRaw === '1'
                                ? ($pageTexts['income_status_enabled'] ?? '')
                                : ($statusRaw === '0' ? ($pageTexts['income_status_disabled'] ?? '') : '');
                            $customerInfoItems = [
                                ['label' => $pageTexts['income_customer_id'] ?? '', 'value' => $customerData['customer_id'] ?? ''],
                                ['label' => $pageTexts['income_erp_id'] ?? '', 'value' => $customerData['cust_id_kor'] ?? ''],
                                ['label' => $pageTexts['income_business_name'] ?? '', 'value' => $customerData['business_name'] ?? ''],
                                ['label' => $pageTexts['income_contact'] ?? '', 'value' => $customerData['contact'] ?? ''],
                                ['label' => $pageTexts['income_email'] ?? '', 'value' => $customerData['email'] ?? ''],
                                ['label' => $pageTexts['income_phone'] ?? '', 'value' => $customerData['phone'] ?? ''],
                                ['label' => $pageTexts['income_cell_phone'] ?? '', 'value' => $customerData['cell_phone'] ?? ''],
                                ['label' => $pageTexts['income_group'] ?? '', 'value' => $customerData['group_name'] ?? ''],
                                ['label' => $pageTexts['income_sellers'] ?? '', 'value' => isset($customerData['sellers']) && is_array($customerData['sellers']) ? implode(', ', $customerData['sellers']) : ''],
                                ['label' => $pageTexts['income_customer_status'] ?? '', 'value' => $statusLabel],
                            ];
                            ?>
                            <div class="card border-light h-100">
                                <div class="card-body pb-2">
                                    <h5 class="mb-3"><?= htmlspecialchars($pageTexts['income_customer_info'] ?? '', ENT_QUOTES, 'UTF-8') ?></h5>
                                    <div class="row">
                                        <?php foreach ($customerInfoItems as $infoItem): ?>
                                            <div class="col-md-6 mb-2">
                                                <small class="text-muted d-block"><?= htmlspecialchars($infoItem['label'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
                                                <strong><?= htmlspecialchars((string) (($infoItem['value'] ?? '') !== '' ? $infoItem['value'] : $emptyLabel), ENT_QUOTES, 'UTF-8') ?></strong>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="col-md-6">
                            <table class="table table-striped">
                                <thead>
                                <tr>
                                    <th><?= htmlspecialchars($pageTexts['income_month'] ?? '', ENT_QUOTES, 'UTF-8') ?></th>
                                    <th><?= htmlspecialchars($pageTexts['income_value'] ?? '', ENT_QUOTES, 'UTF-8') ?></th>
                                    <th><?= htmlspecialchars($pageTexts['income_quantity'] ?? '', ENT_QUOTES, 'UTF-8') ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                $monthss = []; // inicializo array
                                $totalordersval = isset($totalordersval) && is_array($totalordersval) ? $totalordersval : [];

                                // últimos 6 meses
                                for ($j = 0; $j < 6; $j++) {
                                    $monthss[] = date('m-Y', strtotime("-$j month"));
                                }
                                sort($monthss);

                                foreach ($monthss as $monthsortt) {
                                    $strrplcmnth = str_replace("-", "/", $monthsortt);

                                    $totordvalue = 0;
                                    $totordcount = 0;

                                    foreach ($totalordersval as $qtyyvalue) {
                                        $totordcount += $qtyyvalue->qty;
                                        $totordvalue += $qtyyvalue->qty * $qtyyvalue->sale_price;
                                    }

                                    $tottordqty  = $totordcount;
                                    $totordprice = round($totordvalue);
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($strrplcmnth) ?></td>
                                        <td>$<?= number_format($totordprice) ?></td>
                                        <td><?= number_format($tottordqty) ?></td>
                                    </tr>
                                <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Blocks -->
                    <div class="row blockBox">
                        <div class="col-md-3 mt-4">
                            <div class="blueblock text-center p-3 rounded">
                                <h3><?= htmlspecialchars($pageTexts['income_total_sales'] ?? '', ENT_QUOTES, 'UTF-8') ?></h3>
                                <h2><?= number_format($totorderquantity) ?></h2>
                            </div>
                        </div>
                        <div class="col-md-3 mt-4">
                            <div class="blueblock bg2 text-center p-3 rounded">
                                <h3><?= htmlspecialchars($pageTexts['income_total_average_sale'] ?? '', ENT_QUOTES, 'UTF-8') ?></h3>
                                <h2>$<?= number_format($avgsaleprice) ?></h2>
                            </div>
                        </div>
                        <div class="col-md-3 mt-4">
                            <div class="blueblock text-center p-3 rounded">
                                <h3><?= htmlspecialchars($pageTexts['income_total_average_month'] ?? '', ENT_QUOTES, 'UTF-8') ?></h3>
                                <h2>$<?= number_format($avgvaluepermonth) ?></h2>
                            </div>
                        </div>
                        <div class="col-md-3 mt-4">
                            <div class="blueblock bg2 text-center p-3 rounded">
                                <h3><?= htmlspecialchars($pageTexts['income_total_amount'] ?? '', ENT_QUOTES, 'UTF-8') ?></h3>
                                <h2>$<?= number_format($totorderpricevalue) ?></h2>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
