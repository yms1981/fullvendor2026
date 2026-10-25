<?php

class Form  extends Company_Controller
{
    use IsCRUD;
    public function __construct()
    {
        parent::__construct();
        $this->initCrud('product', 'company/product/', 'products');
    }

    protected function getDataForView($view=null,$dataForView=[]) {
        $dataForView = parent::getDataForView($view,$dataForView);
        $companyId = $this->getCompanyId();
        if(!isset($dataForView['groups'])) {
            $dataForView['groups'] = $this->fetchProductPriceGroups($dataForView['product'] ?? null);
        }
        $dataForView['currencyList'] = $this->getObjectFromApi("company/{$companyId}/currencies");
        $catResponse = $this->getObjectFromApi(
            'category',
            [
                'all' => 1,
                'status' => 1,
                'company' => $companyId,
                'props' => ['cat_id', 'category_name', 'category_status'],
                'sort' => 'category_name',
                'companyId' => $this->getCompany()
            ]
        );
        if (is_array($catResponse) && isset($catResponse['error'])) {
            $categoryList = [];
        } elseif (is_array($catResponse)) {
            $categoryList = $catResponse;
        } elseif (is_object($catResponse) && isset($catResponse->data)) {
            $categoryList = is_array($catResponse->data) ? $catResponse->data : [];
        } else {
            $categoryList = [];
        }
        $categoryList = array_values(array_filter($categoryList, static function ($row) {
            $o = is_object($row) ? $row : (object) $row;
            if (property_exists($o, 'category_status')) {
                return (int) $o->category_status === 1;
            }
            if (property_exists($o, 'status')) {
                return (int) $o->status === 1;
            }
            // Si la API ya filtró con status=1 y no expone el campo, no ocultar por error.
            return true;
        }));
        $dataForView['categorylist'] = $categoryList;
        $dataForView['unit_type'] = $this->getObjectFromApi("company/{$companyId}/units");
        $pageTexts = $dataForView['pageTexts'] ?? [];
        $pageTexts = $pageTexts +
            $this->getModuleLang('cmp-products')    +
            $this->getModuleLang('comp-login-head') +
            $this->getModuleLang('comp-csv-flds')   +
            $this->getModuleLang('semaphore')       +
            $this->getModuleLang('company-csvf')   +
            $this->getModuleLang('cm-mensajes')    +
            $this->getModuleLang('cmp-toltip')     +
            $this->HelperLanguage->errors_products();

        $cur = $this->getCurrency();
        $pageTexts['currencySymbol'] = $cur->symbol;
        $dataForView['pageTexts'] =  $pageTexts;
        return $dataForView;
    }

    /**
     * @param object|null $product
     * @return array<int, object>
     */
    protected function fetchProductPriceGroups($product = null): array
    {
        $params = [
            'company_id' => $this->getCompanyId(),
        ];
        if ($product !== null && isset($product->pro_id)) {
            $params['product_id'] = (int) $product->pro_id;
        }

        return $this->coerceApiResponseToObjectList(
            $this->getObjectFromApi('product/price-list', $params)
        );
    }

    /**
     * Convierte la respuesta de product/price-list en lista indexada de objetos (sin perder campos).
     *
     * @param mixed $response
     * @return array<int, object>
     */
    protected function coerceApiResponseToObjectList($response): array
    {
        if ($response === null || $response === false) {
            return [];
        }

        if (is_object($response) && isset($response->error)) {
            return [];
        }
        if (is_array($response) && isset($response['error'])) {
            return [];
        }

        if (is_array($response) && isset($response['data']) && ! array_key_exists(0, $response)) {
            $response = $response['data'];
        } elseif (is_object($response) && isset($response->data)) {
            $response = $response->data;
        }

        $list = [];
        if (is_array($response)) {
            foreach ($response as $item) {
                if (! is_array($item) && ! is_object($item)) {
                    continue;
                }
                $list[] = is_object($item) ? $item : (object) $item;
            }
        } elseif (is_object($response)) {
            foreach ($response as $item) {
                if (! is_array($item) && ! is_object($item)) {
                    continue;
                }
                $list[] = is_object($item) ? $item : (object) $item;
            }
        }

        return $list;
    }
}
