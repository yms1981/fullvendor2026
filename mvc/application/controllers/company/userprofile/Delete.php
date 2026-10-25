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
        $this->initCrud('sellerprofiles', 'company/userprofile/', 'userprofiles');
    }

    public function run($profileId)
    {
        $response = $this->destroy($profileId);

        if ($this->hasError($response)) {
            $this->jsonError($response, 'Error al eliminar el perfil de usuario.');
        }

        $this->jsonSuccess('Perfil de usuario eliminado correctamente.');
    }
}

