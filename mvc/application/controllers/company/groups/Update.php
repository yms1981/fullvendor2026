<?php

class Update extends Company_Controller
{
    use IsAction;
    use IsCRUD;
    use SupportsCustomerGroupValidationMessages;
    protected $itemId;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('customergroup', 'company/group/', 'groups');
        $this->langArr = $this->getModuleLang('cmp-group');
    }

    function run($groupId=null)
    {
        $postData = $this->input->post();
        $postData['company_id'] = $this->getCompanyId();
        
        // Convertir checkBoxDefault a default (0 o 1) — aceptar "on", "1" o valor truthy
        $isDefault = isset($postData['checkBoxDefault'])
            && ($postData['checkBoxDefault'] === 'on' || $postData['checkBoxDefault'] === '1' || $postData['checkBoxDefault'] === true);
        $postData['default'] = $isDefault ? 1 : 0;
        unset($postData['checkBoxDefault']);

        $response = $this->put("{$this->entityPath}/{$groupId}", $postData);

        if ($this->hasError($response)) {
            $name = trim((string) ($postData['name'] ?? ''));
            $response = $this->localizeCustomerGroupApiErrorResponse(
                is_array($response) ? $response : [],
                $name
            );
            $defaultMsg = $this->formatCustomerGroupNameExistsMessage($name);
            $this->jsonError($response, $defaultMsg !== '' ? $defaultMsg : ($this->langArr['group_existente'] ?? ''), 422);
        }

        $this->jsonSuccess($this->langArr['group_updated'] ?? '');
    }
}
