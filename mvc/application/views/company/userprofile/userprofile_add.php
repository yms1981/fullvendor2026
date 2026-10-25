<?php
/**
 * @var $pageTexts array the translated string conglomerated for this view
 */
?>

<div class="container-fluid page-body-wrapper">
    <div class="main-panel">
        <div class="container-fluid pt-3">
            <div class="page-header">
                <h3 class="page-title">
                    Agregar Perfil de Usuario
                </h3>
            </div>

            <div class="card">
                <div class="card-body">
                    <form id="form_add"
                          class="forms-sample"
                          action="<?= company_url('userprofile/save') ?>"
                          method="post"
                          enctype="multipart/form-data">

                        <div class="form-group row">
                            <div class="col-md-6">
                                <label>
                                    Nombre del Perfil: <span class="color-red">*</span>
                                </label>
                                <input type="text"
                                       name="profile_name"
                                       class="form-control"
                                       autocomplete="off"
                                       required
                                       placeholder="Nombre del Perfil">
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-md-6">
                                <label>
                                    Descuento de Orden:
                                </label>
                                <input type="number"
                                       name="order_discount"
                                       class="form-control"
                                       autocomplete="off"
                                       value="0"
                                       min="0"
                                       placeholder="Descuento de Orden">
                            </div>
                            <div class="col-md-6">
                                <label>
                                    Precio de Descuento de Orden:
                                </label>
                                <input type="number"
                                       name="order_disc_price"
                                       class="form-control"
                                       autocomplete="off"
                                       step="0.01"
                                       placeholder="Precio de Descuento">
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-md-6">
                                <label>
                                    Descuento Neto de Orden:
                                </label>
                                <input type="number"
                                       name="order_net_discount"
                                       class="form-control"
                                       autocomplete="off"
                                       value="0"
                                       min="0"
                                       placeholder="Descuento Neto">
                            </div>
                            <div class="col-md-6">
                                <label>
                                    Precio de Descuento Neto:
                                </label>
                                <input type="number"
                                       name="order_net_disc_price"
                                       class="form-control"
                                       autocomplete="off"
                                       step="0.01"
                                       placeholder="Precio de Descuento Neto">
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input type="checkbox"
                                           name="can_change_price"
                                           class="form-check-input"
                                           value="1"
                                           id="can_change_price">
                                    <label class="form-check-label" for="can_change_price">
                                        Puede Cambiar Precio
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input type="checkbox"
                                           name="can_send_catalog"
                                           class="form-check-input"
                                           value="1"
                                           id="can_send_catalog">
                                    <label class="form-check-label" for="can_send_catalog">
                                        Puede Enviar Catálogo
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input type="checkbox"
                                           name="can_create_customer"
                                           class="form-check-input"
                                           value="1"
                                           id="can_create_customer">
                                    <label class="form-check-label" for="can_create_customer">
                                        Puede Crear Cliente
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-md-6">
                                <label>
                                    Estado:
                                </label>
                                <select name="status" class="form-control">
                                    <option value="1">Activo</option>
                                    <option value="0">Inactivo</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mt-4">
                                    <input type="checkbox"
                                           name="default"
                                           class="form-check-input"
                                           value="1"
                                           id="default">
                                    <label class="form-check-label" for="default">
                                        Perfil por Defecto
                                    </label>
                                </div>
                            </div>
                        </div>

                    </form>
                    <?php $this->load->view('components/crud/form_action_fab', [
                        'formId' => 'form_add',
                        'saveLabel' => (string) ($pageTexts['save'] ?? ''),
                        'cancelLabel' => (string) ($pageTexts['atras'] ?? ($pageTexts['back'] ?? '')),
                        'cancelUrl' => company_url('userprofile/lists'),
                    ]); ?>
                </div>
            </div>
        </div>
    </div>
</div>

