<?php
/**
 * @var array $pageTexts The translated string conglomerated for this view
 */
?>
<div class="modal fade" id="modal_add_term" tabindex="-1" role="dialog" aria-labelledby="modalAddTermLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAddTermLabel">
                    <?= strtoupper($pageTexts['titulo_add'] ?? 'AGREGAR TÉRMINO DE PAGO') ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="modal_add_term_body">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="sr-only">Cargando...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

