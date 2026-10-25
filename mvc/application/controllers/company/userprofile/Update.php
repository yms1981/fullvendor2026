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
        $this->initCrud('sellerprofiles', 'company/userprofile/', 'userprofiles');
    }

    function run($profileId = null)
    {
        $postData = $this->input->post();

        $response = $this->put("{$this->entityPath}/{$profileId}", $postData);

        if ($this->hasError($response)) {
            $this->jsonError($response, 'Error al actualizar el perfil de usuario.');
        }

        $this->jsonSuccess('Perfil de usuario actualizado correctamente.');
    }
}

