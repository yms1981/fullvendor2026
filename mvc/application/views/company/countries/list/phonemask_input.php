<?php
/**
 * @var object $listItem the country item from the list
 * @var mixed $value the value of phonemask field
 */

$countryId = $listItem->id ?? '';
$phonemask = trim($listItem->phonemask ?? '');
$phonecode = trim($listItem->phonecode ?? '');

// Guardar phonecode en un atributo data para el JavaScript

// Generar ejemplo de máscara
$exampleNumber = '';
if (!empty($phonemask)) {
    // Reemplazar # con dígitos de ejemplo
    $exampleDigits = '1234567890';
    $exampleNumber = $phonemask;
    $digitIndex = 0;
    for ($i = 0; $i < strlen($exampleNumber) && $digitIndex < strlen($exampleDigits); $i++) {
        if ($exampleNumber[$i] === '#') {
            $exampleNumber[$i] = $exampleDigits[$digitIndex];
            $digitIndex++;
        }
    }
    // Si quedan # sin reemplazar, usar 0
    $exampleNumber = str_replace('#', '0', $exampleNumber);
}

// Construir ejemplo completo con phonecode si existe
$fullExample = '';
if (!empty($phonecode)) {
    $fullExample = '+' . $phonecode . ' ' . $exampleNumber;
} else {
    $fullExample = $exampleNumber;
}
?>

<div class="phonemask-editable-container">
    <div class="form-group mb-2">
        <label class="small text-muted mb-1" style="font-size: 0.75rem; font-weight: 600;">
            Phone Code: <strong><?= htmlspecialchars($phonecode ? '+' . $phonecode : 'N/A') ?></strong>
        </label>
        <div class="input-group input-group-sm" style="max-width: 200px;">
            <input type="text" 
                   class="form-control form-control-sm phonemask-input" 
                   value="<?= htmlspecialchars($phonemask) ?>" 
                   data-country-id="<?= htmlspecialchars($countryId) ?>"
                   data-phonecode="<?= htmlspecialchars($phonecode) ?>"
                   data-original-value="<?= htmlspecialchars($phonemask) ?>"
                   placeholder="Ej: (###) ### ####"
                   id="phonemask-input-<?= htmlspecialchars($countryId) ?>"
                   style="font-size: 0.85rem;">
            <div class="input-group-append">
                <button class="btn btn-primary btn-sm phonemask-save-btn" 
                        type="button"
                        data-country-id="<?= htmlspecialchars($countryId) ?>"
                        title="Guardar máscara">
                    <i class="mdi mdi-content-save"></i>
                </button>
            </div>
        </div>
    </div>
    <?php if (!empty($exampleNumber)): ?>
        <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">
            Ejemplo: <strong><?= htmlspecialchars($fullExample) ?></strong>
        </small>
    <?php else: ?>
        <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">
            Ingrese una máscara (use # para dígitos)
        </small>
    <?php endif; ?>
    <div class="save-indicator-container mt-1" style="min-height: 18px;"></div>
</div>

<script>
// Script para guardar al hacer clic en el botón
(function() {
    if (typeof jQuery !== 'undefined') {
        jQuery(document).ready(function($) {
            // Guardar al hacer clic en el botón
            $(document).on('click', '.phonemask-save-btn', function() {
                var $btn = $(this);
                var countryId = $btn.data('country-id');
                var $input = $('#phonemask-input-' + countryId);
                var newValue = $input.val().trim();
                var originalValue = $input.data('original-value') || '';
                
                // Si no cambió, no hacer nada
                if (newValue === originalValue) {
                    showIndicator($input, 'info', 'Sin cambios');
                    return;
                }
                
                // Deshabilitar input y botón mientras se guarda
                $input.prop('disabled', true);
                $btn.prop('disabled', true);
                
                // Mostrar indicador de guardado
                showIndicator($input, 'loading', 'Guardando...');
                
                // Hacer petición AJAX para actualizar
                $.ajax({
                    url: '<?= company_url("countries/updatePhonemask/") ?>' + countryId,
                    method: 'POST',
                    data: {
                        phonemask: newValue
                    },
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function(response) {
                        // Actualizar valor original
                        $input.data('original-value', newValue);
                        
                        showIndicator($input, 'success', 'Guardado');
                        
                        // Actualizar ejemplo si existe
                        updatePhoneMaskExample($input, newValue);
                    },
                    error: function(xhr) {
                        showIndicator($input, 'error', 'Error al guardar');
                        // Revertir valor
                        $input.val(originalValue);
                    },
                    complete: function() {
                        $input.prop('disabled', false);
                        $btn.prop('disabled', false);
                    }
                });
            });
            
            // Función para mostrar indicador
            function showIndicator($input, type, message) {
                var $container = $input.closest('.phonemask-editable-container');
                var $indicatorContainer = $container.find('.save-indicator-container');
                var iconClass = '';
                var textClass = '';
                
                switch(type) {
                    case 'loading':
                        iconClass = 'mdi mdi-loading mdi-spin';
                        textClass = 'text-info';
                        break;
                    case 'success':
                        iconClass = 'mdi mdi-check';
                        textClass = 'text-success';
                        break;
                    case 'error':
                        iconClass = 'mdi mdi-alert';
                        textClass = 'text-danger';
                        break;
                    case 'info':
                        iconClass = 'mdi mdi-information';
                        textClass = 'text-muted';
                        break;
                }
                
                $indicatorContainer.html('<small class="' + textClass + '" style="font-size: 0.7rem;"><i class="' + iconClass + '"></i> ' + message + '</small>');
                
                if (type === 'success' || type === 'info') {
                    setTimeout(function() {
                        $indicatorContainer.fadeOut(function() {
                            $indicatorContainer.html('');
                            $indicatorContainer.show();
                        });
                    }, 2000);
                }
            }
            
            // Función para actualizar el ejemplo
            function updatePhoneMaskExample($input, mask) {
                if (!mask) return;
                
                var $container = $input.closest('.phonemask-editable-container');
                var $example = $container.find('small.text-muted').first();
                
                // Generar ejemplo
                var exampleDigits = '1234567890';
                var exampleNumber = mask;
                var digitIndex = 0;
                for (var i = 0; i < exampleNumber.length && digitIndex < exampleDigits.length; i++) {
                    if (exampleNumber[i] === '#') {
                        exampleNumber = exampleNumber.substring(0, i) + exampleDigits[digitIndex] + exampleNumber.substring(i + 1);
                        digitIndex++;
                    }
                }
                exampleNumber = exampleNumber.replace(/#/g, '0');
                
                // Obtener phonecode del atributo data
                var phonecode = $input.data('phonecode') || '';
                
                var fullExample = phonecode ? '+' + phonecode + ' ' + exampleNumber : exampleNumber;
                
                if ($example.length) {
                    $example.html('Ejemplo: <strong>' + fullExample + '</strong>');
                }
            }
        });
    }
})();
</script>

