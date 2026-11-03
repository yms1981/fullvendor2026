<?php
/**
 * @var array{count?: int, items?: list<array<string, mixed>>} $returnOverSales
 * @var array<string, string> $pageTexts
 */
$report = is_array($returnOverSales ?? null) ? $returnOverSales : [];
$items = isset($report['items']) && is_array($report['items']) ? $report['items'] : [];
$count = (int) ($report['count'] ?? count($items));
if ($count < 1 || $items === []) {
    return;
}

$pageTexts = is_array($pageTexts ?? null) ? $pageTexts : [];
$fill = static function (string $template, int $count): string {
    return str_replace('{count}', (string) $count, $template);
};
$title = trim((string) ($pageTexts['return_over_sales_title'] ?? ''));
$message = $fill(trim((string) ($pageTexts['return_over_sales_message'] ?? '')), $count);
$button = trim((string) ($pageTexts['return_over_sales_button'] ?? ''));
$fmtQty = static function ($value): string {
    $number = (float) $value;
    $places = abs($number - round($number)) < 0.0001 ? 0 : 2;

    return number_format($number, $places, '.', ',');
};
$groups = [];
foreach ($items as $item) {
    $item = is_array($item) ? $item : (array) $item;
    $key = (string) ((int) ($item['customer_id'] ?? 0));
    if (! isset($groups[$key])) {
        $groups[$key] = [
            'customer_name' => (string) ($item['customer_name'] ?? ''),
            'items' => [],
        ];
    }
    $groups[$key]['items'][] = $item;
}
?>
<div class="alert alert-warning mb-3" role="alert">
    <?php if ($title !== '') : ?>
        <strong><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></strong>
    <?php endif; ?>
    <?php if ($message !== '') : ?>
        <div class="<?= $title !== '' ? 'mt-1' : '' ?>"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if ($button !== '') : ?>
        <button type="button" class="btn btn-sm btn-warning mt-2 fv-btn-with-label" data-toggle="modal" data-target="#modal-return-over-sales">
            <?= htmlspecialchars($button, ENT_QUOTES, 'UTF-8') ?>
        </button>
    <?php endif; ?>
</div>

<style>
#modal-return-over-sales .modal-dialog { max-width: 880px; }
#modal-return-over-sales .modal-body { background: #fff; }
#modal-return-over-sales .fv-over-customer__head {
    position: sticky;
    top: 0;
    z-index: 2;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.7rem 1.15rem;
    background: #fff7f7;
    border-bottom: 1px solid #fecaca;
}
#modal-return-over-sales .fv-over-customer__toggle {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    flex: 1 1 auto;
    min-width: 0;
    margin: 0;
    padding: 0;
    border: 0;
    background: transparent;
    text-align: left;
    cursor: pointer;
}
#modal-return-over-sales .fv-over-customer__chevron {
    flex: 0 0 auto;
    width: 0.45rem;
    height: 0.45rem;
    border-right: 2px solid #6b7280;
    border-bottom: 2px solid #6b7280;
    transform: rotate(-45deg);
}
#modal-return-over-sales .fv-over-customer.is-open .fv-over-customer__chevron {
    transform: rotate(45deg);
}
#modal-return-over-sales .fv-over-customer__name {
    margin: 0;
    font-size: 0.92rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    color: #111827;
}
#modal-return-over-sales .fv-over-customer__print {
    flex: 0 0 auto;
}
#modal-return-over-sales .fv-over-customer__count {
    flex: 0 0 auto;
    padding: 0.15rem 0.55rem;
    border-radius: 999px;
    background: #fff;
    border: 1px solid #fecaca;
    color: #b91c1c;
    font-size: 0.75rem;
    font-weight: 600;
}
#modal-return-over-sales .fv-over-row {
    display: grid;
    grid-template-columns: 64px minmax(0, 1fr) auto;
    gap: 0.9rem;
    align-items: center;
    padding: 0.75rem 1.15rem;
    border-bottom: 1px solid #f3f4f6;
}
#modal-return-over-sales .fv-over-row:last-child { border-bottom: 0; }
#modal-return-over-sales .fv-over-thumb {
    --fv-thumb-size: 56px;
    width: 56px;
    height: 56px;
}
#modal-return-over-sales .fv-over-thumb .fv-product-list-image,
#modal-return-over-sales .fv-over-thumb .fv-product-sku-avatar {
    width: 56px;
    height: 56px;
    max-width: 56px;
    margin: 0;
}
#modal-return-over-sales .fv-over-thumb .fv-product-sku-avatar__img {
    width: 56px;
    height: 56px;
    object-fit: cover;
    border-radius: 0.55rem;
    border: 1px solid #f3f4f6;
    background: #fff;
}
#modal-return-over-sales .fv-over-name {
    margin: 0;
    font-size: 0.84rem;
    font-weight: 600;
    line-height: 1.25;
    color: #111827;
}
#modal-return-over-sales .fv-over-sku {
    margin: 0.15rem 0 0;
    font-size: 0.75rem;
    color: #6b7280;
}
#modal-return-over-sales .fv-over-metrics {
    display: flex;
    gap: 0.85rem;
}
#modal-return-over-sales .fv-over-metric {
    min-width: 4.2rem;
    text-align: right;
}
#modal-return-over-sales .fv-over-metric span {
    display: block;
    font-size: 0.65rem;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #9ca3af;
}
#modal-return-over-sales .fv-over-metric strong {
    font-size: 0.95rem;
    color: #111827;
}
#modal-return-over-sales .fv-over-metric--excess strong { color: #dc2626; }
</style>
<div class="modal fade" id="modal-return-over-sales" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="<?= htmlspecialchars((string) ($pageTexts['cerrar'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <?php foreach ($groups as $customerId => $group) : ?>
                    <?php
                    $groupCount = count($group['items']);
                    $groupLabel = $fill(trim((string) ($pageTexts['return_over_sales_group_count'] ?? '')), $groupCount);
                    $printLabel = trim((string) ($pageTexts['return_over_sales_print'] ?? ''));
                    $printUrl = company_url('orders/returnOverSalesPdf/' . (int) $customerId);
                    ?>
                    <section class="fv-over-customer">
                        <header class="fv-over-customer__head">
                            <button type="button" class="fv-over-customer__toggle" aria-expanded="false">
                                <span class="fv-over-customer__chevron" aria-hidden="true"></span>
                                <h6 class="fv-over-customer__name"><?= htmlspecialchars($group['customer_name'], ENT_QUOTES, 'UTF-8') ?></h6>
                                <?php if ($groupLabel !== '') : ?>
                                    <span class="fv-over-customer__count"><?= htmlspecialchars($groupLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </button>
                            <?php if ($printLabel !== '' && (int) $customerId > 0) : ?>
                                <a class="btn btn-sm btn-outline-secondary fv-btn-with-label fv-over-customer__print" target="_blank" rel="noopener" href="<?= htmlspecialchars($printUrl, ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($printLabel, ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            <?php endif; ?>
                        </header>
                        <div class="fv-over-customer__body" hidden>
                        <?php foreach ($group['items'] as $item) : ?>
                            <div class="fv-over-row">
                                <div class="fv-over-thumb">
                                    <?php $this->load->view('company/product/partials/thumbnail', [
                                        'listItem' => (object) [
                                            'pro_id' => (int) ($item['product_id'] ?? 0),
                                            'sku' => (string) ($item['sku'] ?? ''),
                                            'image_uuid' => (string) ($item['image_uuid'] ?? ''),
                                            'status' => 1,
                                        ],
                                    ]); ?>
                                </div>
                                <div>
                                    <p class="fv-over-name"><?= htmlspecialchars((string) ($item['product_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                                    <p class="fv-over-sku"><?= htmlspecialchars((string) ($item['sku'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                                <div class="fv-over-metrics">
                                    <div class="fv-over-metric">
                                        <span><?= htmlspecialchars((string) ($pageTexts['return_over_sales_sold'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                        <strong><?= htmlspecialchars($fmtQty($item['qty_sold'] ?? 0), ENT_QUOTES, 'UTF-8') ?></strong>
                                    </div>
                                    <div class="fv-over-metric">
                                        <span><?= htmlspecialchars((string) ($pageTexts['return_over_sales_credited'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                        <strong><?= htmlspecialchars($fmtQty($item['qty_credited'] ?? 0), ENT_QUOTES, 'UTF-8') ?></strong>
                                    </div>
                                    <div class="fv-over-metric fv-over-metric--excess">
                                        <span><?= htmlspecialchars((string) ($pageTexts['return_over_sales_excess'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                        <strong><?= htmlspecialchars($fmtQty($item['qty_excess'] ?? 0), ENT_QUOTES, 'UTF-8') ?></strong>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= htmlspecialchars((string) ($pageTexts['cerrar'] ?? ''), ENT_QUOTES, 'UTF-8') ?></button>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    var modal = document.getElementById('modal-return-over-sales');
    if (modal && modal.parentNode !== document.body) {
        document.body.appendChild(modal);
    }
    if (!modal) {
        return;
    }
    function collapseAll() {
        modal.querySelectorAll('.fv-over-customer').forEach(function (section) {
            section.classList.remove('is-open');
            var body = section.querySelector('.fv-over-customer__body');
            var toggle = section.querySelector('.fv-over-customer__toggle');
            if (body) {
                body.setAttribute('hidden', 'hidden');
            }
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    }
    collapseAll();
    modal.addEventListener('click', function (event) {
        var toggle = event.target.closest('.fv-over-customer__toggle');
        if (!toggle || !modal.contains(toggle)) {
            return;
        }
        var section = toggle.closest('.fv-over-customer');
        var body = section ? section.querySelector('.fv-over-customer__body') : null;
        if (!section || !body) {
            return;
        }
        var open = body.hasAttribute('hidden');
        if (open) {
            body.removeAttribute('hidden');
            section.classList.add('is-open');
            toggle.setAttribute('aria-expanded', 'true');
        } else {
            body.setAttribute('hidden', 'hidden');
            section.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
        }
    });
    if (window.jQuery) {
        window.jQuery(document).on('show.bs.modal', '#modal-return-over-sales', collapseAll);
    }
})();
</script>
