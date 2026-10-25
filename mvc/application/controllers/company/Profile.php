<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Perfil de empresa: lectura y actualización exclusivamente vía API Laravel.
 */
class Profile extends Company_Controller {

	public ?int $company;
	public $company_info;
	public $format_numeric;
	public ?int $langId;

	public function __construct()
	{
		parent::__construct();
		$this->checkLoggedInOrLoginWithRedirect();

		$this->company = (int) $this->getCompanyId();
		$this->langId = (int) ($this->getUserData('language_id') ?? 1);

		$companyInfo = $this->getObjectFromApi("company/{$this->company}");
		if ($this->hasError($companyInfo)) {
			$this->company_info = (object) [];
		} else {
			$this->company_info = $companyInfo;
			$this->langId = (int) ($companyInfo->language_id ?? $this->langId);
		}

		$formatNumeric = $this->getObjectFromApi("company/{$this->company}/format-numeric");
		if ($this->hasError($formatNumeric)) {
			$formatNumeric = $this->getObjectFromApi('formatnumeric/default');
		}
		$this->format_numeric = $this->hasError($formatNumeric)
			? (object) ['format_id' => 0, 'numeric_format' => '']
			: $formatNumeric;
	}

	public function index()
	{
		$langPerfil = $this->profileLangSection('cmp-perfil');
		$data = [
			'title' => $langPerfil['page_title'] ?? '',
			'page' => 'profile',
		];

		$info['lang_vendor'] = array_merge(
			$this->profileLangSection('cmp-vendedor'),
			$this->profileLangSection('perfil-vendedores')
		);
		$info['lang_order'] = $this->profileLangSection('cmp-orders');
		$info['lang_perfil'] = $langPerfil;
		$data['lang_unit'] = $this->profileLangSection('unit-languaje');
		$data['langdef'] = $this->profileLangSection('comp-login-head');
		$info['lang_labels'] = $this->profileLangSection('comp-csv-flds');
		$info['lang_customer'] = $this->profileLangSection('cmp-clientes');

		$compId = (int) $this->getCompanyId();
		$data['compId'] = $compId;
		$data['data'] = $this->getObjectFromApi("company/{$compId}");
		if ($this->hasError($data['data'])) {
			show_error($langPerfil['profile_update_error'] ?? 'Error', 500);
			return;
		}

		$data['contactlist'] = $this->apiList($this->getObjectFromApi("company/{$compId}/contacts"));
		$data['language'] = $this->apiList($this->getObjectFromApi('language/actives'));
		$data['countrylist'] = $this->fetchCountriesAlphabetic();
		$data['profile_countries'] = $data['countrylist'];
		$numericFormats = $this->apiList($this->getObjectFromApi('formatnumeric', ['all' => true]));
		$data['numeric_formats'] = $numericFormats;
		$dateFormats = $this->apiList($this->getObjectFromApi('formatdate', ['all' => true]));
		$data['date_formats'] = $dateFormats;
		$data['format_date'] = $this->resolveCompanyFormatDate($data['data']);
		$data['format_sample_currency'] = $this->resolveFormatSampleCurrencySymbol($compId, $data['data']);
		$data['date_format_tiles'] = $this->buildDateFormatTiles($dateFormats, $data['format_date']);
		$data['numeric_format_tiles'] = $this->buildNumericFormatTiles(
			$numericFormats,
			$this->format_numeric,
			$data['format_sample_currency']
		);
		$data['currList'] = $this->apiList($this->getObjectFromApi("company/{$compId}/currencies"));

		$currencyCodeId = (int) ($data['data']->currency_code_id ?? 0);
		$data['formato_definido'] = $currencyCodeId > 0
			? $this->filterCurrencyFormatsByFormatId($this->fetchCurrencyFormats(), $currencyCodeId)
			: [];
		$info['format_numeric'] = $this->format_numeric;
		$info['formats'] = $numericFormats;
		$data['currencyCode'] = $this->obtener_currencyCode();
		$data['format_default'] = $this->obtener_currencyFormat('AED');
		$data['currencies_pather'] = $this->buildCurrenciesPather($compId, $data['data']);
		$data['semaforo_page_texts'] = array_merge(
			$langPerfil,
			$info['lang_vendor'] ?? [],
			$this->getLangJsonSection('semaphore'),
			$this->getLangJsonSection('cmp-products')
		);

		$this->addJsFile(base_url('companyasset/company/profile/profile-update.js'));
		$this->addJsFile(base_url('companyasset/company/product/semaforo.js'));

		$this->showContent('company/profile/profile', array_merge($data, $info));
	}

	/**
	 * @param object|null $company
	 */
	private function resolveCompanyFormatDate($company): object
	{
		$formatDateId = (int) (is_object($company) ? ($company->format_date_id ?? 0) : 0);
		if ($formatDateId > 0) {
			$fmt = $this->getObjectFromApi("formatdate/{$formatDateId}");
			if (! $this->hasError($fmt) && is_object($fmt)) {
				return fv_normalize_format_date_object($fmt);
			}
		}
		$fmt = $this->getObjectFromApi('formatdate/default');
		if (! $this->hasError($fmt) && is_object($fmt)) {
			return fv_normalize_format_date_object($fmt);
		}

		return fv_default_format_date_object();
	}

	/**
	 * @param object|null $company
	 */
	private function resolveFormatSampleCurrencySymbol(int $compId, $company): string
	{
		$currency = $this->getObjectFromApi("company/{$compId}/currency");
		if (! $this->hasError($currency) && is_object($currency)) {
			$symbol = trim((string) ($currency->symbol ?? $currency->Symbol ?? ''));
			if ($symbol !== '') {
				return $symbol;
			}
		}

		return '$';
	}

	/**
	 * @param list<object> $dateFormats
	 * @param object|null $selected
	 * @return list<array{id:int,sample:string,pattern:string,selected:bool}>
	 */
	private function buildDateFormatTiles(array $dateFormats, $selected): array
	{
		$selectedId = (int) (is_object($selected) ? ($selected->id ?? 0) : 0);
		$sampleTs = strtotime('2026-12-31');
		$tiles = [];
		foreach ($dateFormats as $fmt) {
			if (! is_object($fmt)) {
				continue;
			}
			$id = (int) ($fmt->id ?? 0);
			$php = (string) ($fmt->format_php ?? '');
			if ($id <= 0 || $php === '') {
				continue;
			}
			$tiles[] = [
				'id' => $id,
				'sample' => date($php, $sampleTs),
				'pattern' => $this->dateFormatPatternLabel($php),
				'selected' => $selectedId > 0 ? ($id === $selectedId) : ((string) ($fmt->default ?? '') === '1'),
			];
		}

		return $tiles;
	}

	/**
	 * @param list<object> $numericFormats
	 * @param object|null $selected
	 * @return list<array{id:int,sample:string,pattern:string,selected:bool}>
	 */
	private function buildNumericFormatTiles(array $numericFormats, $selected, string $symbol): array
	{
		$selectedId = (int) (is_object($selected) ? ($selected->format_id ?? 0) : 0);
		$tiles = [];
		foreach ($numericFormats as $fmt) {
			if (! is_object($fmt)) {
				continue;
			}
			$id = (int) ($fmt->format_id ?? 0);
			if ($id <= 0) {
				continue;
			}
			$pattern = (string) ($fmt->numeric_format ?? '');
			$sampleValue = (($fmt->code_regional ?? '') === 'en_IN') ? 1234567.89 : 1234.56;
			$tiles[] = [
				'id' => $id,
				'sample' => $this->formatNumericSample($fmt, $symbol, $sampleValue),
				'pattern' => $pattern !== '' ? $pattern : $this->formatNumericSample($fmt, '', $sampleValue),
				'selected' => $selectedId > 0 ? ($id === $selectedId) : ((int) ($fmt->default ?? 0) === 1),
			];
		}

		return $tiles;
	}

	private function dateFormatPatternLabel(string $phpFormat): string
	{
		$map = [
			'd/m/Y' => 'DD/MM/YYYY',
			'd-m-Y' => 'DD-MM-YYYY',
			'd.m.Y' => 'DD.MM.YYYY',
			'm/d/Y' => 'MM/DD/YYYY',
			'm-d-Y' => 'MM-DD-YYYY',
			'Y-m-d' => 'YYYY-MM-DD',
			'Y/m/d' => 'YYYY/MM/DD',
			'Y.m.d' => 'YYYY.MM.DD',
			'd/m/y' => 'DD/MM/YY',
			'd M Y' => 'DD Mon YYYY',
			'M j, Y' => 'Mon DD, YYYY',
			'd \d\e M \d\e Y' => 'DD de Mon de YYYY',
			'Y年n月j日' => 'YYYY年MM月DD日',
		];

		return $map[$phpFormat] ?? strtoupper($phpFormat);
	}

	/**
	 * @param object $fmt
	 */
	private function formatNumericSample($fmt, string $symbol, float $value): string
	{
		$digits = (int) ($fmt->digits ?? 2);
		$radix = (string) ($fmt->radixPoint ?? '.');
		$group = (string) ($fmt->groupSeparator ?? ',');
		$code = (string) ($fmt->code_regional ?? '');

		if ($code === 'en_IN') {
			$formatted = $this->formatIndianNumber($value, $digits, $radix);
		} else {
			$formatted = number_format($value, $digits, $radix, $group);
		}

		$symbol = trim($symbol);
		if ($symbol === '') {
			return $formatted;
		}

		return $symbol . $formatted;
	}

	private function formatIndianNumber(float $value, int $digits, string $radix): string
	{
		$negative = $value < 0;
		$value = abs($value);
		$fixed = number_format($value, $digits, '.', '');
		[$intPart, $decPart] = array_pad(explode('.', $fixed, 2), 2, '');
		$last3 = substr($intPart, -3);
		$rest = substr($intPart, 0, -3);
		$groups = [];
		while ($rest !== '' && $rest !== false) {
			$groups[] = substr($rest, -2);
			$rest = substr($rest, 0, -2);
		}
		$groups = array_reverse(array_filter($groups, static fn ($g) => $g !== '' && $g !== false));
		$grouped = ($groups !== [] ? implode(',', $groups) . ',' : '') . $last3;
		$out = $grouped . ($digits > 0 ? $radix . $decPart : '');

		return ($negative ? '-' : '') . $out;
	}

	public function obtener_currencyCode()
	{
		return $this->fetchCurrencyFormats();
	}

	public function obtener_currencyFormat($currencyCode)
	{
		$formats = $this->fetchCurrencyFormats();
		$code = strtoupper(trim((string) $currencyCode));
		if ($code === '') {
			return [];
		}

		$seen = [];
		$result = [];
		foreach ($formats as $row) {
			$rowCode = strtoupper((string) (is_object($row) ? ($row->CurrencyCode ?? '') : ($row['CurrencyCode'] ?? '')));
			if ($rowCode !== $code) {
				continue;
			}
			$formatId = (int) (is_object($row) ? ($row->format_id ?? 0) : ($row['format_id'] ?? 0));
			if ($formatId > 0 && isset($seen[$formatId])) {
				continue;
			}
			if ($formatId > 0) {
				$seen[$formatId] = true;
			}
			$result[] = $row;
		}

		return $result;
	}

	public function obtener_formatos()
	{
		$currencyCode = $this->input->post('currencycode');
		echo json_encode($this->obtener_currencyFormat($currencyCode));
	}

	public function update()
	{
		echo json_encode($this->input->post());
	}

	public function save()
	{
		$compId = (int) $this->getCompanyId();
		$langPerfil = $this->profileLangSection('cmp-perfil');
		$request = $this->input->post();

		if (!$this->input->post('name')) {
			redirect(company_url('profile'), 'refresh');
			return;
		}

		$existingCompany = $this->getObjectFromApi("company/{$compId}");
		$existingEmail = '';
		if (!$this->hasError($existingCompany) && is_object($existingCompany)) {
			$existingEmail = trim((string) ($existingCompany->email ?? ''));
		}

		$mydata = [
			'language_id' => $this->testInput($this->input->post('language_id')),
			'name' => $this->testInput($this->input->post('name')),
			'short_name' => $this->testInput($this->input->post('short_name')),
			// El email de la empresa no se edita desde el perfil.
			'email' => $existingEmail !== '' ? $existingEmail : $this->testInput($this->input->post('email')),
			'website' => $this->testInput($this->input->post('website')),
			'fax' => $this->testInput($this->input->post('fax')),
			'address' => $this->testInput($this->input->post('address')),
			'zip_code' => $this->testInput($this->input->post('zip_code')),
			'city' => $this->testInput($this->input->post('city')),
			'country' => $this->testInput($this->input->post('country')),
			'state' => $this->testInput($this->input->post('state')),
			'tax_condition' => $this->testInput($this->input->post('tax_condition')),
			'tax_id' => $this->testInput($this->input->post('tax_id')),
			'cell_number' => $this->testInput($this->input->post('cell_number')),
			'phone_number' => $this->testInput($this->input->post('phone_number')),
			'format_id' => $this->testInput($this->input->post('format')),
			'format_date_id' => $this->testInput($this->input->post('format_date')),
			'ordersend_time' => $this->testInput($this->input->post('ordersend_time')),
			'catalogsend_time' => $this->testInput($this->input->post('catalogsend_time')),
			'sell_with_inventory' => $this->testInput($this->input->post('sell_with_inventory')),
			'show_inventory' => $this->testInput($this->input->post('show_inventory')),
			'show_semaforo_stock' => $this->testInput($this->input->post('show_semaforo_stock')),
			'email_client_on_salesman_order' => $this->testInput($this->input->post('email_client_on_salesman_order')),
			'email_on_salesman_order' => @$request['email_on_salesman_order'],
			'publish_price_list' => $this->testInput($this->input->post('publish_price_list')),
			'show_plus_taxes_label' => $this->testInput($this->input->post('show_plus_taxes_label')),
			'plus_taxes_label' => $this->testInput($this->input->post('plus_taxes_label')),
			'max_number_of_clients' => $this->testInput($this->input->post('max_number_of_clients')),
			'max_number_of_orders' => $this->testInput($this->input->post('max_number_of_orders')),
			'max_number_of_vendors' => $this->testInput($this->input->post('max_number_of_vendors')),
			'total_price' => $this->testInput($this->input->post('total_price')),
			'sem_red_hasta' => @$request['sem_red_hasta'],
			'sem_amarillo_desde' => @$request['sem_amarillo_desde'],
			'sem_amarillo_hasta' => @$request['sem_amarillo_hasta'],
			'sem_verde_desde' => @$request['sem_verde_desde'],
			'sem_red' => @$request['sem_red'],
			'sem_amarillo' => @$request['sem_amarillo'],
			'sem_verde' => @$request['sem_verde'],
		];

		$currency = $this->testInput($this->input->post('currency'));
		if ($currency !== '' && $currency !== null) {
			$mydata['currency'] = $currency;
		}

		$slugSource = $this->testInput($this->input->post('name'));
		if ($slugSource !== '') {
			$mydata['slug_url'] = $this->getUniqueSlugUrl(strtolower(url_title($slugSource)), $compId);
		}

		$response = $this->put("company/{$compId}", $mydata);
		if ($this->hasError($response)) {
			$msg = '["' . addslashes($langPerfil['profile_update_error'] ?? 'Error') . '", "error", "#DD6B55"]';
		} else {
			$this->syncCompanyContacts(
				$compId,
				(array) $this->input->post('cname'),
				(array) $this->input->post('cemail'),
				(array) $this->input->post('cwebsite'),
				(array) $this->input->post('cphone'),
				(array) $this->input->post('ccphone')
			);
			$msg = '["' . addslashes($langPerfil['profile_updated_success'] ?? '') . '", "success", "#A5DC86"]';
		}

		$this->flashMessage($msg);
		redirect(company_url('profile'), 'refresh');
	}

	public function logo()
	{
		$langPerfil = $this->getLangJsonSection('cmp-perfil');
		$langImage = $this->getLangJsonSection('cmp-admin-company');
		$compId = (int) $this->getCompanyId();
		$data = [
			'title' => $langPerfil['logo_page_title'] ?? '',
			'page' => 'profile',
			'subpage' => 'logo_settings',
			'compId' => $compId,
			'data' => $this->getObjectFromApi("company/{$compId}"),
			'logoLang' => array_merge($langImage, $langPerfil),
		];

		if ($this->hasError($data['data'])) {
			show_error($langPerfil['profile_update_error'] ?? 'Error', 500);
			return;
		}

		$this->showContent('company/logo_setting', $data);
	}

	public function logosave()
	{
		$compId = (int) $this->getCompanyId();
		$imgType = (string) $this->input->post('imgType');
		$mydata = [];
		$error = null;
		$oldLogo = null;
		$msg = null;
		$langCmp = $this->getLangJsonSection('cmp-admin-company');

		// Solo image | small_image
		if (! in_array($imgType, ['image', 'small_image'], true)) {
			$msg = json_encode(
				[$langCmp['company_logo_invalid'] ?? '', 'error', '#DD6B55'],
				JSON_UNESCAPED_UNICODE
			);
		} elseif (! $this->validImg($imgType)) {
			$msg = json_encode(
				[$langCmp['company_logo_invalid'] ?? '', 'error', '#DD6B55'],
				JSON_UNESCAPED_UNICODE
			);
		} else {
			$uploadErr = (int) ($_FILES['logo']['error'] ?? UPLOAD_ERR_NO_FILE);
			if ($uploadErr !== UPLOAD_ERR_OK) {
				$error = (string) ($langCmp['company_image_upload_failed'] ?? '');
			} else {
				$tmp = (string) ($_FILES['logo']['tmp_name'] ?? '');
				if ($tmp === '' || ! is_uploaded_file($tmp)) {
					$error = (string) ($langCmp['company_image_upload_failed'] ?? '');
				} else {
					$oldLogo = $this->input->post('oldLogo');
					$stored = $this->uploadCompanyImageToMinioApi(
						$compId,
						$tmp,
						(string) ($_FILES['logo']['name'] ?? 'logo.png')
					);
					if ($stored === null) {
						$error = (string) ($langCmp['company_image_upload_failed'] ?? '');
					} else {
						$mydata[$imgType] = $stored;
					}
				}
			}
		}

		if (count($mydata) > 0) {
			$response = $this->put("company/{$compId}", $mydata);
			if ($this->hasError($response)) {
				$msg = json_encode(
					[$langCmp['company_image_upload_failed'] ?? '', 'error', '#DD6B55'],
					JSON_UNESCAPED_UNICODE
				);
			} else {
				if (! empty($oldLogo) && $oldLogo !== '' && ! is_null($oldLogo)) {
					if (! function_exists('company_logo_is_api_image') || ! company_logo_is_api_image((string) $oldLogo)) {
						if (file_exists('./uploads/company/' . $oldLogo)) {
							@unlink('./uploads/company/' . $oldLogo);
						}
					}
				}
				$msg = json_encode(
					[$langCmp['company_logo_updated_success'] ?? '', 'success', '#A5DC86'],
					JSON_UNESCAPED_UNICODE
				);
			}
		} elseif ($error !== null && $error !== '') {
			$msg = json_encode([$error, 'error', '#DD6B55'], JSON_UNESCAPED_UNICODE);
		}

		if ($msg !== null && $msg !== '') {
			$this->flashMessage($msg);
		}

		redirect(company_url('profile/logo'), 'refresh');
	}

	/**
	 * Elimina image o small_image de la empresa (limpia el campo en BD).
	 */
	public function logodelete()
	{
		$compId = (int) $this->getCompanyId();
		$imgType = (string) $this->input->post('imgType');
		$langCmp = $this->getLangJsonSection('cmp-admin-company');

		if (! in_array($imgType, ['image', 'small_image'], true) || $compId < 1) {
			$this->flashMessage(json_encode(
				[$langCmp['company_logo_invalid'] ?? '', 'error', '#DD6B55'],
				JSON_UNESCAPED_UNICODE
			));
			redirect(company_url('profile/logo'), 'refresh');
			return;
		}

		$company = $this->getObjectFromApi("company/{$compId}");
		$oldValue = '';
		if (is_object($company) && ! $this->hasError($company)) {
			$oldValue = trim((string) ($company->{$imgType} ?? ''));
		}

		$response = $this->put("company/{$compId}", [$imgType => null]);
		if ($this->hasError($response)) {
			// Algunos clientes/APIs no aceptan null: reintentar con string vacío.
			$response = $this->put("company/{$compId}", [$imgType => '']);
		}

		if ($this->hasError($response)) {
			$this->flashMessage(json_encode(
				[$langCmp['company_logo_delete_failed'] ?? $langCmp['company_image_upload_failed'] ?? '', 'error', '#DD6B55'],
				JSON_UNESCAPED_UNICODE
			));
		} else {
			if ($oldValue !== '' && (! function_exists('company_logo_is_api_image') || ! company_logo_is_api_image($oldValue))) {
				if (is_file('./uploads/company/' . $oldValue)) {
					@unlink('./uploads/company/' . $oldValue);
				}
			}
			$this->flashMessage(json_encode(
				[$langCmp['company_logo_deleted_success'] ?? '', 'success', '#A5DC86'],
				JSON_UNESCAPED_UNICODE
			));
		}

		redirect(company_url('profile/logo'), 'refresh');
	}

	public function contactdelete($cId = false)
	{
		$langPerfil = $this->profileLangSection('cmp-perfil');
		if ($cId === false || $cId === '') {
			redirect(company_url('profile'), 'refresh');
			return;
		}

		$compId = (int) $this->getCompanyId();
		$contact = $this->getObjectFromApi("companycontact/{$cId}");
		$contactCompanyId = is_object($contact) ? (int) ($contact->company_id ?? 0) : 0;

		if ($this->hasError($contact) || $contactCompanyId !== $compId) {
			$msg = '["' . addslashes($langPerfil['profile_update_error'] ?? 'Error') . '", "error", "#DD6B55"]';
		} else {
			$deleteResponse = $this->apiDelete("companycontact/{$cId}");
			if ($this->hasError($deleteResponse)) {
				$msg = '["' . addslashes($langPerfil['profile_update_error'] ?? 'Error') . '", "error", "#DD6B55"]';
			} else {
				$msg = '["' . addslashes($langPerfil['contact_deleted_success'] ?? '') . '", "success", "#A5DC86"]';
			}
		}

		$this->flashMessage($msg);
		redirect(company_url('profile'), 'refresh');
	}

	public function validImg($imgType): bool
	{
		$fileName = (string) ($_FILES['logo']['name'] ?? '');
		if ($fileName === '') {
			return false;
		}
		// Misma regla que imagen grande: cualquier imagen válida se acepta.
		// La proporción 1:1 / 5:4 queda como recomendación en la vista, no bloquea.
		return in_array((string) $imgType, ['image', 'small_image'], true);
	}

	public function validAspectRatio($width, $height): bool
	{
		$w = (float) $width;
		$h = (float) $height;
		if ($w <= 0 || $h <= 0) {
			return false;
		}
		$ratio = $w / $h;

		return abs($ratio - 1.0) < 0.02 || abs($ratio - 1.25) < 0.02;
	}

	/**
	 * Sección de idioma desde la API (langmdl/{módulo}), que lee los JSON de api/lang.
	 * Se usa la API en lugar de leer el archivo en disco porque el contenedor del MVC
	 * no comparte la ruta de api/lang.
	 *
	 * @return array<string, mixed>
	 */
	private function profileLangSection(string $section): array
	{
		$fromJson = $this->getLangJsonSection($section);
		if ($fromJson !== []) {
			return $fromJson;
		}

		$langId = (int) ($this->langId ?? $this->getUserData('language_id') ?? 1);
		$params = ['locale' => ($langId === 2) ? 'es' : 'en'];
		$compId = (int) $this->getCompanyId();
		if ($compId > 0) {
			$params['company_id'] = $compId;
		}

		$response = $this->getDataFromApi("langmdl/{$section}", $params);
		if (!is_array($response) || $this->hasError($response)) {
			return [];
		}

		return $response;
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
			if (isset($response['data']) && (is_array($response['data']) || is_object($response['data']))) {
				return array_values((array) $response['data']);
			}
			// Lista plana (p. ej. formatdate?all=1)
			if ($response !== [] && array_keys($response) === range(0, count($response) - 1)) {
				return $response;
			}
			if (isset($response['data'])) {
				return [];
			}

			return array_values($response);
		}
		if (is_object($response)) {
			if (isset($response->data)) {
				$data = $response->data;
				if (is_array($data) || is_object($data)) {
					return array_values((array) $data);
				}
			}
			// Colección decodificada como objeto con claves 0..n
			$asArray = get_object_vars($response);
			if ($asArray !== [] && array_keys($asArray) === range(0, count($asArray) - 1)) {
				return array_values($asArray);
			}

			return [$response];
		}

		return [];
	}

	/**
	 * @return array<int, mixed>
	 */
	private function fetchCountriesAlphabetic(): array
	{
		$countriesResponse = $this->getObjectFromApi('country', [
			'props' => ['id', 'name', 'sortname', 'phonecode', 'phonemask'],
			'all' => true,
		]);
		$countries = [];
		if (is_object($countriesResponse) && isset($countriesResponse->data) && is_array($countriesResponse->data)) {
			$countries = $countriesResponse->data;
		} elseif (is_array($countriesResponse) && isset($countriesResponse['data']) && is_array($countriesResponse['data'])) {
			$countries = $countriesResponse['data'];
		} elseif (is_array($countriesResponse)) {
			$countries = $countriesResponse;
		}

		usort($countries, function ($a, $b) {
			$na = is_object($a) ? (string) ($a->name ?? '') : (string) ($a['name'] ?? '');
			$nb = is_object($b) ? (string) ($b->name ?? '') : (string) ($b['name'] ?? '');
			return strcasecmp($na, $nb);
		});

		return $countries;
	}

	/**
	 * @return array<int, mixed>
	 */
	private function fetchCurrencyFormats(): array
	{
		$formats = $this->apiList($this->getObjectFromApi('currencyformat', ['all' => true]));
		usort($formats, function ($a, $b) {
			$oa = (int) (is_object($a) ? ($a->order ?? 0) : ($a['order'] ?? 0));
			$ob = (int) (is_object($b) ? ($b->order ?? 0) : ($b['order'] ?? 0));
			return $oa <=> $ob;
		});

		return $formats;
	}

	/**
	 * @param array<int, mixed> $formats
	 * @return array<int, mixed>
	 */
	private function filterCurrencyFormatsByFormatId(array $formats, int $formatId): array
	{
		return array_values(array_filter($formats, function ($row) use ($formatId) {
			$rowFormatId = (int) (is_object($row) ? ($row->format_id ?? 0) : ($row['format_id'] ?? 0));
			return $rowFormatId === $formatId;
		}));
	}

	/**
	 * @param object $companyData
	 * @return array<int, object>
	 */
	private function buildCurrenciesPather(int $compId, $companyData): array
	{
		$currency = $this->getObjectFromApi("company/{$compId}/currency");
		if ($this->hasError($currency) || !is_object($currency)) {
			return [];
		}

		$companyCurrencyId = (int) ($companyData->currency ?? 0);
		$child = (object) [
			'id' => (int) ($currency->id ?? $companyCurrencyId),
			'Symbol' => (string) ($currency->symbol ?? ''),
			'Code' => (string) ($currency->code ?? ''),
			'Name' => (string) ($currency->name ?? ''),
			'Contry' => '',
			'default' => ((int) ($currency->id ?? 0) === $companyCurrencyId) ? 'selected' : '',
		];

		$group = (object) [
			'GroupID' => (string) ($currency->code ?? ''),
			'currencies' => [$child],
		];

		return [$group];
	}

	private function getUniqueSlugUrl(string $slug, int $companyId): string
	{
		$candidate = $slug;
		while (true) {
			$existing = $this->getObjectFromApi('company/slug-' . rawurlencode($candidate));
			if ($this->hasError($existing) || empty($existing)) {
				return $candidate;
			}
			$existingId = is_object($existing) ? (int) ($existing->company_id ?? 0) : 0;
			if ($existingId === $companyId) {
				return $candidate;
			}
			$candidate .= '1';
		}
	}

	/**
	 * @param array<int, mixed> $cnames
	 * @param array<int, mixed> $cemails
	 * @param array<int, mixed> $cwebsites
	 * @param array<int, mixed> $cphones
	 * @param array<int, mixed> $ccphones
	 */
	private function syncCompanyContacts(int $compId, array $cnames, array $cemails, array $cwebsites, array $cphones, array $ccphones): void
	{
		$existing = $this->apiList($this->getDataFromApi("company/{$compId}/contacts"));
		foreach ($existing as $contact) {
			$cId = (int) (is_object($contact) ? ($contact->c_id ?? 0) : ($contact['c_id'] ?? 0));
			if ($cId > 0) {
				$this->apiDelete("companycontact/{$cId}");
			}
		}

		if (empty(array_filter($cnames))) {
			return;
		}

		$count = count($cnames);
		for ($i = 0; $i < $count; $i++) {
			$name = trim((string) ($cnames[$i] ?? ''));
			if ($name === '') {
				continue;
			}
			$this->post('companycontact', [
				'company_id' => $compId,
				'name' => $name,
				'email' => (string) ($cemails[$i] ?? ''),
				'website' => (string) ($cwebsites[$i] ?? ''),
				'phone' => (string) ($cphones[$i] ?? ''),
				'cell_phone' => (string) ($ccphones[$i] ?? ''),
			]);
		}
	}
}
