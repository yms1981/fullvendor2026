<div class="container-fluid contentHeight">
    <style>
        .warehouse-status-switch {
            display: flex;
            justify-content: center;
        }
        .warehouse-status-switch .custom-control-label::before {
            cursor: pointer;
        }
    </style>
    <div class="row mb-2">
        <div class="col-md-6">
            <div class="page-header">
                <h3 class="page-title"> <?= @$title ?></h3>
            </div>
        </div>
        <div class="col-md-6 text-right">
            <a href="<?= company_url('warehouses/add') ?>" class="btn btn-success"><i class="mdi mdi-plus-circle"></i>
                <?= $lang_warehouse['add_new_deposit'] ?></a>
            <a href="<?= company_url('warehouses/csvImport') ?>" class="btn btn-success mt-2 mt-sm-0 btn-icon-text"><i
                    class="mdi mdi-import"></i> <?= $lang_warehouse['import'] ?></a>
            <a href="<?= company_url('warehouses/export') ?>" download
                class="btn btn-success mt-2 mt-sm-0 btn-icon-text"><i class="mdi mdi-download"></i> <?= $lang_warehouse['export'] ?> </a>
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
                                    <th></th>
                                    <th><?= $lang_status['name'] ?></th>
                                    <th><?= htmlspecialchars((string) ($lang_user['email'] ?? $lang_warehouse['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th><?= $lang_status['mobile'] ?></th>
                                    <th><?= $lang_status['alta'] ?></th>
                                    <th width="150"><?= $lang_user['status'] ?></th>
                                    <th width="70"><?= $lang_user['actions'] ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i = 1; ?>
                                <?php foreach ($list as $key => $v): ?>
                                <tr>
                                    <?php
                                        $warehouseName = trim((string) ($v->first_name ?? '').' '.(string) ($v->last_name ?? ''));
                                        if ($warehouseName === '') {
                                            $warehouseName = trim((string) ($v->name ?? ''));
                                        }
                                        $warehouseEmail = trim((string) ($v->email ?? ''));
                                        if ($warehouseEmail === '' && isset($v->users)) {
                                            if (is_array($v->users) && isset($v->users[0])) {
                                                $u0 = $v->users[0];
                                                $warehouseName = $warehouseName !== '' ? $warehouseName : trim((string) ($u0->name ?? ''));
                                                $warehouseEmail = trim((string) ($u0->email ?? ''));
                                            } elseif (is_object($v->users)) {
                                                $warehouseName = $warehouseName !== '' ? $warehouseName : trim((string) ($v->users->name ?? ''));
                                                $warehouseEmail = trim((string) ($v->users->email ?? ''));
                                            }
                                        }
                                    ?>
                                    <td><?= $i ?></td>
                                    <!-- Imagen -->
                                    <td>
                                        <figure style="width: 4em; height: 4em; overflow:hidden; margin:auto;">
                                            <?php
                                                $profileImage = trim((string) ($v->profile_image ?? ''));
                                                $resolvedImageUrl = '';
                                                if ($profileImage !== '') {
                                                    $candidates = [
                                                        './uploads/users/' . $profileImage,
                                                        './uploads/company/' . $profileImage,
                                                        './uploads/' . $profileImage,
                                                    ];
                                                    foreach ($candidates as $candidatePath) {
                                                        if (file_exists($candidatePath)) {
                                                            $resolvedImageUrl = base_url(ltrim(str_replace('./', '', $candidatePath), '/')) . '?v=' . $v_imagen;
                                                            break;
                                                        }
                                                    }
                                                }
                                            ?>
                                            <?php if ($resolvedImageUrl !== '') { ?>
                                            <span data-toggle="popover-hover" title="<?= htmlspecialchars($warehouseName, ENT_QUOTES, 'UTF-8') ?>"
                                                data-img="<?= $resolvedImageUrl ?>">
                                                <img src="<?= $resolvedImageUrl ?>"
                                                    alt="" style="display: block; width: 100%; height: 100%;"
                                                    class="rounded-0">
                                            </span>
                                            <?php } else { ?>
                                            <img src="<?= base_url('images/nouser.png'. '?v=' . $v_imagen) ?>"
                                                alt="" style="display: block; width: 100%; height: 100%;"
                                                class="rounded-0">
                                            <?php } ?>
                                        </figure>
                                    </td>
                                    <td><?= htmlspecialchars($warehouseName, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($warehouseEmail, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= @$v->phone_number ?></td>
                                    <td><?= fv_format_display_date(@$v->created) ?></td>
                                    <td width="100px">
                                        <?php
                                            $enabledTitle = (string) ($lang_status_aditional['catalog_enabled'] ?? '');
                                            $disabledTitle = (string) ($lang_status_aditional['catalog_disabled'] ?? '');
                                            $isEnabled = ((string) ($v->status ?? '') === '1');
                                        ?>
                                        <div class="custom-control custom-switch warehouse-status-switch">
                                            <input type="checkbox"
                                                class="custom-control-input js-warehouse-status-switch"
                                                id="warehouse-status-<?= (int) $v->user_id ?>"
                                                data-enable-url="<?= company_url('warehouses/enable/'.$v->user_id) ?>"
                                                data-disable-url="<?= company_url('warehouses/disable/'.$v->user_id) ?>"
                                                data-title-enabled="<?= htmlspecialchars($enabledTitle, ENT_QUOTES, 'UTF-8') ?>"
                                                data-title-disabled="<?= htmlspecialchars($disabledTitle, ENT_QUOTES, 'UTF-8') ?>"
                                                <?= $isEnabled ? 'checked' : '' ?>>
                                            <label class="custom-control-label"
                                                for="warehouse-status-<?= (int) $v->user_id ?>"
                                                title="<?= htmlspecialchars($isEnabled ? $enabledTitle : $disabledTitle, ENT_QUOTES, 'UTF-8') ?>"></label>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="form-group">
                                            <a href="<?= company_url('warehouses/edit/'.$v->user_id) ?>"
                                                class="btn btn-success btn-sm" data-toggle="tooltip" title="<?= htmlspecialchars((string) ($lang_warehouse['editar'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                <i class="mdi mdi-pencil"></i>
                                            </a>
                                            <a href="<?= company_url('warehouses/delete/'.$v->user_id) ?>"
                                                data-msg="<?= htmlspecialchars(sprintf((string) ($lang_warehouse['delete_user_confirm'] ?? '%s'), (string) $warehouseEmail), ENT_QUOTES, 'UTF-8') ?>"
                                                class="btn btn-danger btn-sm delete_anygeneral" data-toggle="tooltip"
                                                title="<?= htmlspecialchars((string) ($lang_warehouse['eliminar'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
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

    if (typeof activar_tabla === 'function') {
        activar_tabla();
    } else if (typeof jQuery !== 'undefined' && jQuery.fn && typeof jQuery.fn.DataTable === 'function') {
        $('#infoTable').DataTable({
            ordering: false,
            language: (typeof language !== 'undefined' ? language : {})
        });
    }
    activar_popover_imagenes()

    $(document).on('change', '.js-warehouse-status-switch', function() {
        const $switch = $(this);
        const checked = $switch.is(':checked');
        const url = checked ? $switch.data('enable-url') : $switch.data('disable-url');
        const $label = $switch.closest('.custom-control').find('.custom-control-label');

        $switch.prop('disabled', true);

        fetch(url, { method: 'GET' })
            .then(response => response.json())
            .then(data => {
                if (!data || !data.success) {
                    $switch.prop('checked', !checked);
                    if (data && data.msg && typeof alert_func === 'function') {
                        alert_func([data.msg, 'error']);
                    }
                    return;
                }

                const nowEnabled = checked;
                const titleText = nowEnabled ? $switch.data('title-enabled') : $switch.data('title-disabled');
                $label.attr('title', titleText || '');
                $switch.prop('checked', nowEnabled);
            })
            .catch(() => {
                $switch.prop('checked', !checked);
            })
            .finally(() => {
                $switch.prop('disabled', false);
            });
    });
});


</script>