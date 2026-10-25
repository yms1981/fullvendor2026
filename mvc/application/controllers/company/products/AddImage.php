<?php

class AddImage extends Company_Controller
{
    use IsCRUD;
    use IsAction;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('product', 'company/product/', 'products');
    }

    public function run($productId)
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $companyId = (int) $this->getCompanyId();
            $productId = (int) $productId;
            $product = $this->getObjectFromApi("product/{$productId}", ['relations' => ['images', 'product_images']]);
            if (!$product || (is_array($product) && isset($product['error']))) {
                echo json_encode(['success' => false, 'message' => 'Product not found']);
                return;
            }

            if (!isset($_FILES['file']) || (int) ($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                echo json_encode([
                    'success' => false,
                    'message' => 'No image uploaded or upload error',
                    'file_error' => $_FILES['file']['error'] ?? null,
                ]);
                return;
            }

            $tmpName = (string) ($_FILES['file']['tmp_name'] ?? '');
            $clientName = (string) ($_FILES['file']['name'] ?? 'upload.jpg');
            if ($tmpName === '' || !is_readable($tmpName)) {
                echo json_encode(['success' => false, 'message' => 'Invalid upload']);
                return;
            }

            $sku = strtoupper((string) ($product->sku ?? ''));
            $skuForFilename = preg_replace('/[\\\\\/]+/', '', $sku);
            $skuForFilename = trim((string) $skuForFilename);
            if ($skuForFilename === '') {
                $skuForFilename = 'product_' . $productId;
            }

            $existingImagesCount = 0;
            if (!empty($product->product_images) && (is_array($product->product_images) || is_object($product->product_images))) {
                $existingImagesCount = count((array) $product->product_images);
            } elseif (!empty($product->images) && (is_array($product->images) || is_object($product->images))) {
                $existingImagesCount = count((array) $product->images);
            }

            $slot = (int) ($this->input->post('slot') ?? 0);
            if ($slot >= 1 && $slot <= 5) {
                $imagePosition = $slot - 1;
            } else {
                $imagePosition = $existingImagesCount;
            }

            $fileExtension = strtolower((string) pathinfo($clientName, PATHINFO_EXTENSION));
            if ($fileExtension === 'jpeg') {
                $fileExtension = 'jpg';
            }
            if ($fileExtension === '') {
                $mime = (string) ($_FILES['file']['type'] ?? '');
                if (strpos($mime, 'png') !== false) {
                    $fileExtension = 'png';
                } elseif (strpos($mime, 'webp') !== false) {
                    $fileExtension = 'webp';
                } elseif (strpos($mime, 'gif') !== false) {
                    $fileExtension = 'gif';
                } else {
                    $fileExtension = 'jpg';
                }
            }

            if ($imagePosition === 0) {
                $newImageName = $skuForFilename . '.' . $fileExtension;
            } else {
                $newImageName = $skuForFilename . '_PIC' . ($imagePosition + 1) . '.' . $fileExtension;
            }

            // String (no stream): Guzzle puede reintentar el POST sin consumir el body.
            $binary = file_get_contents($tmpName);
            if ($binary === false || $binary === '') {
                echo json_encode(['success' => false, 'message' => 'Cannot read upload']);
                return;
            }

            $multipart = [
                [
                    'name' => 'contents',
                    'contents' => $binary,
                    'filename' => $clientName !== '' ? $clientName : $newImageName,
                    'headers' => [
                        'Content-Type' => (string) ($_FILES['file']['type'] ?? 'application/octet-stream'),
                    ],
                ],
                [
                    'name' => 'name',
                    'contents' => $newImageName,
                ],
                [
                    'name' => 'company_id',
                    'contents' => (string) $companyId,
                ],
                [
                    'name' => 'type',
                    'contents' => 'product',
                ],
            ];

            $image = $this->apiRequest('images', 'POST', ['multipart' => $multipart], false);

            if (is_array($image) && isset($image['error'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error uploading image: ' . $this->resolveApiErrorMessage($image),
                ]);
                return;
            }

            $uuid = '';
            if (is_object($image) && !empty($image->uuid)) {
                $uuid = (string) $image->uuid;
            } elseif (is_array($image) && !empty($image['uuid'])) {
                $uuid = (string) $image['uuid'];
            } elseif (is_object($image) && !empty($image->data->uuid)) {
                $uuid = (string) $image->data->uuid;
            } elseif (is_array($image) && !empty($image['data']['uuid'])) {
                $uuid = (string) $image['data']['uuid'];
            }

            if ($uuid === '') {
                log_message('error', 'AddImage missing uuid: ' . json_encode($image));
                echo json_encode([
                    'success' => false,
                    'message' => 'Error uploading image: missing uuid',
                    'debug' => $image,
                ]);
                return;
            }

            $replaceUuid = trim((string) ($this->input->post('replace_uuid') ?? ''));
            if ($replaceUuid !== '' && strcasecmp($replaceUuid, $uuid) !== 0) {
                try {
                    $this->apiDelete("product/{$productId}/image/{$replaceUuid}");
                } catch (\Throwable $e) {
                    log_message('error', 'AddImage replace delete failed: ' . $e->getMessage());
                }
            }

            $result = $this->post("product/{$productId}/image/{$uuid}", [], true);
            $ok = (is_array($result) && !empty($result['success']))
                || (is_object($result) && !empty($result->success));

            if (!$ok) {
                // Reintento: a veces el primer POST falla por carrera tras el upload.
                $result = $this->post("product/{$productId}/image/{$uuid}", [], true);
                $ok = (is_array($result) && !empty($result['success']))
                    || (is_object($result) && !empty($result->success));
            }

            if ($ok) {
                $storedName = is_object($image)
                    ? (string) ($image->name ?? $newImageName)
                    : (string) ($image['name'] ?? $newImageName);

                // default_image_id lo fija addImageToProduct; no hacer PUT product
                // (UpdateProductRequest + sync de categorías vacías borra categorías).

                // URL relativa al host actual (sin escalado _175: satura API local).
                $previewUrl = '/api/v2/images/' . rawurlencode($uuid);

                echo json_encode([
                    'success' => true,
                    'message' => 'Image uploaded successfully',
                    'image_name' => $storedName !== '' ? $storedName : $newImageName,
                    'product_id' => $productId,
                    'uuid' => $uuid,
                    'preview_url' => $previewUrl,
                    'image' => $image,
                ]);
                return;
            }

            $err = is_array($result)
                ? ($result['error'] ?? $result['message'] ?? 'Unknown error')
                : (is_object($result) ? ($result->message ?? 'Unknown error') : 'Unknown error');
            log_message('error', 'AddImage attach failed product=' . $productId . ' uuid=' . $uuid . ' resp=' . json_encode($result));
            echo json_encode([
                'success' => false,
                'message' => 'Error uploading image: ' . (is_string($err) ? $err : json_encode($err)),
                'uuid' => $uuid,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'AddImage exception: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error uploading image: ' . $e->getMessage(),
            ]);
        }
    }
}
