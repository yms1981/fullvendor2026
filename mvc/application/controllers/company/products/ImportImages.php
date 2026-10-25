<?php

class ImportImages extends Company_Controller
{
    use IsCRUD;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('product', 'company/product/', 'products');
    }

    public function index()
    {
        $pageTexts = $this->getModuleLang('cmp-products')
            + $this->getModuleLang('cmp-dropzone')
            + $this->getModuleLang('cm-import-images');

        if (method_exists($this, 'getLangJsonSection')) {
            $pageTexts = array_merge(
                $this->getLangJsonSection('config-crud-tools'),
                $pageTexts,
                $this->getLangJsonSection('cm-import-images')
            );
        }

        $pageTexts['template_download_url'] = import_template_download_url('productimagesguide.xlsx');

        $uploadAuthToken = $this->resolvePageUploadAuthToken();
        $uploadCompanyId = $this->resolvePageUploadCompanyId();

        $data['pageTexts'] = $pageTexts;
        $data['uploadAuthToken'] = $uploadAuthToken;
        $data['uploadCompanyId'] = $uploadCompanyId;
        $data['uploadAuthMissingTitle'] = $this->resolveModuleLangString(
            'cm-import-images',
            'import_images_upload_auth_missing',
            $pageTexts
        );
        if ($data['uploadAuthMissingTitle'] === '') {
            $data['uploadAuthMissingTitle'] = $this->resolveModuleLangString(
                'cm-import-images',
                'import_images_error_unauthenticated',
                $pageTexts
            );
        }
        $data['uploadAuthMissingDetail'] = $this->resolveModuleLangString(
            'cm-import-images',
            'import_images_upload_auth_missing_detail',
            $pageTexts
        );
        $this->showContent('company/product/import_images', $data);
    }

    /**
     * Devuelve token y company_id para subidas (una lectura de sesión, JSON).
     */
    public function uploadCredentials()
    {
        // Leer sesión ANTES de soltar el lock.
        $token = $this->resolvePageUploadAuthToken();
        $companyId = $this->resolvePageUploadCompanyId();
        if ($companyId < 1 && isset($this->fvCachedUploadCompanyId)) {
            $companyId = (int) $this->fvCachedUploadCompanyId;
        }
        if ($token !== '' && property_exists($this, 'userToken')) {
            $this->userToken = $token;
        }
        if (method_exists($this, 'releaseSessionLockIfActive')) {
            $this->releaseSessionLockIfActive();
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'token' => $token,
            'companyId' => $companyId,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Índice de SKU de productos de la compañía (validación de nombres de archivo en el cliente).
     */
    public function skuIndex()
    {
        // Token + company ANTES de soltar el lock de sesión.
        $token = $this->resolvePageUploadAuthToken();
        if ($token === '' && ! empty($this->userToken)) {
            $token = trim((string) $this->userToken);
        }
        if ($token !== '' && property_exists($this, 'userToken')) {
            $this->userToken = $token;
        }
        $compId = (int) $this->resolvePageUploadCompanyId();
        if ($compId < 1 && isset($this->fvCachedUploadCompanyId)) {
            $compId = (int) $this->fvCachedUploadCompanyId;
        }
        if (method_exists($this, 'releaseSessionLockIfActive')) {
            $this->releaseSessionLockIfActive();
        }

        header('Content-Type: application/json; charset=utf-8');

        if ($compId < 1) {
            http_response_code(401);
            echo json_encode([
                'error' => 'unauthenticated',
                'skus' => [],
                'sku_filter' => false,
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        if ($token === '') {
            http_response_code(401);
            echo json_encode([
                'error' => 'unauthenticated',
                'skus' => [],
                'sku_filter' => false,
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        $response = $this->getObjectFromApi('product/skus', [
            'company' => $compId,
            'company_id' => $compId,
            'companyId' => $compId,
        ]);

        if (method_exists($this, 'hasError') && $this->hasError($response)) {
            log_message('error', 'ImportImages::skuIndex API failed: ' . json_encode($response));
            // 200 + sku vacíos: el cliente puede listar archivos y validar SKU al importar.
            echo json_encode([
                'error' => 'sku_index_failed',
                'skus' => [],
                'sku_filter' => false,
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        $skus = [];
        if (is_array($response)) {
            if (isset($response['data']) && is_array($response['data'])) {
                $skus = $response['data'];
            } elseif ($this->isSequentialArray($response)) {
                $skus = $response;
            }
        } elseif (is_object($response) && isset($response->data) && is_array($response->data)) {
            $skus = $response->data;
        }

        $normalized = [];
        foreach ($skus as $sku) {
            $value = trim((string) $sku);
            if ($value !== '') {
                $normalized[] = $value;
            }
        }

        echo json_encode([
            'skus' => array_values(array_unique($normalized)),
            'sku_filter' => true,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Token Sanctum para subidas AJAX (cabecera/POST), con respaldo en sesión user.token.
     */
    protected function resolvePageUploadAuthToken(): string
    {
        $token = trim((string) ($this->getUserToken() ?? ''));
        if ($token === '') {
            $user = $this->getUserData('user');
            if (is_array($user) && ! empty($user['token']) && is_string($user['token'])) {
                $token = trim($user['token']);
            } elseif (is_object($user) && isset($user->token) && is_string($user->token)) {
                $token = trim($user->token);
            }
        }
        if ($token === '' && method_exists($this, 'attemptMvcTokenRefresh') && $this->attemptMvcTokenRefresh()) {
            $token = trim((string) ($this->getUserToken() ?? ''));
        }
        if ($token !== '') {
            if (property_exists($this, 'userToken')) {
                $this->userToken = $token;
            }
            if (method_exists($this, 'setUserDataBatch')) {
                $this->setUserDataBatch(['token' => $token]);
            } elseif (method_exists($this, 'setUserData')) {
                $this->setUserData('token', $token);
            }
        }

        return $token;
    }

    protected function resolvePageUploadCompanyId(): int
    {
        $companyId = (int) $this->getCompanyId();
        if ($companyId < 1 && isset($this->fvCachedUploadCompanyId)) {
            $companyId = (int) $this->fvCachedUploadCompanyId;
        }
        if ($companyId < 1) {
            $compId = $this->getUserData('compId');
            if ($compId !== null && $compId !== '') {
                $companyId = (int) $compId;
            }
        }

        return $companyId > 0 ? $companyId : 0;
    }

    protected function importImage($imageUUID, $productId, $slot = null)
    {
        $payload = [];
        if ($slot !== null && (int) $slot >= 1 && (int) $slot <= 5) {
            $payload['slot'] = (int) $slot;
        }

        return $this->post(
            "product/{$productId}/image/{$imageUUID}",
            $payload
        );
    }

    /**
     * Slot 1 = sku.ext (principal). Slot N = sku_picN.ext (N = 2..5).
     */
    protected function getImageSlotFromFilename($sku, $fileName): int
    {
        $fileName = $this->normalizeImportImageFileName((string) $fileName);
        $ext = $this->importableImageExtensionPattern();
        $sku = rtrim(trim((string) $sku), '.');

        foreach ($this->skuFilenamePrefixes($sku) as $prefix) {
            $quoted = preg_quote($prefix, '/');
            if (preg_match("/^{$quoted}\\.({$ext})$/i", $fileName)) {
                return 1;
            }
            if (preg_match("/^{$quoted}_pic([2-5])\\.({$ext})$/i", $fileName, $m)) {
                return (int) $m[1];
            }
        }

        if (preg_match('/_pic([2-5])\./i', $fileName, $m)) {
            return (int) $m[1];
        }

        return 1;
    }

    /**
     * @param mixed $importedResult
     * @param array<string, mixed> $langError
     */
    protected function extractImportApiError($importedResult, array $langError): string
    {
        if (is_array($importedResult)) {
            if (!empty($importedResult['error'])) {
                return (string) $importedResult['error'];
            }
            if (!empty($importedResult['message'])) {
                return is_string($importedResult['message'])
                    ? $importedResult['message']
                    : json_encode($importedResult['message']);
            }
        } elseif (is_object($importedResult)) {
            if (!empty($importedResult->error)) {
                return (string) $importedResult->error;
            }
            if (!empty($importedResult->message)) {
                return is_string($importedResult->message)
                    ? $importedResult->message
                    : json_encode($importedResult->message);
            }
        }

        return (string) ($langError['unknown_error'] ?? 'Unknown error');
    }

    protected function importableImageExtensions()
    {
        return [
            '.png', '.PNG', '.jpg', '.JPG', '.jpeg', '.JPEG',
            '.jfif', '.JFIF', '.jpe', '.JPE',
            '.gif', '.GIF', '.webp', '.WEBP', '.bmp', '.BMP',
            '.avif', '.AVIF', '.tif', '.TIF', '.tiff', '.TIFF',
            '.heic', '.HEIC', '.heif', '.HEIF', '.svg', '.SVG',
            '.ico', '.ICO',
        ];
    }

    /** Extensiones aceptadas en validación de nombre sku.ext / sku_picN.ext */
    protected function importableImageExtensionPattern(): string
    {
        return 'png|jpg|jpeg|jfif|jpe|gif|webp|bmp|avif|tif|tiff|heic|heif|svg|ico';
    }

    protected function resolveImportLang(): array
    {
        if (method_exists($this, 'getLangJsonSection')) {
            $fromJson = $this->getLangJsonSection('cm-import-images');
            if (is_array($fromJson) && $fromJson !== []) {
                return $fromJson;
            }
        }

        return $this->getModuleLang('cm-import-images');
    }

    /**
     * @return list<string>
     */
    protected function skuFilenamePrefixes($sku): array
    {
        $sku = trim((string) $sku);
        if ($sku === '') {
            return [];
        }

        $variants = [
            $sku,
            str_replace('/', '-', $sku),
            str_replace('-', '/', $sku),
            str_replace('/', '', $sku),
        ];

        return array_values(array_unique(array_filter($variants, static function ($value) {
            return is_string($value) && $value !== '';
        })));
    }

    protected function getSkuFromFilename($fileName)
    {
        $fileName = $this->normalizeImportImageFileName((string) $fileName);
        $importableImageExtensions = $this->importableImageExtensions();
        $sku = explode('_', $fileName);
        if (count($sku) >= 2) {
            return $sku[0];
        }

        return rtrim(str_replace($importableImageExtensions, '', $sku[0]), '.');
    }

    protected function normalizeImportImageFileName(string $fileName): string
    {
        $fileName = trim($fileName);
        if ($fileName === '') {
            return $fileName;
        }
        $ext = $this->importableImageExtensionPattern();

        return (string) preg_replace('/\.+(' . $ext . ')$/i', '.$1', $fileName);
    }

    protected function findProductBySku($sku)
    {
        $sku = rtrim(trim((string) $sku), '.');
        foreach ($this->skuFilenamePrefixes($sku) as $candidate) {
            $product = $this->getObjectFromApi(
                'product/sku-' . rawurlencode($candidate),
                ['relations' => ['images']]
            );
            if ($product) {
                return $product;
            }
        }

        return null;
    }


    public function import()
    {
        if (method_exists($this, 'getUserToken')) {
            $this->getUserToken();
        }
        if (method_exists($this, 'releaseSessionLockIfActive')) {
            $this->releaseSessionLockIfActive();
        } elseif (function_exists('session_write_close') && session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $impagesForProductsBySku = $this->input->post('product_images');
        $langError = $this->resolveImportLang();
        $responseResults = array();
        if (!is_array($impagesForProductsBySku)) {
            header('Content-Type: application/json');
            echo json_encode([]);
            return;
        }

        // Pre-parse + ordenar por SKU y slot (principal primero) para resultados predecibles.
        $queue = [];
        foreach ($impagesForProductsBySku as $imageFileImage) {
            if (!is_array($imageFileImage) || count($imageFileImage) < 2) {
                continue;
            }
            $fileName = (string) $imageFileImage[0];
            $imageUuid = (string) $imageFileImage[1];
            $skuFromFile = $this->getSkuFromFilename($fileName);
            $queue[] = [
                'fileName' => $fileName,
                'imageUuid' => $imageUuid,
                'skuFromFile' => $skuFromFile,
            ];
        }
        usort($queue, function ($a, $b) {
            $skuCmp = strcasecmp((string) $a['skuFromFile'], (string) $b['skuFromFile']);
            if ($skuCmp !== 0) {
                return $skuCmp;
            }
            $slotA = $this->getImageSlotFromFilename($a['skuFromFile'], $a['fileName']);
            $slotB = $this->getImageSlotFromFilename($b['skuFromFile'], $b['fileName']);
            if ($slotA !== $slotB) {
                return $slotA <=> $slotB;
            }
            return strcasecmp((string) $a['fileName'], (string) $b['fileName']);
        });

        foreach ($queue as $item) {
            $fileName = $item['fileName'];
            $imageUuid = $item['imageUuid'];
            $sku = $item['skuFromFile'];
            $imageResult = ['imagen' => $fileName, 'success' => false, 'error_code' => '', 'slot' => null];
            $product = $this->findProductBySku($sku);

            if ($product) {
                $productSku = (string) $product->sku;
                $productId = $product->pro_id;
                $slot = $this->getImageSlotFromFilename($productSku, $fileName);
                $imageResult['slot'] = $slot;

                if ($this->isFileNameSkuFormatted($productSku, $fileName)) {
                    try {
                        $importedResult = $this->importImage($imageUuid, $productId, $slot);

                        if (isset($importedResult['success']) && $importedResult['success']) {
                            $replaced = !empty($importedResult['data']['replaced']);
                            $assignedSlot = (int) ($importedResult['data']['slot'] ?? $slot);
                            $msgKey = $replaced ? 'upload_replaced_slot' : 'upload_success_slot';
                            $imageResult['mensaje'] = sprintf(
                                (string) ($langError[$msgKey] ?? $langError['upload_success'] ?? '%s'),
                                $fileName,
                                $assignedSlot
                            );
                            $imageResult['success'] = true;
                            $imageResult['slot'] = $assignedSlot;
                            $imageResult['replaced'] = $replaced;
                        } else {
                            $errorDetail = $this->extractImportApiError($importedResult, $langError);
                            $imageResult['error_code'] = 'upload_error';
                            $imageResult['mensaje'] = sprintf(
                                (string) ($langError['upload_error'] ?? '%s — %s'),
                                $fileName,
                                $errorDetail
                            );
                        }
                    } catch (Exception $e) {
                        $imageResult['error_code'] = 'upload_error';
                        $imageResult['mensaje'] = sprintf(
                            (string) ($langError['upload_error'] ?? '%s — %s'),
                            $fileName,
                            $e->getMessage()
                        );
                    }
                } else {
                    $imageResult['error_code'] = 'no_cumple_format';
                    $imageResult['mensaje'] = sprintf(
                        (string) ($langError['no_cumple_format'] ?? '%s'),
                        $fileName
                    );
                }
                $imageResult['product_id'] = (string) $productId;
                $imageResult['sku'] = $productSku;
            } else {
                $imageResult['error_code'] = 'no_sku_existente';
                $imageResult['mensaje'] = sprintf(
                    (string) ($langError['no_sku_existente'] ?? '%s'),
                    $fileName
                );
            }
            if (trim((string) ($imageResult['mensaje'] ?? '')) === '') {
                $imageResult['mensaje'] = sprintf(
                    (string) ($langError['unknown_error'] ?? '%s'),
                    $fileName
                );
            }
            $responseResults[] = $imageResult;
        }

        header('Content-Type: application/json');
        echo json_encode($responseResults);
    }

    public function limpiar_imagenes_sobrantes($images_ids, $pro_id)
    {
        // Eliminar (vía API) las imágenes del producto que no están en la lista a conservar.
        $imagenes = $this->getObjectFromApi("product/{$pro_id}/images");
        if (!$imagenes) {
            return;
        }

        $conservar = is_array($images_ids)
            ? array_map('strval', $images_ids)
            : array_map('strval', explode(',', (string) $images_ids));

        foreach ($imagenes as $imagen) {
            if (!in_array((string) $imagen->img_id, $conservar, true)) {
                $this->apiDelete("productimage/{$imagen->img_id}");
            }
        }
    }

    public function removeproductsfrmcatelog()
    {
        $productIds = array_values(array_filter(
            array_map('trim', explode(",", (string) $this->input->post('productsIds'))),
            function ($value) {
                return $value !== '';
            }
        ));
        $catalogIds = explode(",", (string) $this->input->post('catlogIds'));

        foreach ($catalogIds as $cid) {
            $cid = trim($cid);
            if ($cid === '') {
                continue;
            }
            // Eliminar los productos del catálogo vía API.
            $this->apiDelete("catalog/{$cid}/products", ['products' => json_encode($productIds)]);
        }

        $mensajes = $this->getModuleLang('cm-mensajes');
        $msg = $mensajes['catalogo_productos_removidos'] ?? '';
        echo json_encode([$msg, "success", "#A5DC86"]);
    }

    // Guardar los productos importados de csv


    public function imagenes_enconflicto($compId, $productId)
    {
        $imagenes = $this->getObjectFromApi("product/{$productId}/images");
        if (!$imagenes) {
            return;
        }

        $producto = $this->getObjectFromApi("product/{$productId}");
        $sku = $producto ? $producto->sku : '';

        $lista_conflicto = array();
        foreach ($imagenes as $imagen) {
            $nombre_imagen = $imagen->images;
            if ($this->isFileNameSkuFormatted($sku, $nombre_imagen)) {
                array_push($lista_conflicto, $imagen);
            }
            if ($this->validacionformato_skuconPic1($sku, $nombre_imagen)) {
                array_push($lista_conflicto, $imagen);
            }
        }

        if (count($lista_conflicto) == 2) {
            $imagen_original = $lista_conflicto[0];
            $imagen_pic1 = $lista_conflicto[1];

            // La imagen original pasa a ser la primera y la duplicada (_pic1) se elimina vía API.
            $this->put("productimage/{$imagen_original->img_id}", ['img_order' => 1]);
            $this->apiDelete("productimage/{$imagen_pic1->img_id}");
        }
    }

    public function imagen_existente_skuOriginal($compId, $productId)
    {
        $imagenes = $this->getObjectFromApi("product/{$productId}/images");
        $lista_formato_skuOriginal = array();
        if (!$imagenes) {
            return $lista_formato_skuOriginal;
        }

        $producto = $this->getObjectFromApi("product/{$productId}");
        $sku = $producto ? $producto->sku : '';

        foreach ($imagenes as $imagen) {
            $nombre_imagen = $imagen->images;
            if ($this->isFileNameSkuFormatted($sku, $nombre_imagen)) {
                array_push($lista_formato_skuOriginal, $imagen);
            }
        }

        return $lista_formato_skuOriginal;
    }

    public function imagen_existente_pic1($compId, $productId)
    {
        $imagenes = $this->getObjectFromApi("product/{$productId}/images");
        $lista_formato_skuconPic1 = array();
        if (!$imagenes) {
            return $lista_formato_skuconPic1;
        }

        $producto = $this->getObjectFromApi("product/{$productId}");
        $sku = $producto ? $producto->sku : '';

        foreach ($imagenes as $imagen) {
            $nombre_imagen = $imagen->images;
            if ($this->validacionformato_pic1($sku, $nombre_imagen)) {
                array_push($lista_formato_skuconPic1, $imagen);
            }
        }

        return $lista_formato_skuconPic1;
    }

    public function validacionformato_skuconPic1($sku, $nombre_imagen)
    {
        $formato_pic1 = $sku . '_pic1';
        $ext = $this->importableImageExtensionPattern();
        $validacion_formatopic1 = preg_match("/^" . $formato_pic1 . "\.(" . $ext . ")$/i", $nombre_imagen);
        return $validacion_formatopic1;
    }

    public function obtener_num_pic($sku, $imagen)
    {
        $number = 1;
        $numero_pic = $this->obtener_numeropic($sku, $imagen);

        if ($numero_pic == null) {
            $number = 1;
        } else {
            $number = $numero_pic;
        }

        return $number;
    }


    public function isFileNameSkuFormatted($sku, $nombre_imagen)
    {
        $ext = $this->importableImageExtensionPattern();
        $nombre_imagen = $this->normalizeImportImageFileName((string) $nombre_imagen);
        $sku = rtrim(trim((string) $sku), '.');

        foreach ($this->skuFilenamePrefixes($sku) as $prefix) {
            $quoted = preg_quote($prefix, '/');
            if (preg_match("/^{$quoted}\.({$ext})$/i", $nombre_imagen)) {
                return true;
            }
            if (preg_match("/^{$quoted}_pic([2-5]{1})\.({$ext})$/i", $nombre_imagen)) {
                return true;
            }
        }

        return false;
    }

    public function validacionformato_skuconPic($sku, $nombre_imagen)
    {
        $ext = $this->importableImageExtensionPattern();
        $formato_2 = $sku . '_pic([0-9]{2}|[0-9]{1}|[0-9]{2}|[0-9]{3})';
        $validacion_formato2 = preg_match("/^" . $formato_2 . "\.(" . $ext . ")$/i", $nombre_imagen);
        return $validacion_formato2;
    }

    public function ordenar_imagenes($compId, $producto_id, $orden)
    {
        $imagenes = $this->getObjectFromApi("product/{$producto_id}/images");
        if (!$imagenes) {
            return;
        }

        // El reordenamiento en la API se hace por UUID de imagen (image_id).
        // Construimos un mapa img_id => image_id para traducir el orden recibido.
        $imgIdToUuid = array();
        foreach ($imagenes as $imagen) {
            $imgIdToUuid[(string) $imagen->img_id] = $imagen->image_id;
        }

        $ordenUuids = array();
        foreach ((array) $orden as $nombre_imagen) {
            if ($nombre_imagen === 'noimg.png,') {
                continue;
            }
            $juntos = explode(",", $nombre_imagen);
            $img_id = isset($juntos[1]) ? trim($juntos[1]) : '';
            if ($img_id !== '' && isset($imgIdToUuid[$img_id])) {
                $ordenUuids[] = $imgIdToUuid[$img_id];
            }
        }

        if (!empty($ordenUuids)) {
            // La API reordena, renombra (images.name) y normaliza el orden internamente.
            $this->put("product/{$producto_id}/images/resort", ['images' => $ordenUuids]);
        }

        // Mantener coherente la imagen por defecto (primera del orden) vía API.
        $this->actualizar_imagenDefecto($compId, $producto_id);
    }


    public function actualizar_imagenDefecto($compId, $productId)
    {
        $imagenes = $this->getObjectFromApi("product/{$productId}/images");
        if (!$imagenes) {
            return;
        }

        // Reiniciar la marca de imagen por defecto en todas las imágenes vía API.
        $this->resetear_camposDefectoImagen($imagenes);

        // La primera imagen (img_order == 1) pasa a ser la imagen por defecto.
        foreach ($imagenes as $imagen) {
            if ((int) $imagen->img_order === 1) {
                $this->put("productimage/{$imagen->img_id}", ['img_default' => 1]);
                break;
            }
        }
    }

    public function resetear_camposDefectoImagen($imagenes)
    {
        foreach ($imagenes as $imagen) {
            $this->put("productimage/{$imagen->img_id}", ['img_default' => null]);
        }
    }


}
