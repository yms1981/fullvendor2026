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
$urlHome = (string) ($url_catalog_home ?? site_url('sellers/catalog'));
$urlProducts = (string) ($url_catalog_products ?? site_url('sellers/catalog/products'));
$urlCart = (string) ($url_catalog_cart ?? site_url('sellers/catalog/cart'));
$urlCheckout = (string) ($url_catalog_checkout ?? site_url('sellers/catalog/checkout'));
$postCart = site_url('sellers/catalog/cart');
?>
        <section class="portal-welcome-card" aria-labelledby="catalog-cart-heading">
          <div class="catalog-toolbar">
            <div>
              <h2 id="catalog-cart-heading"><?= $e($t['catalog_cart_title'] ?? '') ?></h2>
              <p class="portal-orders-sub mb-0"><?= $e($t['catalog_cart_intro'] ?? '') ?></p>
              <?php if ($customerLabel !== '') { ?>
                <p class="portal-orders-sub mt-2 mb-0"><strong style="color:#5c0f18;"><?= $e($t['catalog_selected_customer_prefix'] ?? '') ?></strong> <?= $e($customerLabel) ?></p>
              <?php } ?>
            </div>
            <div class="d-flex flex-wrap gap-2">
              <a class="btn-portal-outline" href="<?= $e($urlProducts) ?>"><?= $e($t['catalog_cart_continue_shopping'] ?? '') ?></a>
            </div>
          </div>
          <?php if ($cartFlashKey !== '') {
              $rawTpl = (string) ($t[$cartFlashKey] ?? $cartFlashKey);
              $cartMsg = ($cartFlashKey === 'catalog_moq_invalid' && $cartFlashMin !== '') ? sprintf($rawTpl, $cartFlashMin) : $rawTpl;
              ?>
            <div class="catalog-flash catalog-flash--error" role="alert"><?= $e($cartMsg) ?></div>
          <?php } ?>
          <?php if ($lines === []) { ?>
            <p class="catalog-empty mb-0" role="status"><?= $e($t['catalog_cart_empty'] ?? '') ?></p>
            <p class="mb-0 mt-3"><a class="btn-portal-primary" href="<?= $e($urlProducts) ?>"><?= $e($t['catalog_cart_continue_shopping'] ?? '') ?></a></p>
          <?php } else { ?>
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
                      <th scope="col" class="col-actions"><?= $e($t['column_actions'] ?? '') ?></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($lines as $line) {
                        if (! is_array($line)) {
                            continue;
                        }
                        $pid = (int) ($line['pro_id'] ?? 0);
                        $qty = (float) ($line['qty'] ?? 0);
                        $price = (float) ($line['price'] ?? 0);
                        $lineTot = $qty * $price;
                        $pf = number_format($price, max(0, $moneyDigits), $moneyRadix, $moneyGroup);
                        $lf = number_format($lineTot, max(0, $moneyDigits), $moneyRadix, $moneyGroup);
                        $lineMin = (float) ($line['minimum_stock'] ?? 0);
                        $lineMoq = ! empty($line['notify_minimum_stock']) && $lineMin > 0;
                        $stepAttr = $lineMoq ? ' step="' . $e(rtrim(rtrim(sprintf('%.8F', $lineMin), '0'), '.')) . '" min="' . $e(rtrim(rtrim(sprintf('%.8F', $lineMin), '0'), '.')) . '"' : ' step="any" min="0"';
                        ?>
                      <tr>
                        <td><?= $e((string) ($line['name'] ?? '')) ?></td>
                        <td><?= $e((string) ($line['sku'] ?? '')) ?></td>
                        <td class="col-total"><?= $e($moneySymbol) ?> <?= $e($pf) ?></td>
                        <td>
                          <form method="post" action="<?= $e($postCart) ?>" class="d-inline-flex align-items-center gap-1 flex-wrap">
                            <input type="hidden" name="cart_action" value="update">
                            <input type="hidden" name="pro_id" value="<?= (string) $pid ?>">
                            <input class="catalog-qty-input" name="qty" type="number"<?= $stepAttr ?> value="<?= $e(rtrim(rtrim(sprintf('%.4F', $qty), '0'), '.')) ?>">
                            <button type="submit" class="btn-portal-outline" style="padding:6px 10px;font-size:0.8125rem;"><?= $e($t['catalog_cart_update'] ?? '') ?></button>
                          </form>
                        </td>
                        <td class="col-total"><?= $e($moneySymbol) ?> <?= $e($lf) ?></td>
                        <td class="col-actions">
                          <form method="post" action="<?= $e($postCart) ?>" onsubmit="return confirm('<?= $e($t['catalog_cart_remove_confirm'] ?? '') ?>');">
                            <input type="hidden" name="cart_action" value="remove">
                            <input type="hidden" name="pro_id" value="<?= (string) $pid ?>">
                            <button type="submit" class="btn-portal-outline" style="padding:6px 10px;font-size:0.8125rem;"><?= $e($t['catalog_cart_remove'] ?? '') ?></button>
                          </form>
                        </td>
                      </tr>
                    <?php } ?>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 gap-3">
              <p class="mb-0" style="font-size:1rem;font-weight:600;color:#5c0f18;">
                <?= $e($t['catalog_cart_grand_total'] ?? '') ?>: <?= $e($moneySymbol) ?> <?= $e($totalFmt) ?>
              </p>
              <div class="catalog-checkout-actions mb-0">
                <form method="post" action="<?= $e($postCart) ?>" class="d-inline" onsubmit="return confirm('<?= $e($t['catalog_cart_clear_confirm'] ?? '') ?>');">
                  <input type="hidden" name="cart_action" value="clear">
                  <button type="submit" class="btn-portal-outline"><?= $e($t['catalog_cart_clear'] ?? '') ?></button>
                </form>
                <a class="btn-portal-primary" href="<?= $e($urlCheckout) ?>"><?= $e($t['catalog_cart_checkout'] ?? '') ?></a>
              </div>
            </div>
          <?php } ?>
        </section>
<?php $this->load->view('seller/partials/portal_foot'); ?>
