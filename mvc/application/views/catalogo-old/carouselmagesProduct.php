<div class="tab-content product-details-large" style="height: 70%">
    <?php $cont = 0; ?>
    <?php foreach ($imagenes as $imagen) {?>

        <?php $cont = $cont +1; ?>
        <div class="tab-pane fade<?= ($cont ==1)?' shown active':'' ?>" id="tab<?= $cont ?>" role="tabpanel">
            <div class="modal_tab_img">
                <?php if (file_exists('./uploads/products/'.$company->company_id . '/'.$imagen->images)) { ?>
                   <a href="#">
                    <img style="height: 400px;width: 100%;object-fit: scale-down;" src="<?= base_url('uploads/products/'.$company->company_id . '/').$imagen->images .'?v=' . $version ?>" alt="">
                   </a>
               <?php } else { ?>    
                   <a href="#">
                    <img style="height: 400px;width: 100%;object-fit: scale-down;" src="<?= base_url('uploads/noimg.png') .'?v=' . $version ?>" alt="">
                </a>
               <?php } ?>

           </div>
       </div> 
   <?php } ?>    
</div>
<div class="modal_tab_button" style="height: 30%">
    <ul class="nav product_navactive owl-carousel" role="tablist">
        <?php $cont = 0; ?>
        <?php foreach ($imagenes as $imagen) {?>    
        <?php $cont = $cont +1; ?>
            <li>
                <?php if (file_exists('./uploads/products/'.$company->company_id . '/'.$imagen->images)) { ?>
                    <a class="nav-link<?= ($cont ==1)?' active':'' ?>" data-toggle="tab" href="#tab<?= $cont ?>" role="tab" aria-controls="tab<?= $cont ?>" aria-selected="false">
                     <img style="height: 100px;width: 100%;object-fit: scale-down;" src="<?= base_url('uploads/products/'.$company->company_id . '/').$imagen->images .'?v=' . $version ?>" alt="">
                    </a>
                <?php } else { ?>
                    <a class="nav-link<?= ($cont ==1)?' active':'' ?>" data-toggle="tab" href="#tab<?= $cont ?>" role="tab" aria-controls="tab<?= $cont ?>" aria-selected="false">
                        <img style="height: 100px;width: 100%;object-fit: scale-down;" src="<?= base_url('uploads/noimg.png') .'?v=' . $version ?>" alt="">
                    </a>

                <?php } ?>
                
            </li>
        <?php } ?>    
    </ul>
</div>
