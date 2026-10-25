<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/company/groups/Home.php';

/**
 * Controlador legado de grupos de clientes (rutas singulares /company/group/*).
 *
 * Migrado para consumir exclusivamente la API (entidad customergroup), sin acceso
 * directo a base de datos. El módulo plural /company/groups/* es la implementación
 * de referencia; aquí se replican sus flujos para mantener compatibilidad de URLs.
 */
class Group extends Home
{
    public ?int $langId;

    public function __construct()
    {
        parent::__construct();
        $this->checkLoggedInOrLoginWithRedirect();
        $this->langId = $this->getUserData('language_id');
        $this->langArr = $this->getModuleLang('cmp-group');
    }

    public function lists($page = 1)
    {
        // Listado vía sistema CRUD (API) del módulo padre.
        $this->index();
    }

    protected function getDataForView($view = null, $dataForView = [])
    {
        $dataForView = parent::getDataForView($view, $dataForView);
        $pageTexts = $dataForView['pageTexts'] ?? [];
        $dataForView['pageTexts'] = $pageTexts + (is_array($this->langArr) ? $this->langArr : []);

        return $dataForView;
    }

    public function add()
    {
        $response = $this->getObjectFromApi("company/{$this->getCompanyId()}", ['props' => ['default_customer_group_id']]);
        $data['defaultGroup'] = $this->hasError($response) ? null : ($response->default_customer_group_id ?? null);

        $this->addJsFile('/companyasset/company/send_add_form_with_default.js');
        $this->addJsFile('/companyasset/company/groups/price-adjustment.js');

        $this->showContent('company/groups/group_add', $data);
    }

    public function edit($groupId = false)
    {
        if (!$groupId) {
            show_404();
            return;
        }

        $group = $this->getObjectFromApi("{$this->entityPath}/{$groupId}");
        if ($this->hasError($group)) {
            show_404();
            return;
        }

        $data['group'] = $group;
        $this->addJsFile('/companyasset/company/send_edit_form_with_default.js');
        $this->addJsFile('/companyasset/company/groups/price-adjustment.js');

        $this->showContent('company/groups/group_edit', $data);
    }

    /**
     * Alta vía formulario clásico (redirección). Crea el grupo a través de la API.
     */
    public function create()
    {
        $postData = $this->input->post();
        $postData['company_id'] = $this->getCompanyId();
        $postData['default'] = $this->resolveDefaultFlag($postData);
        unset($postData['checkBoxDefault']);

        $response = $this->post($this->entityPath, $postData);

        if ($this->hasError($response)) {
            $unpacked = $this->unpackApiErrorResponse(
                is_array($response) ? $response : [],
                $this->langArr['group_existente'] ?? 'Error creating customer group.'
            );
            $this->flash('name', (string) ($postData['name'] ?? ''));
            $this->flashMessage('["' . addslashes($unpacked['message']) . '", "error", "#DD6B55"]');
            redirect(company_url('group/add'), 'refresh');
            return;
        }

        $this->flashMessage('["' . addslashes($this->langArr['group_saved'] ?? 'Customer group saved successfully.') . '", "success", "#A5DC86"]');
        redirect(company_url('groups'), 'refresh');
    }

    /**
     * Alta vía AJAX (mismo contrato que /company/groups/save).
     */
    public function save()
    {
        $postData = $this->input->post();
        $postData['company_id'] = $this->getCompanyId();
        $postData['default'] = $this->resolveDefaultFlag($postData);
        unset($postData['checkBoxDefault']);

        $response = $this->post($this->entityPath, $postData);

        if ($this->hasError($response)) {
            $this->jsonError($response, $this->langArr['group_existente'] ?? 'Error creating customer group.');
        }

        $this->jsonSuccess($this->langArr['group_saved'] ?? 'Customer group saved successfully.');
    }

    /**
     * Actualización vía AJAX (mismo contrato que /company/groups/update).
     */
    public function actualizar($id)
    {
        $postData = $this->input->post();
        $postData['company_id'] = $this->getCompanyId();
        $postData['default'] = $this->resolveDefaultFlag($postData);
        unset($postData['checkBoxDefault']);

        $response = $this->put("{$this->entityPath}/{$id}", $postData);

        if ($this->hasError($response)) {
            $this->jsonError($response, $this->langArr['group_existente'] ?? 'Error updating customer group.');
        }

        $this->jsonSuccess($this->langArr['group_updated'] ?? 'Customer group updated successfully.');
    }

    public function delete($groupId = false)
    {
        if (!$groupId) {
            show_404();
            return;
        }

        $response = $this->destroy($groupId);

        if ($this->hasError($response)) {
            $unpacked = $this->unpackApiErrorResponse(
                is_array($response) ? $response : [],
                $this->langArr['error_eliminar'] ?? 'Error deleting group.'
            );
            $this->flashMessage('["' . addslashes($unpacked['message']) . '", "error", "#DD6B55"]');
            redirect(company_url('groups'), 'refresh');
            return;
        }

        $this->flashMessage('["' . addslashes($this->langArr['deleted_success'] ?? 'Deleted successfully.') . '", "success", "#A5DC86"]');
        redirect(company_url('groups'), 'refresh');
    }

    public function habilitar($group_id)
    {
        $this->enable($group_id);
    }

    public function deshabilitar($group_id)
    {
        $this->disable($group_id);
    }

    /**
     * Normaliza el checkbox "por defecto" del formulario a un entero 0/1.
     *
     * @param array<string, mixed> $postData
     */
    private function resolveDefaultFlag(array $postData): int
    {
        if (isset($postData['default'])) {
            return ((string) $postData['default'] === '1' || $postData['default'] === true) ? 1 : 0;
        }
        $checkbox = $postData['checkBoxDefault'] ?? null;

        return ($checkbox === 'on' || $checkbox === '1' || $checkbox === true) ? 1 : 0;
    }
}
