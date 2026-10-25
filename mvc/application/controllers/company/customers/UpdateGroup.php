<?php

class UpdateGroup extends Company_Controller
{
    use IsAction;
    use IsCRUD;
    protected $itemId;

    function __construct()
    {
        parent::__construct();
        $this->initCrud('customer', 'company/customers', 'customers');
        $this->langArr = [];
    }

    function run($customerId=null)
    {
        $postData = $this->input->post();
        $groupId = $postData['group_id'] ?? null;
        
        if ($customerId === null) {
            $this->jsonError(['error' => 'Customer ID is required'], 'Customer ID is required.');
            return;
        }
        
        // Preparar datos para actualizar solo el grupo
        $updateData = [
            'group_id' => $groupId === '' ? null : (int)$groupId
        ];
        
        $response = $this->put("{$this->entityPath}/{$customerId}", $updateData);

        if ($this->hasError($response)){
            $this->jsonError($response, 'Error updating customer group.');
            return;
        }

        $this->jsonSuccess('Customer group updated successfully.');
    }
}

