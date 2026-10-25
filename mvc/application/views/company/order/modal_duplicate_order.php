<?php
/**
 * Modal duplicar orden: cards con contacto, teléfono formateado, empresa, dirección.
 * Buscador, cliente actual primero y sombreado. Sin espacios en blanco innecesarios.
 * Todas las cadenas usan $pageTexts (traducciones).
 */
if (!function_exists('applyPhoneMask')) {
    function applyPhoneMask($phoneNumber, $mask) {
        if (empty($phoneNumber) || empty($mask)) return $phoneNumber;
        $digits = preg_replace('/\D/', '', $phoneNumber);
        if (empty($digits)) return $phoneNumber;
        $result = '';
        $digitIndex = 0;
        for ($i = 0; $i < strlen($mask) && $digitIndex < strlen($digits); $i++) {
            if ($mask[$i] === '#') {
                $result .= $digits[$digitIndex++];
            } else {
                $result .= $mask[$i];
            }
        }
        if ($digitIndex < strlen($digits)) {
            $result .= substr($digits, $digitIndex);
        }
        return $result;
    }
}

$currentCustomerId = (string)($order->customer_id ?? ($order->customer->customer_id ?? ''));
$customers = $customers ?? [];
$pt = $pageTexts ?? [];
$countryDataMap = isset($countryDataMap) && is_array($countryDataMap) ? $countryDataMap : [];
?>
<style>
#modal_duplicate_order .modal-dialog { max-height: 90vh; margin: 1rem auto; }
#modal_duplicate_order .modal-content { display: flex; flex-direction: column; max-height: 90vh; }
#modal_duplicate_order .modal-body { flex: 1 1 auto; min-height: 0; overflow: hidden; display: flex; flex-direction: column; padding-bottom: 0.5rem; }
#modal_duplicate_order .duplicate-list-wrap {
    flex: 1 1 auto;
    min-height: 0;
    max-height: 55vh;
    overflow-y: scroll;
    overflow-x: hidden;
    -webkit-overflow-scrolling: touch;
}
#modal_duplicate_order .modal-footer { flex-shrink: 0; border-top: 1px solid #dee2e6; padding: 0.5rem 1rem; }
#modal_duplicate_order .duplicate-customer-item.duplicate-customer-selected {
    border: 2px solid #28a745 !important;
    background-color: #d4edda !important;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25) !important;
}
#modal_duplicate_order .duplicate-customer-item { background-color: #fff; min-height: 72px; }
#modal_duplicate_order .duplicate-customer-card { margin-bottom: 0.5rem; }
#modal_duplicate_order .duplicate-customer-card .card-body { padding: 0.6rem 0.85rem; }
#modal_duplicate_order .duplicate-customer-card .card-title { font-size: 0.95rem; margin-bottom: 0.35rem; line-height: 1.3; }
#modal_duplicate_order .duplicate-customer-card .card-detail { font-size: 0.8rem; line-height: 1.45; margin-bottom: 0.1rem; }
#modal_duplicate_order .duplicate-customer-card .card-detail:last-child { margin-bottom: 0; }
#modal_duplicate_order .duplicate-customer-card .card-detail .label { color: #6c757d; min-width: 4.5em; display: inline-block; }
#modal_duplicate_order #duplicate_customer_search { min-height: 38px; }
</style>
<!-- Modal Duplicar Orden -->
<div class="modal fade" id="modal_duplicate_order" tabindex="-1" aria-labelledby="modal_duplicate_orderLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title" id="modal_duplicate_orderLabel"><?= htmlspecialchars($pt['modal_duplicate_order_title'] ?? '') ?></h5>
                <button type="button" class="close py-0" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="form_duplicate_order" method="get" action="<?= company_url('orders/duplicate/'.@$order->order_id) ?>">
                <input type="hidden" name="customer_id" id="duplicate_customer_id_input" value="<?= htmlspecialchars($currentCustomerId) ?>">
                <div class="modal-body py-2">
                    <p class="text-muted small mb-2"><?= htmlspecialchars($pt['modal_duplicate_order_help'] ?? '') ?></p>
                    <div class="form-group mb-2">
                        <label for="duplicate_customer_search" class="small font-weight-bold d-block mb-1"><?= htmlspecialchars($pt['modal_duplicate_select_customer'] ?? '') ?></label>
                        <input type="text"
                               id="duplicate_customer_search"
                               class="form-control"
                               placeholder="<?= htmlspecialchars($pt['modal_duplicate_search_placeholder'] ?? '') ?>"
                               autocomplete="off">
                    </div>
                    <?php if (empty($customers)): ?>
                        <p class="text-muted small mb-0"><?= htmlspecialchars($pt['modal_duplicate_no_customers'] ?? '') ?></p>
                    <?php else: ?>
                        <div id="duplicate_customer_list" class="duplicate-list-wrap">
                            <?php foreach ($customers as $c): ?>
                                <?php
                                $cid = is_object($c) ? ($c->customer_id ?? $c->id ?? '') : ($c['customer_id'] ?? $c['id'] ?? '');
                                $name = is_object($c) ? ($c->name ?? '') : ($c['name'] ?? '');
                                $business = is_object($c) ? ($c->business_name ?? '') : ($c['business_name'] ?? '');
                                $phoneRaw = is_object($c) ? ($c->phone ?? $c->cell_phone ?? '') : ($c['phone'] ?? $c['cell_phone'] ?? '');
                                $countryName = is_object($c) ? ($c->commercial_country ?? '') : ($c['commercial_country'] ?? '');
                                $countryData = ($countryName !== '' && isset($countryDataMap[$countryName])) ? $countryDataMap[$countryName] : null;
                                $phone = '-';
                                if ($phoneRaw !== '') {
                                    $digits = preg_replace('/\D/', '', $phoneRaw);
                                    if ($digits !== '') {
                                        $len = strlen($digits);
                                        if ($countryData && !empty($countryData['phonecode'])) {
                                            $mask = !empty($countryData['phonemask']) ? $countryData['phonemask'] : ($len <= 3 ? '(###)' : ($len <= 6 ? '(###) ###' : '(###) ### ####'));
                                            $formatted = applyPhoneMask($phoneRaw, $mask);
                                            $phone = '+' . $countryData['phonecode'] . ' ' . $formatted;
                                        } else {
                                            $mask = $len <= 3 ? '(###)' : ($len <= 6 ? '(###) ###' : '(###) ### ####');
                                            $phone = applyPhoneMask($phoneRaw, $mask);
                                        }
                                    }
                                }
                                $address = is_object($c) ? ($c->commercial_address ?? '') : ($c['commercial_address'] ?? '');
                                $isCurrent = ($cid !== '' && $currentCustomerId !== '' && (string)$cid === $currentCustomerId);
                                $searchText = strtolower(($name ?? '') . ' ' . ($business ?? '') . ' ' . $phoneRaw . ' ' . ($address ?? ''));
                                ?>
                                <div class="duplicate-customer-card"
                                     data-search="<?= htmlspecialchars($searchText) ?>">
                                    <div class="card duplicate-customer-item border <?= $isCurrent ? 'duplicate-customer-selected' : '' ?>"
                                         data-customer-id="<?= htmlspecialchars($cid) ?>"
                                         role="button"
                                         tabindex="0"
                                         style="cursor: pointer; transition: border-color .2s, box-shadow .2s, background-color .2s;">
                                        <div class="card-body">
                                            <?php if ($isCurrent): ?>
                                                <span class="badge badge-success badge-pill mb-2"><?= htmlspecialchars($pt['modal_duplicate_current_customer'] ?? '') ?></span>
                                            <?php endif; ?>
                                            <h6 class="card-title text-dark font-weight-bold"><?= htmlspecialchars($business ?: $name ?: '-') ?></h6>
                                            <div class="card-detail"><span class="label"><?= htmlspecialchars($pt['contact'] ?? '') ?>:</span> <?= htmlspecialchars($name ?: '-') ?></div>
                                            <div class="card-detail"><span class="label"><?= htmlspecialchars($pt['phone'] ?? '') ?>:</span> <?= htmlspecialchars($phone) ?></div>
                                            <div class="card-detail"><span class="label"><?= htmlspecialchars($pt['address'] ?? '') ?>:</span> <?= htmlspecialchars($address ?: '-') ?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer py-2">
                    <button type="submit" id="btn_duplicate_submit" class="btn btn-success" <?= empty($customers) ? 'disabled' : '' ?>>
                        <i class="fa fa-files-o"></i> <?= htmlspecialchars($pt['duplicate'] ?? '') ?>
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= htmlspecialchars($pt['cerrar'] ?? '') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
(function() {
    var list = document.getElementById('duplicate_customer_list');
    var input = document.getElementById('duplicate_customer_id_input');
    var searchEl = document.getElementById('duplicate_customer_search');
    var btn = document.getElementById('btn_duplicate_submit');
    if (!list || !input) return;

    function selectItem(card) {
        var item = card.classList.contains('duplicate-customer-item') ? card : card.querySelector('.duplicate-customer-item');
        if (!item || item.dataset.customerId === undefined) return;
        var id = item.dataset.customerId;
        input.value = id;
        [].forEach.call(list.querySelectorAll('.duplicate-customer-item'), function(el) {
            el.classList.remove('duplicate-customer-selected');
        });
        item.classList.add('duplicate-customer-selected');
    }

    list.addEventListener('click', function(e) {
        var card = e.target.closest('.duplicate-customer-card');
        if (card) selectItem(card);
    });
    list.addEventListener('keydown', function(e) {
        if (e.key !== 'Enter' && e.key !== ' ') return;
        var card = e.target.closest('.duplicate-customer-card');
        if (!card) return;
        e.preventDefault();
        selectItem(card);
    });

    if (searchEl) {
        searchEl.addEventListener('input', function() {
            var q = (this.value || '').toLowerCase().trim();
            [].forEach.call(list.querySelectorAll('.duplicate-customer-card'), function(wrapper) {
                var show = q === '' || (wrapper.dataset.search || '').indexOf(q) !== -1;
                wrapper.style.display = show ? '' : 'none';
            });
        });
    }

    var modalEl = document.getElementById('modal_duplicate_order');
    if (modalEl && list) {
        modalEl.addEventListener('shown.bs.modal', function() {
            var first = list.querySelector('.duplicate-customer-item.duplicate-customer-selected');
            if (first) first.scrollIntoView({ block: 'nearest', behavior: 'auto' });
        });
    }
})();
</script>
