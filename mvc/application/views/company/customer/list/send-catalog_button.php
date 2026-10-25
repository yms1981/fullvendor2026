<?php
/**
 * @var object $listItem the list item that has state changing capabilities
 * @var string $controllerUrl the URL of the crud controller
 * @var array $pageTexts The page standard texts for the entity module listing
 */
$isEnabled = ($listItem->catalog_emails == 1);
$customerId = $listItem->customer_id ?? '';
$enableUrl = "{$controllerUrl}/enableEmail/{$customerId}";
$disableUrl = "{$controllerUrl}/disableEmail/{$customerId}";
$errorMsg = $pageTexts['msg_error'] ?? '';
?>
<style>
.custom-switch-red .custom-control-input:checked ~ .custom-control-label::before {
    background-color: #dc3545;
    border-color: #dc3545;
}
.custom-switch-red .custom-control-input:focus ~ .custom-control-label::before {
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}
.custom-switch-red .custom-control-input:checked:focus ~ .custom-control-label::before {
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}
</style>
<div class="custom-control custom-switch custom-switch-red">
    <input type="checkbox" 
           class="custom-control-input catalog-email-switch" 
           id="catalog-switch-<?= htmlspecialchars($customerId) ?>"
           data-customer-id="<?= htmlspecialchars($customerId) ?>"
           data-enable-url="<?= htmlspecialchars($enableUrl) ?>"
           data-disable-url="<?= htmlspecialchars($disableUrl) ?>"
           data-error-message="<?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8') ?>"
           <?= $isEnabled ? 'checked' : '' ?>>
    <label class="custom-control-label" for="catalog-switch-<?= htmlspecialchars($customerId) ?>"></label>
</div>

<script>
(function() {
    if (typeof jQuery !== 'undefined') {
        jQuery(document).ready(function($) {
            $(document)
                .off('change.catalogEmailSwitch', '.catalog-email-switch')
                .on('change.catalogEmailSwitch', '.catalog-email-switch', function() {
                var $switch = $(this);
                var isChecked = $switch.is(':checked');
                var url = isChecked ? $switch.data('enable-url') : $switch.data('disable-url');
                var $label = $switch.next('label');
                
                // Deshabilitar switch mientras se procesa
                $switch.prop('disabled', true);
                
                // Mostrar indicador de carga
                $label.html('<span class="spinner-border spinner-border-sm" role="status"></span>');
                
                $.ajax({
                    url: url,
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        var $parent = $switch.closest('td');
                        if ($parent.length && response && response.html) {
                            $parent.html(response.html);
                        }
                        var successMessage = (response && response.message) ? response.message : '';
                        if (typeof swal === 'function' && successMessage) {
                            swal({
                                title: successMessage,
                                icon: 'success'
                            });
                        } else if (successMessage) {
                            alert(successMessage);
                        }
                    },
                    error: function(xhr) {
                        // Revertir el switch en caso de error
                        $switch.prop('checked', !isChecked);
                        $switch.prop('disabled', false);
                        $label.html('');
                        var errorMessage = '';
                        if (xhr && xhr.responseJSON && xhr.responseJSON.msg) {
                            errorMessage = xhr.responseJSON.msg;
                        } else if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        if (!errorMessage) {
                            errorMessage = $switch.attr('data-error-message') || '';
                        }
                        if (typeof swal === 'function' && errorMessage) {
                            swal({
                                title: errorMessage,
                                icon: 'error'
                            });
                        } else if (errorMessage) {
                            alert(errorMessage);
                        }
                    }
                });
                });
        });
    }
})();
</script>
