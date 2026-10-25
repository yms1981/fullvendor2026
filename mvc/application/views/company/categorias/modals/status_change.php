<?php
/**
 * @var array $pageTexts
 */
?>
<div class="modal fade" id="modalchangestatus" tabindex="-1" aria-labelledby="modalchangestatusLabel"
     aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="col-12 form-asign-remove-seller" id="form-change-status"
                  action="<?= company_url('categories/bulkChangeStatus') ?>" method="post">
                <div class="modal-header pt-2 pb-2">
                    <h5 class="modal-title" id="modalchangestatusLabel">
                        <?= $pageTexts['change_status'] ?? 'Cambiar Estado' ?>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p><?= $pageTexts['change_status_description'] ?? 'Seleccione el nuevo estado para las categorías seleccionadas' ?></p>

                    <input type="hidden" name="categories" value=""/>
                    <select name="status_toupdate" class="form-control notranslate" id="multipleStatus">
                        <option value="1"><?= $pageTexts['select_actives'] ?? 'Activo' ?></option>
                        <option value="0"><?= $pageTexts['select_desactives'] ?? 'Inactivo' ?></option>
                    </select>

                </div>
                <div class="modal-footer">
                    <button id="btn-changestatus-category" form="form-change-status" type="submit"
                            class="btn btn-primary">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        <?= $pageTexts['btn-modal-aceptar'] ?? 'Aceptar' ?>
                    </button>
                    <button type="button" class="btn btn-danger"
                            data-dismiss="modal"><?= $pageTexts['btn-modal-cancelar'] ?? 'Cancelar' ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#form-change-status').on('submit', function(e) {
        e.preventDefault();

        // Get selected categories
        let selectedCategories = [];
        document.querySelectorAll('input[name="categoryIds[]"]:checked').forEach(function(checkbox) {
            selectedCategories.push(checkbox.value);
        });

        if (selectedCategories.length === 0) {
            alert('<?= $pageTexts['select_at_least_one'] ?? 'Seleccione al menos una categoría' ?>');
            return false;
        }

        // Set categories value
        $('input[name="categories"]').val(JSON.stringify(selectedCategories));

        // Show spinner
        $('#btn-changestatus-category .spinner-border').removeClass('d-none');

        // Submit form via AJAX
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                $('#btn-changestatus-category .spinner-border').addClass('d-none');

                if (response.success) {
                    $('#modalchangestatus').modal('hide');
                    // Show success message
                    alert_func([response.message || '<?= $pageTexts['status_updated'] ?? 'Estado actualizado correctamente' ?>', 'success']);
                    // Reload page to show updated statuses
                    setTimeout(function() {
                        window.location.reload();
                    }, 1500);
                } else {
                    // Show error message
                    alert_func([response.message || '<?= $pageTexts['status_update_error'] ?? 'Error al actualizar el estado' ?>', 'error']);
                }
            },
            error: function() {
                $('#btn-changestatus-category .spinner-border').addClass('d-none');
                alert_func(['<?= $pageTexts['status_update_error'] ?? 'Error al actualizar el estado' ?>', 'error']);
            }
        });
    });
});
</script>
