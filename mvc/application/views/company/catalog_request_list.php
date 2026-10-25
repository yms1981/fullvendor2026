<!-- partial -->
<div class="container-fluid page-body-wrapper pt-3">
    <div class="main-panel">
        <div class="container-fluid contentHeight">
            <div class="row">
                <div class="col-md-6">
                    <div class="page-header">
                        <h3 class="page-title"> <?= @$title ?></h3>
                    </div>
                </div>
                <div class="col-md-6 text-right">
                    <a href="<?= company_url('catalog')?>" class="btn btn-success">Catalog List</a>
                    <!-- <a href="<?=company_url('Twiliosms/catalog_send')?>" class="btn btn-success">  Send SMS </a> -->

                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <div class="table-responsive">
                                <table id="infoTable" class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th width="20">#</th>
                                            <th>Request ID</th>
                                            <th>Seller Info</th>
                                            <th>Customer Info</th>
                                            <th>Request Date</th>
                                            <th width="60">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $i = 1; ?>
                                        <?php foreach ($list as $key => $v): ?>
                                        <?php
                      $customer=$this->mymodel->get_by('customers', true, array('customer_id' => @$v->customer_id));
                       ?>
                                        <tr>
                                            <td><?= $i ?></td>
                                            <td><?=$v->request_id?></td>
                                            <td>

                                                <?= @$v->first_name.' '.@$v->last_name ?><br>
                                                <?= @$v->email?><br>
                                            </td>
                                            <td>
                                                <?= @$customer->business_name ?><br>
                                                <?= @$customer->email?>
                                            </td>
                                            <td><?= fv_format_display_date(@$v->created, fv_resolve_display_date_format() . ' h:i A') ?></td>
                                            <td>
                                               <div class="form-group text-nowrap">
                                                   <?php 
                                                   $mailcount=$this->db->query("SELECT catlog_reqest_id FROM catalog_sent_mail WHERE catlog_reqest_id='".@$v->request_id."' AND customer_emails='".@$customer->email."'")->num_rows();
                                                   if($mailcount >0){ ?>
                                                   <a href="##" class="btn btn-success btn-sm">Sent</a>
                                                   <a href="<?= company_url('twiliosms/sendrequestcatalogSms/'.$v->request_id) ?>"
                                                       class="btn btn-success btn-sm">Send sms</a>
                                                   <a class="btn btn-warning btn-sm" href="javascript:void(0);" title="Preview"
                                                       data-toggle="modal"
                                                       data-target="#previewModal<?=@$v->request_id?>">View</a>
                                                   <?php }else{
                                                   ?>
                                                   <a href="<?= company_url('catalog/send_catalog/'.$v->request_id) ?>"
                                                       class="btn btn-success">Send</a>
                                                   <a href="<?= company_url('twiliosms/sendrequestcatalogSms/'.$v->request_id) ?>"
                                                       class="btn btn-success">Send sms</a>
                                                   <?php } ?>
                                               </div>                     

                                                <div class="modal fade" id="previewModal<?=$v->request_id?>"
                                                    tabindex="-1" aria-labelledby="exampleModalLabel"
                                                    aria-hidden="true">
                                                    <div class="modal-dialog modal-lg">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="exampleModalLabel">Preview
                                                                </h5>
                                                                <button type="button" class="close" data-dismiss="modal"
                                                                    aria-label="Close">
                                                                    <span aria-hidden="true">&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="table-responsive">
                                                                    <table class="table">
                                                                        <thead>
                                                                            <tr>
                                                                                <th>Hide Price</th>
                                                                                <th>Catalog Name</th>
                                                                                <th>Text</th>
                                                                                <th>Email Text</th>
                                                                                <th>Send Date</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            <?php 
                                                                                $reqmail=$this->mymodel->get_by('catalog_sent_mail', true, array('catlog_reqest_id' => @$v->request_id));
                                                                                $catlogInfo=$this->mymodel->get_by('catalog', true, array('catalog_id' => @$reqmail->catalog_id));
                                                                                ?>
                                                                            <tr>
                                                                                <td><?php if(@$reqmail->hide_price==0){echo "No";}else{echo"Yes";}?>
                                                                                </td>
                                                                                <td><?=@$catlogInfo->catalog_name?></td>
                                                                                <td><?=@$reqmail->catalog_text?></td>
                                                                                <td><?=@$reqmail->catalog_email_text?></td> 
                                                                                <td><?=fv_format_display_date(@$reqmail->send_date)?></td>
                                                                            </tr>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
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
<script>
$(document).ready(function() {
  activar_tabla()
});
</script>