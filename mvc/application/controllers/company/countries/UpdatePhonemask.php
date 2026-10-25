<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class UpdatePhonemask extends Company_Controller
{
    use IsAction;

    public function run($countryId = null)
    {
        if (!$countryId) {
            $this->jsonError(null, 'ID de país no proporcionado');
            return;
        }

        $phonemask = $this->input->post('phonemask');
        
        if ($phonemask === null) {
            $this->jsonError(null, 'Valor de phonemask no proporcionado');
            return;
        }

        // Actualizar solo el campo phonemask
        $response = $this->put("country/{$countryId}", [
            'phonemask' => $phonemask
        ]);

        if ($this->hasError($response)) {
            $this->jsonError($response, 'Error al actualizar la máscara de teléfono');
            return;
        }

        $this->jsonSuccess('Máscara de teléfono actualizada correctamente');
    }
}

