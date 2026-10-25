<?php
defined('BASEPATH') or exit('No direct script access allowed');

$this->load->view('seller/partials/portal_head', [
    'pageTexts' => $pageTexts ?? [],
    'settings' => $settings ?? null,
    'url_home' => $url_home ?? site_url('sellers/customer'),
    'url_orders' => $url_orders ?? site_url('sellers/orders'),
    'url_catalog' => $url_catalog ?? site_url('sellers/catalog'),
    'url_logout' => $url_logout ?? site_url('sellers/logout'),
    'portal_company_name' => $portal_company_name ?? '',
    'html_lang' => $html_lang ?? 'en',
    'active_portal_tab' => $active_portal_tab ?? 'catalog',
    'portal_page_title' => $portal_page_title ?? '',
]);

$t = is_array($pageTexts ?? null) ? $pageTexts : [];
$e = static function (?string $s): string {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
};
$money = is_array($orders_money ?? null) ? $orders_money : [];
$moneySymbol = (string) ($money['symbol'] ?? '$');
$moneyDigits = isset($money['digits']) ? (int) $money['digits'] : 2;
$moneyRadix = (string) ($money['radixPoint'] ?? '.');
$moneyGroup = (string) ($money['groupSeparator'] ?? ',');
$lines = is_array($catalog_cart_lines ?? null) ? $catalog_cart_lines : [];
$total = isset($catalog_cart_total) ? (float) $catalog_cart_total : 0.0;
$totalFmt = number_format($total, max(0, $moneyDigits), $moneyRadix, $moneyGroup);
$customerLabel = (string) ($catalog_customer_label ?? '');
$errKey = isset($catalog_checkout_error) && is_string($catalog_checkout_error) ? $catalog_checkout_error : '';
$errMin = isset($catalog_checkout_error_min) && is_string($catalog_checkout_error_min) ? $catalog_checkout_error_min : '';
$urlHome = (string) ($url_catalog_home ?? site_url('sellers/catalog'));
$urlProducts = (string) ($url_catalog_products ?? site_url('sellers/catalog/products'));
$urlCart = (string) ($url_catalog_cart ?? site_url('sellers/catalog/cart'));
$urlCheckout = (string) ($url_catalog_checkout ?? site_url('sellers/catalog/checkout'));
$postCheckout = site_url('sellers/catalog/checkout');
?>
        <section class="portal-welcome-card" aria-labelledby="catalog-checkout-heading">
          <div class="catalog-toolbar">
            <div>
              <h2 id="catalog-checkout-heading"><?= $e($t['catalog_checkout_title'] ?? '') ?></h2>
              <p class="portal-orders-sub mb-0"><?= $e($t['catalog_checkout_intro'] ?? '') ?></p>
              <?php if ($customerLabel !== '') { ?>
                <p class="portal-orders-sub mt-2 mb-0"><strong style="color:#5c0f18;"><?= $e($t['catalog_selected_customer_prefix'] ?? '') ?></strong> <?= $e($customerLabel) ?></p>
              <?php } ?>
            </div>
          </div>
          <?php if ($errKey !== '') {
              $rawErr = (string) ($t[$errKey] ?? $errKey);
              $errMsg = ($errKey === 'catalog_moq_invalid' && $errMin !== '') ? sprintf($rawErr, $errMin) : $rawErr;
              ?>
            <div class="catalog-flash catalog-flash--error" role="alert"><?= $e($errMsg) ?></div>
          <?php } ?>
          <div class="portal-table-shell">
            <div class="portal-table-responsive">
              <table class="portal-orders-table">
                <thead>
                  <tr>
                    <th scope="col"><?= $e($t['column_line_product'] ?? '') ?></th>
                    <th scope="col"><?= $e($t['column_sku'] ?? '') ?></th>
                    <th scope="col" class="col-total"><?= $e($t['column_unit_price'] ?? '') ?></th>
                    <th scope="col"><?= $e($t['column_qty'] ?? '') ?></th>
                    <th scope="col" class="col-total"><?= $e($t['catalog_cart_line_total'] ?? '') ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($lines as $line) {
                      if (! is_array($line)) {
                          continue;
                      }
                      $qty = (float) ($line['qty'] ?? 0);
                      $price = (float) ($line['price'] ?? 0);
                      $lineTot = $qty * $price;
                      $pf = number_format($price, max(0, $moneyDigits), $moneyRadix, $moneyGroup);
                      $lf = number_format($lineTot, max(0, $moneyDigits), $moneyRadix, $moneyGroup);
                      ?>
                    <tr>
                      <td><?= $e((string) ($line['name'] ?? '')) ?></td>
                      <td><?= $e((string) ($line['sku'] ?? '')) ?></td>
                      <td class="col-total"><?= $e($moneySymbol) ?> <?= $e($pf) ?></td>
                      <td><?= $e(rtrim(rtrim(sprintf('%.4F', $qty), '0'), '.')) ?></td>
                      <td class="col-total"><?= $e($moneySymbol) ?> <?= $e($lf) ?></td>
                    </tr>
                  <?php } ?>
                </tbody>
              </table>
            </div>
          </div>
          <p class="mt-3 mb-0" style="font-size:1.05rem;font-weight:600;color:#5c0f18;">
            <?= $e($t['catalog_cart_grand_total'] ?? '') ?>: <?= $e($moneySymbol) ?> <?= $e($totalFmt) ?>
          </p>
          <div class="catalog-checkout-actions">
            <a class="btn-portal-outline" href="<?= $e($urlCart) ?>"><?= $e($t['catalog_checkout_back_cart'] ?? '') ?></a>
            <form method="post" action="<?= $e($postCheckout) ?>" class="d-inline">
              <button type="submit" class="btn-portal-primary"><?= $e($t['catalog_checkout_submit'] ?? '') ?></button>
            </form>
          </div>
        </section>
<?php $this->load->view('seller/partials/portal_foot'); ?>
