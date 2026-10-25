<?php 
$todos =$_GET['input_todos'];

if ($todos) {
    $clase = 'alert alert-warning alert-dismissible fade show';
}else{
    $clase = 'alert alert-warning alert-dismissible fade';
}

?>

<style>
/* Cabecera grilla: solo color de letra blanco (sin forzar relleno de fondo) */
.inventory-orders-list-table thead th {
    color: #fff !important;
    vertical-align: middle;
}
.inventory-orders-list-table thead th a.sort,
.inventory-orders-list-table thead th a.sort u {
    color: #fff !important;
}
.inventory-orders-list-table thead th span,
.inventory-orders-list-table thead th label {
    color: #fff !important;
}
/* Número de orden por fila: negro */
.inventory-orders-list__order-number {
    color: #000 !important;
}
.inventory-orders-list__order-number:hover {
    color: #111 !important;
}
</style>
<div class="container-fluid page-body-wrapper">

    <div class="main-panel">
        <div class="container-fluid pt-3">
            <div class="row">
                <div class="col-md-6">
                    <div class="page-header">
                        <h3 class="page-title"><?= strtoupper($lang_inventory['list_orders']) ?></h3>
                    </div>
                </div>
                <div class="col-md-6 text-right">
                    <a href="<?= company_url('inventory/order/add') ?>"
                        class="btn btn-success mt-4 mt-sm-0 btn-icon-text px-3"><i
                            class="mdi mdi-plus-circle"></i><?= $lang_inventory['add-new-order'] ?></a>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-10">
                            <div class="form-group">
                                <label class="d-block">&nbsp;</label>
                                <!-- exportar -->
                                <button tabindex="-1" class="btn btn-success mt-4 mt-sm-0 btn-icon-text px-3 mb-1"
                                    data-toggle="modal" data-target="#modal_export"><i class="mdi mdi-download"></i>
                                    <?= $lang_order['bnExport'] ?> </button>
                            </div>

                        </div>
                        <div class="col-2"></div>
                        <div class="col-12">
                            <!-- filtros -->
                            <div class="row">
                                <div class="col-12 p-4">
                                    <form id="form-buscar-filtro" action="<?= company_url('inventory/order') ?>"
                                        class="row border">
                                        <label class="panel-border">Seleccionar</label>

                                        <input id="input-todos" hidden name="input_todos"
                                            value="<?= ($todos)?'todos':'pagina'?>">
                                        <div class="col-12 row">
                                            <div class="col-lg-3 col-sm-12">
                                                <div class="form-group">
                                                    <label><?= $lang_order['desde_fecha'] ?></label>
                                                    <?= fv_render_company_date_input(
                                                        'desde_fecha',
                                                        $this->input->get('desde_fecha') ?: date('Y-m-01'),
                                                        ['tabindex' => '1', 'data-fv-autosubmit' => '1']
                                                    ) ?>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-sm-12">
                                                <div class="form-group">
                                                    <label><?= $lang_order['hasta_fecha'] ?></label>
                                                    <?= fv_render_company_date_input(
                                                        'hasta_fecha',
                                                        $this->input->get('hasta_fecha') ?: date('Y-m-d'),
                                                        ['tabindex' => '2', 'data-fv-autosubmit' => '1']
                                                    ) ?>
                                                </div>
                                            </div>
                                            <div id="myselect2" class="col-lg-3 col-sm-12">
                                                <div class="form-group">
                                                    <label><?= $lang_order['warehouse_user'] ?></label>
                                                    <select id="warehouse_user" class="form-control js-example"
                                                        name="warehouse_user" onchange="buscar_porfiltro()"
                                                        data-width="100%">
                                                        <option value="">
                                                            <?= strtoupper($lang_clientes['todo']) ?></option>
                                                        <?php foreach ($warelist as $wr) { ?>
                                                        <option
                                                            <?= $this->input->get("warehouse_user")==$wr->user_id?'selected':'' ?>
                                                            value="<?=$wr->user_id?>">
                                                            <?= strtoupper(trim($wr->first_name.' '.$wr->last_name)) ?>
                                                        </option>
                                                        <?php } ?>
                                                    </select>
                                                    <input hidden id="order_type" name="order_type"
                                                        value="<?= $this->input->get('order_type_general')!=''? $this->input->get('order_type_general'): ''?>">
                                                    <input hidden id="order_sort" name="sort"
                                                        value="<?= $this->input->get('order_type_sort')!=''? $this->input->get('order_type_sort'): ''?>">
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-sm-12">
                                                <div id="multi_select" class="form-group">
                                                    <label><?= $lang_order['estado'] ?></label>
                                                    <?php $status_get = $this->input->get("estado") == null ? [] : $this->input->get("estado");?>
                                                    <select tabindex="5" id="multiestado" onblur="buscar_porfiltro()"
                                                        name="estado[]" class="form-control selectpicker" multiple
                                                        data-select-all-text="<?= $lang_order['select_todoestatdos'] ?>"
                                                        data-deselect-all-text="<?= $lang_order['deselect'] ?>"
                                                        data-actions-box="true" data-size="5"
                                                        data-none-selected-text="<?= $lang_clientes['todo'] ?>">

                                                        <?php foreach($statuslist as $status){ ?>

                                                        <option value="<?= $status->cod_status ?>"
                                                            <?= (in_array($status->cod_status, $status_get))? 'selected' : ''; ?>>
                                                            <?= $status->name_status ?></option>
                                                        <?php } ?>

                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-lg-3 col-sm-12">
                                            </div>
                                            <div class="col-lg-3 col-sm-12">
                                            </div>
                                            <div class="col-lg-3 col-sm-12">
                                            </div>
                                            <div class="col-lg-3 col-sm-12">
                                                <?= $lang_clientes['total_ordenes'] ?>:
                                                <b><?= $ordenes_totales ?></b><br>
                                                <?= $lang_clientes['ordenes_filtradas'] ?>: <b
                                                    id="countprd"><?= $count_filtered ?></b>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">

                            <div id="alerta" class="<?= $clase ?>" role="alert"
                                <?= ($todos)?'':'style="display: none;"'?>>
                                <strong><?= sprintf($lang_order['alert_cantidad_ordenes'], $count_filtered,$count_filtered) ?></strong>

                                <a id="link-todos"
                                    href="javascript:void(0)"><?= ($todos)?$langprods['desmarcar_todos']:$langprods['marcar_todos'] ?></a>

                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>

                            <div class="table-responsive" style="overflow-y: none;">
                                <table class="table inventory-orders-list-table">
                                    <thead>
                                        <tr>
                                        <th class="text-center">
                                            <span class="d-flex">
                                                <input
                                                    title="<?= ($todos)?$lang_order['desmarcar_todos']:$lang_order['toltip_paginas'] ?>"
                                                    type="checkbox" <?= ($todos)?'checked':''?> name=""
                                                    id="ckbCheckAll">&nbsp;<?= $lang_order['all'] ?>
                                            </span>
                                        </th>

                                        <th style="text-align: left; width:20%;"><a tabindex="-1"
                                                data-sort="orden_number"
                                                data-order_type="<?= $this->input->get('order_type_order_number') ?>"
                                                href="javascript:void(0)"
                                                class="sort"><u><?= $lang_order['order_number'] ?></u></a>
                                        </th>
                                        <th style="width: 30%;"><a tabindex="-1" data-sort="orden_warehouseuser"
                                                data-order_type="<?= $this->input->get('order_type_warehouseuser') ?>"
                                                href="javascript:void(0)"
                                                class="sort"><u><?= $lang_order['warehouse_user'] ?></u></a>
                                        </th>
                                        <th class="text-center"><a data-sort="orden_date"
                                                data-order_type="<?= $this->input->get('order_type_date') ?>"
                                                tabindex="-1" href="javascript:void(0)"
                                                class="sort"><u><?= $lang_order['order_date'] ?></u></a>
                                        </th>
                                        <th style="width: 30%;" class="text-center" style="text-align: left">
                                            <a tabindex="-1" data-sort="orden_comments"
                                                data-order_type="<?= $this->input->get('order_type_comments') ?>"
                                                href="javascript:void(0)"
                                                class="sort"><u><?= $lang_order['comments'] ?></u>
                                            </a>
                                        </th>
                                        <th style="width: 20%;">&nbsp;&nbsp;&nbsp;&nbsp;<?= $lang_order['status'] ?>
                                        </th>
                                        <th style="width: 5%;"><?= $lang_order['action'] ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($orderlist as $ord): ?>
                                        <tr>
                                            <td>                                                
                                                <input type="checkbox" name="orderIds[]" <?= ($todos)?'checked':''?>
                                                    value="<?=$ord->order_id?>" class="checkBoxClass">
                                            </td>
                                            <td>
                                                <a class="inventory-orders-list__order-number"
                                                    href="<?= company_url('inventory/order/view/'.$ord->order_id) ?>">
                                                    <?= htmlspecialchars((string) ($ord->order_number ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                                </a>
                                            </td>
                                            <?php
                                                $warehouseCell = trim((string) ($ord->warehouse_user ?? $ord->warehouseUser ?? ''));
                                                $warehouseCell = strtoupper($warehouseCell);
                                            ?>
                                            <td><?= htmlspecialchars($warehouseCell, ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="text-nowrap"><?= date('M-d-Y', strtotime($ord->created)) ?></td>
                                            <td><?= str_replace('\n'," ",$this->Helper->replace_comentario($ord->order_comments)) ?></td>
                                            <td>
                                                <?php
                                                    $lookup = (isset($statusLookup) && is_array($statusLookup) && $statusLookup !== [])
                                                        ? $statusLookup
                                                        : $statuslist;
                                                    $color = '#5b9d9d';
                                                    $name_status = '';
                                                    $statusCodes = array_column($lookup, 'cod_status');
                                                    $statusIdx = array_search($ord->order_status, $statusCodes, false);
                                                    if ($statusIdx !== false) {
                                                        $color = (string) ($lookup[$statusIdx]->color ?? $color);
                                                        $name_status = ((int) $ord->order_status === 9)
                                                            ? (string) ($lang_order['pending'] ?? $lookup[$statusIdx]->name_status ?? 'Pendiente')
                                                            : (string) ($lookup[$statusIdx]->name_status ?? '');
                                                    }
                                                ?>
                                                <div class="badge bg-secondary text-white p-1 pl-2 pr-2 border rounded">
                                                    <span class='roundblock mb-1'
                                                        style='background: <?= htmlspecialchars($color, ENT_QUOTES, 'UTF-8') ?>;'></span>
                                                    <span class="text-left text-wrap"><?= htmlspecialchars($name_status, ENT_QUOTES, 'UTF-8') ?></span>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <a tabindex="-1"
                                                    href="<?= company_url('inventory/order/view/'.$ord->order_id) ?>"
                                                    class="btn btn-warning btn-sm" data-toggle="tooltip"
                                                    title="<?= $lang_order['view_tol'] ?>">
                                                    <i class="mdi mdi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <div class="paginate">
                                    <?=$paginate?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
    
    $data_modalExport = ["id_formFilter" => "form-buscar-filtro",
        "url_send" => "/inventory/order/export_dataFile",
        "title_modal" => $lang_order["modal_export_title"],
        "options_order"=>true
    ];
    
    $this->load->view("company/layouts/modal_exportar_orden",  $data_modalExport);
?>

<script>

// Importa la biblioteca xlsx.js
$(document).ready(function() {

    $(".js-example").select2({
        dropdownParent: $("#myselect2")
    });

    $(document).on('click', '.sort', function(e) {
        e.preventDefault()

        let order_type = $(this).data('order_type'),
            order_sort = $(this).data('sort')

        $('#order_type').val(order_type)
        $('#order_sort').val(order_sort)
        $('#form-buscar-filtro').submit()

    });

    $(document).on('hide.bs.dropdown', '#multi_select .dropdown', function(e) {

        buscar_porfiltro()
    })

    $(document).on('change', 'input[name="desde_fecha"], input[name="hasta_fecha"]', function() {
        $('#form-buscar-filtro').submit()
    })

    // mostrar alerta todos
    $(document).on('change', '#ckbCheckAll', function(e) {

        if (this.checked) {

            $(this).attr('title', '<?= $langprods['deseleccionar_toda_lapagina'] ?>');
            $('.alert').show()
            $('.alert').addClass('show')
            $('#input-todos').val('pagina')
            $('#link-todos').text('<?= $langprods['marcar_todos'] ?>')

        } else {

            $('#input-todos').val('pagina')
            $(this).attr('title', '<?= $langprods['toltip_paginas'] ?>');
            $('.alert').hide()

        }

    })


    //marcar todos  link-todos
    $(document).on('click', '#link-todos', function(e) {

        let input_todos = $('#input-todos')
        if (input_todos.val() == 'pagina') {

            input_todos.val('todos')
            $(this).text('<?= $langprods['desmarcar_todos'] ?>')

        } else {

            input_todos.val('pagina')
            $('#ckbCheckAll').attr('title', '<?= $langprods['toltip_paginas'] ?>');
            $(this).text('<?= $langprods['marcar_todos'] ?>')
        }

    })


    function descargarCSV() {
        // Datos CSV de ejemplo
        const datosCSV = "Nombre;Apellido;Edad\nJohn;Doe;30\nJane;Smith;25";

        // Crea un elemento <a> para descargar el archivo
        const enlace = document.createElement("a");
        enlace.href = "data:text/csv;charset=utf-8," + encodeURIComponent(datosCSV);
        enlace.target = "_blank";
        enlace.download = "datos.csv"; // Nombre del archivo CSV

        // Simula un clic en el enlace para iniciar la descarga
        document.body.appendChild(enlace);
        enlace.click();

        // Elimina el elemento <a> después de la descarga
        document.body.removeChild(enlace);
    }





});

const buscar_porfiltro = () => {
    $('#form-buscar-filtro').submit()
}
</script>