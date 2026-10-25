<?php
/**
 * @var array $customerSeller customers of the seller
 * @var string $totalCustomers total number of customers
 * @var array $lang_catalogo translations of the catalog module
 */
?>
<div class="faq_page_bg">
    <div class="container">
        <!--faq area start-->
        <div class="faq_content_area pb-2">
            <div class="row">
                <div class="col-12">
                    <div class="faq_content_wrapper">

                    </div>
                </div>
            </div>
        </div>
        <!--faq area end-->
        <!--Accordion area-->
        <div class="accordion_area">
            <div class="row">
                <div class="col-12">
                    <h4><?= $lang_catalogo['select_customer'] ?></h4>
                    <form id="form-create" action="<?= seller_url('home/selectCustomer') ?>" method="post">
                        <div class="row">
                            <div class="col-md-10 col-sm-12">
                                <div class="product_variant quantity">
                                    <input autofocus data-url="<?= seller_url('home/searchCustomer') ?>" tabindex="2" id="id_search"
                                        placeholder="<?= $lang_catalogo['search_customer'] ?>" type="search"
                                        class="animated-search-filter bg-white w-100 m-0">
                                </div>
                            </div>
                            <div class="col-md-2 col-sm-12">
                                <button id="btn-aceptar" type="submit" form="form-create"
                                    class="btn btn-success btn-block p-2 w-100"><?= $lang_catalogo["btn_aceptar"] ?></button>
                            </div>
                        </div>
                        <br>
                        <div class="row">
                            <div class="col-md-9">
                                <!-- <div class="text-left">
                                    <h4><?= $lang_catalogo['select_customer'] ?></h4>
                                </div> -->
                            </div>
                            <div class="col-md-3">
                                <div class="">
                                    <?php
                                        $totalOwnedCustomers = count($customerSeller);
                                    ?>
                                    <h4 data-total="<?= $totalCustomers ?>"><?= $lang_catalogo["total_count"] ?> <?= $totalCustomers ?></h4>
                                    <h4 data-total-filtered="<?= $totalOwnedCustomers ?>"><?= $lang_catalogo["total_filtered"] ?> <span><?= $totalOwnedCustomers ?></span></h4>
                                </div>
                            </div>
                        </div>

                        <h2 <?= $totalOwnedCustomers<=0 ? 'hidden' : '' ?> hidden id="text-emtpy" class="text-center p-5"><?= $lang_catalogo['no_posee_clientes'] ?></h2>

                        <div class="faq-style-wrap animated-search-filter" id="customers_panel">
                            <!-- Panel-default  Customers -->
                            <?php $i=3; foreach ($customerSeller as $cus): $i++;?>
                            <div class="panel panel-default row-customers">
                                <div class="panel-heading">
                                    <h5 class="panel-title">
                                        <a tabindex="<?=$i?>" id="octagon" class="collapsed customer" role="button"
                                            data-bs-toggle="collapse" href="#faq-collapse-<?= $i ?>"
                                            aria-expanded="false" aria-controls="faq-collapse-<?= $i ?>"> <span
                                                class="button-faq"></span><?= strtoupper($cus->business_name. ' ('.$cus->name .')')  ?></a>
                                        <input hidden class="form-check-input" type="radio"
                                            value="<?= $cus->customer_id ?>" name="customer">
                                    </h5>
                                </div>
                                <div id="faq-collapse-<?= $i ?>" class="collapse" aria-expanded="true" role="tabpanel"
                                    data-bs-parent="#faq-five">
                                </div>
                            </div>
                            <?php endforeach;?>
                        </div>
                        <div class="shop_toolbar t_bottom">
                            <?php
                            $this->load->view('components/catalog/pagination');
                            ?>
                        </div>
                    </form>
                    <div id="loader" class="text-center d-none">
                        <img width="100px" src="<?= base_url('uploads/svg-loaders/svg-loaders/oval.svg?v='.$version) ?>" />
                    </div>
                </div>
            </div>
            <!--Accordion area end-->
        </div>
    </div>
</div>
