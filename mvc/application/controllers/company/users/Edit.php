<?php
require_once APPPATH . 'controllers/company/users/Form.php';
class Edit extends Form
{
    use IsAction;

    public function run($userId=null)
    {
        $user = $this->getObjectFromApi("{$this->entityPath}/{$userId}", [
            "withTrashed" => true,
            "relations" => ["companyUsers.profileable"]
        ]);

        if ($this->hasError($user)) {
            if ($this->input->is_ajax_request()) {
                $this->output->set_status_header(404);
                echo '<div class="alert alert-danger">'
                    . htmlspecialchars((string) ($this->langArr['user_edit_load_error'] ?? ''), ENT_QUOTES, 'UTF-8')
                    . '</div>';
                return;
            }
            show_404();
            return;
        }

        $companyId = (int) $this->getCompanyId();
        $data = [];
        $data['editingUser'] = $user;
        $data['company'] = $companyId > 0 ? $this->getObjectFromApi('company/' . $companyId) : null;
        if ($this->hasError($data['company'])) {
            $data['company'] = null;
        }
        $data['userTypes'] = ['manager', 'seller', 'warehouse'];
        $data['isSellerUser'] = false;
        $data['sellerRecord'] = null;
        $data['editRole'] = 'manager';
        $data['editProfileId'] = null;
        $data['editRelatedId'] = null;
        $data['warehouses'] = [];
        $data['sellerProfiles'] = [];
        $sp = $this->getDataFromApi('sellerprofiles', [
            'status' => 1,
            'props' => ['profile_id', 'profile_name', 'status', 'default'],
            'all' => true,
        ], true);
        if (! $this->hasError($sp)) {
            foreach ($this->extractApiListRows($sp) as $row) {
                $item = is_array($row) ? (object) $row : (is_object($row) ? $row : null);
                if ($item === null || (int) ($item->profile_id ?? 0) < 1) {
                    continue;
                }
                $data['sellerProfiles'][] = $item;
            }
        }

        $rolesInCompany = [];
        $profileByType = [];
        foreach (($user->company_users ?? $user->companyUsers ?? []) as $companyUser) {
            $cuCompanyId = (int) ($companyUser->company_id ?? 0);
            if ($companyId > 0 && $cuCompanyId > 0 && $cuCompanyId !== $companyId) {
                continue;
            }
            $type = strtolower(trim((string) ($companyUser->type ?? '')));
            if ($type === '' || in_array($type, $rolesInCompany, true)) {
                continue;
            }
            $rolesInCompany[] = $type;
            $pid = (int) ($companyUser->profileable_id ?? 0);
            if ($pid > 0) {
                $profileByType[$type] = $pid;
            }
        }

        $priority = ['seller', 'warehouse', 'manager', 'customer'];
        foreach ($priority as $candidate) {
            if (in_array($candidate, $rolesInCompany, true)) {
                $data['editRole'] = $candidate;
                break;
            }
        }
        if ($rolesInCompany !== [] && ! in_array($data['editRole'], $rolesInCompany, true)) {
            $data['editRole'] = $rolesInCompany[0];
        }

        $data['isSellerUser'] = ($data['editRole'] === 'seller') || in_array('seller', $rolesInCompany, true);
        $data['editProfileId'] = $profileByType[$data['editRole']] ?? ($profileByType['seller'] ?? null);

        if ($data['isSellerUser'] && $companyId > 0) {
            $seller = $user->seller ?? null;
            if (! is_object($seller) || empty($seller->id)) {
                $seller = $this->getObjectFromApi("user/{$userId}/company/{$companyId}/seller");
            }
            if (! $this->hasError($seller) && is_object($seller) && ! empty($seller->id)) {
                $data['sellerRecord'] = $seller;
            }
        }

        if ($data['editRole'] === 'warehouse' && $companyId > 0) {
            $wh = $this->getDataFromApi('warehouse', [
                'all' => true,
                'orderProperty' => 'name',
                'orderDirection' => 'asc',
                'props' => ['id', 'name', 'company_id'],
            ], true);
            if (! $this->hasError($wh)) {
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

            $warehouse = $user->warehouse ?? null;
            if (! is_object($warehouse) || empty($warehouse->id)) {
                // show() setea warehouse con company_id; si no vino, intentar vía lista de roles
                $warehouse = null;
            }
            if (is_object($warehouse) && ! empty($warehouse->id)) {
                $data['editRelatedId'] = (int) $warehouse->id;
            } elseif (is_object($warehouse) && ! empty($warehouse->warehouse_id)) {
                $data['editRelatedId'] = (int) $warehouse->warehouse_id;
            }
        }

        $this->injectCompanyUserPhoneCountryMeta($data);

        // AJAX desde el listado: devolver solo el formulario embebible en modal.
        if ($this->input->is_ajax_request()) {
            if (function_exists('session_write_close') && session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
            $data['embeddedInModal'] = true;
            $data['pageTexts'] = array_merge(
                is_array($this->langArr) ? $this->langArr : [],
                is_array($data['pageTexts'] ?? null) ? $data['pageTexts'] : []
            );
            $this->fillEmptyUserFormLabels($data['pageTexts']);
            $html = $this->load->view('company/users/companyuser_edit', $data, true);
            if (! is_string($html) || trim($html) === '') {
                $this->output->set_status_header(500);
                echo '<div class="alert alert-danger">'
                    . htmlspecialchars((string) ($data['pageTexts']['user_edit_load_error'] ?? ''), ENT_QUOTES, 'UTF-8')
                    . '</div>';
                return;
            }
            echo $html;
            return;
        }

        $this->addJsFile(base_url('companyasset/js/sweetalert2.js'));
        $this->addJsFile(base_url('companyasset/company/users/send_form.js'));

        $this->showContent('company/users/companyuser_edit', $data);
    }

}
