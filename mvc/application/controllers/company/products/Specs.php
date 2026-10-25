<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Especificaciones de producto desde el listado (modal).
 */
class Specs extends Company_Controller
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
     * GET company/products/specs/{proId}
     */
    public function run($producto_id = null)
    {
        $producto_id = (int) $producto_id;
        if ($producto_id < 1) {
            return $this->jsonSpecsError($this->langArr['specs_error_invalid'] ?? '', 404);
        }

        $companyId = (int) $this->getCompanyId();
        $resp = $this->getDataFromApi('product/' . $producto_id . '/specifications', [
            'company_id' => $companyId,
        ]);

        if ($this->hasError($resp) || (is_array($resp) && isset($resp['error']))) {
            // Fallback: producto completo (si el endpoint specs aún no está desplegado).
            $product = $this->getObjectFromApi('product/' . $producto_id, [
                'company_id' => $companyId,
                'props' => ['pro_id', 'name', 'sku', 'specifications'],
            ]);
            if ($this->hasError($product) || !is_object($product)) {
                return $this->jsonSpecsError($this->langArr['specs_error_load'] ?? '', 502);
            }
            $specs = $product->specifications ?? [];
            if (is_string($specs)) {
                $decoded = json_decode($specs, true);
                $specs = is_array($decoded) ? $decoded : [];
            }
            if (!is_array($specs)) {
                $specs = [];
            }

            return $this->Helper->response_json([
                'success' => true,
                'pro_id' => (int) ($product->pro_id ?? $producto_id),
                'name' => (string) ($product->name ?? ''),
                'sku' => (string) ($product->sku ?? ''),
                'specifications' => $specs,
            ]);
        }

        $data = is_object($resp) ? (array) $resp : (is_array($resp) ? $resp : []);
        $specs = $data['specifications'] ?? [];
        if (is_string($specs)) {
            $decoded = json_decode($specs, true);
            $specs = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($specs)) {
            $specs = [];
        }

        return $this->Helper->response_json([
            'success' => true,
            'pro_id' => (int) ($data['pro_id'] ?? $producto_id),
            'name' => (string) ($data['name'] ?? ''),
            'sku' => (string) ($data['sku'] ?? ''),
            'specifications' => $specs,
        ]);
    }

    /**
     * POST company/products/specsSave/{proId}
     */
    public function save($producto_id = null)
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        $producto_id = (int) $producto_id;
        if ($producto_id < 1) {
            return $this->jsonSpecsError($this->langArr['specs_error_invalid'] ?? '', 404);
        }

        $raw = $this->input->post('specifications');
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($raw)) {
            $raw = [];
        }

        $specs = [];
        foreach ($raw as $row) {
            if (!is_array($row)) {
                continue;
            }
            $name = trim((string) ($row['name'] ?? ''));
            $value = trim((string) ($row['value'] ?? ''));
            if ($name === '' && $value === '') {
                continue;
            }
            $specs[] = ['name' => $name, 'value' => $value];
        }

        $updated = $this->put('product/' . $producto_id . '/specifications', [
            'company_id' => (int) $this->getCompanyId(),
            'specifications' => $specs,
        ]);

        if ($this->hasError($updated)) {
            // Fallback: update genérico del producto.
            $updated = $this->put('product/' . $producto_id, [
                'company_id' => (int) $this->getCompanyId(),
                'specifications' => $specs,
            ]);
        }

        if ($this->hasError($updated)) {
            $message = method_exists($this, 'extractApiErrorMessage')
                ? (string) $this->extractApiErrorMessage($updated)
                : '';
            if ($message === '') {
                $message = (string) ($this->langArr['specs_error_save'] ?? ($this->langArr['error_general'] ?? ''));
            }

            return $this->jsonSpecsError($message, 502);
        }

        return $this->Helper->response_json([
            'success' => true,
            'message' => $this->langArr['specs_saved'] ?? '',
            'pro_id' => $producto_id,
            'specifications' => $specs,
        ]);
    }

    private function jsonSpecsError(string $message, int $code = 400)
    {
        http_response_code($code);

        return $this->Helper->response_json([
            'success' => false,
            'message' => $message !== '' ? $message : 'Error',
        ]);
    }
}
