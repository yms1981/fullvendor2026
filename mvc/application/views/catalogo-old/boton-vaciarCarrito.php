<a onclick="cleanCart(event)" href="javascript:void(0)" data-href="<?= base_url('catalogo/producto/vaciarCarrito?token='.$company->token_kor) ?>" class="btn btn-danger"
   type="submit">
   <?= $lang_catalogo['vaciar_carrito'] ?>
</a>
