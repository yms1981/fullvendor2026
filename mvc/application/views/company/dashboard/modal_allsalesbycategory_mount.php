<!-- Modal -->
<div class="modal fade" id="modal-allsalesByCategoryMount" data-keyboard="false" tabindex="-1"
    aria-labelledby="modal-allsalesByCategoryMountLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-allsalesByCategoryMountLabel">
                    <?= $lang_dashboard['ventas_por_categoria_mount'] . $titulo_filtrado  ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="border-top-0"><?= $lang_dashboard['category'] ?></th>
                                <th class="border-top-0 text-right"><?= $lang_dashboard['amount'] ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (is_array($sales_bycategoryMount ?? null) ? $sales_bycategoryMount : [] as $key => $value) :?>
                            <tr>
                                <td>
                                    <span class="fv-dash-table__cat">
                                        <?php if (isset($categoryAvatarHtml) && is_callable($categoryAvatarHtml)) : ?>
                                            <?= $categoryAvatarHtml($value) ?>
                                        <?php endif; ?>
                                        <h6 class="text-bold mb-0">
                                            <?= htmlspecialchars((string) $value->category_name) ?>
                                        </h6>
                                    </span>
                                </td>
                                <td>
                                    <h6 class="text-bold text-success text-right">
                                        <?= function_exists('fv_format_money') ? fv_format_money($value->amount, $format_numeric, $moneda) : ($moneda. number_format($value->amount, $format_numeric->digits, $format_numeric->radixPoint,$format_numeric->groupSeparator)) ?>
                                    </h6>
                                </td>
                                <td>
                                    <h6 class="text-bold text-right">
                                        <?= number_format($value->percent_amount, $format_numeric->digits, $format_numeric->radixPoint,$format_numeric->groupSeparator) ?>%
                                    </h6>
                                </td>
                            </tr>
                            <?php endforeach;?>

                            <!-- Total -->
                            <?php if(count($sales_bycategoryMount) > 0): ?>
                            <tr>
                                <td class="bg-success">
                                    <h6 class="text-bold">
                                        <?= htmlspecialchars($lang_dashboard['total'] ?? 'Total') ?>
                                    </h6>
                                </td>
                                <td class="bg-success">
                                    <h6 class="text-bold text-right">
                                        <?= function_exists('fv_format_money') ? fv_format_money($sales_bycategoryQty_all['total_mount'] ?? 0, $format_numeric, $moneda) : ($moneda.number_format($sales_bycategoryQty_all['total_mount'] ?? 0, $format_numeric->digits, $format_numeric->radixPoint,$format_numeric->groupSeparator)) ?>
                                    </h6>
                                </td>
                                <td class="bg-success">
                                    <h6 class="text-bold text-right">
                                        100%
                                    </h6>
                                </td>
                            </tr>
                            <?php endif; ?>
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