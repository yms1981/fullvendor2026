<div class="checkout_page_bg">
    <div class="container">
        <div class="Checkout_section">
            <h4><?= $lang_catalogo['detalleOrden'] ?></h4>
            <div class="row">
                <?php $this->load->view("company/order/CabeceraOrden"); ?>
            </div>
            <div class="row">

                <div class="col-12">
                    <?php $this->load->view("company/order/tableDetalleOrden"); ?>
                </div>
            </div>
        </div>
    </div>
</div>
