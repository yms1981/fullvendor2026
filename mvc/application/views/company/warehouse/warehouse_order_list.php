<!-- partial -->
<div class="container-fluid page-body-wrapper pt-3">
    <div class="main-panel">
        <div class="container-fluid contentHeight">
            <div class="row">
                <div class="col-md-6">
                    <div class="page-header">
                        <h3 class="page-title"> <?= $lang_order['title_warehouse_order'] ?></h3>
                    </div>
                </div>
                <div class="col-md-6 text-right">
                 

                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <!-- filtros -->
                            <div class="row">
                                <div class="col-12 p-4">
                                    <div class="row border">
                                        <label
                                            style="position: relative;top: -0.7em;display: inline;background-color:white;font-weight: 600;padding-right: 0.5em;padding-left: 0.5em;margin-left: 0.5em;font-size:1.1em !important;"><?= $lang_order['seleccionar_por'] ?></label>
                                        <div class="col-12">
                                            <div class="row">
                                                <!-- formulario -->
                                                <form id="form-buscar-filtro" class="col-12 pt-2"
                                                    action="<?= company_url('warehouse/orders') ?>">
                                                    <div class="row">
                                                        <?php 
                                                        $warehouse_id= $this->input->get("warehouse_user") == null ? null : $this->input->get("warehouse_user");
                                                        $customer_id= $this->input->get("customer") == null ? null : $this->input->get("customer");                          
                                                        ?>
                                                        <div class="col-lg-3 col-md-6 col-xl-3 col-sm-12">
                                                            <div id="myselect2" class="form-group">
                                                                <label><?= $lang_order['warehouse_user'] ?></label>
                                                                <select onchange="buscar_porfiltro()"
                                                                    class="form-control js-example"
                                                                    name="warehouse_user">
                                                                    <option value="">
                                                                        <?= strtoupper($lang_clientes['todo']) ?>
                                                                    </option>
                                                                    <?php foreach ($warelist as $wr) { ?>
                                                                    <option
                                                                        <?= ($wr->user_id == $warehouse_id)? 'selected' : ''; ?>
                                                                        value="<?=$wr->user_id?>">
                                                                        <?= strtoupper(trim($wr->first_name.' '.$wr->last_name)) ?>
                                                                    </option>
                                                                    <?php } ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-3 col-md-6 col-xl-3 col-sm-12">
                                                            <div class="form-group">
                                                                <label><?= $lang_order['customer_order'] ?></label>
                                                                <select onchange="buscar_porfiltro()"
                                                                    data-live-search="true"
                                                                    class="form-control selectpicker" name="customer">
                                                                    <option value="">
                                                                        <?= strtoupper($lang_clientes['todo']) ?>
                                                                    </option>
                                                                    <?php foreach ($customers as $cus) {
                                                                        $c = is_array($cus) ? $cus : (array) (is_object($cus) ? get_object_vars($cus) : []);
                                                                        $cid = isset($c['customer_id']) ? (int) $c['customer_id'] : 0;
                                                                        if (! empty($c['customer_name'])) {
                                                                            $custLabel = (string) $c['customer_name'];
                                                                        } else {
                                                                            $biz = trim((string) ($c['business_name'] ?? ''));
                                                                            $nm = trim((string) ($c['name'] ?? ''));
                                                                            if ($biz !== '' && $nm !== '') {
                                                                                $custLabel = $biz . ' (' . $nm . ')';
                                                                            } elseif ($biz !== '') {
                                                                                $custLabel = $biz;
                                                                            } elseif ($nm !== '') {
                                                                                $custLabel = $nm;
                                                                            } else {
                                                                                $custLabel = $cid > 0 ? (string) $cid : '';
                                                                            }
                                                                        }
                                                                        ?>
                                                                    <option
                                                                        <?= ($cid === (int) $customer_id) ? 'selected' : ''; ?>
                                                                        value="<?= $cid ?>">
                                                                        <?= htmlspecialchars(strtoupper($custLabel), ENT_QUOTES, 'UTF-8') ?>
                                                                    </option>
                                                                    <?php } ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <!-- <div class="col-lg-3 col-md-6 col-xl-3 col-sm-12">
                                                          <div id="multi_select" class="form-group">
                                                            <label><?= $lang_order['estado'] ?></label>
                                                            <?php 
                                                              $status_get = $this->input->get("estado") == null ? [] : $this->input->get("estado");
                                                            ?>
                                                            <select id="multiestado" name="estado[]" class="form-control selectpicker" multiple data-select-all-text="<?= $lang_order['select_todoestatdos'] ?>" data-deselect-all-text="<?= $lang_order['deselect'] ?>" data-actions-box="true" data-size="5" data-none-selected-text="<?= $lang_clientes['todo'] ?>">

                                                              <?php foreach($statuslist as $status){ ?>

                                                                <option value="<?= $status->cod_status ?>"  <?= (in_array($status->cod_status, $status_get))? 'selected' : ''; ?>><?= $status->name_status ?></option>                                          
                                                              <?php } ?>

                                                            </select>
                                                          </div>
                                                        </div> -->
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <?php if (! empty($apiError)) : ?>
                                    <div class="alert alert-danger" role="alert">
                                        <?= htmlspecialchars((string) $apiError, ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <?php endif; ?>
                                    <div class="table-responsive">
                                        <style>
                                            .warehouse-orders-list-table>thead>tr>th,
                                            .warehouse-orders-list-table>tbody>tr>td {
                                                text-align: left !important;
                                            }
                                            .warehouse-orders-list-table {
                                                table-layout: fixed;
                                                width: 100%;
                                            }
                                            .warehouse-orders-list-table .warehouse-orders-col-customer {
                                                width: 22%;
                                                min-width: 11rem;
                                                max-width: 20rem;
                                                vertical-align: top;
                                                word-break: break-word;
                                                overflow-wrap: break-word;
                                            }
                                            .warehouse-orders-customer-block a.text-black {
                                                text-decoration: none;
                                            }
                                            .warehouse-orders-customer-block a.text-black:hover {
                                                text-decoration: underline;
                                            }
                                            .warehouse-orders-customer-line {
                                                display: flex;
                                                align-items: flex-start;
                                                gap: 0.35rem;
                                            }
                                            .warehouse-orders-customer-line > .mdi {
                                                flex-shrink: 0;
                                                line-height: 1.35;
                                                margin-top: 1px;
                                            }
                                            .warehouse-orders-customer-line__body {
                                                min-width: 0;
                                                flex: 1 1 auto;
                                            }
                                            .warehouse-orders-list-table .warehouse-orders-col-order {
                                                width: 7%;
                                            }
                                            .warehouse-orders-list-table .warehouse-orders-col-seller,
                                            .warehouse-orders-list-table .warehouse-orders-col-warehouse {
                                                width: 10%;
                                            }
                                            .warehouse-orders-list-table .warehouse-orders-col-date {
                                                width: 15%;
                                            }
                                            .warehouse-orders-list-table .warehouse-orders-col-comments {
                                                width: 11%;
                                            }
                                            .warehouse-orders-list-table .warehouse-orders-col-status {
                                                width: 8%;
                                            }
                                            .warehouse-orders-list-table .warehouse-orders-col-actions {
                                                width: 17%;
                                                min-width: 7.5rem;
                                                white-space: nowrap;
                                            }
                                            /* Fecha creado: sin depender de .badge del tema (a veces deja el texto casi blanco) */
                                            .warehouse-orders-list-table .warehouse-orders-date-created {
                                                display: inline-block;
                                                color: #1a1a1a !important;
                                                background-color: #dee2e6 !important;
                                                border: 1px solid #adb5bd !important;
                                                font-size: 11px !important;
                                                font-weight: 600;
                                                line-height: 1.35;
                                                padding: 0.35em 0.55em;
                                                border-radius: 0.25rem;
                                                max-width: 100%;
                                                white-space: normal;
                                                text-align: left;
                                                vertical-align: middle;
                                            }
                                        </style>
                                        <table class="table warehouse-orders-list-table">
                                            <thead>
                                                <tr>
                                                    <th class="text-left warehouse-orders-col-order"><?= $lang_order['order_number'] ?></th>
                                                    <th class="text-left warehouse-orders-col-customer"><?= $lang_order['customer'] ?></th>
                                                    <th class="text-left warehouse-orders-col-seller"><?= $lang_order['vendedor'] ?></th>
                                                    <th class="text-left warehouse-orders-col-warehouse"><?= $lang_order['warehouse_user'] ?></th>
                                                    <th class="text-left warehouse-orders-col-date"><?= $lang_order['order_date'] ?></th>

                                                    <th class="text-left warehouse-orders-col-comments"><?= $lang_order['comments'] ?></th>
                                                    <th class="text-left warehouse-orders-col-status"><?= $lang_order['status'] ?></th>
                                                    <th class="text-left warehouse-orders-col-actions"><?= $lang_order['action'] ?></th>
                                                </tr>
                                            </thead>
                                            <tbody class="filterOrder_data">
                                                <?php
                                                // Solo fecha (sin hora) en listado de órdenes de almacén
                                                $fullDateFmt = (! empty($format_date) && ! empty($format_date->format_php))
                                                    ? trim((string) $format_date->format_php)
                                                    : '';
                                                $dateFmtPhp = fv_display_date_format();
                                                if ($fullDateFmt !== '') {
                                                    $parts = preg_split('/\s+(?=[HGhg])/i', $fullDateFmt, 2);
                                                    $onlyDate = trim((string) ($parts[0] ?? ''));
                                                    $dateFmtPhp = $onlyDate !== '' ? $onlyDate : fv_display_date_format();
                                                }
                                                ?>
                                                <?php if (empty($list)) : ?>
                                                <tr>
                                                    <td colspan="8" class="text-center text-muted py-4">
                                                        <?= htmlspecialchars((string) ($lang_order['empty_orders'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                                    </td>
                                                </tr>
                                                <?php endif; ?>
                                                <?php foreach ($list as $key => $v): ?>
                                                <?php
                                                    $dateRaw = (string) ($v->created ?? '');
                                                    $dateTs = $dateRaw !== '' ? strtotime($dateRaw) : false;
                                                    if ($dateRaw !== '' && $dateTs === false) {
                                                        $dtTry = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dateRaw);
                                                        if ($dtTry instanceof \DateTimeImmutable) {
                                                            $dateTs = $dtTry->getTimestamp();
                                                        } else {
                                                            $dtIso = date_create_immutable($dateRaw);
                                                            if ($dtIso instanceof \DateTimeImmutable) {
                                                                $dateTs = $dtIso->getTimestamp();
                                                            }
                                                        }
                                                    }
                                                    $updatedRaw = (string) ($v->updated ?? '');
                                                    $updatedTs = $updatedRaw !== '' ? strtotime($updatedRaw) : false;
                                                    if ($updatedRaw !== '' && $updatedTs === false) {
                                                        $dtUp = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $updatedRaw);
                                                        if ($dtUp instanceof \DateTimeImmutable) {
                                                            $updatedTs = $dtUp->getTimestamp();
                                                        } else {
                                                            $dtIsoU = date_create_immutable($updatedRaw);
                                                            if ($dtIsoU instanceof \DateTimeImmutable) {
                                                                $updatedTs = $dtIsoU->getTimestamp();
                                                            }
                                                        }
                                                    }
                                                    $commentsRaw = (string) ($v->order_comments ?? '');
                                                    $commentsDisplay = $commentsRaw !== ''
                                                        ? str_replace('\n', ' ', $this->Helper->replace_comentario($commentsRaw))
                                                        : '';
                                                    $statusCod = (int) ($v->order_status ?? 0);
                                                    $statusLabel = $lang_order['pending'] ?? '';
                                                    $statusColor = '#ffc107';
                                                    if (! empty($statusMap[$statusCod])) {
                                                        $st = is_object($statusMap[$statusCod]) ? $statusMap[$statusCod] : (object) $statusMap[$statusCod];
                                                        $statusLabel = (string) ($st->name_status ?? $st->name_status_english ?? $statusLabel);
                                                        $statusColor = (string) ($st->color ?? $statusColor);
                                                    }
                                                    ?>
                        <tr class="notranslate">
                            <td class="text-left align-middle warehouse-orders-col-order">
                                <div class="form-group text-nowrap">
                            <?php
                            switch ($v->source ?? '') {
                             case 'Catalog':
                             echo '<span class="fa-solid fa-globe text-success"></span>';
                             break;
                             case 'app':
                             echo '<span class="fa-solid fa-mobile text-success"></span>';
                             break;
                             case 'manual':
                             echo '<span class="fa-sharp fa-solid fa-house text-success"></span>';
                             break;
                           }
                           ?>
                            <a href="<?= company_url('warehouse/warehouse_orderview/'.(int) $v->order_id) ?>">
                                <?= htmlspecialchars((string) ($v->order_number ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
                        </div>
                    </td>
                    <td class="text-left text-wrap align-top warehouse-orders-col-customer" style="font-size: 13px;">
                        <?php
                        $orderUrl = company_url('warehouse/warehouse_orderview/'.(int) $v->order_id);
                        $contacto = ! empty($v->nombre_contacto) ? htmlspecialchars((string) $v->nombre_contacto, ENT_QUOTES, 'UTF-8') : null;
                        $empresa = ! empty($v->nombre_cliente) ? htmlspecialchars((string) $v->nombre_cliente, ENT_QUOTES, 'UTF-8') : null;
                        $email = $v->customer_email ?? null;
                        $phone = $v->customer_phone ?? null;
                        $cell = $v->customer_cell_phone ?? null;
                        $address = $v->customer_address ?? null;
                        ?>
                        <div class="warehouse-orders-customer-block text-left">
                            <?php if ($contacto) : ?>
                            <div class="warehouse-orders-customer-line mb-1" title="<?= htmlspecialchars((string) ($lang_order['customer'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <i class="mdi mdi-account-outline text-primary" style="font-size: 14px;"></i>
                                <span class="warehouse-orders-customer-line__body"><a tabindex="-1" class="text-black" href="<?= $orderUrl ?>"><strong><?= strtoupper($contacto) ?></strong></a></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($empresa) : ?>
                            <div class="warehouse-orders-customer-line mb-1" title="<?= htmlspecialchars((string) ($lang_clientes['empresa'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <i class="mdi mdi-domain text-secondary" style="font-size: 14px;"></i>
                                <span class="warehouse-orders-customer-line__body"><a tabindex="-1" class="text-black" href="<?= $orderUrl ?>"><?= strtoupper($empresa) ?></a></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($phone) : ?>
                            <div class="warehouse-orders-customer-line mb-1" title="<?= htmlspecialchars((string) ($lang_order['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <i class="mdi mdi-phone-outline text-info" style="font-size: 14px;"></i>
                                <span class="warehouse-orders-customer-line__body"><a tabindex="-1" class="text-black" href="<?= $orderUrl ?>"><?= htmlspecialchars((string) $phone, ENT_QUOTES, 'UTF-8') ?></a></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($cell) : ?>
                            <div class="warehouse-orders-customer-line mb-1" title="<?= htmlspecialchars((string) ($lang_order['cell'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <i class="mdi mdi-cellphone text-info" style="font-size: 14px;"></i>
                                <span class="warehouse-orders-customer-line__body"><a tabindex="-1" class="text-black" href="<?= $orderUrl ?>"><?= htmlspecialchars((string) $cell, ENT_QUOTES, 'UTF-8') ?></a></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($email) : ?>
                            <div class="warehouse-orders-customer-line mb-1" title="<?= htmlspecialchars((string) ($lang_order['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <i class="mdi mdi-email-outline text-success" style="font-size: 14px;"></i>
                                <span class="warehouse-orders-customer-line__body"><a href="mailto:<?= htmlspecialchars((string) $email, ENT_QUOTES, 'UTF-8') ?>" class="text-dark"><?= htmlspecialchars((string) $email, ENT_QUOTES, 'UTF-8') ?></a></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($address) : ?>
                            <div class="warehouse-orders-customer-line mb-0 text-muted" style="font-size: 12px;" title="<?= htmlspecialchars((string) ($lang_order['address'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <i class="mdi mdi-map-marker-outline text-danger" style="font-size: 14px;"></i>
                                <span class="warehouse-orders-customer-line__body"><a tabindex="-1" class="text-muted" href="<?= $orderUrl ?>"><?= htmlspecialchars((string) $address, ENT_QUOTES, 'UTF-8') ?></a></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="text-left text-wrap align-middle warehouse-orders-col-seller"><?= htmlspecialchars(strtoupper((string) ($v->seller ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="text-left text-wrap align-middle warehouse-orders-col-warehouse"><?= htmlspecialchars(strtoupper((string) ($v->warehouse_user ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="text-left text-wrap align-middle warehouse-orders-col-date">
                        <?php
                        $lblCreated = trim((string) ($lang_order['fecha_creacion_order'] ?? $lang_order['order_created'] ?? ''));
                        ?>
                        <?php if ($dateTs) : ?>
                        <span class="badge warehouse-orders-date-created mb-1"><?php if ($lblCreated !== '') : ?><?= htmlspecialchars($lblCreated, ENT_QUOTES, 'UTF-8') ?>: <?php endif; ?><?= date($dateFmtPhp, $dateTs) ?></span><br>
                        <?php endif; ?>
                        <?php if ($updatedTs) : ?>
                        <span class="badge badge-warning" style="font-size: 11px !important;"><?= htmlspecialchars((string) ($lang_order['updated'] ?? ''), ENT_QUOTES, 'UTF-8') ?>: <?= date($dateFmtPhp, $updatedTs) ?></span>
                        <?php endif; ?>
                        <?php if (! $dateTs && ! $updatedTs && $dateRaw !== '') : ?>
                        <?php
                        $dateRawOnly = $dateRaw;
                        if (preg_match('/^(\d{4}-\d{2}-\d{2})[\sT]\d/', $dateRaw, $mDate)) {
                            $dateRawOnly = $mDate[1];
                        }
                        ?>
                        <span class="warehouse-orders-date-created"><?php if ($lblCreated !== '') : ?><?= htmlspecialchars($lblCreated, ENT_QUOTES, 'UTF-8') ?>: <?php endif; ?><?= htmlspecialchars($dateRawOnly, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-left text-wrap align-middle warehouse-orders-col-comments">
                        <?= htmlspecialchars($commentsDisplay, ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td class="text-left text-wrap align-middle warehouse-orders-col-status">
                                  <div
                                      class="badge bg-secondary text-white p-1 pl-2 pr-2 border rounded text-left">
                                      <span class='roundblock mb-1'
                                          style='background: <?= htmlspecialchars($statusColor, ENT_QUOTES, 'UTF-8') ?>;'></span>
                                      <span class="text-left text-wrap"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                  </div>
                              </td>
                              <td class="text-left align-middle warehouse-orders-col-actions">
                                  <div class="form-group text-nowrap">
                                      <a href="<?= company_url('warehouse/warehouse_orderview/'.(int) $v->order_id) ?>"
                                          class="btn btn-warning btn-sm"
                                          title="<?= htmlspecialchars($lang_order['view'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                          <i class="mdi mdi-eye"></i>
                                      </a>
                                      <form method="post" action="<?= company_url('orders/generatePdf') ?>" target="_blank" class="d-inline">
                                          <input type="hidden" name="order_id" value="<?= (int) $v->order_id ?>">
                                          <input type="hidden" name="delivery_notes" value="">
                                          <input type="hidden" name="notas" value="sin">
                                          <button type="submit" class="btn btn-success btn-sm"
                                              title="<?= htmlspecialchars($lang_order['imprimir_orden'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                              <i class="fa-solid fa-file-invoice"></i>
                                          </button>
                                      </form>
                                      <a href="<?= company_url('warehouse/returnorden/'.(int) $v->order_id) ?>"
                                          class="btn btn-secondary btn-sm"
                                          title="<?= htmlspecialchars($lang_order['warehouse_list_return_order'] ?? ($lang_order['return'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                          <i class="fa-solid fa-rotate-left"></i>
                                      </a>
                                  </div>
                              </td>
                          </tr>
                          <?php endforeach ?>


                            </tbody>
                        </table>
                    </div>
                    <?= $paginate ?>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- content-wrapper ends -->
<?php
$warehouseReturnLang = isset($warehouse_return_lang) && is_array($warehouse_return_lang) ? $warehouse_return_lang : [];
?>
<script>
window.warehouseReturnLang = <?= json_encode($warehouseReturnLang, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

$(document).on('click', '.js-warehouse-return-order', function (e) {
    e.preventDefault();
    var $btn = $(this);
    if ($btn.prop('disabled')) {
        return;
    }
    var orderId = $btn.data('order-id');
    var L = window.warehouseReturnLang || {};
    var base = (typeof companyUrl !== 'undefined' && companyUrl) ? companyUrl : ($('#companyUrl').val() || '');
    if (base && base.slice(-1) !== '/') {
        base += '/';
    }
    var reEnableBtn = true;
    $btn.prop('disabled', true);
    $.ajax({
        url: base + 'warehouse/returnorden_ajax',
        type: 'POST',
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        data: { order_id: orderId }
    }).done(function (res) {
        var ok = res && res.success;
        var msg = (res && res.message) ? res.message : (L.error_failed || '');
        if (ok) {
            reEnableBtn = false;
        }
        swal({
            title: msg,
            text: '',
            icon: ok ? 'success' : 'error',
            button: {
                text: L.btn_ok || 'OK',
                value: true,
                visible: true,
                className: 'btn btn-primary'
            }
        }).then(function () {
            if (ok) {
                window.location.reload();
            }
        });
    }).fail(function (xhr) {
        var msg = L.error_failed || 'Error';
        if (xhr.responseJSON && xhr.responseJSON.message) {
            msg = xhr.responseJSON.message;
        }
        swal({
            title: msg,
            text: '',
            icon: 'error',
            button: {
                text: L.btn_ok || 'OK',
                value: true,
                visible: true,
                className: 'btn btn-primary'
            }
        });
    }).always(function () {
        if (reEnableBtn) {
            $btn.prop('disabled', false);
        }
    });
});

function buscar_porfiltro() {
    $('#form-buscar-filtro').submit()
}


$(document).ready(function(e) {

    $(".js-example").select2({
        dropdownParent: $("#myselect2"),
        width: '100%'
    });

    $(document).on('hide.bs.dropdown', '#multi_select .dropdown', function(e) {
        buscar_porfiltro()

    })

});
</script>