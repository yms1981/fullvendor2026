<div class="container-fluid page-body-wrapper pt-3">
    <div class="main-panel">
        <div class="container-fluid contentHeight">
            <div class="row mb-2">
                <div class="col-md-6">
                    <div class="page-header">
                        <h3 class="page-title"> <?= $lang_order['mail_estados'] ?></h3>
                    </div>
                </div>
                <div class="col-md-6 text-right">
                    <button form="form_send" type="submit" class="btn btn-success"><?= $lang_order['boton-save'] ?></button>
                </div>
            </div>
            <div class="card border-0 shadow p-3 mb-5 bg-white rounded">
                <div class="row">
                    <div class="col-12">
                        <form action="<?= company_url('email/status/save_configuracion') ?>" id="form_send">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th class="border-0">#</th>
                                            <th class="border-0"><?= $lang_order['status'] ?></th>
                                            <th class="border-0" width="500">Email</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($status as $key => $s):?>
                                        <?php
                                        $statusName = '';
                                        $id_status_email = '';
                                        $email = '';

                                        if(in_array($s->cod_status, array_column($status_emails,'cod_status'))){
                                            $index = array_search($s->cod_status, array_column($status_emails,'cod_status'));
                                            $id_status_email = $status_emails[$index]->id;
                                            $email = $status_emails[$index]->email;
                                            // Usar el campo 'name' de company_status_order
                                            $statusName = $status_emails[$index]->name ?? '';
                                        }
                                        
                                        // Si no hay name en company_status_order, usar el de status_orders como fallback
                                        if (empty($statusName)) {
                                            $statusName = $s->name ?? $s->name_status ?? '';
                                        }
                                        ?>

                                        <tr>
                                            <td><h1><?= $key + 1 ?></h1></td>
                                            <td class="d-flex">
                                                <div class="cuadro" style="background-color: <?= $s->color ?>;"></div>
                                                <h1>&nbsp;<?= $statusName ?></h1>
                                            </td>
                                            <td>
                                                <input hidden type="text" name="status[<?= $key?>][id_status_email]" value="<?= $id_status_email ?>">
                                                <input hidden type="text" name="status[<?= $key?>][name]" value="<?= $statusName ?>">
                                                <input hidden type="number" name="status[<?= $key?>][cod_status]" value="<?= $s->cod_status ?>">
                                                <textarea placeholder="<?= $lang_order['example'] ?>: <?= $lang_order['email_example'] ?>" name="status[<?= $key?>][emails]" id="" cols="5" rows="4" class="form-control"><?= @$email ?></textarea>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>