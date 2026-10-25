<!-- Modal -->
<div class="modal fade" id="modal-salesman" data-keyboard="false" tabindex="-1" aria-labelledby="modal-salesmanLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <?= $lang_dashboard['ordenes_por_vendedor'] . $titulo_filtrado  ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="border-top-0"><?= $lang_dashboard['seller'] ?></th>
                                <th class="border-top-0 text-right"><?= $lang_dashboard['monto'] ?></th>
                                <th class="border-top-0"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach(is_array($sales_byvendor['all'] ?? null) ? $sales_byvendor['all'] : [] as $key => $ven): ?>
                            <tr>
                                <td>
                                    <span class="fv-dash-table__cat">
                                        <?php if (isset($sellerAvatarHtml) && is_callable($sellerAvatarHtml)) : ?>
                                            <?= $sellerAvatarHtml($ven) ?>
                                        <?php endif; ?>
                                        <h6 class="mb-0"><?= htmlspecialchars((string) $ven->vendor) ?></h6>
                                    </span>
                                </td>
                                <td>
                                    <h6 class="text-success text-right">
                                        <?= function_exists('fv_format_money')
    ? fv_format_money($ven->total, $format_numeric, $moneda)
    : ($moneda. number_format($ven->total, $format_numeric->digits, $format_numeric->radixPoint,$format_numeric->groupSeparator)) ?>
                                    </h6>
                                </td>
                                <td>
                                    <h6 class="text-success text-right">
                                        <?= number_format($ven->percent_amount, $format_numeric->digits, $format_numeric->radixPoint,$format_numeric->groupSeparator) ?>%
                                    </h6>
                                </td>

                            </tr>
                            <?php endforeach; ?>
                            <?php if(count(is_array($sales_byvendor['all'] ?? null) ? $sales_byvendor['all'] : []) > 0): ?>
                            <tr>
                                <td class="bg-success">
                                    <h6 class="text-bold">
                                        Total
                                    </h6>
                                </td>
                                <td class="bg-success">
                                    <h6 class="text-bold text-right">
                                        <?= function_exists('fv_format_money') ? fv_format_money($sales_byvendor['total'], $format_numeric, $moneda) : ($moneda.number_format($sales_byvendor['total'], $format_numeric->digits, $format_numeric->radixPoint,$format_numeric->groupSeparator)) ?>
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