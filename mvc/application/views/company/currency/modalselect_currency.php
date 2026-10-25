<div class="modal fade" id="modalcurrency" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel"><?= $lang_unit['select_money'] ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="form-update" action="<?= company_url('currencies/updatecurrency') ?>" method="post">

                    <div id="myselect2">

                        <select class="form-control js-example-matcher-start" name="currency">
                            <?php foreach ($currencies_pather as $pather) : ?>
                            <optgroup label="<?= $pather->GroupID ?>">
                                <?php foreach ($pather->currencies as $children) : ?>
                                <option value="<?= $children->id ?>"
                                    <?= ($company->currency && $company->currency==$children->id)?"selected":$children->default ?>>
                                    <?= $children->Contry . ' ' . $children->Name. ' ' . $children->Code . ' '.$children->Symbol ?>
                                </option>
                                <?php endforeach;?>
                            </optgroup>
                            <?php endforeach;?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button form="form-update" type="submit" class="btn btn-success"><?= $lang_unit['aceptar'] ?></button>
                <button type="button" class="btn btn-danger" data-dismiss="modal"><?= $lang_unit['cancel'] ?></button>
            </div>
        </div>
    </div>
</div>