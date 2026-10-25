<?php

class ImportImages extends Company_Controller
{
    use IsCRUD;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('category', 'company/categorias/', 'categories');
    }

    public function index()
    {
        $data['pageTexts'] =
            $this->getModuleLang('cmp-products') +
            $this->getModuleLang('cmp-dropzone') +
            $this->getModuleLang('cm-import-images');

        if (method_exists($this, 'getLangJsonSection')) {
            $data['pageTexts'] = array_merge($this->getLangJsonSection('config-crud-tools'), $data['pageTexts']);
        }

        $data['categoryOptions'] = $this->loadCategoryOptionsForImport();

        $this->showContent('company/categorias/import_images', $data);
    }

    /**
     * @return array<int, array{cat_id: int, category_name: string}>
     */
    protected function loadCategoryOptionsForImport(): array
    {
        $compId = (int) $this->getCompanyId();
        if ($compId < 1) {
            return [];
        }

        $response = $this->getObjectFromApi('category', [
            'all' => 1,
            'props' => ['cat_id', 'category_name'],
            'company' => $compId,
            'companyId' => $compId,
            'per_page' => 5000,
            'sort' => 'category_name',
        ]);

        $rows = $this->extractApiListItems($response);
        $options = [];
        foreach ($rows as $row) {
            $item = is_object($row) ? $row : (object) $row;
            $catId = (int) ($item->cat_id ?? 0);
            $name = trim((string) ($item->category_name ?? ''));
            if ($catId > 0 && $name !== '') {
                $options[] = ['cat_id' => $catId, 'category_name' => $name];
            }
        }

        usort($options, static function (array $a, array $b): int {
            return strcasecmp($a['category_name'], $b['category_name']);
        });

        return $options;
    }

    /**
     * Sube una imagen a la API y devuelve el UUID
     * Este método es llamado por Dropzone cuando se arrastra una imagen
     */
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
            log_message('error', 'ImportImages::uploadImage - Sin company_id o token de API');
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(401);
            echo json_encode(['error' => 'unauthenticated', 'message' => 'Unauthenticated.']);
            return;
        }

        if (!isset($_FILES['image'])) {
            log_message('error', 'ImportImages::uploadImage - No se recibió $_FILES[image]');
            header('Content-Type: application/json');
            echo json_encode(['error' => 'No se recibió la imagen o hubo un error en la subida']);
            return;
        }
        if ($_FILES['image']['error'] != 0) {
            log_message('error', 'ImportImages::uploadImage - Error en subida: code=' . $_FILES['image']['error'] . ', name=' . ($_FILES['image']['name'] ?? ''));
            header('Content-Type: application/json');
            echo json_encode(['error' => 'No se recibió la imagen o hubo un error en la subida']);
            return;
        }

        $tmpName = $_FILES['image']['tmp_name'];
        $fileName = $_FILES['image']['name'];
        log_message('debug', 'ImportImages::uploadImage - Iniciando subida a API: company_id=' . $companyId . ', name=' . $fileName);

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
                'contents' => 'category'
            ]
        ];

        try {
            $image = $this->apiRequest(
                'images',
                'POST',
                ['multipart' => $multipart],
                false
            );

            if (isset($image->uuid)) {
                log_message('debug', 'ImportImages::uploadImage - Imagen subida OK: uuid=' . $image->uuid);
                header('Content-Type: application/json');
                echo json_encode($image);
            } else {
                log_message('error', 'ImportImages::uploadImage - API no devolvió uuid. Respuesta: ' . json_encode($image));
                header('Content-Type: application/json');
                echo json_encode(['error' => 'No se pudo obtener el UUID de la imagen', 'response' => $image]);
            }
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            $responseBody = '';
            if (method_exists($e, 'getResponse') && $e->getResponse() !== null) {
                $responseBody = $e->getResponse()->getBody()->getContents();
            }
            log_message('error', 'ImportImages::uploadImage - GuzzleException: ' . $e->getMessage() . (strlen($responseBody) ? ' | Body: ' . $responseBody : ''));
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            log_message('error', 'ImportImages::uploadImage - Exception: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Error desconocido: ' . $e->getMessage()]);
        }
    }

    /**
     * Asocia las imágenes subidas con sus categorías correspondientes
     * Recibe un array de [nombre_archivo, uuid_imagen] y busca la categoría por nombre
     */
    public function import()
    {
        $categoryImages = $this->input->post('category_images');
        $compId = $this->getCompanyId();
        $lang_error = $this->getModuleLang('cm-import-images');
        $responseResults = [];
        $subidos = [];
        $nosubidos = [];

        if (!$categoryImages || !is_array($categoryImages)) {
            header('Content-Type: application/json');
            echo json_encode([
                'subidos' => [],
                'nosubidos' => [(string) ($lang_error['no_images_received'] ?? '')]
            ]);
            return;
        }

        foreach ($categoryImages as $imageData) {
            if (!is_array($imageData) || count($imageData) < 2) {
                continue;
            }

            $fileName = (string) $imageData[0];
            $imageUuid = (string) $imageData[1];
            $catId = isset($imageData[2]) ? (int) $imageData[2] : 0;
            $imageResult = ['imagen' => $fileName, 'success' => false];

            if ($catId < 1) {
                $msg = sprintf(
                    (string) ($lang_error['no_category_link'] ?? '%s no está enlazada a una categoría'),
                    $fileName
                );
                $imageResult['mensaje'] = $msg;
                $nosubidos[] = $fileName;
                $responseResults[] = $imageResult;
                continue;
            }

            try {
                $categoryRaw = $this->getObjectFromApi("category/{$catId}");
                if ($this->hasError($categoryRaw)) {
                    $msg = sprintf(
                        (string) ($lang_error['sin_nombre'] ?? 'No se encontró la categoría para %s'),
                        $fileName
                    );
                    $imageResult['mensaje'] = $msg;
                    $nosubidos[] = $fileName;
                    $responseResults[] = $imageResult;
                    continue;
                }

                $category = is_object($categoryRaw) && isset($categoryRaw->data)
                    ? (is_object($categoryRaw->data) ? $categoryRaw->data : (object) $categoryRaw->data)
                    : (is_object($categoryRaw) ? $categoryRaw : (object) $categoryRaw);
                if (is_array($category)) {
                    $category = (object) $category;
                }

                $categoryCompanyId = (int) ($category->company_id ?? 0);
                if ($categoryCompanyId > 0 && $categoryCompanyId !== (int) $compId) {
                    $msg = sprintf(
                        (string) ($lang_error['upload_error'] ?? '%s error al subir: %s'),
                        $fileName,
                        'invalid category'
                    );
                    $imageResult['mensaje'] = $msg;
                    $nosubidos[] = $fileName;
                    $responseResults[] = $imageResult;
                    continue;
                }

                $categoryNameVal = (string) ($category->category_name ?? '');
                $categoryStatusVal = isset($category->category_status) ? (int) $category->category_status : 1;

                $updateResult = $this->put("category/{$catId}", [
                    'image' => $imageUuid,
                    'company_id' => $compId,
                    'category_name' => $categoryNameVal,
                    'category_status' => $categoryStatusVal,
                ]);

                $updateOk = false;
                if (is_object($updateResult) && isset($updateResult->cat_id)) {
                    $updateOk = true;
                } elseif (is_object($updateResult) && isset($updateResult->data)) {
                    $updateOk = true;
                } elseif (is_array($updateResult) && ((isset($updateResult['success']) && $updateResult['success']) || isset($updateResult['cat_id']) || isset($updateResult['data']['cat_id']))) {
                    $updateOk = true;
                }

                if ($updateOk) {
                    $msg = sprintf((string) ($lang_error['upload_success'] ?? '%s fue subido exitosamente'), $fileName);
                    $imageResult['mensaje'] = $msg;
                    $imageResult['success'] = true;
                    $subidos[] = $fileName;
                } else {
                    $rawMsg = is_array($updateResult) ? ($updateResult['message'] ?? 'Error desconocido') : 'Error desconocido';
                    $errorMsg = is_array($rawMsg) ? json_encode($rawMsg) : (string) $rawMsg;
                    $msg = sprintf((string) ($lang_error['upload_error'] ?? '%s error al subir: %s'), $fileName, $errorMsg);
                    $imageResult['mensaje'] = $msg;
                    $nosubidos[] = $fileName;
                    log_message('error', 'Error al actualizar categoría con imagen: ' . json_encode($updateResult));
                }
            } catch (\Exception $e) {
                $msg = sprintf((string) ($lang_error['upload_error'] ?? '%s error al subir: %s'), $fileName, $e->getMessage());
                $imageResult['mensaje'] = $msg;
                $nosubidos[] = $fileName;
                log_message('error', 'Error al importar imagen de categoría: ' . $e->getMessage());
            }

            $responseResults[] = $imageResult;
        }

        header('Content-Type: application/json');
        echo json_encode([
            'subidos' => $subidos,
            'nosubidos' => $nosubidos,
            'results' => $responseResults
        ]);
    }

    /**
     * Extrae el nombre de la categoría del nombre del archivo
     * Formato esperado: "nombre_categoria.jpg" o "nombre_categoria.png"
     */
    protected function getCategoryNameFromFilename($fileName)
    {
        $extensiones = array('.png', '.PNG', '.jpg', '.JPG', '.jpeg', '.JPEG');
        $categoryName = $fileName;

        // Remover la extensión
        foreach ($extensiones as $ext) {
            if (strpos($categoryName, $ext) !== false) {
                $categoryName = str_replace($ext, '', $categoryName);
                break;
            }
        }

        // Nombre completo; guiones bajos como espacios (ej: "Bebidas_alcoholicas" -> "Bebidas alcoholicas")
        $categoryName = trim(str_replace('_', ' ', $categoryName));
        return $categoryName === '' ? null : $categoryName;
    }

    /**
     * Busca categoría por nombre; prueba variantes " & " <-> " and " si no hay resultado.
     */
    protected function findCategoryByName($compId, $categoryName)
    {
        $namesToTry = [$categoryName];
        if (strpos($categoryName, ' & ') !== false) {
            $namesToTry[] = str_replace(' & ', ' and ', $categoryName);
        }
        if (strpos($categoryName, ' and ') !== false) {
            $namesToTry[] = str_replace(' and ', ' & ', $categoryName);
        }
        foreach ($namesToTry as $name) {
            $categoryRaw = $this->getObjectFromApi('category', [
                'company' => $compId,
                'category_name' => $name,
                'all' => 'true'
            ]);
            if (!$categoryRaw) {
                continue;
            }
            $data = is_object($categoryRaw) && isset($categoryRaw->data) ? $categoryRaw->data : (is_array($categoryRaw) && isset($categoryRaw['data']) ? $categoryRaw['data'] : null);
            if (is_array($data) && count($data) > 0) {
                return $categoryRaw;
            }
            $hasCatId = (is_object($categoryRaw) && isset($categoryRaw->cat_id)) || (is_array($categoryRaw) && isset($categoryRaw['cat_id']));
            if ($hasCatId) {
                return $categoryRaw;
            }
        }
        return null;
    }
}
