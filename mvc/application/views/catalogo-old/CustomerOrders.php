<div class="checkout_page_bg">
    <div class="container">
        <div class="Checkout_section">
            <div class="row">
                <h4> HISTORIAL DE ORDENES </h4>
                <div class="col-12">
                    <?php $this->load->view('company/customer/OrdenesClientes', $info); ?>
                    <div class="shop_toolbar t_bottom">
                        <?php
                            $this->load->view('components/catalog/pagination');
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


