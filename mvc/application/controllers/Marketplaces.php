<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Marketplaces extends Application_Controller {

	public function __construct()
	{
		// Público: sin login de Application_Controller.
		MY_Controller::__construct();
		$this->getDictionary();
	}

	public function index($page = 1)
	{
		if (isset($_GET['page'])) {
			$page = $_GET['page'];
		}
		$show_per_page = 12;
		$page = max(1, (int) $page);

		$resp = $this->getDataFromApi('marketplace/companies', [
			'page' => $page,
			'per_page' => $show_per_page,
		], true);

		$company = ['results' => [], 'total' => 0];
		if (is_array($resp) && ! $this->hasError($resp) && isset($resp['data']) && is_array($resp['data'])) {
			$company = $resp['data'];
		}

		$companyList = $this->marketplaceRowsToObjects($company['results'] ?? []);
		$homeFeed = $this->fetchHomeFeed();
		$data = $this->buildMarketplacePageData(
			$companyList,
			(int) ($company['total'] ?? 0),
			$show_per_page,
			$page,
			$homeFeed
		);
		$data['searchQuery'] = '';
		$this->showContent('marketplaces', $data, 'marketplace_public');
	}

	public function details($slug_url = false, $page = 1)
	{
		$langId = $this->resolvePublicLangId();
		$mp = $this->resolveMarketplaceTexts($langId);
		$landing = $this->resolveLandingNavTexts($langId);
		$data = $this->basePublicViewData(array_merge($landing, $mp));
		$data['mp'] = $mp;
		$data['landingNavPage'] = 'marketplace';
		$data['title'] = (string) ($mp['details_meta_title'] ?? $mp['meta_title'] ?? 'Company');

		$compdetails = null;
		$slug = trim((string) $slug_url);
		if ($slug !== '') {
			$resp = $this->getDataFromApi('marketplace/companies/slug/' . rawurlencode($slug), [], true);
			if (is_array($resp) && ! $this->hasError($resp) && isset($resp['data'])) {
				$compdetails = is_object($resp['data']) ? $resp['data'] : (object) $resp['data'];
			}
		}
		$data['data'] = $compdetails;
		$data['company'] = $compdetails;

		$data['categoryList'] = [];
		$catid = (int) ($this->input->get('cat') ?? 0);
		if ($compdetails && isset($compdetails->company_id)) {
			$catResp = $this->getDataFromApi('marketplace/companies/' . (int) $compdetails->company_id . '/categories', [], true);
			if (is_array($catResp) && ! $this->hasError($catResp) && isset($catResp['data']) && is_array($catResp['data'])) {
				$data['categoryList'] = $this->marketplaceRowsToObjects($catResp['data']);
				$validIds = [];
				foreach ($data['categoryList'] as $c) {
					$validIds[] = (int) ($c->cat_id ?? $c->category_id ?? 0);
				}
				if ($catid < 1 || ! in_array($catid, $validIds, true)) {
					$catid = $validIds[0] ?? 0;
				}
			}
			if (! empty($compdetails->name)) {
				$data['title'] = (string) $compdetails->name . ' · ' . (string) ($mp['meta_brand'] ?? 'FullVendor');
			}
		}

		if (isset($_GET['page'])) {
			$page = $_GET['page'];
		}
		$show_per_page = 12;
		$page = max(1, (int) $page);
		$product = ['results' => [], 'total' => 0];
		if ($compdetails && $catid > 0) {
			$prodResp = $this->getDataFromApi('marketplace/companies/' . (int) $compdetails->company_id . '/products', [
				'category_id' => $catid,
				'page' => $page,
				'per_page' => $show_per_page,
			], true);
			if (is_array($prodResp) && ! $this->hasError($prodResp) && isset($prodResp['data']) && is_array($prodResp['data'])) {
				$product = $prodResp['data'];
			}
		}
		$data['productList'] = $this->marketplaceRowsToObjects($product['results'] ?? []);
		$data['productTotal'] = (int) ($product['total'] ?? 0);
		$data['activeCategoryId'] = $catid;
		$data['paginate'] = $this->buildPagination((int) ($product['total'] ?? 0), $show_per_page, 'marketplaces/details/' . rawurlencode($slug));
		$data['head_content'] = 'common/public-head-marketplace';
		$data['header_content'] = 'marketplace/partials/storefront_header';
		$data['footer_content'] = 'marketplace/partials/storefront_footer';
		// Categorías de la empresa para el nav del storefront.
		$data['categoryCarousel'] = $data['categoryList'] ?? [];
		foreach ($data['categoryCarousel'] as $i => $cat) {
			if (! is_object($cat)) {
				continue;
			}
			$cat->company_slug = (string) ($compdetails->slug_url ?? '');
			$cat->company_name = (string) ($compdetails->name ?? '');
			$cat->company_id = (int) ($compdetails->company_id ?? 0);
			$cat->cover_image_uuid = trim((string) ($cat->image ?? ''));
			$cat->image_uuid = $cat->cover_image_uuid !== '' ? $cat->cover_image_uuid : null;
			$data['categoryCarousel'][$i] = $cat;
		}

		$this->showContent('marketplace/company_details', $data, 'marketplace_public');
	}

	public function search()
	{
		$keyword = trim((string) $this->input->post('company_name'));
		if ($keyword === '') {
			$keyword = trim((string) $this->input->get('q'));
		}
		$resp = $this->post('marketplace/companies/search', ['company_name' => $keyword], true);
		$rows = [];
		if (is_array($resp) && ! $this->hasError($resp) && isset($resp['data']['results']) && is_array($resp['data']['results'])) {
			$rows = $resp['data']['results'];
		}
		$companyList = $this->marketplaceRowsToObjects($rows);
		$homeFeed = $this->fetchHomeFeed();
		$data = $this->buildMarketplacePageData(
			$companyList,
			count($companyList),
			max(count($companyList), 1),
			1,
			$homeFeed
		);
		$data['searchQuery'] = $keyword;
		$data['paginate'] = '';
		$this->showContent('marketplaces', $data, 'marketplace_public');
	}

	public function productfilterSearchbyCategory()
	{
		$categoryId = $this->input->post('categoryId');
		$companyId = (int) $this->input->post('companyId');

		$resp = $this->post('marketplace/products/by-category', [
			'categoryId' => $categoryId,
			'companyId' => $companyId,
		], true);

		$products = [];
		if (is_array($resp) && ! $this->hasError($resp) && isset($resp['data']['products']) && is_array($resp['data']['products'])) {
			$products = $this->marketplaceRowsToObjects($resp['data']['products']);
		}

		$company = (object) [
			'company_id' => $companyId,
			'name' => trim((string) $this->input->post('companyName')),
			'slug_url' => trim((string) $this->input->post('companySlug')),
			'image' => trim((string) $this->input->post('companyImage')),
		];

		$mp = $this->resolveMarketplaceTexts($this->resolvePublicLangId());
		$html = $this->load->view('marketplace/partials/product_cards', [
			'productList' => $products,
			'mp' => $mp,
			'company' => $company,
			'storeMode' => (string) $this->input->post('storeMode') === '1',
		], true);

		echo json_encode(['product_list' => $html]);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function fetchHomeFeed(): array
	{
		$resp = $this->getDataFromApi('marketplace/home', [
			'companies' => 16,
			'products_per_company' => 6,
			'featured' => 16,
		], true);
		if (! is_array($resp) || $this->hasError($resp) || ! isset($resp['data']) || ! is_array($resp['data'])) {
			return [
				'companies' => [],
				'categories_by_company' => [],
				'category_carousel' => [],
				'promo_products' => [],
				'featured_products' => [],
			];
		}

		$data = $resp['data'];
		$blocks = [];
		foreach (($data['companies'] ?? []) as $block) {
			$block = is_array($block) ? $block : (array) $block;
			$company = $block['company'] ?? null;
			$company = is_object($company) ? $company : (is_array($company) ? (object) $company : null);
			$products = $this->marketplaceRowsToObjects(
				is_array($block['products'] ?? null) ? $block['products'] : []
			);
			$categories = $this->marketplaceRowsToObjects(
				is_array($block['categories'] ?? null) ? $block['categories'] : []
			);
			if ($company === null) {
				continue;
			}
			$blocks[] = [
				'company' => $company,
				'products' => $products,
				'categories' => $categories,
			];
		}

		$categoriesByCompany = [];
		foreach (($data['categories_by_company'] ?? []) as $row) {
			$row = is_array($row) ? $row : (array) $row;
			$categoriesByCompany[] = [
				'company_id' => (int) ($row['company_id'] ?? 0),
				'company_name' => (string) ($row['company_name'] ?? ''),
				'company_slug' => (string) ($row['company_slug'] ?? ''),
				'company_image' => (string) ($row['company_image'] ?? ''),
				'categories' => $this->marketplaceRowsToObjects(
					is_array($row['categories'] ?? null) ? $row['categories'] : []
				),
			];
		}

		return [
			'companies' => $blocks,
			'categories_by_company' => $categoriesByCompany,
			'category_carousel' => $this->marketplaceRowsToObjects(
				is_array($data['category_carousel'] ?? null) ? $data['category_carousel'] : []
			),
			'promo_products' => $this->marketplaceRowsToObjects(
				is_array($data['promo_products'] ?? null) ? $data['promo_products'] : []
			),
			'featured_products' => $this->marketplaceRowsToObjects(
				is_array($data['featured_products'] ?? null) ? $data['featured_products'] : []
			),
		];
	}

	/**
	 * @param list<object> $companyList
	 * @param array<string, mixed> $homeFeed
	 * @return array<string, mixed>
	 */
	private function buildMarketplacePageData(
		array $companyList,
		int $total,
		int $perPage,
		int $page,
		array $homeFeed = []
	): array {
		$langId = $this->resolvePublicLangId();
		$mp = $this->resolveMarketplaceTexts($langId);
		$landing = $this->resolveLandingNavTexts($langId);
		$data = $this->basePublicViewData(array_merge($landing, $mp));
		$data['mp'] = $mp;
		$data['landingNavPage'] = 'marketplace';
		$data['title'] = (string) ($mp['meta_title'] ?? 'Marketplace');
		$data['companyList'] = $companyList;
		$data['companyPropositions'] = is_array($homeFeed['companies'] ?? null) && $homeFeed['companies'] !== []
			? $homeFeed['companies']
			: $this->buildCompanyPropositions($companyList, 4);
		$data['categoriesByCompany'] = is_array($homeFeed['categories_by_company'] ?? null)
			? $homeFeed['categories_by_company']
			: [];
		$data['categoryCarousel'] = is_array($homeFeed['category_carousel'] ?? null)
			? $homeFeed['category_carousel']
			: [];
		$data['promoProducts'] = is_array($homeFeed['promo_products'] ?? null)
			? $homeFeed['promo_products']
			: [];
		$data['featuredProducts'] = is_array($homeFeed['featured_products'] ?? null)
			? $homeFeed['featured_products']
			: [];
		$data['paginate'] = $this->buildPagination($total, $perPage, 'marketplaces');
		$data['head_content'] = 'common/public-head-marketplace';
		$data['header_content'] = 'marketplace/partials/storefront_header';
		$data['footer_content'] = 'marketplace/partials/storefront_footer';
		$data['isMarketplaceHome'] = true;

		return $data;
	}

	/**
	 * @param array<string, string> $mp
	 * @return array<string, mixed>
	 */
	private function basePublicViewData(array $mergedTexts): array
	{
		$langId = $this->resolvePublicLangId();

		return [
			'landing' => $mergedTexts,
			'landingLangId' => $langId,
			'landingLocale' => function_exists('fv_language_id_to_locale')
				? fv_language_id_to_locale($langId)
				: 'es',
			'isPublicLanding' => true,
		];
	}

	protected function resolvePublicLangId(): int
	{
		$q = strtolower(trim((string) $this->input->get('lang')));
		if ($q !== '' && function_exists('fv_locale_to_language_id')) {
			$id = fv_locale_to_language_id($q);
			if ($id > 0) {
				$_SESSION['languageid'] = $id;
				$this->setUserData('language_id', $id);

				return $id;
			}
		}

		$session = (int) ($this->getUserData('language_id') ?? ($_SESSION['languageid'] ?? 0));
		if ($session > 0) {
			return $session;
		}

		return 2;
	}

	/**
	 * @return array<string, string>
	 */
	protected function resolveMarketplaceTexts(int $langId): array
	{
		$fromJson = function_exists('fv_lang_json_flat_section')
			? fv_lang_json_flat_section($langId, 'public-marketplace')
			: [];
		if (! is_array($fromJson)) {
			$fromJson = [];
		}

		$defaultsPath = APPPATH . 'config/public_marketplace_defaults.php';
		$defaults = [];
		if (is_readable($defaultsPath)) {
			$all = include $defaultsPath;
			if (is_array($all)) {
				$locale = function_exists('fv_language_id_to_locale')
					? fv_language_id_to_locale($langId)
					: 'es';
				$en = is_array($all['en'] ?? null) ? $all['en'] : [];
				$loc = is_array($all[$locale] ?? null) ? $all[$locale] : [];
				$defaults = array_merge($en, $loc);
			}
		}

		return array_merge($defaults, $fromJson);
	}

	/**
	 * @return array<string, string>
	 */
	protected function resolveLandingNavTexts(int $langId): array
	{
		$fromJson = function_exists('fv_lang_json_flat_section')
			? fv_lang_json_flat_section($langId, 'public-landing')
			: [];

		return is_array($fromJson) ? $fromJson : [];
	}

	/**
	 * @param list<object> $companies
	 * @return list<array{company: object, products: list<object>}>
	 */
	private function buildCompanyPropositions(array $companies, int $productsPerCompany): array
	{
		$out = [];
		$limit = min(6, count($companies));
		for ($i = 0; $i < $limit; $i++) {
			$company = $companies[$i];
			$companyId = (int) ($company->company_id ?? 0);
			if ($companyId < 1) {
				continue;
			}
			$products = [];
			$catResp = $this->getDataFromApi('marketplace/companies/' . $companyId . '/categories', [], true);
			$catId = 0;
			if (is_array($catResp) && ! $this->hasError($catResp) && isset($catResp['data'][0])) {
				$first = is_object($catResp['data'][0]) ? $catResp['data'][0] : (object) $catResp['data'][0];
				$catId = (int) ($first->cat_id ?? $first->category_id ?? 0);
			}
			if ($catId > 0) {
				$prodResp = $this->getDataFromApi('marketplace/companies/' . $companyId . '/products', [
					'category_id' => $catId,
					'page' => 1,
					'per_page' => $productsPerCompany,
				], true);
				if (is_array($prodResp) && ! $this->hasError($prodResp) && isset($prodResp['data']['results']) && is_array($prodResp['data']['results'])) {
					$products = $this->marketplaceRowsToObjects($prodResp['data']['results']);
				}
			}
			$out[] = [
				'company' => $company,
				'products' => $products,
			];
		}

		return $out;
	}

	private function buildPagination(int $total, int $perPage, string $basePath): string
	{
		$config['base_url'] = base_url($basePath);
		$config['num_links'] = 2;
		$config['total_rows'] = $total;
		$config['per_page'] = $perPage;
		$config['full_tag_open'] = '<ul class="fv-mp-pager">';
		$config['full_tag_close'] = '</ul>';
		$config['num_tag_open'] = '<li>';
		$config['num_tag_close'] = '</li>';
		$config['first_link'] = '&laquo;';
		$config['first_tag_open'] = '<li>';
		$config['first_tag_close'] = '</li>';
		$config['last_link'] = '&raquo;';
		$config['last_tag_open'] = '<li>';
		$config['last_tag_close'] = '</li>';
		$config['prev_link'] = '&lsaquo;';
		$config['prev_tag_open'] = '<li>';
		$config['prev_tag_close'] = '</li>';
		$config['next_link'] = '&rsaquo;';
		$config['next_tag_open'] = '<li>';
		$config['next_tag_close'] = '</li>';
		$config['cur_tag_open'] = '<li class="is-active"><span>';
		$config['cur_tag_close'] = '</span></li>';
		$config['use_page_numbers'] = true;
		$config['page_query_string'] = true;
		$config['query_string_segment'] = 'page';
		$config['reuse_query_string'] = true;
		$this->pagination->initialize($config);

		return (string) $this->pagination->create_links();
	}

	/**
	 * @param list<mixed> $rows
	 * @return list<object>
	 */
	private function marketplaceRowsToObjects(array $rows): array
	{
		$out = [];
		foreach ($rows as $row) {
			$out[] = is_object($row) ? $row : (object) $row;
		}

		return $out;
	}
}
