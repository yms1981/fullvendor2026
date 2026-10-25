<?php
/**
 * @var $lang_json Array The lang translations for every translatable string
 * @var $base_url string
 * @var $mostrar_asignados bool
 * @var $allowedStatus bool
 */

$lang_catalogo = $lang_json['cmp_catalog']['lang'];
?>
<div class="checkout_page_bg">
    <div class="container">
        <div class="Checkout_section">
            <h4><?= $lang_catalogo['detalleOrden'] ?></h4>
            <div class="row">
                <?php $this->load->view("company/order/CabeceraOrden"); ?>
            </div>
            <div class="row">
                <div class="col-12">
                    <?php
                        if(isset($mostrar_asignados) && $mostrar_asignados && $allowedStatus)
                            $this->load->view("company/order/tableDetalleOrdenCompleted");
                        else $this->load->view("company/order/tableDetalleOrden");
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
