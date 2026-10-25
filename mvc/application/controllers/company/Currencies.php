<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Monedas de empresa: listado y selección vía API Laravel.
 */
class Currencies extends Company_Controller
{
	use ConfigCrudListTools;

	public ?int $company_id;
	public $company_info;
	public ?int $langId;

	public function __construct()
	{
		parent::__construct();
		$this->checkLoggedInOrLoginWithRedirect();

		$this->company_id = (int) $this->getCompanyId();
		$this->langId = (int) ($this->getUserData('language_id') ?? 1);

		$companyInfo = $this->getObjectFromApi("company/{$this->company_id}");
		if ($this->hasError($companyInfo)) {
			$this->company_info = (object) [];
		} else {
			$this->company_info = $companyInfo;
			$this->langId = (int) ($companyInfo->language_id ?? $this->langId);
		}
	}

	public function lists()
	{
		$langCurrency = $this->currencyLangSection('cm-currency');
		$langUnit = $this->currencyLangSection('unit-languaje');

		$data = [
			'title' => $langCurrency['pageTitle'] ?? ($langCurrency['titulo_list'] ?? ''),
			'page' => 'currency',
			'lang_currency' => $langCurrency,
		];
		$data['lang_datatable'] = $this->Langmodel->getMdl('company-datatable', $this->langId);

		$companyCurrency = $this->getObjectFromApi("company/{$this->company_id}/currency");
		if ($this->hasError($companyCurrency) || !is_object($companyCurrency)) {
			$companyCurrency = $this->resolveDefaultCurrencyFromGrouped();
		}

		$countrySortnameMap = $this->buildCountrySortnameMap();
		$data['currency'] = $this->mapCurrencyForView($companyCurrency, $countrySortnameMap);

		$info['lang_unit'] = $langUnit;

		$this->showContent('company/currency/currency_list', array_merge($data, $info));
	}

	public function updatecurrency()
	{
		$request = $this->input->post();
		$currencyId = $this->testInput($request['currency'] ?? '');

		if ($currencyId === '' || $currencyId === null) {
			redirect(company_url('currencies/lists'), 'refresh');
			return;
		}

		$langCurrency = $this->currencyLangSection('cm-currency');
		$response = $this->put("company/{$this->company_id}", [
			'currency' => $currencyId,
		]);

		if ($this->hasError($response)) {
			$msg = '["' . addslashes($langCurrency['update_error'] ?? 'Error') . '", "error", "#DD6B55"]';
		} else {
			$successText = $langCurrency['success_money'] ?? '';
			$msg = '["' . addslashes($successText) . '!", "success", "#A5DC86"]';
		}

		$this->flashMessage($msg);
		redirect(company_url('currencies/lists'), 'refresh');
	}

	public function add()
	{
		show_404();
	}

	public function create()
	{
		show_404();
	}

	public function edit($currency_id = false)
	{
		show_404();
	}

	public function update()
	{
		show_404();
	}

	public function delete($currency_id = false)
	{
		show_404();
	}

	public function changeStatus()
	{
		show_404();
	}

	public function listPdf()
	{
		$this->langArr = $this->getModuleLang('cm-currency');
		$lang = $this->getConfigCrudLang();
		$langUnit = $this->getModuleLang('unit-languaje');
		$title = $this->resolveConfigCrudPageTitle($lang, 'pageTitle', 'titulo_list');
		$companyId = (int) $this->getCompanyId();
		$companyCurrency = $this->getObjectFromApi("company/{$companyId}/currency");

		$headers = [
			$langUnit['continent'] ?? '',
			$langUnit['country'] ?? '',
			$langUnit['currency'] ?? '',
			$langUnit['code'] ?? '',
			$langUnit['symbol'] ?? '',
		];

		$rows = [];
		if (!$this->hasError($companyCurrency) && is_object($companyCurrency)) {
			$mapped = $this->mapCurrencyForView($companyCurrency);
			$rows[] = [
				(string) ($mapped->GroupID ?? ''),
				(string) ($mapped->Contry ?? ''),
				(string) ($mapped->Name ?? ''),
				(string) ($mapped->Code ?? ''),
				(string) ($mapped->Symbol ?? ''),
			];
		}

		$this->sendConfigListPdf($headers, $rows, $title !== '' ? $title : 'currency', 'currency_list');
	}

	/**
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
	 * @return array<string, string>
	 */
	private function currencyLangSection(string $section): array
	{
		$langId = (int) ($this->langId ?? $this->getUserData('language_id') ?? 1);
		$params = ['locale' => ($langId === 2) ? 'es' : 'en'];
		if ($this->company_id > 0) {
			$params['company_id'] = $this->company_id;
		}

		$response = $this->getDataFromApi("langmdl/{$section}", $params);
		if (!is_array($response) || $this->hasError($response)) {
			return $this->getLangJsonSection($section);
		}

		return $response;
	}

	/**
	 * @return array<string, string> country name => sortname (lowercase)
	 */
	private function buildCountrySortnameMap(): array
	{
		$map = [];
		$response = $this->getObjectFromApi('country', [
			'props' => ['name', 'sortname'],
			'all' => true,
			'orderProperty' => 'name',
		]);

		foreach ($this->apiList($response) as $country) {
			$country = is_object($country) ? $country : (object) (array) $country;
			$name = trim((string) ($country->name ?? ''));
			$sortname = strtolower(trim((string) ($country->sortname ?? '')));
			if ($name !== '' && $sortname !== '') {
				$map[$name] = $sortname;
			}
		}

		return $map;
	}

	/**
	 * @param array<string, string> $countrySortnameMap
	 */
	private function resolveCurrencyCountrySortname(string $countryName, string $currencyCode, array $countrySortnameMap): string
	{
		$countryName = trim($countryName);
		if ($countryName !== '') {
			if (isset($countrySortnameMap[$countryName])) {
				return $countrySortnameMap[$countryName];
			}
			foreach ($countrySortnameMap as $name => $sortname) {
				if (strcasecmp($name, $countryName) === 0) {
					return $sortname;
				}
			}
		}

		$code = strtoupper(trim($currencyCode));
		$byCurrencyCode = [
			'USD' => 'us', 'CAD' => 'ca', 'MXN' => 'mx', 'GBP' => 'gb', 'EUR' => 'eu',
			'JPY' => 'jp', 'CNY' => 'cn', 'AUD' => 'au', 'BRL' => 'br', 'CHF' => 'ch',
			'COP' => 'co', 'PEN' => 'pe', 'CLP' => 'cl', 'ARS' => 'ar',
		];

		return $byCurrencyCode[$code] ?? '';
	}

	/**
	 * @param object|null $currency
	 * @param array<string, string> $countrySortnameMap
	 */
	private function mapCurrencyForView($currency, array $countrySortnameMap = []): object
	{
		if (!is_object($currency)) {
			return (object) [
				'GroupID' => '',
				'Contry' => '',
				'Name' => '',
				'Code' => '',
				'Symbol' => '',
				'country_sortname' => '',
			];
		}

		if (isset($currency->Name) || isset($currency->Contry) || isset($currency->GroupID)) {
			$countryName = (string) ($currency->Contry ?? '');
			$currencyCode = (string) ($currency->Code ?? '');

			return (object) [
				'GroupID' => (string) ($currency->GroupID ?? ''),
				'Contry' => $countryName,
				'Name' => (string) ($currency->Name ?? ''),
				'Code' => $currencyCode,
				'Symbol' => (string) ($currency->Symbol ?? $currency->symbol ?? ''),
				'country_sortname' => $this->resolveCurrencyCountrySortname($countryName, $currencyCode, $countrySortnameMap),
			];
		}

		$tags = $currency->tags ?? [];
		if (is_object($tags)) {
			$tags = (array) $tags;
		}
		if (!is_array($tags)) {
			$tags = [];
		}

		$countryName = (string) ($tags['country_category'] ?? '');
		$currencyCode = (string) ($currency->code ?? '');

		return (object) [
			'GroupID' => (string) ($tags['main_category'] ?? ''),
			'Contry' => $countryName,
			'Name' => (string) ($currency->name ?? ''),
			'Code' => $currencyCode,
			'Symbol' => (string) ($currency->symbol ?? ''),
			'country_sortname' => $this->resolveCurrencyCountrySortname($countryName, $currencyCode, $countrySortnameMap),
		];
	}

	/**
	 * @return object|null
	 */
	private function resolveDefaultCurrencyFromGrouped()
	{
		$groups = $this->apiList($this->getObjectFromApi('currency/grouped-by-continent'));
		foreach ($groups as $group) {
			$children = is_object($group) ? ($group->currencies ?? []) : ($group['currencies'] ?? []);
			if (!is_array($children)) {
				continue;
			}
			foreach ($children as $child) {
				$default = is_object($child) ? ($child->default ?? '') : ($child['default'] ?? '');
				if ($default === 'selected') {
					return is_object($child) ? $child : (object) $child;
				}
			}
		}

		foreach ($groups as $group) {
			$children = is_object($group) ? ($group->currencies ?? []) : ($group['currencies'] ?? []);
			if (is_array($children) && !empty($children[0])) {
				$first = $children[0];
				return is_object($first) ? $first : (object) $first;
			}
		}

		return null;
	}
}
