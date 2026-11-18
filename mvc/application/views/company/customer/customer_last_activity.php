<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$pageTexts = is_array($pageTexts ?? null) ? $pageTexts : [];
$t = static function (string $key, string $fallback = '') use ($pageTexts): string {
    $value = $pageTexts[$key] ?? $fallback;
    return is_string($value) ? $value : $fallback;
};

$catalogs = is_array($customerLastCatalogs ?? null) ? $customerLastCatalogs : [];
$orders = is_array($customerLastOrders ?? null) ? $customerLastOrders : [];
$currency = (string) ($customerLastOrdersCurrency ?? '');
$companyId = (int) ($company->company_id ?? 0);
$apiToken = (string) ($apiToken ?? '');
$apiBaseUrl = rtrim((string) ($apiBaseUrl ?? '/api'), '/');
?>
<div class="card fv-customer-last-activity mt-3"
     data-company-id="<?= $companyId ?>"
     data-api-base="<?= htmlspecialchars($apiBaseUrl, ENT_QUOTES, 'UTF-8') ?>"
     data-api-token="<?= htmlspecialchars($apiToken, ENT_QUOTES, 'UTF-8') ?>"
     data-msg-copied="<?= htmlspecialchars($t('link_copied', 'Link copied'), ENT_QUOTES, 'UTF-8') ?>"
     data-msg-expire-confirm="<?= htmlspecialchars($t('confirm_expire_link', 'Are you sure?'), ENT_QUOTES, 'UTF-8') ?>"
     data-msg-accept="<?= htmlspecialchars($t('btn_accept', $t('aceptar', 'OK')), ENT_QUOTES, 'UTF-8') ?>"
     data-msg-cancel="<?= htmlspecialchars($t('cancelar', 'Cancel'), ENT_QUOTES, 'UTF-8') ?>"
     data-msg-error="<?= htmlspecialchars($t('error_occurred', 'Error'), ENT_QUOTES, 'UTF-8') ?>">
    <div class="card-body">
        <div class="row">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <h4 class="text-uppercase text-success mb-3"><?= htmlspecialchars($t('ultimos_catalogos_enviados', 'Last catalog submissions')) ?></h4>
                <div class="table-responsive fv-customer-last-activity__table">
                    <table class="table mb-0">
                        <thead>
                        <tr>
                            <th style="width:4%">#</th>
                            <th><?= htmlspecialchars($t('send_date', 'Send date')) ?></th>
                            <th style="width:5%"></th>
                            <th><?= htmlspecialchars($t('send_expiration', 'Expiration')) ?></th>
                            <th><?= htmlspecialchars($t('catalog_name', 'Catalog name')) ?></th>
                            <th style="width:8%"></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($catalogs === []) : ?>
                            <tr>
                                <td colspan="6" class="text-muted text-center py-3"><?= htmlspecialchars($t('no_recent_catalogs', ''), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($catalogs as $idx => $vc) :
                                $vc = is_object($vc) ? $vc : (object) $vc;
                                $cartId = (int) ($vc->cart_id ?? 0);
                                $token = (string) ($vc->token_kor ?? '');
                                $urlCatalog = $token !== '' && function_exists('catalog_public_browse_url')
                                    ? catalog_public_browse_url($token)
                                    : '';
                                $expiredTs = !empty($vc->expired_date) ? strtotime((string) $vc->expired_date) : false;
                                $isExpired = $expiredTs !== false && time() > $expiredTs;
                                $viewed = (int) ($vc->view ?? 0) === 1;
                                ?>
                                <tr class="notranslate">
                                    <td><?= (int) $idx + 1 ?></td>
                                    <td><?= htmlspecialchars(function_exists('fv_format_display_datetime') ? fv_format_display_datetime($vc->created ?? null) : (string) ($vc->created ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="<?= $viewed ? 'text-success' : 'text-warning' ?>">
                                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars(function_exists('fv_format_display_datetime') ? fv_format_display_datetime($vc->expired_date ?? null) : (string) ($vc->expired_date ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                        <?php if ($isExpired) : ?>
                                            <span class="badge badge-pill badge-danger ml-1"><?= htmlspecialchars($t('expirado', 'Expired')) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($urlCatalog !== '') : ?>
                                            <a target="_blank" rel="noopener" href="<?= htmlspecialchars($urlCatalog, ENT_QUOTES, 'UTF-8') ?>">
                                                <?php if ((int) ($vc->default_catalog ?? 0) === 1) : ?>
                                                    <img src="<?= base_url('images/logos/logo.png') ?>" class="thumbnail rounded" alt="" style="display:inline-block;width:22px;height:22px;">
                                                <?php endif; ?>
                                                <?= htmlspecialchars((string) ($vc->catalog_name ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                            </a>
                                        <?php else : ?>
                                            <?= htmlspecialchars((string) ($vc->catalog_name ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-right fv-customer-last-activity__actions">
                                        <?php if (!$isExpired && $cartId > 0 && $urlCatalog !== '') : ?>
                                            <div class="fv-catalog-row-actions">
                                                <button type="button"
                                                        class="btn btn-secondary btn-sm js-fv-catalog-actions-toggle"
                                                        aria-haspopup="true"
                                                        aria-expanded="false"
                                                        title="<?= htmlspecialchars($t('acciones', 'Actions'), ENT_QUOTES, 'UTF-8') ?>">
                                                    <i class="fa fa-caret-down" aria-hidden="true"></i>
                                                </button>
                                                <div class="fv-catalog-row-actions__menu" hidden>
                                                    <button type="button" class="fv-catalog-row-actions__item js-fv-copy-catalog-link"
                                                            data-link="<?= htmlspecialchars($urlCatalog, ENT_QUOTES, 'UTF-8') ?>">
                                                        <?= htmlspecialchars($t('copy_link', 'Copy Link')) ?>
                                                    </button>
                                                    <button type="button" class="fv-catalog-row-actions__item js-fv-show-catalog-link"
                                                            data-link="<?= htmlspecialchars($urlCatalog, ENT_QUOTES, 'UTF-8') ?>">
                                                        <?= htmlspecialchars($t('shown_link', 'Shown Link')) ?>
                                                    </button>
                                                    <button type="button" class="fv-catalog-row-actions__item js-fv-expire-catalog-link"
                                                            data-cart-id="<?= $cartId ?>">
                                                        <?= htmlspecialchars($t('do_expire', 'Do Expire')) ?>
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-lg-6">
                <h4 class="text-uppercase text-success mb-3"><?= htmlspecialchars($t('ultimas_ordenes', 'Last orders')) ?></h4>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                        <tr>
                            <th>Id</th>
                            <th><?= htmlspecialchars($t('seller', 'Seller')) ?></th>
                            <th><?= htmlspecialchars($t('orden_date', 'Order date')) ?></th>
                            <th><?= htmlspecialchars($t('total_amount', 'Total')) ?></th>
                            <th><?= htmlspecialchars($t('order_status', $t('estado_orden', 'Order status'))) ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($orders === []) : ?>
                            <tr>
                                <td colspan="5" class="text-muted text-center py-3"><?= htmlspecialchars($t('no_recent_orders', ''), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($orders as $ord) :
                                $ord = is_object($ord) ? $ord : (object) $ord;
                                $orderId = (int) ($ord->order_id ?? 0);
                                $origin = '';
                                switch ((string) ($ord->source ?? '')) {
                                    case 'Catalog':
                                        $origin = '<span class="fa-solid fa-globe text-success"></span>';
                                        break;
                                    case 'app':
                                        $origin = '<span class="fa-solid fa-mobile text-success"></span>';
                                        break;
                                    case 'manual':
                                        $origin = '<span class="fa-solid fa-house text-success"></span>';
                                        break;
                                }
                                $total = is_numeric($ord->total_display ?? null) ? (float) $ord->total_display : 0;
                                ?>
                                <tr>
                                    <td class="text-nowrap">
                                        <a href="<?= htmlspecialchars(company_url('orders/view/' . $orderId), ENT_QUOTES, 'UTF-8') ?>">
                                            <?= $origin ?> <?= htmlspecialchars((string) ($ord->order_number ?? ('#'.$orderId)), ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars((string) ($ord->vendedor ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-nowrap"><?= htmlspecialchars(function_exists('fv_format_display_datetime') ? fv_format_display_datetime($ord->created ?? null) : (string) ($ord->created ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($currency . number_format($total, 2, '.', ','), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <span class="bg-secondary p-2 rounded text-white d-inline-flex align-items-center">
                                            <span class="roundblock notranslate" style="background:<?= htmlspecialchars((string) ($ord->status_color ?? '#94a3b8'), ENT_QUOTES, 'UTF-8') ?>;"></span>
                                            &nbsp;<?= htmlspecialchars((string) ($ord->status_label ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="fv-customer-showlink-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= htmlspecialchars($t('shown_link', 'Shown Link')) ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="<?= htmlspecialchars($t('cerrar', 'Close'), ENT_QUOTES, 'UTF-8') ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p id="fv-customer-showlink-text" class="mb-0 text-break"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= htmlspecialchars($t('cerrar', $t('cancelar', 'Close')), ENT_QUOTES, 'UTF-8') ?></button>
            </div>
        </div>
    </div>
</div>
<?php /* last-activity.js se carga vía Edit.php addJsFile (footer, tras jQuery/Bootstrap) */ ?>
