<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
use Twilio\Rest\Client;

use Dompdf\Dompdf;

/**
 * @property HelperEmail $HelperEmail
 * @property Langmodel $langmodel
 * @property TokenModel $TokenModel
 * @property HelperLanguage $HelperLanguage
 */
class Catalog extends Company_Controller
{
	use IsCRUD;

	protected $canChangeStatus = false; // El switch está en la columna status, no en columna separada

	public ?int $company;
	public ?int $langId;
	public $lang_catalog = array();
	public $lang_prods = array();
	public $lang_def = array();
	public $errores_producto = array();
	public $lang_labels = array();
	public $company_info = array();
	public $format_numeric = array();
	public $currency = array();

	/** @var array<string, string> */
	protected $_studioPdfDataUriMap = [];

	/** @var bool Si false, no descarga imágenes por HTTP (catálogos grandes). */
	protected $_studioPdfAllowRemoteImages = true;

	/** Máximo de productos por PDF de catálogo (preview web sin tope). */
	protected const STUDIO_PDF_MAX_PRODUCTS = 100;

	public function __construct()
	{
		parent::__construct();
		$this->checkLoggedInOrLoginWithRedirect();
		// HelperLanguage solo lee traducciones vía langmodel (API); no consulta BD.
		$this->load->model('HelperLanguage');

		$this->company = (int) $this->getCompanyId();
		$this->langId = $this->resolveCompanyPanelLangId();

		$companyInfo = $this->getObjectFromApi("company/{$this->company}");
		$this->company_info = $this->hasError($companyInfo) ? (object) [] : $companyInfo;
		if (isset($this->company_info->language_id)) {
			$companyLangId = (int) $this->company_info->language_id;
			if ($companyLangId >= 1 && $companyLangId <= 7) {
				$this->langId = $companyLangId;
				$this->persistCompanyPanelLanguage($this->company, $companyLangId);
			}
		}

		$this->lang_catalog = $this->dictionarySection('cmp-catalogo');
		$this->lang_prods = $this->dictionarySection('cmp-products');
		$this->lang_def = $this->dictionarySection('comp-login-head');
		$this->errores_producto = $this->dictionarySection('errores_producto');
		$this->lang_labels = $this->dictionarySection('comp-csv-flds');

		$formatNumeric = $this->getObjectFromApi("company/{$this->company}/format-numeric");
		if ($this->hasError($formatNumeric)) {
			$formatNumeric = $this->getObjectFromApi('formatnumeric/default');
		}
		$this->format_numeric = $this->hasError($formatNumeric) ? (object) [] : $formatNumeric;

		$this->currency = $this->getCurrency();

		// Inicializar CRUD
		$this->initCrud('catalog', 'company/catalog', 'catalogs');

		require_once FCPATH . 'vendor/autoload.php';
	}

	/**
	 * Diccionario instalado en api/lang/*.json. El JSON del idioma de la empresa
	 * pisa lo que devuelva la API para que Save no quede en inglés.
	 *
	 * @return array<string, mixed>
	 */
	private function dictionarySection(string $section): array
	{
		$fromFile = fv_lang_json_flat_section($this->langId, $section);
		$fromApi = [];
		try {
			$api = $this->langmodel->getMdl($section, $this->langId);
			if (is_array($api)) {
				$fromApi = $api;
			}
		} catch (\Throwable $e) {
			log_message('error', 'Catalog dictionary ' . $section . ': ' . $e->getMessage());
		}

		return array_merge($fromApi, $fromFile);
	}

	/**
	 * Normaliza una respuesta de la API a un arreglo de items.
	 *
	 * @param mixed $response
	 * @return array<int, mixed>
	 */
	private function apiList($response): array
	{
		if ($this->hasError($response)) {
			return [];
		}
		if (is_array($response)) {
			if (isset($response['data']) && is_array($response['data'])) {
				return $response['data'];
			}
			return $response;
		}
		if (is_object($response)) {
			if (isset($response->data) && is_array($response->data)) {
				return $response->data;
			}
			return [$response];
		}

		return [];
	}

	/**
	 * Extrae el recurso individual de una respuesta API (POST/PUT) con wrapper `data`.
	 *
	 * @param mixed $response
	 * @return array|object|null
	 */
	private function extractApiResource($response)
	{
		if ($this->hasError($response)) {
			return null;
		}
		if (is_array($response)) {
			return $response['data'] ?? $response;
		}
		if (is_object($response)) {
			return $response->data ?? $response;
		}

		return $response;
	}

	/**
	 * Obtiene el ID numérico de un recurso devuelto por la API.
	 */
	private function extractResourceId($resource, string $field = 'catalog_id'): int
	{
		if (is_object($resource)) {
			return (int) ($resource->{$field} ?? 0);
		}
		if (is_array($resource)) {
			return (int) ($resource[$field] ?? 0);
		}

		return 0;
	}

	/**
	 * Categorías seleccionadas en el formulario (ordenador o dual-listbox).
	 *
	 * @param array<string, mixed> $request
	 * @return array<int, string>
	 */
	private function extractSelectedCategories(array $request): array
	{
		if (isset($request['categ_selected']) && is_array($request['categ_selected'])) {
			return array_values(array_filter($request['categ_selected'], static fn($v) => $v !== '' && $v !== null));
		}
		if (isset($request['categories']) && is_array($request['categories'])) {
			return array_values(array_filter($request['categories'], static fn($v) => $v !== '' && $v !== null));
		}

		return [];
	}

	/**
	 * Símbolo de moneda de la empresa (API Currency usa `symbol`; vistas legacy usan `Symbol`).
	 */
	private function currencySymbol(): string
	{
		if (is_object($this->currency)) {
			return (string) ($this->currency->symbol ?? $this->currency->Symbol ?? '');
		}
		return '';
	}

	/**
	 * Pool de categorías con productos (no por defecto) para el selector de catálogo.
	 *
	 * @return array<int, object>
	 */
	private function fetchCategoriesPool(int $companyId): array
	{
		// Todas las categorías activas (no solo las que tienen productos): el FullVendor Catalog
		// se basa en categories activas y los nombres deben resolverse aunque qty=0.
		$rows = $this->apiList($this->getObjectFromApi("company/{$companyId}/categories", [
			'status' => 1,
		]));
		$pool = [];
		foreach ($rows as $row) {
			$catId = (int) (is_object($row) ? ($row->cat_id ?? 0) : ($row['cat_id'] ?? 0));
			if ($catId <= 0) {
				continue;
			}
			$default = (int) (is_object($row) ? ($row->category_default ?? 0) : ($row['category_default'] ?? 0));
			if ($default === 1) {
				continue; // la categoría "sin categoría" no se edita en el studio
			}
			$name = is_object($row) ? ($row->category_name ?? '') : ($row['category_name'] ?? '');
			$qty = is_object($row) ? ($row->products_count ?? $row->qty_products ?? 0) : ($row['products_count'] ?? $row['qty_products'] ?? 0);
			$pool[] = (object) [
				'cat_id' => $catId,
				'category_name' => $name !== '' ? $name : (string) $catId,
				'qty_products' => (int) $qty,
				'category_default' => $default,
			];
		}
		return $pool;
	}

	/**
	 * Convierte IDs de categoría al campo legacy `cat_id` (CSV).
	 *
	 * @param array<int|string> $categoryIds
	 */
	private function buildCatIdField(array $categoryIds): string
	{
		$ids = array_filter(array_map('intval', $categoryIds), static fn($id) => $id > 0);

		return implode(',', $ids);
	}

	/**
	 * Categorías seleccionadas de un catálogo (tabla catalog_category) con su orden, vía API.
	 *
	 * @return array<int, object>
	 */
	private function fetchCatalogCategories(int $catalogId): array
	{
		$rows = $this->apiList($this->getDataFromApi("catalog/{$catalogId}/categories"));
		$result = [];
		$order = 1;
		foreach ($rows as $row) {
			$catId = is_object($row) ? ($row->cat_id ?? null) : ($row['cat_id'] ?? null);
			$default = is_object($row) ? ($row->category_default ?? null) : ($row['category_default'] ?? null);
			if ((int) $default === 1) {
				continue; // No mostrar la categoría por defecto en el editor
			}
			$name = is_object($row) ? ($row->category_name ?? '') : ($row['category_name'] ?? '');
			$qty = is_object($row) ? ($row->qty_products ?? 0) : ($row['qty_products'] ?? 0);
			$result[] = (object) [
				'catalog_id' => $catalogId,
				'category_id' => $catId,
				'cat_id' => $catId,
				'category_name' => $name,
				'qty_products' => (int) $qty,
				'order' => $order++,
			];
		}
		return $result;
	}

	protected function paginationUrl($url = '')
	{
		return company_url('catalog' . $url);
	}

	public function index()
	{
		// Cargar el archivo JavaScript para la alerta de eliminación genérica
		$this->addJsFile(base_url('companyasset/company/common/delete-alert.js'));
		$this->addJsFile(base_url('companyasset/company/catalog/catalog-product-select2.js'));
		$this->addJsFile(base_url('companyasset/company/catalog/catalog-preview-engine.js'));
		$this->addJsFile(base_url('companyasset/company/catalog/catalog-list-preview.js'));
		$this->addJsFile(base_url('companyasset/company/catalog/catalog-list-download.js'));
		$this->list();
	}

	public function lists()
	{
		// Cargar el archivo JavaScript para la alerta de eliminación genérica
		$this->addJsFile(base_url('companyasset/company/common/delete-alert.js'));
		$this->addJsFile(base_url('companyasset/company/catalog/catalog-product-select2.js'));
		$this->addJsFile(base_url('companyasset/company/catalog/catalog-preview-engine.js'));
		$this->addJsFile(base_url('companyasset/company/catalog/catalog-list-preview.js'));
		$this->addJsFile(base_url('companyasset/company/catalog/catalog-list-download.js'));
		$this->list();
	}
	
	public function formats()
	{
		// Cargar el archivo JavaScript para la alerta de eliminación genérica
		$this->addJsFile(base_url('companyasset/company/common/delete-alert.js'));
		// Cargar la página de catálogos con el modal abierto automáticamente
		// Pasar un parámetro para indicar que se debe abrir el modal
		$_GET['open_formats_modal'] = true;
		$this->list();
	}

	protected function initListButtonBar()
	{
		$this->listButtonBar = new ButtonBar();
		$this->listButtonBar->addLink($this->lang_catalog['add_Catalog'] ?? '', company_url('catalog/add'), 'mdi mdi-plus-circle');
		// Formats: el HTML se carga una sola vez más abajo en list.php (evita #formatsModal duplicado).
		$this->listButtonBar->addModalButton($this->lang_catalog['formats'] ?? '', '#formatsModal', null, 'mdi mdi-file-document-edit');
	}

	protected function initItemActions()
	{
		$langtoltip = $this->getModuleLang('cmp-toltip');
		$lang_catalog = is_array($this->lang_catalog) ? $this->lang_catalog : [];
		$lang_optionbasic = $this->HelperLanguage->option_basic();
		if (!is_array($lang_optionbasic)) {
			$lang_optionbasic = [];
		}

		$downloadLabel = $lang_catalog['download'] ?? $lang_optionbasic['download'] ?? $lang_optionbasic['descargar'] ?? '';
		$performanceLabel = $lang_catalog['performance'] ?? $lang_optionbasic['performance'] ?? '';
		$sendLabel = $lang_catalog['send_catalog'] ?? '';

		$this->addItemAction('download', $downloadLabel, 'javascript:void(0);', 'mdi mdi-download', 'info');
		$this->addItemAction('performance', $performanceLabel, 'javascript:void(0);', 'mdi mdi-chart-line', 'info');
		$this->addItemAction('send_catalog', $sendLabel, 'javascript:void(0);', 'mdi mdi-send', 'info');
		$this->addItemAction('view', $langtoltip['view'] ?? '', 'javascript:void(0);', 'mdi mdi-eye');
		$this->addItemAction('edit', $langtoltip['edit'] ?? '', company_url('catalog/edit/'), 'mdi mdi-pencil', 'warning');
		$this->addItemAction('delete', $langtoltip['delete'] ?? '', company_url('catalog/delete/'), 'mdi mdi-delete', 'go-delete btn-warning');
	}

	protected function getActionButtonTemplates(): array
	{
		return [
			// Usar templates específicos solo cuando tienen lógica especial
			'download' => 'company/catalog/list/download_button',
			'view' => 'company/catalog/list/view_button',
			'performance' => 'company/catalog/list/performance_button',
			'send_catalog' => 'company/catalog/list/send_catalog_button',
			// Usar botón genérico para acciones simples
			'edit' => 'company/catalog/list/action_button',
		];
	}

	protected function getPageTexts($pageContentObject = null): array
	{
		$lang = is_array($this->lang_catalog) ? $this->lang_catalog : [];
		$pageTitle = trim((string) (
			$lang['pageTitle']
			?? $lang['list_page_title']
			?? $lang['catalogs']
			?? $lang['catalog']
			?? ''
		));

		$texts = [
			'pageTitle' => $pageTitle,
			'title' => $pageTitle !== '' ? $pageTitle : ($lang['list_catalog'] ?? $lang['catalogs'] ?? ''),
			'company_total_items_label' => $lang['total_catalogs'] ?? '',
			'cantidad_productos' => $lang['total_catalogs_found'] ?? '',
			'entityNamePlural' => $lang['catalogs'] ?? '',
			'entityName' => $lang['catalog'] ?? '',
			'fields' => [
				'catalog_name' => $lang['catalog_name'] ?? $lang['nombre_catalogo'] ?? '',
				'created' => $lang['created'] ?? '',
				'status' => $lang['status_table'] ?? ($lang['status'] ?? ''),
			],
			'colmn_action' => $lang['colmn_action'] ?? '',
		] + $lang;

		$langErrors = $this->HelperLanguage->errores_catalog();
		if (is_array($langErrors)) {
			$texts = $texts + $langErrors;
		}

		return $texts;
	}

	protected function getListItemFields($list)
	{
		return [
			'catalog_name',
			'created',
			'status',
		];
	}

	protected function getFieldTemplates(): array
	{
		return [
			'catalog_name' => 'company/catalog/list/catalog_name_field',
			'created' => 'company/catalog/list/created_field',
			'status' => 'components/crud/enable-disable_button',
		];
	}

	protected function getItemIdField(): string
	{
		return 'catalog_id';
	}

	protected function getStatusField(): string
	{
		return 'status';
	}

	protected function getFilterData(): array
	{
		return [
			'props' => ['catalog_id', 'catalog_name', 'status', 'created', 'default_catalog'],
			'input_todos' => $this->input->get('input_todos') ?? '',
			'sort' => $this->input->get('sort') ?? 'catalog_id',
			'orderDirection' => $this->input->get('orderDirection') ?? 'desc',
		];
	}

	protected function getPaginatedList(string $resource, array $filter = [], $page = null, $perPage = null)
	{
		// Obtener company_id directamente de la sesión (como en producción)
		$companyId = $this->session->userdata('compId');
		if (!$companyId) {
			$companyId = $this->getCompany();
		}
		
		// Log para depuración
		log_message('error', 'CATALOG getPaginatedList - company_id from session: ' . ($this->session->userdata('compId') ?? 'NULL'));
		log_message('error', 'CATALOG getPaginatedList - company_id final: ' . ($companyId ?? 'NULL'));
		
		$params = array_merge(
			$filter,
			[
				'page' => $page ?? 1,
				'per_page' => $perPage ?? 25,
				'props' => ['catalog_id', 'catalog_name', 'status', 'created', 'default_catalog'],
				'all' => 1, // Incluir todos los catálogos, incluso default_catalog
			]
		);
		$sort = $this->input->get('sort');
		if (isset($sort)) {
			$params['orderProperty'] = $sort;
		}
		
		// No filtrar por status para mostrar todos los catálogos (activos e inactivos)
		// El filtro de status se manejará en el frontend si es necesario
		
		try {
			$result = $this->getObjectFromApi("company/{$companyId}/catalogs", $params);
			
			// Verificar si hay error (puede ser array u objeto)
			$hasError = false;
			if (is_array($result) && isset($result['error'])) {
				$hasError = true;
				log_message('error', 'CATALOG_QUERY ERROR (array) - URL: company/' . $companyId . '/catalogs | Error: ' . json_encode($result));
			} elseif (is_object($result) && isset($result->error)) {
				$hasError = true;
				log_message('error', 'CATALOG_QUERY ERROR (object) - URL: company/' . $companyId . '/catalogs | Error: ' . json_encode($result));
			} elseif ($this->hasError($result)) {
				$hasError = true;
				log_message('error', 'CATALOG_QUERY ERROR (hasError) - URL: company/' . $companyId . '/catalogs | Error: ' . json_encode($result));
			}
			
			if ($hasError) {
				return (object)[
					'data' => [],
					'total' => 0,
					'per_page' => $perPage ?? 25,
					'current_page' => $page ?? 1,
				];
			}
			
			// Log simple para depuración - mostrar resultado completo
			$resultJson = json_encode($result);
			log_message('error', 'CATALOG_QUERY - URL: company/' . $companyId . '/catalogs | Params: ' . json_encode($params) . ' | Result type: ' . gettype($result));
			
			// Log más detallado del resultado
			if (is_object($result)) {
				log_message('error', 'CATALOG_QUERY RESULT - Has data: ' . (isset($result->data) ? 'YES' : 'NO') . ' | Data type: ' . (isset($result->data) ? gettype($result->data) : 'N/A') . ' | Total: ' . (isset($result->total) ? $result->total : 'N/A'));
				if (isset($result->data) && is_array($result->data)) {
					log_message('error', 'CATALOG_QUERY DATA COUNT: ' . count($result->data) . ' | First item: ' . (count($result->data) > 0 ? json_encode($result->data[0]) : 'EMPTY'));
				} elseif (isset($result->data) && is_object($result->data)) {
					log_message('error', 'CATALOG_QUERY DATA - Is object, checking methods...');
					if (method_exists($result->data, 'toArray')) {
						$dataArray = $result->data->toArray();
						log_message('error', 'CATALOG_QUERY DATA COUNT (from toArray): ' . count($dataArray));
					}
				}
			} elseif (is_array($result)) {
				log_message('error', 'CATALOG_QUERY RESULT - Is array, count: ' . count($result) . ' | First item: ' . (count($result) > 0 ? json_encode($result[0]) : 'EMPTY'));
			}
			
			// Si el resultado es un array (puede pasar si la API devuelve directamente un array)
			if (is_array($result)) {
				$result = (object)[
					'data' => $result,
					'total' => count($result),
					'per_page' => $perPage ?? 25,
					'current_page' => $page ?? 1,
				];
			}
			
			// Asegurar que siempre sea un objeto
			if (!is_object($result)) {
				$result = (object)[
					'data' => is_array($result) ? $result : [],
					'total' => is_array($result) ? count($result) : 0,
					'per_page' => $perPage ?? 25,
					'current_page' => $page ?? 1,
				];
			}
			
			// Asegurar que siempre tenga la estructura correcta
			if (!isset($result->data)) {
				$result->data = [];
			}
			if (!isset($result->total)) {
				$result->total = 0;
			}
			if (!isset($result->per_page)) {
				$result->per_page = $perPage ?? 25;
			}
			if (!isset($result->current_page)) {
				$result->current_page = $page ?? 1;
			}
			
			// Si data no es un array, convertirlo
			if (!is_array($result->data)) {
				// Si es una colección de Laravel o un objeto iterable, convertir a array
				if (is_object($result->data) && method_exists($result->data, 'toArray')) {
					$result->data = $result->data->toArray();
				} elseif (is_object($result->data) && ($result->data instanceof \Traversable || is_iterable($result->data))) {
					$result->data = iterator_to_array($result->data);
				} else {
					$result->data = [];
				}
			}
			
			return $result;
		} catch (\Exception $e) {
			log_message('error', 'Catalog::getPaginatedList - Error: ' . $e->getMessage());
			log_message('error', 'Catalog::getPaginatedList - Trace: ' . $e->getTraceAsString());
			// Devolver estructura vacía en caso de error
			return (object)[
				'data' => [],
				'total' => 0,
				'per_page' => $perPage ?? 25,
				'current_page' => $page ?? 1,
			];
		}
	}

	protected function getListData($paginatedList, $contextualButtonBar = null): array
	{
		$paginationTexts = $this->Langmodel->getMdl('pagination');
		$pageTexts = $this->getPageTexts($paginatedList) + $paginationTexts;
		// Obtener modales del contextualButtonBar y del listButtonBar
		$contextualModals = $contextualButtonBar ? $contextualButtonBar->getModals() : [];
		$listButtonBarModals = isset($this->listButtonBar) ? $this->listButtonBar->getModals() : [];
		$modals = array_merge($this->modals ?? [], $contextualModals, $listButtonBarModals);
		
		// Asegurar que data sea un array
		$listItems = [];
		if (isset($paginatedList->data)) {
			if (is_array($paginatedList->data)) {
				$listItems = $paginatedList->data;
			} elseif (is_object($paginatedList->data) && method_exists($paginatedList->data, 'toArray')) {
				$listItems = $paginatedList->data->toArray();
			} elseif (is_object($paginatedList->data) && ($paginatedList->data instanceof \Traversable || is_iterable($paginatedList->data))) {
				$listItems = iterator_to_array($paginatedList->data);
			}
		}
		
		// Obtener los campos y filtrar catalog_id para ocultarlo de la visualización
		// Si la lista está vacía, usar los campos definidos manualmente
		if (empty($listItems) || count($listItems) === 0) {
			$allFields = ['catalog_name', 'created', 'status'];
		} else {
			$allFields = $this->getListItemFields($listItems);
			if (empty($allFields)) {
				$allFields = ['catalog_name', 'created', 'status'];
			}
		}
		$visibleFields = array_filter($allFields, function($field) {
			// Ocultar campos técnicos que no deben mostrarse en la grilla
			return !in_array($field, ['catalog_id', 'company_id', 'product_name', 'customer_emails', 'email_sent_on', 'attach', 'dayexpired', 'cabeza_pagina', 'deleted_at', 'updated_at', 'default_catalog', 'default_indicator']);
		});
		
		// Datos auxiliares para los modales (agregar/enviar/descargar) vía API.
		// Productos: no precargar todos (timeout en empresas grandes); Select2 busca por AJAX.
		$compId = (int) $this->getCompanyId() ?: $this->company;

		$product = [];
		$category = $this->fetchCategoriesPool($compId);

		$category_selected_order = []; // Array vacío para el modal de agregar
		$lang_prods = $this->lang_prods;
		$lang_labels = $this->lang_labels;
		// Usar getModuleLang para respetar idioma de la empresa (en/es) y evitar Undefined index en modal send catalog
		$lang_optionbasic = $this->getModuleLang('cmp-option_basic');
		if (empty($lang_optionbasic)) {
			$lang_optionbasic = $this->HelperLanguage->option_basic();
		}
		$lang_optionbasic = is_array($lang_optionbasic) ? $lang_optionbasic : [];

		// Inicializar editModals si no existe
		$editModals = [];

		$company = $this->company_info ?? null;
		$defaultGroupId = is_object($company) ? (int) ($company->default_customer_group_id ?? 0) : 0;

		// Grupos para los modales de enviar/descargar (mapeados al formato legacy id/name/default).
		$group_list = [];
		foreach ($this->apiList($this->getObjectFromApi("company/{$compId}/customer-groups")) as $g) {
			$gid = (int) (is_object($g) ? ($g->customer_group_id ?? 0) : ($g['customer_group_id'] ?? 0));
			$gname = is_object($g) ? ($g->name ?? '') : ($g['name'] ?? '');
			$group_list[] = (object) [
				'id' => $gid,
				'name' => $gname,
				'default' => ($defaultGroupId > 0 && $gid === $defaultGroupId) ? 1 : 0,
			];
		}

		// Clientes habilitados para el modal de enviar.
		$customerlist = $this->apiList($this->getObjectFromApi("company/{$compId}/customers", ['enabled' => 1]));

		// Formatos de la empresa para el modal de descarga.
		$format_list = [];
		foreach ($this->apiList($this->getObjectFromApi("company/{$compId}/formats")) as $f) {
			$format_list[] = (object) [
				'id' => (int) (is_object($f) ? ($f->format_id ?? 0) : ($f['format_id'] ?? 0)),
				'name' => is_object($f) ? ($f->format_name ?? '') : ($f['format_name'] ?? ''),
				'is_default' => (int) (is_object($f) ? ($f->is_default ?? 0) : ($f['is_default'] ?? 0)) === 1,
			];
		}

		// Mapas de performance/enriquecido: se cargan bajo demanda en los modales (AJAX), aquí van vacíos.
		$catalogPerformanceMap = [];
		$catalogEnrichedMap = [];
		
		$listData = [
			'pageTitle' => $pageTexts['pageTitle'] ?? $this->entityPath,
			'title' => $pageTexts['pageTitle'] ?? $pageTexts['title'] ?? $this->entityPath,
			'addItem' => null, // El botón está en buttonBar
			'totalItemsCount' => $paginatedList->total ?? 0,
			'editModals' => $editModals, // Modales de edición
			'page' => $paginatedList,
			'buttonBar' => isset($this->listButtonBar) ? $this->listButtonBar->getButtons() : [],
			'contextualButtonBar' => $contextualButtonBar ? $contextualButtonBar->getButtons() : [],
			'formFilterView' => $this->getFormFilterView(),
			'selectionExpansible' => $this->selectionExpansible,
			'pageTexts' => $pageTexts,
			'list' => $listItems, // Usar la lista procesada
			'fields' => array_values($visibleFields),
			'sortableFields' => !empty($listItems) ? $this->getSortableFields($listItems) : ['catalog_name', 'created', 'status'],
			'fieldTemplates' => $this->getFieldTemplates(),
			'actionButtonTemplates' => $this->getActionButtonTemplates(),
			'itemActions' => $this->itemActions ?? [],
			'itemIdField' => $this->getItemIdField(),
			'itemStatusField' => $this->getStatusField(),
			'modals' => $modals,
			'controllerUrl' => $this->paginationUrl(),
			'multipleSelection' => $this->hasMultipleSelection ?? false,
			'changeStatus' => $this->canChangeStatus ?? true,
			'apiToken' => $this->getUserToken(),
			// Datos para el modal de agregar catálogo
			'product' => $product,
			'category' => $category,
			'category_selected_order' => $category_selected_order,
			'lang_prods' => $lang_prods,
			'lang_labels' => $lang_labels,
			'lang_optionbasic' => $lang_optionbasic,
			// Datos para modales (performance / otros) replicados de producción
			'format_numeric' => $this->format_numeric,
			'moneda' => $this->currencySymbol(),
			'lang_modal' => $this->HelperLanguage->modal(),
			// Datos para el modal de enviar catálogo (igual que en producción)
			'group_list' => $group_list,
			'format_list' => $format_list,
			'customerlist' => $customerlist,
			'company' => $company,
			'langcatalog' => $this->lang_catalog,
			// Datos de performance pre-cargados para los modales
			'catalogPerformanceMap' => $catalogPerformanceMap,
			// Datos enriquecidos (products, categories) para modales de preview
			'catalogEnrichedMap' => $catalogEnrichedMap,
			// Company ID para console.log en frontend
			'company_id' => $this->session->userdata('compId') ?? $this->getCompany(),
			// Studio reemplaza los modales add/edit
			'disableCatalogEditModals' => true,
		];
		
		return $listData;
	}

	protected function getSortableFields($list)
	{
		return [
			'catalog_name',
			'created',
			'status'
		];
	}

	public function add()
	{
		$this->addJsFile(base_url('companyasset/js/select2.min.js'));
		$this->addJsFile($this->studioAssetUrl('companyasset/company/catalog/catalog-studio.js'));
		$this->showContent('company/catalog/studio', $this->buildStudioViewData('create'), 'company');
	}

	public function edit($catalogId = null)
	{
		$catalogId = (int) ($catalogId ?? $this->input->get('id') ?? 0);
		if ($catalogId <= 0) {
			show_404();
			return;
		}
		$data = $this->buildStudioViewData('edit', $catalogId);
		if (empty($data['catalog'])) {
			// Volúmenes «Part N» eliminados u otros IDs inválidos → catálogo default (FullVendor).
			$defaultId = $this->resolveDefaultCatalogId();
			if ($defaultId > 0 && $defaultId !== $catalogId) {
				redirect(company_url('catalog/edit/' . $defaultId));
				return;
			}
			show_404();
			return;
		}
		$this->addJsFile(base_url('companyasset/js/select2.min.js'));
		$this->addJsFile($this->studioAssetUrl('companyasset/company/catalog/catalog-studio.js'));
		$this->showContent('company/catalog/studio', $data, 'company');
	}

	/**
	 * URL de asset del studio con bust de caché por mtime.
	 */
	protected function studioAssetUrl(string $relativePath): string
	{
		$full = FCPATH . ltrim(str_replace(['\\', '..'], ['/', ''], $relativePath), '/');
		$v = is_file($full) ? (string) filemtime($full) : (string) time();
		return base_url($relativePath) . '?v=' . rawurlencode($v);
	}

	/**
	 * ID del catálogo default de la compañía (FullVendor Catalog).
	 */
	protected function resolveDefaultCatalogId(): int
	{
		$compId = (int) $this->getCompanyId() ?: (int) $this->company;
		$list = $this->getObjectFromApi('catalog', [
			'company_id' => $compId,
			'default_catalog' => 1,
			'props' => ['catalog_id', 'default_catalog'],
			'all' => 1,
		]);
		$rows = [];
		if (is_object($list) && isset($list->data) && is_array($list->data)) {
			$rows = $list->data;
		} elseif (is_array($list)) {
			$rows = $list;
		}
		foreach ($rows as $row) {
			$row = is_object($row) ? $row : (object) $row;
			if ((int) ($row->default_catalog ?? 0) === 1) {
				return (int) ($row->catalog_id ?? 0);
			}
		}
		return 0;
	}

	/**
	 * Datos del Catalog Studio (crear / editar).
	 */
	protected function buildStudioViewData(string $mode, int $catalogId = 0): array
	{
		$compId = (int) $this->getCompanyId() ?: (int) $this->company;
		$pageTexts = $this->getPageTexts(null);
		$templates = $this->loadCatalogTemplateManifest();

		$catalog = null;
		$selectedProducts = [];
		$selectedCategories = [];
		$templateKey = 'classic-grid';
		$showPrices = true;
		$themeColors = $this->defaultThemeColorsForTemplate('classic-grid');
		$isDefault = false;

		if ($mode === 'edit' && $catalogId > 0) {
			$catalog = $this->getObjectFromApi("catalog/{$catalogId}");
			if ($this->hasError($catalog) || !is_object($catalog) || !isset($catalog->catalog_id)) {
				return ['catalog' => null];
			}
			if ((int) ($catalog->company_id ?? 0) !== (int) $this->company && (int) ($catalog->company_id ?? 0) !== $compId) {
				return ['catalog' => null];
			}

			$isDefault = (int) ($catalog->default_catalog ?? 0) === 1;
			$parsed = $this->parseCatalogAttach((string) ($catalog->attach ?? ''));
			$templateKey = $parsed['template_key'];
			$showPrices = $parsed['show_prices'];
			$themeColors = [
				'banner_bg' => $parsed['banner_bg'],
				'banner_text' => $parsed['banner_text'],
				'footer_bg' => $parsed['footer_bg'],
				'footer_text' => $parsed['footer_text'],
				'accent' => $parsed['accent'],
			];

			$selectedCategories = $this->fetchCatalogCategories($catalogId);

			// FullVendor Catalog: siempre todas las categorías de la empresa (orden actual + faltantes al final).
			if ($isDefault) {
				$pool = $this->fetchCategoriesPool($compId);
				$byId = [];
				foreach ($selectedCategories as $sel) {
					$sel = is_object($sel) ? $sel : (object) $sel;
					$cid = (int) ($sel->cat_id ?? $sel->category_id ?? 0);
					if ($cid > 0) {
						$byId[$cid] = $sel;
					}
				}
				$merged = [];
				foreach ($selectedCategories as $sel) {
					$sel = is_object($sel) ? $sel : (object) $sel;
					$cid = (int) ($sel->cat_id ?? $sel->category_id ?? 0);
					if ($cid > 0) {
						$merged[] = $sel;
					}
				}
				foreach ($pool as $cat) {
					$cat = is_object($cat) ? $cat : (object) $cat;
					$cid = (int) ($cat->cat_id ?? $cat->category_id ?? 0);
					if ($cid <= 0 || isset($byId[$cid])) {
						continue;
					}
					$merged[] = (object) [
						'catalog_id' => $catalogId,
						'category_id' => $cid,
						'cat_id' => $cid,
						'category_name' => (string) ($cat->category_name ?? ''),
						'qty_products' => (int) ($cat->qty_products ?? 0),
						'order' => count($merged) + 1,
					];
					$byId[$cid] = true;
				}
				$selectedCategories = $merged;
				$selectedProducts = [];
				if (trim((string) ($catalog->catalog_name ?? '')) === '') {
					$catalog->catalog_name = 'FullVendor Catalog';
				}
			}

			if (!$isDefault && !empty($catalog->product_name)) {
				$ids = array_values(array_filter(array_map('intval', explode(',', (string) $catalog->product_name))));
				foreach ($ids as $pid) {
					$prod = $this->getObjectFromApi("product/{$pid}");
					if (!$this->hasError($prod) && is_object($prod)) {
						$selectedProducts[] = (object) [
							'pro_id' => (int) ($prod->pro_id ?? $pid),
							'sku' => (string) ($prod->sku ?? ''),
							'name' => (string) ($prod->name ?? ''),
							'sale_price' => $prod->sale_price ?? '',
						];
					} else {
						$selectedProducts[] = (object) [
							'pro_id' => $pid,
							'sku' => (string) $pid,
							'name' => '',
						];
					}
				}
			}
		}

		$companyName = '';
		$companyLogoUrl = '';
		$companyEmail = '';
		$companyPhone = '';
		$companyCell = '';
		$companyWebsite = '';
		$companyAddress = '';
		$companyCity = '';
		$companyState = '';
		$companyCountry = '';
		$companyZip = '';
		if (is_object($this->company_info)) {
			$companyName = (string) ($this->company_info->name ?? $this->company_info->company_name ?? '');
			$companyEmail = trim((string) ($this->company_info->email ?? ''));
			$companyPhone = trim((string) ($this->company_info->phone_number ?? ''));
			$companyCell = trim((string) ($this->company_info->cell_number ?? ''));
			$companyWebsite = trim((string) ($this->company_info->website ?? ''));
			$companyAddress = trim((string) ($this->company_info->full_address ?? $this->company_info->address ?? ''));
			$companyCity = trim((string) ($this->company_info->city ?? ''));
			$companyState = trim((string) ($this->company_info->state ?? ''));
			$companyCountry = trim((string) ($this->company_info->country ?? ''));
			$companyZip = trim((string) ($this->company_info->zip_code ?? ''));
			if (!empty($this->company_info->image) && function_exists('company_logo_url')) {
				$companyLogoUrl = (string) company_logo_url((string) $this->company_info->image);
				// Dompdf / fetch necesitan esquema absoluto.
				if (strpos($companyLogoUrl, '//') === 0) {
					$companyLogoUrl = 'https:' . $companyLogoUrl;
				}
			}
		}

		$catalogLogoUuid = '';
		$catalogLogoUrl = '';
		if (is_object($catalog) && !empty($catalog->logo)) {
			$catalogLogoUuid = (string) $catalog->logo;
			$catalogLogoUrl = $this->resolveCatalogLogoUrl($catalogLogoUuid);
		}

		return [
			'pageTitle' => $pageTexts['pageTitle'] ?? ($this->lang_catalog['catalogs'] ?? ''),
			'title' => $mode === 'edit'
				? ($this->lang_catalog['studio_edit_title'] ?? $this->lang_catalog['edit_Catalog'] ?? '')
				: ($this->lang_catalog['studio_create_title'] ?? $this->lang_catalog['add_Catalog'] ?? ''),
			'page' => 'catalog',
			'pageTexts' => $pageTexts,
			'langcatalog' => $this->lang_catalog,
			'category' => $this->fetchCategoriesPool($compId),
			'templates' => $templates,
			'colorPresets' => $this->loadCatalogColorPresets(),
			'themeColors' => $themeColors,
			'catalog' => $catalog,
			'selectedProducts' => $selectedProducts,
			'selectedCategories' => $selectedCategories,
			'mode' => $mode,
			'isDefault' => $isDefault,
			'templateKey' => $templateKey,
			'showPrices' => $showPrices,
			'companyName' => $companyName !== '' ? $companyName : 'FullVendor',
			'companyLogoUrl' => $companyLogoUrl,
			'companyEmail' => $companyEmail,
			'companyPhone' => $companyPhone,
			'companyCell' => $companyCell,
			'companyWebsite' => $companyWebsite,
			'companyAddress' => $companyAddress,
			'companyCity' => $companyCity,
			'companyState' => $companyState,
			'companyCountry' => $companyCountry,
			'companyZip' => $companyZip,
			'catalogLogoUuid' => $catalogLogoUuid,
			'catalogLogoUrl' => $catalogLogoUrl,
			'saveUrl' => $mode === 'edit'
				? company_url('catalog/update/' . $catalogId)
				: company_url('catalog/create'),
			'saveCategoriesUrl' => company_url('catalog/studioSaveCategories'),
			'backUrl' => company_url('catalog'),
			'templatesBaseUrl' => base_url('companyasset/company/catalog/templates'),
			'productSearchUrl' => company_url('products/searchProductSelect2'),
			'orderSearchUrl' => company_url('catalog/studioSearchOrders'),
			'orderProductsUrl' => company_url('catalog/studioOrderProducts'),
			'excelImportUrl' => company_url('catalog/studioImportSkus'),
			'excelTemplateUrl' => company_url('catalog/studioSkuTemplate'),
			'previewUrl' => company_url('catalog/studioPreview'),
			'exportPdfUrl' => company_url('catalog/studioExportPdf'),
			'suggestMessageUrl' => company_url('catalog/suggestFeaturedMessage'),
		];
	}

	/**
	 * Resuelve URL pública del logo del catálogo (UUID de images).
	 */
	private function resolveCatalogLogoUrl(?string $uuid): string
	{
		$uuid = trim((string) $uuid);
		if ($uuid === '') {
			return '';
		}
		if (preg_match('/^([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/i', $uuid, $m)) {
			$uuid = $m[1];
		}

		// Preferir archivo local (uploads/…) cuando la imagen está en images_local.
		$imageRecord = $this->getObjectFromApi('image/' . $uuid);
		if ($this->hasError($imageRecord) || !is_object($imageRecord)) {
			$imageRecord = $this->getObjectFromApi('images/' . $uuid);
		}
		if (!$this->hasError($imageRecord) && is_object($imageRecord)) {
			$path = trim((string) ($imageRecord->path ?? ''));
			if ($path !== '' && defined('FCPATH')) {
				$local = rtrim((string) FCPATH, '/') . '/uploads/' . ltrim($path, '/');
				if (is_file($local) && is_readable($local)) {
					return $local;
				}
			}
			if (function_exists('fv_list_image_urls')) {
				$list = fv_list_image_urls($uuid, $imageRecord);
				$primary = trim((string) ($list['primary'] ?? ''));
				if ($primary !== '') {
					return $primary;
				}
			}
		}

		if (function_exists('fv_list_image_urls')) {
			$list = fv_list_image_urls($uuid);
			$primary = trim((string) ($list['primary'] ?? ''));
			if ($primary !== '') {
				return $primary;
			}
		}
		if (function_exists('company_url')) {
			return company_url('image/' . rawurlencode($uuid));
		}

		return '';
	}

	/**
	 * Sube logo del catálogo a la API de imágenes. Devuelve UUID o null.
	 */
	private function uploadCatalogLogoFromRequest(): ?string
	{
		if (empty($_FILES['logo']['tmp_name']) || !is_uploaded_file($_FILES['logo']['tmp_name'])) {
			return null;
		}
		$tmpName = $_FILES['logo']['tmp_name'];
		$origName = (string) ($_FILES['logo']['name'] ?? 'logo.jpg');
		$multipart = [
			[
				'name' => 'contents',
				'contents' => fopen($tmpName, 'r'),
				'filename' => $origName,
			],
			[
				'name' => 'name',
				'contents' => $origName,
			],
			[
				'name' => 'company_id',
				'contents' => $this->company,
			],
			[
				'name' => 'type',
				'contents' => 'catalog',
			],
		];
		try {
			$image = $this->apiRequest('images', 'POST', ['multipart' => $multipart], true);
		} catch (\Throwable $e) {
			log_message('error', 'Catalog::uploadCatalogLogoFromRequest - ' . $e->getMessage());
			return null;
		}
		if ($this->hasError($image)) {
			log_message('error', 'Catalog::uploadCatalogLogoFromRequest API error: ' . json_encode($image));
			return null;
		}
		$data = is_array($image) ? ($image['data'] ?? $image) : [];
		if (is_object($data)) {
			$data = (array) $data;
		}
		$uuid = trim((string) ($data['uuid'] ?? $data['image'] ?? ''));
		if ($uuid !== '' && preg_match('/^[0-9a-f-]{36}$/i', $uuid)) {
			return $uuid;
		}

		return null;
	}

	/**
	 * Aplica logo subido / limpieza al payload de create/update.
	 *
	 * @param array<string, mixed> $payload
	 * @param array<string, mixed> $request
	 * @return array<string, mixed>
	 */
	private function applyCatalogLogoToPayload(array $payload, array $request): array
	{
		// clear_logo llega como "0"/"1". En PHP empty("0") === true, así que NO usar empty().
		$clearRaw = $request['clear_logo'] ?? '0';
		$clearLogo = $clearRaw === true || $clearRaw === 1 || $clearRaw === '1';
		if ($clearLogo) {
			$payload['logo'] = null;
			return $payload;
		}
		$uploaded = $this->uploadCatalogLogoFromRequest();
		if ($uploaded !== null) {
			$payload['logo'] = $uploaded;
			return $payload;
		}
		// No reenviar logo_uuid en cada save: si el UUID ya no está en `images`,
		// la FK catalog.logo rompe el update en producción. El logo existente se conserva.

		return $payload;
	}

	/**
	 * URL de imagen de producto para preview/PDF del catálogo.
	 * Siempre /api/v2/images/{uuid} (disco images_local / MinIO). Nunca uploads/products/…
	 * que en web.fullvendor.com suele 404 aunque el path en BD sea products/{companyId}/file.jpg.
	 *
	 * @param object|array $prod
	 */
	private function studioProductImageUrl($prod, string $fallback = ''): string
	{
		$prodObj = is_object($prod) ? $prod : (object) (array) $prod;
		$uuid = $this->studioResolveProductImageUuid($prodObj);

		$url = '';
		if ($uuid !== '') {
			if (function_exists('fv_api_image_url')) {
				$url = trim((string) fv_api_image_url($uuid));
			}
			if ($url === '' && function_exists('base_url')) {
				$url = rtrim((string) base_url(), '/') . '/api/v2/images/' . rawurlencode($uuid);
			}
		}
		if ($url === '') {
			$url = $fallback !== ''
				? $fallback
				: (function_exists('catalog_public_no_image_url') ? catalog_public_no_image_url() : base_url('uploads/noimg.png'));
		}
		if ($url !== '' && !preg_match('#^(https?:)?//#i', $url) && function_exists('base_url')) {
			$url = rtrim((string) base_url(), '/') . '/' . ltrim($url, '/');
		}

		return $url;
	}

	/**
	 * Extrae UUID de imagen como en product/partials/thumbnail.php.
	 *
	 * @param object $prod
	 */
	private function studioResolveProductImageUuid(object $prod): string
	{
		$resolveUuid = static function ($value): string {
			if ($value === null || $value === '') {
				return '';
			}
			if (is_object($value) && isset($value->uuid) && $value->uuid !== '') {
				return (string) $value->uuid;
			}
			if (is_array($value) && !empty($value['uuid'])) {
				return (string) $value['uuid'];
			}
			$s = trim((string) $value);
			if ($s === '') {
				return '';
			}
			if (preg_match('#/images/([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})#i', $s, $m)) {
				return $m[1];
			}
			if (preg_match('/^([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/i', $s, $m)) {
				return $m[1];
			}

			return '';
		};

		$candidates = [
			$prod->default_image ?? null,
			$prod->default_image_id ?? null,
			$prod->Img ?? null,
			$prod->first_ordered_image ?? null,
			$prod->product_image ?? null,
			$prod->image_uuid ?? null,
		];
		foreach ($candidates as $value) {
			$uuid = $resolveUuid($value);
			if ($uuid !== '') {
				return $uuid;
			}
		}

		$rows = $prod->product_images ?? null;
		if ($rows !== null && $rows !== '') {
			if (is_object($rows) && isset($rows->data)) {
				$rows = $rows->data;
			}
			$list = [];
			foreach ((array) $rows as $pi) {
				$list[] = is_object($pi) ? $pi : (object) (array) $pi;
			}
			usort($list, static function ($a, $b) {
				return ((int) ($a->img_order ?? 999999)) <=> ((int) ($b->img_order ?? 999999));
			});
			foreach ($list as $pi) {
				if (!empty($pi->image)) {
					$img = is_object($pi->image) ? $pi->image : (object) (array) $pi->image;
					$uuid = $resolveUuid($img);
					if ($uuid !== '') {
						return $uuid;
					}
				}
				$uuid = $resolveUuid($pi->image_id ?? null);
				if ($uuid !== '') {
					return $uuid;
				}
			}
		}

		if (!empty($prod->images)) {
			$images = is_array($prod->images)
				? $prod->images
				: (is_object($prod->images) ? (array) $prod->images : []);
			foreach ($images as $image) {
				$uuid = $resolveUuid($image);
				if ($uuid !== '') {
					return $uuid;
				}
			}
		}

		if (function_exists('company_product_image_uuid')) {
			return (string) company_product_image_uuid($prod);
		}

		return '';
	}

	/**
	 * URL de imagen para categoría / grupo / subgrupo (thumb).
	 *
	 * @param object|array<string, mixed>|null $entity
	 */
	private function studioTaxonomyImageUrl($entity): string
	{
		if ($entity === null) {
			return '';
		}
		$obj = is_object($entity) ? $entity : (object) (array) $entity;
		$uuid = '';
		if (!empty($obj->image_uuid) && is_string($obj->image_uuid)) {
			if (preg_match('/^([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/i', $obj->image_uuid, $m)) {
				$uuid = $m[1];
			}
		}
		if ($uuid === '' && !empty($obj->image)) {
			if (is_string($obj->image) && preg_match('/^([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/i', $obj->image, $m)) {
				$uuid = $m[1];
			} elseif (is_object($obj->image) && !empty($obj->image->uuid)) {
				$uuid = (string) $obj->image->uuid;
			} elseif (is_array($obj->image) && !empty($obj->image['uuid'])) {
				$uuid = (string) $obj->image['uuid'];
			}
		}
		if ($uuid === '' && !empty($obj->imageRecord)) {
			$rec = is_object($obj->imageRecord) ? $obj->imageRecord : (object) (array) $obj->imageRecord;
			if (!empty($rec->uuid)) {
				$uuid = (string) $rec->uuid;
			}
		}
		$url = '';
		if ($uuid !== '' && function_exists('company_product_image_url')) {
			$url = trim((string) company_product_image_url($uuid, 200));
		}
		if ($url === '' && function_exists('fv_category_image_urls')) {
			$urls = fv_category_image_urls($entity);
			if (!empty($urls['has_image'])) {
				$thumb = trim((string) ($urls['thumb'] ?? ''));
				if ($thumb !== '') {
					$url = $thumb;
				} else {
					$preview = trim((string) ($urls['preview'] ?? ''));
					if ($preview !== '') {
						$url = $preview;
					}
				}
			}
		}
		if ($url !== '' && !preg_match('#^(https?:)?//#i', $url) && function_exists('base_url')) {
			$url = rtrim((string) base_url(), '/') . '/' . ltrim($url, '/');
		}

		return $url;
	}

	/**
	 * Extrae IDs de relación (array de objetos/arrays) o un ID escalar.
	 *
	 * @param mixed $items
	 * @return list<int>
	 */
	private function studioRelationIds($items, string $idField): array
	{
		$ids = [];
		if (is_object($items) && isset($items->data)) {
			$items = $items->data;
		}
		if (!is_array($items)) {
			return $ids;
		}
		foreach ($items as $item) {
			if (is_object($item)) {
				$id = (int) ($item->{$idField} ?? $item->id ?? 0);
			} elseif (is_array($item)) {
				$id = (int) ($item[$idField] ?? $item['id'] ?? 0);
			} else {
				$id = (int) $item;
			}
			if ($id > 0) {
				$ids[] = $id;
			}
		}

		return array_values(array_unique($ids));
	}

	/**
	 * Arma una sección de preview: categoría (+foto) → grupos → subgrupos → productos.
	 *
	 * @param callable $mapProduct
	 * @param array<int, bool> $seen
	 * @param list<array<string, mixed>> $flat
	 * @return array<string, mixed>
	 */
	private function buildStudioCategorySection(
		int $catId,
		string $nameHint,
		int $order,
		int $maxPerCat,
		callable $mapProduct,
		array &$seen,
		array &$flat
	): array {
		$catName = strtoupper(trim($nameHint));
		$catImage = '';
		$cat = $this->getObjectFromApi("category/{$catId}", [
			'relations' => ['imageRecord'],
		]);
		if (!$this->hasError($cat) && is_object($cat)) {
			if ($catName === '') {
				$catName = strtoupper((string) ($cat->category_name ?? ''));
			}
			$catImage = $this->studioTaxonomyImageUrl($cat);
		}

		$groupsTree = [];
		$groupIndex = [];
		$subgroupToGroup = [];
		$groupsResponse = $this->getDataFromApi("category/{$catId}/groups", [], true);
		$groupsRaw = [];
		if (!$this->hasError($groupsResponse) && is_array($groupsResponse)) {
			$groupsRaw = $groupsResponse['data'] ?? $groupsResponse;
			if (!is_array($groupsRaw)) {
				$groupsRaw = [];
			}
		}

		usort($groupsRaw, static function ($a, $b) {
			$ao = (int) (is_object($a) ? ($a->sort_order ?? 0) : ($a['sort_order'] ?? 0));
			$bo = (int) (is_object($b) ? ($b->sort_order ?? 0) : ($b['sort_order'] ?? 0));
			if ($ao !== $bo) {
				return $ao <=> $bo;
			}
			$an = strtoupper((string) (is_object($a) ? ($a->group_name ?? '') : ($a['group_name'] ?? '')));
			$bn = strtoupper((string) (is_object($b) ? ($b->group_name ?? '') : ($b['group_name'] ?? '')));
			return $an <=> $bn;
		});

		foreach ($groupsRaw as $gRaw) {
			$g = is_object($gRaw) ? $gRaw : (object) (array) $gRaw;
			$gid = (int) ($g->group_id ?? 0);
			if ($gid <= 0) {
				continue;
			}
			$subsRaw = $g->subgroups ?? [];
			if (is_object($subsRaw) && isset($subsRaw->data)) {
				$subsRaw = $subsRaw->data;
			}
			if (!is_array($subsRaw)) {
				$subsRaw = [];
			}
			usort($subsRaw, static function ($a, $b) {
				$ao = (int) (is_object($a) ? ($a->sort_order ?? 0) : ($a['sort_order'] ?? 0));
				$bo = (int) (is_object($b) ? ($b->sort_order ?? 0) : ($b['sort_order'] ?? 0));
				if ($ao !== $bo) {
					return $ao <=> $bo;
				}
				$an = strtoupper((string) (is_object($a) ? ($a->subgroup_name ?? '') : ($a['subgroup_name'] ?? '')));
				$bn = strtoupper((string) (is_object($b) ? ($b->subgroup_name ?? '') : ($b['subgroup_name'] ?? '')));
				return $an <=> $bn;
			});

			$subgroups = [];
			foreach ($subsRaw as $sRaw) {
				$s = is_object($sRaw) ? $sRaw : (object) (array) $sRaw;
				$sid = (int) ($s->subgroup_id ?? 0);
				if ($sid <= 0) {
					continue;
				}
				$subgroupToGroup[$sid] = $gid;
				$subgroups[$sid] = [
					'subgroup_id' => $sid,
					'subgroup_name' => strtoupper(trim((string) ($s->subgroup_name ?? ''))),
					'subgroup_image' => $this->studioTaxonomyImageUrl($s),
					'sort_order' => (int) ($s->sort_order ?? 0),
					'products' => [],
				];
			}

			$groupIndex[$gid] = count($groupsTree);
			$groupsTree[] = [
				'group_id' => $gid,
				'group_name' => strtoupper(trim((string) ($g->group_name ?? ''))),
				'group_image' => $this->studioTaxonomyImageUrl($g),
				'sort_order' => (int) ($g->sort_order ?? 0),
				'subgroups' => array_values($subgroups),
				'products' => [],
			];
			// Keep a map of subgroup_id => index inside this group's subgroups for fast push
			$groupsTree[$groupIndex[$gid]]['_sub_map'] = [];
			foreach ($groupsTree[$groupIndex[$gid]]['subgroups'] as $si => $sub) {
				$groupsTree[$groupIndex[$gid]]['_sub_map'][$sub['subgroup_id']] = $si;
			}
		}

		$ungrouped = [];
		$maxFetch = max(1, (int) $maxPerCat);
		$productQuery = [
			'per_page' => $maxFetch,
			'page' => 1,
			'status' => 1,
			'catalog_preview' => 1,
		];
		// Preview web (tope alto): pedir el listado completo. PDF (tope bajo): solo la primera página.
		if ($maxPerCat >= 500) {
			$productQuery['all'] = 1;
			$productQuery['per_page'] = max($maxFetch, 40);
		}
		$rows = $this->apiList($this->getObjectFromApi("category/{$catId}/products", $productQuery));

		$countInCat = 0;
		foreach ($rows as $row) {
			$rowObj = is_object($row) ? $row : (object) (array) $row;
			$mapped = $mapProduct($rowObj);
			if ($mapped === null) {
				continue;
			}
			if (isset($seen[$mapped['id']])) {
				continue;
			}
			// Dedup global, but still bucket; display limit applied after tree walk.
			$seen[$mapped['id']] = true;
			$countInCat++;
			$flat[] = $mapped + [
				'category_id' => $catId,
				'category_name' => $catName,
				'category_order' => $order,
			];

			$subIds = $this->studioRelationIds(
				$rowObj->category_subgroups ?? $rowObj->categorySubgroups ?? [],
				'subgroup_id'
			);
			$legacySub = (int) ($rowObj->category_subgroup_id ?? 0);
			if ($legacySub > 0) {
				$subIds[] = $legacySub;
			}
			$subIds = array_values(array_unique(array_filter($subIds)));

			$groupIds = $this->studioRelationIds(
				$rowObj->category_groups ?? $rowObj->categoryGroups ?? [],
				'group_id'
			);
			$legacyGroup = (int) ($rowObj->category_group_id ?? 0);
			if ($legacyGroup > 0) {
				$groupIds[] = $legacyGroup;
			}
			$groupIds = array_values(array_unique(array_filter($groupIds)));

			$placed = false;
			foreach ($subIds as $sid) {
				if (!isset($subgroupToGroup[$sid])) {
					continue;
				}
				$gid = $subgroupToGroup[$sid];
				if (!isset($groupIndex[$gid])) {
					continue;
				}
				$gi = $groupIndex[$gid];
				$si = $groupsTree[$gi]['_sub_map'][$sid] ?? null;
				if ($si === null) {
					continue;
				}
				$groupsTree[$gi]['subgroups'][$si]['products'][] = $mapped;
				$placed = true;
				break;
			}
			if (!$placed) {
				foreach ($groupIds as $gid) {
					if (!isset($groupIndex[$gid])) {
						continue;
					}
					$gi = $groupIndex[$gid];
					$groupsTree[$gi]['products'][] = $mapped;
					$placed = true;
					break;
				}
			}
			if (!$placed) {
				$ungrouped[] = $mapped;
			}
			if ($countInCat >= $maxFetch) {
				break;
			}
		}

		$groupsOut = [];
		foreach ($groupsTree as $group) {
			unset($group['_sub_map']);
			$subsOut = [];
			foreach ($group['subgroups'] as $sub) {
				if ($sub['products'] === []) {
					continue;
				}
				$subsOut[] = $sub;
			}
			$group['subgroups'] = $subsOut;
			if ($group['products'] === [] && $group['subgroups'] === []) {
				continue;
			}
			$groupsOut[] = $group;
		}

		return $this->pruneStudioCategorySection([
			'category_id' => $catId,
			'category_name' => $catName !== '' ? $catName : ('#' . $catId),
			'category_image' => $catImage,
			'order' => $order,
			'groups' => $groupsOut,
			'products' => $ungrouped,
		], $maxPerCat);
	}

	/**
	 * Limita productos del preview priorizando grupos/subgrupos (orden de catálogo).
	 *
	 * @param array<string, mixed> $section
	 * @return array<string, mixed>
	 */
	private function pruneStudioCategorySection(array $section, int $maxPerCat): array
	{
		if ($maxPerCat < 1) {
			return $section;
		}
		$kept = 0;
		$groupsOut = [];
		foreach ($section['groups'] as $group) {
			if ($kept >= $maxPerCat) {
				break;
			}
			$subsOut = [];
			foreach ($group['subgroups'] as $sub) {
				if ($kept >= $maxPerCat) {
					break;
				}
				$prods = [];
				foreach ($sub['products'] as $product) {
					if ($kept >= $maxPerCat) {
						break;
					}
					$prods[] = $product;
					$kept++;
				}
				if ($prods !== []) {
					$sub['products'] = $prods;
					$subsOut[] = $sub;
				}
			}
			$groupProds = [];
			foreach ($group['products'] as $product) {
				if ($kept >= $maxPerCat) {
					break;
				}
				$groupProds[] = $product;
				$kept++;
			}
			$group['subgroups'] = $subsOut;
			$group['products'] = $groupProds;
			if ($group['subgroups'] !== [] || $group['products'] !== []) {
				$groupsOut[] = $group;
			}
		}
		$ungrouped = [];
		foreach ($section['products'] as $product) {
			if ($kept >= $maxPerCat) {
				break;
			}
			$ungrouped[] = $product;
			$kept++;
		}
		$section['groups'] = $groupsOut;
		$section['products'] = $ungrouped;

		return $section;
	}

	/**
	 * Preview del catálogo en el Studio: productos reales ordenados por categorías.
	 * POST category_ids[] (orden) y/o product_ids[].
	 */
	public function studioPreview()
	{
		while (ob_get_level()) {
			ob_end_clean();
		}
		header('Content-Type: application/json; charset=utf-8');

		try {
			$categoryIds = $this->input->post('category_ids');
			if ($categoryIds === null) {
				$categoryIds = $this->input->post('category_ids[]');
			}
			if (!is_array($categoryIds)) {
				$raw = (string) ($this->input->post('category_ids') ?? '');
				$categoryIds = $raw !== '' ? preg_split('/\s*,\s*/', $raw) : [];
			}
			$categoryIds = array_values(array_filter(array_map('intval', $categoryIds), static fn($id) => $id > 0));

			$productIds = $this->input->post('product_ids');
			if ($productIds === null) {
				$productIds = $this->input->post('product_ids[]');
			}
			if (!is_array($productIds)) {
				$raw = (string) ($this->input->post('product_ids') ?? '');
				$productIds = $raw !== '' ? preg_split('/\s*,\s*/', $raw) : [];
			}
			$productIds = array_values(array_filter(array_map('intval', $productIds), static fn($id) => $id > 0));

			$names = $this->input->post('category_names');
			if ($names === null) {
				$names = $this->input->post('category_names[]');
			}
			if (!is_array($names)) {
				$names = [];
			}

			$currency = $this->currencySymbol();
			$formatNumeric = $this->format_numeric;
			$noImg = function_exists('catalog_public_no_image_url') ? catalog_public_no_image_url() : base_url('uploads/noimg.png');
			if ($noImg !== '' && !preg_match('#^(https?:)?//#i', $noImg)) {
				$noImg = rtrim((string) base_url(), '/') . '/' . ltrim($noImg, '/');
			}

			$formatPrice = static function ($value) use ($currency, $formatNumeric): string {
				if ($value === null || $value === '') {
					return '';
				}
				if (function_exists('fv_format_money')) {
					return trim($currency . ' ' . fv_format_money($value, $formatNumeric, null));
				}
				$digits = is_object($formatNumeric) ? (int) ($formatNumeric->digits ?? 2) : 2;
				$radix = is_object($formatNumeric) ? (string) ($formatNumeric->radixPoint ?? '.') : '.';
				$group = is_object($formatNumeric) ? (string) ($formatNumeric->groupSeparator ?? ',') : ',';
				return trim($currency . ' ' . number_format((float) $value, $digits, $radix, $group));
			};

			$self = $this;
			$mapProduct = function ($prod) use ($formatPrice, $noImg, $self): ?array {
				$prod = is_object($prod) ? $prod : (object) $prod;
				$pid = (int) ($prod->pro_id ?? $prod->product_id ?? $prod->id ?? 0);
				if ($pid <= 0) {
					return null;
				}
				// Catálogo: solo productos enabled (status = 1).
				if (isset($prod->status) && (int) $prod->status !== 1) {
					return null;
				}
				$imageUrl = $self->studioProductImageUrl($prod, $noImg);
				$price = $prod->sale_price ?? $prod->price ?? null;
				return [
					'id' => $pid,
					'sku' => (string) ($prod->sku ?? ''),
					'name' => (string) ($prod->name ?? ''),
					'price' => $price,
					'price_fmt' => $formatPrice($price),
					'image' => $imageUrl,
					'rating' => function_exists('company_product_rating_value')
						? company_product_rating_value($prod)
						: (isset($prod->rating) && is_numeric($prod->rating) ? (float) $prod->rating : 3.5),
				];
			};

			$sections = [];
			$flat = [];
			$seen = [];

			if ($categoryIds !== []) {
				// Preview/PDF: todos los productos de cada categoría seleccionada.
				$maxCats = 200;
				$maxPerCat = 2000;
				$order = 1;
				foreach (array_slice($categoryIds, 0, $maxCats) as $idx => $catId) {
					$section = $this->buildStudioCategorySection(
						$catId,
						(string) ($names[$idx] ?? $names[(string) $catId] ?? ''),
						$order,
						$maxPerCat,
						$mapProduct,
						$seen,
						$flat
					);
					$sections[] = $section;
					$order++;
				}
			} elseif ($productIds !== []) {
				$products = [];
				foreach ($productIds as $pid) {
					$prod = $this->getObjectFromApi("product/{$pid}", [
						'relations' => ['default_image', 'product_images.image'],
					]);
					if ($this->hasError($prod)) {
						continue;
					}
					$mapped = $mapProduct($prod);
					if ($mapped === null) {
						continue;
					}
					$products[] = $mapped;
					$flat[] = $mapped;
				}
				$sections[] = [
					'category_id' => 0,
					'category_name' => '',
					'category_image' => '',
					'order' => 1,
					'groups' => [],
					'products' => $products,
				];
			}

			echo json_encode([
				'success' => true,
				'sections' => $sections,
				'products' => $flat,
				'total' => count($flat),
			], JSON_UNESCAPED_UNICODE);
		} catch (\Throwable $e) {
			log_message('error', 'Catalog::studioPreview - ' . $e->getMessage());
			echo json_encode([
				'success' => false,
				'msg' => $this->lang_catalog['filter_error'] ?? '',
				'sections' => [],
				'products' => [],
			]);
		}
	}

	/**
	 * Preview completo de un catálogo guardado (listado → modal ojo).
	 */
	public function studioPreviewCatalog($id = null)
	{
		while (ob_get_level()) {
			ob_end_clean();
		}
		header('Content-Type: application/json; charset=utf-8');

		try {
			$catalogId = (int) ($id ?? $this->input->get('id') ?? 0);
			if ($catalogId <= 0) {
				echo json_encode(['success' => false, 'msg' => $this->lang_catalog['id_required'] ?? '', 'sections' => []]);
				return;
			}

			$compId = (int) $this->getCompanyId() ?: (int) $this->company;
			$catalog = $this->getObjectFromApi("catalog/{$catalogId}");
			if ($this->hasError($catalog) || !is_object($catalog) || !isset($catalog->catalog_id)) {
				echo json_encode(['success' => false, 'msg' => $this->lang_catalog['not_found'] ?? '', 'sections' => []]);
				return;
			}
			if ((int) ($catalog->company_id ?? 0) !== (int) $this->company && (int) ($catalog->company_id ?? 0) !== $compId) {
				echo json_encode(['success' => false, 'msg' => $this->lang_catalog['not_found'] ?? '', 'sections' => []]);
				return;
			}

			$parsed = $this->parseCatalogAttach((string) ($catalog->attach ?? ''));
			$groupId = (int) ($this->input->get_post('group_id') ?? $this->input->get_post('group') ?? 0);
			$hidePriceRaw = $this->input->get_post('hide_price');
			$hidePrice = $hidePriceRaw === true || $hidePriceRaw === 1 || $hidePriceRaw === '1';
			$showPrices = $hidePrice ? false : !empty($parsed['show_prices']);

			$categories = $this->fetchCatalogCategories($catalogId);
			$categoryIds = [];
			$categoryNames = [];
			foreach ($categories as $cat) {
				$cat = is_object($cat) ? $cat : (object) $cat;
				$cid = (int) ($cat->cat_id ?? $cat->category_id ?? 0);
				if ($cid <= 0) {
					continue;
				}
				$categoryIds[] = $cid;
				$categoryNames[] = strtoupper((string) ($cat->category_name ?? $cid));
			}

			$priceByProduct = [];
			if ($groupId > 0) {
				$dlProducts = $this->apiList($this->getObjectFromApi(
					"catalog/{$catalogId}/download-products",
					['group_id' => $groupId]
				));
				foreach ($dlProducts as $dp) {
					$dp = is_object($dp) ? $dp : (object) $dp;
					$pid = (int) ($dp->pro_id ?? $dp->product_id ?? 0);
					if ($pid <= 0) {
						continue;
					}
					$priceByProduct[$pid] = [
						'price' => $dp->sale_price ?? $dp->price ?? null,
						'price_fmt' => (string) ($dp->price_formated ?? $dp->price_formatted ?? ''),
					];
				}
			}

			$currency = $this->currencySymbol();
			$formatNumeric = $this->format_numeric;
			$noImg = function_exists('catalog_public_no_image_url') ? catalog_public_no_image_url() : base_url('uploads/noimg.png');
			if ($noImg !== '' && !preg_match('#^(https?:)?//#i', $noImg)) {
				$noImg = rtrim((string) base_url(), '/') . '/' . ltrim($noImg, '/');
			}
			$formatPrice = static function ($value) use ($currency, $formatNumeric): string {
				if ($value === null || $value === '') {
					return '';
				}
				if (function_exists('fv_format_money')) {
					return trim($currency . ' ' . fv_format_money($value, $formatNumeric, null));
				}
				$digits = is_object($formatNumeric) ? (int) ($formatNumeric->digits ?? 2) : 2;
				$radix = is_object($formatNumeric) ? (string) ($formatNumeric->radixPoint ?? '.') : '.';
				$group = is_object($formatNumeric) ? (string) ($formatNumeric->groupSeparator ?? ',') : ',';
				return trim($currency . ' ' . number_format((float) $value, $digits, $radix, $group));
			};

			$self = $this;
			$mapProduct = function ($prod) use ($formatPrice, $noImg, $self, $priceByProduct): ?array {
				$prod = is_object($prod) ? $prod : (object) $prod;
				$pid = (int) ($prod->pro_id ?? $prod->product_id ?? $prod->id ?? 0);
				if ($pid <= 0) {
					return null;
				}
				if (isset($prod->status) && (int) $prod->status !== 1) {
					return null;
				}
				$imageUrl = $self->studioProductImageUrl($prod, $noImg);
				$price = $prod->sale_price ?? $prod->price ?? null;
				$priceFmt = $formatPrice($price);
				if (isset($priceByProduct[$pid])) {
					$price = $priceByProduct[$pid]['price'];
					$priceFmt = $priceByProduct[$pid]['price_fmt'] !== ''
						? $priceByProduct[$pid]['price_fmt']
						: $formatPrice($price);
				}
				return [
					'id' => $pid,
					'sku' => (string) ($prod->sku ?? ''),
					'name' => (string) ($prod->name ?? ''),
					'price' => $price,
					'price_fmt' => $priceFmt,
					'image' => $imageUrl,
					'rating' => function_exists('company_product_rating_value')
						? company_product_rating_value($prod)
						: (isset($prod->rating) && is_numeric($prod->rating) ? (float) $prod->rating : 3.5),
				];
			};

			$sections = [];
			$seen = [];
			$flat = [];
			$maxCats = 200;
			$maxPerCat = 2000;
			$order = 1;
			foreach (array_slice($categoryIds, 0, $maxCats) as $idx => $catId) {
				$section = $this->buildStudioCategorySection(
					$catId,
					(string) ($categoryNames[$idx] ?? ''),
					$order,
					$maxPerCat,
					$mapProduct,
					$seen,
					$flat
				);
				if (is_array($section)) {
					$sections[] = $section;
					$order++;
				}
			}

			$companyName = 'FullVendor';
			$companyLogoUrl = '';
			if (is_object($this->company_info)) {
				$companyName = trim((string) ($this->company_info->name ?? $this->company_info->company_name ?? '')) ?: $companyName;
				if (!empty($this->company_info->image) && function_exists('company_logo_url')) {
					$companyLogoUrl = (string) company_logo_url((string) $this->company_info->image);
				}
			}
			$catalogLogoUrl = '';
			if (!empty($catalog->logo)) {
				$catalogLogoUrl = $this->resolveCatalogLogoUrl((string) $catalog->logo);
			}
			$logoUrl = $catalogLogoUrl !== '' ? $catalogLogoUrl : $companyLogoUrl;

			echo json_encode([
				'success' => true,
				'catalog_id' => $catalogId,
				'catalog_name' => (string) ($catalog->catalog_name ?? ''),
				'company_name' => $companyName,
				'logo_url' => $logoUrl,
				'message' => (string) ($catalog->cabeza_pagina ?? ''),
				'template_key' => $parsed['template_key'],
				'show_prices' => $showPrices,
				'theme' => [
					'banner_bg' => $parsed['banner_bg'],
					'banner_text' => $parsed['banner_text'],
					'footer_bg' => $parsed['banner_bg'],
					'footer_text' => $parsed['banner_text'],
					'accent' => $parsed['accent'],
				],
				'sections' => $sections,
			], JSON_UNESCAPED_UNICODE);
		} catch (\Throwable $e) {
			log_message('error', 'Catalog::studioPreviewCatalog - ' . $e->getMessage());
			echo json_encode([
				'success' => false,
				'msg' => $this->lang_catalog['filter_error'] ?? '',
				'sections' => [],
			]);
		}
	}

	/**
	 * Exporta PDF del Catalog Studio.
	 * Mismo formato lean; cada 100 productos = un PDF.
	 * Si hay más de un volumen, descarga un ZIP con todos.
	 */
	public function studioExportPdf()
	{
		while (ob_get_level()) {
			ob_end_clean();
		}

		@ini_set('memory_limit', '1024M');
		@ini_set('max_execution_time', '900');
		@set_time_limit(900);

		try {
			$catalogName = trim((string) ($this->input->post('catalog_name') ?? 'catalog'));
			$safeName = preg_replace('/[^a-zA-Z0-9_\-]+/', '_', $catalogName) ?: 'catalog';
			$stamp = date('Ymd_His');

			$showPricesRaw = $this->input->post('show_prices');
			$showPrices = $showPricesRaw === null
				? true
				: in_array((string) $showPricesRaw, ['1', 'true', 'on'], true);

			$this->_studioPdfAllowRemoteImages = false;

			$sections = $this->studioCollectExportSections(false);
			if ($sections === []) {
				$this->_studioPdfAllowRemoteImages = true;
				header('Content-Type: application/json; charset=utf-8');
				http_response_code(400);
				echo json_encode([
					'success' => false,
					'msg' => $this->lang_catalog['studio_export_pdf_empty'] ?? '',
				], JSON_UNESCAPED_UNICODE);
				return;
			}

			$theme = [
				'banner_bg' => $this->sanitizeHexColor((string) ($this->input->post('theme_banner_bg') ?? ''), '#B91C1C'),
				'banner_text' => $this->sanitizeHexColor((string) ($this->input->post('theme_banner_text') ?? ''), '#FFFFFF'),
				'footer_bg' => $this->sanitizeHexColor((string) ($this->input->post('theme_banner_bg') ?? ''), '#B91C1C'),
				'footer_text' => $this->sanitizeHexColor((string) ($this->input->post('theme_banner_text') ?? ''), '#FFFFFF'),
			];

			$companyName = 'FullVendor';
			if (is_object($this->company_info)) {
				$companyName = trim((string) ($this->company_info->name ?? $this->company_info->company_name ?? '')) ?: $companyName;
			}
			$message = trim((string) ($this->input->post('message') ?? ''));
			$logoUrl = trim((string) ($this->input->post('logo_url') ?? ''));
			$catalogIdPost = (int) ($this->input->post('catalog_id') ?? 0);
			if ($catalogIdPost > 0) {
				$catObj = $this->getObjectFromApi('catalog/' . $catalogIdPost);
				if (!$this->hasError($catObj) && is_object($catObj) && !empty($catObj->logo)) {
					$resolvedLogo = $this->resolveCatalogLogoUrl((string) $catObj->logo);
					if ($resolvedLogo !== '') {
						$logoUrl = $resolvedLogo;
					}
				}
			}
			if ($logoUrl === '' && is_object($this->company_info) && !empty($this->company_info->image) && function_exists('company_logo_url')) {
				$logoUrl = (string) company_logo_url((string) $this->company_info->image);
			}
			if (strpos($logoUrl, '//') === 0) {
				$host = (string) (parse_url('http:' . $logoUrl, PHP_URL_HOST) ?? '');
				$scheme = (stripos($host, 'localhost') !== false
					|| stripos($host, 'laravel.test') !== false
					|| $host === '127.0.0.1')
					? 'http:'
					: 'https:';
				$logoUrl = $scheme . $logoUrl;
			}

			$metaBase = [
				'catalog_name' => $catalogName,
				'company_name' => $companyName,
				'message' => $message,
				'logo_url' => $logoUrl,
				'show_prices' => $showPrices,
				'theme' => $theme,
			];

			$this->studioStreamPdfVolumes($sections, $metaBase, $safeName, $stamp, $theme);
		} catch (\Throwable $e) {
			log_message('error', 'Catalog::studioExportPdf - ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
			header('Content-Type: application/json; charset=utf-8');
			http_response_code(500);
			echo json_encode([
				'success' => false,
				'msg' => $this->lang_catalog['studio_export_pdf_error'] ?? '',
			], JSON_UNESCAPED_UNICODE);
		}
	}

	/**
	 * Recolecta secciones (mismas reglas que studioPreview) para el PDF.
	 *
	 * @param bool $capToPdfMax Si true, limita a STUDIO_PDF_MAX_PRODUCTS (preview legacy).
	 *                           Si false, recolecta todos (export multi-volumen).
	 * @return list<array<string, mixed>>
	 */
	protected function studioCollectExportSections(bool $capToPdfMax = true): array
	{
		$categoryIds = $this->input->post('category_ids');
		if ($categoryIds === null) {
			$categoryIds = $this->input->post('category_ids[]');
		}
		if (!is_array($categoryIds)) {
			$raw = (string) ($this->input->post('category_ids') ?? '');
			$categoryIds = $raw !== '' ? preg_split('/\s*,\s*/', $raw) : [];
		}
		$categoryIds = array_values(array_filter(array_map('intval', $categoryIds), static function ($id) {
			return $id > 0;
		}));

		$productIds = $this->input->post('product_ids');
		if ($productIds === null) {
			$productIds = $this->input->post('product_ids[]');
		}
		if (!is_array($productIds)) {
			$raw = (string) ($this->input->post('product_ids') ?? '');
			$productIds = $raw !== '' ? preg_split('/\s*,\s*/', $raw) : [];
		}
		$productIds = array_values(array_filter(array_map('intval', $productIds), static function ($id) {
			return $id > 0;
		}));

		$names = $this->input->post('category_names');
		if ($names === null) {
			$names = $this->input->post('category_names[]');
		}
		if (!is_array($names)) {
			$names = [];
		}

		$catalogId = (int) ($this->input->post('catalog_id') ?? 0);
		if ($categoryIds === [] && $productIds === [] && $catalogId > 0) {
			$catalog = $this->getObjectFromApi('catalog/' . $catalogId);
			if (!$this->hasError($catalog) && is_object($catalog)) {
				$cats = $this->fetchCatalogCategories($catalogId);
				foreach ($cats as $cat) {
					$cat = is_object($cat) ? $cat : (object) $cat;
					$cid = (int) ($cat->cat_id ?? $cat->category_id ?? 0);
					if ($cid > 0) {
						$categoryIds[] = $cid;
						$names[] = strtoupper((string) ($cat->category_name ?? $cid));
					}
				}
			}
		}

		$currency = $this->currencySymbol();
		$formatNumeric = $this->format_numeric;
		$noImg = function_exists('catalog_public_no_image_url') ? catalog_public_no_image_url() : base_url('uploads/noimg.png');
		if ($noImg !== '' && !preg_match('#^(https?:)?//#i', $noImg)) {
			$noImg = rtrim((string) base_url(), '/') . '/' . ltrim($noImg, '/');
		}

		$formatPrice = static function ($value) use ($currency, $formatNumeric): string {
			if ($value === null || $value === '') {
				return '';
			}
			if (function_exists('fv_format_money')) {
				return trim($currency . ' ' . fv_format_money($value, $formatNumeric, null));
			}
			$digits = is_object($formatNumeric) ? (int) ($formatNumeric->digits ?? 2) : 2;
			$radix = is_object($formatNumeric) ? (string) ($formatNumeric->radixPoint ?? '.') : '.';
			$group = is_object($formatNumeric) ? (string) ($formatNumeric->groupSeparator ?? ',') : ',';
			return trim($currency . ' ' . number_format((float) $value, $digits, $radix, $group));
		};

		$self = $this;
		$mapProduct = function ($prod) use ($formatPrice, $noImg, $self): ?array {
			$prod = is_object($prod) ? $prod : (object) $prod;
			$pid = (int) ($prod->pro_id ?? $prod->product_id ?? $prod->id ?? 0);
			if ($pid <= 0) {
				return null;
			}
			if (isset($prod->status) && (int) $prod->status !== 1) {
				return null;
			}
			$imageUrl = $self->studioProductImageUrl($prod, $noImg);
			$price = $prod->sale_price ?? $prod->price ?? null;
			return [
				'id' => $pid,
				'sku' => (string) ($prod->sku ?? ''),
				'name' => (string) ($prod->name ?? ''),
				'price' => $price,
				'price_fmt' => $formatPrice($price),
				'image' => $imageUrl,
			];
		};

		$sections = [];
		$flat = [];
		$seen = [];
		$hardMax = $capToPdfMax ? self::STUDIO_PDF_MAX_PRODUCTS : 50000;

		if ($categoryIds !== []) {
			$order = 1;
			foreach ($categoryIds as $idx => $catId) {
				$remaining = $hardMax - count($flat);
				if ($remaining < 1) {
					break;
				}
				$section = $this->buildStudioCategorySection(
					(int) $catId,
					(string) ($names[$idx] ?? $names[(string) $catId] ?? ''),
					$order,
					$remaining,
					$mapProduct,
					$seen,
					$flat
				);
				if (is_array($section) && $this->studioCountSectionProducts($section) > 0) {
					$sections[] = $section;
					$order++;
				}
			}
		} elseif ($productIds !== []) {
			$products = [];
			foreach (array_slice($productIds, 0, $hardMax) as $pid) {
				$prod = $this->getObjectFromApi("product/{$pid}", [
					'relations' => ['default_image', 'product_images.image'],
				]);
				if ($this->hasError($prod)) {
					continue;
				}
				$mapped = $mapProduct($prod);
				if ($mapped === null) {
					continue;
				}
				$products[] = $mapped;
				if (count($products) >= $hardMax) {
					break;
				}
			}
			if ($products !== []) {
				$sections[] = [
					'category_name' => '',
					'category_image' => '',
					'groups' => [],
					'products' => $products,
				];
			}
		}

		if ($capToPdfMax) {
			return $this->studioCapSectionsProductTotal($sections, self::STUDIO_PDF_MAX_PRODUCTS);
		}

		return $sections;
	}

	/**
	 * Parte secciones en volúmenes de N productos (conserva categorías/grupos).
	 *
	 * @param list<array<string, mixed>> $sections
	 * @return list<list<array<string, mixed>>>
	 */
	protected function studioChunkSectionsByProductLimit(array $sections, int $limit): array
	{
		$limit = max(1, $limit);
		$flat = $this->studioFlattenProductsWithTaxonomy($sections);
		if ($flat === []) {
			return [];
		}
		$chunks = array_chunk($flat, $limit);
		$volumes = [];
		foreach ($chunks as $chunk) {
			$volumes[] = $this->studioRebuildSectionsFromFlatRows($chunk);
		}

		return $volumes;
	}

	/**
	 * @param list<array<string, mixed>> $sections
	 * @return list<array<string, mixed>>
	 */
	protected function studioFlattenProductsWithTaxonomy(array $sections): array
	{
		$rows = [];
		foreach ($sections as $section) {
			if (!is_array($section)) {
				continue;
			}
			$catName = (string) ($section['category_name'] ?? '');
			$catImage = (string) ($section['category_image'] ?? '');
			foreach (($section['groups'] ?? []) as $group) {
				if (!is_array($group)) {
					continue;
				}
				$gName = (string) ($group['group_name'] ?? $group['name'] ?? '');
				$gImage = (string) ($group['group_image'] ?? $group['image'] ?? '');
				foreach (($group['subgroups'] ?? []) as $sub) {
					if (!is_array($sub)) {
						continue;
					}
					$sName = (string) ($sub['subgroup_name'] ?? $sub['name'] ?? '');
					$sImage = (string) ($sub['subgroup_image'] ?? $sub['image'] ?? '');
					foreach (($sub['products'] ?? []) as $product) {
						if (!is_array($product)) {
							continue;
						}
						$rows[] = [
							'category_name' => $catName,
							'category_image' => $catImage,
							'group_name' => $gName,
							'group_image' => $gImage,
							'subgroup_name' => $sName,
							'subgroup_image' => $sImage,
							'product' => $product,
						];
					}
				}
				foreach (($group['products'] ?? []) as $product) {
					if (!is_array($product)) {
						continue;
					}
					$rows[] = [
						'category_name' => $catName,
						'category_image' => $catImage,
						'group_name' => $gName,
						'group_image' => $gImage,
						'subgroup_name' => '',
						'subgroup_image' => '',
						'product' => $product,
					];
				}
			}
			foreach (($section['products'] ?? []) as $product) {
				if (!is_array($product)) {
					continue;
				}
				$rows[] = [
					'category_name' => $catName,
					'category_image' => $catImage,
					'group_name' => '',
					'group_image' => '',
					'subgroup_name' => '',
					'subgroup_image' => '',
					'product' => $product,
				];
			}
		}

		return $rows;
	}

	/**
	 * @param list<array<string, mixed>> $rows
	 * @return list<array<string, mixed>>
	 */
	protected function studioRebuildSectionsFromFlatRows(array $rows): array
	{
		$sections = [];
		$secIdx = -1;
		$grpIdx = -1;
		$subIdx = -1;
		$lastCat = null;
		$lastGrp = null;
		$lastSub = null;

		foreach ($rows as $row) {
			$cat = (string) ($row['category_name'] ?? '');
			$grp = (string) ($row['group_name'] ?? '');
			$sub = (string) ($row['subgroup_name'] ?? '');
			$product = $row['product'] ?? null;
			if (!is_array($product)) {
				continue;
			}

			if ($secIdx < 0 || $cat !== $lastCat) {
				$sections[] = [
					'category_name' => $cat,
					'category_image' => (string) ($row['category_image'] ?? ''),
					'groups' => [],
					'products' => [],
				];
				$secIdx = count($sections) - 1;
				$grpIdx = -1;
				$subIdx = -1;
				$lastCat = $cat;
				$lastGrp = null;
				$lastSub = null;
			}

			if ($grp === '' && $sub === '') {
				$sections[$secIdx]['products'][] = $product;
				continue;
			}

			if ($grpIdx < 0 || $grp !== $lastGrp) {
				$sections[$secIdx]['groups'][] = [
					'group_name' => $grp,
					'name' => $grp,
					'group_image' => (string) ($row['group_image'] ?? ''),
					'subgroups' => [],
					'products' => [],
				];
				$grpIdx = count($sections[$secIdx]['groups']) - 1;
				$subIdx = -1;
				$lastGrp = $grp;
				$lastSub = null;
			}

			if ($sub === '') {
				$sections[$secIdx]['groups'][$grpIdx]['products'][] = $product;
				continue;
			}

			if ($subIdx < 0 || $sub !== $lastSub) {
				$sections[$secIdx]['groups'][$grpIdx]['subgroups'][] = [
					'subgroup_name' => $sub,
					'name' => $sub,
					'subgroup_image' => (string) ($row['subgroup_image'] ?? ''),
					'products' => [],
				];
				$subIdx = count($sections[$secIdx]['groups'][$grpIdx]['subgroups']) - 1;
				$lastSub = $sub;
			}
			$sections[$secIdx]['groups'][$grpIdx]['subgroups'][$subIdx]['products'][] = $product;
		}

		return $sections;
	}

	/**
	 * Genera 1 PDF o ZIP con un PDF cada STUDIO_PDF_MAX_PRODUCTS (mismo formato).
	 *
	 * @param list<array<string, mixed>> $sections
	 * @param array<string, mixed> $metaBase
	 * @param array{banner_bg?:string,banner_text?:string,footer_bg?:string,footer_text?:string} $theme
	 */
	protected function studioStreamPdfVolumes(
		array $sections,
		array $metaBase,
		string $safeName,
		string $stamp,
		array $theme
	): void {
		$volumes = $this->studioChunkSectionsByProductLimit($sections, self::STUDIO_PDF_MAX_PRODUCTS);
		if ($volumes === []) {
			$this->_studioPdfAllowRemoteImages = true;
			header('Content-Type: application/json; charset=utf-8');
			http_response_code(400);
			echo json_encode([
				'success' => false,
				'msg' => $this->lang_catalog['studio_export_pdf_empty'] ?? '',
			], JSON_UNESCAPED_UNICODE);
			return;
		}

		$totalParts = count($volumes);
		if ($totalParts === 1) {
			$meta = $metaBase;
			$html = $this->studioBuildLeanPdfHtml($volumes[0], $meta);
			$this->studioStreamLeanPdf($html, $safeName . '_' . $stamp . '.pdf', $theme);
			return;
		}

		$this->load->library('zip');
		$this->zip->clear_data();

		foreach ($volumes as $i => $volSections) {
			$part = $i + 1;
			$meta = $metaBase;
			$meta['catalog_name'] = (string) ($metaBase['catalog_name'] ?? 'catalog')
				. ' (' . $part . '/' . $totalParts . ')';
			$html = $this->studioBuildLeanPdfHtml($volSections, $meta);
			$binary = $this->studioRenderLeanPdfBinary($html, $theme);
			$partName = sprintf('%s_part%02d_of_%02d_%s.pdf', $safeName, $part, $totalParts, $stamp);
			$this->zip->add_data($partName, $binary);
			// Liberar memoria entre volúmenes.
			unset($html, $binary, $volSections);
			if (function_exists('gc_collect_cycles')) {
				gc_collect_cycles();
			}
		}

		$this->_studioPdfAllowRemoteImages = true;
		$this->_studioPdfDataUriMap = [];
		$zipName = $safeName . '_' . $stamp . '.zip';
		$this->zip->download($zipName);
	}

	/**
	 * @param array{banner_bg?:string,banner_text?:string,footer_bg?:string,footer_text?:string} $theme
	 */
	protected function studioRenderLeanPdfBinary(string $html, array $theme = []): string
	{
		@ini_set('memory_limit', '1024M');
		@set_time_limit(900);

		$this->_studioPdfAllowRemoteImages = false;

		$html = $this->studioMaterializeImagesForPdf($html);
		$html = preg_replace_callback(
			'/<img\b([^>]*?)src=(["\'])data:image\/[^"\']*\2([^>]*)>/i',
			function (array $m): string {
				$size = 148;
				if (preg_match('/\bwidth=(["\']?)(\d+)\1/i', $m[1] . $m[3], $wm)) {
					$size = max(16, min(220, (int) $wm[2]));
				}
				return $this->studioPdfPlaceholderSquare($size);
			},
			$html
		) ?? $html;

		if (stripos($html, 'pdf-page-header') !== false && stripos($html, 'position:fixed') === false) {
			$pdfCss = $this->studioPdfCompatibilityCss($theme);
			if (stripos($html, '</head>') !== false) {
				$html = preg_replace('/<\/head>/i', '<style>' . $pdfCss . '</style></head>', $html, 1) ?? $html;
			} else {
				$html = '<style>' . $pdfCss . '</style>' . $html;
			}
		}

		// Instancia nueva por volumen (evitar estado residual del singleton CI).
		$dompdf = new \Dompdf\Dompdf();
		$dompdf->set_option('isRemoteEnabled', false);
		$dompdf->set_option('isHtml5ParserEnabled', true);
		if (defined('FCPATH')) {
			$dompdf->set_option('chroot', [FCPATH, sys_get_temp_dir()]);
		}
		$dompdf->loadHtml($html);
		$dompdf->setPaper('A4', 'portrait');
		$dompdf->render();

		return (string) $dompdf->output();
	}

	/**
	 * Recorta secciones para no superar un total de productos (export PDF).
	 *
	 * @param list<array<string, mixed>> $sections
	 * @return list<array<string, mixed>>
	 */
	protected function studioCapSectionsProductTotal(array $sections, int $maxTotal): array
	{
		if ($maxTotal < 1 || $sections === []) {
			return $sections;
		}
		$remaining = $maxTotal;
		$out = [];
		foreach ($sections as $section) {
			if ($remaining < 1) {
				break;
			}
			if (!is_array($section)) {
				continue;
			}
			$capped = $this->pruneStudioCategorySection($section, $remaining);
			$n = $this->studioCountSectionProducts($capped);
			if ($n < 1) {
				continue;
			}
			$out[] = $capped;
			$remaining -= $n;
		}
		return $out;
	}


	/**
	 * PDF del listado (botón descargar): documento lean Dompdf-friendly.
	 *
	 * @param object $catalog
	 * @param array<string, mixed> $request
	 */
	protected function downloadStudioPdf($catalog, array $request): void
	{
		@ini_set('memory_limit', '1024M');
		@set_time_limit(900);

		$catalogId = (int) ($catalog->catalog_id ?? 0);
		$hidePrice = !empty($request['hide_price']) && (string) $request['hide_price'] !== '0';

		$_POST['catalog_id'] = $catalogId;
		if ($hidePrice) {
			$_POST['show_prices'] = '0';
		}
		$sections = $this->studioCollectExportSections(false);
		if ($sections === []) {
			header('Content-Type: application/json; charset=utf-8');
			http_response_code(400);
			echo json_encode([
				'success' => false,
				'msg' => $this->lang_catalog['studio_export_pdf_empty'] ?? '',
			], JSON_UNESCAPED_UNICODE);
			return;
		}

		$parsed = $this->parseCatalogAttach((string) ($catalog->attach ?? ''));
		$theme = [
			'banner_bg' => $parsed['banner_bg'] ?? '#B91C1C',
			'banner_text' => $parsed['banner_text'] ?? '#FFFFFF',
			'footer_bg' => $parsed['banner_bg'] ?? '#B91C1C',
			'footer_text' => $parsed['banner_text'] ?? '#FFFFFF',
		];
		$companyName = 'FullVendor';
		if (is_object($this->company_info)) {
			$companyName = trim((string) ($this->company_info->name ?? $this->company_info->company_name ?? '')) ?: $companyName;
		}
		$logoUrl = '';
		if (!empty($catalog->logo)) {
			$logoUrl = $this->resolveCatalogLogoUrl((string) $catalog->logo);
		}
		if ($logoUrl === '' && is_object($this->company_info) && !empty($this->company_info->image) && function_exists('company_logo_url')) {
			$logoUrl = (string) company_logo_url((string) $this->company_info->image);
		}
		$showPrices = $hidePrice ? false : !empty($parsed['show_prices']);

		$catalogName = trim((string) ($catalog->catalog_name ?? 'catalog'));
		$safeName = preg_replace('/[^a-zA-Z0-9_\-]+/', '_', $catalogName) ?: 'catalog';
		$stamp = date('Ymd_His');

		$this->studioStreamPdfVolumes($sections, [
			'catalog_name' => $catalogName,
			'company_name' => $companyName,
			'message' => (string) ($catalog->cabeza_pagina ?? ''),
			'logo_url' => $logoUrl,
			'show_prices' => $showPrices,
			'theme' => $theme,
		], $safeName, $stamp, $theme);
	}

	/**
	 * HTML del PDF con el formato Studio anterior:
	 * header/footer fijos en cada página + grilla de tarjetas 3 columnas.
	 *
	 * @param list<array<string, mixed>> $sections
	 * @param array<string, mixed> $meta
	 */
	protected function studioBuildLeanPdfHtml(array $sections, array $meta): string
	{
		$esc = static function ($v): string {
			return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
		};
		$theme = is_array($meta['theme'] ?? null) ? $meta['theme'] : [];
		$bannerBg = $this->sanitizeHexColor((string) ($theme['banner_bg'] ?? ''), '#B91C1C');
		$bannerText = $this->sanitizeHexColor((string) ($theme['banner_text'] ?? ''), '#FFFFFF');
		$footerBg = $this->sanitizeHexColor((string) ($theme['footer_bg'] ?? $bannerBg), $bannerBg);
		$footerText = $this->sanitizeHexColor((string) ($theme['footer_text'] ?? $bannerText), $bannerText);
		$catalogName = (string) ($meta['catalog_name'] ?? 'Catalog');
		$companyName = (string) ($meta['company_name'] ?? 'FullVendor');
		$message = trim((string) ($meta['message'] ?? ''));
		$logoUrl = trim((string) ($meta['logo_url'] ?? ''));
		$showPrices = !empty($meta['show_prices']);

		$logoHtml = '';
		if ($logoUrl !== '') {
			// El logo es 1 sola imagen: forzar resolución (local / API) aunque el PDF
			// tenga bloqueadas las descargas remotas masivas de productos.
			$prevRemote = $this->_studioPdfAllowRemoteImages;
			$this->_studioPdfAllowRemoteImages = true;
			$logoTag = $this->studioContainedImageTagForPdf($logoUrl, 110);
			$this->_studioPdfAllowRemoteImages = $prevRemote;

			$isPlaceholder = (stripos($logoTag, 'data:image') !== false)
				|| (stripos($logoTag, 'fv-pdf-ph') !== false);
			if (!$isPlaceholder && preg_match('/\bsrc=(["\'])([^"\']+)\1/i', $logoTag, $sm)) {
				$src = html_entity_decode($sm[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
				// Dompdf (isRemoteEnabled=false) solo embebe archivos locales legibles.
				if ($src !== '' && is_file($src) && is_readable($src)) {
					$logoHtml = '<div class="fv-catalog-logo brand" data-slot="logo">'
						. '<img class="fv-catalog-logo" src="' . htmlspecialchars($src, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"'
						. ' width="110" height="40" alt="">'
						. '</div>';
				}
			}
		}

		// Header fijo (se repite en cada página vía position:fixed + @page margin).
		$header = '<div class="pdf-page-header">'
			. '<table class="pdf-header-table" width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="' . $esc($bannerBg) . '">'
			. '<tr><td style="padding:10px 8mm;color:' . $esc($bannerText) . ';background:' . $esc($bannerBg) . ';">'
			. '<div class="banner pdf-page-header-inner">'
			. '<div class="wrap-inner">'
			. $logoHtml
			. '<div style="color:' . $esc($bannerText) . ';font-size:15px;font-weight:700;font-family:DejaVu Sans,sans-serif;">'
			. $esc($companyName) . ' ' . $esc($catalogName)
			. '</div>'
			. '</div></div>'
			. '</td></tr></table></div>';

		$footerInner = $this->studioCompanyFooterHtmlForPdf();
		$footer = '';
		if ($footerInner !== '') {
			$footer = '<div class="pdf-page-footer">'
				. '<table class="pdf-footer-table" width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="' . $esc($footerBg) . '">'
				. '<tr><td class="pdf-page-footer-inner" style="padding:8px 8mm;text-align:center;font-size:8.5px;line-height:1.35;color:'
				. $esc($footerText) . ';background:' . $esc($footerBg) . ';">'
				. $footerInner
				. '</td></tr></table></div>';
		}

		$body = $header . $footer;
		$body .= '<div class="pdf-header-spacer">&nbsp;</div>';

		if ($message !== '') {
			$body .= '<div class="featured is-visible" style="margin:0 0 10px 0;padding:8px;background:#f3f4f6;border:1px solid #e5e7eb;font-size:11px;font-family:DejaVu Sans,sans-serif;">'
				. $esc($message) . '</div>';
		}

		$flushProducts = function (array $products) use (&$body, $esc, $showPrices): void {
			if ($products === []) {
				return;
			}
			$cells = [];
			foreach ($products as $p) {
				if (!is_array($p)) {
					continue;
				}
				$img = $this->studioContainedImageTagForPdf((string) ($p['image'] ?? ''), 148);
				$sku = $esc($p['sku'] ?? '');
				$name = $esc($p['name'] ?? '');
				$price = $showPrices ? $esc($p['price_fmt'] ?? '') : '';
				$card = '<table class="pdf-product-card" width="100%" cellspacing="0" cellpadding="0" border="0">'
					. '<tr><td class="pdf-card-img" width="100%" height="158" align="center" valign="middle">'
					. $img
					. '</td></tr>'
					. '<tr><td class="pdf-card-body" width="100%" height="108" valign="top">'
					. ($sku !== '' ? '<div class="product-sku">' . $sku . '</div>' : '')
					. '<div class="product-name">' . $name . '</div>'
					. ($price !== '' ? '<div class="product-price">' . $price . '</div>' : '')
					. '</td></tr></table>';
				$cells[] = '<td class="pdf-grid-cell" width="33%" height="275" valign="top">' . $card . '</td>';
			}
			while (count($cells) % 3 !== 0) {
				$cells[] = '<td class="pdf-grid-cell pdf-grid-cell--empty" width="33%" height="275">&nbsp;</td>';
			}
			$body .= '<table class="pdf-product-grid" width="100%" cellspacing="0" cellpadding="0" border="0">';
			for ($i = 0, $n = count($cells); $i < $n; $i += 3) {
				$body .= '<tr class="pdf-grid-row">'
					. $cells[$i] . $cells[$i + 1] . $cells[$i + 2]
					. '</tr>';
			}
			$body .= '</table>';
		};

		$headerRow = static function (string $title, string $level) use (&$body, $esc): void {
			$title = trim($title);
			if ($title === '') {
				return;
			}
			$class = 'fv-tax-header fv-tax-header--' . $level;
			$tag = $level === 'category' ? 'h2' : ($level === 'group' ? 'h3' : 'h4');
			$body .= '<table class="pdf-product-grid" width="100%" cellspacing="0" cellpadding="0" border="0">'
				. '<tr class="pdf-grid-header-row"><td class="pdf-grid-header-cell" colspan="3">'
				. '<div class="' . $class . '">'
				. '<' . $tag . ' class="fv-tax-title">' . $esc($title) . '</' . $tag . '>'
				. '</div>'
				. '</td></tr></table>';
		};

		foreach ($sections as $section) {
			if (!is_array($section)) {
				continue;
			}
			$headerRow((string) ($section['category_name'] ?? ''), 'category');
			$flushProducts(is_array($section['products'] ?? null) ? $section['products'] : []);
			foreach (($section['groups'] ?? []) as $group) {
				if (!is_array($group)) {
					continue;
				}
				$headerRow((string) ($group['group_name'] ?? ''), 'group');
				$flushProducts(is_array($group['products'] ?? null) ? $group['products'] : []);
				foreach (($group['subgroups'] ?? []) as $sub) {
					if (!is_array($sub)) {
						continue;
					}
					$headerRow((string) ($sub['subgroup_name'] ?? $sub['name'] ?? ''), 'subgroup');
					$flushProducts(is_array($sub['products'] ?? null) ? $sub['products'] : []);
				}
			}
		}

		$css = $this->studioPdfCompatibilityCss([
			'banner_bg' => $bannerBg,
			'banner_text' => $bannerText,
			'footer_bg' => $footerBg,
			'footer_text' => $footerText,
		]);

		return '<!DOCTYPE html><html><head><meta charset="utf-8"><style>' . $css . '</style></head><body>'
			. $body . '</body></html>';
	}

	/**
	 * Render Dompdf con formato Studio (header/footer fijos + tarjetas).
	 *
	 * @param array{banner_bg?:string,banner_text?:string,footer_bg?:string,footer_text?:string} $theme
	 */
	protected function studioStreamLeanPdf(string $html, string $filename, array $theme = []): void
	{
		@ini_set('memory_limit', '1024M');
		@set_time_limit(900);

		// Solo archivos locales: no descargar miles de imágenes por HTTP.
		$this->_studioPdfAllowRemoteImages = false;

		$html = $this->studioMaterializeImagesForPdf($html);
		$html = preg_replace_callback(
			'/<img\b([^>]*?)src=(["\'])data:image\/[^"\']*\2([^>]*)>/i',
			function (array $m): string {
				$size = 148;
				if (preg_match('/\bwidth=(["\']?)(\d+)\1/i', $m[1] . $m[3], $wm)) {
					$size = max(16, min(220, (int) $wm[2]));
				}
				return $this->studioPdfPlaceholderSquare($size);
			},
			$html
		) ?? $html;

		// Asegurar CSS de compatibilidad (header/footer fijos) si el HTML no lo trae.
		if (stripos($html, 'pdf-page-header') !== false && stripos($html, 'position:fixed') === false) {
			$pdfCss = $this->studioPdfCompatibilityCss($theme);
			if (stripos($html, '</head>') !== false) {
				$html = preg_replace('/<\/head>/i', '<style>' . $pdfCss . '</style></head>', $html, 1) ?? $html;
			} else {
				$html = '<style>' . $pdfCss . '</style>' . $html;
			}
		}

		$this->load->library('pdf');
		$this->pdf->set_option('isRemoteEnabled', false);
		$this->pdf->set_option('isHtml5ParserEnabled', true);
		if (defined('FCPATH')) {
			$this->pdf->set_option('chroot', [FCPATH, sys_get_temp_dir()]);
		}
		$this->pdf->loadHtml($html);
		$this->pdf->setPaper('A4', 'portrait');
		$this->pdf->render();
		$this->_studioPdfAllowRemoteImages = true;
		$this->_studioPdfDataUriMap = [];
		$this->pdf->stream($filename, ['Attachment' => 1]);
	}

	/**
	 * Renderiza Dompdf desde HTML del Studio (prepare + CSS de compatibilidad).
	 * @deprecated Preferir studioStreamLeanPdf para catálogos grandes.
	 */
	protected function studioStreamPdfFromHtml(string $html, string $filename, string $catalogName = ''): void
	{
		if ($catalogName !== '') {
			$_POST['catalog_name'] = $catalogName;
		}
		@ini_set('memory_limit', '1024M');
		@set_time_limit(600);

		$theme = $this->studioExtractThemeColorsFromHtml($html);
		$this->studioStreamLeanPdf(
			$this->studioPrepareHtmlForPdf($html),
			$filename,
			$theme
		);
	}

	/**
	 * Convierte todo src (data-URI / http / ruta) a archivo local bajo chroot.
	 * Dompdf con base64 o remote se cuelga (Css/Style + 600s).
	 */
	protected function studioMaterializeImagesForPdf(string $html): string
	{
		$out = preg_replace_callback(
			'/<img\b([^>]*?)src=(["\'])([^"\']*)\2([^>]*)>/i',
			function (array $m): string {
				$before = $m[1];
				$after = $m[4];
				$src = trim(html_entity_decode($m[3], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
				if ($src !== '' && isset($this->_studioPdfDataUriMap[$src])) {
					$src = (string) $this->_studioPdfDataUriMap[$src];
				}
				// Conservar tamaño pedido si existe.
				$size = 148;
				if (preg_match('/\bwidth=(["\']?)(\d+)\1/i', $before . $after, $wm)) {
					$size = max(16, min(220, (int) $wm[2]));
				} elseif (preg_match('/\bfv-tax-photo\b/i', $before . $after)) {
					$size = 22;
				} elseif (preg_match('/\bfv-catalog-logo\b|data-slot=(["\'])logo\1/i', $before . $after)) {
					$size = 110;
				}
				$tag = $this->studioContainedImageTagForPdf($src, $size);
				if (preg_match('/\bsrc=(["\'])([^"\']+)\1/i', $tag, $sm)
					&& preg_match('/\bwidth=(["\']?)(\d+)\1/i', $tag, $wm2)
					&& preg_match('/\bheight=(["\']?)(\d+)\1/i', $tag, $hm2)
				) {
					$class = '';
					if (preg_match('/\bclass=(["\'])([^"\']*)\1/i', $before . $after, $cm)) {
						$class = ' class="' . htmlspecialchars($cm[2], ENT_QUOTES, 'UTF-8') . '"';
					}
					return '<img' . $class
						. ' src="' . $sm[2] . '"'
						. ' width="' . $wm2[2] . '" height="' . $hm2[2] . '"'
						. ' alt="">';
				}
				return $tag;
			},
			$html
		);
		return is_string($out) ? $out : $html;
	}

	/**
	 * Quita atributos/estilos que hacen explotar el motor CSS de Dompdf.
	 * Dompdf 3.0.x: custom properties (--fv-*) + var() → cuelgue en Css/Style.php.
	 */
	protected function studioStripHeavyMarkupForDompdf(string $html): string
	{
		// Quitar TODOS los <style>: se inyecta un único bloque mínimo después.
		$html = preg_replace('/<style\b[^>]*>[\s\S]*?<\/style>/i', '', $html) ?? $html;
		// Inline styles: Dompdf recalcula CSS por nodo y se cuelga con catálogos grandes.
		$html = preg_replace('/\sstyle=(["\'])(?:\\\\.|(?!\1).)*\1/iu', '', $html) ?? $html;
		// Scripts / links residuales.
		$html = preg_replace('/<script\b[^>]*>[\s\S]*?<\/script>/i', '', $html) ?? $html;
		$html = preg_replace('/<link\b[^>]*>/i', '', $html) ?? $html;
		// Por si queda var(--fv-...) en atributos o texto residual.
		$html = preg_replace('/var\s*\(\s*--[a-z0-9\-]+(?:\s*,\s*[^)]*)?\)/i', 'inherit', $html) ?? $html;
		return $html;
	}

	/**
	 * Carga el HTML de plantilla del Studio desde disco.
	 */
	protected function studioLoadTemplateHtml(string $templateKey): string
	{
		$key = preg_replace('/[^a-zA-Z0-9\-]/', '', $templateKey) ?: 'classic-grid';
		$base = rtrim((string) FCPATH, '/') . '/companyasset/company/catalog/templates/';
		$path = $base . $key . '.html';
		if (!is_file($path) || !is_readable($path)) {
			$path = $base . 'classic-grid.html';
		}
		if (!is_file($path) || !is_readable($path)) {
			return '';
		}
		return (string) file_get_contents($path);
	}

	/**
	 * Arma el documento HTML del catálogo igual que el preview del Studio.
	 *
	 * @param object $catalog
	 * @param array{hide_price?:bool,group_id?:int} $options
	 */
	protected function studioBuildHtmlDocumentForCatalog($catalog, array $options = []): string
	{
		$catalogId = (int) ($catalog->catalog_id ?? 0);
		if ($catalogId <= 0) {
			return '';
		}

		$parsed = $this->parseCatalogAttach((string) ($catalog->attach ?? ''));
		$hidePrice = !empty($options['hide_price']);
		$showPrices = $hidePrice ? false : !empty($parsed['show_prices']);
		$groupId = (int) ($options['group_id'] ?? 0);

		$categories = $this->fetchCatalogCategories($catalogId);
		$categoryIds = [];
		$categoryNames = [];
		foreach ($categories as $cat) {
			$cat = is_object($cat) ? $cat : (object) $cat;
			$cid = (int) ($cat->cat_id ?? $cat->category_id ?? 0);
			if ($cid <= 0) {
				continue;
			}
			$categoryIds[] = $cid;
			$categoryNames[] = strtoupper((string) ($cat->category_name ?? $cid));
		}

		$priceByProduct = [];
		if ($groupId > 0) {
			$dlProducts = $this->apiList($this->getObjectFromApi(
				"catalog/{$catalogId}/download-products",
				['group_id' => $groupId]
			));
			foreach ($dlProducts as $dp) {
				$dp = is_object($dp) ? $dp : (object) $dp;
				$pid = (int) ($dp->pro_id ?? $dp->product_id ?? 0);
				if ($pid <= 0) {
					continue;
				}
				$priceByProduct[$pid] = [
					'price' => $dp->sale_price ?? $dp->price ?? null,
					'price_fmt' => (string) ($dp->price_formated ?? $dp->price_formatted ?? ''),
				];
			}
		}

		$currency = $this->currencySymbol();
		$formatNumeric = $this->format_numeric;
		$noImg = function_exists('catalog_public_no_image_url') ? catalog_public_no_image_url() : base_url('uploads/noimg.png');
		if ($noImg !== '' && !preg_match('#^(https?:)?//#i', $noImg)) {
			$noImg = rtrim((string) base_url(), '/') . '/' . ltrim($noImg, '/');
		}
		$formatPrice = static function ($value) use ($currency, $formatNumeric): string {
			if ($value === null || $value === '') {
				return '';
			}
			if (function_exists('fv_format_money')) {
				return trim($currency . ' ' . fv_format_money($value, $formatNumeric, null));
			}
			$digits = is_object($formatNumeric) ? (int) ($formatNumeric->digits ?? 2) : 2;
			$radix = is_object($formatNumeric) ? (string) ($formatNumeric->radixPoint ?? '.') : '.';
			$group = is_object($formatNumeric) ? (string) ($formatNumeric->groupSeparator ?? ',') : ',';
			return trim($currency . ' ' . number_format((float) $value, $digits, $radix, $group));
		};

		$self = $this;
		$mapProduct = function ($prod) use ($formatPrice, $noImg, $self, $priceByProduct): ?array {
			$prod = is_object($prod) ? $prod : (object) $prod;
			$pid = (int) ($prod->pro_id ?? $prod->product_id ?? $prod->id ?? 0);
			if ($pid <= 0) {
				return null;
			}
			if (isset($prod->status) && (int) $prod->status !== 1) {
				return null;
			}
			$imageUrl = $self->studioProductImageUrl($prod, $noImg);
			$price = $prod->sale_price ?? $prod->price ?? null;
			$priceFmt = $formatPrice($price);
			if (isset($priceByProduct[$pid])) {
				$price = $priceByProduct[$pid]['price'];
				$priceFmt = $priceByProduct[$pid]['price_fmt'] !== ''
					? $priceByProduct[$pid]['price_fmt']
					: $formatPrice($price);
			}
			return [
				'id' => $pid,
				'sku' => (string) ($prod->sku ?? ''),
				'name' => (string) ($prod->name ?? ''),
				'price' => $price,
				'price_fmt' => $priceFmt,
				'image' => $imageUrl,
				'rating' => function_exists('company_product_rating_value')
					? company_product_rating_value($prod)
					: (isset($prod->rating) && is_numeric($prod->rating) ? (float) $prod->rating : 3.5),
			];
		};

		$sections = [];
		$seen = [];
		$flat = [];
		// PDF del listado: incluir todos los productos por categoría.
		$maxCats = 200;
		$maxPerCat = 2000;
		$order = 1;
		foreach (array_slice($categoryIds, 0, $maxCats) as $idx => $catId) {
			$section = $this->buildStudioCategorySection(
				$catId,
				(string) ($categoryNames[$idx] ?? ''),
				$order,
				$maxPerCat,
				$mapProduct,
				$seen,
				$flat
			);
			if (is_array($section)) {
				$sections[] = $section;
				$order++;
			}
		}

		$companyName = 'FullVendor';
		$companyLogoUrl = '';
		if (is_object($this->company_info)) {
			$companyName = trim((string) ($this->company_info->name ?? $this->company_info->company_name ?? '')) ?: $companyName;
			if (!empty($this->company_info->image) && function_exists('company_logo_url')) {
				$companyLogoUrl = (string) company_logo_url((string) $this->company_info->image);
				if (strpos($companyLogoUrl, '//') === 0) {
					$companyLogoUrl = 'https:' . $companyLogoUrl;
				}
			}
		}
		$catalogLogoUrl = '';
		if (!empty($catalog->logo)) {
			$catalogLogoUrl = $this->resolveCatalogLogoUrl((string) $catalog->logo);
		}
		$logoUrl = $catalogLogoUrl !== '' ? $catalogLogoUrl : $companyLogoUrl;
		$catalogName = (string) ($catalog->catalog_name ?? '');
		$message = (string) ($catalog->cabeza_pagina ?? '');

		$theme = [
			'banner_bg' => $parsed['banner_bg'],
			'banner_text' => $parsed['banner_text'],
			// Mismo estilo header/footer en PDF.
			'footer_bg' => $parsed['banner_bg'],
			'footer_text' => $parsed['banner_text'],
			'accent' => $parsed['accent'],
		];

		return $this->studioFillTemplateHtml(
			$this->studioLoadTemplateHtml($parsed['template_key']),
			[
				'catalog_name' => $catalogName,
				'company_name' => $companyName,
				'message' => $message,
				'logo_url' => $logoUrl,
				'show_prices' => $showPrices,
				'sections' => $sections,
				'theme' => $theme,
			]
		);
	}

	/**
	 * Rellena plantilla HTML del Studio (equivalente a FvCatalogPreview en PHP).
	 *
	 * @param array<string, mixed> $opts
	 */
	protected function studioFillTemplateHtml(string $templateHtml, array $opts): string
	{
		if (trim($templateHtml) === '') {
			return '';
		}
		$esc = static function ($v): string {
			return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
		};
		$catalogName = (string) ($opts['catalog_name'] ?? 'Catalog');
		$companyName = (string) ($opts['company_name'] ?? 'FullVendor');
		$message = (string) ($opts['message'] ?? '');
		$logoUrl = (string) ($opts['logo_url'] ?? '');
		$showPrices = !empty($opts['show_prices']);
		/** @var list<array<string, mixed>> $sections */
		$sections = is_array($opts['sections'] ?? null) ? $opts['sections'] : [];
		$theme = is_array($opts['theme'] ?? null) ? $opts['theme'] : [];

		$extraCss = $this->studioPreviewExtraCss();
		$html = str_replace(
			['{{catalog_name}}', '{{company_name}}'],
			[$esc($catalogName), $esc($companyName)],
			$templateHtml
		);
		if (stripos($html, 'fv-tax-header{') === false && stripos($html, '</style>') !== false) {
			$html = preg_replace('/<\/style>/i', $extraCss . '</style>', $html, 1) ?? $html;
		}

		$bannerBg = $this->sanitizeHexColor($theme['banner_bg'] ?? null, '#B91C1C');
		$bannerText = $this->sanitizeHexColor($theme['banner_text'] ?? null, '#FFFFFF');
		$footerBg = $this->sanitizeHexColor($theme['footer_bg'] ?? null, $bannerBg);
		$footerText = $this->sanitizeHexColor($theme['footer_text'] ?? null, $bannerText);
		$accent = $this->sanitizeHexColor($theme['accent'] ?? null, '#DC2626');
		$rootVars = ':root{--fv-banner-bg:' . $bannerBg . ';--fv-banner-text:' . $bannerText
			. ';--fv-footer-bg:' . $footerBg . ';--fv-footer-text:' . $footerText
			. ';--fv-accent:' . $accent . ';}';
		if (stripos($html, '</head>') !== false) {
			$html = preg_replace('/<\/head>/i', '<style>' . $rootVars . '</style></head>', $html, 1) ?? $html;
		} else {
			$html = '<style>' . $rootVars . '</style>' . $html;
		}

		// Logo
		$logoInner = $logoUrl !== ''
			? '<img src="' . $esc($logoUrl) . '" alt="">'
			: '';
		$logoBlock = '<div class="fv-catalog-logo brand" data-slot="logo">' . $logoInner . '</div>';
		if (preg_match('/<div\b[^>]*\bdata-slot=(["\'])logo\1[^>]*>[\s\S]*?<\/div>/i', $html)) {
			$html = preg_replace(
				'/<div\b[^>]*\bdata-slot=(["\'])logo\1[^>]*>[\s\S]*?<\/div>/i',
				$logoBlock,
				$html,
				1
			) ?? $html;
		}

		// Mensaje destacado
		if (preg_match('/<(div|p)\b([^>]*\bdata-slot=(["\'])message\3[^>]*)>([\s\S]*?)<\/\1>/i', $html, $mm)) {
			$msgClass = $mm[2];
			if ($message !== '') {
				if (stripos($msgClass, 'is-visible') === false) {
					if (preg_match('/\bclass=(["\'])([^"\']*)\1/i', $msgClass)) {
						$msgClass = preg_replace('/\bclass=(["\'])([^"\']*)\1/i', 'class=$1$2 is-visible$1', $msgClass, 1);
					} else {
						$msgClass .= ' class="featured is-visible"';
					}
				}
				$replacement = '<' . $mm[1] . $msgClass . '>' . $esc($message) . '</' . $mm[1] . '>';
			} else {
				$replacement = '<' . $mm[1] . $mm[2] . '></' . $mm[1] . '>';
			}
			$html = str_replace($mm[0], $replacement, $html);
		}

		$layout = 'grid';
		if (preg_match('/data-slot=(["\'])products\1[^>]*data-layout=(["\'])([^"\']+)\2/i', $html, $lm)
			|| preg_match('/data-layout=(["\'])([^"\']+)\1[^>]*data-slot=(["\'])products\3/i', $html, $lm2)
		) {
			$layout = isset($lm[3]) ? $lm[3] : ($lm2[2] ?? 'grid');
		}

		$productsHtml = '';
		$total = 0;
		foreach ($sections as $section) {
			$chunk = $this->studioSectionHtml($section, $layout, $showPrices);
			$productsHtml .= $chunk;
			$total += $this->studioCountSectionProducts($section);
		}

		if (preg_match('/<(div|section)\b([^>]*\bdata-slot=(["\'])products\3[^>]*)>[\s\S]*?<\/\1>/i', $html)) {
			$html = preg_replace(
				'/<(div|section)\b([^>]*\bdata-slot=(["\'])products\3[^>]*)>[\s\S]*?<\/\1>/i',
				'<$1$2>' . $productsHtml . '</$1>',
				$html,
				1
			) ?? $html;
		}

		if (preg_match('/<(div|p)\b([^>]*\bdata-slot=(["\'])empty\3[^>]*)>[\s\S]*?<\/\1>/i', $html, $em)) {
			if ($total === 0) {
				$emptyLabel = (string) ($this->lang_catalog['studio_empty_preview'] ?? '');
				$html = str_replace(
					$em[0],
					'<' . $em[1] . $em[2] . '>' . $esc($emptyLabel) . '</' . $em[1] . '>',
					$html
				);
				$html = preg_replace(
					'/(data-slot=(["\'])empty\2[^>]*)\bhidden\b/i',
					'$1',
					$html,
					1
				) ?? $html;
			} else {
				$html = preg_replace(
					'/<(div|p)\b([^>]*\bdata-slot=(["\'])empty\3[^>]*)>[\s\S]*?<\/\1>/i',
					'<$1$2 hidden></$1>',
					$html,
					1
				) ?? $html;
			}
		}

		$footerHtml = $this->studioCompanyFooterHtmlForPdf();
		if ($footerHtml !== '' && preg_match('/<footer\b[^>]*>[\s\S]*?<\/footer>/i', $html)) {
			if (preg_match('/(<footer\b[^>]*>[\s\S]*?<div\b[^>]*\bclass=(["\'])[^"\']*\bwrap-inner\b[^"\']*\2[^>]*>)([\s\S]*?)(<\/div>[\s\S]*?<\/footer>)/i', $html)) {
				$html = preg_replace(
					'/(<footer\b[^>]*>[\s\S]*?<div\b[^>]*\bclass=(["\'])[^"\']*\bwrap-inner\b[^"\']*\2[^>]*>)([\s\S]*?)(<\/div>[\s\S]*?<\/footer>)/i',
					'$1' . $footerHtml . '$4',
					$html,
					1
				) ?? $html;
			} else {
				$html = preg_replace(
					'/<footer\b([^>]*)>[\s\S]*?<\/footer>/i',
					'<footer$1>' . $footerHtml . '</footer>',
					$html,
					1
				) ?? $html;
			}
		}

		return $html;
	}

	/** @param array<string, mixed> $section */
	protected function studioCountSectionProducts(array $section): int
	{
		$n = is_array($section['products'] ?? null) ? count($section['products']) : 0;
		foreach (($section['groups'] ?? []) as $g) {
			if (!is_array($g)) {
				continue;
			}
			$n += is_array($g['products'] ?? null) ? count($g['products']) : 0;
			foreach (($g['subgroups'] ?? []) as $sub) {
				if (!is_array($sub)) {
					continue;
				}
				$n += is_array($sub['products'] ?? null) ? count($sub['products']) : 0;
			}
		}
		return $n;
	}

	/** @param array<string, mixed> $section */
	protected function studioSectionHtml(array $section, string $layout, bool $showPrices): string
	{
		$html = '';
		$catName = (string) ($section['category_name'] ?? '');
		$catImage = (string) ($section['category_image'] ?? '');
		$groups = is_array($section['groups'] ?? null) ? $section['groups'] : [];
		$ungrouped = is_array($section['products'] ?? null) ? $section['products'] : [];
		$hasAny = $ungrouped !== [] || $this->studioCountSectionProducts($section) > 0;
		if (!$hasAny && $catName === '') {
			return '';
		}
		$html .= '<div class="fv-tax-block fv-tax-block--category">';
		if ($catName !== '') {
			$html .= $this->studioTaxonomyHeaderHtml('category', $catName, $catImage);
		}
		foreach ($groups as $group) {
			if (!is_array($group)) {
				continue;
			}
			$gName = (string) ($group['group_name'] ?? '');
			$gImage = (string) ($group['group_image'] ?? '');
			$subs = is_array($group['subgroups'] ?? null) ? $group['subgroups'] : [];
			$gProducts = is_array($group['products'] ?? null) ? $group['products'] : [];
			$html .= '<div class="fv-tax-block fv-tax-block--group">';
			if ($gName !== '') {
				$html .= $this->studioTaxonomyHeaderHtml('group', $gName, $gImage);
			}
			foreach ($subs as $sub) {
				if (!is_array($sub)) {
					continue;
				}
				$sProducts = is_array($sub['products'] ?? null) ? $sub['products'] : [];
				if ($sProducts === []) {
					continue;
				}
				$sName = (string) ($sub['subgroup_name'] ?? '');
				$sImage = (string) ($sub['subgroup_image'] ?? '');
				$html .= '<div class="fv-tax-block fv-tax-block--subgroup">';
				if ($sName !== '') {
					$html .= $this->studioTaxonomyHeaderHtml('subgroup', $sName, $sImage);
				}
				$html .= '<div class="fv-tax-products">' . $this->studioProductsBatchHtml($sProducts, $layout, $showPrices) . '</div></div>';
			}
			if ($gProducts !== []) {
				$html .= '<div class="fv-tax-products">' . $this->studioProductsBatchHtml($gProducts, $layout, $showPrices) . '</div>';
			}
			$html .= '</div>';
		}
		if ($ungrouped !== []) {
			$html .= '<div class="fv-tax-products">' . $this->studioProductsBatchHtml($ungrouped, $layout, $showPrices) . '</div>';
		}
		$html .= '</div>';
		return $html;
	}

	protected function studioTaxonomyHeaderHtml(string $level, string $name, string $imageUrl = ''): string
	{
		$esc = static function ($v): string {
			return htmlspecialchars((string) $v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		};
		$title = trim($name);
		if ($title === '') {
			return '';
		}
		$imgHtml = $imageUrl !== ''
			? '<img class="fv-tax-photo" src="' . $esc($imageUrl) . '" alt="">'
			: '';
		$tag = $level === 'category' ? 'h2' : ($level === 'group' ? 'h3' : 'h4');
		$cls = 'fv-tax-header fv-tax-header--' . $level . ($imgHtml !== '' ? ' has-photo' : '');
		return '<header class="' . $cls . '">' . $imgHtml
			. '<' . $tag . ' class="fv-tax-title">' . $esc($title) . '</' . $tag . '></header>';
	}

	/** @param list<array<string, mixed>> $products */
	protected function studioProductsBatchHtml(array $products, string $layout, bool $showPrices): string
	{
		$out = '';
		foreach ($products as $p) {
			if (!is_array($p)) {
				continue;
			}
			$out .= $this->studioProductCardHtml($layout, $p, $showPrices);
		}
		return $out;
	}

	/** @param array<string, mixed> $product */
	protected function studioProductCardHtml(string $layout, array $product, bool $showPrices): string
	{
		$esc = static function ($v): string {
			return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
		};
		$sku = $esc($product['sku'] ?? '');
		$name = $esc($product['name'] ?? $product['text'] ?? $sku);
		$price = $esc($product['price_fmt'] ?? $product['price'] ?? '');
		$img = $esc($product['image'] ?? '');
		$imgHtml = $img !== ''
			? '<img src="' . $img . '" alt="">'
			: '<span style="color:#94a3b8;font-size:1.4rem;">&#9632;</span>';
		$rating = isset($product['rating']) && is_numeric($product['rating']) ? (float) $product['rating'] : 3.5;
		$ratingHtml = '<div class="product-rating"><span class="product-rating__value">'
			. $esc(number_format($rating, 1, '.', '')) . '</span></div>';
		$priceClass = 'product-price' . ($showPrices ? '' : ' is-hidden');
		$priceHtml = '<div class="' . $priceClass . '">' . ($showPrices ? $price : '') . '</div>';
		if ($layout === 'table' || $layout === 'list') {
			return '<div class="product product--row">'
				. '<div class="product-img">' . $imgHtml . '</div>'
				. '<div class="product-sku">' . $sku . '</div>'
				. '<div class="product-name">' . $name . '</div>'
				. $ratingHtml . $priceHtml . '</div>';
		}
		return '<article class="product">'
			. '<div class="product-img">' . $imgHtml . '</div>'
			. '<div class="product-body">'
			. '<div class="product-sku">' . $sku . '</div>'
			. '<div class="product-name">' . $name . '</div>'
			. $ratingHtml . $priceHtml
			. '</div></article>';
	}

	protected function studioPreviewExtraCss(): string
	{
		return '.fv-tax-block,.fv-tax-products{display:contents;}'
			. '.fv-tax-header{grid-column:1/-1;width:100%;display:flex;align-items:center;gap:0.65rem;margin:0;padding:0;}'
			. '.fv-tax-header--category{margin:1.35rem 0 0.55rem;padding:0 0 0.45rem;border-bottom:2px solid #111827;}'
			. '.fv-tax-header--group{margin:1rem 0 0.35rem;padding:0 0 0 0.65rem;border-left:3px solid #dc2626;}'
			. '.fv-tax-header--subgroup{margin:0.65rem 0 0.25rem;}'
			. '.fv-tax-photo{width:2.25rem;height:2.25rem;object-fit:contain;}'
			. '.fv-tax-title{margin:0;font-weight:700;line-height:1.2;text-transform:uppercase;}'
			. '.fv-catalog-logo{margin:0;display:flex;align-items:center;}'
			. '.fv-catalog-logo img{max-height:52px;max-width:180px;object-fit:contain;background:#fff;}'
			. '.product-img{background:#fff!important;overflow:hidden;}'
			. '.product-img img{width:100%;height:100%;object-fit:contain;object-position:center;display:block;background:#fff!important;}'
			. '.product-rating{display:flex;align-items:center;gap:0.08rem;margin:0.2rem 0 0.35rem;color:#f59e0b;font-size:0.85rem;}'
			. '.product-rating__value{margin-left:0.25rem;color:#64748b;font-size:0.75rem;font-weight:700;}';
	}

	/**
	 * Prepara el HTML del preview para Dompdf: encabezado fijo en cada página.
	 * Nota: no usar DOMDocument/saveHTML — corrompe el documento y Dompdf genera PDF en blanco.
	 */
	protected function studioPrepareHtmlForPdf(string $html): string
	{
		// No truncar productos: el PDF debe incluir todos los de cada categoría.
		// Los data-URI se protegen abajo para que Dompdf no se cuelgue.
		[$html, $dataUriMap] = $this->studioProtectDataUris($html);
		$this->_studioPdfDataUriMap = $dataUriMap;

		try {
			$html = $this->studioScrubPreviewCssForPdf($html);
			$html = $this->studioFlattenTaxonomyForPdf($html);
			$html = $this->studioProductsToTableForPdf($html);

			$banner = null;
			if (preg_match('/<header\b[^>]*\bclass=(["\'])[^"\']*\bbanner\b[^"\']*\1[^>]*>.*?<\/header>/is', $html, $m)) {
				$banner = $m[0];
			} elseif (preg_match('/<(header|div)\b[^>]*\bdata-slot=(["\'])banner\2[^>]*>.*?<\/\1>/is', $html, $m)) {
				$banner = $m[0];
			}

			$theme = $this->studioExtractThemeColorsFromHtml($html);

			$inject = '';
			if ($banner !== null && $banner !== '') {
				$clone = $banner;
				if (preg_match('/\bclass=(["\'])([^"\']*)\1/i', $clone)) {
					$clone = preg_replace(
						'/\bclass=(["\'])([^"\']*)\1/i',
						'class=$1$2 pdf-page-header-inner$1',
						$clone,
						1
					);
				} else {
					$clone = preg_replace('/^<(header|div)\b/i', '<$1 class="pdf-page-header-inner"', $clone, 1);
				}

				$fixedInner = $this->studioMoveLogoLeftInBannerHtml($clone);
				$fixedInner = $this->studioEnsureBannerLogoForPdf($fixedInner);
				$fixedInner = $this->studioEmbedBannerImagesForPdf($fixedInner);
				$fixed = '<div class="pdf-page-header">'
					. '<table class="pdf-header-table" width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="' . htmlspecialchars($theme['banner_bg'], ENT_QUOTES, 'UTF-8') . '"'
					. ' style="width:100%;border-collapse:collapse;background:' . htmlspecialchars($theme['banner_bg'], ENT_QUOTES, 'UTF-8') . ';">'
					. '<tr><td style="padding:10px 8mm;color:' . htmlspecialchars($theme['banner_text'], ENT_QUOTES, 'UTF-8') . ';background:' . htmlspecialchars($theme['banner_bg'], ENT_QUOTES, 'UTF-8') . ';">'
					. $fixedInner
					. '</td></tr></table></div>';

				// Espacio bajo el header fijo (sin data-URI: Dompdf las odia).
				$spacer = '<div class="pdf-header-spacer">&nbsp;</div>';

				$pos = strpos($html, $banner);
				if ($pos !== false) {
					$html = substr_replace($html, $spacer, $pos, strlen($banner));
				}
				$inject .= $fixed;
			}

			$footerHtml = $this->studioCompanyFooterHtmlForPdf();
			if ($footerHtml !== '') {
				$inject .= '<div class="pdf-page-footer">'
					. '<table class="pdf-footer-table" width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="' . htmlspecialchars($theme['footer_bg'], ENT_QUOTES, 'UTF-8') . '"'
					. ' style="width:100%;border-collapse:collapse;background:' . htmlspecialchars($theme['footer_bg'], ENT_QUOTES, 'UTF-8') . ';">'
					. '<tr><td class="pdf-page-footer-inner" style="padding:8px 8mm;text-align:center;font-size:8.5px;line-height:1.35;color:'
					. htmlspecialchars($theme['footer_text'], ENT_QUOTES, 'UTF-8') . ';background:' . htmlspecialchars($theme['footer_bg'], ENT_QUOTES, 'UTF-8') . ';">'
					. $footerHtml
					. '</td></tr></table></div>';
			}
			$html = preg_replace(
				'/<footer\b[^>]*\bclass=(["\'])[^"\']*\bfooter\b[^"\']*\1[^>]*>.*?<\/footer>/is',
				'',
				$html,
				1
			) ?? $html;
			$html = preg_replace(
				'/<footer\b[^>]*\bdata-slot=(["\'])footer\1[^>]*>.*?<\/footer>/is',
				'',
				$html,
				1
			) ?? $html;

			if ($inject !== '') {
				$replaced = preg_replace('/<body([^>]*)>/i', '<body$1>' . $inject, $html, 1);
				$html = is_string($replaced) ? $replaced : $html;
			}

			// No restaurar data-URI aquí: Dompdf se cuelga con base64.
			// Quedan placeholders fvpdfdataNx; studioMaterializeImagesForPdf los resuelve a archivos.
			return $html;
		} catch (\Throwable $e) {
			$this->_studioPdfDataUriMap = [];
			throw $e;
		}
	}

	/**
	 * Quita productos sobrantes del HTML antes de Dompdf (máx. estable ~60).
	 * El preview Studio usa <article class="product">.
	 */
	protected function studioCapProductsForPdf(string $html, int $maxProducts = 60): string
	{
		if ($maxProducts < 1) {
			return $html;
		}
		$count = 0;
		$out = preg_replace_callback(
			'/<article\b([^>]*\bclass=(["\'])(?:(?!\2).)*\bproduct\b(?:(?!\2).)*\2[^>]*)>([\s\S]*?)<\/article>/i',
			static function (array $m) use (&$count, $maxProducts): string {
				$count++;
				return $count <= $maxProducts ? $m[0] : '';
			},
			$html
		);
		return is_string($out) ? $out : $html;
	}

	/**
	 * @return array{0: string, 1: array<string, string>}
	 */
	protected function studioProtectDataUris(string $html): array
	{
		$map = [];
		$out = preg_replace_callback(
			'/(src\s*=\s*)(["\'])(data:image\/[a-z0-9.+-]+;base64,[A-Za-z0-9+\/=]+)\2/i',
			static function (array $m) use (&$map): string {
				$key = 'fvpdfdata' . count($map) . 'x';
				$map[$key] = $m[3];
				return $m[1] . $m[2] . $key . $m[2];
			},
			$html
		);
		return [is_string($out) ? $out : $html, $map];
	}

	/**
	 * @param array<string, string> $map
	 */
	protected function studioRestoreDataUris(string $html, array $map): string
	{
		if ($map === []) {
			return $html;
		}
		// Restaurar de mayor a menor índice para no pisar prefijos.
		$keys = array_keys($map);
		usort($keys, static function ($a, $b): int {
			return strlen($b) <=> strlen($a);
		});
		foreach ($keys as $key) {
			$html = str_replace($key, $map[$key], $html);
		}
		return $html;
	}

	/**
	 * Coloca el logo a la izquierda del bloque de título en el banner.
	 */
	protected function studioMoveLogoLeftInBannerHtml(string $banner): string
	{
		if (!preg_match(
			'/(<div\b[^>]*\bclass=(["\'])[^"\']*\bwrap-inner\b[^"\']*\2[^>]*>)([\s\S]*?)(<\/div>\s*<\/(?:header|div)>)/i',
			$banner,
			$m,
			PREG_OFFSET_CAPTURE
		)) {
			return $banner;
		}
		$open = $m[1][0];
		$inner = $m[3][0];
		$closeTail = $m[4][0];
		if (!preg_match(
			'/<div\b[^>]*\bdata-slot=(["\'])logo\1[^>]*>[\s\S]*?<\/div>/i',
			$inner,
			$lm,
			PREG_OFFSET_CAPTURE
		)) {
			return $banner;
		}
		$logo = $lm[0][0];
		$logoPos = (int) $lm[0][1];
		$withoutLogo = substr($inner, 0, $logoPos) . substr($inner, $logoPos + strlen($logo));
		$newInner = $logo . $withoutLogo;
		$newBlock = $open . $newInner . $closeTail;
		return substr_replace($banner, $newBlock, (int) $m[0][1], strlen($m[0][0]));
	}

	/**
	 * Reescribe <img> del banner a data-URI / ruta local para Dompdf.
	 */
	protected function studioEmbedBannerImagesForPdf(string $html): string
	{
		$replaced = preg_replace_callback(
			'/<img\b([^>]*)\bsrc=(["\'])([^"\']+)\2([^>]*)>/i',
			function (array $m): string {
				$src = trim(html_entity_decode($m[3], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
				if ($src !== '' && isset($this->_studioPdfDataUriMap[$src])) {
					$src = (string) $this->_studioPdfDataUriMap[$src];
				}
				if ($src === '') {
					return $m[0];
				}
				$tag = $this->studioContainedImageTagForPdf($src, 110);
				if (preg_match('/\bsrc=(["\'])([^"\']+)\1/i', $tag, $sm)) {
					$newSrc = $sm[2];
					$extra = $m[1] . $m[4];
					if (!preg_match('/\bwidth=/i', $extra)) {
						$extra .= ' width="110"';
					}
					if (!preg_match('/\bheight=/i', $extra) && preg_match('/\bheight=(["\']?)(\d+)\1/i', $tag, $hm)) {
						$extra .= ' height="' . $hm[2] . '"';
					}
					return '<img' . $extra . ' src="' . $newSrc . '"'
						. ' style="max-height:40px;max-width:120px;height:auto;width:auto;border:0;display:inline-block;vertical-align:middle;background:#fff;">';
				}
				return $m[0];
			},
			$html
		);
		return is_string($replaced) ? $replaced : $html;
	}

	/**
	 * Si el slot de logo está vacío, inyecta el logo de empresa / catálogo / fallback.
	 */
	protected function studioEnsureBannerLogoForPdf(string $bannerHtml): string
	{
		// Solo cuenta como logo si el <img> tiene src útil (data:/http/ruta), no vacío ni la URL de la página.
		$hasUsableImg = false;
		if (preg_match(
			'/<div\b[^>]*\bdata-slot=(["\'])logo\1[^>]*>\s*<img\b([^>]*)>/i',
			$bannerHtml,
			$im
		)) {
			$attrs = $im[2];
			if (preg_match('/\bsrc=(["\'])([^"\']*)\1/i', $attrs, $sm)) {
				$srcAttr = trim(html_entity_decode($sm[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
				$hasUsableImg = $srcAttr !== '' && (
					stripos($srcAttr, 'data:image/') === 0
					|| (bool) preg_match('/^fvpdfdata\d+x$/i', $srcAttr)
					|| (bool) preg_match('#^(https?:)?//#i', $srcAttr)
					|| (bool) preg_match('#^(/|images/|uploads/)#i', $srcAttr)
				);
				// Descartar src accidental de la propia página del Studio.
				if ($hasUsableImg && preg_match('#/company/catalog/#i', $srcAttr)) {
					$hasUsableImg = false;
				}
			}
		}
		if ($hasUsableImg) {
			return $bannerHtml;
		}

		$src = trim((string) ($this->input->post('logo_data_uri') ?? ''));
		if ($src !== '' && stripos($src, 'data:image/') !== 0) {
			$src = '';
		}
		if ($src === '' && is_object($this->company_info) && !empty($this->company_info->image) && function_exists('company_logo_url')) {
			$src = (string) company_logo_url((string) $this->company_info->image);
			if (strpos($src, '//') === 0) {
				$src = 'https:' . $src;
			}
		}
		// Fallback visible: isologo FullVendor (hasta que la empresa suba su logo).
		if ($src === '' && defined('FCPATH')) {
			foreach (['images/logos/isologo.png', 'images/logos/logo.png', 'uploads/logos/isologo.png'] as $rel) {
				$p = rtrim((string) FCPATH, '/') . '/' . $rel;
				if (is_file($p) && is_readable($p)) {
					$src = $p;
					break;
				}
			}
		}
		if ($src === '') {
			return $bannerHtml;
		}

		// Siempre materializar a archivo local (nunca data-URI crudo en el HTML final).
		$imgTag = $this->studioContainedImageTagForPdf($src, 110);
		if ($imgTag === '' || stripos($imgTag, '<img') === false) {
			return $bannerHtml;
		}

		$logoBlock = '<div class="fv-catalog-logo brand" data-slot="logo">' . $imgTag . '</div>';
		if (preg_match('/<div\b[^>]*\bdata-slot=(["\'])logo\1[^>]*>[\s\S]*?<\/div>/i', $bannerHtml)) {
			return preg_replace(
				'/<div\b[^>]*\bdata-slot=(["\'])logo\1[^>]*>[\s\S]*?<\/div>/i',
				$logoBlock,
				$bannerHtml,
				1
			) ?? $bannerHtml;
		}
		if (preg_match('/(<div\b[^>]*\bclass=(["\'])[^"\']*\bwrap-inner\b[^"\']*\2[^>]*>)/i', $bannerHtml, $wm)) {
			return preg_replace(
				'/(<div\b[^>]*\bclass=(["\'])[^"\']*\bwrap-inner\b[^"\']*\2[^>]*>)/i',
				'$1' . $logoBlock,
				$bannerHtml,
				1
			) ?? $bannerHtml;
		}
		return $bannerHtml;
	}

	/**
	 * Pie de página PDF con datos de la empresa (fijo en cada hoja).
	 */
	protected function studioCompanyFooterHtmlForPdf(): string
	{
		$c = is_object($this->company_info) ? $this->company_info : null;
		if ($c === null) {
			return '';
		}
		$name = trim((string) ($c->name ?? $c->company_name ?? ''));
		$email = trim((string) ($c->email ?? ''));
		$phone = trim((string) ($c->phone_number ?? ''));
		$cell = trim((string) ($c->cell_number ?? ''));
		$website = trim((string) ($c->website ?? ''));
		$address = trim((string) ($c->full_address ?? $c->address ?? ''));
		$city = trim((string) ($c->city ?? ''));
		$state = trim((string) ($c->state ?? ''));
		$country = trim((string) ($c->country ?? ''));
		$zip = trim((string) ($c->zip_code ?? ''));

		$phones = array_values(array_filter([$phone, $cell], static function ($v) {
			return $v !== '';
		}));
		$locParts = array_values(array_filter([$address, $city, $state, $zip, $country], static function ($v) {
			return $v !== '';
		}));

		$lines = [];
		if ($name !== '') {
			$lines[] = '<strong>' . htmlspecialchars($name, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</strong>';
		}
		$contactBits = [];
		if ($email !== '') {
			$contactBits[] = htmlspecialchars($email, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		}
		if ($phones !== []) {
			$contactBits[] = htmlspecialchars(implode(' · ', $phones), ENT_QUOTES | ENT_HTML5, 'UTF-8');
		}
		if ($contactBits !== []) {
			$lines[] = implode(' &nbsp;|&nbsp; ', $contactBits);
		}
		if ($locParts !== []) {
			$lines[] = htmlspecialchars(implode(', ', $locParts), ENT_QUOTES | ENT_HTML5, 'UTF-8');
		}
		if ($website !== '') {
			$lines[] = htmlspecialchars($website, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		}
		if ($lines === []) {
			return '';
		}
		return implode('<br>', $lines);
	}

	/**
	 * Extrae colores del tema desde CSS variables del HTML del preview.
	 *
	 * @return array{banner_bg:string,banner_text:string,footer_bg:string,footer_text:string}
	 */
	protected function studioExtractThemeColorsFromHtml(string $html): array
	{
		$defaults = [
			'banner_bg' => '#B91C1C',
			'banner_text' => '#FFFFFF',
			'footer_bg' => '#B91C1C',
			'footer_text' => '#FFFFFF',
		];
		$pick = static function (string $html, string $var, string $fallback): string {
			if (preg_match('/--' . preg_quote($var, '/') . '\\s*:\\s*(#[0-9A-Fa-f]{3,8})/i', $html, $m)) {
				return strtoupper($m[1]);
			}
			return $fallback;
		};
		$bannerBg = $pick($html, 'fv-banner-bg', $defaults['banner_bg']);
		$bannerText = $pick($html, 'fv-banner-text', $defaults['banner_text']);
		// PDF: mismo estilo header/footer (color del tema del banner).
		$footerBg = $bannerBg;
		$footerText = $bannerText;
		$postBanner = trim((string) ($this->input->post('theme_banner_bg') ?? ''));
		$postBannerText = trim((string) ($this->input->post('theme_banner_text') ?? ''));
		if (preg_match('/^#[0-9A-Fa-f]{3,8}$/', $postBanner)) {
			$bannerBg = strtoupper($postBanner);
			$footerBg = $bannerBg;
		}
		if (preg_match('/^#[0-9A-Fa-f]{3,8}$/', $postBannerText)) {
			$bannerText = strtoupper($postBannerText);
			$footerText = $bannerText;
		}
		return [
			'banner_bg' => $bannerBg,
			'banner_text' => $bannerText,
			'footer_bg' => $footerBg,
			'footer_text' => $footerText,
		];
	}

	/**
	 * Dompdf no soporta display:contents: los wrappers .fv-tax-* anidados provocan
	 * saltos de página prematuros. Se desenrollan dejando headers + productos
	 * como hermanos en .products.
	 *
	 * IMPORTANTE: no usar preg no-greedy hasta el primer </div> — eso robaba
	 * cierres de .product-img / .product-body y dejaba src vacío en el PDF.
	 */
	protected function studioFlattenTaxonomyForPdf(string $html): string
	{
		$guard = 0;
		while ($guard < 60) {
			$guard++;
			if (!preg_match(
				'/<div\b[^>]*\bclass=(["\'])[^"\']*\bfv-tax-(?:block|products)\b[^"\']*\1[^>]*>/i',
				$html,
				$m,
				PREG_OFFSET_CAPTURE
			)) {
				break;
			}
			$open = $m[0][0];
			$openStart = (int) $m[0][1];
			$innerStart = $openStart + strlen($open);
			$closePos = $this->studioFindMatchingClosingTag($html, 'div', $innerStart);
			if ($closePos < 0) {
				break;
			}
			$inner = substr($html, $innerStart, $closePos - $innerStart);
			$html = substr_replace($html, $inner, $openStart, ($closePos + strlen('</div>')) - $openStart);
		}
		return $html;
	}

	/**
	 * Convierte el listado de productos a tabla 3 columnas (formato estable en Dompdf).
	 * Mantiene el aspecto de tarjetas del preview y reparte productos de forma uniforme.
	 * El preview usa <article class="product"> y <header class="fv-tax-header">.
	 */
	protected function studioProductsToTableForPdf(string $html): string
	{
		if (!preg_match(
			'/<div\b[^>]*(?:\bclass=(["\'])[^"\']*\bproducts\b[^"\']*\1|\bdata-slot=(["\'])products\2)[^>]*>/i',
			$html,
			$m,
			PREG_OFFSET_CAPTURE
		)) {
			return $html;
		}

		$open = $m[0][0];
		$openStart = (int) $m[0][1];
		$innerStart = $openStart + strlen($open);

		// Layout lista/tabla: no forzar 3 columnas.
		if (preg_match('/\bdata-layout=(["\'])(list|table)\1/i', $open)) {
			return $html;
		}

		$closePos = $this->studioFindMatchingClosingTag($html, 'div', $innerStart);
		if ($closePos < 0) {
			return $html;
		}
		$inner = substr($html, $innerStart, $closePos - $innerStart);
		$close = '</div>';
		$blockLen = ($closePos + strlen($close)) - $openStart;

		// Preview: <header class="fv-tax-header"> + <article class="product"> (también admite div legacy).
		$chunks = preg_split(
			'/(?=<header\b[^>]*\bclass=(["\'])(?:(?!\1).)*\bfv-tax-header\b(?:(?!\1).)*\1[^>]*>'
			. '|<article\b[^>]*\bclass=(["\'])(?:(?!\2).)*\bproduct\b(?:(?!\2).)*\2[^>]*>'
			. '|<div\b[^>]*\bclass=(["\'])(?:(?!\3).)*\bfv-tax-header\b(?:(?!\3).)*\3[^>]*>'
			. '|<div\b[^>]*\bclass=(["\'])product\4[^>]*>)/i',
			$inner,
			-1,
			PREG_SPLIT_NO_EMPTY
		);
		if (!is_array($chunks) || $chunks === []) {
			return $html;
		}

		$rowsHtml = '';
		$productCells = [];
		$flushProducts = static function () use (&$rowsHtml, &$productCells): void {
			if ($productCells === []) {
				return;
			}
			$cells = $productCells;
			while (count($cells) % 3 !== 0) {
				$cells[] = '<td class="pdf-grid-cell pdf-grid-cell--empty" width="33%" height="275">&nbsp;</td>';
			}
			for ($i = 0, $n = count($cells); $i < $n; $i += 3) {
				$rowsHtml .= '<tr class="pdf-grid-row">'
					. $cells[$i] . $cells[$i + 1] . $cells[$i + 2]
					. '</tr>';
			}
			$productCells = [];
		};

		foreach ($chunks as $chunk) {
			$chunk = trim($chunk);
			if ($chunk === '') {
				continue;
			}
			if (preg_match('/\bfv-tax-header\b/i', $chunk)) {
				$flushProducts();
				if (preg_match('/(<(header|div)\b[^>]*\bclass=(["\'])(?:(?!\3).)*\bfv-tax-header\b(?:(?!\3).)*\3[^>]*>)/i', $chunk, $hm, PREG_OFFSET_CAPTURE)) {
					$tag = strtolower((string) $hm[2][0]);
					$hOpenEnd = (int) $hm[0][1] + strlen($hm[0][0]);
					$hCloseRel = $this->studioFindMatchingClosingTag($chunk, $tag, $hOpenEnd);
					if ($hCloseRel >= 0) {
						$closeLen = strlen('</' . $tag . '>');
						$chunk = substr($chunk, (int) $hm[0][1], ($hCloseRel + $closeLen) - (int) $hm[0][1]);
					}
				}
				// Dompdf renderiza mal <header> dentro de tablas → convertir a <div>.
				$chunk = $this->studioTaxHeaderToDivForPdf($chunk);
				$rowsHtml .= '<tr class="pdf-grid-header-row"><td class="pdf-grid-header-cell" colspan="3">'
					. $chunk
					. '</td></tr>';
				continue;
			}
			if (preg_match('/^<(article|div)\b[^>]*\bclass=(["\'])(?:(?!\2).)*\bproduct\b(?:(?!\2).)*\2/i', $chunk)) {
				// Aislar solo el nodo .product (article o div).
				if (preg_match('/(<(article|div)\b[^>]*\bclass=(["\'])(?:(?!\3).)*\bproduct\b(?:(?!\3).)*\3[^>]*>)/i', $chunk, $pm, PREG_OFFSET_CAPTURE)) {
					$tag = strtolower((string) $pm[2][0]);
					$pOpenEnd = (int) $pm[0][1] + strlen($pm[0][0]);
					$pCloseRel = $this->studioFindMatchingClosingTag($chunk, $tag, $pOpenEnd);
					if ($pCloseRel >= 0) {
						$closeLen = strlen('</' . $tag . '>');
						$chunk = substr($chunk, (int) $pm[0][1], ($pCloseRel + $closeLen) - (int) $pm[0][1]);
					}
				}
				$chunk = $this->studioProductCardToPdfTable($chunk);
				$productCells[] = '<td class="pdf-grid-cell" width="33%" height="275" valign="top">' . $chunk . '</td>';
				if (count($productCells) >= 3) {
					$flushProducts();
				}
			}
		}
		$flushProducts();

		if ($rowsHtml === '') {
			return $html;
		}

		$table = $open
			. '<table class="pdf-product-grid" width="100%" cellspacing="0" cellpadding="0" border="0">'
			. $rowsHtml
			. '</table>'
			. $close;

		return substr_replace($html, $table, $openStart, $blockLen);
	}

	/**
	 * Dompdf no pinta bien <header> en celdas: pasar a <div> y asegurar título visible.
	 */
	protected function studioTaxHeaderToDivForPdf(string $chunk): string
	{
		// Dompdf: <header> dentro de <td> a menudo no se pinta → <div>.
		$chunk = str_ireplace(['<header', '</header>'], ['<div', '</div>'], $chunk);

		// Asegurar que el título no quede vacío / oculto.
		if (preg_match('/<(h[2-4])\b([^>]*\bclass=(["\'])(?:(?!\3).)*\bfv-tax-title\b(?:(?!\3).)*\3[^>]*)>([\s\S]*?)<\/\1>/i', $chunk, $tm)) {
			$title = trim(html_entity_decode(strip_tags($tm[4]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
			if ($title !== '') {
				$level = strtolower($tm[1]);
				$safe = htmlspecialchars($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
				$chunk = preg_replace(
					'/<h[2-4]\b[^>]*\bfv-tax-title\b[^>]*>[\s\S]*?<\/h[2-4]>/i',
					'<' . $level . ' class="fv-tax-title">' . $safe . '</' . $level . '>',
					$chunk,
					1
				) ?? $chunk;
			}
		}

		// Embebido de foto de categoría/grupo si hay <img class="fv-tax-photo">.
		if (preg_match('/<img\b[^>]*\bfv-tax-photo\b[^>]*>/i', $chunk)) {
			$chunk = preg_replace_callback(
				'/<img\b([^>]*)\bsrc=(["\'])([^"\']+)\2([^>]*)>/i',
				function (array $m): string {
					$src = trim(html_entity_decode($m[3], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
					if ($src !== '' && isset($this->_studioPdfDataUriMap[$src])) {
						$src = (string) $this->_studioPdfDataUriMap[$src];
					}
					$tag = $this->studioContainedImageTagForPdf($src, 28);
					if (preg_match('/\bsrc=(["\'])([^"\']+)\1/i', $tag, $sm)) {
						return '<img class="fv-tax-photo" src="' . $sm[2] . '" width="22" height="22" alt="">';
					}
					return '';
				},
				$chunk
			) ?? $chunk;
		}

		return $chunk;
	}

	/**
	 * Elimina el CSS del preview (flex/grid/var) que hace colgar Dompdf.
	 * El CSS PDF se inyecta aparte en studioStreamPdfFromHtml.
	 */
	protected function studioScrubPreviewCssForPdf(string $html): string
	{
		// Eliminar TODO el CSS del preview (incluye :root{--fv-*} y var()).
		// Dompdf 3 se cuelga resolviendo custom properties (Css/Style.php).
		// Los colores de tema se aplican luego como hex en studioPdfCompatibilityCss().
		$replaced = preg_replace('/<style\b[^>]*>[\s\S]*?<\/style>/i', '', $html);
		return is_string($replaced) ? $replaced : $html;
	}

	/**
	 * Tarjeta PDF uniforme: borde en celda, foto contain centrada, textos completos.
	 */
	protected function studioProductCardToPdfTable(string $chunk): string
	{
		// Más alto el cuerpo para nombre de 2 líneas + SKU + precio sin recorte.
		$imgH = 158;
		$bodyH = 108;
		$imgBox = 148;

		$imgInner = '';
		if (preg_match(
			'/<div\b[^>]*\bclass=(["\'])(?:(?!\1).)*\bproduct-img\b(?:(?!\1).)*\1[^>]*>/i',
			$chunk,
			$im,
			PREG_OFFSET_CAPTURE
		)) {
			$openEnd = (int) $im[0][1] + strlen($im[0][0]);
			$closePos = $this->studioFindMatchingClosingTag($chunk, 'div', $openEnd);
			if ($closePos >= 0) {
				$imgInner = trim(substr($chunk, $openEnd, $closePos - $openEnd));
			}
		}

		$src = '';
		if ($imgInner !== '' && preg_match('/<img\b[^>]*\bsrc=(["\'])([^"\']*)\1/i', $imgInner, $sm)) {
			$src = trim(html_entity_decode($sm[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
		}
		// Fallback: data-src / data-original (lazy-load)
		if ($src === '' && $imgInner !== '' && preg_match('/<img\b[^>]*\b(?:data-src|data-original)=(["\'])([^"\']+)\1/i', $imgInner, $dm)) {
			$src = trim(html_entity_decode($dm[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
		}
		// Fallback: cualquier URL uploads/ o http en el bloque de imagen
		if ($src === '' && $imgInner !== '' && preg_match('#https?://[^"\'\s<>]+#i', $imgInner, $um)) {
			$src = trim($um[0]);
		}
		if ($src === '' && $imgInner !== '' && preg_match('#/(uploads/[^"\'\s<>]+)#i', $imgInner, $um2)) {
			$src = $um2[0];
		}
		$imgInner = $this->studioContainedImageTagForPdf($src, $imgBox);

		$bodyInner = '';
		if (preg_match(
			'/<div\b[^>]*\bclass=(["\'])(?:(?!\1).)*\bproduct-body\b(?:(?!\1).)*\1[^>]*>/i',
			$chunk,
			$bm,
			PREG_OFFSET_CAPTURE
		)) {
			$openEnd = (int) $bm[0][1] + strlen($bm[0][0]);
			$closePos = $this->studioFindMatchingClosingTag($chunk, 'div', $openEnd);
			if ($closePos >= 0) {
				$bodyInner = trim(substr($chunk, $openEnd, $closePos - $openEnd));
			}
		}
		if ($bodyInner === '') {
			return $chunk;
		}

		$cardH = $imgH + $bodyH;
		return '<table class="pdf-product-card" width="100%" cellspacing="0" cellpadding="0" border="0"'
			. ' style="width:100%;height:' . $cardH . 'px;border-collapse:collapse;background:#fff;table-layout:fixed;">'
			. '<tr><td class="pdf-card-img" width="100%" height="' . $imgH . '" align="center" valign="middle"'
			. ' style="height:' . $imgH . 'px;background:#fff;text-align:center;vertical-align:middle;padding:4px 4px 2px;overflow:hidden;">'
			. $imgInner
			. '</td></tr>'
			. '<tr><td class="pdf-card-body" width="100%" height="' . $bodyH . '" valign="top"'
			. ' style="height:' . $bodyH . 'px;background:#fff;vertical-align:top;padding:4px 7px 8px;overflow:visible;text-align:left;">'
			. $bodyInner
			. '</td></tr>'
			. '</table>';
	}

	/**
	 * Imagen local (ruta bajo chroot) o data-URI; tamaño contain exacto.
	 * Preferir archivo local: Dompdf con chroot es más fiable que HTTP/data-URI masivos.
	 */
	protected function studioContainedImageTagForPdf(string $src, int $size): string
	{
		static $cache = [];
		$size = max(16, min(220, $size));
		$placeholder = $this->studioPdfPlaceholderSquare($size);

		$src = trim($src);
		// Resolver placeholders de protectDataUris (fvpdfdataNx) → data-URI real.
		if ($src !== '' && isset($this->_studioPdfDataUriMap[$src])) {
			$src = (string) $this->_studioPdfDataUriMap[$src];
		}
		if ($src === '' || stripos($src, 'no-image') !== false || stripos($src, 'noimg') !== false) {
			return $placeholder;
		}

		$cacheKey = md5(strtolower(substr($src, 0, 120)) . '|' . strlen($src) . '|file|' . $size);
		if (isset($cache[$cacheKey])) {
			return $cache[$cacheKey];
		}

		$resolved = $src;
		$local = null;
		$iw = 0;
		$ih = 0;
		$mime = 'image/jpeg';
		$imgSrcAttr = null;

		// data:image/...;base64,... → archivo temporal (Dompdf lo maneja bien).
		if (preg_match('#^data:image/([a-z0-9.+-]+);base64,(.+)$#is', $resolved, $dm)) {
			$bin = base64_decode(preg_replace('/\s+/', '', $dm[2]), true);
			if (is_string($bin) && strlen($bin) > 24 && strlen($bin) < 8 * 1024 * 1024) {
				$extMap = [
					'jpeg' => 'jpg',
					'jpg' => 'jpg',
					'png' => 'png',
					'gif' => 'gif',
					'webp' => 'webp',
					'svg+xml' => 'svg',
				];
				$subtype = strtolower($dm[1]);
				$ext = $extMap[$subtype] ?? 'img';
				$tmp = tempnam(sys_get_temp_dir(), 'fvpdfimg_');
				if ($tmp !== false) {
					$tmpFile = $tmp . '.' . $ext;
					@rename($tmp, $tmpFile);
					if (@file_put_contents($tmpFile, $bin) !== false) {
						$info = @getimagesize($tmpFile);
						if (is_array($info) && !empty($info[0]) && !empty($info[1])) {
							$iw = (int) $info[0];
							$ih = (int) $info[1];
							$mime = !empty($info['mime']) ? (string) $info['mime'] : ('image/' . $subtype);
							$imgSrcAttr = $tmpFile;
						} else {
							@unlink($tmpFile);
						}
					}
				}
			}
		}

		if ($imgSrcAttr === null) {
			// Ruta absoluta ya en disco.
			if (is_file($resolved) && is_readable($resolved)) {
				$local = $resolved;
			} else {
				if (preg_match('#^//#', $resolved)) {
					$host = (string) (parse_url('http:' . $resolved, PHP_URL_HOST) ?? '');
					$scheme = (stripos($host, 'localhost') !== false
						|| stripos($host, 'laravel.test') !== false
						|| $host === '127.0.0.1')
						? 'http:'
						: 'https:';
					$resolved = $scheme . $resolved;
				} elseif (preg_match('#^/#', $resolved)) {
					$resolved = rtrim((string) base_url(), '/') . $resolved;
				}
				$local = $this->studioResolveLocalImagePath($resolved);
				if ($local === null && preg_match('#^https?://#i', $resolved)) {
					$pathOnly = (string) (parse_url($resolved, PHP_URL_PATH) ?? '');
					if ($pathOnly !== '') {
						$local = $this->studioResolveLocalImagePath($pathOnly);
					}
				}
			}

			if ($local !== null) {
				$info = @getimagesize($local);
				if (is_array($info) && !empty($info[0]) && !empty($info[1])) {
					$iw = (int) $info[0];
					$ih = (int) $info[1];
					$mime = !empty($info['mime']) ? (string) $info['mime'] : 'image/jpeg';
					$imgSrcAttr = $local;
				}
			}
		}

		if ($imgSrcAttr === null && !empty($this->_studioPdfAllowRemoteImages) && preg_match('#^https?://#i', $resolved)) {
			$ctx = stream_context_create([
				'http' => [
					'timeout' => 3,
					'follow_location' => 1,
					'max_redirects' => 2,
					'user_agent' => 'FullVendor-CatalogPDF/1.0',
				],
				'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
			]);
			$bin = @file_get_contents($resolved, false, $ctx);
			if (is_string($bin) && strlen($bin) > 24 && strlen($bin) < 5 * 1024 * 1024) {
				$tmp = tempnam(sys_get_temp_dir(), 'fvpdfimg_');
				if ($tmp !== false) {
					$ext = 'jpg';
					if (stripos($resolved, '.png') !== false || (isset($http_response_header) && stripos(implode(' ', $http_response_header), 'image/png') !== false)) {
						$ext = 'png';
					} elseif (stripos($resolved, '.gif') !== false) {
						$ext = 'gif';
					} elseif (stripos($resolved, '.webp') !== false) {
						$ext = 'webp';
					}
					$tmpFile = $tmp . '.' . $ext;
					@rename($tmp, $tmpFile);
					@file_put_contents($tmpFile, $bin);
					$info = @getimagesize($tmpFile);
					if (is_array($info) && !empty($info[0]) && !empty($info[1])) {
						$iw = (int) $info[0];
						$ih = (int) $info[1];
						$mime = !empty($info['mime']) ? (string) $info['mime'] : 'image/jpeg';
						$imgSrcAttr = $tmpFile;
					} else {
						@unlink($tmpFile);
					}
				}
			}
		}

		if ($imgSrcAttr === null || $iw < 1 || $ih < 1) {
			return $cache[$cacheKey] = $placeholder;
		}

		$scale = min($size / $iw, $size / $ih);
		$dw = max(1, (int) round($iw * $scale));
		$dh = max(1, (int) round($ih * $scale));

		$tag = '<img src="' . htmlspecialchars($imgSrcAttr, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"'
			. ' width="' . $dw . '" height="' . $dh . '"'
			. ' style="width:' . $dw . 'px!important;height:' . $dh . 'px!important;'
			. 'max-width:' . $size . 'px!important;max-height:' . $size . 'px!important;'
			. 'border:0;display:inline;vertical-align:middle;" alt="">';
		return $cache[$cacheKey] = $tag;
	}

	protected function studioResolveLocalImagePath(string $src): ?string
	{
		$path = parse_url($src, PHP_URL_PATH);
		if (!is_string($path) || $path === '') {
			$path = $src;
		}
		$rel = null;
		if (preg_match('#/(uploads/.+)$#i', $path, $pm)) {
			$rel = $pm[1];
		} elseif (preg_match('#/(images/logos/.+)$#i', $path, $pmLogo)) {
			$rel = $pmLogo[1];
		} elseif (preg_match('#^(uploads/.+)$#i', ltrim($src, '/'), $pm2)) {
			$rel = $pm2[1];
		} elseif (preg_match('#^(images/logos/.+)$#i', ltrim($src, '/'), $pm3)) {
			$rel = $pm3[1];
		} elseif (preg_match('#/api/v2/images/([0-9a-f\-]+)(?:\.[a-z0-9]+)?$#i', $path, $pmApi)
			|| preg_match('#/(?:company/)?image/([0-9a-f\-]+)(?:\.[a-z0-9]+)?$#i', $path, $pmApi)
		) {
			$uuid = $pmApi[1];
			// 1) Disco images_local / uploads vía nombre conocido.
			$file = $pmApi[1];
			$apiCandidates = [];
			if (defined('FCPATH')) {
				$base = rtrim((string) FCPATH, '/');
				$apiCandidates[] = $base . '/../api/storage/app/images/' . $file;
				// Buscar por UUID en uploads (incluye logos de catálogo).
				foreach (['users', 'products', 'categories', 'company', 'catalogs'] as $dir) {
					foreach (['jpg', 'jpeg', 'png', 'webp', 'gif'] as $ext) {
						$apiCandidates[] = $base . '/uploads/' . $dir . '/' . $uuid . '.' . $ext;
					}
				}
				// images.path suele ser catalogs/{id}/nombre.ext — resolver vía API.
				$imageRecord = $this->getObjectFromApi('image/' . $uuid);
				if ($this->hasError($imageRecord) || !is_object($imageRecord)) {
					$imageRecord = $this->getObjectFromApi('images/' . $uuid);
				}
				if (!$this->hasError($imageRecord) && is_object($imageRecord)) {
					$imgPath = trim((string) ($imageRecord->path ?? ''));
					if ($imgPath !== '') {
						$apiCandidates[] = $base . '/uploads/' . ltrim($imgPath, '/');
					}
				}
			}
			$apiCandidates[] = '/var/www/html/../api/storage/app/images/' . $file;
			$apiCandidates[] = '/var/www/api/storage/app/images/' . $file;
			foreach ($apiCandidates as $cand) {
				$real = realpath($cand);
				if ($real !== false && is_file($real) && is_readable($real)) {
					return $real;
				}
				if (is_file($cand) && is_readable($cand)) {
					return $cand;
				}
			}
			// 2) Descargar vía API interna solo si está permitido (PDF grande = off,
			//    excepto logo forzado temporalmente a true).
			if (empty($this->_studioPdfAllowRemoteImages)) {
				return null;
			}
			$apiUrls = [
				'http://laravel.test/api/v2/images/' . $uuid,
				'http://localhost/api/v2/images/' . $uuid,
				rtrim((string) base_url(), '/') . '/api/v2/images/' . $uuid,
			];
			foreach ($apiUrls as $apiUrl) {
				$ctx = stream_context_create([
					'http' => [
						'timeout' => 5,
						'follow_location' => 1,
						'user_agent' => 'FullVendor-CatalogPDF/1.0',
					],
					'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
				]);
				$bin = @file_get_contents($apiUrl, false, $ctx);
				if (!is_string($bin) || strlen($bin) < 24 || strlen($bin) > 5 * 1024 * 1024) {
					continue;
				}
				$tmp = tempnam(sys_get_temp_dir(), 'fvpdfapi_');
				if ($tmp === false) {
					continue;
				}
				$tmpFile = $tmp . '.img';
				@rename($tmp, $tmpFile);
				if (@file_put_contents($tmpFile, $bin) === false) {
					@unlink($tmpFile);
					continue;
				}
				$info = @getimagesize($tmpFile);
				if (is_array($info) && !empty($info[0])) {
					return $tmpFile;
				}
				@unlink($tmpFile);
			}
		}
		if ($rel === null) {
			return null;
		}
		$rel = str_replace('\\', '/', $rel);
		$candidates = [];
		$bases = [];
		if (defined('FCPATH')) {
			$bases[] = rtrim((string) FCPATH, '/');
		}
		$doc = rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
		if ($doc !== '') {
			$bases[] = $doc;
		}
		$bases[] = '/var/www/html';
		$bases = array_values(array_unique(array_filter($bases)));

		foreach ($bases as $base) {
			$candidates[] = $base . '/' . $rel;
			$dir = dirname($base . '/' . $rel);
			$baseName = basename($rel);
			if (is_dir($dir)) {
				$lower = strtolower($baseName);
				foreach ((array) @scandir($dir) as $entry) {
					if ($entry === '.' || $entry === '..') {
						continue;
					}
					if (strtolower($entry) === $lower) {
						$candidates[] = $dir . '/' . $entry;
					}
				}
			}
		}
		foreach ($candidates as $local) {
			if (is_file($local) && is_readable($local)) {
				return $local;
			}
		}
		return null;
	}

	protected function studioPdfPlaceholderSquare(int $size): string
	{
		static $bySize = [];
		$size = max(16, min(220, (int) $size));
		if (isset($bySize[$size])) {
			return $bySize[$size];
		}
		if (!function_exists('imagecreatetruecolor') || !function_exists('imagepng')) {
			return $bySize[$size] = '<span>&nbsp;</span>';
		}
		$im = imagecreatetruecolor($size, $size);
		$white = imagecolorallocate($im, 255, 255, 255);
		$gray = imagecolorallocate($im, 203, 213, 225);
		$dark = imagecolorallocate($im, 148, 163, 184);
		imagefilledrectangle($im, 0, 0, $size, $size, $white);
		imagerectangle($im, 6, 6, $size - 7, $size - 7, $gray);
		$cx = (int) ($size / 2);
		$cy = (int) ($size / 2);
		imagefilledellipse($im, $cx, $cy + 4, (int) ($size * 0.28), (int) ($size * 0.22), $gray);
		imagefilledellipse($im, $cx, $cy + 4, (int) ($size * 0.14), (int) ($size * 0.14), $dark);
		imagefilledrectangle($im, $cx - 10, $cy - 18, $cx + 10, $cy - 10, $gray);
		ob_start();
		imagepng($im);
		$png = ob_get_clean();
		imagedestroy($im);

		$tmp = tempnam(sys_get_temp_dir(), 'fvpdfph_');
		if ($tmp === false) {
			return $bySize[$size] = '<span>&nbsp;</span>';
		}
		$tmpFile = $tmp . '.png';
		@rename($tmp, $tmpFile);
		if (@file_put_contents($tmpFile, (string) $png) === false) {
			@unlink($tmpFile);
			return $bySize[$size] = '<span>&nbsp;</span>';
		}

		$tag = '<img src="' . htmlspecialchars($tmpFile, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"'
			. ' width="' . $size . '" height="' . $size . '" alt="">';
		return $bySize[$size] = $tag;
	}

	/**
	 * Encuentra el cierre que empareja el tag abierto en $innerStart (índice tras el '>').
	 */
	protected function studioFindMatchingClosingTag(string $html, string $tag, int $innerStart): int
	{
		$tag = strtolower($tag);
		$openNeedle = '<' . $tag;
		$closeNeedle = '</' . $tag . '>';
		$closeLen = strlen($closeNeedle);
		$len = strlen($html);
		$depth = 1;
		$i = $innerStart;
		$guards = 0;
		$maxGuards = max(1000, (int) (($len - $innerStart) / 8) + 50);
		while ($i < $len && $depth > 0) {
			if (++$guards > $maxGuards) {
				return -1;
			}
			$nextOpen = stripos($html, $openNeedle, $i);
			$nextClose = stripos($html, $closeNeedle, $i);
			if ($nextClose === false) {
				return -1;
			}
			// Evitar falsos positivos: <divxxx o <articlefoo
			if ($nextOpen !== false && $nextOpen < $nextClose) {
				$after = $nextOpen + strlen($openNeedle);
				$ch = $after < $len ? $html[$after] : '';
				if ($ch !== '>' && $ch !== ' ' && $ch !== "\t" && $ch !== "\n" && $ch !== "\r" && $ch !== '/') {
					$i = $after;
					continue;
				}
				$endBracket = strpos($html, '>', $nextOpen);
				if ($endBracket === false) {
					return -1;
				}
				// Tags auto-cerrados no aumentan profundidad.
				$selfClosing = ($endBracket > $nextOpen && $html[$endBracket - 1] === '/');
				if (!$selfClosing) {
					$depth++;
				}
				$i = $endBracket + 1;
				continue;
			}
			$depth--;
			if ($depth === 0) {
				return (int) $nextClose;
			}
			$i = $nextClose + $closeLen;
		}
		return -1;
	}

	/** @deprecated Usar studioFindMatchingClosingTag('div', ...) */
	protected function studioFindMatchingClosingDiv(string $html, int $innerStart): int
	{
		return $this->studioFindMatchingClosingTag($html, 'div', $innerStart);
	}

	/**
	 * CSS PDF: grilla uniforme, aire bajo header, categorías legibles, bordes visibles.
	 */
	/**
	 * CSS mínimo para Dompdf. Sin custom properties ni var() (cuelgue en Style.php).
	 *
	 * @param array{banner_bg?:string,banner_text?:string,footer_bg?:string,footer_text?:string} $theme
	 */
	protected function studioPdfCompatibilityCss(array $theme = []): string
	{
		$bannerBg = htmlspecialchars($theme['banner_bg'] ?? '#B91C1C', ENT_QUOTES, 'UTF-8');
		$bannerText = htmlspecialchars($theme['banner_text'] ?? '#FFFFFF', ENT_QUOTES, 'UTF-8');
		$footerBg = htmlspecialchars($theme['footer_bg'] ?? $bannerBg, ENT_QUOTES, 'UTF-8');
		$footerText = htmlspecialchars($theme['footer_text'] ?? $bannerText, ENT_QUOTES, 'UTF-8');

		return implode('', [
			'@page{margin:34mm 0 16mm 0;}',
			'body{background:#fff;padding:0 8mm;margin:0;font-family:DejaVu Sans,sans-serif;}',
			'.wrap{max-width:100%;padding:0;margin:0;}',
			'.footer{display:none;}',
			'.pdf-page-header{position:fixed;top:-34mm;left:0;right:0;width:100%;}',
			'.pdf-header-table{width:100%;border-collapse:collapse;background:' . $bannerBg . ';}',
			'.pdf-page-header .banner,.pdf-page-header .pdf-page-header-inner{margin:0;width:100%;max-width:none;padding:0;background:transparent;color:' . $bannerText . ';}',
			'.pdf-page-header .wrap-inner{max-width:none;width:100%;margin:0;padding:0;background:transparent;color:' . $bannerText . ';}',
			'.pdf-page-header td{color:' . $bannerText . ';background:' . $bannerBg . ';}',
			'.pdf-page-footer{position:fixed;bottom:-16mm;left:0;right:0;width:100%;}',
			'.pdf-footer-table{width:100%;border-collapse:collapse;background:' . $footerBg . ';}',
			'.pdf-page-footer-inner{width:100%;padding:7px 8mm;font-size:8.5px;line-height:1.35;text-align:center;color:' . $footerText . ';background:' . $footerBg . ';}',
			'.pdf-page-footer-inner strong{font-size:9.5px;color:inherit;}',
			'.pdf-header-spacer{height:22px;margin:0;padding:0;border:0;line-height:22px;font-size:1px;color:#fff;}',
			'.pdf-banner-source{display:none;height:0;margin:0;padding:0;overflow:hidden;}',
			'.products{display:block;width:100%;margin:0;padding:14px 0 0;}',
			'.pdf-product-grid{width:100%;border-collapse:separate;border-spacing:5px 8px;table-layout:fixed;margin:6px 0 0;}',
			'.pdf-grid-row{page-break-inside:avoid;}',
			'.pdf-grid-header-row{page-break-after:avoid;page-break-inside:avoid;}',
			'.pdf-grid-header-cell{padding:12px 2px 14px;vertical-align:middle;}',
			'.pdf-grid-cell{vertical-align:top;height:275px;max-height:275px;padding:0;border:1.5px solid #9ca3af;background:#fff;}',
			'.pdf-grid-cell--empty{border:0;height:275px;background:transparent;}',
			'.fv-tax-header,div.fv-tax-header{display:block;width:100%;margin:10px 0 8px;padding:6px 0;line-height:1.35;background:transparent;border:0;}',
			'.fv-tax-header--category,div.fv-tax-header--category{margin:14px 0 10px;padding:0 0 8px;border-bottom:2.5px solid #111827;font-weight:800;}',
			'.fv-tax-header--group,div.fv-tax-header--group{margin:12px 0 8px;padding:2px 0 2px 10px;border-left:4px solid #dc2626;}',
			'.fv-tax-header--subgroup,div.fv-tax-header--subgroup{margin:8px 0 10px;color:#4b5563;}',
			'.fv-tax-photo{width:22px;height:22px;vertical-align:middle;margin-right:8px;}',
			'.fv-tax-title,h2.fv-tax-title,h3.fv-tax-title,h4.fv-tax-title{display:inline;vertical-align:middle;margin:0;padding:0;color:#111827;font-family:DejaVu Sans,sans-serif;}',
			'h2.fv-tax-title,.fv-tax-header--category .fv-tax-title{font-size:15px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:#111827;}',
			'h3.fv-tax-title,.fv-tax-header--group .fv-tax-title{font-size:12.5px;font-weight:700;color:#1f2937;}',
			'h4.fv-tax-title,.fv-tax-header--subgroup .fv-tax-title{font-size:11px;font-weight:600;color:#4b5563;}',
			'.pdf-product-card{width:100%;height:266px;max-height:266px;margin:0;border:0;background:#fff;overflow:hidden;}',
			'.pdf-card-img{height:158px;max-height:158px;width:100%;background:#fff;text-align:center;vertical-align:middle;overflow:hidden;padding:4px;}',
			'.pdf-card-img img{max-width:148px;max-height:148px;border:0;vertical-align:middle;}',
			'.pdf-card-body{height:108px;max-height:108px;padding:4px 7px 8px;line-height:1.2;text-align:left;vertical-align:top;}',
			'.product-sku{font-size:9px;line-height:1.2;margin:0 0 2px;color:#6b7280;}',
			'.product-name{font-size:10.5px;font-weight:700;line-height:1.25;margin:0 0 4px;max-height:2.6em;overflow:hidden;color:#111827;}',
			'.product-rating{margin:0 0 3px;font-size:9px;line-height:1;height:12px;overflow:hidden;}',
			'.product-rating__star{display:none;}',
			'.product-rating__value{font-size:9px;color:#6b7280;}',
			'.product-price{font-size:12px;font-weight:800;margin:2px 0 0;display:block;}',
			'.product-price.is-hidden{display:none;}',
			'.featured{display:none;}',
			'.featured.is-visible{display:block;background:#f3f4f6;border:1px solid #e5e7eb;padding:8px;margin-bottom:6px;font-size:12px;}',
			'.banner{width:100%;max-width:none;margin:0;background:transparent;color:' . $bannerText . ';}',
			'.banner .wrap-inner{display:table;width:100%;max-width:none;table-layout:fixed;margin:0;background:transparent;}',
			'.banner .wrap-inner > div{display:table-cell;vertical-align:middle;background:transparent;color:' . $bannerText . ';}',
			'.fv-catalog-logo{display:table-cell;vertical-align:middle;width:24%;text-align:left;padding-right:10px;}',
			'.fv-catalog-logo img{max-height:40px;max-width:120px;background:#fff;}',
			'.empty[hidden]{display:none;}',
		]);
	}

	/**
	 * Select2: buscar órdenes de la empresa para origen de catálogo.
	 */
	public function studioSearchOrders()
	{
		while (ob_get_level()) {
			ob_end_clean();
		}
		header('Content-Type: application/json; charset=utf-8');

		$companyId = (int) $this->getCompanyId();
		$q = trim((string) ($this->input->get('q') ?? $this->input->get('term') ?? $this->input->get('search') ?? ''));
		$page = max(1, (int) ($this->input->get('page') ?? 1));
		$perPage = 20;

		$rows = [];
		try {
			// Listado paginado + search (evitar order/list que carga todas las órdenes).
			$params = [
				'company_id' => $companyId,
				'perPage' => $perPage,
				'page' => $page,
				'orderProperty' => 'order_id',
				'orderDirection' => 'desc',
				'relations' => ['customer', 'statusOrder'],
			];
			if ($q !== '') {
				$params['search'] = $q;
			}

			$resp = $this->getDataFromApi('order', $params);
			$list = [];
			$total = 0;
			$lastPage = $page;

			if (!$this->hasError($resp)) {
				if (is_array($resp)) {
					$list = $resp['data'] ?? [];
					$total = (int) ($resp['total'] ?? $resp['meta']['total'] ?? count($list));
					$lastPage = (int) ($resp['last_page'] ?? $resp['meta']['last_page'] ?? $page);
					// Laravel paginator a veces anida data.data
					if (isset($list['data']) && is_array($list['data']) && !$this->isListArray($list)) {
						$total = (int) ($list['total'] ?? $total);
						$lastPage = (int) ($list['last_page'] ?? $lastPage);
						$list = $list['data'];
					}
				} elseif (is_object($resp)) {
					$list = $resp->data ?? [];
					$total = (int) ($resp->total ?? 0);
					$lastPage = (int) ($resp->last_page ?? $page);
					if (is_object($list) && isset($list->data)) {
						$total = (int) ($list->total ?? $total);
						$lastPage = (int) ($list->last_page ?? $lastPage);
						$list = $list->data;
					}
				}
			}

			if (is_object($list)) {
				$list = (array) $list;
			}
			if (!is_array($list)) {
				$list = [];
			}

			$currency = $this->currencySymbol();
			$formatNumeric = $this->format_numeric;
			$formatPrice = static function ($value) use ($currency, $formatNumeric): string {
				if ($value === null || $value === '') {
					return '';
				}
				if (function_exists('fv_format_money')) {
					return trim($currency . ' ' . fv_format_money($value, $formatNumeric, null));
				}
				$digits = is_object($formatNumeric) ? (int) ($formatNumeric->digits ?? 2) : 2;
				$radix = is_object($formatNumeric) ? (string) ($formatNumeric->radixPoint ?? '.') : '.';
				$group = is_object($formatNumeric) ? (string) ($formatNumeric->groupSeparator ?? ',') : ',';
				return trim($currency . ' ' . number_format((float) $value, $digits, $radix, $group));
			};

			foreach ($list as $order) {
				if (!is_array($order) && !is_object($order)) {
					continue;
				}
				$order = is_object($order) ? $order : (object) $order;
				$oid = (int) ($order->order_id ?? $order->id ?? 0);
				if ($oid < 1) {
					continue;
				}
				$number = trim((string) ($order->order_number ?? $oid));
				$customer = $order->customer ?? null;
				$customerName = '';
				if (is_object($customer)) {
					$customerName = trim((string) ($customer->business_name ?? $customer->name ?? ''));
				} elseif (is_array($customer)) {
					$customerName = trim((string) ($customer['business_name'] ?? $customer['name'] ?? ''));
				}

				$status = $order->statusOrder ?? $order->status_order ?? null;
				$statusName = '';
				if (is_object($status)) {
					$statusName = trim((string) ($status->name_status_english ?? $status->name_status_spanish ?? $status->name ?? ''));
				} elseif (is_array($status)) {
					$statusName = trim((string) ($status['name_status_english'] ?? $status['name_status_spanish'] ?? $status['name'] ?? ''));
				}
				if ($statusName === '') {
					$statusName = trim((string) ($order->order_status_label ?? $order->status_name ?? ''));
				}

				$rawDate = (string) ($order->created_at ?? $order->created ?? $order->order_date ?? '');
				$dateLabel = '';
				if ($rawDate !== '') {
					$ts = strtotime($rawDate);
					if ($ts) {
						$dateLabel = date('Y-m-d', $ts);
					}
				}

				$totalLabel = $formatPrice($order->total ?? $order->order_total ?? null);
				$title = '#' . $number . ($customerName !== '' ? (' — ' . $customerName) : '');
				$metaParts = array_values(array_filter([$dateLabel, $totalLabel, $statusName], static function ($part) {
					return $part !== null && trim((string) $part) !== '';
				}));
				$meta = implode(' · ', $metaParts);

				$rows[] = [
					'id' => $oid,
					'text' => $title,
					'order_number' => $number,
					'customer_name' => $customerName,
					'date' => $dateLabel,
					'total' => $totalLabel,
					'status' => $statusName,
					'title' => $title,
					'meta' => $meta,
				];
			}

			echo json_encode([
				'results' => $rows,
				'pagination' => ['more' => $page < $lastPage || ($total > 0 && ($page * $perPage) < $total)],
			], JSON_UNESCAPED_UNICODE);
			return;
		} catch (\Throwable $e) {
			log_message('error', 'Catalog::studioSearchOrders - ' . $e->getMessage());
			echo json_encode([
				'results' => [],
				'pagination' => ['more' => false],
				'msg' => $this->lang_catalog['studio_order_empty'] ?? '',
			], JSON_UNESCAPED_UNICODE);
		}
	}

	/**
	 * ¿Array de lista (0..n) o mapa asociativo?
	 */
	private function isListArray($value): bool
	{
		if (!is_array($value)) {
			return false;
		}
		if ($value === []) {
			return true;
		}
		return array_keys($value) === range(0, count($value) - 1);
	}

	/**
	 * Productos de una orden para poblar el catálogo (mismo shape que Select2 de SKUs).
	 */
	public function studioOrderProducts()
	{
		while (ob_get_level()) {
			ob_end_clean();
		}
		header('Content-Type: application/json; charset=utf-8');

		$orderId = (int) ($this->input->post('order_id') ?? $this->input->get('order_id') ?? 0);
		if ($orderId < 1) {
			echo json_encode(['success' => false, 'products' => [], 'msg' => $this->lang_catalog['studio_order_required'] ?? ''], JSON_UNESCAPED_UNICODE);
			return;
		}

		$companyId = (int) $this->getCompanyId();
		$order = $this->getObjectFromApi('order/' . $orderId, [
			'company_id' => $companyId,
			'relations' => ['order_details', 'order_details.product'],
		]);
		if ($this->hasError($order) || !is_object($order)) {
			$order = $this->getObjectFromApi('order/' . $orderId . '/full', [
				'company_id' => $companyId,
			]);
		}
		if ($this->hasError($order) || !is_object($order)) {
			echo json_encode(['success' => false, 'products' => [], 'msg' => $this->lang_catalog['studio_order_empty'] ?? ''], JSON_UNESCAPED_UNICODE);
			return;
		}
		if (isset($order->company_id) && (int) $order->company_id !== $companyId) {
			echo json_encode(['success' => false, 'products' => [], 'msg' => $this->lang_catalog['studio_order_empty'] ?? ''], JSON_UNESCAPED_UNICODE);
			return;
		}

		$details = $order->order_details ?? $order->orderDetails ?? [];
		if (is_object($details) && isset($details->data)) {
			$details = $details->data;
		}
		if (is_array($details) && isset($details['data']) && is_array($details['data'])) {
			$details = $details['data'];
		}
		if (is_object($details)) {
			$details = (array) $details;
		}
		if (!is_array($details)) {
			$details = [];
		}

		$products = [];
		$seen = [];
		foreach ($details as $detail) {
			$detail = is_object($detail) ? $detail : (object) $detail;
			$prod = $detail->product ?? null;
			if (is_object($prod) && isset($prod->data) && (is_object($prod->data) || is_array($prod->data))) {
				$prod = $prod->data;
			}
			if (!$prod) {
				$pid = (int) ($detail->product_id ?? $detail->pro_id ?? 0);
				if ($pid > 0) {
					$prod = $this->getObjectFromApi('product/' . $pid, [
						'company_id' => $companyId,
						'companyId' => $companyId,
					]);
					if ($this->hasError($prod) || !is_object($prod)) {
						continue;
					}
				}
			}
			$prod = is_object($prod) ? $prod : (is_array($prod) ? (object) $prod : null);
			if (!$prod) {
				continue;
			}
			$pid = (int) ($prod->pro_id ?? $prod->product_id ?? $prod->id ?? $detail->product_id ?? 0);
			if ($pid < 1 || isset($seen[$pid])) {
				continue;
			}
			if (isset($prod->status) && (int) $prod->status !== 1) {
				continue;
			}
			$seen[$pid] = true;
			$sku = trim((string) ($prod->sku ?? $detail->sku ?? ''));
			$name = trim((string) ($prod->name ?? $detail->product_name ?? $detail->name ?? ''));
			$products[] = [
				'id' => $pid,
				'text' => trim($sku . ' | ' . $name, " |"),
				'sku' => $sku,
				'name' => $name,
			];
		}

		echo json_encode([
			'success' => count($products) > 0,
			'products' => $products,
			'count' => count($products),
			'msg' => count($products) > 0 ? '' : ($this->lang_catalog['studio_order_empty'] ?? ''),
		], JSON_UNESCAPED_UNICODE);
	}

	/**
	 * Descarga plantilla Excel/CSV para importar SKUs al Estudio de catálogo.
	 * Columna A = SKU (cabecera "SKU"); incluye SKUs reales de la empresa cuando existan.
	 */
	public function studioSkuTemplate()
	{
		while (ob_get_level()) {
			ob_end_clean();
		}

		$companyId = (int) $this->getCompanyId();
		$examples = $this->studioCollectCompanySkus($companyId, 25);
		$filenameBase = 'catalog_sku_template';

		if (class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
			try {
				$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
				$sheet = $spreadsheet->getActiveSheet();
				$sheet->setTitle('SKUs');
				$sheet->setCellValue('A1', 'SKU');
				$row = 2;
				foreach ($examples as $sku) {
					$sheet->setCellValueExplicit('A' . $row, $sku, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
					$row++;
				}
				// Filas vacías para que el usuario añada más SKUs.
				for ($i = 0; $i < 5; $i++) {
					$sheet->setCellValue('A' . $row, '');
					$row++;
				}
				$sheet->getStyle('A1')->getFont()->setBold(true);
				$sheet->getColumnDimension('A')->setWidth(28);

				$tmp = tempnam(sys_get_temp_dir(), 'fvsku');
				if ($tmp === false) {
					throw new \RuntimeException('tempnam failed');
				}
				$xlsxPath = $tmp . '.xlsx';
				@unlink($tmp);
				$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
				$writer->save($xlsxPath);
				$spreadsheet->disconnectWorksheets();
				unset($spreadsheet);

				header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
				header('Content-Disposition: attachment; filename="' . $filenameBase . '.xlsx"');
				header('Content-Length: ' . (string) filesize($xlsxPath));
				header('Cache-Control: no-store, no-cache, must-revalidate');
				readfile($xlsxPath);
				@unlink($xlsxPath);
				return;
			} catch (\Throwable $e) {
				log_message('error', 'Catalog::studioSkuTemplate xlsx - ' . $e->getMessage());
			}
		}

		$bom = "\xEF\xBB\xBF";
		$lines = ["SKU"];
		foreach ($examples as $sku) {
			$lines[] = $sku;
		}
		$body = $bom . implode("\n", $lines) . "\n";
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename="' . $filenameBase . '.csv"');
		header('Content-Length: ' . (string) strlen($body));
		header('Cache-Control: no-store, no-cache, must-revalidate');
		echo $body;
	}

	/**
	 * SKUs enabled de la compañía para la plantilla de importación.
	 *
	 * @return list<string>
	 */
	protected function studioCollectCompanySkus(int $companyId, int $limit = 25): array
	{
		if ($companyId < 1) {
			return [];
		}
		$skus = [];
		try {
			$resp = $this->getDataFromApi('product/skus', [
				'company_id' => $companyId,
				'companyId' => $companyId,
			]);
			$list = [];
			if (!$this->hasError($resp)) {
				if (is_array($resp)) {
					$list = $resp['data'] ?? $resp;
				} elseif (is_object($resp)) {
					$list = $resp->data ?? [];
				}
			}
			if (is_object($list)) {
				$list = (array) $list;
			}
			if (!is_array($list)) {
				$list = [];
			}
			foreach ($list as $sku) {
				$sku = strtoupper(trim((string) $sku));
				if ($sku === '' || isset($skus[$sku])) {
					continue;
				}
				$skus[$sku] = $sku;
				if (count($skus) >= $limit) {
					break;
				}
			}
		} catch (\Throwable $e) {
			log_message('error', 'Catalog::studioCollectCompanySkus - ' . $e->getMessage());
		}

		if ($skus !== []) {
			return array_values($skus);
		}

		// Fallback: listado paginado de productos enabled.
		try {
			$resp = $this->getDataFromApi('product', [
				'company_id' => $companyId,
				'companyId' => $companyId,
				'status' => 1,
				'perPage' => $limit,
				'page' => 1,
				'orderProperty' => 'sku',
				'orderDirection' => 'asc',
			]);
			$list = [];
			if (!$this->hasError($resp) && is_array($resp)) {
				$list = $resp['data'] ?? [];
				if (isset($list['data']) && is_array($list['data'])) {
					$list = $list['data'];
				}
			} elseif (!$this->hasError($resp) && is_object($resp)) {
				$list = $resp->data ?? [];
				if (is_object($list) && isset($list->data)) {
					$list = $list->data;
				}
			}
			foreach ((array) $list as $prod) {
				$prod = is_object($prod) ? $prod : (object) $prod;
				$sku = strtoupper(trim((string) ($prod->sku ?? '')));
				if ($sku === '' || isset($skus[$sku])) {
					continue;
				}
				$skus[$sku] = $sku;
				if (count($skus) >= $limit) {
					break;
				}
			}
		} catch (\Throwable $e) {
			log_message('error', 'Catalog::studioCollectCompanySkus product list - ' . $e->getMessage());
		}

		return array_values($skus);
	}

	/**
	 * Importa SKUs desde Excel/CSV y resuelve productos enabled de la empresa.
	 */
	public function studioImportSkus()
	{
		while (ob_get_level()) {
			ob_end_clean();
		}
		header('Content-Type: application/json; charset=utf-8');

		$companyId = (int) $this->getCompanyId();
		if (empty($_FILES['file']) || (int) ($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
			echo json_encode(['success' => false, 'products' => [], 'msg' => $this->lang_catalog['studio_excel_required'] ?? ''], JSON_UNESCAPED_UNICODE);
			return;
		}

		$tmp = (string) ($_FILES['file']['tmp_name'] ?? '');
		$clientName = (string) ($_FILES['file']['name'] ?? 'import.csv');
		$ext = strtolower((string) pathinfo($clientName, PATHINFO_EXTENSION));
		$skus = [];

		try {
			if (in_array($ext, ['xlsx', 'xls'], true) && class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
				$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmp);
				$sheet = $spreadsheet->getActiveSheet();
				$rows = $sheet->toArray(null, true, true, false);
				foreach ($rows as $idx => $row) {
					if (!is_array($row)) {
						continue;
					}
					$cell = trim((string) ($row[0] ?? ''));
					if ($cell === '') {
						continue;
					}
					if ($idx === 0 && preg_match('/^(sku|código|codigo|code)$/i', $cell)) {
						continue;
					}
					// Ignorar placeholders de plantillas antiguas.
					if (preg_match('/^SKU[-_]?EJEMPLO/i', $cell)) {
						continue;
					}
					$skus[] = mb_strtoupper($cell, 'UTF-8');
				}
			} else {
				$raw = (string) file_get_contents($tmp);
				$raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw) ?? $raw;
				$lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
				foreach ($lines as $idx => $line) {
					$line = trim((string) $line);
					if ($line === '') {
						continue;
					}
					$parts = str_getcsv($line, strpos($line, ';') !== false ? ';' : ',');
					$cell = trim((string) ($parts[0] ?? ''));
					if ($cell === '') {
						continue;
					}
					if ($idx === 0 && preg_match('/^(sku|código|codigo|code)$/i', $cell)) {
						continue;
					}
					if (preg_match('/^SKU[-_]?EJEMPLO/i', $cell)) {
						continue;
					}
					$skus[] = mb_strtoupper($cell, 'UTF-8');
				}
			}
		} catch (\Throwable $e) {
			log_message('error', 'Catalog::studioImportSkus parse - ' . $e->getMessage());
			echo json_encode(['success' => false, 'products' => [], 'msg' => $this->lang_catalog['studio_excel_error'] ?? ''], JSON_UNESCAPED_UNICODE);
			return;
		}

		$skus = array_values(array_unique(array_filter($skus, static function ($s) {
			return is_string($s) && trim($s) !== '';
		})));
		if ($skus === []) {
			echo json_encode(['success' => false, 'products' => [], 'msg' => $this->lang_catalog['studio_excel_empty'] ?? ''], JSON_UNESCAPED_UNICODE);
			return;
		}

		$products = [];
		$seen = [];
		$notFound = 0;
		foreach (array_slice($skus, 0, 500) as $sku) {
			$prod = $this->studioResolveProductBySku($sku, $companyId);
			if (!$prod) {
				$notFound++;
				continue;
			}
			$pid = (int) ($prod->pro_id ?? $prod->product_id ?? $prod->id ?? 0);
			if ($pid < 1 || isset($seen[$pid])) {
				continue;
			}
			if (isset($prod->status) && (int) $prod->status !== 1) {
				$notFound++;
				continue;
			}
			$seen[$pid] = true;
			$skuVal = trim((string) ($prod->sku ?? $sku));
			$name = trim((string) ($prod->name ?? ''));
			$products[] = [
				'id' => $pid,
				'text' => trim($skuVal . ' | ' . $name, " |"),
				'sku' => $skuVal,
				'name' => $name,
			];
		}

		if ($products === []) {
			$msg = $this->lang_catalog['studio_excel_not_found'] ?? ($this->lang_catalog['studio_excel_empty'] ?? '');
			$msg = str_replace('{count}', (string) $notFound, (string) $msg);
			echo json_encode([
				'success' => false,
				'products' => [],
				'count' => 0,
				'not_found' => $notFound,
				'msg' => $msg,
			], JSON_UNESCAPED_UNICODE);
			return;
		}

		echo json_encode([
			'success' => true,
			'products' => $products,
			'count' => count($products),
			'not_found' => $notFound,
		], JSON_UNESCAPED_UNICODE);
	}

	/**
	 * Resuelve un producto enabled por SKU para la compañía.
	 */
	protected function studioResolveProductBySku(string $sku, int $companyId): ?object
	{
		$sku = trim($sku);
		if ($sku === '' || $companyId < 1) {
			return null;
		}

		$candidates = [
			'product/sku-' . rawurlencode($sku),
			'product/sku-' . $sku,
		];
		foreach ($candidates as $path) {
			$found = $this->getObjectFromApi($path, [
				'company_id' => $companyId,
				'companyId' => $companyId,
			]);
			if ($found === null || $this->hasError($found)) {
				continue;
			}
			if (is_array($found)) {
				if (isset($found['data']) && (is_array($found['data']) || is_object($found['data']))) {
					$found = $found['data'];
				}
				$found = (object) $found;
			}
			if (!is_object($found)) {
				continue;
			}
			$pid = (int) ($found->pro_id ?? $found->product_id ?? $found->id ?? 0);
			if ($pid > 0) {
				return $found;
			}
		}

		return null;
	}

	/**
	 * Sugiere con IA un texto para el mensaje destacado del catálogo.
	 */
	public function suggestFeaturedMessage()
	{
		while (ob_get_level()) {
			ob_end_clean();
		}
		header('Content-Type: application/json; charset=utf-8');

		try {
			$catalogName = trim((string) ($this->input->post('catalog_name') ?? ''));
			$companyName = '';
			if (is_object($this->company_info)) {
				$companyName = trim((string) ($this->company_info->name ?? $this->company_info->company_name ?? ''));
			}
			if ($companyName === '') {
				$companyName = 'FullVendor';
			}

			$categoryNames = $this->input->post('category_names');
			if (!is_array($categoryNames)) {
				$raw = (string) ($this->input->post('category_names') ?? '');
				$categoryNames = $raw !== '' ? preg_split('/\s*,\s*/', $raw) : [];
			}
			$categoryNames = array_values(array_filter(array_map(static function ($n) {
				return strtoupper(trim((string) $n));
			}, $categoryNames), static fn($n) => $n !== ''));
			$categoryNames = array_slice($categoryNames, 0, 12);

			$lang = strtolower((string) ($this->session->userdata('lang') ?? $this->session->userdata('language') ?? 'es'));
			$isEs = (strpos($lang, 'es') === 0);

			if ($isEs) {
				$prompt = 'Genera un mensaje destacado para el banner de un catálogo B2B.';
				if ($catalogName !== '') {
					$prompt .= ' Nombre del catálogo: "' . $catalogName . '".';
				}
				$prompt .= ' Empresa: "' . $companyName . '".';
				if ($categoryNames !== []) {
					$prompt .= ' Categorías incluidas: ' . implode(', ', $categoryNames) . '.';
				}
				$prompt .= ' Máximo 200 caracteres. Solo el texto del mensaje, sin comillas ni explicación.';
			} else {
				$prompt = 'Generate a featured banner message for a B2B wholesale catalog.';
				if ($catalogName !== '') {
					$prompt .= ' Catalog name: "' . $catalogName . '".';
				}
				$prompt .= ' Company: "' . $companyName . '".';
				if ($categoryNames !== []) {
					$prompt .= ' Included categories: ' . implode(', ', $categoryNames) . '.';
				}
				$prompt .= ' Max 200 characters. Return only the message text, no quotes or explanation.';
			}

			$response = $this->apiRequest('assistant/chat', 'POST', [
				'json' => [
					'message' => $prompt,
					'context' => [
						'lang' => $isEs ? 'es' : 'en',
						'screen' => 'catalog/studio/featured',
						'controller' => 'Catalog',
						'method' => 'suggestFeaturedMessage',
					],
				],
			], true);

			if ($this->hasError($response)) {
				$code = is_array($response) ? (string) ($response['code'] ?? '') : '';
				$msg = $this->lang_catalog['studio_featured_ai_error'] ?? '';
				if ($code === 'openai_not_configured') {
					$msg = $this->lang_catalog['studio_featured_ai_unavailable'] ?? $msg;
				} elseif ($code === 'openai_quota_exceeded') {
					$msg = $this->lang_catalog['studio_featured_ai_quota'] ?? $msg;
				}
				echo json_encode([
					'success' => false,
					'msg' => $msg,
					'code' => $code,
				], JSON_UNESCAPED_UNICODE);
				return;
			}

			$text = '';
			if (is_array($response)) {
				$text = trim((string) ($response['reply'] ?? $response['message'] ?? ''));
			}
			$text = trim($text, " \t\n\r\0\x0B\"'");
			if (function_exists('mb_substr') && mb_strlen($text) > 255) {
				$text = mb_substr($text, 0, 255);
			} elseif (strlen($text) > 255) {
				$text = substr($text, 0, 255);
			}

			if ($text === '') {
				echo json_encode([
					'success' => false,
					'msg' => $this->lang_catalog['studio_featured_ai_error'] ?? '',
				], JSON_UNESCAPED_UNICODE);
				return;
			}

			echo json_encode([
				'success' => true,
				'text' => $text,
			], JSON_UNESCAPED_UNICODE);
		} catch (\Throwable $e) {
			log_message('error', 'Catalog::suggestFeaturedMessage - ' . $e->getMessage());
			echo json_encode([
				'success' => false,
				'msg' => $this->lang_catalog['studio_featured_ai_error'] ?? '',
			], JSON_UNESCAPED_UNICODE);
		}
	}

	protected function loadCatalogTemplateManifestFile(): array
	{
		$path = FCPATH . 'companyasset/company/catalog/templates/manifest.json';
		if (!is_file($path)) {
			return [];
		}
		$json = json_decode((string) file_get_contents($path), true);
		return is_array($json) ? $json : [];
	}

	protected function loadCatalogTemplateManifest(): array
	{
		$json = $this->loadCatalogTemplateManifestFile();
		if (empty($json['templates']) || !is_array($json['templates'])) {
			return [];
		}
		return $json['templates'];
	}

	protected function loadCatalogColorPresets(): array
	{
		$json = $this->loadCatalogTemplateManifestFile();
		if (empty($json['color_presets']) || !is_array($json['color_presets'])) {
			return [];
		}
		return $json['color_presets'];
	}

	protected function sanitizeHexColor(?string $value, string $fallback): string
	{
		$raw = strtoupper(ltrim(trim((string) $value), '#'));
		if (preg_match('/^[0-9A-F]{6}$/', $raw)) {
			return '#' . $raw;
		}
		if (preg_match('/^[0-9A-F]{3}$/', $raw)) {
			return '#' . $raw[0] . $raw[0] . $raw[1] . $raw[1] . $raw[2] . $raw[2];
		}
		$fb = strtoupper(ltrim(trim($fallback), '#'));
		if (!preg_match('/^[0-9A-F]{6}$/', $fb)) {
			$fb = '111827';
		}
		return '#' . $fb;
	}

	protected function defaultThemeColorsForTemplate(string $templateKey): array
	{
		foreach ($this->loadCatalogTemplateManifest() as $tpl) {
			if (($tpl['key'] ?? '') !== $templateKey) {
				continue;
			}
			$d = is_array($tpl['defaults'] ?? null) ? $tpl['defaults'] : [];
			return [
				'banner_bg' => $this->sanitizeHexColor($d['banner_bg'] ?? null, '#111827'),
				'banner_text' => $this->sanitizeHexColor($d['banner_text'] ?? null, '#FFFFFF'),
				'footer_bg' => $this->sanitizeHexColor($d['footer_bg'] ?? null, '#111827'),
				'footer_text' => $this->sanitizeHexColor($d['footer_text'] ?? null, '#E5E7EB'),
				'accent' => $this->sanitizeHexColor($d['accent'] ?? null, '#B91C1C'),
			];
		}
		return [
			'banner_bg' => '#111827',
			'banner_text' => '#FFFFFF',
			'footer_bg' => '#111827',
			'footer_text' => '#E5E7EB',
			'accent' => '#B91C1C',
		];
	}

	protected function parseCatalogAttach(string $attach): array
	{
		$attach = trim($attach);
		$defaults = $this->defaultThemeColorsForTemplate('classic-grid');
		if ($attach === '') {
			return array_merge(['template_key' => 'classic-grid', 'show_prices' => true], $defaults);
		}
		$parts = explode('::', $attach);
		$key = preg_replace('/[^a-zA-Z0-9\-]/', '', $parts[0] ?? '') ?: 'classic-grid';
		$manifestKeys = array_column($this->loadCatalogTemplateManifest(), 'key');
		if ($manifestKeys !== [] && !in_array($key, $manifestKeys, true)) {
			$key = 'classic-grid';
		}
		$show = !isset($parts[1]) || $parts[1] !== '0';
		$tplDefaults = $this->defaultThemeColorsForTemplate($key);
		return [
			'template_key' => $key,
			'show_prices' => $show,
			'banner_bg' => $this->sanitizeHexColor($parts[2] ?? null, $tplDefaults['banner_bg']),
			'banner_text' => $this->sanitizeHexColor($parts[3] ?? null, $tplDefaults['banner_text']),
			'footer_bg' => $this->sanitizeHexColor($parts[4] ?? null, $tplDefaults['footer_bg']),
			'footer_text' => $this->sanitizeHexColor($parts[5] ?? null, $tplDefaults['footer_text']),
			'accent' => $this->sanitizeHexColor($parts[6] ?? null, $tplDefaults['accent']),
		];
	}

	protected function buildCatalogAttachFromRequest(array $request): string
	{
		$key = preg_replace('/[^a-zA-Z0-9\-]/', '', (string) ($request['template_key'] ?? '')) ?: '';
		$hasThemeFields = isset($request['theme_banner_bg'])
			|| isset($request['theme_banner_text'])
			|| isset($request['theme_footer_bg'])
			|| isset($request['theme_footer_text'])
			|| isset($request['theme_accent']);

		// Preferir theme_* del formulario (fuente actual del Studio). El string attach
		// puede llegar truncado/stale si el POST es grande o hay caché intermedia.
		if ($hasThemeFields) {
			if ($key === '') {
				$fromAttach = $this->parseCatalogAttach((string) ($request['attach'] ?? ''));
				$key = $fromAttach['template_key'];
			}
			$manifestKeys = array_column($this->loadCatalogTemplateManifest(), 'key');
			if ($manifestKeys !== [] && !in_array($key, $manifestKeys, true)) {
				$key = 'classic-grid';
			}
			$show = !isset($request['show_prices']) || in_array((string) $request['show_prices'], ['1', 'true', 'on'], true);
			$tplDefaults = $this->defaultThemeColorsForTemplate($key);
			$parsed = [
				'template_key' => $key,
				'show_prices' => $show,
				'banner_bg' => $this->sanitizeHexColor($request['theme_banner_bg'] ?? null, $tplDefaults['banner_bg']),
				'banner_text' => $this->sanitizeHexColor($request['theme_banner_text'] ?? null, $tplDefaults['banner_text']),
				'footer_bg' => $this->sanitizeHexColor($request['theme_footer_bg'] ?? null, $tplDefaults['footer_bg']),
				'footer_text' => $this->sanitizeHexColor($request['theme_footer_text'] ?? null, $tplDefaults['footer_text']),
				'accent' => $this->sanitizeHexColor($request['theme_accent'] ?? null, $tplDefaults['accent']),
			];
		} elseif (!empty($request['attach']) && strpos((string) $request['attach'], '::') !== false) {
			$parsed = $this->parseCatalogAttach((string) $request['attach']);
		} else {
			$key = $key !== '' ? $key : 'classic-grid';
			$manifestKeys = array_column($this->loadCatalogTemplateManifest(), 'key');
			if ($manifestKeys !== [] && !in_array($key, $manifestKeys, true)) {
				$key = 'classic-grid';
			}
			$show = !isset($request['show_prices']) || in_array((string) $request['show_prices'], ['1', 'true', 'on'], true);
			$tplDefaults = $this->defaultThemeColorsForTemplate($key);
			$parsed = [
				'template_key' => $key,
				'show_prices' => $show,
				'banner_bg' => $tplDefaults['banner_bg'],
				'banner_text' => $tplDefaults['banner_text'],
				'footer_bg' => $tplDefaults['footer_bg'],
				'footer_text' => $tplDefaults['footer_text'],
				'accent' => $tplDefaults['accent'],
			];
		}
		$hex = static function (string $c): string {
			return strtoupper(ltrim($c, '#'));
		};
		return $parsed['template_key']
			. '::' . ($parsed['show_prices'] ? '1' : '0')
			. '::' . $hex($parsed['banner_bg'])
			. '::' . $hex($parsed['banner_text'])
			. '::' . $hex($parsed['footer_bg'])
			. '::' . $hex($parsed['footer_text'])
			. '::' . $hex($parsed['accent']);
	}

	public function update($id = null)
	{
		// Limpiar cualquier output buffer previo
		while (ob_get_level()) {
			ob_end_clean();
		}
		ob_start();
		header('Content-Type: application/json');
		$oldDisplayErrors = ini_get('display_errors');
		ini_set('display_errors', 0);

		try {
			$request = $this->input->post();
			$catalogId = (int) ($request['catalog_id'] ?? $id);

			if (!$catalogId) {
				ob_end_clean();
				echo json_encode(["success" => false, "msg" => $this->lang_catalog['id_required'] ?? 'ID de catálogo no proporcionado']);
				return;
			}

			$products = '';
			$order_categories = $this->extractSelectedCategories($request);
			if (isset($request['products'])) {
				$products = is_array($request['products']) ? implode(',', $request['products']) : $request['products'];
			}

			$catalog = $this->getObjectFromApi("catalog/{$catalogId}");
			if ($this->hasError($catalog) || !is_object($catalog) || !isset($catalog->catalog_id)) {
				ob_end_clean();
				echo json_encode(["success" => false, "msg" => $this->lang_catalog['not_found'] ?? 'Catálogo no encontrado']);
				return;
			}

			if ((int) ($catalog->default_catalog ?? 0) !== 1) {
				if (!isset($request['catalog_name']) || trim((string) $request['catalog_name']) == '') {
					ob_end_clean();
					echo json_encode(["success" => false, "msg" => $this->lang_catalog['name_required'] ?? 'El nombre del catálogo es requerido']);
					return;
				}
			} else {
				// Catálogo FullVendor: el nombre puede no venir en el POST (campos ocultos/deshabilitados).
				if (!isset($request['catalog_name']) || trim((string) $request['catalog_name']) === '') {
					$request['catalog_name'] = (string) ($catalog->catalog_name ?? 'FullVendor Catalog');
				}
			}

			$lang_error = $this->HelperLanguage->errores_catalog();
			if ((int) ($catalog->default_catalog ?? 0) === 1) {
				$newIds = array_values(array_unique(array_map('intval', array_values($order_categories))));
				$newIds = array_values(array_filter($newIds, static fn($id) => $id > 0));

				if ($newIds === []) {
					ob_end_clean();
					echo json_encode([
						'success' => false,
						'msg' => $this->lang_catalog['category_and_producto_are_required']
							?? $lang_error['catalogo_default_reorder_only']
							?? 'Debe seleccionar al menos una categoría',
					]);
					return;
				}

				// Guardar tema/plantilla primero: si el sync de categorías falla, los colores no se pierden.
				$defaultPayload = [
					'catalog_name' => (string) ($catalog->catalog_name ?? 'FullVendor Catalog'),
					'cabeza_pagina' => $request['cabeza_pagina'] ?? ($catalog->cabeza_pagina ?? null),
					'attach' => $this->buildCatalogAttachFromRequest($request),
					'status' => 1,
					'product_name' => '',
					'cat_id' => $this->buildCatIdField($newIds),
				];
				$defaultPayload = $this->applyCatalogLogoToPayload($defaultPayload, $request);
				$defaultPutResponse = $this->put("catalog/{$catalogId}", $defaultPayload);
				if ($this->hasError($defaultPutResponse)) {
					log_message('error', 'Catalog::update default - attach/theme error: ' . json_encode($defaultPutResponse));
					ob_end_clean();
					$apiMsg = $this->resolveApiErrorMessage($defaultPutResponse);
					echo json_encode(["success" => false, "msg" => $apiMsg !== '' ? $apiMsg : ($this->lang_catalog['update_error'] ?? 'Error al actualizar el catálogo')]);
					return;
				}

				$syncResponse = $this->put("catalog/{$catalogId}/categories", ['categories' => $newIds]);
				if ($this->hasError($syncResponse)) {
					log_message('error', 'Catalog::update default - syncCategories error: ' . json_encode($syncResponse));
					ob_end_clean();
					$apiMsg = $this->resolveApiErrorMessage($syncResponse);
					echo json_encode(["success" => false, "msg" => $apiMsg !== '' ? $apiMsg : ($this->lang_catalog['update_error'] ?? 'Error al actualizar el catálogo')]);
					return;
				}

				$successMessage = $this->lang_catalog['updated_success']
					?? $this->lang_catalog['edit_success']
					?? 'Catálogo actualizado';
				$this->flashMessage(json_encode([$successMessage, 'success', '#A5DC86'], JSON_UNESCAPED_UNICODE));

				ob_end_clean();
				echo json_encode([
					"success" => true,
					"msg" => $successMessage,
					"urlredirect" => company_url('catalog'),
				]);
				return;
			}

			if (empty($order_categories) && $products == '') {
				ob_end_clean();
				echo json_encode(["success" => false, "msg" => $this->lang_catalog['category_and_producto_are_required'] ?? 'Debe seleccionar al menos una categoría o un producto']);
				return;
			}

			$updatePayload = [
				'catalog_name' => $request['catalog_name'],
				'cat_id' => $this->buildCatIdField($order_categories),
				'product_name' => $products,
				'status' => $request['status'] ?? 1,
				'cabeza_pagina' => $request['cabeza_pagina'] ?? null,
				'attach' => $this->buildCatalogAttachFromRequest($request),
			];
			$updatePayload = $this->applyCatalogLogoToPayload($updatePayload, $request);
			$updateResponse = $this->put("catalog/{$catalogId}", $updatePayload);
			if ($this->hasError($updateResponse)) {
				ob_end_clean();
				log_message('error', 'Catalog::update - API error: ' . json_encode($updateResponse));
				$apiMsg = $this->resolveApiErrorMessage($updateResponse);
				echo json_encode(["success" => false, "msg" => $apiMsg !== '' ? $apiMsg : ($this->lang_catalog['update_error'] ?? 'Error al actualizar el catálogo')]);
				return;
			}

			// Sincronizar categorías (con orden) vía API.
			$syncResponse = $this->put("catalog/{$catalogId}/categories", ['categories' => array_values($order_categories)]);
			if ($this->hasError($syncResponse)) {
				log_message('error', 'Catalog::update - syncCategories error: ' . json_encode($syncResponse));
				ob_end_clean();
				$apiMsg = $this->resolveApiErrorMessage($syncResponse);
				echo json_encode(["success" => false, "msg" => $apiMsg !== '' ? $apiMsg : ($this->lang_catalog['update_error'] ?? 'Error al actualizar el catálogo')]);
				return;
			}

			ob_end_clean();
			$successMessage = $this->lang_catalog['updated_success'] ?? 'Catálogo actualizado';
			$this->flashMessage(json_encode([$successMessage, 'success', '#A5DC86'], JSON_UNESCAPED_UNICODE));
			echo json_encode(["success" => true, "msg" => $successMessage, "urlredirect" => company_url('catalog')]);
			return;
		} catch (\Throwable $e) {
			ob_end_clean();
			log_message('error', 'Catalog::update - Error: ' . $e->getMessage());
			ini_set('display_errors', $oldDisplayErrors);
			echo json_encode(["success" => false, "msg" => $this->lang_catalog['update_error'] ?? 'Error al actualizar el catálogo']);
			return;
		} finally {
			ini_set('display_errors', $oldDisplayErrors);
		}
	}

	/**
	 * Guarda de inmediato el orden de categorías del modal Apply (sin esperar a Save).
	 */
	public function studioSaveCategories()
	{
		while (ob_get_level()) {
			ob_end_clean();
		}
		header('Content-Type: application/json; charset=utf-8');

		try {
			$catalogId = (int) ($this->input->post('catalog_id') ?: 0);
			if ($catalogId <= 0) {
				echo json_encode([
					'success' => false,
					'msg' => $this->lang_catalog['id_required'] ?? '',
				], JSON_UNESCAPED_UNICODE);
				return;
			}

			$catalog = $this->getObjectFromApi("catalog/{$catalogId}");
			if ($this->hasError($catalog) || !is_object($catalog) || !isset($catalog->catalog_id)) {
				echo json_encode([
					'success' => false,
					'msg' => $this->lang_catalog['not_found'] ?? '',
				], JSON_UNESCAPED_UNICODE);
				return;
			}
			if ((int) ($catalog->company_id ?? 0) !== (int) $this->company) {
				echo json_encode([
					'success' => false,
					'msg' => $this->lang_catalog['not_found'] ?? '',
				], JSON_UNESCAPED_UNICODE);
				return;
			}

			$ids = $this->input->post('categories');
			if (!is_array($ids)) {
				$ids = $this->input->post('categories[]');
			}
			if (!is_array($ids)) {
				$ids = [];
			}
			$ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn ($id) => $id > 0)));
			if ($ids === []) {
				echo json_encode([
					'success' => false,
					'msg' => $this->lang_catalog['category_and_producto_are_required']
						?? $this->lang_catalog['studio_categories_none']
						?? '',
				], JSON_UNESCAPED_UNICODE);
				return;
			}

			$syncResponse = $this->put("catalog/{$catalogId}/categories", ['categories' => $ids]);
			if ($this->hasError($syncResponse)) {
				log_message('error', 'Catalog::studioSaveCategories error: ' . json_encode($syncResponse));
				$apiMsg = $this->resolveApiErrorMessage($syncResponse);
				echo json_encode([
					'success' => false,
					'msg' => $apiMsg !== '' ? $apiMsg : ($this->lang_catalog['update_error'] ?? ''),
				], JSON_UNESCAPED_UNICODE);
				return;
			}

			echo json_encode([
				'success' => true,
				'msg' => $this->lang_catalog['studio_categories_order_saved']
					?? $this->lang_catalog['updated_success']
					?? '',
				'categories' => $ids,
			], JSON_UNESCAPED_UNICODE);
		} catch (\Throwable $e) {
			log_message('error', 'Catalog::studioSaveCategories - ' . $e->getMessage());
			echo json_encode([
				'success' => false,
				'msg' => $this->lang_catalog['update_error'] ?? '',
			], JSON_UNESCAPED_UNICODE);
		}
	}

	public function create()
	{
		// Limpiar cualquier output buffer previo
		while (ob_get_level()) {
			ob_end_clean();
		}
		ob_start();
		
		// Configurar headers para JSON
		header('Content-Type: application/json');
		
		try {
			$request = $this->input->post();
			$order_categories = $this->extractSelectedCategories($request);
			$products = '';
			if (isset($request['products'])) {
				$products = is_array($request['products']) ? implode(',', $request['products']) : $request['products'];
			}

			if (!isset($request['catalog_name']) || trim((string) $request['catalog_name']) == '') {
				ob_end_clean();
				echo json_encode(["success" => false, "msg" => $this->lang_catalog['name_required'] ?? 'El nombre del catálogo es requerido']);
				return;
			}

			if (empty($order_categories) && $products == '') {
				ob_end_clean();
				echo json_encode(["success" => false, "msg" => $this->lang_catalog['category_and_producto_are_required'] ?? 'Se requiere al menos una categoría o un producto']);
				return;
			}

			// Validar nombre duplicado consultando los catálogos de la empresa vía API.
			$catalogName = trim((string) $request['catalog_name']);
			$existing = $this->apiList($this->getObjectFromApi("company/{$this->company}/catalogs", ['all' => 1]));
			foreach ($existing as $cat) {
				$existingName = is_object($cat) ? ($cat->catalog_name ?? '') : ($cat['catalog_name'] ?? '');
				if (strcasecmp(trim((string) $existingName), $catalogName) === 0) {
					ob_end_clean();
					echo json_encode(["success" => false, "msg" => $this->lang_catalog['catalog_exists'] ?? 'Ya existe un catálogo con este nombre']);
					return;
				}
			}

			$createPayload = [
				'company_id' => $this->company,
				'catalog_name' => $catalogName,
				'cat_id' => $this->buildCatIdField($order_categories),
				'product_name' => $products,
				'status' => isset($request['status']) ? $request['status'] : 1,
				'cabeza_pagina' => isset($request['cabeza_pagina']) ? $request['cabeza_pagina'] : '',
				'attach' => $this->buildCatalogAttachFromRequest($request),
				'created' => date("Y-m-d H:i:s"),
			];
			$createPayload = $this->applyCatalogLogoToPayload($createPayload, $request);
			$createResponse = $this->post('catalog', $createPayload);
			if ($this->hasError($createResponse)) {
				ob_end_clean();
				log_message('error', 'Catalog::create - API error: ' . json_encode($createResponse));
				$apiMsg = $this->resolveApiErrorMessage($createResponse);
				echo json_encode(["success" => false, "msg" => $apiMsg !== '' ? $apiMsg : ($this->lang_catalog['create_error'] ?? 'Error al crear el catálogo')]);
				return;
			}

			$newCatalog = $this->extractApiResource($createResponse);
			$catalogInsertId = $this->extractResourceId($newCatalog, 'catalog_id');

			if ($catalogInsertId <= 0) {
				ob_end_clean();
				log_message('error', 'Catalog::create - catalog_id missing in API response: ' . json_encode($createResponse));
				echo json_encode(["success" => false, "msg" => $this->lang_catalog['create_error'] ?? 'Error al crear el catálogo']);
				return;
			}

			if (count($order_categories) > 0) {
				$syncResponse = $this->put("catalog/{$catalogInsertId}/categories", ['categories' => array_values($order_categories)]);
				if ($this->hasError($syncResponse)) {
					log_message('error', 'Catalog::create - syncCategories error: ' . json_encode($syncResponse));
					ob_end_clean();
					$apiMsg = $this->resolveApiErrorMessage($syncResponse);
					echo json_encode(["success" => false, "msg" => $apiMsg !== '' ? $apiMsg : ($this->lang_catalog['create_error'] ?? 'Error al crear el catálogo')]);
					return;
				}
			}

			$successMessage = $this->lang_catalog['added_success'] ?? 'Catálogo agregado exitosamente';
			$this->flashMessage('["' . $successMessage . '", "success", "#A5DC86"]');

			ob_end_clean();
			echo json_encode(["success" => true, "msg" => $successMessage, "urlredirect" => company_url('catalog')]);
			return;
		} catch (\Throwable $e) {
			ob_end_clean();
			log_message('error', 'Catalog::create - Error: ' . $e->getMessage());
			echo json_encode(["success" => false, "msg" => $this->lang_catalog['create_error'] ?? 'Error al crear el catálogo']);
			return;
		}
	}

	public function getEditData($catalogId)
	{
		while (ob_get_level()) {
			ob_end_clean();
		}
		header('Content-Type: application/json; charset=utf-8');

		try {
			if (!$catalogId) {
				echo json_encode(["success" => false, "msg" => $this->lang_catalog['id_required'] ?? 'ID de catálogo no proporcionado']);
				return;
			}

			$catalog = $this->getObjectFromApi("catalog/{$catalogId}");
			if ($this->hasError($catalog) || !is_object($catalog) || !isset($catalog->catalog_id)) {
				echo json_encode(["success" => false, "msg" => $this->lang_catalog['not_found'] ?? 'Catálogo no encontrado']);
				return;
			}

			// Productos seleccionados (desde product_name)
			$selectedProducts = [];
			if (!empty($catalog->product_name)) {
				$selectedProducts = array_values(array_filter(array_map('trim', explode(',', (string) $catalog->product_name))));
			}

			// Categorías seleccionadas (con orden) vía API
			$category_selected_order = $this->fetchCatalogCategories((int) $catalogId);
			$selectedCategories = [];
			foreach ($category_selected_order as $cat) {
				$selectedCategories[] = $cat->category_id;
			}

			// Payload ligero: evitar serializar relaciones/objetos pesados del catálogo.
			echo json_encode([
				"success" => true,
				"catalog" => [
					"catalog_id" => (int) ($catalog->catalog_id ?? 0),
					"catalog_name" => (string) ($catalog->catalog_name ?? ''),
					"status" => (int) ($catalog->status ?? 1),
					"cabeza_pagina" => (string) ($catalog->cabeza_pagina ?? ''),
					"default_catalog" => (int) ($catalog->default_catalog ?? 0),
					"product_name" => (string) ($catalog->product_name ?? ''),
				],
				"category_selected_order" => $category_selected_order,
				"selectedProducts" => $selectedProducts,
				"selectedCategories" => $selectedCategories,
			], JSON_UNESCAPED_UNICODE);
		} catch (\Throwable $e) {
			log_message('error', 'Catalog::getEditData - Error: ' . $e->getMessage());
			echo json_encode([
				"success" => false,
				"msg" => $this->lang_catalog['not_found'] ?? 'Error al cargar los datos del catálogo',
			]);
		}
	}

	public function delete($id)
	{
		while (ob_get_level()) {
			ob_end_clean();
		}
		ob_start();
		header('Content-Type: application/json');

		try {
			$lang_error = $this->HelperLanguage->errores_catalog();

			$catalog = $this->getObjectFromApi("catalog/{$id}");
			if ($this->hasError($catalog) || !is_object($catalog) || !isset($catalog->catalog_id) || (int) ($catalog->company_id ?? 0) !== (int) $this->company) {
				ob_end_clean();
				echo json_encode(["success" => false, "msg" => $this->lang_catalog['not_found'] ?? 'Catálogo no encontrado']);
				return;
			}

			// Bloquear eliminación del catálogo FullVendor (por defecto)
			if ((int) ($catalog->default_catalog ?? 0) === 1) {
				ob_end_clean();
				echo json_encode(["success" => false, "msg" => $lang_error['catalogo_default'] ?? 'El catálogo por defecto (FullVendor Catalog) no se puede eliminar']);
				return;
			}

			$deleteResponse = $this->apiDelete("catalog/{$id}");
			if ($this->hasError($deleteResponse)) {
				ob_end_clean();
				echo json_encode(["success" => false, "msg" => $this->lang_catalog['delete_error'] ?? 'Error al eliminar el catálogo']);
				return;
			}

			$successMessage = $this->lang_catalog['deleted_success'] ?? 'Catálogo eliminado exitosamente';
			$this->flashMessage('["' . addslashes($successMessage) . '", "success", "#A5DC86"]');

			ob_end_clean();
			echo json_encode([
				"success" => true,
				"message" => $successMessage,
				"msg" => $successMessage,
			]);
		} catch (\Throwable $e) {
			ob_end_clean();
			log_message('error', 'Catalog::delete - Error: ' . $e->getMessage());
			echo json_encode(["success" => false, "msg" => $this->lang_catalog['delete_error'] ?? 'Error al eliminar el catálogo']);
		}
	}


	public function send_by($onecatalog_id = null){

		header('Content-Type: application/json; charset=utf-8');
		$request = $this->input->post();
		if (!is_array($request)) {
			$request = [];
		}
		$langOptionBasic = $this->getModuleLang('cmp-option_basic');
		if (empty($langOptionBasic)) {
			$langOptionBasic = $this->HelperLanguage->option_basic();
		}
		$langOptionBasic = is_array($langOptionBasic) ? $langOptionBasic : [];
		$langCustomer = $this->HelperLanguage->lang_customer();
		$msgSelectCustomer = $langOptionBasic['msg_select_at_least_one_customer'] ?? $langCustomer['select_customer'] ?? 'Please select at least one customer.';
		$msgSelectCatalog = $langOptionBasic['msg_select_at_least_one_catalog'] ?? 'Please select at least one catalog.';
		$msgSuccess = $langCustomer['mail_sucess'] ?? $langOptionBasic['mail_success'] ?? 'Mail sent successfully!';

		$compId = $this->getCompany();
		if (is_array($compId)) {
			$compId = isset($compId['company_id']) ? $compId['company_id'] : (isset($compId[0]) ? $compId[0] : $this->company);
		}
		$compId = intval($compId) ?: $this->company;
		$company = $this->getObjectFromApi("company/{$compId}", ['relations' => ['formatDate']]);
		if (!is_object($company) || !isset($company->company_id)) {
			log_message('error', 'Catalog::send_by - Invalid company response for compId ' . $compId);
			echo json_encode(["success" => false, "msg" => $msgSelectCustomer]);
			return;
		}

		if (!isset($request['customerIds']) || (is_array($request['customerIds']) && empty($request['customerIds']))) {
			echo json_encode(["success" => false, "msg" => $msgSelectCustomer]);
			return;
		}

		$catalogIdsRaw = isset($request['catalogIds']) ? $request['catalogIds'] : (isset($request['catlogIds']) ? $request['catlogIds'] : null);
		if (!$onecatalog_id && (empty($catalogIdsRaw) || (is_array($catalogIdsRaw) && empty($catalogIdsRaw)))) {
			echo json_encode(["success" => false, "msg" => $msgSelectCatalog]);
			return;
		}

		$customerId = is_string($request['customerIds']) ? explode(',', $request['customerIds']) : $request['customerIds'];
		$customerId = array_filter(array_map('intval', (array)$customerId));
		if (empty($customerId)) {
			echo json_encode(["success" => false, "msg" => $msgSelectCustomer]);
			return;
		}

		$catalogIdsStr = is_scalar($catalogIdsRaw) ? (string) $catalogIdsRaw : (is_array($catalogIdsRaw) ? implode(',', array_map(function ($v) { return is_scalar($v) ? $v : ''; }, $catalogIdsRaw)) : '');
		$catalogIds = $onecatalog_id ? (array) $onecatalog_id : (is_array($catalogIdsRaw) ? $catalogIdsRaw : explode(',', $catalogIdsStr));
		$catalogIds = array_filter(array_map('intval', $catalogIds));
		if (empty($catalogIds)) {
			echo json_encode(["success" => false, "msg" => $msgSelectCatalog]);
			return;
		}

		try {
			foreach ($customerId as $customer_id) {
				// Cargar cliente con relación de seller asignado al catálogo (cada customer tiene su seller)
				$customer = $this->getObjectFromApi("customer/{$customer_id}", ['relations' => ['sellerAssignCatalog', 'sellers']]);
				if (!is_object($customer) || !isset($customer->customer_id)) {
					log_message('error', 'Catalog::send_by - Invalid customer response for id ' . $customer_id);
					echo json_encode(["success" => false, "msg" => $msgSelectCustomer]);
					return;
				}
				$seller = $this->resolveSellerForCustomer($customer);
				foreach ($catalogIds as $catalog_id) {
					$email_text = isset($request['email_text']) && $request['email_text'] !== '' && $request['email_text'] !== null ? $request['email_text'] : null;
					$expDays = $request['expiration_days'] ?? 15;
					$expiration_days_int = is_scalar($expDays) ? (int) $expDays : 15;
					$catalog_text_param = isset($request['catalog_text']) && is_scalar($request['catalog_text']) ? (string) $request['catalog_text'] : '';
					$this->sendCatalogToCustomer($company, $customer, $seller, $catalog_id, $expiration_days_int, $catalog_text_param, $email_text);
				}
			}
		} catch (\GuzzleHttp\Exception\BadResponseException $e) {
			$body = $e->getResponse() ? (string) $e->getResponse()->getBody() : '';
			$errMsg = $msgSelectCustomer;
			try {
				$data = json_decode($body, true);
				if (!empty($data['message'])) {
					$errMsg = is_array($data['message']) ? ($data['message']['message'] ?? $errMsg) : $data['message'];
				}
			} catch (\Throwable $t) {
				// keep default
			}
			log_message('error', 'Catalog::send_by API error: ' . $e->getMessage());
			echo json_encode(["success" => false, "msg" => $errMsg]);
			return;
		} catch (\Throwable $e) {
			log_message('error', 'Catalog::send_by: ' . $e->getMessage());
			log_message('error', 'Catalog::send_by trace: ' . $e->getTraceAsString());
			echo json_encode(["success" => false, "msg" => $e->getMessage()]);
			return;
		}

		// No usar set_flash aquí: el envío es AJAX y el modal ya muestra el éxito.
		// Un flash residual aparecería al abrir el enlace del catálogo en la misma sesión.
		echo json_encode(["success" => true, "load" => true, "message" => $msgSuccess]);
	}

	/**
	 * Envía por correo un catálogo a un cliente usando exclusivamente la API.
	 * Reemplaza CatalogoModel::sendMailCatalog (sin consultas directas a BD).
	 *
	 * @param object $company
	 * @param object $customer
	 * @param object|null $seller
	 */
	private function sendCatalogToCustomer($company, $customer, $seller, $catalog_id, int $expiration_days = 15, string $catalog_text = '', $email_text = null): void
	{
		$formatDate = $company->formatDate ?? null;
		if (!$formatDate) {
			$formatDate = $this->getObjectFromApi('formatdate/default');
		}

		$customerId = (int) (is_scalar($customer->customer_id ?? null) ? $customer->customer_id : 0);

		$invitationPayload = [
			'catalog_id' => (int) $catalog_id,
			'customer_id' => $customerId,
			'catalog_text' => $catalog_text,
		];
		if (is_object($seller) && isset($seller->id) && is_scalar($seller->id)) {
			$invitationPayload['seller_id'] = (int) $seller->id;
		}

		$tokenInvitation = $this->post('order/invitation', $invitationPayload, false);
		if ($this->hasError($tokenInvitation)) {
			throw new \RuntimeException(is_object($tokenInvitation) ? ($tokenInvitation->message ?? 'Error creating invitation token') : 'Error creating invitation token');
		}
		$tokenInvitation = is_array($tokenInvitation) ? (object) $tokenInvitation : $tokenInvitation;
		if (isset($tokenInvitation->data)) {
			$tokenInvitation = is_object($tokenInvitation->data) ? $tokenInvitation->data : (object) $tokenInvitation->data;
		}
		$tokenString = (isset($tokenInvitation->token) && is_scalar($tokenInvitation->token)) ? (string) $tokenInvitation->token : '';
		if ($tokenString === '') {
			throw new \RuntimeException('Invalid response from invitation API');
		}

		$expirationDateRaw = $tokenInvitation->expirationDate ?? null;
		$expirationDateString = is_scalar($expirationDateRaw) ? (string) $expirationDateRaw : null;
		$catalogsend_time = isset($company->catalogsend_time) && is_scalar($company->catalogsend_time) ? (int) $company->catalogsend_time : 15;
		$fechaExpiredForView = ($expirationDateString !== null && $expirationDateString !== '')
			? $expirationDateString
			: date('Y-m-d H:i:s', strtotime('+' . ($expiration_days ?: 15) . ' days'));

		$lang = $this->HelperLanguage->catalog();
		if (is_object($lang)) {
			$lang = method_exists($lang, 'getArrayCopy') ? $lang->getArrayCopy() : (array) $lang;
		}

		$data_tosend = [
			'token' => $tokenString,
			'company' => $company,
			'customer_info' => $customer,
			'seller' => $seller,
			'lang' => is_array($lang) ? $lang : [],
			'expiration_days' => $catalogsend_time,
			'catalog_text' => $catalog_text,
			'email_text' => is_scalar($email_text) ? (string) $email_text : '',
			'catalogo_id' => (int) $catalog_id,
			'fecha_expired' => $fechaExpiredForView,
			'format_date' => $formatDate,
		];

		$htmlContent = $this->load->view('company/catalogo/template_email', $data_tosend, true);

		$toEmail = isset($customer->email) && is_scalar($customer->email) ? trim((string) $customer->email) : '';
		if ($toEmail === '') {
			throw new \RuntimeException('Customer has no email address');
		}

		$langClient = $this->HelperLanguage->lang_customer();
		$subjectTpl = '%s Product Catalog';
		if (is_array($langClient) && !empty($langClient['subjet_email_send_catalog'])) {
			$subjectTpl = (string) $langClient['subjet_email_send_catalog'];
		}
		$companyName = isset($company->company_name) && is_scalar($company->company_name)
			? (string) $company->company_name
			: (isset($company->name) && is_scalar($company->name) ? (string) $company->name : 'Catalog');
		$subject = str_replace('%s', $companyName, $subjectTpl);

		$companyId = isset($company->company_id) ? (int) $company->company_id : 0;
		$this->load->helper('send_template');
		$catalogLink = rtrim((string) base_url(), '/') . '/catalogo/producto?token=' . rawurlencode($tokenString);
		$customerName = isset($customer->name) && is_scalar($customer->name) ? trim((string) $customer->name) : '';
		if ($customerName === '' && isset($customer->business_name) && is_scalar($customer->business_name)) {
			$customerName = trim((string) $customer->business_name);
		}
		$catalogName = trim($catalog_text);
		if ($catalogName === '') {
			$catalogObj = $this->getObjectFromApi('catalog/' . (int) $catalog_id);
			if (!$this->hasError($catalogObj) && is_object($catalogObj) && isset($catalogObj->catalog_name) && is_scalar($catalogObj->catalog_name)) {
				$catalogName = (string) $catalogObj->catalog_name;
			}
		}
		$renderedTpl = fv_send_template_render($companyId, 'catalog', 'email', [
			'{{customer_name}}' => $customerName,
			'{{company_name}}' => $companyName,
			'{{catalog_name}}' => $catalogName,
			'{{catalog_link}}' => $catalogLink,
			'{{order_number}}' => '',
			'{{order_total}}' => '',
			'{{order_status}}' => '',
			'{{message}}' => is_scalar($email_text) ? (string) $email_text : '',
		]);
		if (is_array($renderedTpl) && trim((string) ($renderedTpl['body'] ?? '')) !== '') {
			$htmlContent = (string) $renderedTpl['body'];
			if (trim((string) ($renderedTpl['subject'] ?? '')) !== '') {
				$subject = (string) $renderedTpl['subject'];
			}
		}

		$payload = [
			'html_body' => $htmlContent,
			'token' => $tokenString,
			'customer_id' => $customerId,
			'catalog_id' => (int) $catalog_id,
			'skip_mailer_send' => true,
		];

		require_once APPPATH . 'helpers/telecom_messaging_helper.php';
		if (!TelecomMessagingHelper::sendEmail([$toEmail], $subject, $htmlContent, true, $companyId)) {
			throw new \RuntimeException('Could not send catalog email via Telecom API (check TELECOM_API_BASE_URL / TELECOM_API_KEY)');
		}

		$sendResponse = $this->post('catalog/send', $payload);
		if ($this->hasError($sendResponse)) {
			$err = is_object($sendResponse) ? ($sendResponse->message ?? 'Error sending catalog') : ($sendResponse['message'] ?? 'Error sending catalog');
			throw new \RuntimeException(is_string($err) ? $err : 'Error sending catalog');
		}
	}

	/**
	 * Resuelve el seller (vendedor) asignado a un cliente para el envío de catálogo.
	 * Orden: seller_assign_catalog -> vendor por id -> customer/{id}/seller (primer seller o default company).
	 *
	 * @param object $customer Cliente desde la API (con o sin relaciones)
	 * @return object|null Vendor/seller o null
	 */
	protected function resolveSellerForCustomer($customer)
	{
		$sellerIdRaw = $customer->seller_assign_catalog ?? $customer->assign_catalog ?? null;
		// seller_assign_catalog puede ser id (escalar) o objeto vendor desde la relación API
		if (is_object($sellerIdRaw) && isset($sellerIdRaw->id)) {
			return $sellerIdRaw;
		}
		if (is_scalar($sellerIdRaw) && $sellerIdRaw !== '' && $sellerIdRaw !== null) {
			$seller = $this->getObjectFromApi('vendor/' . (string) $sellerIdRaw);
			if (is_object($seller) && isset($seller->id)) {
				return $seller;
			}
		}
		// Relación cargada sellerAssignCatalog (objeto vendor)
		if (!empty($customer->seller_assign_catalog) && is_object($customer->sellerAssignCatalog ?? null) && isset($customer->sellerAssignCatalog->id)) {
			return $customer->sellerAssignCatalog;
		}
		// Fallback: endpoint que devuelve el seller del cliente (primer seller o default de la company)
		$custId = isset($customer->customer_id) && is_scalar($customer->customer_id) ? (int) $customer->customer_id : 0;
		if ($custId <= 0) {
			return null;
		}
		$seller = $this->getObjectFromApi("customer/{$custId}/seller");
		if (is_object($seller) && isset($seller->id)) {
			return $seller;
		}
		return null;
	}

	public function enable($id){
		$lang = $this->HelperLanguage->status_active();
		$this->put("catalog/{$id}", ['status' => 1]);

		// Si es una petición AJAX para el switch, devolver HTML
		if ($this->input->is_ajax_request() || $this->input->get('format') === 'html') {
			$listItem = (object) ['catalog_id' => $id, 'status' => 1];
			$this->load->view('components/crud/enable-disable_button', [
				'listItem' => $listItem,
				'itemStatusField' => 'status',
				'itemIdField' => 'catalog_id',
				'controllerUrl' => $this->paginationUrl(),
				'pageTexts' => []
			]);
			return;
		}

		echo json_encode(["success"=>true,"url"=> company_url('catalog/disable/'.$id),
													 "class_toremove" =>"btn-success",
													 "type_btn" => "btn-warning",
													 "data-loading" =>$lang["loader-disabling"],
													 "text_btn"=>$lang["option_desactive"],
													 "disable" => false,
													 "change_inmodal"=>true
													]);
	}

	public function disable($id){
		$lang_error = $this->HelperLanguage->errores_catalog();

		// Verificar si es catálogo por defecto (vía API)
		$catalog = $this->getObjectFromApi("catalog/{$id}");
		if (is_object($catalog) && (int) ($catalog->default_catalog ?? 0) === 1) {
			if ($this->input->is_ajax_request() || $this->input->get('format') === 'html') {
				http_response_code(400);
				echo '<div class="alert alert-danger">' . ($lang_error['cannot_desactive'] ?? '') . '</div>';
				return;
			}
			echo json_encode(["success"=>false, "msg"=>$lang_error['cannot_desactive'] ?? '']);
			return;
		}

		$lang = $this->HelperLanguage->status_active();
		$this->put("catalog/{$id}", ['status' => 0]);

		// Si es una petición AJAX para el switch, devolver HTML
		if ($this->input->is_ajax_request() || $this->input->get('format') === 'html') {
			$listItem = (object) ['catalog_id' => $id, 'status' => 0];
			$this->load->view('components/crud/enable-disable_button', [
				'listItem' => $listItem,
				'itemStatusField' => 'status',
				'itemIdField' => 'catalog_id',
				'controllerUrl' => $this->paginationUrl(),
				'pageTexts' => []
			]);
			return;
		}

		echo json_encode(["success"=>true,"url"=>company_url('catalog/enable/'.$id),
						  "type_btn"=>"btn-success",
						  "class_toremove" =>"btn-warning",
						  "data-loading"=>$lang["loader-enabling"],
						  "text_btn"=>$lang["option_active"],
						  "disable" => true,
						  "change_inmodal"=>true
						]);
	}

	public function filterbygroup($id=null){
		// Limpiar output buffer
		while (ob_get_level()) {
			ob_end_clean();
		}
		ob_start();
		
		// Configurar headers para JSON
		header('Content-Type: application/json');
		
		try {
			if (!$id || $id == 'todo') {
				$customers = $this->apiList($this->getObjectFromApi("company/{$this->company}/customers", ['enabled' => 1]));
			} else {
				$customers = $this->apiList($this->getObjectFromApi("customergroup/{$id}/customers", ['enabled' => 1]));
			}

			$list = [];
			foreach ($customers as $customer) {
				$list[] = [
					'id' => is_object($customer) ? ($customer->customer_id ?? 0) : ($customer['customer_id'] ?? 0),
					'name' => is_object($customer) ? ($customer->name ?? '') : ($customer['name'] ?? ''),
					'business_name' => is_object($customer) ? ($customer->business_name ?? '') : ($customer['business_name'] ?? ''),
				];
			}
			ob_end_clean();
			echo json_encode(["success" => true, "customers" => $list, "groups" => $list]);
		} catch (\Throwable $e) {
			ob_end_clean();
			log_message('error', 'Catalog::filterbygroup - Error: ' . $e->getMessage());
			echo json_encode(["success" => false, "msg" => $this->lang_catalog['filter_error'] ?? 'Error al filtrar por grupo']);
		}
	}

	public function performance($id){
		// El modal de rendimiento se abre desde el listado; no hay página analytics aún.
		redirect(company_url('catalog'));
	}

	public function getFormats(){
		ob_start();
		
		try {
			$companyId = $this->session->userdata('compId') ?? $this->getCompanyId();
			
			if (!$companyId) {
				ob_end_clean();
				header('Content-Type: application/json');
				echo json_encode([
					"success" => false,
					"msg" => "Company ID no encontrado"
				]);
				return;
			}
			
			// Formatos de la empresa vía API.
			$formats = $this->apiList($this->getObjectFromApi("company/{$companyId}/formats"));
			$formatsList = [];
			foreach ($formats as $format) {
				$formatsList[] = [
					'id' => is_object($format) ? ($format->format_id ?? 0) : ($format['format_id'] ?? 0),
					'name' => is_object($format) ? ($format->format_name ?? '') : ($format['format_name'] ?? ''),
					'description' => is_object($format) ? ($format->format_description ?? '') : ($format['format_description'] ?? ''),
					'template' => is_object($format) ? ($format->template ?? '') : ($format['template'] ?? ''),
					'order' => is_object($format) ? ($format->order ?? 0) : ($format['order'] ?? 0),
					'is_default' => is_object($format) ? ($format->is_default ?? 0) : ($format['is_default'] ?? 0),
				];
			}

			ob_end_clean();
			header('Content-Type: application/json');
			echo json_encode([
				"success" => true,
				"formats" => $formatsList
			]);

		} catch (\Throwable $e) {
			ob_end_clean();
			log_message('error', 'Catalog::getFormats - Error: ' . $e->getMessage());
			header('Content-Type: application/json');
			echo json_encode([
				"success" => false,
				"msg" => $this->lang_catalog['formats_error'] ?? 'Error al obtener formatos'
			]);
		}
	}
	
	public function saveFormat(){
		// Limpiar cualquier output buffer previo
		while (ob_get_level()) {
			ob_end_clean();
		}
		ob_start();
		
		// Configurar headers para JSON
		header('Content-Type: application/json');
		
		try {
			$companyId = (int) ($this->session->userdata('compId') ?? $this->getCompanyId());
			$request = $this->input->post();

			if (!$companyId) {
				ob_end_clean();
				echo json_encode(["success" => false, "msg" => $this->lang_catalog['company_not_found'] ?? 'Company ID no encontrado']);
				return;
			}

			$formatId = null;
			if (isset($request['format_id']) && $request['format_id'] !== '' && $request['format_id'] !== null) {
				$formatId = intval($request['format_id']);
				if ($formatId <= 0) {
					$formatId = null;
				}
			}
			$formatName = trim($request['format_name'] ?? '');
			$formatDescription = trim($request['format_description'] ?? '');
			$template = $request['template'] ?? '';
			$order = isset($request['order']) ? intval($request['order']) : 0;
			$isDefault = isset($request['is_default']) ? intval($request['is_default']) : 0;

			if (strlen($template) > 10000) {
				ob_end_clean();
				echo json_encode(["success" => false, "msg" => $this->lang_catalog['format_too_long'] ?? 'El contenido del formato es demasiado largo (máximo 10,000 caracteres).']);
				return;
			}

			if (empty($formatName)) {
				ob_end_clean();
				echo json_encode(["success" => false, "msg" => $this->lang_catalog['format_name_required'] ?? 'El nombre del formato es requerido']);
				return;
			}

			$payload = [
				'format_name' => $formatName,
				'format_description' => $formatDescription,
				'template' => $template,
				'order' => $order,
				'is_default' => $isDefault,
				'company_id' => $companyId,
			];

			if ($formatId && $formatId > 0) {
				$response = $this->put("format/{$formatId}", $payload);
			} else {
				$response = $this->post('format', $payload);
			}

			if ($this->hasError($response)) {
				ob_end_clean();
				echo json_encode(["success" => false, "msg" => $this->lang_catalog['format_save_error'] ?? 'Error al guardar el formato']);
				return;
			}

			$saved = is_object($response) && isset($response->data) ? $response->data : $response;
			$savedId = $formatId;
			if (is_object($saved) && isset($saved->format_id)) {
				$savedId = (int) $saved->format_id;
			} elseif (is_array($saved) && isset($saved['format_id'])) {
				$savedId = (int) $saved['format_id'];
			}

			ob_end_clean();
			header('Content-Type: application/json');
			echo json_encode([
				"success" => true,
				"msg" => $this->lang_catalog['format_saved'] ?? 'Formato guardado correctamente',
				"format_id" => $savedId
			]);
			
		} catch (\Throwable $e) {
			ob_end_clean();
			log_message('error', 'Catalog::saveFormat - Error: ' . $e->getMessage());
			header('Content-Type: application/json');
			echo json_encode([
				"success" => false,
				"msg" => $this->lang_catalog['format_save_error'] ?? 'Error al guardar el formato'
			]);
		}
	}

	public function deleteFormat($formatId){
		ob_start();

		try {
			$companyId = (int) ($this->session->userdata('compId') ?? $this->getCompanyId());

			if (!$companyId) {
				ob_end_clean();
				header('Content-Type: application/json');
				echo json_encode(["success" => false, "msg" => $this->lang_catalog['company_not_found'] ?? 'Company ID no encontrado']);
				return;
			}

			$response = $this->apiDelete("format/{$formatId}");
			if ($this->hasError($response)) {
				ob_end_clean();
				header('Content-Type: application/json');
				echo json_encode(["success" => false, "msg" => $this->lang_catalog['format_delete_error'] ?? 'Error al eliminar el formato']);
				return;
			}

			ob_end_clean();
			header('Content-Type: application/json');
			echo json_encode(["success" => true, "msg" => $this->lang_catalog['format_deleted'] ?? 'Formato eliminado correctamente']);
		} catch (\Throwable $e) {
			ob_end_clean();
			log_message('error', 'Catalog::deleteFormat - Error: ' . $e->getMessage());
			header('Content-Type: application/json');
			echo json_encode(["success" => false, "msg" => $this->lang_catalog['format_delete_error'] ?? 'Error al eliminar el formato']);
		}
	}

	public function download($id){
		ob_start();
		
		try {
			// Limpiar cualquier salida previa
			ob_clean();

			$request = $this->input->post();
			if (!is_array($request)) {
				$request = [];
			}

			// Validar que el catálogo existe (vía API)
			$catalogo = $this->getObjectFromApi("catalog/{$id}");
			if ($this->hasError($catalogo) || !is_object($catalogo) || !isset($catalogo->catalog_id)) {
				ob_end_clean();
				header('Content-Type: application/json');
				echo json_encode(["success" => false, "msg" => $this->lang_catalog['not_found'] ?? 'Catálogo no encontrado']);
				return;
			}

			// PDF del listado = misma plantilla del Studio (preview de edición).
			if (isset($request['output']) && $request['output'] === 'pdf') {
				ob_end_clean();
				try {
					$this->downloadStudioPdf($catalogo, $request);
				} catch (\Throwable $e) {
					log_message('error', 'Catalog::download studio PDF - ' . $e->getMessage());
					header('Content-Type: application/json; charset=utf-8');
					http_response_code(500);
					echo json_encode([
						'success' => false,
						'msg' => $this->lang_catalog['studio_export_pdf_error']
							?? ('Error al generar el PDF: ' . $e->getMessage()),
					], JSON_UNESCAPED_UNICODE);
				}
				return;
			}

			// Grupo de clientes (seleccionado o por defecto de la empresa)
			$groupId = isset($request['group']) && !empty($request['group']) && $request['group'] !== 'todo'
				? (int) $request['group']
				: (int) ($this->company_info->default_customer_group_id ?? 0);
			$customerGroup = null;
			if ($groupId > 0) {
				$customerGroup = $this->getObjectFromApi("customergroup/{$groupId}");
				if ($this->hasError($customerGroup)) {
					$customerGroup = null;
				}
			}
			if (!is_object($customerGroup)) {
				$customerGroup = (object) ['name' => ''];
			}

			$format_date = $this->company_info->formatDate ?? null;
			if (!$format_date) {
				$format_date = $this->getObjectFromApi('formatdate/default');
			}
			if ($this->hasError($format_date) || !is_object($format_date)) {
				$format_date = fv_default_format_date_object();
			}

			// Formato de impresión seleccionado (vía API)
			$formatId = isset($request['format_id']) && !empty($request['format_id']) ? intval($request['format_id']) : null;
			$selectedFormat = null;
			if ($formatId && $formatId > 0) {
				$format = $this->getObjectFromApi("format/{$formatId}");
				if (!$this->hasError($format) && is_object($format) && (int) ($format->company_id ?? 0) === (int) $this->company) {
					$selectedFormat = [
						'id' => $format->format_id,
						'name' => $format->format_name ?? '',
						'description' => $format->format_description ?? '',
						'template' => $format->template ?? '',
						'order' => $format->order ?? 0,
						'is_default' => $format->is_default ?? 0,
					];
				}
			}

			// Productos del catálogo listos para descarga (precio por grupo) vía API
			$products = $this->apiList($this->getObjectFromApi("catalog/{$id}/download-products", $groupId > 0 ? ['group_id' => $groupId] : []));
			
			$noProductImageUrl = catalog_public_no_image_url();
			// Preparar rutas de imágenes (logo en API/MinIO o legacy uploads/company)
			$image_company = '';
			if (! empty($this->company_info->image)) {
				$image_company = company_logo_url((string) $this->company_info->image);
			}
			if ($image_company === '') {
				$image_company = base_url('uploads/noimg.png');
			}
			
			$image_logo = ($catalogo->default_catalog == 1)
				? base_url('images/logos/logo.png')
				: base_url('uploads/catalogo_download.png');
			
			// Mapa de etiquetas a datos del producto (todas las equivalencias disponibles para el formato)
			$getProductTags = function($product) use ($noProductImageUrl) {
				$esc = function($v) { return $v !== null && $v !== '' ? htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8') : ''; };
				$imageUrl = ! empty($product->imagen_js) ? $product->imagen_js : $noProductImageUrl;
				$imgTag = '<img src="' . htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') . '" style="max-width: 100px; max-height: 100px;" />';
				$tags = [
					'##id##' => $esc($product->pro_id ?? null),
					'##name##' => $esc($product->name ?? null),
					'##price##' => $esc($product->price_formated ?? null),
					'##description##' => $esc($product->description ?? null),
					'##sku##' => $esc($product->sku ?? null),
					'##category##' => $esc($product->categories_name ?? null),
					'##quantity##' => $esc($product->moq ?? null),
					'##unit##' => $esc($product->unit ?? null),
					'##image##' => $imgTag,
					'##pro_id##' => $esc($product->pro_id ?? null),
					'##price_formated##' => $esc($product->price_formated ?? null),
					'##categories_name##' => $esc($product->categories_name ?? null),
					'##moq##' => $esc($product->moq ?? null),
					'##stock##' => $esc(isset($product->stock) ? $product->stock : null),
					'##sale_price##' => $esc(isset($product->sale_price) ? $product->sale_price : null),
					'##barcode##' => $esc($product->barcode ?? null),
					'##minimum_stock##' => $esc(isset($product->minimum_stock) ? $product->minimum_stock : null),
					'##currency_type##' => $esc($product->currency_type ?? null),
					'##status##' => $esc($product->status ?? null),
				];
				// Cualquier otra propiedad del producto como ##nombre_campo##
				foreach ($product as $key => $value) {
					if (is_scalar($value) || $value === null) {
						$tag = '##' . $key . '##';
						if (!isset($tags[$tag])) {
							$tags[$tag] = $esc($value);
						}
					}
				}
				return $tags;
			};

			// Si hay formato seleccionado, guardar productos procesables (para PDF usamos la fila-plantilla del formato)
			$processedProducts = [];
			$maxForPdf = (isset($request['output']) && $request['output'] === 'pdf') ? 350 : PHP_INT_MAX;
			if ($selectedFormat && !empty($selectedFormat['template'])) {
				$total = count($products);
				log_message('debug', 'Catalog::download - Procesando formato para ' . min($total, $maxForPdf) . ' productos');
				$n = 0;
				foreach ($products as $product) {
					if ($n >= $maxForPdf) break;
					$processedProducts[] = ['product' => $product, 'tags' => $getProductTags($product)];
					$n++;
				}
			}
			
			// Preparar respuesta JSON (legado / clientes que no piden PDF)
			$response = [
				"products" => $products,
				"company_name" => $this->company_info->name ?? '',
				"catalogo_name" => $catalogo->catalog_name ?? '',
				"image_company" => $image_company,
				"image_logo" => $image_logo,
				"image_bussiness" => base_url('uploads/company_.jpg'),
				"group_name" => $customerGroup->name ?? '',
				"date" => date($format_date->format_php ?? fv_display_date_format(), strtotime(date('Y-m-d'))),
				"format" => $selectedFormat,
				"processed_products" => $processedProducts
			];
			
			log_message('debug', 'Catalog::download - Respuesta preparada correctamente');
			
			ob_end_clean();
			header('Content-Type: application/json');
			echo json_encode($response);
			
		} catch (\Exception $e) {
			$errorMsg = $e->getMessage();
			$errorTrace = $e->getTraceAsString();
			log_message('error', 'Catalog::download - Error: ' . $errorMsg);
			log_message('error', 'Catalog::download - Trace: ' . $errorTrace);
			
			ob_end_clean();
			header('Content-Type: application/json');
			echo json_encode([
				"success" => false,
				"msg" => "Error al generar el PDF: " . $errorMsg,
				"trace" => $errorTrace
			]);
		} catch (\Error $e) {
			$errorMsg = $e->getMessage();
			$errorTrace = $e->getTraceAsString();
			log_message('error', 'Catalog::download - Fatal Error: ' . $errorMsg);
			log_message('error', 'Catalog::download - Trace: ' . $errorTrace);
			
			ob_end_clean();
			header('Content-Type: application/json');
			echo json_encode([
				"success" => false,
				"msg" => "Error fatal al generar el PDF: " . $errorMsg,
				"trace" => $errorTrace
			]);
		}
	}

}
