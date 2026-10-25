<!-- partial -->
<div class="container-fluid page-body-wrapper pt-3">
    <div class="main-panel">
        <div class="container-fluid contentHeight">
            <div class="row">
                <div class="col-md-6">
                    <div class="page-header">
                        <h3 class="page-title"> <?= $lang_optionbasic['list_catalog'] ?></h3>
                    </div>
                </div>
                <div class="col-md-6 text-right">
                    <a href="<?=company_url('catalog/add')?>" class="btn btn-success">
                        <i class="mdi mdi-plus-circle"></i> <?= $lang_optionbasic['add_Catalog'] ?>
                    </a>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <div class="table-responsive">
                                <table id="infoTable" class="table">
                                    <thead>
                                        <tr>
                                            <th style="width:1%;">#</th>
                                            <th style="width:1%;"></th>
                                            <th style="width:40%;"><?= $lang_optionbasic['catalog_name'] ?></th>
                                            <th style="width:20%;"><?= $lang_optionbasic['created'] ?></th>
                                            <th style="width:13%;" class="text-center"><?= $lang_optionbasic['status_table'] ?></th>
                                            <th style="width:27%;" class="text-center"><?= $lang_optionbasic['actions'] ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $i = 1; ?>
                                        <?php foreach ($list as $key => $v): ?>
                                        <?php $prdnames=explode(",",$v->product_name);
                                            $compId = $this->session->userdata('compId');
                                            ?>
                                        <tr>
                                            <td><?= $i ?></td>
                                            <td>
                                                <?php if($v->default_catalog != null){?>
                                                <img src="<?= base_url('images/logos/logo.png') ?>"
                                                    class="thumbnail rounded"
                                                    style="display:inline-block;width: 22px;height: 22px;">
                                                <?php }?>
                                            </td>
                                            <td><?= @$v->catalog_name?></td>
                                            <td><?= fv_format_display_date(@$v->created) ?></td>
                                            <td width="100px">
                                                <?php
                                                if($v->status == "1"){
                                                  echo '<a title="'. $lang_status_aditional['catalog_enabled'] .'" href="'. company_url('catalog/disable/'.$v->catalog_id) .'" class="btn btn-warning btn-sm w-100 boton_status_general" data-loader="'. $lang_status['loader-disabling'] .'">'.$lang_status['option_desactive'].'</a>';
                                                } elseif($v->status == "0" || $v->status == null){
                                                  echo '<a title="'. $lang_status_aditional['catalog_disabled'] .'" href="'. company_url('catalog/enable/'.$v->catalog_id) .'" class="btn btn-success btn-sm w-100 boton_status_general" data-loader="'. $lang_status['loader-enabling'] .'"><font color="White">'.$lang_status['option_active'].'</font></a>';
                                                } ?>
                                            </td>

                                            <td>
                                                <div class="form-group text-nowrap text-right">
                                                    <a class="btn btn-success btn-sm download-buttons"
                                                        href="javascript:void(0)"
                                                        title="<?= $lang_optionbasic['download'] ?>"
                                                        data-toggle="modal"
                                                        data-target="#downloadModal_<?=$v->catalog_id?>"
                                                        >
                                                        <i class="fa-solid fa-cloud-arrow-down"></i>
                                                        <?= $lang_optionbasic['download'] ?>
                                                    </a>
                                                    <a class="btn btn-success btn-sm" href="javascript:void(0);"
                                                        title="<?= $lang_optionbasic['performance'] ?>" data-toggle="modal"
                                                        data-target="#performanceModal<?=$v->catalog_id?>">
                                                        <i class="fa-solid fa-chart-simple"></i>
                                                        <?= $lang_optionbasic['performance'] ?>
                                                    </a>

                                                    <?php
                                                        $msg = $v->categories == null && $v->products == null?$langcatalog['no_send_catalog']:'';
                                                    ?>
                                                    <a  data-msg-error="<?= $msg ?>" class="btn btn-success btn-sm btn-send-catalog <?= $v->status == "0" || $v->status == null?'disabled':'' ?>" href="javascript:void(0);"
                                                        title="<?= $lang_optionbasic['send_catalog'] ?>"
                                                        data-modal="#sendcatalogModal<?=$v->catalog_id?>">
                                                        <i class="fa-solid fa-envelope"></i>
                                                        <?= $lang_optionbasic['send_catalog'] ?>
                                                    </a>
                                                    <a class="btn btn-warning btn-sm" href="javascript:void(0);"
                                                        data-toggle="modal"
                                                        title="<?= $lang_optionbasic['toltip_view'] ?>"
                                                        data-target="#previewModal<?=$v->catalog_id?>">
                                                        <i class="mdi mdi-eye"></i>
                                                    </a>
                                                    <a href="<?= company_url('catalog/edit/'.$v->catalog_id) ?>"
                                                        title="<?= $lang_optionbasic['toltip_edit'] ?>"
                                                        class="btn btn-success btn-sm" data-toggle="tooltip"
                                                        title="Edit">
                                                        <i class="mdi mdi-pencil"></i>
                                                    </a>
                                                    <a class="btn btn-warning btn-sm delete_anygeneral"
                                                        data-toggle="tooltip" title="Delete"
                                                        title="<?= $lang_optionbasic['toltip_delete'] ?>"
                                                        href="<?= company_url('catalog/delete/'  . $v->catalog_id) ?>"
                                                        title="Delete">
                                                        <i class="mdi mdi-delete"></i>
                                                    </a>
                                                </div>

                                                <?php
                                                ?>

                                            </td>
                                        </tr>

                                        <?php $i++; endforeach ?>

                                    </tbody>
                                </table>
                            </div>
                            <div id="table_print" class="table-responsive">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
