<?php
require_once APPPATH . 'controllers/company/users/Form.php';
class Add extends Form
{
    use IsAction;

    public function run(): void
    {
        $type = $this->input->get('type');
        if ($type === 'customer') {
            redirect(company_url('users?type=customer'), 'refresh');
            return;
        }

        if (!$type || !in_array($type, ['manager', 'seller', 'warehouse'], true)) {
            $type = 'manager';
        }

        $data = [];
        $data['addFlowType'] = $type;
        $data['userTypes'] = ['manager', 'seller', 'warehouse'];

        $data['sellerProfiles'] = $this->getObjectFromApi('sellerprofiles', [
            'status' => 1,
            'props' => ['profile_id', 'profile_name', 'status', 'default'],
            'all' => true
            ]);

        $data['warehouses'] = [];
        $wh = $this->getDataFromApi('warehouse', [
            'all' => true,
            'orderProperty' => 'name',
            'orderDirection' => 'asc',
            'props' => ['id', 'name', 'company_id'],
        ], true);
        if (!$this->hasError($wh)) {
            foreach ($this->extractApiListRows($wh) as $row) {
                $item = is_array($row) ? $row : (array) (is_object($row) ? $row : []);
                $wid = (int) ($item['id'] ?? 0);
                if ($wid < 1) {
                    continue;
                }
                $data['warehouses'][] = (object) [
                    'id' => $wid,
                    'name' => trim((string) ($item['name'] ?? '')),
                ];
            }
        }

        $planLimits = $this->fetchCompanyPlanLimits();
        $data['planLimitBlock'] = $this->buildPlanLimitBlock($planLimits, 'sellers', 'cmp-user');

        $this->addJsFile(base_url('companyasset/js/sweetalert2.js'));
        $this->addJsFile(base_url('companyasset/company/users/send_form.js'));
        $this->addJsFile(base_url('companyasset/company/users/searchRelated.js'));
        $this->showContent('company/users/companyuser_add' , $data);
    }
}
