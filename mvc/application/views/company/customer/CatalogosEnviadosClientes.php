<div class="table-responsive">
    <table class="table">
        <thead>
        <tr>
            <th style="width:2%;">#</th>
            <th style="width:20%;"><?= $langcliente['send_date'] ?></th>
            <th style="width:5%;"></th>
            <th style="width:30%;"><?= $langcliente['send_expiration'] ?></th>
            <th style="width:38%;"><?= $langcliente['catalog_name'] ?></th>
            <th style="width:5%;"></th>
        </tr>
        </thead>
        <tbody>
        <?php if(is_array($carts_catalogs) && count($carts_catalogs)>0){
            $j=0;
            foreach ($carts_catalogs as $vc) {
                $catdetl=$this->mymodel->get_by('catalog', true, array('catalog_id'=>@$vc->catalog_id));
                $j++;
                ?>
                <tr class="notranslate">
                    <td><?=$j?></td>
                    <td><?= fv_format_display_datetime($vc->created ?? null) ?></td>
                    <td class="<?= ($vc->view == 1)?'text-success':'color-yellow' ?>">
                        <i class="fa-solid fa-eye"></i>
                    </td>
                    <td><?= fv_format_display_datetime($vc->expired_date ?? null) ?></td>
                        <?php
                        $fecha_actual = strtotime(date('Y-m-d H:m:i'));
                        $expired_date = strtotime($vc->expired_date);
                        if ($fecha_actual > $expired_date) {                             ?>
                            <span class="badge badge-pill badge-danger"><?= $langcliente['expirado'] ?></span>
                        <?php } ?>
                    </td>
                    <td>
                        <a target="_blank" href="<?= htmlspecialchars(catalog_public_browse_url((string) ($vc->token_kor ?? '')), ENT_QUOTES, 'UTF-8') ?>">
                            <?php if($vc->default_catalog == 1){?>
                                <img src="<?= base_url('images/logos/logo.png') ?>" class="thumbnail rounded" style="display:inline-block;width: 22px;height: 22px;">
                            <?php }?>
                            <?= $vc->catalog_name?>
                        </a>
                    </td>
                    <td>
                        <?php if ($fecha_actual < $expired_date) {  ?>
                            <div class="btn-group" style="position: static;">
                                <button type="button" class="btn btn-secondary dropdown-toggle dropdown-toggle-split btn-sm" id="dropdownMenuReference" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-reference="parent">
                                    <span class="sr-only">Toggle Dropdown</span>
                                </button>
                                <?php $url_catalog = catalog_public_browse_url((string) ($vc->token_kor ?? '')); ?>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuReference" style="width: 150px;">
                                    <a class="dropdown-item" href="javascript:void(0)" onclick="copyToClip('<?= htmlspecialchars($url_catalog, ENT_QUOTES, 'UTF-8') ?>')">Copy Link</a>
                                    <a class="dropdown-item" href="#" data-toggle="modal" data-link="<?= htmlspecialchars($url_catalog, ENT_QUOTES, 'UTF-8') ?>" data-target="#modal-showlink">Shown Link</a>
                                    <a class="dropdown-item" href="javascript:void(0)" onclick='expirar_link(<?= $vc->cart_id ?>)'>Do Expire</a>
                                </div>
                            </div>
                        <?php } ?>

                    </td>
                </tr>
            <?php } } ?>
        </tbody>
    </table>
