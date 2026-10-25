<?php

class Save extends Company_Controller
{
    use IsCRUD;
    use IsAction;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('product', 'company/product/', 'products');
    }

    protected function setupValidationRules() {
        $error = $this->HelperLanguage->errors_products();
        $this->form_validation->set_rules('name', 'Name', 'required', array('required' => $error['required_name']));
        $this->form_validation->set_rules('category_id', 'Category', 'callback_required_category');
        $this->form_validation->set_rules(
            'sku',
            'SKU',
            'required|callback_unique_sku',
            array('required' => $error['required_sku'])
        );
        $this->form_validation->set_rules('sale_price', 'Sale Price', 'required', array('required' => $error['required_saleprice']));
        $this->form_validation->set_rules(
            'stock',
            'Stock',
            'callback_stock_greater_than_zero',
            array(
                'stock_greater_than_zero' => $error['stock_must_be_greater_than_0'] ?? 'Stock must be greater than 0'
            )
        );
        $this->form_validation->set_rules('purchase_price', 'Purchase price', 'required', array('required' => $error['required_purchaseprice']));
    }

    public function stock_greater_than_zero($stock)
    {
        $error = $this->HelperLanguage->errors_products();
        if ($stock === null || trim((string) $stock) === '') {
            $this->form_validation->set_message(
                'stock_greater_than_zero',
                $error['stock_must_be_greater_than_0'] ?? 'Stock must be greater than 0'
            );
            return false;
        }

        $normalizedStock = (float) $this->Commonmodel->get_value_format($stock);
        if ($normalizedStock <= 0) {
            $this->form_validation->set_message(
                'stock_greater_than_zero',
                $error['stock_must_be_greater_than_0'] ?? 'Stock must be greater than 0'
            );
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

    public function unique_sku($sku_nuevo){
        if (trim((string) $sku_nuevo) === '') {
            return true;
        }

        $langId = $this->getUserData('language_id');
        $mensajes = $this->langmodel->getMdl('errores_producto', $langId);
        $existing = $this->getDataFromApi("product/sku-{$sku_nuevo}");
        $hasExisting = false;
        if (is_array($existing)) {
            $hasExisting = !isset($existing['error']) && isset($existing['pro_id']) && (int) $existing['pro_id'] > 0;
        } elseif (is_object($existing)) {
            $hasExisting = isset($existing->pro_id) && (int) $existing->pro_id > 0;
        }
        if ($hasExisting) {
            $this->form_validation->set_message('unique_sku', $mensajes['sku_existente']);
            return false;
        }
        return true;
    }

    protected function getValidationErrorMessage()
    {
        $err = $this->HelperLanguage->errors_products();
        return $err['validation_error_title'] ?? 'Validation error';
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

    /**
     * Genera el nombre de archivo según la nomenclatura SKU, SKU_PIC2..SKU_PIC5.
     */
    protected function buildProductImageName(string $sku, int $slotIndex, string $originalName): string
    {
        $safeSku = str_replace('/', '', mb_strtoupper(trim($sku)));
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        $ext = $ext !== '' ? '.' . $ext : '';

        if ($slotIndex <= 0) {
            return $safeSku . $ext;
        }

        return $safeSku . '_PIC' . ($slotIndex + 1) . $ext;
    }

    /**
     * Adjunta las imágenes cargadas en Add al producto recién creado.
     */
    protected function attachUploadedImagesToProduct(int $productId, int $companyId, string $sku): void
    {
        if (!isset($_FILES['images']) || !is_array($_FILES['images'])) {
            return;
        }

        $images = $_FILES['images'];
        // Con un solo archivo en images[], PHP entrega escalares (no arrays).
        // Hay que normalizar antes del foreach o se descarta el upload.
        if (!is_array($images['name'] ?? null)) {
            $images = [
                'name' => [$images['name'] ?? ''],
                'tmp_name' => [$images['tmp_name'] ?? ''],
                'error' => [$images['error'] ?? UPLOAD_ERR_NO_FILE],
                'type' => [$images['type'] ?? ''],
                'size' => [$images['size'] ?? 0],
            ];
        }

        $names = $images['name'] ?? [];
        $tmpNames = $images['tmp_name'] ?? [];
        $errors = $images['error'] ?? [];

        foreach ($names as $index => $originalName) {
            $originalName = (string) $originalName;
            $tmpName = $tmpNames[$index] ?? null;
            $uploadError = $errors[$index] ?? UPLOAD_ERR_NO_FILE;

            if ($uploadError === UPLOAD_ERR_NO_FILE || $originalName === '' || empty($tmpName) || !is_uploaded_file($tmpName)) {
                continue;
            }
            if ($uploadError !== UPLOAD_ERR_OK) {
                log_message('error', 'Save product image upload error. product=' . $productId . ' slot=' . $index . ' code=' . $uploadError);
                continue;
            }

            $newImageName = $this->buildProductImageName($sku, (int) $index, $originalName);
            $multipart = [
                [
                    'name' => 'contents',
                    'contents' => fopen($tmpName, 'r'),
                    'filename' => $newImageName,
                ],
                [
                    'name' => 'name',
                    'contents' => $newImageName,
                ],
                [
                    'name' => 'company_id',
                    'contents' => $companyId,
                ],
                [
                    'name' => 'type',
                    'contents' => 'product',
                ],
            ];

            try {
                $image = $this->apiRequest('images', 'POST', ['multipart' => $multipart], false);
                if (is_object($image) && isset($image->uuid) && $image->uuid !== '') {
                    $this->post("product/{$productId}/image/{$image->uuid}");
                } else {
                    log_message('error', 'Save::attachUploadedImagesToProduct missing uuid. product=' . $productId . ' slot=' . $index);
                }
            } catch (\Throwable $e) {
                log_message('error', 'Save::attachUploadedImagesToProduct failed. product=' . $productId . ' slot=' . $index . ' error=' . $e->getMessage());
            }
        }
    }

    protected function save()
    {
        $request = $this->input->post();
        $companyId = $this->getCompanyId();
        $request['company_id'] = $companyId;
        $request['sale_price'] = $this->Commonmodel->get_value_format($request['sale_price']);
        $request['stock'] = $this->Commonmodel->get_value_format($request['stock']);
        $request['purchase_price'] = $this->Commonmodel->get_value_format($request['purchase_price']);
        $request['price_reduced'] = $this->Commonmodel->get_value_format($request['price_reduced']);
        $request['fob_price'] = $this->Commonmodel->get_value_format($request['fob_price']);

        $unitInput = $request['unit_id'] ?? ($request['unit_type'] ?? null);
        $unitId = $this->resolveUnitIdFromInput($unitInput, (int) $companyId);
        if ($unitId !== null) {
            $request['unit_id'] = $unitId;
        }
        unset($request['unit_type']);

        // Asegurar que category_id sea siempre array para multiselect (la API espera array)
        if (isset($request['category_id'])) {
            $request['category_id'] = is_array($request['category_id'])
                ? array_values(array_filter(array_map('intval', $request['category_id'])))
                : array((int) $request['category_id']);
        }

        //TODO: set images format

        $response=  $this->store($request);

        if ($this->hasError($response)){
            $err = $this->HelperLanguage->errors_products();
            $errorMessage = is_array($err) ? ($err['error_occurred'] ?? '') : '';
            $this->jsonError($response, $errorMessage !== '' ? $errorMessage : 'Error creating product.');
        }

        $createdProduct = is_object($response) ? $response : (is_array($response) ? (object) $response : null);
        $createdProductId = isset($createdProduct->pro_id) ? (int) $createdProduct->pro_id : 0;
        $createdSku = isset($createdProduct->sku) && $createdProduct->sku !== '' ? (string) $createdProduct->sku : (string) ($request['sku'] ?? '');
        if ($createdProductId > 0 && $createdSku !== '') {
            $this->attachUploadedImagesToProduct($createdProductId, (int) $companyId, $createdSku);
        }

        $msg = $this->HelperLanguage->errors_products();
        $successMessage = is_array($msg) ? ($msg['success_created'] ?? $msg['success_saved'] ?? '') : '';
        if ($successMessage === '') {
            $toltip = $this->getModuleLang('cmp-toltip');
            $successMessage = is_array($toltip) ? ($toltip['success_created'] ?? $toltip['success_saved'] ?? '') : '';
        }
        $this->jsonSuccess(
            $successMessage !== '' ? $successMessage : 'Product saved successfully.',
            [
                'product_id' => $createdProductId,
                'urlredirect' => company_url('products'),
            ]
        );
    }

    public function run()
    {
        $this->save();
    }

}
