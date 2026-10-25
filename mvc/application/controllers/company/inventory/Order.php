<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Órdenes de inventario (tipo_d = I). Datos vía API Laravel; sin consultas SQL directas en MVC.
 */
class Order extends Company_Controller {

	public $company_info;
	public $format_numeric;
	public $lang_order = array();
	public $lang_def = array();
	public $identify = "inventory";
	public $data = array('title' => '','page' => 'order','subpage' => 'grouplist');
	public $categories = array();

	public ?int $langId;
	public ?int $company;

	public function __construct()
	{
		parent::__construct();
        $this->load->model('langmodel');
        $this->load->model('Helper');
        $this->load->model('PrintModel');

		$this->company = (int) $this->getCompanyId();
		$this->langId = (int) ($this->getUserData('language_id') ?? 1);

		$companyInfo = $this->getObjectFromApi("company/{$this->company}");
		$this->company_info = (is_object($companyInfo) && ! isset($companyInfo->error))
			? $companyInfo
			: (object) ['company_id' => $this->company, 'language_id' => $this->langId];

		$formatNumeric = $this->getObjectFromApi("company/{$this->company}/format-numeric");
		if ($this->hasError($formatNumeric)) {
			$formatNumeric = $this->getObjectFromApi('formatnumeric/default');
		}
		$this->format_numeric = (is_object($formatNumeric) && ! isset($formatNumeric->error)) ? $formatNumeric : (object) [];

		$ahora = DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''));
		$this->data['version'] = $ahora->format("YmdHisu");
		$this->data['company_info'] = $this->company_info;
		$this->data['lang_order'] = $this->lang_order = $this->langmodel->getMdl('cmp-orders', $this->langId);
		$this->data['lang_prods'] = $this->langmodel->getMdl('cmp-products', $this->langId);
		$this->data['lang_inventory'] = $this->langmodel->getMdl('cmp-inventory', $this->langId);
		$this->data['langdef'] = $this->langmodel->getMdl('comp-login-head', $this->langId);
		$this->data['lang_clientes'] = $this->langmodel->getMdl('cmp-clientes', $this->langId);
		$this->data['title'] = (string) ($this->data['lang_order']['titulo_inventario']
			?? $this->data['lang_inventory']['page_title']
			?? $this->data['title']
			?? '');
		$this->data['warelist'] = $this->inventoryWarehouseUsersForSelect();
		$statusAll = $this->inventoryStatusListAll();
		$this->data['statusLookup'] = $statusAll;
		$this->data['statuslist'] = $this->inventoryStatusListForFilter($statusAll);
	}

	/**
	 * @return array<int, object>
	 */
	private function inventoryWarehouseUsersForSelect(): array
	{
		$resp = $this->getObjectFromApi("company/{$this->company}/warehouse-users-for-order-assignment");
		$rows = [];
		if (is_object($resp) && isset($resp->data) && is_array($resp->data)) {
			$rows = $resp->data;
		} elseif (is_array($resp) && isset($resp['data']) && is_array($resp['data'])) {
			$rows = $resp['data'];
		}

		$out = [];
		foreach ($rows as $row) {
			$row = (object) $row;
			$name = trim((string) ($row->name ?? ''));
			if ($name === '' && isset($row->email)) {
				$name = trim((string) $row->email);
			}
			$parts = preg_split('/\s+/', $name, 2);
			$out[] = (object) [
				'user_id' => (int) ($row->user_id ?? $row->id ?? 0),
				'first_name' => (string) ($parts[0] ?? $name),
				'last_name' => (string) ($parts[1] ?? ''),
			];
		}

		return $out;
	}

	/**
	 * Todos los estados de orden (para colorear/etiquetar la grilla).
	 *
	 * @return array<int, object>
	 */
	private function inventoryStatusListAll(): array
	{
		$raw = $this->getObjectFromApi('statusorder/list');
		$rows = $this->normalizeAndDeduplicateOrderStatuses($raw ?: []);

		$nameKey = ((int) $this->langId === 2) ? 'name_status_spanish' : 'name_status_english';
		$out = [];
		foreach ($rows as $status) {
			$status = is_object($status) ? $status : (object) $status;
			$cod = (int) ($status->cod_status ?? 0);
			if ($cod < 0) {
				continue;
			}
			$status->name_status = (string) ($status->{$nameKey} ?? $status->name_status_english ?? '');
			$out[] = $status;
		}

		return $out;
	}

	/**
	 * Estados del filtro de pedidos de inventario (códigos legacy 2, 11, 16).
	 *
	 * @param array<int, object>|null $allStatuses
	 * @return array<int, object>
	 */
	private function inventoryStatusListForFilter(?array $allStatuses = null): array
	{
		$allowed = [2, 11, 16];
		$rows = $allStatuses ?? $this->inventoryStatusListAll();
		$out = [];
		foreach ($rows as $status) {
			$status = is_object($status) ? $status : (object) $status;
			$cod = (int) ($status->cod_status ?? 0);
			if (! in_array($cod, $allowed, true)) {
				continue;
			}
			$out[] = $status;
		}

		return $out;
	}

	private function inventoryPaginationConfig(string $baseUrl, int $totalRows, int $showPerPage): array
	{
		$langProds = $this->langmodel->getMdl('cmp-products', $this->langId);

		return [
			'base_url' => $baseUrl,
			'num_links' => 2,
			'uri_segment' => 4,
			'total_rows' => $totalRows,
			'per_page' => $showPerPage,
			'full_tag_open' => '<ul class="pagination pagination-sm">',
			'full_tag_close' => '</ul>',
			'num_tag_open' => '<li>',
			'num_tag_close' => '</li>',
			'first_link' => $langProds['pagination_first'] ?? '',
			'first_tag_open' => '<li>',
			'first_tag_close' => '</li>',
			'last_link' => $langProds['pagination_last'] ?? '',
			'last_tag_open' => '<li>',
			'last_tag_close' => '</li>',
			'prev_link' => $langProds['pagination_prev'] ?? '',
			'prev_tag_open' => '<li>',
			'prev_tag_close' => '</li>',
			'next_link' => $langProds['pagination_next'] ?? '',
			'next_tag_open' => '<li>',
			'next_tag_close' => '</li>',
			'cur_tag_open' => '<li class="active"><a href="#">',
			'cur_tag_close' => '</a></li>',
			'attributes' => ['class' => 'page-link'],
		];
	}

	private function inventoryFormatDate(): object
	{
		if (function_exists('fv_resolve_format_date_object')) {
			return fv_normalize_format_date_object(fv_resolve_format_date_object());
		}

		$formatDate = $this->getObjectFromApi('formatdate/default');
		if (is_object($formatDate) && ! isset($formatDate->error)) {
			return function_exists('fv_normalize_format_date_object')
				? fv_normalize_format_date_object($formatDate)
				: $formatDate;
		}

		return fv_default_format_date_object();
	}

	/**
	 * @param array<string,mixed> $body
	 * @return array<string,mixed>|null
	 */
	private function inventoryOrdersApiPost(int $companyId, array $body): ?array
	{
		$payload = array_merge(['company_id' => $companyId], $body);
		$resp = $this->post('order/inventory/orders-list', $payload, true);
		if (! is_array($resp) || isset($resp['error'])) {
			log_message('error', 'inventoryOrdersApiPost: '.json_encode($resp));
			return null;
		}
		return $resp;
	}

	private function inventoryOrdersApiBaseQuery(string $desde_fecha, string $hasta_fecha, $status, $order, $type_order, $warehouse_user): array
	{
		$q = [
			'desde_fecha' => $desde_fecha,
			'hasta_fecha' => $hasta_fecha,
			'sort' => $order,
			'order_type' => $type_order ?? '',
		];
		$statusList = [];
		if (is_array($status)) {
			$statusList = $status;
		} elseif ($status !== null && $status !== '') {
			$statusList = [$status];
		}
		if ($statusList !== []) {
			$q['estado'] = array_map('intval', $statusList);
		}
		$warehouseId = null;
		if ($warehouse_user !== null && $warehouse_user !== '') {
			if (is_array($warehouse_user)) {
				$wu = array_values(array_filter($warehouse_user, static function ($v) {
					return $v !== null && $v !== '';
				}));
				$warehouseId = $wu !== [] ? (int) $wu[0] : null;
			} else {
				$warehouseId = (int) $warehouse_user;
			}
		}
		if ($warehouseId !== null && $warehouseId > 0) {
			$q['warehouse_user'] = $warehouseId;
		}
		return $q;
	}

	private function inventoryOrderRowFromApi($row): object
	{
		$a = is_array($row) ? $row : (array) $row;
		$o = (object) $a;
		if (empty($o->warehouse_user_id) && isset($a['warehouseUserId'])) {
			$o->warehouse_user_id = $a['warehouseUserId'];
		}
		$wu = isset($o->warehouse_user) ? trim((string) $o->warehouse_user) : '';
		if ($wu === '' && isset($a['warehouseUser'])) {
			$o->warehouse_user = $a['warehouseUser'];
		}

		return $o;
	}

	private function inventoryCategoriesForAddSelect(): array
	{
		$resp = $this->post('order/inventory/categories-for-select', ['company_id' => (int) $this->company], true);
		if (! is_array($resp) || isset($resp['error'])) {
			log_message('error', 'inventoryCategoriesForAddSelect: '.json_encode($resp));
			return [];
		}
		$out = [];
		foreach ($resp['data'] ?? [] as $row) {
			$out[] = is_object($row) ? $row : (object) $row;
		}

		return $out;
	}

	public function get_25orden($compId,$desde_fecha=null,$hasta_fecha=null,$search=null,$status=null,$order=null,$type_order = null,$show_per_page=null,$offset=null,$warehouse_user=null){

		$base = $this->inventoryOrdersApiBaseQuery(
			$desde_fecha ?? '',
			$hasta_fecha ?? '',
			$status,
			$order,
			$type_order,
			$warehouse_user
		);
		if ($show_per_page !== null && $offset !== null) {
			$page = (int) floor((int) $offset / (int) $show_per_page) + 1;
			$base['page'] = max(1, $page);
			$base['per_page'] = (int) $show_per_page;
			$resp = $this->inventoryOrdersApiPost((int) $compId, $base);
			if ($resp === null) {
				return [];
			}
			$out = [];
			foreach ($resp['data'] ?? [] as $row) {
				$out[] = (object) $row;
			}
			return $out;
		}
		$all = [];
		$page = 1;
		$per = 200;
		do {
			$q = array_merge($base, ['page' => $page, 'per_page' => $per]);
			$resp = $this->inventoryOrdersApiPost((int) $compId, $q);
			if ($resp === null) {
				return [];
			}
			foreach ($resp['data'] ?? [] as $row) {
				$all[] = (object) $row;
			}
			$got = count($resp['data'] ?? []);
			$page++;
		} while ($got === $per);
		return $all;

	}

	public function index(){

		$page = isset($_GET['page'])?$_GET['page']:1;

		$show_per_page = 25;
		$offset = ($page - 1) * $show_per_page;
		$format_date = $this->inventoryFormatDate();
		$request = $this->input->get();

		if (isset($request['desde_fecha_texto']) && isset($request['desde_fecha']) && $request['desde_fecha']!=null) {
			$_GET['desde_fecha_texto'] = date($format_date->format_php, strtotime($request['desde_fecha']));
		}

		if (isset($request['hasta_fecha_texto']) && isset($request['hasta_fecha']) && $request['hasta_fecha']!=null) {
			$_GET['hasta_fecha_texto'] = date($format_date->format_php, strtotime($request['hasta_fecha']));

		}else if (isset($request['hasta_fecha_texto']) && isset($request['hasta_fecha']) && $request['hasta_fecha']==null) {
			$_GET['hasta_fecha_texto'] = date($format_date->format_php);
		}

		$desde_fecha = $this->input->get("desde_fecha") == null ? '' : $this->input->get("desde_fecha");
		$hasta_fecha= $this->input->get("hasta_fecha") == null ? '' : $this->input->get("hasta_fecha");
		$status = $this->input->get("estado") == null ? [] : $this->input->get("estado");
		$warehouse_id= $this->input->get("warehouse_user") == null ? [] : $this->input->get("warehouse_user");
		$order_type = $this->input->get("order_type") == null ? '' : $this->input->get("order_type");
		$order_sort = $this->input->get("sort") == null ? null : $this->input->get("sort");

		$input_todos = $this->input->get("select_todos") == 'todos' ? true : false;
		$_GET['input_todos'] = $input_todos;
		$_GET['order_type_general']=$order_type;
		$_GET['order_type_sort']=$order_sort;

		$base_url = company_url('inventory/order');

		$listQuery = $this->inventoryOrdersApiBaseQuery($desde_fecha, $hasta_fecha, $status, $order_sort, $order_type, $warehouse_id);
		$listQuery['page'] = max(1, (int) $page);
		$listQuery['per_page'] = $show_per_page;
		$listResp = $this->inventoryOrdersApiPost((int) $this->company, $listQuery);
		if ($listResp === null) {
			$this->data['count_filtered'] = 0;
			$this->data['orderlist'] = [];
			$ordenes_totales = 0;
		} else {
			$this->data['count_filtered'] = (int) ($listResp['total_filtered'] ?? 0);
			$this->data['orderlist'] = [];
			foreach ($listResp['data'] ?? [] as $row) {
				$this->data['orderlist'][] = $this->inventoryOrderRowFromApi($row);
			}
			$ordenes_totales = (int) ($listResp['total_company_inventory'] ?? 0);
		}
		$config = $this->inventoryPaginationConfig($base_url, $this->data['count_filtered'], $show_per_page);
		$this->pagination->initialize($config);
		$this->data['paginate'] = $this->pagination->create_links();

		$this->load->model('HelperLanguage');
		$info['lang_meses'] = $this->HelperLanguage->meses();
		$info['lang_clientes'] = $this->HelperLanguage->lang_customer();
		$info['ordenes_totales'] = $ordenes_totales;
		$info['format_date'] = $format_date;
		$_GET['order_type_order_number']= ($order_sort == 'orden_number')?($order_type == 'ASC')?'DESC':'ASC':'ASC';
		$_GET['order_type_warehouseuser']= ($order_sort == 'orden_warehouseuser')?($order_type == 'ASC')?'DESC':'ASC':'ASC';
		$_GET['order_type_date']= ($order_sort == 'orden_date')?($order_type == 'ASC')?'DESC':'ASC':'ASC';
		$_GET['order_type_comments']=($order_sort == 'orden_comments')?($order_type == 'ASC')?'DESC':'ASC':'ASC';
		$info['langprods'] = $this->HelperLanguage->lang_products();

		$viewData = array_merge($this->data, $info, [
			'js' => ['general/export_file.js'],
		]);
		$this->showContent('company/inventario/lists', $viewData, 'company');

	}

	public function add(){

		$this->data['category'] = $this->inventoryCategoriesForAddSelect();
		$this->data['lang_inventory'] = array_merge(
			(array) ($this->data['lang_inventory'] ?? []),
			$this->getLangJsonSection('cmp-inventory')
		);

		$langIdForUi = (int) ($this->company_info->language_id ?? $this->langId ?? 1);
		$this->data['pagination_prev_label'] = $this->getTranslationFromLangJsonByLanguageId(
			$langIdForUi,
			'company-datatable',
			'previous',
			'Previous'
		);
		$this->data['pagination_next_label'] = $this->getTranslationFromLangJsonByLanguageId(
			$langIdForUi,
			'company-datatable',
			'next',
			'Next'
		);

		$this->addJsFile(base_url('companyasset/company/order/ImagePopover.js'));
		$this->addJsFile(base_url('companyasset/company/inventario/state_products.js'));
		$this->showContent('company/inventario/add', $this->data, 'company');

	}

	public function request_haveerror($request){

		$invLang = array_merge(
			(array) ($this->data['lang_inventory'] ?? []),
			$this->getLangJsonSection('cmp-inventory')
		);

		if (!isset($request['warehouse_user']) || !$request['warehouse_user']) {
			return trim((string) ($invLang['inventory_create_warehouse_required'] ?? ''))
				?: ($this->lang_order['invalid_request'] ?? '');
		}

		if (!isset($request['product_list']) || ! is_array($request['product_list']) || count($request['product_list']) === 0) {
			return trim((string) ($invLang['inventory_create_products_required'] ?? ''))
				?: ($this->lang_order['details_required'] ?? ($this->lang_order['invalid_request'] ?? ''));
		}

		$uniqueProducts = array_values(array_unique(array_filter(array_map('strval', $request['product_list']))));
		if (count($uniqueProducts) > 500) {
			return trim((string) ($invLang['inventory_create_products_max'] ?? ''))
				?: ($this->lang_order['invalid_request'] ?? '');
		}
		return null;
	}

	private function resolveInventoryCreateApiErrorMessage($resp): string
	{
		$fallback = $this->lang_order['invalid_request'] ?? '';
		$invLang = array_merge(
			(array) ($this->data['lang_inventory'] ?? []),
			$this->getLangJsonSection('cmp-inventory')
		);

		if (! is_array($resp)) {
			return $fallback;
		}

		$payload = $resp;
		if (isset($resp['message']) && is_array($resp['message'])) {
			$payload = $resp['message'];
		}

		$apiMsg = $payload['msg'] ?? null;
		if (is_string($apiMsg) && $apiMsg !== '') {
			if ($apiMsg === 'customer_required') {
				return $this->lang_order['select_customer'] ?? $fallback;
			}
			if ($apiMsg === 'product_list_required') {
				return trim((string) ($invLang['inventory_create_products_required'] ?? ''))
					?: ($this->lang_order['details_required'] ?? $fallback);
			}
			if ($apiMsg === 'product_list_max') {
				return trim((string) ($invLang['inventory_create_products_max'] ?? '')) ?: $fallback;
			}
			if ($apiMsg === 'inventory_create_failed') {
				return trim((string) ($invLang['inventory_create_failed'] ?? '')) ?: $fallback;
			}

			return $apiMsg;
		}

		$apiMessage = $payload['message'] ?? null;
		if (is_string($apiMessage) && $apiMessage !== '') {
			return $apiMessage;
		}

		if (isset($resp['error'])) {
			log_message('error', 'inventory order create API: '.json_encode($resp));
		}

		return $fallback;
	}

	private function resolveInventoryDetailImageUrl(string $imageName, string $imageUuid, string $legacyImgUrl): string
	{
		$uuid = trim($imageUuid);
		if ($uuid !== '' && function_exists('company_product_image_url')) {
			return company_product_image_url($uuid, 90);
		}
		if ($uuid !== '' && function_exists('company_url')) {
			return rtrim(company_url(), '/') . '/image/' . rawurlencode($uuid) . '_90';
		}

		$companyId = (int) ($this->company_info->company_id ?? $this->company ?? 0);
		$fallback = function_exists('catalog_public_no_image_url')
			? catalog_public_no_image_url()
			: base_url('uploads/noimg.png');

		$name = trim($imageName);
		if ($name !== '' && $companyId > 0) {
			$basePath = (defined('FCPATH') ? FCPATH : './') . 'uploads/products/' . $companyId . DIRECTORY_SEPARATOR;
			if (is_file($basePath . $name)) {
				return base_url('uploads/products/' . $companyId . '/' . rawurlencode($name));
			}
		}

		$legacy = trim($legacyImgUrl);
		if ($legacy !== '') {
			if (preg_match('#^https?://#i', $legacy) || strpos($legacy, '//') === 0) {
				return $legacy;
			}
			if ($companyId > 0) {
				$basePath = (defined('FCPATH') ? FCPATH : './') . 'uploads/products/' . $companyId . DIRECTORY_SEPARATOR;
				if (is_file($basePath . $legacy)) {
					return base_url('uploads/products/' . $companyId . '/' . rawurlencode($legacy));
				}
			}
		}

		return $fallback;
	}

	/**
	 * @param array<string, mixed> $row
	 * @return array<string, mixed>
	 */
	private function enrichInventoryProductRowImages(array $row): array
	{
		$row['image'] = $this->resolveInventoryDetailImageUrl(
			(string) ($row['image_name'] ?? $row['imagen_principal'] ?? ''),
			(string) ($row['image_uuid'] ?? ''),
			(string) ($row['legacy_imgURL'] ?? $row['imagen_principal'] ?? '')
		);
		$previewUuid = trim((string) ($row['image_uuid'] ?? ''));
		if ($previewUuid !== '' && function_exists('company_product_image_url')) {
			$row['image_preview'] = company_product_image_url($previewUuid, 400);
		} else {
			$row['image_preview'] = $row['image'];
		}

		$categories = [];
		$rawCats = $row['categories'] ?? null;
		if (! is_array($rawCats) || $rawCats === []) {
			// Fallback: construir desde ids + nombres si la API vieja no envió categories[].
			$ids = array_values(array_filter(array_map('intval', explode(',', (string) ($row['category_id'] ?? '')))));
			$names = array_values(array_filter(array_map('trim', explode('|', (string) ($row['categories_name'] ?? '')))));
			foreach ($ids as $i => $cid) {
				$categories[] = [
					'cat_id' => $cid,
					'category_name' => $names[$i] ?? '',
					'image_uuid' => '',
					'image' => '',
					'has_image' => false,
				];
			}
		} else {
			foreach ($rawCats as $cat) {
				$cat = is_array($cat) ? $cat : (array) $cat;
				$uuid = trim((string) ($cat['image_uuid'] ?? ''));
				if ($uuid === '' && ! empty($cat['image']) && is_string($cat['image'])) {
					if (preg_match('/^([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/i', $cat['image'], $m)) {
						$uuid = $m[1];
					}
				}
				$thumb = '';
				$preview = '';
				$hasImage = false;
				if (function_exists('fv_category_image_urls')) {
					$urls = fv_category_image_urls([
						'image_uuid' => $uuid,
						'image' => $uuid,
						'category_name' => (string) ($cat['category_name'] ?? ''),
					]);
					$thumb = (string) ($urls['thumb'] ?? '');
					$preview = (string) ($urls['preview'] ?? $urls['thumb'] ?? '');
					$hasImage = ! empty($urls['has_image']);
				} elseif ($uuid !== '' && function_exists('company_product_image_url')) {
					$thumb = company_product_image_url($uuid, 48);
					$preview = company_product_image_url($uuid, 400);
					$hasImage = $thumb !== '';
				}
				$categories[] = [
					'cat_id' => (int) ($cat['cat_id'] ?? 0),
					'category_name' => (string) ($cat['category_name'] ?? ''),
					'image_uuid' => $uuid,
					'image' => $thumb,
					'image_preview' => $preview !== '' ? $preview : $thumb,
					'has_image' => $hasImage,
				];
			}
		}
		$row['categories'] = $categories;

		return $row;
	}

	public function listproduct(){
		$request = $this->input->post();
		if (!isset($request['order_categories'])){
			echo json_encode(["success"=>false,"redirect"=>false,"msg"=>$this->lang_order["category_one"]]);
			return;
		}

		if (isset($request['from']) && $request['from']==0){
			if ($error=$this->request_haveerror($request)) {
				echo json_encode(["success"=>false,"redirect"=>false,"msg"=>$error]);
				return;
			}
			echo json_encode(["success"=>false,"redirect"=>true,"msg"=>""]);
			return;
		}

		$resp = $this->post('order/inventory/products-for-add', ['company_id' => (int) $this->company], true);
		$allProducts = is_array($resp) && isset($resp['data']) && is_array($resp['data']) ? $resp['data'] : [];
		$selectedCategoryIds = array_map(static function ($c) {
			return (int) ($c['id'] ?? 0);
		}, $request['order_categories'] ?? []);

		$list_objectcategories = [];
		foreach ($request['order_categories'] as $categoryid_order) {
			$fileObjeto = json_decode(json_encode($categoryid_order, JSON_FORCE_OBJECT));
			$fileObjeto->arrayproduct = [];
			$list_objectcategories[] = $fileObjeto;
		}

		$aditional_products = [];
		$proid_array = isset($request['product_list']) ? $request['product_list'] : [];
		$proid_lookup = array_flip(array_map('strval', $proid_array));

		$result = [];
		foreach ($allProducts as $row) {
			$row = (object) $this->enrichInventoryProductRowImages(is_array($row) ? $row : (array) $row);
			$proId = (int) ($row->pro_id ?? 0);
			$isAditional = isset($proid_lookup[(string) $proId]);
			if ($isAditional) {
				$row->aditional = 1;
				$row->category_name = (string) ($row->categories_name ?? '');
				$aditional_products[] = $row;
				continue;
			}

			$catIds = array_filter(array_map('intval', explode(',', (string) ($row->category_id ?? ''))));
			$matchesCategory = false;
			foreach ($catIds as $cid) {
				if (in_array($cid, $selectedCategoryIds, true)) {
					$matchesCategory = true;
					break;
				}
			}
			if (! $matchesCategory) {
				continue;
			}

			$row->aditional = 0;
			$row->category_name = (string) ($row->categories_name ?? '');
			$row->category_array = array_map('strval', $catIds);
			$result[] = $row;

			foreach ($list_objectcategories as $category) {
				if (in_array((string) $category->id, $row->category_array, true)) {
					$category->arrayproduct[] = $row;
				}
			}
		}

		$result = array_merge($aditional_products, $result);
		$list_aditionalproducts = $aditional_products;
		$list_products = [
			'adtional_products' => $list_aditionalproducts,
			'order_products_bycategory' => $list_objectcategories,
		];

		echo json_encode([
			"success"=>true,
			'aditional_products'=>$aditional_products,
			'products_result'=>$list_products,
			'previous_category'=>$request['order_categories'],
			'request'=>$request
		]);
	}

	public function addone(){
		$request = $this->input->post();
		$resp = $this->post('order/inventory/add-one-product', [
			'company_id' => (int) $this->company,
			'aditional_product' => (int) ($request['aditional_product'] ?? 0),
		], true);

		if (! is_array($resp)) {
			echo json_encode(["success"=>false,"msg"=>$this->lang_order["product_noexist"]]);
			return;
		}

		if (empty($resp['success'])) {
			echo json_encode(["success"=>false,"msg"=>$this->lang_order["product_noexist"]]);
			return;
		}

		if (!isset($request['product_list'])) {
			$request['product_list'] = [];
		}

		if(in_array($request['aditional_product'], $request['product_list'])){
			echo json_encode(["success"=>false,"msg"=>$this->lang_order["product_list_table"]]);
			return;
		}

		echo json_encode($resp);
	}

	public function create(){

		if (strtoupper($this->input->server('REQUEST_METHOD') ?? '') !== 'POST') {
			if (! $this->input->is_ajax_request()) {
				redirect(company_url('inventory/order/add'));
				return;
			}
			$this->output->set_status_header(405);
			$this->output->set_header('Allow: POST');
			$this->output->set_content_type('application/json');
			echo json_encode([
				'success' => false,
				'redirect' => false,
				'msg' => $this->lang_order['invalid_request'] ?? '',
			]);
			return;
		}

		$request = $this->input->post();

		if ($error=$this->request_haveerror($request)) {
			$this->output->set_content_type('application/json');
			echo json_encode(["success"=>false,"redirect"=>false,"msg"=>$error]);
			return;
		}

		$this->output->set_content_type('application/json');

		$this->data['lang_inventory'] = array_merge(
			(array) ($this->data['lang_inventory'] ?? []),
			$this->getLangJsonSection('cmp-inventory')
		);

		$productList = [];
		if (isset($request['product_list']) && is_array($request['product_list'])) {
			$productList = array_values(array_unique($request['product_list']));
		}

		$resp = $this->post('order/inventory/create', [
			'company_id' => (int) $this->company,
			'warehouse_user' => (int) ($request['warehouse_user'] ?? 0),
			'product_list' => $productList,
		], true);

		if (! is_array($resp) || empty($resp['success'])) {
			echo json_encode([
				"success" => false,
				"redirect" => false,
				"msg" => $this->resolveInventoryCreateApiErrorMessage($resp),
			]);
			return;
		}

		$ordersCount = (int) ($resp['orders_count'] ?? 1);
		if ($ordersCount < 1) {
			$ordersCount = 1;
		}
		$successText = $this->data['lang_inventory']['inventoryorder_saved'] ?? '';
		if ($ordersCount > 1) {
			$many = trim((string) ($this->data['lang_inventory']['inventoryorder_saved_many'] ?? ''));
			if ($many !== '') {
				$successText = str_replace('%s', (string) $ordersCount, $many);
			}
		}
		echo json_encode([
			'request' => [$request],
			'success' => true,
			'msg' => $successText,
			'orders_count' => $ordersCount,
			'urlredirect' => company_url('inventory/order'),
		]);

	}

	public function get_detail($order_id){
		$resp = $this->getObjectFromApi("order/inventory/order/{$order_id}/details", [
			'company_id' => (int) $this->company,
		]);
		$result = [];
		if (is_object($resp) && isset($resp->data) && is_array($resp->data)) {
			$result = $resp->data;
		} elseif (is_array($resp) && isset($resp['data']) && is_array($resp['data'])) {
			$result = $resp['data'];
		}

		foreach ($result as $idx => $rs) {
			$rs = is_object($rs) ? $rs : (object) $rs;
			$rs->image = $this->resolveInventoryDetailImageUrl(
				(string) ($rs->image_name ?? ''),
				(string) ($rs->image_uuid ?? ''),
				(string) ($rs->legacy_imgURL ?? '')
			);
			$result[$idx] = $rs;
		}

		return $result;
	}

	public function view($order_id = false){

		if ($order_id == false) show_404();

		$orderResp = $this->getObjectFromApi("order/inventory/order/{$order_id}", [
			'company_id' => (int) $this->company,
		]);
		if (! is_object($orderResp) || ! isset($orderResp->data) || isset($orderResp->error)) {
			show_404();
		}

		$this->data["order"] = $orderResp->data;
		$this->data["detail"] = $this->get_detail($order_id);

		$warehouseUserId = isset($this->data["order"]->warehouse_user_id) ? (int) $this->data["order"]->warehouse_user_id : 0;
		$warehouseLabel = '';
		foreach ($this->data['warelist'] as $wr) {
			if ((int) ($wr->user_id ?? 0) === $warehouseUserId) {
				$warehouseLabel = trim((string) ($wr->first_name ?? '').' '.(string) ($wr->last_name ?? ''));
				break;
			}
		}
		$this->data['warehouse_user_label'] = $warehouseLabel;
		$this->data["wuser"] = null;

		$statusCode = isset($this->data["order"]->order_status) ? (int) $this->data["order"]->order_status : 0;

		$statusText = '';
		$statusColor = '#cfd8dc';
		foreach ((array) ($this->data['statuslist'] ?? []) as $st) {
			if (isset($st->cod_status) && (int) $st->cod_status === $statusCode) {
				$statusText = (string) ($st->name_status ?? '');
				$statusColor = (string) ($st->color ?? $statusColor);
				break;
			}
		}
		if ($statusText === '' && $statusCode === 9) {
			$statusText = (string) ($this->lang_order['pending'] ?? 'Pending');
		}
		$this->data['order_status_text'] = $statusText;
		$this->data['order_status_color'] = $statusColor;

		$this->data['lang_inventory'] = array_merge(
			(array) ($this->data['lang_inventory'] ?? []),
			$this->getLangJsonSection('cmp-inventory')
		);

		$this->showContent('company/inventario/view', $this->data, 'company');
	}

	public function delete($detail_id){

		$resp = $this->apiDelete("order/inventory/detail/{$detail_id}", ['company_id' => (int) $this->company]);

		$failed = false;
		$msgKey = 'order_typeI';
		if (is_array($resp) && isset($resp['success']) && $resp['success'] === false) {
			$failed = true;
			$key = (string) ($resp['msg'] ?? '');
			$msgKey = ($key === 'order_type_pending') ? 'order_type_pending' : 'order_typeI';
		} elseif (is_object($resp) && isset($resp->success) && $resp->success === false) {
			$failed = true;
			$key = (string) ($resp->msg ?? '');
			$msgKey = ($key === 'order_type_pending') ? 'order_type_pending' : 'order_typeI';
		} elseif ($this->hasError($resp)) {
			$failed = true;
		}

		$wantsJson = $this->input->is_ajax_request()
			|| str_contains((string) ($this->input->server('HTTP_ACCEPT') ?? ''), 'application/json');

		if ($failed) {
			$msg = (string) ($this->lang_order[$msgKey] ?? '');
			if ($wantsJson) {
				$this->output->set_content_type('application/json', 'utf-8');
				echo json_encode(['success' => false, 'redirect' => false, 'msg' => $msg]);
				return;
			}
			$this->session->set_flashdata('msg', '["'.addslashes($msg).'", "error", "#DD6B55"]');
			redirect($this->agent->referrer() ?: company_url('inventory/order'), 'refresh');
			return;
		}

		$okMsg = (string) (($this->data['lang_inventory']['delete_success'] ?? '') . '!');
		$this->session->set_flashdata('msg', '["'.addslashes($okMsg).'", "success", "#A5DC86"]');

		if ($wantsJson) {
			$this->output->set_content_type('application/json', 'utf-8');
			echo json_encode(['success' => true]);
			return;
		}

		$referer = (string) ($this->agent->referrer() ?? '');
		redirect($referer !== '' ? $referer : company_url('inventory/order'), 'refresh');
	}

	public function load_products() {
		$resp = $this->post('order/inventory/products-for-add', ['company_id' => (int) $this->company], true);
		$products = is_array($resp) && isset($resp['data']) ? $resp['data'] : [];
		if (! is_array($products)) {
			$products = [];
		}
		foreach ($products as $idx => $row) {
			$row = is_array($row) ? $row : (array) $row;
			$products[$idx] = $this->enrichInventoryProductRowImages($row);
		}
		return $this->Helper->response_json($products);
	}

	public function print($order_id){

		$orderResp = $this->getObjectFromApi("order/inventory/order/{$order_id}", [
			'company_id' => (int) $this->company,
		]);
		if (! is_object($orderResp) || ! isset($orderResp->data)) {
			show_404();
		}

		$this->data["order"] = $orderResp->data;
		$this->data["detail"] = $this->get_detail($order_id);
		$this->data["wuser"] = null;

		$html = $this->load->view('company/inventario/paper_print', $this->data, TRUE);
		$this->load->library('pdf');
		$this->pdf->set_option('isRemoteEnabled',TRUE);
		$this->pdf->loadHtml($html);
		$this->pdf->setPaper('A4', 'portrait');
		$this->pdf->render();
		$this->pdf->stream("prueba" . '_order.pdf', array("Attachment" => 0));

	}

	public function export_dataFile(){

		$request_post = $this->input->post();
		if (! is_array($request_post)) {
			$request_post = [];
		}

		$compId = (int) ($this->company_info->company_id ?? $this->company);
		$estados = $this->inventoryStatusListForFilter();
		$lang_order = $this->lang_order;
		if (empty($lang_order)) {
			$this->load->model('HelperLanguage');
			$lang_order = $this->HelperLanguage->lang_order();
		}
		$lang_modal = [];
		try {
			$this->load->model('HelperLanguage');
			$lang_modal = $this->HelperLanguage->modal_exportLayout();
			if (! is_array($lang_modal)) {
				$lang_modal = [];
			}
		} catch (\Throwable $e) {
			$lang_modal = [];
		}

		$selectAtLeastOne = (string) ($lang_order['alert_select_at_least_one'] ?? $lang_modal['alert_select_at_least_one'] ?? $lang_order['invalid_request'] ?? '');

		$header = array(
					$lang_order['order_orden_number'] ?? '',
					'ID',
					'SKU',
					$lang_order['order_product'] ?? '');


		if (isset($request_post['show_stock_erp'])) {
			array_push($header, (string) ($lang_modal['show_stock_erp'] ?? 'Stock ERP'));
		}

		array_push(
			$header,
			$lang_order['order_qty'] ?? '',
			$lang_order['order_created'] ?? '',
			$lang_order['status'] ?? '',
			$lang_order['deposito_asignado'] ?? ''
		);

		if (isset($request_post['show_comment'])) {
			array_push($header, $lang_order['comments'] ?? '');
		}

		$date = date('Y-m-d');
		$body = [];
		$inputTodos = (string) ($request_post['input_todos'] ?? 'pagina');

		if ($inputTodos === 'pagina') {
			$order_ids = $request_post['orderIds'] ?? [];
			if (! is_array($order_ids)) {
				$order_ids = [];
			}
			$order_ids = array_values(array_filter(array_map('intval', $order_ids)));
			if ($order_ids === []) {
				return $this->Helper->response_json(['success' => false, 'msg' => $selectAtLeastOne]);
			}
			$ordersData = $this->inventoryExportRows($compId, $order_ids);
			$body = $this->get_discountTotalAndStatusPrint($ordersData, $estados, $lang_order, $request_post);
		} elseif ($inputTodos === 'todos') {
			$desde_fecha = $this->input->post('desde_fecha') == null ? '' : $this->input->post('desde_fecha');
			$hasta_fecha = $this->input->post('hasta_fecha') == null ? '' : $this->input->post('hasta_fecha');
			$status = $this->input->post('estado') == null ? [] : $this->input->post('estado');
			$warehouse_id = $this->input->post('warehouse_user') == null ? [] : $this->input->post('warehouse_user');
			$order_type = $this->input->post('order_type') == null ? '' : $this->input->post('order_type');
			$order_sort = $this->input->post('sort') == null ? null : $this->input->post('sort');

			$ordenes = $this->get_25orden($this->company, $desde_fecha, $hasta_fecha, null, $status, $order_sort, $order_type, null, null, $warehouse_id);

			$list_ordersID = [];
			foreach ($ordenes as $orden) {
				$list_ordersID[] = (int) $orden->order_id;
			}

			$ordersData = $this->inventoryExportRows($compId, $list_ordersID);
			$body = $this->get_discountTotalAndStatusPrint($ordersData, $estados, $lang_order, $request_post);
		}

		if ($body === []) {
			$emptyMsg = (string) ($lang_order['export_order_no_lines'] ?? $selectAtLeastOne);
			return $this->Helper->response_json(['success' => false, 'msg' => $emptyMsg]);
		}

		$typeFile = strtolower(trim((string) ($request_post['type_file'] ?? 'excel')));
		if (! in_array($typeFile, ['excel', 'pdf'], true)) {
			$typeFile = 'excel';
		}

		while (ob_get_level() > 0) {
			ob_end_clean();
		}

		switch ($typeFile) {
			case 'pdf':
				$filename = "OrderInventory-$date.pdf";
				$this->PrintModel->print_pdf($header, $body, $filename, 'portrait', '10px', [
					'bussiness_name' => true,
					'date' => true,
					'title' => $lang_order['titulo_inventario'] ?? '',
				]);
				break;
			default:
				$filename = "OrderInventory-$date.xlsx";
				$this->PrintModel->print_excel($header, $body, $filename);
				break;
		}
		exit;
	}

	/**
	 * @param array<int, int|string> $orderIds
	 * @return array<int, object>
	 */
	private function inventoryExportRows(int $companyId, array $orderIds): array
	{
		$resp = $this->post('order/inventory/export-rows', [
			'company_id' => $companyId,
			'order_ids' => array_values($orderIds),
		], true);

		if (! is_array($resp) || ! isset($resp['data']) || ! is_array($resp['data'])) {
			return [];
		}

		$out = [];
		foreach ($resp['data'] as $row) {
			$out[] = is_object($row) ? $row : (object) $row;
		}

		return $out;
	}

	public function get_discountTotalAndStatusPrint($ordersData,$estados,$lang_order,$request_post){

		$list = [];
		$lang_order = $this->lang_order;
		if (empty($lang_order)) {
			$this->load->model('HelperLanguage');
			$lang_order = $this->HelperLanguage->lang_order();
		}

		foreach ($ordersData as $order) {
			$row = [];
			$estado_print = null;
			foreach($estados as $estado){
				if ($estado->cod_status == $order->order_status) {
					$estado_print = $estado->name_status;
					break;
				}
			}

			array_push($row,$order->order_number,$order->order_id,$order->sku,$order->name);

			if(isset($request_post['show_stock_erp'])) array_push($row,$order->stock_erp);

			array_push($row,
							($order->qty <= 1)?$lang_order["no_counted"]:floatval($order->qty),
							$order->created,
							$estado_print,
							$order->deposito_asignado);

			if(isset($request_post['show_comment'])) array_push($row,$order->comments);
			array_push($list,$row);
		}
		return $list;
	}

}
