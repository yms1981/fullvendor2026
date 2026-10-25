<?php
$CI = &get_instance();
$setting = method_exists($CI, 'getPlatformSettings') ? $CI->getPlatformSettings() : (object) [];
$caturl = $CI->session->userdata('setUrl');
 ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= SITENAME ?></title>
    <link rel="shortcut icon" href="<?= base_url('companyasset/images/favicon.png')?>" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&amp;display=swap" rel="stylesheet">
    <link href="<?=base_url('frontend/')?>css/bootstrap.min.css" rel="stylesheet">
    <link href="<?=base_url('frontend/')?>css/font-awesome.min.css" rel="stylesheet">
    <link href="<?=base_url('frontend/')?>css/magnific-popup.css" rel="stylesheet">
    <link href="<?=base_url('frontend/')?>css/owl.carousel.min.css" rel="stylesheet">
    <link href="<?=base_url('frontend/')?>css/owl.theme.default.min.css" rel="stylesheet">
    <link href="<?=base_url('frontend/')?>css/slide-css.css" rel="stylesheet">
    <link href="<?=base_url('frontend/')?>css/style.css" rel="stylesheet">
    <link href="<?=base_url('frontend/')?>css/responsive.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@9.17.2/dist/sweetalert2.min.css">
</head>

<body>
    <nav class="navbar navbar-b navbar-trans navbar-expand-md fixed-top" id="mainNav">
        <div class="container">
            <div class="hederrPnl">
                <div class="row align-items-center">

                    <div class="col-md-4">
                        <a class="navbar-brand logocatlog js-scroll" href="<?=@$caturl?>">
                            <img src="<?= company_logo_url((string) @$company->image) ?>" alt="Logo">
                        </a>
                    </div>
                    <div class="col-md-7 offset-md-1">
                        <div class="row">
                            <div class="col-md-9 col-8">
                                <div class="searchForm">
                                    <form action="javascript:void(0);" method="post">
                                        <input type="text" class="form-control" id="prsearchName" autocomplete="off"  placeholder="Search here">
                                        <button class="searchBtn" onclick="getproductsearch()"><i class="fa fa-search" aria-hidden="true"></i></button>
                                    </form>
                                </div>
                            </div>
                            <div class="col-md-3 text-right col-4">
                                <a href="<?=base_url('products/ordercheckout/'.$token)?>"><div class="cartboloc"><i class="fa fa-shopping-cart" aria-hidden="true"></i> Cart <span><?=@$catnums?></span></div></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </nav>
