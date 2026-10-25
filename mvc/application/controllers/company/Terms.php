<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/company/terms/Home.php';

class Terms extends Home
{
    public function __construct()
    {
        parent::__construct();
    }

    public function lists()
    {
        // Usar el método index() del padre que muestra la lista con el sistema CRUD
        $this->index();
    }

    public function addTerm()
    {
        // Obtener el término por defecto si existe
        $response = $this->getObjectFromApi("{$this->entityPath}/company/{$this->getCompanyId()}");
        $data['defaultTerm'] = $this->hasError($response) ? null : $response;
        $data['pageTexts'] = $this->langArr;

        // Cargar la vista del formulario de agregar sin el wrapper completo
        $formContent = $this->load->view('company/terms/modals/add_term_form', $data, true);
        
        echo $formContent;
    }

    public function save()
    {
        $postData = $this->input->post();
        $postData['company_id'] = $this->getCompanyId();
        
        // Convertir checkBoxDefault a default (0 o 1, o null si no está marcado)
        if (isset($postData['checkBoxDefault']) && $postData['checkBoxDefault'] == 'on') {
            $postData['default'] = 1;
        } else {
            $postData['default'] = 0;
        }
        unset($postData['checkBoxDefault']);

        $response = $this->post("{$this->entityPath}", $postData);

        if ($this->hasError($response)){
            $this->jsonError($response, 'Error al crear el término de venta.');
        }

        $this->jsonSuccess($this->langArr['term_saved']);
    }

    public function getEditForm($termId = null)
    {
        if (!$termId) {
            show_404();
            return;
        }

        $term = $this->getObjectFromApi("{$this->entityPath}/{$termId}");

        if ($this->hasError($term)) {
            show_404();
            return;
        }

        $data['term'] = $term;
        $data['pageTexts'] = $this->langArr;

        // Cargar la vista del formulario de edición sin el wrapper completo
        $formContent = $this->load->view('company/terms/modals/edit_term_form', $data, true);
        
        echo $formContent;
    }

    public function update($termId = null)
    {
        if (!$termId) {
            $this->jsonError(null, 'ID de término no proporcionado.');
            return;
        }

        $postData = $this->input->post();
        $postData['company_id'] = $this->getCompanyId();
        
        // Convertir checkBoxDefault a default (0 o 1)
        if (isset($postData['checkBoxDefault']) && $postData['checkBoxDefault'] == 'on') {
            $postData['default'] = 1;
        } else {
            $postData['default'] = 0;
        }
        unset($postData['checkBoxDefault']);

        $response = $this->put("{$this->entityPath}/{$termId}", $postData);

        if ($this->hasError($response)){
            $this->jsonError($response, 'Error al actualizar el término de venta.');
        }

        $this->jsonSuccess($this->langArr['term_updated']);
    }

    /**
     * Eliminar un término desde la grilla (/company/terms/delete/{id})
     * Usado por el botón eliminar del CRUD genérico (components/crud/list.php + delete-alert.js).
     * Devuelve JSON con el mensaje para que el frontend muestre la alerta correspondiente.
     */
    public function delete($termId = null)
    {
        if (!$termId) {
            $this->jsonError(['message' => 'ID de término no proporcionado.'], 'ID de término no proporcionado.', 400);
            return;
        }

        // Llamar al API para eliminar, pasando la compañía como parámetro
        $response = $this->destroy($termId, ['company' => $this->getCompanyId()]);

        if ($this->hasError($response)) {
            // Extraer mensaje bruto del API y traducirlo según idioma (cmp-terms)
            $rawMessage = $this->extractDeleteErrorMessage($response);
            $message = $this->getDeleteErrorMessage($rawMessage);
            $this->jsonError(['message' => $message], $message, 400);
            return;
        }

        // Mensaje de éxito según idioma (cmp-terms)
        $successMessage = $this->langArr['deleted_success'] ?? 'Deleted Successfully';
        $this->jsonSuccess($successMessage);
    }

    /**
     * Deshabilitar un término desde la grilla (/company/terms/disable/{id}).
     * Respuesta JSON para el switch de estado en la grilla CRUD.
     */
    public function disable($termId = null)
    {
        if (!$termId) {
            show_404();
            return;
        }

        $termId = (int) $termId;
        if ($termId <= 0) {
            $message = $this->termsLang('term_not_found');
            $this->jsonError(['message' => $message], $message, 404);
            return;
        }

        $companyId = (int) $this->getCompanyId();
        $updated = $this->put("termsofsale/{$termId}/disable", [
            'company_id' => $companyId,
            'company' => $companyId,
        ]);

        if ($this->hasError($updated)) {
            $rawMessage = $this->extractDisableErrorMessage($updated);
            $message = $this->getDisableErrorMessage($rawMessage);
            $this->jsonError(['message' => $message], $message, 400);
            return;
        }

        if (is_object($updated)) {
            $updated = (array) $updated;
        }

        if (!is_array($updated) || !isset($updated['term_id'])) {
            log_message('error', 'Terms::disable - Respuesta inválida: ' . json_encode($updated));
            $message = $this->termsLang('no_puede_desactivar');
            $this->jsonError(['message' => $message], $message, 500);
            return;
        }

        if (!isset($updated['term_status'])) {
            $updated['term_status'] = 0;
        } else {
            $updated['term_status'] = ($updated['term_status'] == 1 || $updated['term_status'] === true || $updated['term_status'] === '1') ? 1 : 0;
        }

        $html = $this->load->view(
            'components/crud/enable-disable_button',
            [
                'listItem' => (object) $updated,
                'itemIdField' => 'term_id',
                'itemStatusField' => 'term_status',
                'controllerUrl' => $this->paginationUrl(),
                'pageTexts' => $this->getModuleLang('cmp-enabled'),
            ],
            true
        );

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'html' => $html,
            'message' => $this->termsLang('disabled_success'),
        ]);
    }

    /**
     * Extrae el mensaje de error de la respuesta del API para deshabilitar términos.
     */
    private function extractDisableErrorMessage($updated): string
    {
        if (!is_array($updated) || !isset($updated['message'])) {
            return '';
        }
        $msg = $updated['message'];
        if (is_array($msg) && isset($msg['message'])) {
            return is_string($msg['message']) ? $msg['message'] : '';
        }
        return is_string($msg) ? $msg : '';
    }

    /**
     * Devuelve el mensaje de error traducido según idioma (cmp-terms) para desactivar un término.
     * Si el término es el por defecto, usa la clave de idioma 'defecto_nopuede_desactivar'
     * (\"El Termino de Venta por defecto no se puede desactivar\" / \"Default Term of Sale cannot be disabled\").
     */
    private function getDisableErrorMessage(string $rawMessage): string
    {
        $raw = $rawMessage;

        if ($raw !== '' && (
            stripos($raw, 'No query results for model') !== false
            || stripos($raw, 'Término no encontrado') !== false
            || stripos($raw, 'not found') !== false
        )) {
            return $this->termsLang('term_not_found');
        }

        // Caso específico: término por defecto no puede desactivarse
        if ($raw !== '' && (
            stripos($raw, 'default') !== false && (stripos($raw, 'no se puede desactivar') !== false || stripos($raw, 'cannot be disabled') !== false)
            || stripos($raw, 'término default') !== false
        )) {
            return $this->termsLang('defecto_nopuede_desactivar');
        }

        // Cualquier otro error: devolver el mensaje del API o un genérico traducido
        return $raw !== '' ? $raw : $this->termsLang('no_puede_desactivar');
    }

    /**
     * Habilitar un término desde la grilla (/company/terms/enable/{id}).
     * Respuesta JSON para el switch de estado en la grilla CRUD.
     */
    public function enable($termId = null)
    {
        if (!$termId) {
            show_404();
            return;
        }

        $termId = (int) $termId;
        if ($termId <= 0) {
            $message = $this->termsLang('term_not_found');
            $this->jsonError(['message' => $message], $message, 404);
            return;
        }

        $companyId = (int) $this->getCompanyId();
        $updated = $this->put("termsofsale/{$termId}/enable", [
            'company_id' => $companyId,
            'company' => $companyId,
        ]);

        if ($this->hasError($updated)) {
            $errorMessage = $this->termsLang('no_puede_desactivar');
            if (is_array($updated) && isset($updated['message'])) {
                if (is_array($updated['message']) && isset($updated['message']['message'])) {
                    $errorMessage = $updated['message']['message'];
                } elseif (is_string($updated['message'])) {
                    $errorMessage = $updated['message'];
                }
            }
            if (stripos($errorMessage, 'No query results for model') !== false
                || stripos($errorMessage, 'Término no encontrado') !== false
                || stripos($errorMessage, 'not found') !== false) {
                $errorMessage = $this->termsLang('term_not_found');
            }
            $this->jsonError(['message' => $errorMessage], $errorMessage, 400);
            return;
        }

        if (is_object($updated)) {
            $updated = (array) $updated;
        }

        if (!is_array($updated) || !isset($updated['term_id'])) {
            log_message('error', 'Terms::enable - Respuesta inválida: ' . json_encode($updated));
            $message = $this->termsLang('no_puede_desactivar');
            $this->jsonError(['message' => $message], $message, 500);
            return;
        }

        if (!isset($updated['term_status'])) {
            $updated['term_status'] = 1;
        } else {
            $updated['term_status'] = ($updated['term_status'] == 1 || $updated['term_status'] === true || $updated['term_status'] === '1') ? 1 : 0;
        }

        $html = $this->load->view(
            'components/crud/enable-disable_button',
            [
                'listItem' => (object) $updated,
                'itemIdField' => 'term_id',
                'itemStatusField' => 'term_status',
                'controllerUrl' => $this->paginationUrl(),
                'pageTexts' => $this->getModuleLang('cmp-enabled'),
            ],
            true
        );

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'html' => $html,
            'message' => $this->termsLang('enabled_success'),
        ]);
    }

    /**
     * Herramientas CRUD: /company/terms/* resuelve a este controlador (no al subdirectorio).
     *
     * @return array{headers: array<int, string>, rows: array<int, array<int, string>>}
     */
    private function buildTermsExportDataset(): array
    {
        $lang = $this->getConfigCrudLang();
        // Endpoint dedicado sin paginación; no usar columna "default" (eliminada de terms_of_sales).
        $list = $this->fetchConfigCrudApiList('termsofsale/all', [
            'company' => $this->getCompanyId(),
        ]);

        $headers = [
            $lang['name'] ?? 'name',
            $lang['default'] ?? 'default',
            $lang['alta'] ?? 'created',
        ];

        $rows = [];
        foreach ($list as $item) {
            $item = (object) $item;
            $rows[] = [
                (string) ($item->name ?? ''),
                ! empty($item->isDefault) || ! empty($item->default) ? '1' : '0',
                $this->normalizeConfigCrudDate($item->created ?? $item->created_at ?? null),
            ];
        }

        return ['headers' => $headers, 'rows' => $rows];
    }

    public function export()
    {
        $dataset = $this->buildTermsExportDataset();
        $this->sendConfigCsvDownload(
            $dataset['headers'],
            $dataset['rows'],
            'terms_list_' . date('Ymd') . '.csv'
        );
    }

    public function csvImport()
    {
        $lang = $this->getConfigCrudLang();
        $lang['template_download_url'] = import_template_download_url('termscsv.xlsx');
        $this->langArr = $lang;
        $this->showConfigCsvImportView('terms', company_url('terms/lists'), 'csvImportSuccess');
    }

    public function csvImportSuccess()
    {
        $schema = ConfigCrudImportSchemas::terms();
        if (!empty($_FILES['file']['name']) && empty($this->input->post('import_token'))) {
            $this->handleConfigCrudImportPage('terms', company_url('terms/lists'), (string) $schema['template'], $schema);

            return;
        }
        $_POST['step'] = 'confirm';
        $this->executeConfigCrudImportConfirm(company_url('terms/lists'), $schema);
    }

    public function listPdf()
    {
        $lang = $this->getConfigCrudLang();
        $title = $this->resolveConfigCrudPageTitle($lang, 'pageTitle', 'titulo');
        $dataset = $this->buildTermsExportDataset();
        $this->sendConfigListPdf(
            $dataset['headers'],
            $dataset['rows'],
            $title !== '' ? $title : 'terms',
            'terms_list'
        );
    }
}

