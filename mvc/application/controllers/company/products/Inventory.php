<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Listado de productos con precios e inventario (submayor por producto).
 */
class Inventory extends Company_Controller
{
    public function index()
    {
        $this->load->library('pagination');
        $langProducts = $this->getModuleLang('cmp-products');
        $langToltip = $this->getModuleLang('cmp-toltip');
        $companyId = (int) $this->getCompanyId();

        $page = max(1, (int) ($this->input->get('page') ?? 1));
        $perPage = max(10, min(100, (int) ($this->input->get('per_page') ?? 25)));
        $search = trim((string) ($this->input->get('q') ?? ''));
        $stockFilter = (string) ($this->input->get('stock_filter') ?? 'all');
        if (! in_array($stockFilter, ['all', 'with_stock', 'without_stock'], true)) {
            $stockFilter = 'all';
        }
        $categoryIds = $this->normalizeCategoryIds($this->input->get('categories'));

        $response = $this->post("company/{$companyId}/products/inventory-list", [
            'page' => $page,
            'per_page' => $perPage,
            'search' => $search,
            'stock_filter' => $stockFilter,
            'category_ids' => $categoryIds,
        ]);

        $items = [];
        $total = 0;
        $currentPage = $page;
        $perPageResolved = $perPage;
        $apiError = '';

        if ($this->hasError($response)) {
            $apiError = $this->resolveApiErrorMessage($response);
            if ($apiError === '') {
                $apiError = (string) ($langProducts['inventory_error_generic'] ?? 'Error loading data');
            }
        } elseif (is_array($response)) {
            $items = is_array($response['data'] ?? null) ? $response['data'] : [];
            $total = (int) ($response['total'] ?? count($items));
            $currentPage = (int) ($response['current_page'] ?? $page);
            $perPageResolved = (int) ($response['per_page'] ?? $perPage);
        } elseif (is_object($response)) {
            $items = is_array($response->data ?? null) ? $response->data : [];
            $total = (int) ($response->total ?? count($items));
            $currentPage = (int) ($response->current_page ?? $page);
            $perPageResolved = (int) ($response->per_page ?? $perPage);
        }

        $categories = $this->loadInventoryCategories($companyId);

        $query = [];
        if ($search !== '') {
            $query['q'] = $search;
        }
        if ($stockFilter !== 'all') {
            $query['stock_filter'] = $stockFilter;
        }
        foreach ($categoryIds as $catId) {
            $query['categories'][] = $catId;
        }
        if ($perPageResolved !== 25) {
            $query['per_page'] = $perPageResolved;
        }

        $baseUrl = company_url('products/inventory');
        if ($query !== []) {
            $baseUrl .= '?'.http_build_query($query);
        }

        $this->pagination->initialize([
            'base_url' => $baseUrl,
            'total_rows' => $total,
            'per_page' => $perPageResolved,
            'page_query_string' => true,
            'query_string_segment' => 'page',
            'use_page_numbers' => true,
            'num_links' => 2,
            'full_tag_open' => '<ul class="pagination pagination-sm justify-content-end mb-0">',
            'full_tag_close' => '</ul>',
            'num_tag_open' => '<li class="page-item">',
            'num_tag_close' => '</li>',
            'cur_tag_open' => '<li class="page-item active"><span class="page-link">',
            'cur_tag_close' => '</span></li>',
            'next_tag_open' => '<li class="page-item">',
            'next_tag_close' => '</li>',
            'prev_tag_open' => '<li class="page-item">',
            'prev_tag_close' => '</li>',
            'anchor_class' => 'page-link',
            'first_link' => $langProducts['pagination_first'] ?? 'First',
            'last_link' => $langProducts['pagination_last'] ?? 'Last',
            'prev_link' => $langProducts['pagination_prev'] ?? 'Prev',
            'next_link' => $langProducts['pagination_next'] ?? 'Next',
        ]);

        $this->addJsFile(function_exists('dated_file_url')
            ? dated_file_url('companyasset/company/product/inventory.js')
            : base_url('companyasset/company/product/inventory.js'));

        $data = [
            'page' => 'products',
            'navPage' => 'products-inventory',
            'pageTexts' => $langProducts,
            'toltipLang' => $langToltip,
            'items' => $items,
            'total' => $total,
            'search' => $search,
            'stockFilter' => $stockFilter,
            'selectedCategoryIds' => $categoryIds,
            'categories' => $categories,
            'perPage' => $perPageResolved,
            'paginate' => $this->pagination->create_links(),
            'apiError' => $apiError,
            'movementsUrl' => company_url('products/inventory/movements/'),
            'viewUrl' => company_url('products/view/'),
            'editUrl' => company_url('products/edit/'),
            'listUrl' => company_url('products/inventory'),
            'exportUrl' => company_url('products/inventory/export'),
            'exportHeadings' => [
                $langProducts['field_sku'] ?? 'SKU',
                $langProducts['field_name'] ?? 'Name',
                $langProducts['field_sale_price'] ?? 'Sale price',
                $langProducts['inventory_col_stock'] ?? 'Inventory',
                $langProducts['field_category_name'] ?? 'Category',
            ],
            'exportHeadingsPdf' => [
                $langProducts['field_sku'] ?? 'SKU',
                $langProducts['field_name'] ?? 'Name',
                $langProducts['field_sale_price'] ?? 'Sale price',
                $langProducts['inventory_col_stock'] ?? 'Inventory',
                $langProducts['inventory_col_real_stock'] ?? 'Real inventory',
                $langProducts['field_category_name'] ?? 'Category',
            ],
            'exportPdfLabels' => [
                'title' => $langProducts['inventory_pdf_document_title'] ?? 'Product inventory',
                'print_date' => $langProducts['inventory_pdf_print_date'] ?? 'Print date',
                'company' => $langProducts['inventory_pdf_company'] ?? 'Company',
                'phone' => $langProducts['inventory_pdf_phone'] ?? 'Phone',
                'email' => $langProducts['inventory_pdf_email'] ?? 'Email',
                'address' => $langProducts['inventory_pdf_address'] ?? 'Address',
            ],
        ];

        $this->showContent('company/product/inventory_list', $data);
    }

    /**
     * Exportar inventario (Excel o PDF) con los filtros actuales.
     */
    public function export()
    {
        // Proxy a la API: Excel/PDF de inventario completo pueden tardar varios minutos.
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        $langProducts = $this->getModuleLang('cmp-products');
        $companyId = (int) $this->getCompanyId();
        $search = trim((string) ($this->input->post('q') ?? ''));
        $stockFilter = (string) ($this->input->post('stock_filter') ?? 'all');
        if (! in_array($stockFilter, ['all', 'with_stock', 'without_stock'], true)) {
            $stockFilter = 'all';
        }
        $categoryIds = $this->normalizeCategoryIds($this->input->post('categories'));
        $typeFile = (string) ($this->input->post('type_file') ?? 'excel');
        if (! in_array($typeFile, ['excel', 'pdf', 'csv'], true)) {
            $typeFile = 'excel';
        }

        $headings = $this->input->post('headings');
        if (! is_array($headings) || $headings === []) {
            if ($typeFile === 'pdf') {
                $headings = [
                    $langProducts['field_sku'] ?? 'SKU',
                    $langProducts['field_name'] ?? 'Name',
                    $langProducts['field_sale_price'] ?? 'Sale price',
                    $langProducts['inventory_col_stock'] ?? 'Inventory',
                    $langProducts['inventory_col_real_stock'] ?? 'Real inventory',
                    $langProducts['field_category_name'] ?? 'Category',
                ];
            } else {
                $headings = [
                    $langProducts['field_sku'] ?? 'SKU',
                    $langProducts['field_name'] ?? 'Name',
                    $langProducts['field_sale_price'] ?? 'Sale price',
                    $langProducts['inventory_col_stock'] ?? 'Inventory',
                    $langProducts['field_category_name'] ?? 'Category',
                ];
            }
        }

        $pdfLabels = $this->input->post('pdf_labels');
        if (! is_array($pdfLabels) || $pdfLabels === []) {
            $pdfLabels = [
                'title' => $langProducts['inventory_pdf_document_title'] ?? 'Product inventory',
                'print_date' => $langProducts['inventory_pdf_print_date'] ?? 'Print date',
                'company' => $langProducts['inventory_pdf_company'] ?? 'Company',
                'phone' => $langProducts['inventory_pdf_phone'] ?? 'Phone',
                'email' => $langProducts['inventory_pdf_email'] ?? 'Email',
                'address' => $langProducts['inventory_pdf_address'] ?? 'Address',
            ];
        }

        $export = $this->apiRawRequest("company/{$companyId}/products/inventory-export", [
            'search' => $search,
            'stock_filter' => $stockFilter,
            'category_ids' => $categoryIds,
            'type_file' => $typeFile,
            'headings' => array_values($headings),
            'pdf_labels' => $pdfLabels,
        ], 'POST', [
            'headers' => ['Accept' => '*/*'],
            // PDF de milés de filas puede superar el timeout por defecto del cliente HTTP (30s).
            'timeout' => 300.0,
            'connect_timeout' => 30.0,
        ]);

        if ($this->hasError($export)) {
            $this->jsonError($export, $langProducts['inventory_export_failed'] ?? 'Export failed');
            return;
        }

        if (! is_object($export) || ! method_exists($export, 'getHeader')) {
            $this->jsonError(['error' => 'export_failed'], $langProducts['inventory_export_failed'] ?? 'Export failed');
            return;
        }

        $disposition = $export->getHeader('Content-Disposition');
        if (! empty($disposition)) {
            header('Content-Disposition: '.$disposition[0]);
        }

        $contentType = $export->getHeader('Content-Type');
        if (! empty($contentType)) {
            header('Content-Type: '.$contentType[0]);
        }

        $contentLength = $export->getHeader('Content-Length');
        if (! empty($contentLength)) {
            header('Content-Length: '.$contentLength[0]);
        }

        echo $export->getBody()->getContents();
        exit;
    }

    /**
     * Movimientos de inventario de un producto (JSON para modal submayor).
     */
    public function movements($proId = null)
    {
        $proId = (int) $proId;
        $lang = $this->getModuleLang('cmp-products');

        if ($proId < 1) {
            header('Content-Type: application/json');
            http_response_code(422);
            echo json_encode(['message' => (string) ($lang['inventory_error_invalid_product'] ?? 'Invalid product')]);
            exit;
        }

        $companyId = (int) $this->getCompanyId();
        $response = $this->getObjectFromApi("company/{$companyId}/products/{$proId}/inventory-movements");

        if ($this->hasError($response)) {
            header('Content-Type: application/json');
            http_response_code(404);
            echo json_encode(['message' => (string) ($lang['inventory_error_not_found'] ?? 'Not found')]);
            exit;
        }

        return $this->jsonSuccess('', is_array($response) ? $response : (array) $response);
    }

    /**
     * @param mixed $raw
     * @return list<int>
     */
    private function normalizeCategoryIds($raw): array
    {
        if ($raw === null || $raw === false || $raw === '') {
            return [];
        }
        if (! is_array($raw)) {
            $raw = [$raw];
        }
        $ids = [];
        foreach ($raw as $value) {
            $id = (int) $value;
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @return list<object>
     */
    private function loadInventoryCategories(int $companyId): array
    {
        $response = $this->getDataFromApi("company/{$companyId}/categories", ['withProducts' => 1], false);
        $rows = [];
        if (is_array($response)) {
            $rows = $response;
        } elseif (is_object($response) && isset($response->data) && is_array($response->data)) {
            $rows = $response->data;
        }

        $imageMap = $this->loadCategoryImageMap();

        $normalized = [];
        foreach ($rows as $row) {
            $item = is_object($row) ? $row : (object) $row;
            $catId = (int) ($item->cat_id ?? 0);
            if ($catId < 1) {
                continue;
            }
            $withImage = $imageMap[$catId] ?? null;
            $normalized[] = (object) [
                'cat_id' => $catId,
                'category_name' => (string) ($item->category_name ?? ''),
                'products_count' => (int) ($item->products_count ?? $item->qty_products ?? 0),
                'image' => $withImage !== null ? ($withImage->image ?? null) : null,
            ];
        }

        usort($normalized, static function ($a, $b) {
            return strcasecmp((string) $a->category_name, (string) $b->category_name);
        });

        return $normalized;
    }

    /**
     * @return array<int, object>
     */
    private function loadCategoryImageMap(): array
    {
        try {
            $catResponse = $this->getObjectFromApi(
                'category',
                [
                    'all' => 1,
                    'props' => ['cat_id', 'category_name', 'image'],
                    'relations' => ['image'],
                    'companyId' => $this->getCompany(),
                ]
            );
        } catch (\Throwable $e) {
            return [];
        }

        $items = [];
        if (is_array($catResponse)) {
            $items = $catResponse;
        } elseif (is_object($catResponse) && isset($catResponse->data) && is_array($catResponse->data)) {
            $items = $catResponse->data;
        }

        $map = [];
        foreach ($items as $cat) {
            $cat = is_object($cat) ? $cat : (object) (array) $cat;
            $id = (int) ($cat->cat_id ?? 0);
            if ($id > 0) {
                $map[$id] = $cat;
            }
        }

        return $map;
    }
}
