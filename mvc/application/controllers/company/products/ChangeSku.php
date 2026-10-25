<?php

require_once APPPATH . 'controllers/company/products/Form.php';

class ChangeSku extends Form
{
    use IsAction;
    use IsCRUD;

    public function run($productId = null)
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        $productId = (int) $productId;
        if ($productId <= 0) {
            return $this->jsonSkuError('Invalid product.', 404);
        }

        $newSku = trim(str_replace('&quot;', '"', (string) $this->input->post('sku')));
        $lang = $this->getModuleLang('cmp-products');
        $errorsLang = $this->HelperLanguage->errors_products();

        if ($newSku === '') {
            return $this->jsonSkuError($lang['change_sku_required'] ?? ($errorsLang['required_sku'] ?? ''), 422);
        }

        $product = $this->getObjectFromApi("product/{$productId}");
        if (!$product || (is_array($product) && isset($product['error']))) {
            return $this->jsonSkuError('Product not found.', 404);
        }

        $currentSku = trim(str_replace('&quot;', '"', (string) ($product->sku ?? '')));
        if (strcasecmp($currentSku, $newSku) === 0) {
            return $this->jsonSkuError($lang['change_sku_same'] ?? '', 422);
        }

        $existing = $this->getDataFromApi('product/sku-' . rawurlencode($newSku), ['companyId' => $this->getCompanyId()]);
        $existingId = null;
        if (is_array($existing) && !isset($existing['error']) && isset($existing['pro_id'])) {
            $existingId = (int) $existing['pro_id'];
        } elseif (is_object($existing) && isset($existing->pro_id)) {
            $existingId = (int) $existing->pro_id;
        }
        if ($existingId !== null && $existingId !== $productId) {
            return $this->jsonSkuError($errorsLang['sku_existente'] ?? ($lang['sku_existente'] ?? ''), 422);
        }

        $updated = $this->put("product/{$productId}", [
            'sku' => $newSku,
            'company_id' => $this->getCompanyId(),
        ]);

        if ($this->hasError($updated)) {
            $message = is_array($updated)
                ? ($updated['message']['message'] ?? $updated['message'] ?? 'Error')
                : 'Error';
            if (is_array($message)) {
                $message = $message['message'] ?? json_encode($message);
            }

            return $this->jsonSkuError((string) $message, 422);
        }

        return $this->Helper->response_json([
            'success' => true,
            'message' => $lang['change_sku_success'] ?? '',
            'sku' => $newSku,
        ]);
    }

    private function jsonSkuError(string $message, int $code = 400)
    {
        http_response_code($code);
        return $this->Helper->response_json([
            'success' => false,
            'message' => $message,
        ]);
    }
}
