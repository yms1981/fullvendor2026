    <div class="bredcumbpnl">
        <div class="container">
            <h2>Company Details</h2>
            <ul>
                <li><a href="<?=base_url()?>">Home</a></li> // 
                <li>Company Details</li>
            </ul>
        </div>
    </div>
    <section class="innerpnl">
        <div class="container ">
            <div class="row">
                <div class="col-md-12">
                    <div class="companyDetailInfo">
                        <div class="companyLogo text-center">
                            <img src="<?= company_logo_url((string) @$data->image) ?>">
                        </div>
                        <div class="companyInfo">
                            <div class="row">
                                <div class="col-md-8">
                                    <h3><?=@$data->name?></h3>
                                    <p><strong><i class="fa fa-phone"></i> Phone:</strong> <?=@$data->phone_number?></p>
                                    <p><strong><i class="fa fa-envelope-open"></i> Email:</strong> <?=@$data->email?></p>
                                    <p><strong><i class="fa fa-calendar"></i> Join Date:</strong> <?=date('m-d-Y',strtotime(@$data->created))?></p>
                                </div>
                                <div class="col-md-4 text-right">
                                    <p class="mt-2"><span class="btn btn-sm btn-success">Active</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <h4 class="font-weight-bold text-danger mb-3 fs-20">Categories</h4>
                    <div class="categoryPnl">
                      <?php if(is_array($categoryList) && count($categoryList)>0){
                        $i=0;
                        foreach ($categoryList as $catlist) {
                        $i++; ?>
                        <label class="radio"><?=$catlist->category_name?>
                          <input type="radio" <?php if($i==1){?> checked="checked" <?php } ?> name="is_company" value="<?=$catlist->category_id?>" onclick="productsearch($(this).val(),'<?= @$data->company_id ?>')">
                          <span class="checkround"></span>
                        </label>
                      <?php } } ?>
                    </div>
                </div>
                <div class="col-md-9">
                    <h4 class="font-weight-bold text-danger mb-3 fs-20">Product List</h4>
                    <table class="table table-striped productListTable">
                        <thead>
                          <tr>
                            <th>#</th>
                            <th>Image</th>
                            <th>Product Name</th>
                            <th>SKU</th>
                            <th>Stock</th>
                            <th>Price</th>
                            <th width="120"></th>
                          </tr>
                        </thead>
                        <tbody class="productfilter_data">
                          <?php if(is_array($productList) && count($productList) > 0){
                            $j=0;
                            foreach ($productList as $plist) {
                              $curr=$this->mymodel->get_by('currency', true,array('currency_id'=>$plist->currency_type));
                             $j++; ?>
                          <tr role="row">
                            <td><?=$j?></td>
                            <td class="proimg">
                            <?php if (file_exists('./uploads/products/'.@$plist->productImage)) { ?>
                            <img src="<?= base_url('uploads/products/').@$plist->productImage ?>" class="img-responsive" alt="" style="width: 120px; max-height: 120px;">
                            <?php } else { ?>
                            <img src="<?= base_url('uploads/noimg.png') ?>" class="img-responsive" alt="" style="width: 120px; max-height: 100px;">
                            <?php } ?>
                            </td>
                            <td><?=@$plist->name?> </td>
                            <td><?=@$plist->sku?></td>
                            <td><?=@$plist->stock?></td>
                            <td><?= @$curr->symbol ?> <?= $plist->sale_price ?> </td>
                            <td>
                               <a href="<?=base_url('products/details/'.$plist->pro_id)?>" class="btn btn-secondary btn-sm">View Details</a>
                            </td>
                          </tr>
                        <?php } }else{ ?>
                          <tr class="text-center">
                            <td colspan="7">
                              <?php
                            echo "No data available";} ?>
                           </td>
                         </tr>
                        </tbody>
                    </table>

                    <div class="text-center paginationpnl mt-4">
                        <nav aria-label="Page navigation example">
                           <?=@$paginate?>
                          <!-- <ul class="pagination">
                            <li class="page-item">
                              <a class="page-link" href="#" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                                <span class="sr-only">Previous</span>
                              </a>
                            </li>
                            <li class="page-item active"><a class="page-link" href="#">1</a></li>
                            <li class="page-item"><a class="page-link" href="#">2</a></li>
                            <li class="page-item"><a class="page-link" href="#">3</a></li>
                            <li class="page-item">
                              <a class="page-link" href="#" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                                <span class="sr-only">Next</span>
                              </a>
                            </li>
                          </ul> -->
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </section>
     <script>
      function productsearch(catId,companyId) {
       $.ajax({
        url: '<?= base_url()?>marketplaces/productfilterSearchbyCategory',       
        type: 'POST',       
        dataType: 'json',       
        data: {categoryId:catId,companyId:companyId},
        success: function(responseproduct) 
        {
         
          $('.productfilter_data').html(responseproduct.product_list);
            //alert(response.log_list);

          }
        })
      }
    </script>


