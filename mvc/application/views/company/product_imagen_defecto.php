<?php 
$compId = $this->session->userdata('compId');

$imagen_defectosinorden = $imagen_defecto ? $imagen_defecto->images : '';
$imagen_defecto = '';

if (count($productImagesList) > 0){

  foreach ($productImagesList as $key => $v){
    if ($v->img_default ==  1){

      if (file_exists('./uploads/products/'.$compId.'/'.$v->images)){
        $imagen_defecto = base_url('uploads/products/'.$compId.'/'.$v->images);

      }
    }
  }

  if ($imagen_defecto == '') {
    $imagen_defecto = base_url('uploads/products/'.$compId.'/'.$imagen_defectosinorden);
  }
}else{
  $imagen_defecto = base_url('uploads/noimg.png');
}

?>
<figure style="width: 12em;overflow:hidden; margin:auto;" class="rounded">
    <img class="" src="<?= $imagen_defecto ?>" class="rounded-0" style="display: block; width: 100%;">
</figure>
<br>
<button type="button" class="btn btn-success" data-toggle="modal" data-target="#modal_imagenes">
    <i class="fa-solid fa-edit"></i>
</button>