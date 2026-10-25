<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Asigna imágenes IA a productos enabled sin foto (lote desde el listado).
 */
class AssignAiImages extends Company_Controller
{
    use IsCRUD;
    use IsAction;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('product', 'company/product/', 'products');
        $this->langArr = $this->getModuleLang('cmp-products');
        $this->load->model('Helper');
    }

    /**
     * POST company/products/assignAiImages
     */
    public function run()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        $raw = $this->input->post('product_ids');
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : explode(',', $raw);
        }
        if (!is_array($raw)) {
            $raw = [];
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', $raw))));

        if ($ids === []) {
            return $this->Helper->response_json([
                'success' => true,
                'assigned' => [],
                'skipped' => [],
                'failed' => [],
            ]);
        }

        $resp = $this->post('product/assign-ai-images', [
            'company_id' => (int) $this->getCompanyId(),
            'product_ids' => $ids,
        ]);

        if ($this->hasError($resp)) {
            $code = '';
            if (is_array($resp)) {
                $code = (string) ($resp['code'] ?? ($resp['message']['code'] ?? ''));
            } elseif (is_object($resp)) {
                $code = (string) ($resp->code ?? '');
            }
            $message = (string) ($this->langArr['ai_image_error'] ?? ($this->langArr['error_general'] ?? 'Error'));
            if ($code === 'ai_image_not_configured' || $code === 'openai_not_configured') {
                $message = (string) ($this->langArr['ai_image_not_configured'] ?? $message);
            } elseif ($code === 'openai_quota_exceeded') {
                $message = (string) ($this->langArr['ai_image_quota'] ?? $message);
            }
            http_response_code(502);

            return $this->Helper->response_json([
                'success' => false,
                'code' => $code,
                'message' => $message,
            ]);
        }

        $data = is_object($resp) ? (array) $resp : (is_array($resp) ? $resp : []);

        return $this->Helper->response_json([
            'success' => true,
            'assigned' => $data['assigned'] ?? [],
            'skipped' => $data['skipped'] ?? [],
            'failed' => $data['failed'] ?? [],
            'message' => $this->langArr['ai_image_assigned'] ?? '',
        ]);
    }
}
