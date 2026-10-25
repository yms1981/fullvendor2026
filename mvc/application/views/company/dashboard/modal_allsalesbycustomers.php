<!-- Modal -->
<div class="modal fade" id="modal-allsalesbycustomers" data-keyboard="false" tabindex="-1"
    aria-labelledby="modal-allsalesbycustomersLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-allsalesbycustomersLabel">
                    <?= $lang_dashboard['ventas_por_cliente'] . $titulo_filtrado  ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="border-top-0"><?= $lang_dashboard['cliente'] ?></th>
                                <th class="border-top-0"><?= $lang_dashboard['seller'] ?></th>
                                <th class="border-top-0"><?= $lang_dashboard['last_order'] ?></th>
                                <th class="border-top-0"><?= $lang_dashboard['cantidades'] ?></th>
                                <th class="border-top-0"><?= $lang_dashboard['monto'] ?></th>
                                <th class="border-top-0"><?= $lang_dashboard['ticket_promedio'] ?></th>
                                <th class="border-top-0"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach(is_array($sales_bycustomer['all'] ?? null) ? $sales_bycustomer['all'] : [] as $key => $sales): ?>
                            <tr>
                                <td>
                                    <h6 class="text-bold text-wrap">
                                        <?= $sales->customer ?>
                                    </h6>
                                </td>
                                <td>
                                    <h6 class="text-bold">
                                        <?= $sales->seller ?>
                                    </h6>
                                </td>
                                <td>
                                    <h6 class="text-bold text-nowrap">
                                        <?= date($format_date->format_php, strtotime($sales->last_created)) ?>
                                    </h6>
                                </td>


                                <td>
                                    <h6 class="text-bold text-success text-center">
                                        <?=  $sales->items ?>
                                    </h6>
                                </td>
                                <td>
                                    <h6 class="text-bold text-success text-right">
                                        <?= $moneda. number_format($sales->total, $format_numeric->digits, $format_numeric->radixPoint,$format_numeric->groupSeparator) ?>
                                    </h6>
                                </td>
                                <td>
                                    <h6 class="text-bold text-success">
                                        <?= $moneda. number_format($sales->ticket, $format_numeric->digits, $format_numeric->radixPoint,$format_numeric->groupSeparator) ?>
                                    </h6>
                                </td>

                                <td>
                                    <h6 class="text-bold text-center">
                                        <?= number_format($sales->percent_amount, $format_numeric->digits, $format_numeric->radixPoint,$format_numeric->groupSeparator) ?>%
                                    </h6>
                                </td>

                            </tr>

                            <?php endforeach; ?>
                            <?php if(count(is_array($sales_bycustomer['all'] ?? null) ? $sales_bycustomer['all'] : []) > 0): ?>
                            <tr>
                                <td class="bg-success">

                                    <h6 class="text-bold">
                                        Total
                                    </h6>
                                </td>
                                <td class="bg-success"></td>
                                <td class="bg-success"></td>
                                <td class="bg-success">
                                    <h6 class="text-bold text-center">
                                        <?= $sales_bycustomer['total_qty'] ?>
                                    </h6>
                                </td>
                                <td class="bg-success">
                                    <h6 class="text-bold text-right">
                                        <?= $moneda.number_format($sales_bycustomer['total'], $format_numeric->digits, $format_numeric->radixPoint,$format_numeric->groupSeparator) ?>
                                    </h6>
                                </td>
                                <td class="bg-success">

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