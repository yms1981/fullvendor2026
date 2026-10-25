<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Update extends Company_Controller
{
    use IsAction;
    use IsCRUD;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('rols', 'company/rols/', 'roles');
    }

    /**
     * Override injectParams to prevent adding company_id since rols table doesn't have this field
     */
    protected function injectParams(&$options, $paramsWhere = 'query'): void
    {
        // No inject company_id for rols
    }

    function run($rolId = null)
    {
        $postData = $this->input->post();

        // Filtrar solo los campos permitidos: type, desc_es, desc_en
        // (rol_id no se actualiza, se usa solo para identificar el registro)
        $filteredData = [
            'type' => $postData['type'] ?? '',
            'desc_es' => $postData['desc_es'] ?? '',
            'desc_en' => $postData['desc_en'] ?? ''
        ];

        $response = $this->put("{$this->entityPath}/{$rolId}", $filteredData);

        if ($this->hasError($response)) {
            $this->jsonError($response, 'Error al actualizar el rol.');
        }

        $this->jsonSuccess('Rol actualizado correctamente.');
    }
}

