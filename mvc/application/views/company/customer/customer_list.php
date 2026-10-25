<?php

$text_search =$_GET['text_search'];
$get_grupo =$_GET['get_grupo'];
$get_vendedor =$_GET['get_vendedor'];
$get_term =$_GET['get_term'];

$order_type_id =$_GET['type_id'];
$order_type_name =$_GET['type_name'];
$order_type_business_name =$_GET['type_business_name'];
$order_type_email =$_GET['type_email'];

$order_type_general =$_GET['order_type_general'];
$order_type_sort =$_GET['order_type_sort'];
$todos = $_GET['input_todos'];

?>

<?php if($todos){?>
<form id="form-full-vendor" action="<?=company_url('customer/exportCSVcustomers?separador=puntoycoma&cantidad=todos')?>"
    method="post">
</form>
<?php }else{?>
<form id="form-full-vendor"
    action="<?=company_url('customer/exportCSVcustomers?separador=puntoycoma&cantidad=pagina')?>" method="post">
</form>
<?php }?>
<!-- partial -->
<div class="container-fluid page-body-wrapper pt-3">
    <div class="main-panel">
        <div class="container-fluid contentHeight">
            <div class="row">
                <div class="col-md-2">
                    <div class="page-header">
                        <h3 class="page-title"> <?= strtoupper($lang_clientes["titulo_lists"]) ?></h3>
                    </div>
                </div>
                <div class="col-md-10 text-right">
                    <a href="<?=company_url('customer/add')?>" class="btn btn-success">
                        <i class="mdi mdi-plus-circle"></i> <?= $lang_clientes["add"] ?>
                    </a>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <a href="<?= company_url('customers/csvImport') ?>"
                                    class="btn btn-success mt-4 mt-sm-0 btn-icon-text px-3 mb-1"><i
                                        class="mdi mdi-import"></i>
                                    <?= $lang_clientes["importar"] ?></a>
                                <button type="button" class="btn btn-success mt-4 mt-sm-0 btn-icon-text px-3 mb-1"
                                    data-toggle="modal" data-target="#modal_export"><i class="mdi mdi-download"></i>
                                    <?= $lang_clientes["exportar"] ?></button>
                                <button type="button" class="btn btn-success mt-4 mt-sm-0 btn-icon-text px-3 mb-1"
                                    onclick="asign_seller()"> <?= $lang_clientes['bnAssignseller'] ?> </button>
                                <button type="button" class="btn btn-success mt-4 mt-sm-0 btn-icon-text px-3 mb-1"
                                    onclick="remove_seller()"> <?= $lang_clientes['btnRemoveSeller'] ?> </button>
                                <a href="<?= company_url('terms/lists')?>"
                                    class="btn btn-success mt-4 mt-sm-0 btn-icon-text px-3 mb-1"><?= $lang_clientes["term_pago"] ?></a>
                                <a href="<?= company_url('userprofile/lists')?>"
                                    class="btn btn-success mt-4 mt-sm-0 btn-icon-text px-3 mb-1"><?= $lang_clientes["user_profile"] ?></a>
                                <a href="<?= company_url('groups')?>"
                                    class="btn btn-success mt-4 mt-sm-0 btn-icon-text px-3 mb-1"><?= $lang_clientes["grupos"] ?></a>
                                <a href="javascript:void(0)" onclick="getCommonCustomer();"
                                    class="btn btn-success mt-4 mt-sm-0 btn-icon-text px-3 mb-1"><?= $lang_clientes["send_catalog"] ?></a>
                                <button type="button" onclick="eliminarClienteGrupo()"
                                    class="btn btn-success mt-4 mt-sm-0 btn-icon-text px-3 mb-1"><?= $lang_clientes["eliminar_grupo"] ?>
                                </button>
                            </div>
                        </div>
                        <div class="col-12 p-4">
                            <div class="row border">
                                <label
                                    style="position: relative;top: -0.7em;display: inline;background-color:white;font-weight: 600;padding-right: 0.5em;padding-left: 0.5em;margin-left: 0.5em;font-size:1.1em !important;"><?= $langprods['seleccionar_por'] ?></label>
                                <form class="col-12" id="form-buscar-filtro"
                                    action="<?= company_url('customer/lists') ?>">
                                    <div class="row">
                                        <div class="col-lg-2 col-md-6 col-xl-2 col-sm-12">
                                            <div class="form-group">
                                                <label><?= $langprods['colmn_status_filtro'] ?></label>
                                                <!-- filtro grupo -->
                                                <?php
                                                  $status_found = $this->input->get('status');
                                                ?>
                                                <select class="form-control notranslate" name="status"
                                                    onchange="buscar_porfiltro()">
                                                    <option <?= $status_found=='todos'?'selected':''  ?> value="todos">
                                                        <?= strtoupper($lang_clientes['todo']) ?></option>
                                                    <option <?= $status_found=="todos"?'':'selected' ?> value="1">
                                                        <?= strtoupper($langprods['select_actives']) ?></option>
                                                    <option <?= $status_found=='0'?'selected':'' ?> value="0">
                                                        <?= strtoupper($langprods['select_desactives']) ?></option>
                                                </select>
                                                <input id="input-todos" name="input_todos"
                                                    value="<?= $this->input->get("input_todos")=="todos"?'todos':'pagina'?>"
                                                    hidden>
                                            </div>
                                            <!--
                                            <div class="form-group">
                                                <label for="">State</label>
                                                <input type="text" name="state_search"
                                                    onkeyup="reemplazar($(this).val(),event)" class="form-control"
                                                    placeholder="<?= $langprods['caja_text_search'] ?>"
                                                    >
                                            </div>
                                            -->

                                        </div>
                                        <div class="col-lg-2 col-md-6 col-xl-2 col-sm-12">
                                            <div class="form-group">
                                                <label><?= $lang_clientes['grupos'] ?></label>
                                                <!-- filtro grupo -->
                                                <select class="form-control notranslate" name="grupoId"
                                                    onchange="buscar_porfiltro()">
                                                    <option
                                                        <?= $get_grupo != '' ? $get_grupo =='todo' ? 'selected' : '' : 'selected'  ?>
                                                        value="todo"><?= strtoupper($lang_clientes['todo']) ?></option>
                                                    <option
                                                        <?= $get_grupo != '' ? $get_grupo =='vacio' ? 'selected' : '' : ''  ?>
                                                        value="vacio"><?= strtoupper($lang_clientes['option_vacio']) ?>
                                                    </option>
                                                    <?php foreach ($grouplist as $group) { ?>
                                                    <option
                                                        <?= $get_grupo != null ? $get_grupo==$group->group_id?'selected':'':''  ?>
                                                        value="<?=$group->group_id?>"><?= strtoupper($group->name)?>
                                                    </option>
                                                    <?php } ?>
                                                </select>

                                            </div>

                                            <input hidden id="order_type" name="order_type"
                                                value="<?= $order_type_general!=''? $order_type_general : ''?>">
                                            <input hidden id="order_sort" name="sort"
                                                value="<?= $order_type_sort!=''? $order_type_sort : ''?>">
                                        </div>
                                        <div class="col-lg-2 col-md-6 col-xl-2 col-sm-12">
                                            <div class="form-group">
                                                <label><?= $lang_clientes['vendedores'] ?></label>
                                                <!-- filtro vendedores -->
                                                <select class="form-control notranslate" name="vendedorId"
                                                    onchange="buscar_porfiltro()">
                                                    <option
                                                        <?= $get_vendedor != '' ? $get_vendedor =='todo' ? 'selected' : '' : 'selected'  ?>
                                                        value="todo"><?= strtoupper($lang_clientes['todo']) ?></option>
                                                    <option
                                                        <?= $get_vendedor != '' ? $get_vendedor =='vacio' ? 'selected' : '' : ''  ?>
                                                        value="vacio"><?= strtoupper($lang_clientes['option_vacio']) ?>
                                                    </option>
                                                    <?php foreach ($vendedorlist as $vendedor) { ?>
                                                    <option
                                                        <?= $get_vendedor != null ? $get_vendedor==$vendedor->user_id?'selected':'':''  ?>
                                                        value="<?=$vendedor->user_id?>">
                                                        <?= strtoupper($vendedor->first_name . ' ' . $vendedor->last_name)?>
                                                    </option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-lg-3 col-md-6 col-xl-3 col-sm-12">
                                            <div class="form-group">
                                                <label><?= $lang_clientes['Termino_Pago'] ?></label>
                                                <!-- filtro termino de pago -->
                                                <select class="form-control notranslate" name="termId"
                                                    onchange="buscar_porfiltro()">
                                                    <option
                                                        <?= $get_term != '' ? $get_term =='todo' ? 'selected' : '' : 'selected'  ?>
                                                        value="todo"><?= strtoupper($lang_clientes['todo']) ?></option>
                                                    <option
                                                        <?= $get_term != '' ? $get_term =='vacio' ? 'selected' : '' : ''  ?>
                                                        value="vacio"><?= strtoupper($lang_clientes['option_vacio']) ?>
                                                    </option>
                                                    <?php foreach ($termlist as $termino) { ?>
                                                    <option
                                                        <?= $get_term != null ? $get_term==$termino->term_id?'selected':'':''  ?>
                                                        value="<?=$termino->term_id?>"><?= strtoupper($termino->name)?>
                                                    </option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>


                                        <div class="col-lg-3 col-md-12 col-xl-3 col-sm-12">

                                            <div class="form-group">
                                                <label>&nbsp;</label>
                                                <div class="input-group mb-3">
                                                    <input type="text" name="prsearch"
                                                        onkeyup="reemplazar($(this).val(),event)" class="form-control"
                                                        placeholder="<?= $langprods['caja_text_search'] ?>"
                                                        value="<?= $text_search!= null ? $text_search : '' ?>">
                                                    <div id="btn-submit-textsearch" class="btn btn-light">
                                                        <span id="basic-addon1"><i
                                                                class="fa-sharp fa-solid fa-magnifying-glass"></i></span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div>
                                                <?= $lang_clientes["total_clientes"] .': ' ?><b><?= $clientescount ?></b>
                                                <br>
                                                <?= $lang_clientes["total_clientes_filtrados"] .': '?><b><?= $cantidad_registros_filtrados ?></b>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="col-12">
                            <?php
                              if ($todos) {
                              $clase = 'alert alert-warning alert-dismissible fade show';
                            }else{
                              $clase = 'alert alert-warning alert-dismissible fade';
                            }
                            ?>
                            <div id="alerta" class="<?= $clase ?>" role="alert"
                                <?= ($todos)?'':'style="display: none;"'?>>
                                <strong><?= sprintf($lang_clientes['alert_cantidad_clientes'], count($totcustomers),count($totcustomers)) ?></strong>
                                <a id="link-todos"
                                    href="javascript:void(0)"><?= ($todos)?$langprods['desmarcar_todos']:$langprods['marcar_todos'] ?></a>


                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <!-- tabla listado -->
                            <!-- id="customer-listing" -->
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th class="text-wrap" style="width: 2%;vertical-align: top;">
                                                <span class="d-flex">
                                                    <input
                                                        title="<?= ($todos)?$langprods['desmarcar_todos']:$langprods['toltip_paginas'] ?>"
                                                        type="checkbox" <?= ($todos)?'checked':''?> name=""
                                                        id="ckbCheckAll">&nbsp;<?= $lang_clientes['all'] ?>
                                                </span>
                                            </th>

                                            <th class="text-wrap" style="width: 2%;vertical-align: top;">
                                                <a data-sort="id" data-order_type="<?= $order_type_id ?>"
                                                    href="javascript:void(0)" class="sort"><u>Id</u></a>
                                            </th>

                                            <th class="text-wrap" style="width: 2%;vertical-align: top;">Id ERP</th>

                                            <!-- <th class="text-wrap" style="width: 10%;vertical-align: top;">
                                                <a data-sort="name" data-order_type="<?= $order_type_name ?>"
                                                    href="javascript:void(0)"
                                                    class="sort"><u><?= $lang_clientes["nombre"] ?></u></a>
                                            </th> -->

                                            <th class="text-wrap" style="width: 10%;vertical-align: top;">
                                                <a data-sort="business_name"
                                                    data-order_type="<?= $order_type_business_name ?>"
                                                    href="javascript:void(0)"
                                                    class="sort"><u><?= $lang_clientes["nombre_empresa"] ?></u></a>
                                            </th>
                                            <!-- <th class="text-wrap" style="width: 10%;vertical-align: top;">
                                                <?= $lang_clientes["phones"] ?></th> -->

                                            <th class="text-wrap" style="width: 10%;vertical-align: top;">
                                                <a data-sort="email" data-order_type="<?= $order_type_email ?>"
                                                    href="javascript:void(0)"
                                                    class="sort"><u><?= $lang_clientes["email"] ?></u></a>
                                            </th>

                                            <th class="text-wrap" style="width: 10%;vertical-align: top;">
                                                <?= $lang_clientes["group_customer"] ?>
                                            </th>

                                            <th class="text-wrap" style="width: 8%;vertical-align: top;">
                                                <?= $lang_clientes["col_seller"] ?>
                                            </th>
                                            <!-- <th class="text-wrap" style="width: 10%;vertical-align: top;">
                                                <?= $lang_clientes["created"] ?></th> -->
                                            <th class="text-wrap" style="width: 10%;vertical-align: top;">
                                                <?= $langprods["estado_nuevo"] ?></th>
                                            <th class="text-wrap" style="width: 10%;vertical-align: top;">
                                                <?= $lang_clientes["enviar_catalogo"] ?>
                                            </th>
                                            <th class="text-wrap" style="width: 10%;vertical-align: top;">
                                                <?= $lang_clientes["accion"] ?></th>
                                        </tr>
                                    </thead>
                                    <tbody class="getallcust">
                                        <?php $i = 1; ?>
                                        <?php foreach ($list as $key => $v): ?>
                                        <?php
                                          $group=$this->mymodel->get_by('customer_groups', true,array('group_id'=>$v->group_id));
                                          $sellers=$this->mymodel->get_by('sellers', true,array('user_id'=>$v->user_id));
                                        ?>
                                        <tr class="<?= ($v->customer_status == 0) ? 'disabled' : '' ?>">
                                            <td style="vertical-align: top;" class="text-wrap"><input type="checkbox"
                                                    name="customerIds[]" <?= ($todos)?'checked':''?>
                                                    value="<?=@$v->customer_id?>" class="checkBoxClass"> <?= $i ?>
                                            </td>
                                            <td style="vertical-align: top;" class="text-wrap">
                                                <a href="<?= company_url('customer/view/'.$v->customer_id) ?>"
                                                    data-toggle="tooltip"
                                                    title="View Income"><u><?= @$v->customer_id ?></u></a>
                                            </td>
                                            <td style="vertical-align: top;" class="text-wrap"><?= $v->cust_id_kor ?>
                                            </td>
                                            <!-- <td style="vertical-align: top;" class="text-wrap">
                                                <a href="<?= company_url('customer/view/'.$v->customer_id) ?>"
                                                    data-toggle="tooltip"
                                                    title="View Income"><u><?= @$v->name ?></u></a>
                                                <?php if ($v->catalog_emails == 0) { ?>
                                                <i class="mdi mdi-email-lock text-danger"></i>
                                                <?php } ?>
                                            </td> -->
                                            <td style="vertical-align: top;" class="text-wrap"><a
                                                    href="<?= company_url('customer/view/'.$v->customer_id) ?>"
                                                    data-toggle="tooltip"
                                                    title="View Income"><u><?= strtoupper($v->business_name) ?></u></a>
                                            </td>
                                            <!-- <td style="vertical-align: top;" class="text-wrap"><?= @$v->phone ?> </td> -->
                                            <td style="vertical-align: top;">
                                                <div style="width: 100px;overflow-wrap: break-word;" class="text-wrap">
                                                    <a href="<?= company_url('customer/view/'.$v->customer_id) ?>"
                                                        data-toggle="tooltip" title="View Income">
                                                        <u><?= $v->email ?></u>
                                                    </a>
                                                </div>
                                            </td>

                                            <td style="vertical-align: top;" class="text-wrap">
                                                <?= isset($group->name)? strtoupper($group->name):'' ?>
                                            </td>

                                            <td style="vertical-align: top;" class="text-wrap">
                                                <?= htmlspecialchars(function_exists('mb_strtoupper') ? mb_strtoupper((string) ($v->sellers ?? ''), 'UTF-8') : strtoupper((string) ($v->sellers ?? '')), ENT_QUOTES, 'UTF-8') ?>
                                            </td>
                                            <!-- <td style="vertical-align: top;" class="text-wrap">
                                                <?= fv_format_display_date(@$v->customer_created_at) ?>
                                            </td> -->
                                            <td style="vertical-align: top;">
                                                <?php
                                                if($v->customer_status == "1"){
                                                  echo '<a href="'. company_url('customer/deshabilitar/'.$v->customer_id) .'" class="btn btn-warning btn-sm w-100 boton_status" data-loader="'. $langprods['loader-disabling'] .'">'.$langprods['option_desactive'].'</a>';

                                                } elseif($v->customer_status == "0" || $v->customer_status == null){

                                                  echo '<a href="'. company_url('customer/habilitar/'.$v->customer_id) .'" class="btn btn-success btn-sm w-100 boton_status" data-loader="'. $langprods['loader-enabling'] .'"><font color="White">'.$langprods['option_active'].'</font></a>';
                                                } ?>
                                            </td>
                                            <td style="vertical-align: top;" class="text-wrap">

                                                <?php
                                                if($v->catalog_emails == "1"){
                                                  echo '<a href="'. company_url('customer/deshabilitar_email/'.$v->customer_id) .'" class="btn btn-warning btn-sm w-100 boton_email" data-loader="'. $langprods['loader-disabling'] .'">'.$langprods['option_desactive'].'</a>';
                                                } elseif($v->catalog_emails == "0" || $v->catalog_emails == null){
                                                  echo '<a href="'. company_url('customer/habilitar_email/'.$v->customer_id) .'" class="btn btn-success btn-sm w-100 boton_email" data-loader="'. $langprods['loader-enabling'] .'"><font color="White">'.$langprods['option_active'].'</font></a>';
                                                } ?>
                                            </td>

                                            <td style="vertical-align: top;">
                                                <div class="form-group text-nowrap">
                                                    <a href="<?= company_url('customer/income/'.$v->customer_id) ?>"
                                                        class="btn btn-info btn-sm" data-toggle="tooltip"
                                                        title="<?= $lang_clientes['view_income'] ?>">
                                                        <i class="mdi mdi-wallet"></i>
                                                    </a>
                                                    <a href="javascript:void(0)" 
                                                        class="btn btn-success btn-sm btn-edit-customer"
                                                        title="<?= $lang_clientes['edit'] ?>"
                                                        data-edit-url="<?= company_url('customers/edit/'.$v->customer_id) ?>">
                                                        <i class="mdi mdi-pencil"></i>
                                                    </a>
                                                    <!-- onclick="deleteCustomer(<?= @$v->customer_id ?>,'<?= $v->business_name ?>')" -->
                                                    <button onclick="delete_customer(<?= @$v->customer_id ?>)"
                                                        type="button" class="btn btn-danger btn-sm"
                                                        data-toggle="tooltip" title="<?= $lang_clientes['delete'] ?>"><i
                                                            class="mdi mdi-delete"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php $i++; ?>
                                        <?php endforeach ?>

                                    </tbody>
                                </table>
                                <div class="paginate">
                                    <?=@$paginate?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- modal enviar catálogo: se carga vía contextualButtonBar → modals (send_catalog) -->

<!-- modal_exportacion -->
<?php $this->load->view("company/customer/modal_exportacion") ?>

<!-- modal asig seller-->
<?php $this->load->view("company/customer/modalasign") ?>

<!-- modal remove seller -->
<?php $this->load->view("company/customer/modalremove") ?>
<script>
var companyUrl = '<?= company_url() ?>';

window.openurl = function(url) {
    var w = 1500;
    var h = 1200;
    var left = (screen.width / 2) - (w / 2);
    var top = (screen.height / 2) - (h / 2);

    var childWindow = window.open(url, 'NewWindow33', 'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=no, width=' + w + ', height=' + h + ', top=' + top + ', left=' + left);

    if (childWindow) {
        // Enfocar la ventana
        childWindow.focus();
        // Manejar evento cuando la ventana hijo se cierre
        childWindow.onbeforeunload = function() {
            // Refrescar la ventana padre
            location.reload();
        };
    } else {
        alert('Por favor, permite ventanas emergentes para este sitio.');
    }
};

// Alias para compatibilidad
function openurl(url) {
    window.openurl(url);
}

// Manejador inmediato para botón de editar - se ejecuta antes que jQuery ready
(function() {
    document.addEventListener('click', function(e) {
        var target = e.target;
        // Buscar el botón de editar o su padre
        while (target && target !== document.body) {
            if (target.classList && target.classList.contains('btn-edit-customer')) {
                e.preventDefault();
                e.stopImmediatePropagation();
                e.stopPropagation();
                var url = target.getAttribute('data-edit-url');
                if (url && typeof window.openurl === 'function') {
                    window.openurl(url);
                } else if (url && typeof openurl === 'function') {
                    openurl(url);
                }
                return false;
            }
            // También verificar si el clic fue en el icono dentro del botón
            if (target.classList && target.classList.contains('mdi-pencil')) {
                var btn = target.closest('.btn-edit-customer');
                if (btn) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    e.stopPropagation();
                    var url = btn.getAttribute('data-edit-url');
                    if (url && typeof window.openurl === 'function') {
                        window.openurl(url);
                    } else if (url && typeof openurl === 'function') {
                        openurl(url);
                    }
                    return false;
                }
            }
            target = target.parentElement;
        }
    }, true); // Usar capture phase para máxima prioridad
})();

$(document).on('click', '.sort', function(e) {
    e.preventDefault()

    let order_type = $(this).data('order_type'),
        order_sort = $(this).data('sort')

    $('#order_type').val(order_type)
    $('#order_sort').val(order_sort)
    $('#form-buscar-filtro').submit()

});

function buscar_porfiltro() {

    $('#form-buscar-filtro').submit()
}

$(document).on("click", "#btn-submit-textsearch", function(e) {
    buscar_porfiltro()
})

function reemplazar(prsearch, event) {

    if (event.key === 'Enter') {
        $('#text_buscar_caracter').val(prsearch)
        buscar_porfiltro()
    } else {
        $('#text_buscar_caracter').val(prsearch)
    }

}

//marcar todos  link-todos
$(document).on('click', '#link-todos', function(e) {

    let input_todos = $('#input-todos')
    console.log(input_todos.val())
    if (input_todos.val() == 'pagina') {

        input_todos.val('todos')
        $(this).text('<?= $langprods['desmarcar_todos'] ?>')
        let todos = $('#input-todos').val()
        $('#form-full-vendor').attr('action',
            '<?= company_url('customer/exportCSVcustomers?separador=puntoycoma&cantidad=') ?>' +
            todos)

        let add_parameter = '&select_todos=todos'
        $(".paginate .pagination a[data-ci-pagination-page]").each(function() {

            let url = $(this).attr('href')
            $(this).attr('href', url + add_parameter)

        });

    } else {

        input_todos.val('pagina')
        $('#ckbCheckAll').attr('title', '<?= $langprods['toltip_paginas'] ?>');
        $(this).text('<?= $langprods['marcar_todos'] ?>')
        let todos = $('#input-todos').val()
        $('.table input[type=checkbox]').prop('checked', false)
        $('.alert').hide()
        $('#form-full-vendor').attr('action',
            '<?= company_url('customer/exportCSVcustomers?separador=puntoycoma&cantidad=') ?>' +
            todos)

        let delete_parameter = '&select_todos=todos'
        $(".paginate .pagination a[data-ci-pagination-page]").each(function() {
            let url = $(this).attr('href')
            let url_principal = url.split('?')[0]
            console.log(url_principal)
            let parameters = url.split('?')[1].replace('&select_todos=todos', '')
            let nueva_url = url_principal + '?' + parameters
            //console.log(url.replace(delete_parameter, ''))
            $(this).attr('href', nueva_url)
            //console.log($(this).attr('href'))
        });


    }
})

// mostrar alerta todos
$(document).on('change', '#ckbCheckAll', function(e) {

    if (this.checked) {
        $(this).attr('title', '<?= $langprods['deseleccionar_toda_lapagina'] ?>');
        $('.alert').show()
        $('.alert').addClass('show')
        $('#input-todos').val('pagina')
        $('#link-todos').text('<?= $langprods['marcar_todos'] ?>')
        let todos = $('#input-todos').val()
        $('#form-full-vendor').attr('action',
            '<?= company_url('customer/exportCSVcustomers?separador=puntoycoma&cantidad=') ?>' +
            todos)

    } else {

        $('#input-todos').val('pagina')
        $(this).attr('title', '<?= $langprods['toltip_paginas'] ?>');
        $('.alert').hide()
        let todos = $('#input-todos').val()
        $('#form-full-vendor').attr('action',
            '<?= company_url('customer/exportCSVcustomers?separador=puntoycoma&cantidad=') ?>' +
            todos)
    }

})

// cambiar el send catalogo
$(document).on('change', '.btn-check', function(e) {

    let send_catalog = $(this).val()
    let idcustomer = $(this).data('id')

    if (send_catalog == 1) {
        $.ajax({

            url: companyUrl + 'customer/activar_sendcatalog',
            type: 'post',
            dataType: 'json',
            data: {
                idcustomer: idcustomer
            },
            success: function(response) {

            }
        })

    } else {

        $.ajax({

            url: companyUrl + 'customer/desactivar_sendcatalog',
            type: 'post',
            data: {
                idcustomer: idcustomer
            },
            dataType: 'json',
            success: function(response) {

            }
        })


    }


})

$(document).on('change', '.checkBoxClass', function(e) {

    if (!this.checked) {
        $('#ckbCheckAll').prop('checked', false)
        $('#ckbCheckAll').attr('title', '<?= $langprods['toltip_paginas'] ?>');
        let input_todos = $('#input-todos')
        input_todos.val('pagina')
        $('#link-todos').text('<?= $langprods['marcar_todos'] ?>')

        let url = $('#form-full-vendor').attr('action')
        let url_replace = url.replace('pagina', input_todos.val())
        url_replace = url_replace.replace('todos', input_todos.val())
        console.log(url_replace)
        $('#form-full-vendor').attr('action', url_replace)
        $('.alert').hide()

        //desactivamos
        let todos = $('#input-todos').val()
        $('.table input[type=checkbox]').prop('checked', false)
        $('.alert').hide()
        $('#form-full-vendor').attr('action',
            '<?= company_url('customer/exportCSVcustomers?separador=puntoycoma&cantidad=') ?>' +
            todos)

        let delete_parameter = '&select_todos=todos'
        $(".paginate .pagination a[data-ci-pagination-page]").each(function() {
            let url = $(this).attr('href')
            let url_principal = url.split('?')[0]
            console.log(url_principal)
            let parameters = url.split('?')[1].replace('&select_todos=todos', '')
            let nueva_url = url_principal + '?' + parameters
            //console.log(url.replace(delete_parameter, ''))
            $(this).attr('href', nueva_url)
            //console.log($(this).attr('href'))
        });
    } else {

        $('#ckbCheckAll').attr('title', '<?= $langprods['deseleccionar_toda_lapagina'] ?>');
    }
})

function eliminarClienteGrupo() {


    var valorclienteId = [];
    let todos = $('#input-todos').val();

    $('input.checkBoxClass[type=checkbox]').each(function() {
        if (this.checked)
            valorclienteId.push($(this).val());
    });

    if (valorclienteId.length == 0) {
        let mensaje = '<?= $lang_clientes['seleccione_cliente']?>';
        alert_func([mensaje, "error", "#DD6B55"]);
        return false;
    }

    let _filtro = obtener_filtros()
    let prsearch = null,
        order_type = null,
        sort = null,
        grupoId = null,
        vendedorId = null,
        termId = null
    if (_filtro.grupoId == "" || _filtro.grupoId != null) {


        prsearch = _filtro.prsearch
        order_type = _filtro.order_type
        sort = _filtro.sort
        grupoId = _filtro.grupoId
        vendedorId = _filtro.vendedorId
        termId = _filtro.termId

    }

    swal({
        title: '<?= $lang_clientes['eliminar_cliente_seleccionados']?>',
        icon: 'error',
        buttons: {
            cancel: {
                text: "<?= $lang_clientes['cancelar'] ?>",
                value: false,
                visible: true,
                className: "sweet-danager",
                closeModal: true,
            },
            confirm: {
                text: "<?= $lang_clientes['delete'] ?>",
                value: true,
                visible: true,
                className: "sweet-warning",
                closeModal: true
            },
        },
    }).then((willDelete) => {
        if (willDelete) {
            $.ajax({
                    url: "<?= company_url('customer/eliminaren_Grupos')?>",
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        customerIds: String(valorclienteId),
                        todos: todos,
                        prsearch: prsearch,
                        order_type: order_type,
                        sort: sort,
                        grupoId: grupoId,
                        vendedorId: vendedorId,
                        termId: termId

                    },
                })
                .done(function(responsedata) {

                    location.reload();
                    /*alert_func(responsedata)

                    setTimeout(() => {

                    }, 1000)*/

                })
                .fail(function(data) {
                    console.log(data);
                });
        }
    });

}


function exportar() {

    let inputs = null
    $('.table tbody tr .checkBoxClass:checked').each(function(index, element) {
        let value = $(element).val()
        let name = $(element).attr('name')
        let inputs = '<input type="checkbox" name="' + name + '"  value="' + value +
            '" class="checkBoxClass" checked>'
        $('#form-full-vendor').append(inputs)
    });

    let _filtro = obtener_filtros()

    if (_filtro.grupoId == "" || _filtro.grupoId != null) {

        $('#form-full-vendor').append('<input type="text" name="grupoId"  value="' + _filtro.grupoId + '">')
        $('#form-full-vendor').append('<input type="text" name="vendedorId"  value="' + _filtro.vendedorId +
            '">')
        $('#form-full-vendor').append('<input type="text" name="termId"  value="' + _filtro.termId + '">')
        $('#form-full-vendor').append('<input type="text" name="status"  value="' + _filtro.status + '">')
        $('#form-full-vendor').append('<input type="text" name="prsearch"  value="' + _filtro.prsearch + '">')
        $('#form-full-vendor').append('<input type="text" name="order_type"  value="' + _filtro.order_type +
            '">')
        $('#form-full-vendor').append('<input type="text" name="sort"  value="' + _filtro.sort + '">')

    } else {

        console.log('es vacio', _filtro)
        $('#form-full-vendor').append('<input type="text" name="groupId">')
        $('#form-full-vendor').append('<input type="text" name="vendedorId">')
        $('#form-full-vendor').append('<input type="text" name="termId">')
        $('#form-full-vendor').append('<input type="text" name="status">')
        $('#form-full-vendor').append('<input type="text" name="prsearch">')
        $('#form-full-vendor').append('<input type="text" name="order_type">')
        $('#form-full-vendor').append('<input type="text" name="sort">')

    }

    $('#form-full-vendor').submit()
    $('#form-full-vendor').html('')
}

$(document).on('click', '#sepComma', function(e) {
    let input_todos = $('#input-todos').val()
    $('#form-full-vendor').attr('action',
        '<?= company_url('customer/exportCSVcustomers?separador=coma&cantidad=') ?>' + input_todos)

})

$(document).on('click', '#sepSemicolon', function(e) {
    let input_todos = $('#input-todos').val()
    $('#form-full-vendor').attr('action',
        '<?= company_url('customer/exportCSVcustomers/puntoycoma?separador=puntoycoma&cantidad=') ?>' +
        input_todos)
})

function deleteCustomer(id, nombre) {
    $.ajax({
        url: '<?= company_url('customer/delete/') ?>' + id,
        type: 'get',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {

                if (response.confirm) {
                    console.log("confirm");
                    confirm_elimination(response.msg, id)
                    return
                }
                swal({
                    title: response.msg,
                    icon: 'error',
                })
            }
        }
    })

    swal({
        title: '<?= $lang_clientes['eliminar_cliente']?> ' + nombre + '?',
        icon: 'warning',
        buttons: {
            confirm: {
                text: "<?= $lang_clientes['si'] ?>",
                value: true,
                visible: true,
                className: "sweet-warning",
                closeModal: true
            },
            cancel: {
                text: "No",
                value: false,
                visible: true,
                className: "sweet-danager",
                closeModal: true,
            },

        },
    }).then((willDelete) => {
        if (willDelete) {


            //window.location.href = '<?= company_url('customer/delete/') ?>'+cId
        }
    });
}

function confirm_elimination(msg, id) {
    swal({
        title: msg,
        icon: 'error',
        buttons: {
            confirm: {
                text: "<?= $lang_clientes['si'] ?>",
                value: true,
                visible: true,
                className: "sweet-warning",
                closeModal: true
            },
            cancel: {
                text: "No",
                value: false,
                visible: true,
                className: "sweet-danager",
                closeModal: true,
            },

        },
    }).then((willDelete) => {
        if (willDelete) {

            $.ajax({
                url: '<?= company_url('customer/delete/') ?>' + id,
                type: 'get',
                dataType: 'json',
                data: {
                    confirm: true
                },
                success: function(response) {

                    if (response.success) {
                        location.reload();
                        return
                    }
                    swal({
                        title: response.msg,
                        icon: 'error',
                    })
                }
            })
        }
    });
}

function getCommonCustomer() {

    var valexproductId = [];
    $('input.checkBoxClass[type=checkbox]').each(function() {
        if (this.checked)
            valexproductId.push($(this).val());
    });

    if (valexproductId.length == 0) {
        alert_func(["Please select at least one customer!", "error", "#DD6B55"]);
        return false;
    } else {
        $("#customerIds").val(valexproductId);
        $("#exampleModal").modal('show');
    }

}

$(document).ready(function() {

    // Manejar clic en botón de editar - DEBE estar al inicio para máxima prioridad
    $(document).on('click', '.btn-edit-customer', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        e.stopPropagation();
        var url = $(this).data('edit-url') || $(this).attr('data-edit-url');
        console.log('Edit button clicked, URL:', url);
        if (url) {
            if (typeof window.openurl === 'function') {
                console.log('Using window.openurl');
                window.openurl(url);
            } else if (typeof openurl === 'function') {
                console.log('Using openurl');
                openurl(url);
            } else {
                console.log('Using fallback window.open');
                // Fallback: usar window.open directamente
                var w = 1500;
                var h = 1200;
                var left = (screen.width / 2) - (w / 2);
                var top = (screen.height / 2) - (h / 2);
                var childWindow = window.open(url, 'NewWindow33', 'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=no, width=' + w + ', height=' + h + ', top=' + top + ', left=' + left);
                if (childWindow) {
                    childWindow.focus();
                    childWindow.onbeforeunload = function() {
                        location.reload();
                    };
                } else {
                    alert('Por favor, permite ventanas emergentes para este sitio.');
                }
            }
        }
        return false;
    });

    //boton_email
    $(document).on('click', '.boton_email', function(e) {
        e.preventDefault()

        let url = $(this).attr('href');
        let loader_text = $(this).data('loader');
        let boton = $(this)

        $.ajax({

            url: url,
            type: 'get',
            dataType: 'html',
            beforeSend: function() {

                let contenido_boton =
                    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>  ' +
                    loader_text;

                boton.html(contenido_boton)
            },
            success: function(response) {

                let columna_boton = boton.parent()
                columna_boton.html(response)
                location.reload()


            }
        })

    })

    //cambiar estado
    $(document).on('click', '.boton_status', function(e) {
        e.preventDefault()

        let url = $(this).attr('href');
        let loader_text = $(this).data('loader');
        let boton = $(this)

        $.ajax({

            url: url,
            type: 'get',
            dataType: 'html',
            beforeSend: function() {

                let contenido_boton =
                    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>  ' +
                    loader_text;

                boton.html(contenido_boton)
            },
            success: function(response) {

                let columna_boton = boton.parent()
                columna_boton.html(response)
                location.reload()


            }
        })

    })

    $(document).on('submit', '#form-send', function(e) {

        let boton = $('#btn-sendemail')
        console.log(boton)

        e.preventDefault()
        $.ajax({

            url: $(this).attr('action'),
            type: 'post',
            dataType: 'json',
            data: $(this).serializeArray(),
            beforeSend: function() {
                boton.find('.spinner-border').removeClass('d-none')
                boton.prop('disabled', true)
            },
            success: function(response) {

                boton.find('.spinner-border').addClass('d-none')
                boton.prop('disabled', false)

                if (response.success) {
                    location.reload()
                    return
                }
                alert_func([response.msg, "error"])
            }
        })


    })

    //catalog_default
    //created_catalogs
    $(document).on('click', '#form-send #catalog_default', function(e) {

        let checkBox = document.getElementById("catalog_default");
        if (checkBox.checked == true) {

            $('#form-send .created_catalogs').prop('checked', false);
            $('#form-send .created_catalogs').prop('disabled', true);

        } else {
            $('#form-send .created_catalogs').prop('disabled', false);

        }
    })


});
</script>
