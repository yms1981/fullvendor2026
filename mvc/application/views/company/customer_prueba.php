<div class="container-fluid page-body-wrapper">
  <div class="main-panel">
    <div class="container-fluid pt-3">
      <div class="row">

        <form class="col-12" action="<?= company_url('customer/formprueba') ?>" method="POST">
          <div class="row">
            <div class="col-6">
              <div class="form-group">
                <label>status</label>
                <input type="text" name="prdstatus" value="<?= $_POST['prdstatus'] ?>">
              </div>
            </div>
            <div class="col-6">
              <div class="form-group">
                <label>Nombre</label>
                <input type="text" name="nombre" value="<?= $_POST['nombre'] ?>">
              </div>
            </div>
          </div>
          <input type="submit" name="enviar" value="Enviar">
        </form>
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>

                  <th class="fuente_16px" width="20"  style="text-align: left"><a data-sort="id" data-order_type="<?= $order_type_id ?>" href="javascript:void(0)" class="sort"><u>Id</u></a></th>
                  <th><?= $langprods['colmn_image_producto'] ?></th>
                  <th class="fuente_16px"><a data-sort="sku" data-order_type="<?= $order_type_sku ?>" href="javascript:void(0)" class="sort"><u>SKU</u></a></th>
                  <th class="fuente_16px"><a data-sort="name" data-order_type="<?= $order_type_name ?>" href="javascript:void(0)" class="sort"><u><?= $langprods['colmn_name_producto'] ?></u></a></th>
                  <th class="fuente_16px text-center" style="text-align: right;width:8%;"><a data-sort="price" data-order_type="<?= $order_type_price ?>" href="javascript:void(0)" class="sort"><u><?= $langprods['colmn_price'] ?></u></a></th>
                  <th class="fuente_16px"><?= $langprods['colmn_category'] ?></th>
                  <th>
                    <a data-sort="tags" data-order_type="<?= $order_type_tags ?>" href="javascript:void(0)" class="sort"><u><?= $langprods['column_tags'] ?></u></a>
                  </th>
                  <th class="fuente_16px"><a data-sort="stock" data-order_type="<?= $order_type_stock ?>" href="javascript:void(0)" class="sort"><u>Stock</u></a></th>
                  <th>
                    <a data-sort="location" data-order_type="<?= $order_type_location ?>" href="javascript:void(0)" class="sort"><u><?= $langprods['column_location'] ?></u>
                    </a>
                  </th>
                  <th class="fuente_16px text-center" style="width:7%;"><a data-sort="moq" data-order_type="<?= $order_type_moq ?>" href="javascript:void(0)" class="sort"><u>MOQ</u></a></th>
                  <th class="fuente_16px"><?= $langprods['colmn_status'] ?></th>
                  <th class="fuente_16px"><?= $langprods['colmn_action'] ?></th>
                </tr>
              </thead>
              <tbody class="productfilter_data">
                <?php $i = 1;
                ?>
                <?php foreach (@$list as $key => $v): ?>
                  <?php
                  $currInfo= $this->mymodel->get('currency', true, 'currency_id', @$company->currency);
                  $primg=$this->mymodel->get('product_images', true, 'product_id', $v->pro_id);
                  $compId = $this->session->userdata('compId');
                  ?>
                  <tr>
                    <td style="vertical-align: top;text-align: center;" class="fuente_16px">
                      <br>
                      <a href="<?= company_url('product/edit/'.$v->pro_id)?>" title="Edit"><?= $v->pro_id ?></a>
                    </td>
                    <td class="">
                      <figure data-defeto="<?= $v->imagen_por_defecto ?>" style="width: 6em; height: 6em; overflow:hidden; margin:auto;">
                        <?php
                        $imagen = null;

                        if ($v->imagen_principal) {

                         $imagen = $v->imagen_principal;

                       }else if($v->imagen_por_defecto){
                         $imagen = $v->imagen_por_defecto;
                       }

                       ?>

                       <?php if ($imagen!="") { ?>
                        <span data-toggle="popover-hover" title="<?= $v->sku ?>" data-img="<?= base_url('uploads/products/'.$compId . '/').$imagen .'?v=' . $v_imagen ?>">
                          <img src="<?= base_url('uploads/products/'.$compId . '/').$imagen .'?v=' . $v_imagen ?>" style="display: block; width: 100%; height: 100%;" class="rounded-0" alt=""></span>
                        <?php } else { ?>
                          <img src="<?= base_url('uploads/noimg.png') .'?v=' . $v_imagen ?>" class="rounded-0" style="display: block; width: 100%; height: 100%;" alt="">
                        <?php } ?>
                      </figure>
                    </td>
                    <td style="vertical-align: top;" class="fuente_16px">
                      <br>
                      <a href="<?= company_url('product/edit/'.$v->pro_id)?>" title="Edit"><?= $v->sku ?></a>
                      <a title="Barcode: <?= $v->barcode ?>"><img src="<?= base_url('images/codbarra.png')?>" alt="<?= $v->barcode ?>" class="img-fluid img-thumbnail" style="width: 20px; height: 20px;"></a>
                    </td>
                    <td style="vertical-align: top;" class="fuente_16px text-wrap">
                      <br><a href="<?= company_url('product/edit/'.$v->pro_id)?>" title="Edit"><?= strtoupper($v->name) ?></a></td>
                      <?php
                      if ($valores_formato['tiene_codigoRegional']) {
                        $codigo_regional = $valores_formato['codigoRegional'];
                        $fmt = new NumberFormatter($codigo_regional, NumberFormatter::DECIMAL);
                        $precio = $fmt->format($v->sale_price);
                        $stock = $fmt->format($v->stock);
                        $moq = $fmt->format($v->minimum_stock);
                      }else{
                        $precio = $v->sale_price;
                        $stock= $v->stock;
                      }
                      ?>
                      <td style="vertical-align: top;" class="fuente_16px text-center">
                        <br>
                        <span class="text-success"> <?= @$currInfo->symbol ?> <?= $precio ?></span></td>
                        <td style="vertical-align: top;" class="fuente_16px text-wrap">
                          <br>
                          <?php
                          $catnames=explode(",",@$v->category_id);
                          $catname=array();
                          foreach ($catnames as $cat) {
                            $productcat=$this->mymodel->get_by('categories', true, array('cat_id' =>$cat,'cat_id!='=>'0'));

                            $catname[]=@$productcat->category_name;
                          }
                          $comma_separated = implode(" | ", $catname);
                          echo "<span>" .$comma_separated."</span>";
                          ?>
                          <input type="hidden" name="categoryName[]"  value="<?=@$comma_separated?>" class="checkBoxcategory<?=$v->pro_id?>">
                        </td>
                        <td style="vertical-align: top;">
                          <br>
                          <?= $v->tags ?>
                        </td>
                        <td style="vertical-align: top;" class="fuente_16px">
                          <br>
                          <strong><?= ucwords($v->unit_type) ?>: </strong>
                          <?= $stock ?>

                        </td>
                        <td style="vertical-align: top;">
                          <br>
                          <?= $v->locations ?>
                        </td>
                        <td style="vertical-align: top;" class="fuente_16px text-center"><br><span><?= $moq ?></span></td>
                        <td style="vertical-align: top;" width="120px">
                          <br>
                          <?php
                          if($v->status == "1"){
                            echo '<a href="'. company_url('product/deshabilitar/'.$v->pro_id) .'" class="btn btn-warning btn-sm w-100 boton_estado" data-loader="'. $langprods['loader-disabling'] .'">'.$langprods['option_desactive'].'</a>';

                          } elseif($v->status == "0"){

                            echo '<a href="'. company_url('product/habilitar/'.$v->pro_id) .'" class="btn btn-success btn-sm w-100 boton_estado" data-loader="'. $langprods['loader-enabling'] .'"><font color="White">'.$langprods['option_active'].'</font></a>';
                          } ?>
                        </td>

                        <td style="vertical-align: top;">
                          <br>
                          <a href="<?= company_url('product/view/'.$v->pro_id) ?>" class="btn btn-success btn-sm" data-toggle="tooltip" title="View">
                            <i class="mdi mdi-eye"></i>
                          </a>
                      <!-- <a href="<?= company_url('product/edit/'.$v->pro_id)?>?redirectto=<?=urlencode($url)?>" class="btn btn-warning btn-sm" data-toggle="tooltip" title="Edit">
                        <i class="mdi mdi-pencil"></i>
                      </a> -->
                      <a href="<?= company_url('product/edit/'.$v->pro_id)?>" class="btn btn-warning btn-sm" title="Edit">
                        <i class="mdi mdi-pencil"></i>
                      </a>
                      <button type="button" class="btn btn-success btn-sm"  data-toggle="modal" data-target="#GSCCModal<?=$v->pro_id?>" title="Add to catalog">
                        <i class="mdi mdi-plus"></i>
                      </button>
                    </td>
                  </tr>
                  <div id="GSCCModal<?=$v->pro_id?>" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                   <div class="modal-dialog">
                    <form action="<?= company_url('product/addtocatalog')?>" method="post">
                      <div class="modal-content">
                        <div class="modal-header">
                          <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;  </button>
                        </div>
                        <div class="modal-body">
                          <label>Catalog: <span class="color-red">*</span></label>
                          <input type="hidden" name="product_id" value="<?= @$v->sku ?>">
                          <select class="form-control notranslate" name="catalog_id">
                            <?php foreach ($cataloglist as $catalog) { ?>
                              <option value="<?=$catalog->catalog_id?>"><?=$catalog->catalog_name?></option>
                            <?php } ?>
                          </select>
                        </div>
                        <div class="modal-footer">
                          <button type="submit" class="btn btn-success">Add to catalog </button>
                        </div>
                      </div>
                    </form>
                  </div>
                </div>
                <?php $i++; ?>
              <?php endforeach ?>

            </tbody>
          </table>
          <div class="paginate">
           <?=@$paginate?>
         </div>
       </div>


      </div>
    </div>
  </div>
</div>
