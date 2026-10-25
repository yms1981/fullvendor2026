<!-- Modal -->
<div class="modal fade" id="modal-allsalesByCategoryCount" data-keyboard="false" tabindex="-1"
    aria-labelledby="modal-allsalesByCategoryCountLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-allsalesByCategoryCountLabel">
                    <?= $lang_dashboard['ventas_por_categoria_count'] . $titulo_filtrado  ?></h5>
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
                                <th class="border-top-0"><?= $lang_dashboard['qty'] ?></th>
                                <th class="border-top-0"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (is_array($sales_bycategoryQty ?? null) ? $sales_bycategoryQty : [] as $key => $value) :?>
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
                                    <h6 class="text-bold text-success">
                                        <?= $value->qty_items ?>
                                    </h6>
                                </td>
                                <td>
                                    <h6 class="text-bold text-right">
                                        <?= number_format($value->percent_qty, $format_numeric->digits, $format_numeric->radixPoint,$format_numeric->groupSeparator) ?>%
                                    </h6>
                                </td>
                            </tr>
                            <?php endforeach;?>
                            <!-- Total -->
                            <?php if(count(is_array($sales_bycategoryQty ?? null) ? $sales_bycategoryQty : []) > 0): ?>
                            <tr>
                                <td class="bg-success">
                                    <h6 class="text-bold">
                                        Total
                                    </h6>
                                </td>
                                <td class="bg-success">
                                    <h6 class="text-bold">
                                        <?= $sales_bycategoryQty_all['total_qty'] ?? 0 ?>
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