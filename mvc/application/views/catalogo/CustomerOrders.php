<div class="checkout_page_bg">
    <div class="container">
        <div class="Checkout_section">
            <div class="row">
                <h4><?= $lang_catalogo['historyOrders'] ?></h4>
                <div class="col-12">
                    <?php
                    $ordenVars = [
                        'orders' => $orders ?? [],
                        'info_company' => $info_company ?? null,
                        'mostrarColumnaseller' => $mostrarColumnaseller ?? false,
                        'lang_json' => $lang_json ?? [],
                        'urlDetalleOrden' => $urlDetalleOrden ?? '',
                        'catalog_token' => $catalog_token ?? '',
                    ];
                    $this->load->view('company/customer/OrdenesClientes', $ordenVars);
                    ?>
                    <div class="shop_toolbar t_bottom">
                        <div class="pagination">
                            <?=$paginate?>
                        </div>
                    </div>


                </div>
            </div>
        </div>
    </div>
</div>


