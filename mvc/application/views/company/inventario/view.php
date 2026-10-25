<style>
.inventory-order-view .inventory-order-view__title {
    font-size: 0.95rem;
    font-weight: 700;
    margin-bottom: 0;
}
.inventory-order-view .inventory-order-view__card {
    border: 1px solid #e6e6e6;
    box-shadow: none;
    overflow: visible;
}
.inventory-order-view .inventory-order-view__card > .card-body {
    overflow: visible;
    padding-top: 1.25rem;
}
.inventory-order-view .inventory-order-view__toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
}
.inventory-order-view .inventory-order-view__print {
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    min-height: 2.5rem;
    padding: 0.55rem 1.15rem !important;
    font-size: 0.95rem !important;
    font-weight: 700 !important;
    line-height: 1.2;
    letter-spacing: 0.02em;
    text-transform: uppercase;
    white-space: nowrap;
    color: #fff !important;
    background-color: #dc2626 !important;
    border: 1px solid #b91c1c !important;
    border-radius: 0.5rem !important;
    box-shadow: 0 0.2rem 0.55rem rgba(220, 38, 38, 0.35);
}
.inventory-order-view .inventory-order-view__print:hover,
.inventory-order-view .inventory-order-view__print:focus {
    color: #fff !important;
    background-color: #b91c1c !important;
    border-color: #991b1b !important;
    text-decoration: none;
}
.inventory-order-view .inventory-order-view__print i {
    font-size: 1.1rem;
    line-height: 1;
}
.inventory-order-view .inventory-order-view__meta {
    color: #1e88e5;
    font-size: 0.82rem;
    margin-bottom: 0.15rem;
}
.inventory-order-view .inventory-order-view__meta strong {
    font-weight: 700;
}
.inventory-order-view .inventory-order-view__table th {
    font-size: 0.78rem;
    font-weight: 700;
    white-space: nowrap;
}
.inventory-order-view .inventory-order-view__table td {
    font-size: 0.8rem;
    vertical-align: middle;
}
</style>
<div class="container-fluid page-body-wrapper inventory-order-view">

    <div class="main-panel">
        <div class="container-fluid pt-3">

            <div class="row">
                <div class="col-md-6">
                    <div class="page-header mb-2">
                        <h3 class="page-title inventory-order-view__title"><?= strtoupper($lang_inventory['inventory_order_details']) ?></h3>
                    </div>
                </div>
                <div class="col-md-6 text-right">
                </div>
            </div>
            <div class="card inventory-order-view__card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <div class="inventory-order-view__toolbar">
                                <a target="_blank"
                                   href="<?= company_url('inventory/order/print/'. (int) ($order->order_id ?? 0)) ?>"
                                   class="btn inventory-order-view__print">
                                    <i class="mdi mdi-printer" aria-hidden="true"></i>
                                    <span><?= htmlspecialchars((string) ($lang_order['imprimir_orden'] ?? $lang_order['imprimir'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                </a>
                            </div>
                        </div>
                        <div class="col-lg-6 mb-3">

                            <?php if($order->warehouse_user_id !=""){ ?>
                            <p class="inventory-order-view__meta">
                                <span class="notranslate">
                                    <strong><?= $lang_order['warehouse_user'] ?>:</strong>
                                    <?= htmlspecialchars((string) ($warehouse_user_label ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </p>
                            <?php } ?>
                        </div>
                        <div class="col-lg-6  mb-3 text-right">
                            <p class="inventory-order-view__meta"><strong><?= $lang_order['fecha_creacion_order'] ?>:
                                </strong><?= htmlspecialchars(fv_format_display_datetime(@$order->created), ENT_QUOTES, 'UTF-8') ?> hs</p>
                            <p class="inventory-order-view__meta"><strong><?= $lang_order['order_orden_number'] ?>:
                                </strong><?=@$order->order_number?></p>
                            <p class="inventory-order-view__meta">
                                <span class="roundblock notranslate" style="background: <?= htmlspecialchars((string) ($order_status_color ?? ''), ENT_QUOTES, 'UTF-8') ?>;"></span>
                                <?= htmlspecialchars((string) ($order_status_text ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </p>
                        </div>

                        <div class="col-12">
                            <?php
                                $invSearchLabel = trim((string) ($lang_inventory['view_search_label'] ?? ($lang_order['buscar'] ?? 'Search')));
                                $invSearchPlaceholder = trim((string) ($lang_inventory['view_search_placeholder'] ?? ''));
                            ?>
                            <div class="d-flex justify-content-end align-items-center mb-2">
                                <label for="inventory-order-view-line-search" class="mb-0 mr-2"><?= htmlspecialchars($invSearchLabel, ENT_QUOTES, 'UTF-8') ?></label>
                                <input id="inventory-order-view-line-search" type="search" autocomplete="off"
                                    class="form-control form-control-sm" style="max-width: 260px;"
                                    placeholder="<?= htmlspecialchars($invSearchPlaceholder, ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <table id="infoTable" class="table inventory-order-view__table">
                                <thead>
                                    <tr>
                                        <th style="width:5%;">#</th>
                                        <th style="width:15%;"><?= $lang_order['sku'] ?></th>
                                        <th style="width:5%;">
                                            <?= $lang_order['image'] ?></th>
                                        <th style="width:40%;">
                                            <?= $lang_order['product_name'] ?></th>
                                        <th style="width:15%;"><?= $lang_order['category_view'] ?></th>
                                        <th style="width:8%; min-width:6rem;" class="text-center"><?= htmlspecialchars($lang_order['stock_erp'] ?? '', ENT_QUOTES, 'UTF-8') ?></th>
                                        <th style="width:5%;"><?= $lang_order['pack'] ?></th>
                                        <th style="width:5%;"><?= $lang_order['cantidad'] ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $cont = 1; ?>
                                    <?php foreach($detail as $pro):?>

                                    <tr>
                                        <td><?= $cont ?></td>
                                        <td class="text-wrap"><?= strtoupper($pro->sku) ?></td>
                                        <td class="text-wrap">
                                            <?php $this->load->view('company/product/partials/thumbnail', ['listItem' => $pro]); ?>
                                        </td>
                                        <td class="text-wrap">
                                            <a
                                                href="<?= company_url('products/view/'.(int) ($pro->pro_id ?? 0)) ?>"><?= htmlspecialchars((string) ($pro->name ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
                                        </td>
                                        <td class="text-wrap">
                                            <?= $pro->category_name ?>
                                        </td>
                                        <td class="text-center"><?= $pro->stock_ERP <=0? $lang_order["no_counted"]:floatval($pro->stock_ERP) ?></td>

                                        <td class="text-center">

                                            <?php
                                                if ($pro->delivered_pack <= -1) {
                                                    
                                                    echo $value = $lang_order["no_counted"];

                                                }else{
                                                    echo $value = floatval($pro->delivered_pack);
                                                }
                                                
                                            ?>  
                                                    

                                        </td>
                                        <td class="text-center">
                                            <!-- No Contabilizado -->
                                            <?php
                                                if ($pro->qty <= -1) {
                                                   echo  $value = $lang_order["no_counted"];
                                                }else{
                                                   echo $value = floatval($pro->qty);
                                                }
                                            ?>

                                        </td>
                                    </tr>
                                    <?php $cont++; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
var tbl
$(document).ready(function() {
    tbl = $('#infoTable').DataTable({
        "pageLength": 50,
        "language": language,
        "dom": "lrtip",
        "columnDefs": [
            { "searchable": false, "targets": [0, 2] },
            { "orderable": false, "targets": [2] }
        ]
    });

    var $invSearch = $('#inventory-order-view-line-search');
    if ($invSearch.length) {
        $invSearch.on('keyup input search', function() {
            tbl.search($(this).val()).draw();
        });
    }

    activar_popover_imagenes()

    $('#infoTable').on('draw.dt page.dt', function() {
        setTimeout(function() {
            activar_popover_imagenes();
        }, 120);
    });
});
</script>
