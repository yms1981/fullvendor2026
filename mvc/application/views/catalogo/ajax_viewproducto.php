<?php

$precio = number_format($precio_obtenido,$format_numeric->digits,$format_numeric->radixPoint,$format_numeric->groupSeparator);
$reduced_price = number_format($producto_conformato->price_reduced , $format_numeric->digits,$format_numeric->radixPoint,$format_numeric->groupSeparator);

$color_semaforo = $this->Helper->obtener_colorSemaforo($producto_conformato, $info_company);
$leyenda_semaforo = $this->Helper->obtener_leyendaSemaforo($color_semaforo, $info_company);



?>

<div class="modal_title mb-10">
	<h2><?= $producto_conformato->name ?></h2>
	<p>SKU: <?= strtoupper($producto_conformato->sku) ?></p>
	<?php  if($producto_conformato->minimum_stock !=0 && $producto_conformato->minimum_stock !=1){?>
		<p>PACK: <?= intval($producto_conformato->minimum_stock) ?></p>
	<?php }?>
	<p><?= strtoupper($lang_pop['unit']) ?> <?= strtoupper($producto_conformato->unit_type) ?></p>

</div>
<div class="price_box">
<span
	class="current_price <?=  $producto_conformato->price_reduced <> 0 ? 'strikethrough text-dark' : ''  ?>">$<?= $precio ?></span>
</div>
<?php if($producto_conformato->price_reduced <> 0): ?>
<div class="price_box">
<span class="current_price">$<?= $reduced_price ?></span>
</div>
<?php else: ?>
<div class="price_box">
<span class="current_price">&nbsp;</span>
</div>


<?php endif;?>

<div class="d-flex">
	<?php if($show_stock): ?>

	<!-- Mostrar stock -->
	<?php if($info_company->show_inventory): ?>
	<div class="price_box d-flex">
		<span class="badge text-bg-light text-danger"
			style="font-size: 14px;text-align: left;padding-left: 0;">STOCK:
			<?= $producto_conformato->stock <=0 ? 0 : number_format($producto_conformato->stock, 0) ?></span>

	</div>

	<?php else: ?>
	<div class="price_box">
		<span class="badge text-bg-light text-danger"
			style="font-size: 14px;text-align: left;padding-left: 0;">&nbsp;</span>

	</div>
	<br>
	<?php endif; ?>

    <?php
    if($info_company->show_semaforo_stock)
        $this->load->view('components/semaphore/semaphore',['info_company'=>$info_company,'product'=>$producto_conformato])
    ?>
	<?php endif; ?>
</div>
<div class="modal_description mb-15">
	<p><?= $producto_conformato->descriptions ?></p>
</div>
<div class="variants_selects">
	<div class="modal_add_to_cart">
		<form novalidate id="form-modal" action="<?= base_url('catalogo/producto/saveqty') ?>">
			<div class="variants_size">
				<h2><?= $lang_pop['comment'] ?></h2>
				<div class="order-notes">
					<textarea name="comment" style="height: auto;" id="order_note" placeholder="..."></textarea>
				</div>
			</div>
			<div class="variants_size">
				<h2><?= $lang_pop['qty'] ?></h2>
			</div>
			<input min="1"  name="qty" type="number" value="<?= ($product_detail!=null)?$product_detail->qty:0?>">
			<input hidden type="text" name="token" value="<?= $company->token_kor ?>">
			<input hidden type="text" name="origen" value="list">
			<input hidden name="product_id" type="text" value="<?= $producto_conformato->pro_id ?>">
			<button style="font-size: 15px;font-weight: 500;" id="m-btn-submit" class="boton-add-car"><span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span> <?= $lang_pop['add'] ?> <i class="fa fa-cart-plus" aria-hidden="true"></i></button>
		</form>
	</div>
</div>
<div class="modal_social">
	<!--
	<h2>Share this product</h2>
	<ul>
		<li class="facebook"><a href="#"><i class="fa fa-facebook"></i></a></li>
		<li class="twitter"><a href="#"><i class="fa fa-twitter"></i></a></li>
		<li class="pinterest"><a href="#"><i class="fa fa-pinterest"></i></a></li>
		<li class="google-plus"><a href="#"><i class="fa fa-google-plus"></i></a></li>
		<li class="linkedin"><a href="#"><i class="fa fa-linkedin"></i></a></li>
	</ul> -->
</div>
