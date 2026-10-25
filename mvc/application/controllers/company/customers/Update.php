<?php

class Update extends Company_Controller
{
    use IsAction;
    use IsCRUD;
    protected $itemId;

    function __construct()
    {
        parent::__construct();
        $this->initCrud('customer', 'company/customers', 'customers');
        $this->langArr = $this->getModuleLang('cmp-cliente');
    }

    function run($customerId=null)
    {
        $postData = $this->input->post();

        // Normalizar sellers a array de IDs (multiselect); puede quedar vacío
        if (isset($postData['sellers'])) {
            $postData['sellers'] = is_array($postData['sellers'])
                ? array_values(array_filter(array_map('intval', $postData['sellers'])))
                : (strlen((string)$postData['sellers']) ? [(int)$postData['sellers']] : []);
        } else {
            $postData['sellers'] = [];
        }

        $response = $this->put("{$this->entityPath}/{$customerId}", $postData);

        if ($this->hasError($response)){
            $this->jsonError($response, (string) ($this->langArr['error_occurred'] ?? ''));
            return;
        }

        $this->jsonSuccess((string) ($this->langArr['cliente_actualizado'] ?? ''));
    }
}
