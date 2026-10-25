<?php

class Save extends Company_Controller
{
    use IsCRUD;
    use IsAction;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('customer', 'company/customer/', 'customers');
        $this->langArr = $this->getModuleLang('cmp-cliente');
    }

    public function run(): void
    {
        $postData = $this->input->post();

        // Normalizar sellers a array de IDs (multiselect); la API espera 'sellers' => array de ids (puede quedar vacío)
        if (isset($postData['sellers'])) {
            $sellers = is_array($postData['sellers'])
                ? $postData['sellers']
                : [$postData['sellers']];
            $postData['sellers'] = array_values(array_filter(array_map('intval', $sellers)));
        } else {
            $postData['sellers'] = [];
        }

        $response = $this->post("{$this->entityPath}", $postData);

        if ($this->hasError($response)) {
            $this->jsonError($response, $this->langArr['error_occurred'] ?? 'An error occurred');
        }

        $this->jsonSuccess($this->langArr['success_created'] ?? 'Successfully created.');
    }

}
