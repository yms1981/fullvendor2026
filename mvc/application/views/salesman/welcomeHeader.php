<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title><?= SITENAME ?></title>
    <link rel="shortcut icon" href="<?= base_url('companyasset/images/favicon.png')?>" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <!-- Plugins CSS -->
    <link rel="stylesheet" href="<?= base_url('companyasset/antomi/assets/css/plugins.css')?>">
    <!-- Main Style CSS -->
    <link rel="stylesheet" href="<?= base_url('companyasset/antomi/assets/css/style.css')?>">
    <link rel="stylesheet" href="<?= dated_file_url('companyasset/antomi/assets/css/style_adicional.css')?>">

    <script>
        var urlBase = '<?= base_url('salesman') ?>'

    </script>
</head>

<body>

    <!--Offcanvas menu area start-->
    <div class="off_canvars_overlay"></div>
    <div class="Offcanvas_menu">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="canvas_open">
                        <a href="javascript:void(0)"><i class="ion-navicon"></i></a>
                    </div>
                    <div class="Offcanvas_menu_wrapper">
                        <div class="canvas_close">
                            <a href="javascript:void(0)"><i class="ion-android-close"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--Offcanvas menu area end-->
    <!--header area start-->
    <header>
        <div class="main_header header_padding">
            <div class="container">

                <!--header middel start-->
                <div class="header_middle sticky-header">
                    <div class="row align-items-center">
                        <div class="col-lg-2 col-md-3 col-4">
                            <div class="logo">
                                <a href="#" tabindex="-1">
                                    <img src="<?= (isset($company) && !empty($company->image))?company_logo_url((string) $company->image):base_url('images/logos/isologo.png') ?>"
                                        style="height: 70px;width: 70px; object-fit: scale-down;">
                                </a>
                            </div>
                        </div>
                        <div class="col-lg-7 col-md-12 text-center">
                            <h3><?= $lang_catalogo['welcome'] ." " . $info_seller->user->name ?></h3>
                        </div>
                        <div class="col-lg-3 col-md-7 col-6">
                            <div class="header_configure_area">
                                <div class="d-flex justify-content-end align-items-start">
                                    <div class="d-flex justify-content-end">
                                        <div class="btn-group" style="margin-bottom:7px;" data-tippy-placement="top"
                                             data-tippy-arrow="true" data-tippy-inertia="false"
                                             data-tippy="<?= $info_seller->user->name ?>">
                                            <button type="button" class="btn btn-danger dropdown-toggle rounded-circle"
                                                    data-bs-toggle="dropdown" aria-expanded="false"
                                                    style="width:50px;height:50px;">
                                                <i class="fa fa-user" aria-hidden="true"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="<?= base_url('logout') ?>"><i
                                                            class="fa fa-sign-out" aria-hidden="true"></i>
                                                        <?= $lang_catalogo['logout'] ?></a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </header>
    <!--header area end-->
