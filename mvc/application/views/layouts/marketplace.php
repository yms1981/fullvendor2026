<?php
/**
 * @var string $main_content the content template path
 * @var array $lang_catalogo translations of the catalog module
 */
?>
<!DOCTYPE html>
<html>
<head>
    <?php
    $this->load->view('common/head');
    $this->load->view('catalogo/header-scripts/scripts-css');
    ?>
</head>
<body>
<?php
$this->load->view('company/layouts/loader'); ?>
<!--Offcanvas menu area start-->
<div class="off_canvars_overlay"></div>
<div class="Offcanvas_menu">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="canvas_open">
                    <!-- <a href="javascript:void(0)"><i class="ion-navicon"></i></a> -->
                    <a id="seach_insidebar" hidden href="javascript:void(0)"><i
                            class="ion-ios-search-strong"></i></a>
                </div>
                <div class="Offcanvas_menu_wrapper">
                    <br><br>
                    <div class="search_container">
                        <form class="form-filtro-buscar" action="<?= base_url('catalogo/producto') ?>" method="get">
                            <input hidden class="short-value" type="text" name="sort">
                            <div class="hover_category movil">
                                <select class="select_option" name="cat_id">
                                    <option selected value><?= strtoupper($lang_catalogo['option_all']) ?></option>
                                    <?php

                                    if (count($categorias) > 0) {
                                        foreach ($categorias as $cat) {
                                            if ($cat->qty_products > 0) {
                                                ?>
                                                <option <?= $this->input->get(
                                                    'cat_id'
                                                ) == $cat->cat_id ? 'selected' : '' ?>
                                                    value="<?= $cat->cat_id ?>">
                                                    <?= strtoupper(
                                                        $cat->category_default == 1 ? $lang_catalogo["sin_categoria"] : $cat->category_name
                                                    ) ?>
                                                </option>
                                                <?php
                                            }
                                        } ?>
                                        <?php
                                    } ?>
                                </select>
                            </div>
                            <div class="search_box">
                                <input name="search" value="<?= $this->input->get('search') ?>"
                                       placeholder="<?= $lang_catalogo['seach_product'] ?>" type="text">
                                <button type="submit"><?= $lang_catalogo['search'] ?></button>
                            </div>
                        </form>
                    </div>
                    <div id="menu" class="text-start ">

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="app_vue">
    <?php
    $this->load->view('components/catalog/header'); ?>

    <section id="main-content">
        <?php
        $this->load->view($main_content); ?>
    </section>
</div>
<footer>
    <?php
    $this->load->view('components/catalog/footer');
    ?>
</footer>
</body>
</html>
