<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Actualiza categoría + grupos/subgrupos de un producto desde el listado (sin entrar a edit).
 */
class InlineTaxonomy extends Company_Controller
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
     * POST company/products/inlineTaxonomy/{proId}
     */
    public function run($producto_id = null)
    {
        $producto_id = (int) $producto_id;
        if ($producto_id < 1) {
            return $this->jsonTaxonomyError($this->langArr['error_general'] ?? 'Invalid product', 404);
        }

        $product = $this->getObjectFromApi('product/' . $producto_id);
        if (!isset($product) || (is_array($product) && isset($product['error']))) {
            return $this->jsonTaxonomyError($this->langArr['error_general'] ?? 'Product not found', 404);
        }

        $categoryIds = $this->normalizeIdList($this->input->post('category_id'));
        if ($categoryIds === []) {
            return $this->jsonTaxonomyError(
                $this->langArr['must_select_category'] ?? ($this->langArr['select_category'] ?? 'Select a category'),
                422
            );
        }

        $groupIds = $this->normalizeIdList($this->input->post('category_group_ids'));
        $subgroupIds = $this->normalizeIdList($this->input->post('category_subgroup_ids'));

        $payload = [
            'company_id' => (int) $this->getCompanyId(),
            'category_id' => $categoryIds,
            'category_group_ids' => $groupIds,
            'category_subgroup_ids' => $subgroupIds,
        ];

        $updated = $this->updateItem($producto_id, $payload);
        if ($this->hasError($updated)) {
            $message = method_exists($this, 'extractApiErrorMessage')
                ? (string) $this->extractApiErrorMessage($updated)
                : '';
            if ($message === '') {
                $message = (string) ($this->langArr['error_general'] ?? 'Error');
            }

            return $this->jsonTaxonomyError($message, 502);
        }

        return $this->Helper->response_json([
            'success' => true,
            'message' => $this->langArr['inline_taxonomy_saved']
                ?? ($this->langArr['successfully_assigned_category'] ?? 'Saved'),
            'pro_id' => $producto_id,
            'category_ids' => $categoryIds,
            'category_group_ids' => $groupIds,
            'category_subgroup_ids' => $subgroupIds,
        ]);
    }

    /**
     * @param mixed $raw
     * @return list<int>
     */
    private function normalizeIdList($raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }
        if (!is_array($raw)) {
            $raw = preg_split('/\s*,\s*/', (string) $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        }
        $ids = [];
        foreach ($raw as $item) {
            $id = (int) $item;
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    private function jsonTaxonomyError(string $message, int $status = 400)
    {
        http_response_code($status);

        return $this->Helper->response_json([
            'success' => false,
            'message' => $message,
            'msg' => $message,
        ]);
    }
}
