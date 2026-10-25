<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Save extends Company_Controller
{
    use IsCRUD;
    use IsAction;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('sellerprofiles', 'company/userprofile/', 'userprofiles');
    }

    public function run(): void
    {
        $postData = $this->input->post();

        $response = $this->post("{$this->entityPath}", $postData);

        if ($this->hasError($response)) {
            $this->jsonError($response, 'Error al crear el perfil de usuario.');
        }

        $this->jsonSuccess('Perfil de usuario guardado correctamente.');
    }
}

