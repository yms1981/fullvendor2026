<?php $catalogUriIndexBv = isset($catalog_uri_index) ? (string) $catalog_uri_index : 'catalogo/producto'; ?>
<a onclick="cleanCart(event)" href="javascript:void(0)" data-href="<?= base_url('catalogo/producto/vaciarCarrito?token='.$company->token_kor) ?>" data-redirect="<?= base_url($catalogUriIndexBv) ?>?token=<?= rawurlencode((string) $company->token_kor) ?>" class="btn btn-danger"
   type="button">
   <?= isset($lang_catalogo['vaciar_carrito']) && $lang_catalogo['vaciar_carrito'] !== '' ? $lang_catalogo['vaciar_carrito'] : (isset($info_company->language_id) && (int)$info_company->language_id === 2 ? 'Vaciar carrito' : 'Clear cart') ?>
</a>
