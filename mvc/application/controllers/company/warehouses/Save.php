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
        $this->initCrud('warehouse', 'company/warehouse/', 'warehouses');
        $this->langArr = $this->getModuleLang('cmp-warehouse');
    }

    public function run()
    {
        $postData = $this->input->post();
        $postData['company_id'] = $this->getCompanyId();

        $response = $this->post("{$this->entityPath}", $postData);

        if ($this->hasError($response)) {
            $this->jsonError($response, $this->langArr['warehouse_error_save'] ?? '');
        }

        $warehouseId = 0;
        if (is_object($response)) {
            if (isset($response->data->id)) {
                $warehouseId = (int) $response->data->id;
            } elseif (isset($response->id)) {
                $warehouseId = (int) $response->id;
            }
        } elseif (is_array($response)) {
            if (isset($response['data']['id'])) {
                $warehouseId = (int) $response['data']['id'];
            } elseif (isset($response['id'])) {
                $warehouseId = (int) $response['id'];
            }
        }

        $payload = [];
        if ($warehouseId > 0) {
            $pendingUserIds = $postData['pending_user_ids'] ?? [];
            if (! is_array($pendingUserIds)) {
                $pendingUserIds = [$pendingUserIds];
            }
            $pendingUserIds = array_values(array_unique(array_filter(array_map('intval', $pendingUserIds))));

            foreach ($pendingUserIds as $userId) {
                if ($userId < 1) {
                    continue;
                }
                $assignResponse = $this->post("warehouse/assignUser/{$warehouseId}", [
                    'company_id' => (int) $postData['company_id'],
                    'user_id' => $userId,
                ]);
                if ($this->hasError($assignResponse)) {
                    $this->jsonError(
                        $assignResponse,
                        $this->langArr['user_assign_error'] ?? '',
                        422
                    );
                }
            }
        }

        $payload['urlredirect'] = company_url('warehouses');

        $this->jsonSuccess($this->langArr['warehouse_saved'] ?? '', $payload);
    }
}
