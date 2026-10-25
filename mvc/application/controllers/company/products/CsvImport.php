<?php

require_once APPPATH . 'core/ConfigCrudImportSchemas.php';

class CsvImport extends Company_Controller
{

    use IsCRUD;
    use ConfigCrudListTools;

    /** @var array<string, int>|null */
    private $productImportCategoryMap = null;

    /** @var array<string, int>|null */
    private $productImportUnitMap = null;

    /** @var object|null */
    private $productImportDefaultUnit = null;

    private $productImportDefaultUnitLoaded = false;

    /** @var string */
    private $productImportLastError = '';

    public function pullProductImportLastError(): string
    {
        $error = $this->productImportLastError;
        $this->productImportLastError = '';

        return $error;
    }

    private function setProductImportLastError(string $message): void
    {
        $this->productImportLastError = trim($message);
    }

    private function productImportLang(string $key, string $fallback = ''): string
    {
        $text = trim((string) ($this->langArr[$key] ?? ''));

        return $text !== '' ? $text : $fallback;
    }

    /**
     * Garantiza token Sanctum en sesión antes de llamar a la API (preview profunda e importación).
     */
    public function ensureProductImportApiSession(): bool
    {
        return $this->ensureConfigImportApiSession();
    }

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('product', 'company/product/', 'products');
        $this->langArr = $this->getModuleLang('cmp-products');
    }

    public function index()
    {
        $schema = ConfigCrudImportSchemas::products();
        if (method_exists($this, 'getLangJsonSection')) {
            $this->langArr = array_merge(
                is_array($this->langArr ?? null) ? $this->langArr : [],
                $this->getLangJsonSection('config-crud-tools'),
                $this->getLangJsonSection('cmp-products')
            );
        }
        $this->showConfigCsvImportView('products', company_url('products'), 'csvImport');
    }

    /**
     * @param array<int, string> $cells
     * @return array{valid: bool, errors: array<int, string>, error_indexes?: array<int, int>}
     */
    public function validateProductCsvRow(array $cells): array
    {
        $data = $this->sanitizeProductCsvCells($cells);
        $errors = [];

        if (trim($data[0]) === '') {
            $errors[] = 'import_col_product_name';
        }
        if (trim($data[1]) === '') {
            $errors[] = 'import_col_category_list';
        }
        if (trim($data[2]) === '') {
            $errors[] = 'import_col_sku';
        }
        if (!is_numeric($data[5])) {
            $errors[] = 'import_col_sale_price';
        }
        if (!is_numeric($data[6])) {
            $errors[] = 'import_col_fob_price';
        }
        if (!is_numeric($data[7])) {
            $errors[] = 'import_col_purchase_price';
        }
        if ($data[14] !== '0' && $data[14] !== '1') {
            $errors[] = 'import_col_status';
        }

        if (!empty($errors)) {
            return ['valid' => false, 'errors' => $errors];
        }

        $apiCheck = $this->validateProductCsvRowApi($data);

        return [
            'valid' => empty($apiCheck['errors']),
            'errors' => $apiCheck['errors'],
            'error_indexes' => $apiCheck['error_indexes'],
        ];
    }

    /**
     * Valida contra la API que categoría y unidad se pueden resolver (misma lógica que la importación).
     *
     * @param array<int, string> $data
     * @return array{errors: array<int, string>, error_indexes: array<int, int>}
     */
    private function validateProductCsvRowApi(array $data): array
    {
        $errors = [];
        $errorIndexes = [];

        if (!$this->ensureProductImportApiSession()) {
            return [
                'errors' => ['import_row_error_api_auth'],
                'error_indexes' => [0, 1, 2, 4],
            ];
        }

        $compId = (int) $this->getCompanyId();
        $categoryMap = &$this->getProductImportCategoryMapRef();
        $unitMap = &$this->getProductImportUnitMapRef();

        $cateIds = $this->resolveCategoryIds(trim($data[1]), $categoryMap, $compId);
        if (empty($cateIds)) {
            $errors[] = 'import_row_error_category';
            $errorIndexes[] = 1;
        }

        $unitId = $this->resolveProductImportUnitId(trim($data[4]), $unitMap, $compId);
        if ($unitId === null) {
            $errors[] = 'import_row_error_unit';
            $errorIndexes[] = 4;
        }

        return [
            'errors' => $errors,
            'error_indexes' => array_values(array_unique($errorIndexes)),
        ];
    }

    /**
     * @param array<int, string> $cells
     */
    public function importProductCsvRow(array $cells): bool
    {
        if (!$this->ensureProductImportApiSession()) {
            $this->setProductImportLastError($this->productImportLang(
                'import_row_error_api_auth',
                'import_row_error_api_auth'
            ));

            return false;
        }

        $row = $this->sanitizeProductCsvCells($cells);
        $compId = $this->getCompanyId();
        $compname = (string) $this->getUserData('name');

        $kname = trim($row[0]);
        $rawcatnames = trim($row[1]);
        $ksku = trim($row[2]);
        $kbarcode = trim($row[3]);
        $ksaleprice = $row[5];
        $kfobprice = $row[6];
        $kpurchaseprice = $row[7];
        $kstock = $row[8] === '' ? 0 : $row[8];
        $kminimumstock = $row[9] === '' ? 0 : $row[9];
        $knotifymoq = $row[10] === '' ? 0 : $row[10];
        $ktags = $row[11];
        $klocations = $row[12];
        $kdescriptions = $row[13];
        $kstatus = $row[14];

        if ($kname === '' || $ksku === '' || $rawcatnames === '') {
            $this->setProductImportLastError($this->productImportLang(
                'import_row_error_required',
                'import_row_error_required'
            ));

            return false;
        }

        $categoryMap = &$this->getProductImportCategoryMapRef();
        $unitMap = &$this->getProductImportUnitMapRef();
        $cateIds = $this->resolveCategoryIds($rawcatnames, $categoryMap, $compId);
        if (empty($cateIds)) {
            $this->setProductImportLastError($this->productImportLang(
                'import_row_error_category',
                'import_row_error_category'
            ));

            return false;
        }

        $unitId = $this->resolveProductImportUnitId(trim($row[4]), $unitMap, $compId);
        if ($unitId === null) {
            $this->setProductImportLastError($this->productImportLang(
                'import_row_error_unit',
                'import_row_error_unit'
            ));

            return false;
        }

        $payload = [
            'company_id' => $compId,
            'name' => $kname,
            'sku' => $ksku,
            'barcode' => $kbarcode,
            'sale_price' => is_numeric($ksaleprice) ? $ksaleprice + 0 : 0,
            'fob_price' => is_numeric($kfobprice) ? $kfobprice + 0 : 0,
            'purchase_price' => is_numeric($kpurchaseprice) ? $kpurchaseprice + 0 : 0,
            'price_reduced' => 0,
            'stock' => (int) (is_numeric($kstock) ? $kstock : 0),
            'minimum_stock' => is_numeric($kminimumstock) ? $kminimumstock + 0 : 0,
            'notify_minimum_stock' => (int) (is_numeric($knotifymoq) ? $knotifymoq : 0),
            'status' => (int) $kstatus,
            'tags' => $ktags,
            'descriptions' => $kdescriptions,
            'locations' => $klocations,
            // IDs de categoría (no nombres): array para pivot + CSV para columnas denormalizadas
            'category_id' => $cateIds,
            'cat_id' => implode(',', $cateIds),
            'cats_id' => implode(',', $cateIds),
        ];
        if ($unitId !== null) {
            $payload['unit_id'] = $unitId;
        }

        $existingProId = $this->findProductIdBySku($ksku, $compId);
        if ($existingProId !== null) {
            $response = $this->put("product/{$existingProId}", $payload);
        } else {
            $payload['created_by'] = $compname;
            $response = $this->post('product', $payload);
        }

        if ($this->hasError($response)) {
            $apiError = $this->extractApiErrorMessage($response);
            $this->setProductImportLastError($apiError !== '' ? $apiError : $this->productImportLang(
                'import_row_error_generic',
                'import_row_error_generic'
            ));
            log_message('error', 'CsvImport::importProductCsvRow sku=' . $ksku . ' error: ' . $apiError);

            return false;
        }

        return true;
    }

    /**
     * @param array<int, string> $cells
     * @return array<int, string>
     */
    private function sanitizeProductCsvCells(array $cells): array
    {
        $data = array_values($cells);
        while (count($data) < 15) {
            $data[] = '';
        }
        $data = array_slice($data, 0, 15);

        $data[0] = str_replace([',', ';'], ' ', (string) $data[0]);
        $data[1] = trim(str_replace(';', ' ', (string) $data[1]), " ;,");
        $data[2] = str_replace([',', ';'], ' ', (string) $data[2]);
        $data[3] = str_replace([',', ';'], ' ', (string) $data[3]);
        $data[11] = str_replace([',', ';'], ' ', (string) $data[11]);
        $data[12] = str_replace([',', ';'], ' ', (string) $data[12]);
        $data[13] = str_replace([',', ';'], ' ', (string) $data[13]);

        if (!is_numeric($data[8]) || $data[8] === '') {
            $data[8] = '0';
        }
        if (!is_numeric($data[9]) || $data[9] === '') {
            $data[9] = '0';
        }
        if (!is_numeric($data[10]) || $data[10] === '') {
            $data[10] = '0';
        }

        return array_map(static function ($value) {
            return is_string($value) ? $value : (string) $value;
        }, $data);
    }

    /**
     * @return object|null
     */
    private function getProductImportDefaultUnit()
    {
        if (!$this->productImportDefaultUnitLoaded) {
            $this->productImportDefaultUnitLoaded = true;
            $this->productImportDefaultUnit = $this->getDefaultUnit($this->getCompanyId());
        }

        return $this->productImportDefaultUnit;
    }

    /**
     * @return array<string, int>
     */
    private function &getProductImportCategoryMapRef(): array
    {
        if ($this->productImportCategoryMap === null) {
            $this->productImportCategoryMap = $this->buildCategoryMap($this->getCompanyId());
        }

        return $this->productImportCategoryMap;
    }

    /**
     * @return array<string, int>
     */
    private function &getProductImportUnitMapRef(): array
    {
        if ($this->productImportUnitMap === null) {
            $this->productImportUnitMap = $this->buildUnitMap($this->getCompanyId());
        }

        return $this->productImportUnitMap;
    }

    public function success()
    {
        $compId = (int) $this->getCompany();
        $variable = $this->input->post('rawdata');
        if (empty($variable)) {
            echo json_encode(['success' => false, 'msg' => 'No se recibieron datos para importar.', 'url' => company_url('products/csvImport')]);
            return;
        }
        $datos = json_decode($variable, true);
        if (!is_array($datos) || count($datos) === 0) {
            echo json_encode(['success' => false, 'msg' => 'Datos de importación no válidos.', 'url' => company_url('products/csvImport')]);
            return;
        }
        $rowc = 0;
        $total = 0;
        $errors = [];
        $compname = $this->getUserData('name');

        // Mapas de referencia obtenidos una sola vez vía API (nombre en minúsculas => id)
        $categoryMap = $this->buildCategoryMap($compId);
        $unitMap = $this->buildUnitMap($compId);

        for ($i = 0; $i < count($datos); $i++) {
            $row = $datos[$i];
            $total++;
            if (!is_array($row)) {
                $errors[] = ['row' => $i + 1, 'sku' => '', 'error' => $this->langArr['error_estructura'] ?? 'Fila con formato inválido'];
                log_message('error', 'CsvImport::success fila ' . ($i + 1) . ' descartada: no es un array');
                continue;
            }
            $kname = isset($row[0]) ? trim((string) $row[0]) : '';
            $ksku = isset($row[2]) ? trim((string) $row[2]) : '';
            $rawcatnames = isset($row[1]) ? (string) $row[1] : '';
            $kbarcode = isset($row[3]) ? trim((string) $row[3]) : '';
            // Columna 4 puede ser string (unidad) o array/object con 'unit' (unidad por defecto en la grilla)
            $kunittype = isset($row[4]) ? $row[4] : '';
            if (is_array($kunittype) && isset($kunittype['unit'])) {
                $kunittype = $kunittype['unit'];
            } elseif (is_object($kunittype) && isset($kunittype->unit)) {
                $kunittype = $kunittype->unit;
            }
            $kunittype = is_string($kunittype) ? trim($kunittype) : '';
            $ksaleprice = isset($row[5]) ? $row[5] : 0;
            $kfobprice = isset($row[6]) ? $row[6] : 0;
            $kpurchaseprice = isset($row[7]) ? $row[7] : 0;
            $kstock = isset($row[8]) ? $row[8] : 0;
            $kminimumstock = isset($row[9]) ? $row[9] : 0;
            $knotifymoq = isset($row[10]) ? $row[10] : 0;
            $ktags = isset($row[11]) ? $row[11] : '';
            $klocations = isset($row[12]) ? $row[12] : '';
            $kdescriptions = isset($row[13]) ? $row[13] : '';
            $kstatus = isset($row[14]) ? $row[14] : 1;

            // Validación previa de campos obligatorios (evita 500/422 silenciosos en la API)
            if ($kname === '') {
                $errors[] = ['row' => $i + 1, 'sku' => $ksku, 'error' => $this->langArr['error_name'] ?? 'Falta el nombre'];
                continue;
            }
            if ($ksku === '') {
                $errors[] = ['row' => $i + 1, 'sku' => $ksku, 'error' => $this->langArr['error_sku'] ?? 'Falta el SKU'];
                continue;
            }

            // Resolver categorías por nombre (creando las que no existan) -> array de cat_id
            $cateIds = $this->resolveCategoryIds($rawcatnames, $categoryMap, $compId);
            if (empty($cateIds)) {
                $errors[] = ['row' => $i + 1, 'sku' => $ksku, 'error' => $this->langArr['error_categ_list'] ?? 'Categoría inválida'];
                continue;
            }

            // Resolver unidad: vacío → por defecto; si no existe → se crea
            $unitId = $this->resolveProductImportUnitId($kunittype, $unitMap, $compId);

            $payload = [
                'company_id' => $compId,
                'name' => $kname,
                'sku' => $ksku,
                'barcode' => $kbarcode,
                'sale_price' => is_numeric($ksaleprice) ? $ksaleprice + 0 : 0,
                'fob_price' => is_numeric($kfobprice) ? $kfobprice + 0 : 0,
                'purchase_price' => is_numeric($kpurchaseprice) ? $kpurchaseprice + 0 : 0,
                'price_reduced' => 0,
                'stock' => (int) (is_numeric($kstock) ? $kstock : 0),
                'minimum_stock' => is_numeric($kminimumstock) ? $kminimumstock + 0 : 0,
                'notify_minimum_stock' => (int) (is_numeric($knotifymoq) ? $knotifymoq : 0),
                'status' => (int) $kstatus,
                'tags' => $ktags,
                'descriptions' => $kdescriptions,
                'locations' => $klocations,
                // IDs de categoría (no nombres)
                'category_id' => $cateIds,
                'cat_id' => implode(',', $cateIds),
                'cats_id' => implode(',', $cateIds),
            ];
            if ($unitId !== null) {
                $payload['unit_id'] = $unitId;
            }

            $existingProId = $this->findProductIdBySku($ksku, $compId);
            if ($existingProId !== null) {
                $response = $this->put("product/{$existingProId}", $payload);
            } else {
                $payload['created_by'] = $compname;
                $response = $this->post('product', $payload);
            }

            if ($this->hasError($response)) {
                $errorMsg = $this->extractApiErrorMessage($response);
                $errors[] = ['row' => $i + 1, 'sku' => $ksku, 'error' => $errorMsg];
                log_message('error', 'CsvImport::success fila ' . ($i + 1) . ' (sku=' . $ksku . ') falló: ' . $errorMsg);
                continue;
            }
            $rowc++;
        }

        // Registrar historial de importación vía API
        if ($rowc > 0) {
            $this->post('importhistory', [
                'company_id' => $compId,
                'import_date' => date('Y-m-d H:i:s'),
                'importedfile' => 'product',
                'records_imported' => $rowc,
            ]);
        }

        echo json_encode([
            'success' => true,
            'url' => company_url('products'),
            'total' => $total,
            'loaded' => $rowc,
            'notLoaded' => count($errors),
            'errors' => $errors,
        ]);
    }

    /**
     * Construye el mapa de categorías de la empresa (nombre en minúsculas => cat_id) vía API.
     *
     * @param int $compId
     * @return array<string,int>
     */
    private function buildCategoryMap($compId)
    {
        $map = [];
        $response = $this->getObjectFromApi('category', [
            'all' => 1,
            'props' => ['cat_id', 'category_name'],
            'company' => $compId,
            'companyId' => $compId,
        ]);
        foreach ($this->extractApiList($response) as $cat) {
            $name = is_object($cat) ? ($cat->category_name ?? '') : ($cat['category_name'] ?? '');
            $id = is_object($cat) ? ($cat->cat_id ?? null) : ($cat['cat_id'] ?? null);
            if ($name !== '' && $id !== null) {
                $map[mb_strtolower(trim((string) $name))] = (int) $id;
            }
        }
        return $map;
    }

    /**
     * Construye el mapa de unidades de la empresa (nombre en minúsculas => unit_id) vía API.
     *
     * @param int $compId
     * @return array<string,int>
     */
    private function buildUnitMap($compId)
    {
        $map = [];
        $response = $this->getObjectFromApi("company/{$compId}/units");
        foreach ($this->extractApiList($response) as $unit) {
            $name = is_object($unit) ? ($unit->unit ?? '') : ($unit['unit'] ?? '');
            $id = is_object($unit) ? ($unit->unit_id ?? null) : ($unit['unit_id'] ?? null);
            if ($name !== '' && $id !== null) {
                $map[mb_strtolower(trim((string) $name))] = (int) $id;
            }
        }
        return $map;
    }

    /**
     * Resuelve los IDs de categoría a partir de los nombres del CSV, creando las faltantes vía API.
     *
     * @param string $rawcatnames
     * @param array<string,int> $categoryMap
     * @param int $compId
     * @return int[]
     */
    private function resolveCategoryIds($rawcatnames, array &$categoryMap, $compId)
    {
        $ids = [];
        $names = array_filter(
            array_map('trim', explode(',', (string) $rawcatnames)),
            static function ($n) {
                return $n !== '';
            }
        );
        foreach ($names as $name) {
            $key = mb_strtolower($name);
            if (isset($categoryMap[$key])) {
                $ids[] = $categoryMap[$key];
                continue;
            }
            $created = $this->post('category', [
                'company_id' => $compId,
                'category_name' => $name,
            ]);
            $catId = $this->extractId($created, 'cat_id');
            if ($catId !== null) {
                $categoryMap[$key] = $catId;
                $ids[] = $catId;
                continue;
            }

            log_message(
                'error',
                'CsvImport::resolveCategoryIds could not create category "' . $name . '": '
                . $this->extractApiErrorMessage($created)
            );
        }
        return array_values(array_unique($ids));
    }

    /**
     * Resuelve unit_id en importación: vacío → unidad por defecto; nombre desconocido → se crea.
     *
     * @param array<string, int> $unitMap
     */
    private function resolveProductImportUnitId(string $unitName, array &$unitMap, int $compId): ?int
    {
        $unitName = trim($unitName);
        if ($unitName === '') {
            return $this->extractProductImportDefaultUnitId($unitMap);
        }

        $key = mb_strtolower($unitName);
        if (isset($unitMap[$key])) {
            return $unitMap[$key];
        }

        $created = $this->post('unit', [
            'company_id' => $compId,
            'unit' => $unitName,
            'description' => $unitName,
            'formato' => 'entero',
            'default' => false,
        ]);
        $unitId = $this->extractId($created, 'unit_id');
        if ($unitId !== null) {
            $unitMap[$key] = $unitId;

            return $unitId;
        }

        $this->productImportUnitMap = null;
        $unitMap = &$this->getProductImportUnitMapRef();
        if (isset($unitMap[$key])) {
            return $unitMap[$key];
        }

        log_message(
            'error',
            'CsvImport::resolveProductImportUnitId could not create unit "' . $unitName . '": '
            . $this->extractApiErrorMessage($created)
        );

        return null;
    }

    /**
     * @param array<string, int> $unitMap
     */
    private function extractProductImportDefaultUnitId(array &$unitMap): ?int
    {
        $defaultUnit = $this->getProductImportDefaultUnit();
        if ($defaultUnit === null) {
            return null;
        }

        $unitId = $this->extractId($defaultUnit, 'unit_id');
        if ($unitId === null || $unitId < 1) {
            return null;
        }

        $unitLabel = '';
        if (is_object($defaultUnit) && isset($defaultUnit->unit)) {
            $unitLabel = trim((string) $defaultUnit->unit);
        } elseif (is_array($defaultUnit) && isset($defaultUnit['unit'])) {
            $unitLabel = trim((string) $defaultUnit['unit']);
        }

        if ($unitLabel !== '') {
            $unitMap[mb_strtolower($unitLabel)] = $unitId;
        }

        return $unitId;
    }

    /**
     * Busca un producto por SKU dentro de la empresa vía API.
     *
     * @param string $sku
     * @param int $compId
     * @return int|null  pro_id si existe, null si no
     */
    private function findProductIdBySku($sku, $compId)
    {
        $sku = trim((string) $sku);
        if ($sku === '') {
            return null;
        }
        $existing = $this->getObjectFromApi('product/sku-' . rawurlencode($sku), ['company_id' => $compId]);
        return $this->extractId($existing, 'pro_id');
    }

    /**
     * Extrae un id de la respuesta de la API (soporta objeto/array y envoltorio 'data').
     *
     * @param mixed $response
     * @param string $field
     * @return int|null
     */
    private function extractId($response, $field)
    {
        if (empty($response) || $this->hasError($response)) {
            return null;
        }
        $data = $response;
        if (is_object($response) && isset($response->data)) {
            $data = $response->data;
        } elseif (is_array($response) && isset($response['data'])) {
            $data = $response['data'];
        }
        if (is_object($data) && isset($data->{$field})) {
            return (int) $data->{$field};
        }
        if (is_array($data) && isset($data[$field])) {
            return (int) $data[$field];
        }
        return null;
    }

    /**
     * Normaliza la respuesta de la API a una lista (array) de elementos.
     *
     * @param mixed $response
     * @return array
     */
    private function extractApiList($response)
    {
        if (empty($response) || $this->hasError($response)) {
            return [];
        }
        if (is_object($response) && isset($response->data) && is_array($response->data)) {
            return $response->data;
        }
        if (is_array($response) && isset($response['data']) && is_array($response['data'])) {
            return $response['data'];
        }
        if (is_array($response)) {
            return $response;
        }
        return [];
    }

    /**
     * Obtiene un mensaje de error legible desde la respuesta de la API.
     * Soporta el formato del ApiClient: ['error' => 'Client error', 'message' => ['message' => '...', 'errors' => [...]]]
     * y errores 5xx: ['error' => 'Server error', 'message' => '...'].
     *
     * @param mixed $response
     * @return string
     */
    private function extractApiErrorMessage($response)
    {
        if (is_object($response)) {
            $response = json_decode(json_encode($response), true);
        }
        if (!is_array($response)) {
            return 'error';
        }

        // 1) Errores de validación de Laravel anidados en message.errors
        $message = $response['message'] ?? null;
        if (is_array($message)) {
            if (isset($message['errors']) && is_array($message['errors'])) {
                $parts = [];
                foreach ($message['errors'] as $err) {
                    $parts[] = is_array($err) ? implode(', ', $err) : (string) $err;
                }
                $joined = implode(' | ', array_filter($parts));
                if ($joined !== '') {
                    return $joined;
                }
            }
            if (isset($message['message']) && is_string($message['message']) && $message['message'] !== '') {
                return $message['message'];
            }
        }

        // 2) message como string
        if (is_string($message) && $message !== '') {
            return $message;
        }

        // 3) errors en el primer nivel
        if (isset($response['errors']) && is_array($response['errors'])) {
            $parts = [];
            foreach ($response['errors'] as $err) {
                $parts[] = is_array($err) ? implode(', ', $err) : (string) $err;
            }
            $joined = implode(' | ', array_filter($parts));
            if ($joined !== '') {
                return $joined;
            }
        }

        // 4) etiqueta genérica del tipo de error
        if (isset($response['error']) && is_string($response['error'])) {
            return $response['error'];
        }

        return 'error';
    }

    public function validate()
    {
        $request = $this->input->post();

        if (($request['product_name'] ?? '') == '') {
            echo json_encode(
                ['input_name' => 'ifg_product_name', 'success' => false, 'msg' => $this->langArr['error_name'] ?? '']
            );
            return;
        }

        if (($request['categ_list'] ?? '') == '') {
            echo json_encode(
                ['input_name' => 'ifg_categ_list', 'success' => false, 'msg' => $this->langArr['error_categ_list'] ?? '']
            );
            return;
        }

        if (($request['sku'] ?? '') == '') {
            echo json_encode(['input_name' => 'ifg_sku', 'success' => false, 'msg' => $this->langArr['error_sku'] ?? '']);
            return;
        }

        if (($request['sale_price'] ?? '') == '') {
            echo json_encode(
                ['input_name' => 'ifg_sale_price', 'success' => false, 'msg' => $this->langArr['error_saleprice'] ?? '']
            );
            return;
        }

        if (($request['sale_price'] ?? 0) <= 0) {
            echo json_encode(
                ['input_name' => 'ifg_sale_price', 'success' => false, 'msg' => $this->langArr['error_saleprice_qty'] ?? '']
            );
            return;
        }

        if (($request['status'] ?? '') == '') {
            echo json_encode(['input_name' => 'ifg_status', 'success' => false, 'msg' => $this->langArr['error_status'] ?? '']);
            return;
        }

        echo json_encode(['success' => true]);
        return;
    }

    /**
     * Unidad de medida por defecto de la empresa vía API (GET unit/company/{id}).
     * Devuelve un objeto con ->unit o null si no hay/da error.
     *
     * @param int $compId
     * @return object|null
     */
    private function getDefaultUnit($compId)
    {
        $response = $this->getObjectFromApi("unit/company/{$compId}");
        if ($this->hasError($response) || empty($response) || !is_object($response)) {
            return null;
        }

        return $response;
    }

}
