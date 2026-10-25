<?php

/**
 * Images Controller
 *
 * This controller handles product image operations including importing,
 * deleting, ordering, and setting default images.
 */
class Images extends Company_Controller
{
    use IsCRUD;

    /**
     * Allowed image extensions
     *
     * @var array
     */
    private $allowedImageExtensions = [
        '.jpg', '.png', '.jpeg', '.jfif', '.jpe', '.gif', '.webp', '.bmp', '.avif', '.tif', '.tiff', '.heic', '.heif', '.svg', '.ico',
        '.JPG', '.PNG', '.JPEG', '.JFIF', '.JPE', '.GIF', '.WEBP', '.BMP', '.AVIF', '.TIF', '.TIFF', '.HEIC', '.HEIF', '.SVG', '.ICO',
    ];

    private function allowedImageExtensionPattern(): string
    {
        return 'png|jpg|jpeg|jfif|jpe|gif|webp|bmp|avif|tif|tiff|heic|heif|svg|ico';
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->initCrud('product', 'company/product/', 'products');
    }

    /**
     * Index method
     *
     * Redirects to products page
     */
    public function index()
    {
        redirect(company_url('products'));
    }

    /**
     * Find images by original SKU
     *
     * @param int $compId Company ID
     * @param int $productId Product ID
     * @param string $image Image name
     * @param bool $originalSku Whether to use original SKU
     * @return array Matching images
     */
    public function findImagesByOriginalSku($compId, $productId, $image, $originalSku = false)
    {
        $imageNameToProcess = $image;
        // Remove extensions from the string

        $extensionArray = $this->allowedImageExtensions;
        $skuLike = '';

        foreach ($extensionArray as $ext) {
            $skuLike = str_replace($extensionArray, '', $imageNameToProcess);
        }

        $ext = $this->allowedImageExtensionPattern();
        $format1 = '^' . $skuLike . '_[^_]*\.(' . $ext . ')$';
        $format2 = '^' . $skuLike . '\.(' . $ext . ')$';

        // Use the API to get images that match the format
        $images = $this->getObjectFromApi("product/{$productId}/images");
        $matchingImages = [];

        if ($images) {
            foreach ($images as $image) {
                if (preg_match("/$format1/i", $image->images) || preg_match("/$format2/i", $image->images)) {
                    $matchingImages[] = $image;
                }
            }
        }

        return $matchingImages;
    }

    /**
     * Find images by SKU pattern
     *
     * @param int $compId Company ID
     * @param int $productId Product ID
     * @param string $image Image name
     * @param bool $originalSku Whether to use original SKU
     * @return array Matching images
     */
    public function findImagesBySku($compId, $productId, $image, $originalSku = false)
    {
        $imageNameToProcess = $image;
        // Remove extensions from the string

        $skuLike = '';
        foreach ($this->allowedImageExtensions as $ext) {
            $skuLike = str_replace($this->allowedImageExtensions, '', $imageNameToProcess);
        }

        $ext = $this->allowedImageExtensionPattern();
        $format = '^' . $skuLike . '[_a-zA-Z0-9]*\.(' . $ext . ')$';

        // Use the API to get images that match the format
        $images = $this->getObjectFromApi("product/{$productId}/images");
        $matchingImages = [];

        if ($images) {
            foreach ($images as $image) {
                if (preg_match("/$format/i", $image->images)) {
                    $matchingImages[] = $image;
                }
            }
        }

        return $matchingImages;
    }

    /**
     * Delete a product image
     *
     * Handles the deletion of a product image and updates the default image if needed
     */
    public function deleteProductImage()
    {
        $imageId = $this->input->post('imageId');
        $productId = $this->input->post('productId');

        $languageDefinitions = $this->getModuleLang('company-csvf');
        $response = [];
        $errorMessage = $languageDefinitions['imagen_error'];

        try {
            // Use the API to delete the image
            $this->apiDelete("product/{$productId}/image/{$imageId}");
            $successMessage = $languageDefinitions['imagen_eliminado'];
            $response = [$successMessage , "success", "#A5DC86"];
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            $response = [ $errorMessage . " " . $e->getMessage() , "error", "#DD6B55"];
        }
        echo json_encode($response);
    }

    /**
     * Get images modal content for a product
     *
     * @param int $productId Product ID
     */
    public function getProductImagesModal($productId)
    {
        $product = $this->getObjectFromApi("product/{$productId}");
        if (!$product || (is_array($product) && isset($product['error']))) {
            set_status_header(404);
            echo 'Product not found';
            return;
        }

        $imagesHash = $this->input->get('images_hash');

        // Fuente de verdad: product_images (image_id) + imagen anidada.
        $productImagesOrdered = [];
        try {
            $productImagesOrdered = $this->getObjectFromApi("product/{$productId}/images");
        } catch (\Exception $e) {
            log_message('error', 'getProductImagesModal - Error obteniendo ProductImages: ' . $e->getMessage());
        }

        $sortedImages = [];
        if (!empty($productImagesOrdered)) {
            if (is_object($productImagesOrdered) && !($productImagesOrdered instanceof \Traversable)) {
                $productImagesOrdered = (array) $productImagesOrdered;
            }
            if (!is_array($productImagesOrdered)) {
                $productImagesOrdered = [];
            }

            $rows = [];
            foreach ($productImagesOrdered as $pi) {
                $piObj = is_object($pi) ? $pi : (object) $pi;
                $rows[] = $piObj;
            }

            usort($rows, static function ($a, $b) {
                $oa = (int) ($a->img_order ?? 999999);
                $ob = (int) ($b->img_order ?? 999999);
                if ($oa === $ob) {
                    return ((int) ($a->img_id ?? 0)) <=> ((int) ($b->img_id ?? 0));
                }
                return $oa <=> $ob;
            });

            foreach ($rows as $piObj) {
                if (count($sortedImages) >= 5) {
                    break;
                }

                $nested = $piObj->image ?? null;
                if (is_array($nested)) {
                    $nested = (object) $nested;
                }

                $uuid = '';
                if (is_object($nested) && !empty($nested->uuid)) {
                    $uuid = (string) $nested->uuid;
                } elseif (!empty($piObj->image_id)) {
                    $uuid = (string) $piObj->image_id;
                } elseif (!empty($piObj->uuid)) {
                    $uuid = (string) $piObj->uuid;
                }

                $uuid = trim($uuid);
                if ($uuid === '') {
                    continue;
                }

                $slot = is_object($nested) ? clone $nested : new \stdClass();
                $slot->uuid = $uuid;
                if (empty($slot->name)) {
                    $slot->name = (string) ($piObj->images ?? $uuid);
                }
                $sortedImages[] = $slot;
            }
        }

        while (count($sortedImages) < 5) {
            $sortedImages[] = null;
        }

        $product->images = $sortedImages;
        $product->images_hash = md5(json_encode($product->images));

        header("X-Images-Hash: $product->images_hash");
        if ($imagesHash && $imagesHash == $product->images_hash) {
            set_status_header(204);
            exit;
        }

        $data = [
            'product' => $product,
            'pageTexts' =>
                $this->getModuleLang('cmp-products')    +
                $this->getModuleLang('comp-login-head') +
                $this->getModuleLang('comp-csv-flds')   +
                $this->getModuleLang('semaphore')       +
                $this->getModuleLang('company-csvf')
        ];
        $this->load->view('company/product/modals/images', $data);
    }

    /**
     * Get default product image and display view
     *
     * @param int $productId Product ID
     */
    public function getDefaultProductImage($productId)
    {
        $companyId = $this->getCompany();;

        // Get all images for the product using the API
        $productImages = $this->getObjectFromApi("product/{$productId}/images");

        // Get the default image (first image or image with img_default = 1)
        $defaultImage = null;
        if (!empty($productImages)) {
            // First try to find an image with img_default = 1
            foreach ($productImages as $image) {
                if (isset($image->img_default) && $image->img_default == 1) {
                    $defaultImage = $image;
                    break;
                }
            }

            // If no default image found, use the first image
            if (!$defaultImage && count($productImages) > 0) {
                $defaultImage = $productImages[0];
            }
        }

        $data['imagen_defecto'] = $defaultImage;
        $data['productImagesList'] = $productImages;

        $this->load->view('company/product_imagen_defecto', $data);
    }

    /**
     * Import an image for a product
     *
     * @param string $imageName Image name
     * @param string $imageFileName Full path to image file
     * @param int $productId Product ID
     * @return array Result of the import operation
     */
    protected function importImage($imageName, $imageFileName, $productId)
    {
        // Use the API to upload the image
        $result = $this->post(
            "product/{$productId}/image",
            [
                'name' => $imageName,
                'contents' => new \CURLFile($imageFileName)
            ]
        );

        return $result;
    }

    /**
     * Get SKU from filename
     *
     * @param string $fileName Filename to extract SKU from
     * @return string Extracted SKU
     */
    protected function getSkuFromFilename($fileName)
    {
        $sku = explode("_", $fileName);
        if (count($sku) >= 2) {
            $sku = $sku[0];
        } else {
            $sku = str_replace($this->allowedImageExtensions, '', $sku[0]);
        }
        return $sku;
    }


    /**
     * Update default image for a product
     *
     * @param int $companyId Company ID
     * @param int $productId Product ID
     */
    public function updateDefaultImage($companyId, $productId)
    {
        // Get all images for the product using the API
        $images = $this->getObjectFromApi("product/{$productId}/images");

        if (!$images) {
            return;
        }

        // Reset all default flags
        foreach ($images as $image) {
            $this->put("productimage/{$image->img_id}", ['img_default' => null]);
        }

        // Set the first image as default
        foreach ($images as $image) {
            if ($image->img_order == 1) {
                $this->put("productimage/{$image->img_id}", ['img_default' => 1]);
                break;
            }
        }
    }

    /**
     * Get default product image for AJAX update
     *
     * @param int $productId Product ID
     */
    public function obtener_producto_default($productId)
    {
        $companyId = $this->getCompany();;
        $company = $this->getCompany();

        // Get product information
        $product = $this->getObjectFromApi("product/{$productId}", ['relations' => ['images']]);

        if (!$product) {
            echo json_encode(['error' => 'Product not found']);
            return;
        }

        $data['product'] = $product;
        $data['company'] = $company;
        $data['pageTexts'] = $this->getModuleLang('cmp-products');

        $this->load->view('company/product/product_imagen_defecto', $data);
    }

    /**
     * Order product images
     *
     * Handles the reordering of product images based on user input
     */
    public function orderImages()
    {
        $productId = $this->input->post('id');
        $requestedOrder = $this->input->post('orden');

        if ($productId === null || $productId === '') {
            $payload = json_decode((string) $this->input->raw_input_stream, true);
            if (is_array($payload)) {
                $productId = $payload['id'] ?? $productId;
                $requestedOrder = $payload['orden'] ?? $requestedOrder;
            }
        }

        if (is_string($requestedOrder) && $requestedOrder !== '') {
            $requestedOrder = [$requestedOrder];
        }

        if (!$productId || empty($requestedOrder) || !is_array($requestedOrder)) {
            log_message('error', 'orderImages missing params id=' . var_export($productId, true) . ' orden=' . var_export($requestedOrder, true));
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
            return;
        }

        $productId = (int) $productId;

        $requestedOrder = array_values(array_filter($requestedOrder, static function ($uuid) {
            return is_string($uuid) && trim($uuid) !== '';
        }));

        if ($requestedOrder === []) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'No images to sort']);
            return;
        }

        log_message('debug', 'orderImages product=' . $productId . ' orden=' . json_encode($requestedOrder));

        $messages = $this->getModuleLang('cm-mensajes');
        $response = [
            'message' => $messages['orden_cambiado'] ?? '',
            'success' => false,
        ];

        $apiResponse = $this->put("product/{$productId}/images/resort", [
            'images' => $requestedOrder,
        ]);

        log_message('debug', 'orderImages API response=' . json_encode($apiResponse));

        if (is_array($apiResponse) && isset($apiResponse['error'])) {
            $messagePayload = $apiResponse['message'] ?? null;
            if (is_array($messagePayload)) {
                $response['message'] = $messagePayload['message'] ?? json_encode($messagePayload);
            } else {
                $response['message'] = (string) ($messagePayload ?? 'Error');
            }

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($response);
            return;
        }

        if (is_object($apiResponse) && isset($apiResponse->success)) {
            $response['success'] = (bool) $apiResponse->success;
            if (isset($apiResponse->data->amountResorted)) {
                $response['amountResorted'] = (int) $apiResponse->data->amountResorted;
            }
            if (!$response['success'] && isset($apiResponse->message)) {
                $response['message'] = (string) $apiResponse->message;
            }
        } elseif (is_array($apiResponse) && isset($apiResponse['success'])) {
            $response['success'] = (bool) $apiResponse['success'];
            if (isset($apiResponse['data']['amountResorted'])) {
                $response['amountResorted'] = (int) $apiResponse['data']['amountResorted'];
            }
            if (!$response['success'] && isset($apiResponse['message'])) {
                $response['message'] = (string) $apiResponse['message'];
            }
        } else {
            $response['success'] = true;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
    }

    /**
     * Alias legacy para reordenar imágenes desde JS antiguo.
     */
    public function ordenar_imagenes()
    {
        $productId = $this->input->post('id');
        $requestedOrder = $this->input->post('orden_solicitado') ?: $this->input->post('orden');
        $_POST['id'] = $productId;
        $_POST['orden'] = $requestedOrder;
        $this->orderImages();
    }

    public function uploadImage()
    {
        $postedToken = method_exists($this, 'resolveImageUploadApiToken')
            ? $this->resolveImageUploadApiToken()
            : trim((string) ($this->input->post('fv_upload_token') ?? ''));
        if ($postedToken !== '') {
            $this->userToken = $postedToken;
        } elseif (method_exists($this, 'getUserToken')) {
            $this->getUserToken();
        }

        $companyId = method_exists($this, 'resolveImageUploadCompanyId')
            ? (int) $this->resolveImageUploadCompanyId()
            : (int) ($this->input->post('fv_company_id') ?? 0);
        if ($companyId < 1) {
            $companyId = (int) $this->getCompanyId();
        }

        if (method_exists($this, 'releaseSessionLockIfActive')) {
            $this->releaseSessionLockIfActive();
        } elseif (function_exists('session_write_close') && session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        if ($companyId < 1 || empty($this->userToken)) {
            log_message('error', 'Images::uploadImage - Sin company_id o token de API (company_id=' . $companyId . ', token=' . (empty($this->userToken) ? 'vacío' : 'presente') . ')');
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(401);
            echo json_encode(['error' => 'unauthenticated', 'message' => 'Unauthenticated.']);
            return;
        }

        if (!isset($_FILES['image'])) {
            log_message('error', 'Images::uploadImage - No se recibió $_FILES[image]');
            header('Content-Type: application/json');
            echo json_encode(['error' => 'no_file_received']);
            return;
        }
        if ((int) ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            log_message('error', 'Images::uploadImage - Error en subida: code=' . ($_FILES['image']['error'] ?? '') . ', name=' . ($_FILES['image']['name'] ?? ''));
            header('Content-Type: application/json');
            echo json_encode(['error' => 'upload_error', 'code' => (int) ($_FILES['image']['error'] ?? 0)]);
            return;
        }

        $tmpName = $_FILES['image']['tmp_name'];
        $fileName = $_FILES['image']['name'];
        log_message('debug', 'Images::uploadImage - Iniciando subida a API: company_id=' . $companyId . ', name=' . $fileName);

        $multipart = [
            [
                'name' => 'contents',
                'contents' => fopen($tmpName, 'r'),
                'filename' => $fileName
            ],
            [
                'name' => 'name',
                'contents' => $fileName
            ],
            [
                'name' => 'company_id',
                'contents' => $companyId
            ],
            [
                'name' => 'type',
                'contents' => 'product'
            ]
        ];

        try {
            $image = $this->apiRequest(
                'images',
                'POST',
                ['multipart' => $multipart],
                false
            );

            $uuid = is_object($image) ? ($image->uuid ?? null) : (is_array($image) ? ($image['uuid'] ?? null) : null);
            if ($uuid) {
                log_message('debug', 'Images::uploadImage - Imagen subida OK: uuid=' . $uuid);
                header('Content-Type: application/json');
                echo json_encode($image);
                return;
            }

            log_message('error', 'Images::uploadImage - API no devolvió uuid. Respuesta: ' . json_encode($image));
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'missing_uuid',
                'response' => $image,
            ]);
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            $responseBody = '';
            if (method_exists($e, 'getResponse') && $e->getResponse() !== null) {
                $responseBody = $e->getResponse()->getBody()->getContents();
            }
            log_message('error', 'Images::uploadImage - GuzzleException: ' . $e->getMessage() . (strlen($responseBody) ? ' | Body: ' . $responseBody : ''));
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage(), 'body' => $responseBody]);
        } catch (\Exception $e) {
            log_message('error', 'Images::uploadImage - Exception: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
