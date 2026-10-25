<?php

class Update extends Company_Controller
{
    use IsAction;
    use IsCRUD;
    protected $itemId;

    function __construct()
    {
        parent::__construct();
        $this->initCrud('product', 'company/product/', 'products');
    }

    protected function setupValidationRules()
    {
        $error = $this->HelperLanguage->errors_products();
        $this->form_validation->set_rules('name', 'Name', 'required', array('required' => $error['required_name']));
        $this->form_validation->set_rules('category_id', 'Category', 'callback_required_category');
        $this->form_validation->set_rules(
            'sale_price',
            'Sale Price',
            'required',
            array('required' => $error['required_saleprice'])
        );
        $this->form_validation->set_rules(
            'stock',
            'Stock',
            'callback_stock_greater_than_zero',
            array(
                'stock_greater_than_zero' => $error['stock_must_be_greater_than_0'] ?? 'Stock must be greater than 0'
            )
        );
        $this->form_validation->set_rules(
            'purchase_price',
            'Purchase price',
            'required',
            array('required' => $error['required_purchaseprice'])
        );
        $this->form_validation->set_rules('status', 'Status', 'callback_required_status');
    }

    /**
     * Valida que el estado sea 0 (inactivo) o 1 (activo).
     */
    public function required_status($status)
    {
        $productsLang = $this->getModuleLang('cmp-products');
        $message = $productsLang['error_status'] ?? 'Status is required';

        if ($status === null || $status === false || $status === '') {
            $this->form_validation->set_message('required_status', $message);
            return false;
        }

        if (!in_array((string) $status, ['0', '1'], true)) {
            $this->form_validation->set_message('required_status', $message);
            return false;
        }

        return true;
    }

    /**
     * Extrae el mensaje de error de una respuesta API (array u objeto anidado).
     */
    protected function extractApiErrorMessage($response): string
    {
        if (!is_array($response)) {
            return '';
        }

        $msg = $response['message'] ?? null;
        if (is_string($msg) && trim($msg) !== '') {
            return trim($msg);
        }
        if (is_array($msg)) {
            $nested = $msg['message'] ?? '';
            return is_string($nested) ? trim($nested) : '';
        }
        if (is_object($msg) && isset($msg->message)) {
            return trim((string) $msg->message);
        }

        return '';
    }

    /**
     * Valida que el stock ingresado sea mayor a cero.
     */
    public function stock_greater_than_zero($stock)
    {
        $error = $this->HelperLanguage->errors_products();
        $message = $error['stock_must_be_greater_than_0'] ?? 'Stock must be greater than 0';

        if ($stock === null || trim((string) $stock) === '') {
            $this->form_validation->set_message('stock_greater_than_zero', $message);
            return false;
        }

        $normalizedStock = (float) $this->Commonmodel->get_value_format($stock);
        if ($normalizedStock <= 0) {
            $this->form_validation->set_message('stock_greater_than_zero', $message);
            return false;
        }

        return true;
    }

    /**
     * Valida que se haya seleccionado al menos una categoría (multiselect).
     */
    public function required_category($category_id)
    {
        $error = $this->HelperLanguage->errors_products();
        $ids = $this->input->post('category_id');
        if (!is_array($ids)) {
            $ids = $ids !== null && $ids !== '' ? array($ids) : array();
        }
        $ids = array_filter(array_map('intval', $ids));
        if (empty($ids)) {
            $this->form_validation->set_message('required_category', isset($error['required_category']) ? $error['required_category'] : 'Debe seleccionar al menos una categoría.');
            return false;
        }
        return true;
    }

    public function unique_sku($sku_nuevo)
    {
        if (trim((string) $sku_nuevo) === '') {
            return true;
        }

        $langId = $this->getUserData('language_id');
        $mensajes = $this->langmodel->getMdl('errores_producto', $langId);
        $existing = $this->getDataFromApi("product/sku-{$sku_nuevo}",['companyId'=>$this->getCompanyId()]);
        $existingProductId = null;
        if (is_array($existing) && !isset($existing['error']) && isset($existing['pro_id'])) {
            $existingProductId = (int) $existing['pro_id'];
        } elseif (is_object($existing) && isset($existing->pro_id)) {
            $existingProductId = (int) $existing->pro_id;
        }

        if ($existingProductId !== null && $existingProductId !== (int) $this->itemId) {
            $this->form_validation->set_message('unique_sku', $mensajes['sku_existente']);
            return false;
        }
        return true;
    }

    protected function prepareParam($paramName, $valueFormat = false)
    {
        $paramValue = $this->testInput($this->input->post($paramName, true));
        return $valueFormat ? $this->Commonmodel->get_value_format($paramValue) : $paramValue;
    }

    /**
     * Convierte el valor del select de unidad (id o texto) al unit_id esperado por la API.
     */
    protected function resolveUnitIdFromInput($unitInput, int $companyId): ?int
    {
        if ($unitInput === null || $unitInput === '') {
            return null;
        }

        $units = $this->getObjectFromApi("company/{$companyId}/units");
        if (!is_array($units) && !is_object($units)) {
            return is_numeric($unitInput) ? (int) $unitInput : null;
        }

        foreach ((array) $units as $unit) {
            $unit = is_object($unit) ? $unit : (object) $unit;
            $unitId = isset($unit->unit_id) ? (int) $unit->unit_id : null;
            $unitName = isset($unit->unit) ? (string) $unit->unit : '';
            if ($unitId === null) {
                continue;
            }
            if ((string) $unitInput === (string) $unitId || mb_strtolower((string) $unitInput) === mb_strtolower($unitName)) {
                return $unitId;
            }
        }

        return is_numeric($unitInput) ? (int) $unitInput : null;
    }

    function run($producto_id = null)
    {
        $this->itemId = $producto_id;
        if ($producto_id == null) {
            return show_404();
        }
        $product = $this->getObjectFromApi("product/{$producto_id}");
        if (!isset($product)) {
            return show_404();
        }

        $request = $this->input->post();


        if (!$this->validate()) {
            $err = $this->HelperLanguage->errors_products();
            $title = $err['validation_error_title'] ?? 'Validation error';
            return $this->Helper->response_json([
                'success' => false,
                'msg'     => $this->form_validation->error_string(),
                'message' => $title,
                'errors'  => $this->form_validation->error_array()
            ]);
        }

        $request['name'] = $this->prepareParam('name');
        $request['barcode'] = $this->prepareParam('barcode');
        $unitInput = $this->prepareParam('unit_type');
        $unitId = $this->resolveUnitIdFromInput($unitInput, (int) $this->getCompanyId());
        if ($unitId !== null) {
            $request['unit_id'] = $unitId;
        }
        unset($request['unit_type']);
        // $request['currency_type'] = $this->testInput($this->input->post('currency_type')); no es ta funcionando
        $request['sale_price'] = $this->prepareParam('sale_price', true);
        $request['fob_price'] = $this->prepareParam('fob_price', true);
        $request['purchase_price'] = $this->prepareParam('purchase_price', true);
        $request['minimum_stock'] = $this->prepareParam('minimum_stock', true);
        $request['stock'] = $this->prepareParam('stock', true);
        $request['notify_minimum_stock'] = $request['notify_minimum_stock'] ?? 0;
        $request['tags'] = $this->prepareParam('tags');
        $request['descriptions'] = $this->prepareParam('descriptions');
        $request['locations'] = $this->prepareParam('locations');
        $request['price_reduced'] = $this->prepareParam('price_reduced', true);
        $request['company_id'] = $this->getCompanyId();

        $newStatus = (int) $this->input->post('status');
        $currentStatus = (int) ($product->status ?? 1);
        unset($request['status']);

        // Asegurar que category_id sea siempre array para multiselect (igual que en add)
        if (isset($request['category_id'])) {
            $request['category_id'] = is_array($request['category_id'])
                ? array_values(array_filter(array_map('intval', $request['category_id'])))
                : array((int) $request['category_id']);
        }

        //TODO: que hacemos con las imagenes?

        //1. actualizamos el producto (campos generales; el estado usa enable/disable como en el listado)
        $updated = $this->updateItem($producto_id, $request);

        if ($this->hasError($updated)) {
            $message = $this->extractApiErrorMessage($updated);
            return $this->Helper->response_json([
                'success' => false,
                'msg'     => $message,
                'message' => $message,
            ]);
        }

        if ($newStatus !== $currentStatus) {
            $statusAction = $newStatus === 1 ? 'enable' : 'disable';
            $statusUpdated = $this->put("product/{$producto_id}/{$statusAction}");
            if ($this->hasError($statusUpdated)) {
                $mensajes_producto = $this->getModuleLang('cmp-products');
                $message = $this->extractApiErrorMessage($statusUpdated)
                    ?: ($mensajes_producto['error_general'] ?? 'Error updating status.');
                return $this->Helper->response_json([
                    'success' => false,
                    'msg'     => $message,
                    'message' => $message,
                ]);
            }
        }

        $mensajes_producto = $this->getModuleLang('cmp-products');
        $successMessage = $mensajes_producto['success'] ?? 'Record saved successfully.';
        $msg = '["' . $successMessage . '", "success", "#A5DC86"]';

        $this->flashMessage($msg);
        $response = [
            'success' => true,
            'message' => $successMessage,
            'updated' => is_array($updated) ? ($updated['data'] ?? $updated) : $updated,
            'urlredirect' => company_url('products'),
        ];

        return $this->Helper->response_json($response);
    }
}
