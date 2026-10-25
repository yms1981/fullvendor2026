<!-- Modal -->
<div class="modal fade" id="modal-top10" data-keyboard="false" tabindex="-1" aria-labelledby="modal-top10Label"
    aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-top10Label">
                    <?= $lang_dashboard['top_10'] . $titulo_filtrado  ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="border-top-0"></th>
                                <th class="border-top-0"><?= htmlspecialchars($lang_dashboard['label_sku'] ?? 'SKU') ?></th>
                                <th class="border-top-0"><?= htmlspecialchars($lang_dashboard['label_product'] ?? 'Producto') ?></th>
                                <th class="border-top-0"><?= htmlspecialchars($lang_dashboard['qty'] ?? 'Qty') ?></th>
                                <th class="border-top-0"><?= htmlspecialchars($lang_dashboard['label_purchase_price'] ?? 'Purchase Price') ?></th>
                                <th class="border-top-0"><?= htmlspecialchars($lang_dashboard['label_average_ticket'] ?? 'Average Ticket') ?></th>
                                <th class="border-top-0"><?= htmlspecialchars($lang_dashboard['label_total_sales'] ?? 'Total Sales') ?></th>
                                <th class="border-top-0"><?= htmlspecialchars($lang_dashboard['label_markup'] ?? 'MarkUp') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach(is_array($top10_productos ?? null) ? $top10_productos : [] as $key => $pro): ?>
                            
                            <tr>
                                <td class="fv-dash-table__avatar-cell">
                                    <?php if (isset($productAvatarHtml) && is_callable($productAvatarHtml)) : ?>
                                        <?= $productAvatarHtml($pro) ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <h6>
                                        <?= htmlspecialchars((string) $pro->sku) ?>
                                    </h6>
                                </td>
                                <td>
                                    <h6>
                                        <?= htmlspecialchars((string) $pro->name) ?>
                                    </h6>
                                </td>
                                <td>
                                    <h6>
                                        <?= floatval($pro->qty_items) ?>
                                    </h6>
                                </td>
                                <td class="text-right">
                                    <h6 class="text-bold text-success">
                                        <?= function_exists('fv_format_money') ? fv_format_money($pro->purchase_price, $format_numeric, $moneda) : ($moneda. number_format($pro->purchase_price, $format_numeric->digits, $format_numeric->radixPoint,$format_numeric->groupSeparator)) ?>
                                    </h6>
                                </td>
                                <td class="text-right">
                                    <h6 class="text-bold text-success">
                                        <?= function_exists('fv_format_money') ? fv_format_money($pro->average_ticket, $format_numeric, $moneda) : ($moneda. number_format($pro->average_ticket, $format_numeric->digits, $format_numeric->radixPoint,$format_numeric->groupSeparator)) ?>
                                    </h6>
                                </td>
                                <td class="text-right">
                                    <h6 class="text-bold text-success">
                                        <?= function_exists('fv_format_money') ? fv_format_money($pro->total, $format_numeric, $moneda) : ($moneda. number_format($pro->total, $format_numeric->digits, $format_numeric->radixPoint,$format_numeric->groupSeparator)) ?>
                                    </h6>
                                </td>
                                <td class="text-right">
                                    <h6 class="text-bold <?= $pro->mark_up <= 0?'text-danger':'text-success' ?>">
                                        <?= number_format($pro->mark_up, $format_numeric->digits, $format_numeric->radixPoint,$format_numeric->groupSeparator) ?>%
                                    </h6>
                                </td>
                            </tr>
                            
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger"
                    data-dismiss="modal"><?= $lang_dashboard['close'] ?></button>
            </div>
        </div>
    </div>
</div>