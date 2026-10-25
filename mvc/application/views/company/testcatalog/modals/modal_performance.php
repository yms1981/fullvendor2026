<?php 

$sending = isset($v->sending)?$v->sending:0;
$customer_count = isset($v->customer_count)?$v->customer_count:0;
$total_import = isset($v->total)?$v->total:0;
$ticket_average = 0;

if($sending>0 && $total_import>0){
    $ticket_average = $total_import/$sending;
}

?>

<div class="modal fade" id="performanceModal<?=$v->catalog_id?>" tabindex="-1" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header p-3">
                <h5 class="modal-title" id="exampleModalLabel"><?= $lang_optionbasic['view_performance'] ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">

                <h5><?= $lang_optionbasic['from'] ?> <?= date('M-d-Y', strtotime(date('Y-01-01'))) ?><br><?= $lang_optionbasic['to'] ?> <?= date('M-d-Y', strtotime(date('Y-m-d'))) ?></h5>

                <div class="card mb-2">
                    <div class="card-body">
                        <div class="float-right">
                            <div class="fullvendor_bg_primary fullvendor_text-primary rounded d-flex justify-content-center align-items-center"
                                style="width: 48px; height: 48px;font-size: 25px !important;">
                                <i class="fa-solid fa-paste"></i>
                                <!-- <i class="fas fa-user"></i> -->
                            </div>
                        </div>
                        <h5><?= $lang_optionbasic['total_order_quantity'] ?></h5>
                        <h5><?= $sending ?></h5>
                    </div>
                </div>
                <div class="card mb-2">
                    <div class="card-body">
                        <div class="float-right">
                            <div class="fullvendor_bg-success fullvendor_text-success rounded d-flex justify-content-center align-items-center"
                                style="width: 48px; height: 48px;font-size: 25px !important;">
                                <i class="fa-solid fa-cart-shopping"></i>
                                <!-- <i class="fas fa-user"></i> -->
                            </div>
                        </div>
                        <h5><?= $lang_optionbasic['total_order_amount'] ?></h5>
                        <h5><?= $moneda ?><?= number_format($total_import, $format_numeric->digits, $format_numeric->radixPoint,$format_numeric->groupSeparator); ?></h5>
                    </div>
                </div>
                <div class="card mb-2">
                    <div class="card-body">
                        <div class="float-right">
                            <div class="fullvendor_bg-warning fullvendor_text-warning rounded d-flex justify-content-center align-items-center"
                                style="width: 48px; height: 48px;font-size: 25px !important;">
                                <i class="fa-solid fa-tag"></i>
                                <!-- <i class="fas fa-user"></i> -->
                            </div>
                        </div>
                        <h5><?= $lang_optionbasic['ticket_average'] ?></h5>
                        <h5><?= $moneda ?><?= number_format($ticket_average, $format_numeric->digits, $format_numeric->radixPoint,$format_numeric->groupSeparator); ?></h5>
                    </div>
                </div>
                <div class="card mb-2">
                    <div class="card-body">
                        <div class="float-right">
                            <div class="fullvendor_bg-danger fullvendor_text-danger rounded d-flex justify-content-center align-items-center"
                                style="width: 48px; height: 48px;font-size: 25px !important;">
                                <i class="fa-solid fa-user-tag"></i>
                                <!-- <i class="fas fa-user"></i> -->
                            </div>
                        </div>
                        <h5><?= $lang_optionbasic['total_customers'] ?></h5>
                        <h5><?= $customer_count ?></h5>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" data-dismiss="modal" aria-label="Close"
                    class="btn btn-danger"><?= $lang_modal['close'] ?></button>
            </div>

        </div>
    </div>
</div>