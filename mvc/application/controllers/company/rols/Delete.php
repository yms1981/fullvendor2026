<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Delete extends Company_Controller
{
    use IsCRUD;
    use IsAction;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('rols', 'company/rols/', 'roles');
    }

    public function run($rolId)
    {
        $response = $this->destroy($rolId);

        if ($this->hasError($response)) {
            $this->jsonError($response, 'Error al eliminar el rol.');
        }

        $this->jsonSuccess('Rol eliminado correctamente.');
    }
}

