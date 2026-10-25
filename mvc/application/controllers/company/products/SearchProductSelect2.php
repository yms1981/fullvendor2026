<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Búsqueda paginada de productos para Select2 (inventario / catálogos).
 *
 * Usa GET api/v2/product con filtros (search + company) para no cargar todo el catálogo.
 * Respuesta: { items, total_count, incomplete_results }.
 */
class SearchProductSelect2 extends Company_Controller
{
    private const PER_PAGE = 15;

    public function index()
    {
        $page = max(1, (int) $this->input->get('page'));
        $search = trim((string) ($this->input->get('search') ?? $this->input->get('q') ?? ''));
        $companyId = (int) $this->getCompanyId();
        $langId = (int) $this->getUserData('language_id');
        $langOrder = $this->langmodel->getMdl('cmp-orders', $langId);
        if (! is_array($langOrder)) {
            $langOrder = [];
        }

        if ($companyId < 1) {
            $this->respondSelect2([], 0);

            return;
        }

        $params = [
            'company' => $companyId,
            'company_id' => $companyId,
            'prdstatus' => 1,
            'page' => $page,
            'perPage' => self::PER_PAGE,
            'per_page' => self::PER_PAGE,
            'orderProperty' => 'name',
            'orderDirection' => 'asc',
            'relations' => ['productRating'],
            'props' => 'pro_id,product_id,sku,name,status',
        ];

        if ($search !== '') {
            $params['search'] = $search;
        }

        $response = $this->getDataFromApi('product', $params, true);
        if ($this->hasError($response)) {
            log_message('error', 'SearchProductSelect2 API error: ' . json_encode($response));
            $this->respondSelect2([], 0);

            return;
        }

        $rows = $this->extractProductList($response);
        $total = $this->extractTotalCount($response, count($rows));

        $items = [];
        foreach ($rows as $p) {
            $row = is_array($p) ? $p : (array) $p;
            // Solo productos enabled (status = 1) para catálogo y búsquedas de pedido.
            if (isset($row['status']) && (int) $row['status'] !== 1) {
                continue;
            }
            $mapped = $this->mapProductToSelect2Item($row, $langOrder);
            if ((int) ($mapped['id'] ?? 0) > 0) {
                $items[] = $mapped;
            }
        }

        $this->respondSelect2($items, $total);
    }

    /**
     * @param array<int, mixed> $items
     */
    private function respondSelect2(array $items, int $total): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $this->output
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode([
                'items' => $items,
                'incomplete_results' => false,
                'total_count' => $total,
            ], JSON_UNESCAPED_UNICODE));
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

            // Lista plana sin wrapper.
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

    /**
     * @param mixed $response
     */
    private function extractTotalCount($response, int $fallback): int
    {
        if (is_array($response)) {
            if (isset($response['total'])) {
                return (int) $response['total'];
            }
            if (isset($response['meta']['total'])) {
                return (int) $response['meta']['total'];
            }
        }
        if (is_object($response)) {
            if (isset($response->total)) {
                return (int) $response->total;
            }
            if (isset($response->meta) && is_object($response->meta) && isset($response->meta->total)) {
                return (int) $response->meta->total;
            }
        }

        return $fallback;
    }

    /**
     * @param array<string, mixed> $item
     * @param array<string, string> $langOrder
     * @return array<string, mixed>
     */
    private function mapProductToSelect2Item(array $item, array $langOrder): array
    {
        $proId = (int) ($item['pro_id'] ?? $item['product_id'] ?? 0);
        $sku = (string) ($item['sku'] ?? '');
        $name = (string) ($item['name'] ?? '');
        $status = (int) ($item['status'] ?? 1);
        $label = strtoupper(trim($sku . ' | ' . $name));

        return [
            'id' => $proId,
            'text' => $label,
            'name' => $name,
            'name_completo' => $label,
            'imagen' => '',
            'rating' => function_exists('company_product_rating_value')
                ? company_product_rating_value($item)
                : (isset($item['rating']) && is_numeric($item['rating']) ? (float) $item['rating'] : 3.5),
            'css' => $status === 0 ? 'color: #721c24;background-color: #f8d7da;' : null,
            'span' => '',
        ];
    }
}
