<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Búsqueda de productos para el modal "Nuevo Producto" de órdenes.
 *
 * Usa GET api/v2/product con filtros (search + company) para no cargar todo el catálogo.
 * Respuesta: array JSON de productos mapeados para el dropdown.
 */
class SearchProduct extends Company_Controller
{
    private const PER_PAGE = 40;

    public function index()
    {
        $rawTerms = $this->input->get('terms');
        if (is_array($rawTerms)) {
            $search = trim(implode(' ', array_filter(array_map('trim', $rawTerms))));
        } else {
            $search = is_string($rawTerms) ? trim($rawTerms) : '';
        }

        $companyId = (int) $this->getCompanyId();
        if ($companyId < 1) {
            $this->respondJson([]);

            return;
        }

        $params = [
            'company' => $companyId,
            'company_id' => $companyId,
            'prdstatus' => 1,
            'page' => 1,
            'perPage' => self::PER_PAGE,
            'per_page' => self::PER_PAGE,
            'orderProperty' => 'name',
            'orderDirection' => 'asc',
            'relations' => ['default_image', 'productRating'],
            'props' => 'pro_id,product_id,sku,name,status',
        ];

        if ($search !== '') {
            $params['search'] = $search;
        }

        $response = $this->getDataFromApi('product', $params, true);
        if ($this->hasError($response)) {
            log_message('error', 'SearchProduct API error: ' . json_encode($response));
            $this->respondJson([]);

            return;
        }

        $rows = $this->extractProductList($response);
        $out = [];
        foreach ($rows as $p) {
            if (! is_array($p) && ! is_object($p)) {
                continue;
            }
            $mapped = $this->mapProductForOrderSearch($p);
            $proId = (int) ($mapped['pro_id'] ?? $mapped['product_id'] ?? 0);
            if ($proId > 0) {
                $out[] = $mapped;
            }
        }

        $this->respondJson($out);
    }

    /**
     * @param list<mixed> $payload
     */
    private function respondJson(array $payload): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $this->output
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return list<array<string, mixed>|object>
     */
    private function extractProductList($response): array
    {
        if (is_array($response)) {
            if (isset($response['data']) && is_array($response['data'])) {
                return array_values($response['data']);
            }

            if ($response !== [] && array_keys($response) === range(0, count($response) - 1)) {
                return array_values($response);
            }

            return [];
        }

        if (is_object($response) && isset($response->data)) {
            $data = $response->data;

            return is_array($data) ? array_values($data) : [];
        }

        return [];
    }
}
