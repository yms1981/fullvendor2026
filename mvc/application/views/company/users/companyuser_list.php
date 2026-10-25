<?php 

$ahora = DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''));
$v_imagen = $ahora->format("YmdHisu");

?>
<div class="container-fluid pt-3">
    <div class="row mb-2">
        <div class="col-md-6">
            <div class="page-header">
                <h3 class="page-title"> <?= $lang_user['list_users']?></h3>
            </div>
        </div>
        <div class="col-md-6 text-right">
            <a href="<?=company_url('users/add')?>" class="btn btn-success mt-2 mt-sm-0 btn-icon-text">
            <i class="mdi mdi-plus-circle"></i> <?= $lang_user['add_user'] ?></a>
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
                                    <th width="20">#</th>
                                    <th><?= $lang_user['image'] ?></th>
                                    <th><?= $lang_user['name'] ?></th>
                                    <th>Email</th>
                                    <th><?= $lang_user['telefono'] ?></th>
                                    <th><?= $lang_user['alta'] ?></th>
                                    <th width="150"><?= $lang_user['status'] ?></th>
                                    <th width="80"><?= $lang_user['actions'] ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i = 1; ?>
                                <?php foreach ($list as $key => $v): ?>
                                <tr>
                                    <td><?=$i?></td>
                                    <td>
                                        <figure style="width: 4em; height: 4em; overflow:hidden; margin:auto;">
                                            <?php
                                            $__uimg = trim((string) (@$v->image ?? ''));
                                            $__src = '';
                                            if (function_exists('fv_user_avatar_image_url')) {
                                                $__src = fv_user_avatar_image_url($v);
                                                if ($__src !== '') {
                                                    $__src .= (strpos($__src, '?') === false ? '?v=' : '&v=') . $v_imagen;
                                                }
                                            }
                                            if ($__src === '') {
                                                $__uuid = $__uimg !== '' && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i', $__uimg);
                                                if (function_exists('company_logo_is_api_image') && company_logo_is_api_image($__uimg)) {
                                                    $__src = company_logo_url($__uimg) . '?v=' . $v_imagen;
                                                } elseif ($__uuid && function_exists('fv_api_image_url')) {
                                                    $__src = fv_api_image_url($__uimg) . '?v=' . $v_imagen;
                                                } elseif ($__uimg !== '') {
                                                    $__uurl = company_logo_url($__uimg);
                                                    $__src = $__uurl !== '' ? ($__uurl . '?v=' . $v_imagen) : '';
                                                }
                                            }
                                            ?>
                                            <?php if ($__src !== '') { ?>
                                            <span data-toggle="popover-hover" title="<?= $v->name ?>"
                                                data-img="<?= htmlspecialchars($__src, ENT_QUOTES, 'UTF-8') ?>">
                                                <img src="<?= htmlspecialchars($__src, ENT_QUOTES, 'UTF-8') ?>"
                                                    alt="" style="display: block; width: 100%; height: 100%;"
                                                    class="rounded-0">
                                            </span>
                                            <?php } else { ?>
                                            <img src="<?= base_url('uploads/user_anonimo.jpeg'. '?v=' . $v_imagen) ?>"
                                                alt="" style="display: block; width: 100%; height: 100%;"
                                                class="rounded-0">
                                            <?php } ?>
                                        </figure>

                                    </td>
                                    <td><?= @$v->short_name ?></td>
                                    <td><?= @$v->email ?></td>
                                    <td><?=@$v->phone_number?></td>
                                    <td><?= fv_format_display_date(@$v->created) ?></td>
                                    <td>
                                        <?php
                                            if($v->status == "1"){ 
                                                echo '<a title="'. $lang_status_aditional['catalog_enabled'] .'" href="'. company_url('users/disable/'.$v->company_id) .'" class="btn btn-warning btn-sm w-100 boton_status_general" data-loader="'. $lang_status['loader-disabling'] .'">'.$lang_status['option_desactive'].'</a>'; 
                                            } elseif($v->status == "0" || $v->status == null){ 
                                                echo '<a title="'. $lang_status_aditional['catalog_disabled'] .'" href="'. company_url('users/enable/'.$v->company_id) .'" class="btn btn-success btn-sm w-100 boton_status_general" data-loader="'. $lang_status['loader-enabling'] .'"><font color="White">'.$lang_status['option_active'].'</font></a>';

                                            } 
                                        ?>

                                    </td>
                                    <td>
                                        <div class="form-group">
                                            <a href="<?= company_url('users/edit/'.$v->company_id) ?>"
                                                class="btn btn-success btn-sm ml-2" data-toggle="tooltip" title="Edit">
                                                <i class="mdi mdi-pencil"></i>
                                            </a>
                                            <a href="<?= company_url('users/delete/'.$v->company_id) ?>" 
                                                data-msg = "<?= 'Delete user ' . $v->email . '?' ?>" 
                                                class="btn btn-danger btn-sm delete_anygeneral" 
                                                data-toggle="tooltip" 
                                                title="Delete"
                                                onclick="deleteCompanyuser(<?= @$v->company_id ?>)">
                                                <i class="mdi mdi-delete"></i>
                                            </a>
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

<script>
$(document).ready(function() {

    activar_tabla();
    activar_popover_imagenes()
});


function deleteCompanyuser(cId) {
    swal({
        title: 'Are you sure want to delete this?',
        text: 'Once deleted, You wont be able to revert this! ⚠️',
        icon: "warning",
        buttons: {
            cancel: {
                text: "No, cancel !",
                value: false,
                visible: true,
                className: "sweet-danager",
                closeModal: true,
            },
            confirm: {
                text: "Yes, delete it!",
                value: true,
                visible: true,
                className: "sweet-warning",
                closeModal: true
            },
        },
    }).then((willDelete) => {
        if (willDelete) {
            window.location.href = '<?= company_url('users/delete/') ?>' + cId
        }
    });
}
</script>