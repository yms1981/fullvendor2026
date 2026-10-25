<?php
/**
 * Panel de cliente + metadatos (selector + card).
 *
 * @var array $pageTexts
 * @var array $orderFormConfig
 * @var array $sellers
 * @var array|null $customers
 * @var object|null $seller
 * @var string $mode add|edit|view
 * @var object|null $order
 */
$pageTexts = is_array($pageTexts ?? null) ? $pageTexts : [];
$orderFormConfig = is_array($orderFormConfig ?? null) ? $orderFormConfig : [];
$mode = ($mode ?? 'add');
if (!in_array($mode, ['add', 'edit', 'view'], true)) {
    $mode = 'add';
}
$initialCustomer = $orderFormConfig['initialCustomer'] ?? [];
$initialCustomerId = (int) ($orderFormConfig['initialCustomerId'] ?? ($initialCustomer['customer_id'] ?? 0));
$seller = $seller ?? null;
$order = $order ?? null;

$discountValue = ($mode === 'edit' || $mode === 'view') && $order ? ($order->discount ?? 0) : ($initialCustomer['discount'] ?? 0);
$discountType = ($mode === 'edit' || $mode === 'view') && $order ? (string) ($order->discount_type ?? '1') : '1';
$selectedSellerId = 0;
if ($mode === 'view' && $seller) {
    $selectedSellerId = (int) ($seller->id ?? 0);
} elseif ($seller) {
    $selectedSellerId = (int) ($seller->id ?? 0);
} elseif ($order && !empty($order->seller_id)) {
    $selectedSellerId = (int) $order->seller_id;
} else {
    $selectedSellerId = (int) ($initialCustomer['seller_id'] ?? 0);
}
$sellerName = '';
if ($mode === 'view' && $seller) {
    $sellerName = trim((string) ($seller->user->name ?? $seller->name ?? ''));
}
$groupPrice = (string) ($initialCustomer['group_price'] ?? '-');
$customerEmail = (string) ($initialCustomer['email'] ?? '');

$countryMap = $orderFormConfig['countryDataMap'] ?? [];

$fvFormatPhone = static function ($raw, array $countryMap, array $cus): string {
    $phone = trim((string) $raw);
    if ($phone === '') {
        return '';
    }
    $countryName = trim((string) ($cus['commercial_country'] ?? $cus['dispatch_country'] ?? ''));
    $data = ($countryName !== '' && isset($countryMap[$countryName])) ? $countryMap[$countryName] : null;
    if (!$data || empty($data['phonemask'])) {
        return $phone;
    }
    $digits = preg_replace('/\D/', '', $phone);
    $mask = (string) $data['phonemask'];
    $result = '';
    $di = 0;
    $len = strlen($mask);
    for ($i = 0; $i < $len && $di < strlen($digits); $i++) {
        if ($mask[$i] === '#') {
            $result .= $digits[$di++];
        } else {
            $result .= $mask[$i];
        }
    }
    if ($di < strlen($digits)) {
        $result .= substr($digits, $di);
    }
    if (!empty($data['phonecode'])) {
        return '+' . $data['phonecode'] . ' ' . $result;
    }
    return $result;
};

$fvBuildAddress = static function (array $cus): string {
    $segments = [];
    $push = static function ($val) use (&$segments) {
        $t = trim((string) $val);
        if ($t !== '') {
            $segments[] = mb_strtoupper($t);
        }
    };
    foreach (['commercial_address', 'commercial_state', 'commercial_city', 'commercial_zone', 'commercial_zip_code', 'commercial_country'] as $k) {
        $push($cus[$k] ?? '');
    }
    if (!$segments) {
        foreach (['dispatch_address', 'dispatch_state', 'dispatch_city', 'dispatch_zone', 'dispatch_zip_code', 'dispatch_country'] as $k) {
            $push($cus[$k] ?? '');
        }
    }
    if (!$segments && !empty($cus['dispatch_delivery_address'])) {
        $push($cus['dispatch_delivery_address']);
    }
    return implode(', ', $segments);
};

$fvFlagUrl = static function (array $cus, array $countryMap): string {
    $countryName = trim((string) ($cus['commercial_country'] ?? $cus['dispatch_country'] ?? ''));
    if ($countryName === '' || !isset($countryMap[$countryName]['sortname'])) {
        return '';
    }
    return rtrim(base_url('images/flags/'), '/') . '/' . strtolower((string) $countryMap[$countryName]['sortname']) . '.png';
};

$fvInitials = static function (string $business, string $contact): string {
    $base = trim($business !== '' ? $business : $contact);
    if ($base === '') {
        return '?';
    }
    $parts = preg_split('/\s+/', $base) ?: [];
    if (count($parts) === 1) {
        return mb_strtoupper(mb_substr($parts[0], 0, 2));
    }
    return mb_strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1));
};

$renderCustomerCard = static function (?array $cus) use ($pageTexts, $countryMap, $fvFormatPhone, $fvBuildAddress, $fvFlagUrl, $fvInitials): string {
    if (!$cus || empty($cus['customer_id'])) {
        return '<div class="fv-order-customer-card fv-order-customer-card--empty">'
            . '<i class="mdi mdi-account-search-outline" aria-hidden="true"></i>'
            . '<span>' . htmlspecialchars((string) ($pageTexts['debe_elegir_cliente'] ?? ''), ENT_QUOTES, 'UTF-8') . '</span>'
            . '</div>';
    }

    $business = trim((string) ($cus['business_name'] ?? ''));
    $contact = trim((string) ($cus['name'] ?? ''));
    $title = $business !== '' ? $business : $contact;
    $flag = $fvFlagUrl($cus, $countryMap);
    $initials = $fvInitials($business, $contact);
    $email = trim((string) ($cus['email'] ?? ''));
    $phone = $fvFormatPhone($cus['phone'] ?? '', $countryMap, $cus);
    $cell = $fvFormatPhone($cus['cell_phone'] ?? '', $countryMap, $cus);
    $taxId = trim((string) ($cus['tax_id'] ?? ''));
    $address = $fvBuildAddress($cus);
    $group = trim((string) ($cus['group_price'] ?? ''));

    ob_start();
    ?>
    <div class="fv-order-customer-card">
        <div class="fv-order-customer-card__head">
            <div class="fv-order-customer-card__avatar">
                <?php if ($flag !== ''): ?>
                    <img src="<?= htmlspecialchars($flag, ENT_QUOTES, 'UTF-8') ?>" alt="" class="fv-order-customer-card__flag" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                <?php endif; ?>
                <span class="fv-order-customer-card__initials"<?= $flag !== '' ? ' style="display:none"' : '' ?>><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="fv-order-customer-card__title-wrap">
                <h4 class="fv-order-customer-card__title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h4>
                <?php if ($contact !== '' && $contact !== $business): ?>
                    <p class="fv-order-customer-card__subtitle"><i class="mdi mdi-account-outline"></i> <?= htmlspecialchars($contact, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
            </div>
        </div>
        <div class="fv-order-customer-card__body">
            <?php if ($address !== ''): ?>
                <div class="fv-order-customer-card__item fv-order-customer-card__item--address">
                    <i class="mdi mdi-map-marker-outline"></i>
                    <span><?= htmlspecialchars($address, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            <?php endif; ?>
            <?php if ($phone !== '' || ($cell !== '' && $cell !== $phone)): ?>
                <div class="fv-order-customer-card__phones">
                    <?php if ($phone !== ''): ?>
                        <div class="fv-order-customer-card__item">
                            <i class="mdi mdi-phone-outline"></i>
                            <span><?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($cell !== '' && $cell !== $phone): ?>
                        <div class="fv-order-customer-card__item">
                            <i class="mdi mdi-cellphone"></i>
                            <span><?= htmlspecialchars($cell, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <?php if ($email !== ''): ?>
                <div class="fv-order-customer-card__item fv-order-customer-card__item--email">
                    <i class="fa-solid fa-envelope" aria-hidden="true"></i>
                    <span><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            <?php endif; ?>
            <?php if ($taxId !== '' || ($group !== '' && $group !== '-')): ?>
                <div class="fv-order-customer-card__meta">
                    <?php if ($taxId !== ''): ?>
                        <div class="fv-order-customer-card__item">
                            <i class="mdi mdi-card-account-details-outline"></i>
                            <span><?= htmlspecialchars($taxId, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($group !== '' && $group !== '-'): ?>
                        <div class="fv-order-customer-card__item">
                            <i class="mdi mdi-account-group-outline"></i>
                            <span><?= htmlspecialchars($group, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
};
?>
<link rel="stylesheet" href="<?= dated_file_url('companyasset/css/fv-order-form.css') ?>" />

<section class="fv-order-sheet">
    <div class="fv-order-sheet__row fv-order-sheet__row--top">
        <div class="fv-order-sheet__block fv-order-sheet__block--customer">
            <div class="fv-order-sheet__customer-header">
                <label class="fv-order-sheet__label"><?= htmlspecialchars((string) ($pageTexts['buyer_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>
                <?php if ($mode !== 'view'): ?>
                <button type="button"
                        id="fv_order_btn_change_customer"
                        class="fv-order-btn-change-customer"
                        aria-haspopup="dialog"
                        aria-controls="modal_order_change_customer">
                    <i class="fa-solid fa-user-pen" aria-hidden="true"></i>
                    <span id="fv_order_btn_change_customer_label"><?= htmlspecialchars((string) ($initialCustomerId > 0 ? ($pageTexts['order_change_customer'] ?? '') : ($pageTexts['customer_order'] ?? '')), ENT_QUOTES, 'UTF-8') ?></span>
                </button>
                <?php endif; ?>
            </div>

            <input type="hidden" id="selectCustomer" name="customer_id" value="<?= $initialCustomerId > 0 ? $initialCustomerId : '' ?>">

            <div id="fv_order_customer_card" class="fv-order-sheet__customer-card">
                <?= $renderCustomerCard($initialCustomerId > 0 ? $initialCustomer : null) ?>
            </div>
        </div>

        <?php if (($mode === 'edit' || $mode === 'view') && $order): ?>
        <div class="fv-order-sheet__block fv-order-sheet__block--order-meta">
            <div class="fv-order-meta-pills">
                <div class="fv-order-meta-pill">
                    <span class="fv-order-meta-pill__label"><?= htmlspecialchars((string) ($pageTexts['order_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                    <strong><?= htmlspecialchars((string) ($order->order_number ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                    <input type="hidden" name="order_id" value="<?= (int) ($order->order_id ?? 0) ?>">
                    <input type="hidden" name="order_number" value="<?= htmlspecialchars((string) ($order->order_number ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="fv-order-meta-pill">
                    <span class="fv-order-meta-pill__label"><?= htmlspecialchars((string) ($pageTexts['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="fv-order-meta-pill__status">
                        <?php
                        $so = $order->status_order ?? $order->statusOrder ?? null;
                        $statusLangKeys = [
                            'New' => 'new', 'Read' => 'read', 'Pending' => 'pending', 'Dispatched' => 'dispatched',
                            'Delivered' => 'delivered', 'Return Request' => 'return_request', 'Returned' => 'returned',
                            'Canceled' => 'canceled', 'Approved' => 'approved', 'Warehouse Assigned' => 'warehouse_assigned',
                            'Partially Completed' => 'partialy_completed', 'Warehouse Completed' => 'warehouse_completed',
                            'Completed' => 'completed', 'Rejected' => 'rechazado', 'In Proccess' => 'pending',
                            'Credit Note' => 'rechazado', 'Returned by Warehouse' => 'returned', 'Shopping' => 'new',
                        ];
                        $nameEn = is_object($so) ? (string) ($so->name_status_english ?? '') : '';
                        $statusKey = $statusLangKeys[$nameEn] ?? strtolower(str_replace(' ', '_', $nameEn));
                        $statusLabel = trim((string) ($pageTexts[$statusKey] ?? ''));
                        if ($statusLabel === '' && is_object($so)) {
                            $statusLabel = $nameEn !== '' ? $nameEn : (string) ($so->name_status_spanish ?? '');
                        }
                        ?>
                        <span class="roundblock notranslate" style="background:<?= htmlspecialchars((string) ($so->color ?? '#94a3b8'), ENT_QUOTES, 'UTF-8') ?>"></span>
                        <?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <input type="hidden" id="customer_email" value="<?= htmlspecialchars($customerEmail, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" id="group_price" value="<?= htmlspecialchars($groupPrice, ENT_QUOTES, 'UTF-8') ?>">

    <div class="fv-order-sheet__controls">
        <?php if ($mode === 'view'): ?>
        <div class="fv-order-sheet__control">
            <label><?= htmlspecialchars((string) ($pageTexts['vendedor'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>
            <div class="fv-order-readonly-field"><?= htmlspecialchars($sellerName !== '' ? mb_strtoupper($sellerName) : '—', ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <div class="fv-order-sheet__control">
            <label><?= htmlspecialchars((string) ($pageTexts['discount_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>
            <div class="fv-order-readonly-field"><?= htmlspecialchars(($discountType === '2' ? ($pageTexts['in'] ?? '') . ' $' : ($pageTexts['in'] ?? '') . ' %'), ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <div class="fv-order-sheet__control">
            <label><?= htmlspecialchars((string) ($pageTexts['discount'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>
            <div class="fv-order-readonly-field"><?= htmlspecialchars((string) $discountValue, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <?php if (!empty($order->created)): ?>
        <div class="fv-order-sheet__control">
            <label><?= htmlspecialchars((string) ($pageTexts['date_order_combine'] ?? $pageTexts['fecha'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>
            <div class="fv-order-readonly-field"><?= htmlspecialchars(fv_format_display_date($order->created ?? null), ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <?php endif; ?>
        <?php else: ?>
        <div class="fv-order-sheet__control">
            <label for="picker_seller"><?= htmlspecialchars((string) ($pageTexts['vendedor'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>
            <select id="picker_seller" class="form-control selectpicker show-tick" name="user_id" data-width="100%">
                <option value="" disabled <?= $selectedSellerId < 1 ? 'selected' : '' ?>><?= htmlspecialchars(strtoupper((string) ($pageTexts['seleccionar_por'] ?? '')), ENT_QUOTES, 'UTF-8') ?></option>
                <?php foreach ($sellers as $sel): ?>
                    <?php $sid = (int) ($sel['id'] ?? 0); ?>
                    <option value="<?= $sid ?>" <?= $sid === $selectedSellerId ? 'selected' : '' ?>><?= htmlspecialchars(strtoupper((string) ($sel['user']['name'] ?? '-')), ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="fv-order-sheet__control">
            <label for="discountTypeSelect"><?= htmlspecialchars((string) ($pageTexts['discount_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>
            <select id="discountTypeSelect" class="form-control select_discount" name="discount_type">
                <option value="1" <?= $discountType === '1' ? 'selected' : '' ?>><?= htmlspecialchars((string) ($pageTexts['in'] ?? ''), ENT_QUOTES, 'UTF-8') ?> %</option>
                <option value="2" <?= $discountType === '2' ? 'selected' : '' ?>><?= htmlspecialchars((string) ($pageTexts['in'] ?? ''), ENT_QUOTES, 'UTF-8') ?> $</option>
            </select>
        </div>
        <div class="fv-order-sheet__control">
            <label for="input_discount"><?= htmlspecialchars((string) ($pageTexts['discount'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>
            <input id="input_discount" class="form-control mask text-left select_discount" type="text" name="discount" value="<?= htmlspecialchars((string) $discountValue, ENT_QUOTES, 'UTF-8') ?>" placeholder="0.00">
        </div>
        <?php if ($mode === 'add'): ?>
        <div class="fv-order-sheet__control fv-order-sheet__control--check">
            <label class="fv-order-sheet__check">
                <input type="checkbox" name="proforma" id="seller-proforma" value="1">
                <span><?= htmlspecialchars((string) ($pageTexts['proforma'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
            </label>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php if ($mode !== 'view'): ?>
<?php $this->load->view('company/order/_modal_order_change_customer', [
    'pageTexts' => $pageTexts,
    'orderFormConfig' => $orderFormConfig,
]); ?>

<script>
window.fvOrderFormConfig = <?= json_encode([
    'mode' => $mode,
    'searchUrl' => $orderFormConfig['searchUrl'] ?? '',
    'customerFormDataUrl' => $orderFormConfig['customerFormDataUrl'] ?? '',
    'flagsBaseUrl' => $orderFormConfig['flagsBaseUrl'] ?? '',
    'initialCustomerId' => $initialCustomerId,
    'initialCustomer' => $initialCustomer,
    'countryDataMap' => $orderFormConfig['countryDataMap'] ?? [],
    'lang' => [
        'loading' => $pageTexts['add_order_loading_customers'] ?? ($pageTexts['cargando'] ?? ''),
        'empty' => $pageTexts['sin_resultados'] ?? '',
        'select_customer' => $pageTexts['seleccionar_por'] ?? '',
        'search_customer' => $pageTexts['add_order_search_placeholder'] ?? ($pageTexts['search_customer'] ?? ''),
        'order_change_customer' => $pageTexts['order_change_customer'] ?? '',
        'customer_order' => $pageTexts['customer_order'] ?? '',
        'debe_elegir_cliente' => $pageTexts['debe_elegir_cliente'] ?? '',
        'email' => $pageTexts['email'] ?? '',
        'phone' => $pageTexts['phone'] ?? '',
        'cell_phone' => $pageTexts['cell_phone'] ?? '',
        'address' => $pageTexts['address'] ?? '',
        'tax_id' => $pageTexts['tax_id'] ?? '',
        'group' => $pageTexts['group'] ?? '',
    ],
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
</script>
<?php endif; ?>
