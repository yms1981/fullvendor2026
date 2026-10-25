<!-- partial -->
<div class="container-fluid page-body-wrapper pt-3">
    <div class="main-panel">
        <div class="container-fluid contentHeight">
            <div class="row">
                <div class="col-md-4">
                    <div class="page-header">
                        <h3 class="page-title"> <?= @$title ?></h3>
                    </div>
                </div>

            </div>
            <ul class="nav nav-tabs searchTab">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" href="#home">Products</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#menu1">Warehouses</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#menu2">Sellers</a>
                </li>
            </ul>
            <div class="card">
                <div class="card-body">

                    <!-- Tab panes -->
                    <div class="tab-content searchTabcontent">
                        <div class="tab-pane active" id="home">
                            <div class="row">
                                <div class="col-12">
                                    <div class="table-responsive">
                                        <table id="order-listing" class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th width="20">#</th>
                                                    <th>Product Name</th>
                                                    <th>Minimum Stock</th>
                                                    <th>SKU</th>
                                                    <th>Stock</th>
                                                    <th>Price</th>
                                                    <th>Created</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $i = 1; ?>
                                                <?php foreach (@$productlist as $key => $v): ?>
                                                <tr>
                                                    <td><?= $i ?></td>
                                                    <td><?= $v->name ?></td>
                                                    <td><?= @$v->minimum_stock ?></td>
                                                    <td><?= $v->sku ?></td>
                                                    <td><?= $v->stock ?></td>
                                                    <td><span class="text-success"> <?= @$v->currency_symbol ?>
                                                            <?= $v->purchase_price ?></span> </td>
                                                    <td><?= fv_format_display_date($v->created_on) ?></td>
                                                </tr>
                                                <?php $i++; ?>
                                                <?php endforeach ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="menu1">
                            <div class="row">
                                <div class="col-12">
                                    <div class="table-responsive">
                                        <table id="ware-listing" class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th width="20">#</th>
                                                    <th>Name</th>
                                                    <th>Email</th>
                                                    <th>Mobile</th>
                                                    <th width="60">Join Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $i = 1; ?>
                                                <?php foreach ($warehouselist as $key => $v): ?>
                                                <tr>
                                                    <td><?= $i ?></td>
                                                    <td> <?= @$v->first_name ?> <?= @$v->last_name ?></td>
                                                    <td><?= @$v->email ?></td>
                                                    <td><?= @$v->phone_number ?></td>
                                                    <td><?= fv_format_display_date(@$v->created) ?></td>
                                                </tr>
                                                <?php $i++; ?>
                                                <?php endforeach ?>

                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="menu2">
                            <div class="row">
                                <div class="col-12">
                                    <div class="table-responsive">
                                        <table id="seller-listing" class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th width="20">#</th>
                                                    <th>Name</th>
                                                    <th>Email</th>
                                                    <th>Username </th>
                                                    <th>Phone Number</th>
                                                    <th width="60">Join Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $i = 1; ?>
                                                <?php foreach ($sellerlist as $key => $v): ?>
                                                <tr>
                                                    <td><?= $i ?></td>
                                                    <td class="proimg">
                                                        <?php if (@$v->profile_image && file_exists('./uploads/users/'.@$v->profile_image)) { ?>
                                                        <img src="<?= base_url('uploads/users/'.@$v->profile_image) ?>"
                                                            alt="">
                                                        <?php } else { ?>
                                                        <img src="<?= base_url('uploads/noimg.png') ?>" alt="">
                                                        <?php } ?>
                                                        <?= @$v->first_name ?> <?= @$v->last_name ?>
                                                    </td>
                                                    <td><?= @$v->email ?></td>
                                                    <td><?= @$v->username ?></td>
                                                    <td><?= @$v->phone_number ?></td>
                                                    <td><?= fv_format_display_date(@$v->created) ?></td>
                                                </tr>
                                                <?php $i++; ?>
                                                <?php endforeach ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                </div>
            </div>
        </div>