<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (! function_exists('admin_lang_code_to_id')) {
	function admin_lang_code_to_id(string $code): int
	{
		$map = ['en' => 1, 'es' => 2, 'pt' => 3, 'it' => 4, 'ru' => 5, 'de' => 6, 'zh' => 7];
		$code = strtolower(substr(trim($code), 0, 2));

		return $map[$code] ?? 1;
	}
}

if (! function_exists('admin_detect_language_id_from_request')) {
	function admin_detect_language_id_from_request(): int
	{
		$CI = &get_instance();
		$lang = '';
		if (is_object($CI) && isset($CI->input) && is_object($CI->input)) {
			$get = method_exists($CI->input, 'get') ? $CI->input->get('lang') : null;
			$post = method_exists($CI->input, 'post') ? $CI->input->post('lang') : null;
			if ($get !== null && $get !== '') {
				$lang = (string) $get;
			} elseif ($post !== null && $post !== '') {
				$lang = (string) $post;
			}
		}
		if ($lang !== '') {
			return admin_lang_code_to_id($lang);
		}
		if (is_object($CI) && isset($CI->session) && is_object($CI->session) && method_exists($CI->session, 'userdata')) {
			$sessId = (int) ($CI->session->userdata('language_id') ?? $CI->session->userdata('languageid') ?? 0);
			if ($sessId >= 1 && $sessId <= 7) {
				return $sessId;
			}
		}
		$browser = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? (string) $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';
		if ($browser !== '') {
			$first = trim(explode(',', $browser)[0] ?? '');
			$first = trim(explode(';', $first)[0] ?? '');
			$code = strtolower(substr($first, 0, 2));
			$supported = ['en' => 1, 'es' => 2, 'pt' => 3, 'it' => 4, 'ru' => 5, 'de' => 6, 'zh' => 7];
			if (isset($supported[$code])) {
				return $supported[$code];
			}
		}

		return 1;
	}
}

if (! function_exists('admin_sync_session_language_from_request')) {
	function admin_sync_session_language_from_request(): void
	{
		$CI = &get_instance();
		if (! is_object($CI) || ! isset($CI->session) || ! is_object($CI->session) || ! method_exists($CI->session, 'set_userdata')) {
			return;
		}
		$langId = admin_detect_language_id_from_request();
		if ($langId < 1 || $langId > 7) {
			$langId = 1;
		}
		$CI->session->set_userdata('language_id', $langId);
		$CI->session->set_userdata('languageid', $langId);
	}
}

if (! function_exists('admin_lang_merged_section')) {
	/**
	 * Sección de api/lang/*.json fusionando en-EN + locale de sesión.
	 *
	 * @return array<string, string>
	 */
	function admin_lang_merged_section(string $section): array
	{
		static $cache = [];
		$file = admin_shell_lang_filename();
		$cacheKey = $file . ':' . $section;
		if (isset($cache[$cacheKey])) {
			return $cache[$cacheKey];
		}
		$fromEn = admin_lang_json_flat_section('en-EN.json', $section);
		if ($file === 'en-EN.json') {
			return $cache[$cacheKey] = $fromEn;
		}
		$user = admin_lang_json_flat_section($file, $section);

		return $cache[$cacheKey] = array_merge($fromEn, $user);
	}
}

if (! function_exists('admin_lang_string')) {
	function admin_lang_string(string $section, string $key, string $fallback = ''): string
	{
		$merged = admin_lang_merged_section($section);
		if (isset($merged[$key]) && is_string($merged[$key]) && trim($merged[$key]) !== '') {
			return $merged[$key];
		}

		return $fallback;
	}
}

if (! function_exists('admin_shell_lang_filename')) {
	function admin_shell_lang_filename(): string
	{
		$CI = &get_instance();
		$langId = admin_detect_language_id_from_request();
		$map = [
			1 => 'en-EN.json',
			2 => 'es-ES.json',
			3 => 'pt-PT.json',
			4 => 'it-IT.json',
			5 => 'ru-RU.json',
			6 => 'de-DE.json',
			7 => 'zh-CN.json',
		];

		return $map[$langId] ?? 'en-EN.json';
	}
}

if (! function_exists('admin_shell_html_lang')) {
	/**
	 * Código BCP 47 para el atributo html lang del panel admin.
	 */
	function admin_shell_html_lang(): string
	{
		static $map = [
			1 => 'en',
			2 => 'es',
			3 => 'pt',
			4 => 'it',
			5 => 'ru',
			6 => 'de',
			7 => 'zh-CN',
		];
		$langId = admin_detect_language_id_from_request();

		return $map[$langId] ?? 'en';
	}
}

if (! function_exists('admin_capabilities_defaults')) {
	/**
	 * Valores por defecto si la API de capabilities no está disponible.
	 *
	 * @return array<string, mixed>
	 */
	function admin_capabilities_defaults(): array
	{
		return [
			'plan' => [
				'installed' => true,
				'primaryKey' => 'plan_id',
				'columns' => [],
				'hasLangSlots' => true,
				'hasLanguage1Id' => true,
				'hasLanguage2Id' => true,
				'hasSingleLanguageId' => false,
				'hasStatus' => true,
				'hasLifecycleStatus' => false,
				'hasStripeFields' => true,
				'columnMaxLengths' => ['title' => 150, 'description' => 80],
			],
			'subscriptions' => [
				'installed' => true,
				'primaryKey' => 'subscription_id',
				'columns' => [],
				'hasPlanId' => true,
				'hasStatus' => true,
				'hasStripeSubscriptionId' => true,
				'hasNotes' => true,
			],
			'invoices' => [
				'installed' => true,
				'primaryKey' => 'invoice_id',
				'columns' => [],
				'hasEntidadId' => true,
			],
			'issuing_entity' => [
				'installed' => true,
				'primaryKey' => 'entity_id',
				'columns' => [],
			],
			'companies' => [
				'installed' => true,
				'primaryKey' => 'company_id',
				'columns' => [],
				'hasStripeId' => true,
				'hasUseWhatsapp' => true,
				'hasWhatsappInstance' => false,
				'hasWhatsappInstanceName' => true,
				'hasWhatsappApikey' => true,
				'hasCompanyCategoryId' => true,
				'hasCategoryId' => false,
				'hasMaxClients' => true,
				'hasMaxOrders' => true,
				'hasMaxVendors' => true,
				'addressFields' => ['full_address', 'address', 'country', 'state', 'city', 'zip_code', 'zone'],
			],
		];
	}
}

if (! function_exists('admin_capabilities')) {
	/**
	 * Capacidades de esquema admin vía API (cache por request).
	 *
	 * @return array<string, mixed>
	 */
	function admin_capabilities(): array
	{
		static $cache = null;
		if (is_array($cache)) {
			return $cache;
		}

		$CI = &get_instance();
		if (! is_object($CI) || ! method_exists($CI, 'getObjectFromApi')) {
			return $cache = admin_capabilities_defaults();
		}

		$resp = $CI->getObjectFromApi('admin/capabilities');
		if (is_object($resp) && isset($resp->data) && (is_array($resp->data) || is_object($resp->data))) {
			$data = is_array($resp->data) ? $resp->data : (array) $resp->data;

			return $cache = array_merge(admin_capabilities_defaults(), $data);
		}
		if (is_array($resp) && isset($resp['data']) && is_array($resp['data'])) {
			return $cache = array_merge(admin_capabilities_defaults(), $resp['data']);
		}

		return $cache = admin_capabilities_defaults();
	}
}

if (! function_exists('admin_capability_section')) {
	/**
	 * @return array<string, mixed>
	 */
	function admin_capability_section(string $section): array
	{
		$all = admin_capabilities();
		$sec = $all[$section] ?? null;
		if (is_array($sec)) {
			return $sec;
		}
		if (is_object($sec)) {
			return (array) $sec;
		}
		$defaults = admin_capabilities_defaults();

		return is_array($defaults[$section] ?? null) ? $defaults[$section] : [];
	}
}

if (! function_exists('admin_module_installed')) {
	function admin_module_installed(string $module): bool
	{
		$sec = admin_capability_section($module);

		return (bool) ($sec['installed'] ?? false);
	}
}

if (! function_exists('admin_plan_primary_key_column')) {
	/**
	 * PK de la tabla `plan`: desde API admin/capabilities (antes introspección SQL local).
	 */
	function admin_plan_primary_key_column(): string
	{
		static $cache = null;
		if ($cache !== null) {
			return $cache;
		}
		$plan = admin_capability_section('plan');
		$pk = isset($plan['primaryKey']) ? trim((string) $plan['primaryKey']) : '';
		if ($pk !== '' && preg_match('/^[A-Za-z0-9_]+$/', $pk)) {
			return $cache = $pk;
		}

		return $cache = 'plan_id';
	}
}

if (! function_exists('admin_subscriptions_primary_key_column')) {
	function admin_subscriptions_primary_key_column(): string
	{
		static $cache = null;
		if ($cache !== null) {
			return $cache;
		}
		$sec = admin_capability_section('subscriptions');
		$pk = isset($sec['primaryKey']) ? trim((string) $sec['primaryKey']) : '';
		if ($pk !== '' && preg_match('/^[A-Za-z0-9_]+$/', $pk)) {
			return $cache = $pk;
		}

		return $cache = 'subscription_id';
	}
}

if (! function_exists('admin_plan_row_id')) {
	/**
	 * @param array<string, mixed>|object $row
	 */
	function admin_plan_row_id($row): int
	{
		$pk = admin_plan_primary_key_column();
		if (is_array($row)) {
			if (isset($row[$pk])) {
				return (int) $row[$pk];
			}

			return (int) ($row['plan_id'] ?? $row['subscription_id'] ?? 0);
		}
		if (is_object($row)) {
			if (isset($row->{$pk})) {
				return (int) $row->{$pk};
			}

			return (int) ($row->plan_id ?? $row->subscription_id ?? 0);
		}

		return 0;
	}
}

if (! function_exists('admin_lang_json_resolved_path')) {
	/**
	 * Primera ruta legible a api/lang/{basename} (mismo criterio que admin Login).
	 */
	function admin_lang_json_resolved_path(string $jsonBasename): string
	{
		$safe = basename($jsonBasename);
		if ($safe === '' || $safe !== $jsonBasename) {
			return '';
		}
		$candidates = [];
		// application/ → subir dos niveles hasta la raíz del repo (mvc/application → repo)
		if (defined('APPPATH')) {
			$appRoot = rtrim((string) APPPATH, '/\\');
			$candidates[] = $appRoot . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $safe;
			$rpApp = @realpath($appRoot . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $safe);
			if (is_string($rpApp) && $rpApp !== '') {
				$candidates[] = $rpApp;
			}
		}
		// helpers → application → mvc → raíz del repo (fullvendor2025/api/lang)
		$fromHelpers = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $safe;
		$candidates[] = $fromHelpers;
		$rpHelpers = @realpath($fromHelpers);
		if (is_string($rpHelpers) && $rpHelpers !== '') {
			$candidates[] = $rpHelpers;
		}
		$candidates[] = dirname(APPPATH, 2) . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $safe;
		$repoRoot = @realpath(APPPATH . '..' . DIRECTORY_SEPARATOR . '..');
		if (is_string($repoRoot) && $repoRoot !== '') {
			$candidates[] = $repoRoot . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $safe;
		}
		if (defined('FCPATH')) {
			$fcp = rtrim((string) FCPATH, '/\\');
			if ($fcp !== '') {
				$candidates[] = dirname($fcp) . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $safe;
			}
		}
		$candidates = array_values(array_unique(array_filter($candidates)));

		foreach ($candidates as $p) {
			if ($p !== '' && is_readable($p)) {
				return $p;
			}
		}

		return '';
	}
}

if (! function_exists('admin_lang_json_flat_section')) {
	/**
	 * @return array<string, string>
	 */
	function admin_lang_json_flat_section(string $jsonBasename, string $section): array
	{
		$path = admin_lang_json_resolved_path($jsonBasename);
		if ($path === '' || ! is_readable($path)) {
			return [];
		}
		$raw = @file_get_contents($path);
		if ($raw === false || $raw === '') {
			return [];
		}
		$data = json_decode($raw, true);
		if (! is_array($data) || ! isset($data[$section]) || ! is_array($data[$section])) {
			return [];
		}
		$out = [];
		foreach ($data[$section] as $k => $v) {
			if (is_string($k) && (is_string($v) || is_numeric($v))) {
				$out[$k] = is_string($v) ? $v : (string) $v;
			}
		}

		return $out;
	}
}

if (! function_exists('admin_plan_emergency_labels_en')) {
	/**
	 * Último recurso si no se puede leer api/lang (mismas claves que cmp-admin-plan en en-EN.json).
	 *
	 * @return array<string, string>
	 */
	function admin_plan_emergency_labels_en(): array
	{
		return [
			'table_missing' => 'The plan table is not installed. Run database migrations.',
			'list_title' => 'Plans',
			'list_add_new' => 'Add plan',
			'col_num' => '#',
			'col_title' => 'Title',
			'col_description' => 'Description',
			'col_language' => 'Language',
			'col_billing_period' => 'Billing period',
			'col_status' => 'Status',
			'col_max_clients' => 'Client quota',
			'col_max_orders' => 'Order quota',
			'col_max_users' => 'User quota',
			'col_price' => 'Price',
			'col_created' => 'Created',
			'col_updated' => 'Updated',
			'col_actions' => 'Actions',
			'billing_monthly' => 'Monthly',
			'billing_annual' => 'Annual',
			'status_active' => 'Active',
			'status_expired' => 'Expired',
			'status_cancelled' => 'Cancelled',
			'plan_select_status_enabled' => 'Enabled',
			'plan_select_status_disabled' => 'Disabled',
			'form_add_title' => 'New plan',
			'form_edit_title' => 'Edit plan',
			'label_language' => 'Language',
			'label_language_1' => 'Language 1',
			'label_language_2' => 'Language 2',
			'plan_language_checkbox_assign' => 'Assign this language to the plan',
			'plan_language_list_none' => '—',
			'label_title' => 'Title',
			'label_description' => 'Description',
			'label_billing_period' => 'Billing period',
			'label_status' => 'Status',
			'label_max_clients' => 'Client quota',
			'label_max_orders' => 'Order quota',
			'label_max_users' => 'User quota',
			'label_price' => 'Price',
			'placeholder_price' => 'e.g. 99.00',
			'placeholder_title' => 'e.g. Professional plan',
			'placeholder_description' => 'Optional summary for administrators',
			'select_language' => 'Select language',
			'btn_submit' => 'Save',
			'btn_cancel' => 'Cancel',
			'tooltip_edit' => 'Edit',
			'tooltip_delete' => 'Delete',
			'delete_confirm_title' => 'Delete this plan?',
			'delete_confirm_text' => 'You will not be able to undo this action.',
			'delete_confirm_yes' => 'Yes, delete',
			'delete_confirm_no' => 'No',
			'msg_created' => 'Plan created.',
			'msg_updated' => 'Plan updated.',
			'msg_deleted' => 'Plan deleted.',
			'msg_validation_error' => 'Please check the form and try again.',
			'msg_plan_language_required' => 'Select at least one language (check the box and choose a language).',
			'msg_plan_duplicate_language' => 'The two languages must be different.',
			'msg_not_found' => 'Plan not found.',
			'msg_plan_delete_blocked_active_subscriptions' => 'You cannot delete a plan that has an active subscription linked to it.',
			'datatable_length_menu' => 'Show _MENU_ entries',
			'datatable_search_placeholder' => 'Search',
			'datatable_all' => 'All',
			'datatable_zero_records' => 'No matching records found.',
			'datatable_info' => 'Showing _START_ to _END_ of _TOTAL_ entries',
			'datatable_info_empty' => 'Showing 0 to 0 of 0 entries',
			'datatable_info_filtered' => '(filtered from _MAX_ total entries)',
			'datatable_previous' => 'Previous',
			'datatable_next' => 'Next',
			'plan_status_switch_aria' => 'Enable or disable this plan',
			'plan_status_enabled_success' => 'Plan enabled.',
			'plan_status_disabled_success' => 'Plan disabled.',
			'plan_status_update_error' => 'The status could not be updated. Please try again.',
			'plan_list_empty' => 'No plans yet. Add one to get started.',
			'plan_card_currency_prefix' => '$',
			'plan_card_billing_suffix_monthly' => 'per month',
			'plan_card_billing_suffix_annual' => 'per year',
			'plan_card_cta_select' => 'SELECT',
			'plan_card_admin_tools_aria' => 'Administrative actions for this plan',
			'plan_card_disabled_badge' => 'DISABLED',
			'btn_stripe_sync_catalog' => 'Sync Stripe catalog',
			'confirm_stripe_sync_catalog_title' => 'Sync plan with Stripe?',
			'confirm_stripe_sync_catalog_text' => 'Product/Price will be created or updated in Stripe.',
			'msg_stripe_plan_synced' => 'Plan synced with Stripe.',
			'section_general' => 'General',
			'section_names' => 'Commercial names',
			'section_languages' => 'Languages',
			'section_limits' => 'Limits',
			'section_payment' => 'Payment',
			'section_stripe' => 'Stripe',
			'label_plan_name_spa' => 'Commercial name (ES)',
			'label_plan_name_eng' => 'Commercial name (EN)',
			'label_line1_spa' => 'Tagline (ES)',
			'label_line1_eng' => 'Tagline (EN)',
			'label_payment' => 'Payment',
			'label_payment_text' => 'Payment text',
			'label_payment_periodicity' => 'Payment periodicity',
			'label_days' => 'Duration (days)',
			'label_max_customers' => 'Max customers (legacy)',
			'label_never_expire' => 'Never expires',
			'label_ecommerce_permission' => 'eCommerce permission',
			'label_stripe_id' => 'Stripe ID (Price)',
			'label_stripe_name' => 'Stripe name (Product)',
			'label_stripe_status' => 'Stripe status',
			'label_stripe_doc' => 'Stripe document ID',
			'label_stripe_url' => 'Stripe URL',
			'label_stripe_pdf' => 'Stripe PDF URL',
			'label_open_link' => 'Open link',
			'label_open_pdf' => 'Open PDF',
			'label_yes' => 'Yes',
			'label_no' => 'No',
			'btn_view_more' => 'View more',
			'modal_title_plan_details' => 'Plan details',
			'modal_close' => 'Close',
			'label_created_at' => 'Created at',
			'label_updated_at' => 'Updated at',
			'label_plan_id' => 'Internal ID',
		];
	}
}

if (! function_exists('admin_plan_fill_missing_text_keys')) {
	/**
	 * Rellena claves vacías o ausentes desde emergencia EN (formulario plan add/edit).
	 *
	 * @param array<string, string> $texts
	 *
	 * @return array<string, string>
	 */
	function admin_plan_fill_missing_text_keys(array $texts): array
	{
		$fallback = admin_plan_emergency_labels_en();
		foreach ($fallback as $k => $v) {
			if (! is_string($k) || $k === '') {
				continue;
			}
			if (! isset($texts[$k]) || trim((string) $texts[$k]) === '') {
				$texts[$k] = $v;
			}
		}

		return $texts;
	}
}

if (! function_exists('admin_lang_cmp_admin_plan_texts')) {
	/**
	 * cmp-admin-plan: en-EN como base + locale de sesión (misma estructura de claves).
	 * Siempre se fusiona con etiquetas de emergencia para que ninguna clave quede vacía.
	 *
	 * @return array<string, string>
	 */
	function admin_lang_cmp_admin_plan_texts(): array
	{
		$emergency = admin_plan_emergency_labels_en();
		$fromEn = admin_lang_json_flat_section('en-EN.json', 'cmp-admin-plan');
		$defaults = array_merge($emergency, $fromEn);
		$file = admin_shell_lang_filename();
		if ($file === 'en-EN.json') {
			return $defaults;
		}
		$user = admin_lang_json_flat_section($file, 'cmp-admin-plan');
		$merged = array_merge($defaults, $user);
		$stripeEn = admin_lang_json_flat_section('en-EN.json', 'cmp-stripe-plan');
		$stripeUser = admin_lang_json_flat_section($file, 'cmp-stripe-plan');

		return admin_plan_fill_missing_text_keys(array_merge($merged, $stripeEn, $stripeUser));
	}
}

if (! function_exists('admin_plan_page_texts')) {
	/**
	 * Textos de pantallas Plan: JSON + relleno de huecos + emergencia EN si hace falta.
	 *
	 * @param array<string, string>|null $pageTextsFromController opcional; solo se aplican valores no vacíos
	 *
	 * @return array<string, string>
	 */
	function admin_plan_page_texts(?array $pageTextsFromController = null): array
	{
		$base = admin_lang_cmp_admin_plan_texts();
		if (! is_array($pageTextsFromController) || $pageTextsFromController === []) {
			return $base;
		}
		foreach ($pageTextsFromController as $k => $v) {
			if (! is_string($k) || $k === '') {
				continue;
			}
			if (is_string($v) && trim($v) !== '') {
				$base[$k] = $v;
			} elseif (is_numeric($v)) {
				$base[$k] = (string) $v;
			}
		}

		return admin_plan_fill_missing_text_keys($base);
	}
}

if (! function_exists('admin_subscriptions_emergency_labels_en')) {
	/**
	 * Último recurso: mismas claves que cmp-admin-subscriptions en en-EN.json.
	 *
	 * @return array<string, string>
	 */
	function admin_subscriptions_emergency_labels_en(): array
	{
		return [
			'table_missing' => 'The subscriptions table is not installed. Run database migrations.',
			'table_plan_missing' => 'The plan table is not installed. Create plans before subscriptions.',
			'list_title' => 'Subscriptions',
			'list_add_new' => 'Add subscription',
			'col_num' => '#',
			'col_company' => 'Company',
			'col_plan' => 'Plan',
			'col_starts' => 'Start date',
			'col_ends' => 'End date',
			'col_status' => 'Status',
			'col_actions' => 'Actions',
			'form_add_title' => 'New subscription',
			'form_edit_title' => 'Edit subscription',
			'label_company' => 'Company',
			'label_plan' => 'Plan',
			'label_status' => 'Status',
			'label_starts_at' => 'Start date',
			'label_ends_at' => 'End date',
			'label_notes' => 'Notes',
			'placeholder_notes' => 'Optional notes for administrators',
			'select_company' => 'Select company',
			'select_plan' => 'Select plan',
			'status_option_enabled' => 'Enabled',
			'status_option_disabled' => 'Disabled',
			'btn_submit' => 'Save',
			'btn_cancel' => 'Cancel',
			'tooltip_edit' => 'Edit',
			'tooltip_delete' => 'Delete',
			'delete_confirm_title' => 'Delete this subscription?',
			'delete_confirm_text' => 'You will not be able to undo this action.',
			'delete_confirm_yes' => 'Yes, delete',
			'delete_confirm_no' => 'No',
			'msg_created' => 'Subscription created.',
			'msg_updated' => 'Subscription updated.',
			'msg_deleted' => 'Subscription deleted.',
			'msg_validation_error' => 'Please check the form and try again.',
			'msg_not_found' => 'Subscription not found.',
			'msg_company_invalid' => 'Select a valid company.',
			'msg_plan_invalid' => 'Select a valid plan.',
			'msg_dates_invalid' => 'End date must be on or after the start date.',
			'status_switch_aria' => 'Enable or disable this subscription',
			'toast_status_enabled' => 'Subscription enabled.',
			'toast_status_disabled' => 'Subscription disabled.',
			'status_update_error' => 'The status could not be updated. Please try again.',
			'msg_delete_blocked_active_invoice' => 'This subscription cannot be deleted because it has an active invoice.',
			'label_yes' => 'Yes',
			'label_no' => 'No',
			'col_stripe' => 'Stripe',
			'section_stripe_actions' => 'Stripe actions',
			'section_stripe_details' => 'Stripe data',
			'stripe_readonly_notice' => 'These fields are read-only and are synchronized from Stripe.',
			'stripe_readonly_notice_add' => 'These fields will be filled automatically after creating the subscription and syncing it with Stripe.',
			'btn_stripe_create' => 'Create in Stripe',
			'btn_stripe_sync' => 'Sync from Stripe',
			'btn_stripe_cancel' => 'Cancel in Stripe',
			'btn_stripe_sync_all' => 'Sync all with Stripe',
			'tooltip_stripe_create' => 'Create subscription in Stripe',
			'tooltip_stripe_sync' => 'Refresh from Stripe',
			'tooltip_stripe_sync_requires_id' => 'Create the subscription in Stripe first.',
			'tooltip_stripe_cancel_requires_id' => 'Create the subscription in Stripe before cancelling.',
			'confirm_stripe_create_title' => 'Create this subscription in Stripe?',
			'confirm_stripe_create_text' => 'A recurring Stripe subscription will be created for this record using the linked customer and plan.',
			'confirm_stripe_sync_title' => 'Sync from Stripe?',
			'confirm_stripe_sync_text' => 'The local Stripe data (status, period, trial, cancellation, etc.) will be refreshed with the current values from Stripe.',
			'confirm_stripe_cancel_title' => 'Cancel the subscription in Stripe?',
			'confirm_stripe_cancel_text' => 'The subscription will be cancelled in Stripe at the end of the current billing period. No further charges will happen after that.',
			'confirm_stripe_sync_all_title' => 'Sync all subscriptions with Stripe?',
			'confirm_stripe_sync_all_text' => 'All subscriptions with a Stripe ID will be refreshed. This may take a while.',
			'confirm_stripe_yes_create' => 'Yes, create in Stripe',
			'confirm_stripe_yes_sync' => 'Yes, sync now',
			'confirm_stripe_yes_cancel' => 'Yes, cancel in Stripe',
			'confirm_stripe_yes_sync_all' => 'Yes, sync all',
			'confirm_stripe_no' => 'No',
			'msg_stripe_bulk_result' => 'Stripe: %1$d updated of %2$d processed.',
			'label_stripe_subscription_id' => 'Stripe subscription ID',
			'label_stripe_status' => 'Stripe status',
			'label_stripe_price_id' => 'Stripe price ID',
			'label_stripe_currency' => 'Currency',
			'label_stripe_collection_method' => 'Collection method',
			'label_stripe_cancel_at_period_end' => 'Cancel at period end',
			'label_stripe_current_period_start' => 'Current period start',
			'label_stripe_current_period_end' => 'Current period end',
			'label_stripe_trial_start' => 'Trial start',
			'label_stripe_trial_end' => 'Trial end',
			'label_stripe_cancel_at' => 'Cancel at',
			'label_stripe_canceled_at' => 'Canceled at',
			'label_stripe_ended_at' => 'Ended at',
			'label_stripe_latest_invoice_id' => 'Latest invoice ID (Stripe)',
			'label_stripe_default_payment_method_id' => 'Default payment method ID',
			'label_stripe_created_at' => 'Created in Stripe',
		];
	}
}

if (! function_exists('admin_lang_cmp_admin_subscriptions_texts')) {
	/**
	 * cmp-admin-subscriptions: en-EN como base + locale de sesión.
	 *
	 * @return array<string, string>
	 */
	function admin_lang_cmp_admin_subscriptions_texts(): array
	{
		$emergency = admin_subscriptions_emergency_labels_en();
		$fromEn = admin_lang_json_flat_section('en-EN.json', 'cmp-admin-subscriptions');
		$defaults = array_merge($emergency, $fromEn);
		$file = admin_shell_lang_filename();
		if ($file === 'en-EN.json') {
			return $defaults;
		}
		$user = admin_lang_json_flat_section($file, 'cmp-admin-subscriptions');
		$merged = array_merge($defaults, $user);
		$stripeEn = admin_lang_json_flat_section('en-EN.json', 'cmp-stripe-subscription');
		$stripeUser = admin_lang_json_flat_section($file, 'cmp-stripe-subscription');

		return array_merge($merged, $stripeEn, $stripeUser);
	}
}

if (! function_exists('admin_subscriptions_page_texts')) {
	/**
	 * Textos pantalla Suscripciones (JSON + emergencia EN).
	 *
	 * @param array<string, string>|null $pageTextsFromController
	 *
	 * @return array<string, string>
	 */
	function admin_subscriptions_page_texts(?array $pageTextsFromController = null): array
	{
		$base = admin_lang_cmp_admin_subscriptions_texts();
		if (! is_array($pageTextsFromController) || $pageTextsFromController === []) {
			return $base;
		}
		foreach ($pageTextsFromController as $k => $v) {
			if (! is_string($k) || $k === '') {
				continue;
			}
			if (is_string($v) && trim($v) !== '') {
				$base[$k] = $v;
			} elseif (is_numeric($v)) {
				$base[$k] = (string) $v;
			}
		}

		return $base;
	}
}

if (! function_exists('admin_invoices_emergency_labels_en')) {
	/**
	 * Último recurso: mismas claves que cmp-admin-invoices en en-EN.json.
	 *
	 * @return array<string, string>
	 */
	function admin_invoices_emergency_labels_en(): array
	{
		return [
			'table_missing' => 'The invoices table is not installed. Run database migrations.',
			'list_title' => 'Invoices',
			'list_add_new' => 'Add invoice',
			'col_num' => '#',
			'col_doc_number' => 'Document no.',
			'col_company' => 'Company',
			'col_subscription' => 'Subscription / plan',
			'col_date' => 'Date',
			'col_paid' => 'Paid flag',
			'col_invoice_paid' => 'Amount paid',
			'col_debt' => 'Debt',
			'col_actions' => 'Actions',
			'form_add_title' => 'New invoice',
			'form_edit_title' => 'Edit invoice',
			'form_view_title' => 'View invoice',
			'btn_back_to_list' => 'Back to list',
			'label_doc_number' => 'Document number',
			'label_company' => 'Company',
			'label_subscription' => 'Subscription or plan ID',
			'label_date' => 'Invoice date',
			'label_date_expired' => 'Expiry date',
			'label_paid' => 'Paid (flag)',
			'label_days' => 'Days',
			'label_issuing_entity' => 'Issuing entity',
			'label_address_entity' => 'Address',
			'label_country_entity' => 'Country',
			'label_state_entity' => 'State / region',
			'label_city_entity' => 'City',
			'label_concept' => 'Description',
			'label_invoice_paid' => 'Amount paid',
			'label_debt' => 'Debt',
			'label_stripe_id' => 'Stripe ID',
			'label_stripe_doc' => 'Stripe document',
			'label_stripe_url' => 'Stripe URL',
			'label_stripe_pdf' => 'Stripe PDF URL',
			'label_stripe_status' => 'Stripe status',
			'select_company' => 'Select company',
			'select_subscription' => 'Select subscription or plan',
			'option_plan_prefix' => 'Plan',
			'paid_option_empty' => 'Not set',
			'paid_option_no' => 'No (0)',
			'paid_option_yes' => 'Yes (1)',
			'placeholder_doc_number' => 'Optional document number',
			'paid_display_yes' => 'Yes',
			'paid_display_no' => 'No',
			'paid_display_dash' => '—',
			'btn_submit' => 'Save',
			'btn_cancel' => 'Cancel',
			'tooltip_edit' => 'Edit',
			'tooltip_view' => 'View',
			'tooltip_delete' => 'Delete',
			'delete_confirm_title' => 'Delete this invoice?',
			'delete_confirm_text' => 'You will not be able to undo this action.',
			'delete_confirm_yes' => 'Yes, delete',
			'delete_confirm_no' => 'No',
			'msg_created' => 'Invoice created.',
			'msg_updated' => 'Invoice updated.',
			'msg_deleted' => 'Invoice deleted.',
			'msg_validation_error' => 'Please check the form and try again.',
			'msg_not_found' => 'Invoice not found.',
			'msg_company_invalid' => 'Select a valid company.',
			'msg_subscription_invalid' => 'Select a valid subscription or plan.',
			'msg_subscription_company_mismatch' => 'The subscription does not belong to the selected company.',
			'msg_datetime_invalid' => 'Enter a valid date and time.',
			'section_stripe_actions' => 'Stripe',
			'btn_stripe_create' => 'Create in Stripe',
			'btn_stripe_sync' => 'Sync from Stripe',
			'btn_stripe_sync_all' => 'Sync all with Stripe',
			'btn_stripe_create_missing' => 'Create missing in Stripe',
			'btn_open_stripe_url' => 'Open Stripe invoice',
			'tooltip_stripe_create' => 'Create invoice in Stripe',
			'tooltip_stripe_sync' => 'Update from Stripe',
			'confirm_stripe_create_title' => 'Create in Stripe?',
			'confirm_stripe_create_text' => 'A Stripe invoice will be created for this record.',
			'confirm_stripe_sync_title' => 'Sync from Stripe?',
			'confirm_stripe_sync_text' => 'Local Stripe fields will be updated from the API.',
			'confirm_stripe_sync_all_title' => 'Sync all invoices?',
			'confirm_stripe_sync_all_text' => 'All invoices with a Stripe ID will be updated. This may take a while.',
			'confirm_stripe_create_missing_title' => 'Create missing Stripe invoices?',
			'confirm_stripe_create_missing_text' => 'Invoices without a Stripe ID will be sent to Stripe.',
			'msg_stripe_bulk_result' => 'Stripe: %1$d updated of %2$d processed.',
			'msg_stripe_missing_result' => 'Stripe: %1$d created of %2$d attempted.',
		];
	}
}

if (! function_exists('admin_lang_cmp_admin_invoices_texts')) {
	/**
	 * cmp-admin-invoices: en-EN como base + locale de sesión.
	 *
	 * @return array<string, string>
	 */
	function admin_lang_cmp_admin_invoices_texts(): array
	{
		$emergency = admin_invoices_emergency_labels_en();
		$fromEn = admin_lang_json_flat_section('en-EN.json', 'cmp-admin-invoices');
		$defaults = array_merge($emergency, $fromEn);
		$file = admin_shell_lang_filename();
		if ($file === 'en-EN.json') {
			return $defaults;
		}
		$user = admin_lang_json_flat_section($file, 'cmp-admin-invoices');
		$merged = array_merge($defaults, $user);
		$stripeEn = admin_lang_json_flat_section('en-EN.json', 'cmp-stripe-invoice');
		$stripeUser = admin_lang_json_flat_section($file, 'cmp-stripe-invoice');

		return array_merge($merged, $stripeEn, $stripeUser);
	}
}

if (! function_exists('admin_invoices_page_texts')) {
	/**
	 * Textos pantalla Facturas (JSON + emergencia EN).
	 *
	 * @param array<string, string>|null $pageTextsFromController
	 *
	 * @return array<string, string>
	 */
	function admin_invoices_page_texts(?array $pageTextsFromController = null): array
	{
		$base = admin_lang_cmp_admin_invoices_texts();
		if (! is_array($pageTextsFromController) || $pageTextsFromController === []) {
			return $base;
		}
		foreach ($pageTextsFromController as $k => $v) {
			if (! is_string($k) || $k === '') {
				continue;
			}
			if (is_string($v) && trim($v) !== '') {
				$base[$k] = $v;
			} elseif (is_numeric($v)) {
				$base[$k] = (string) $v;
			}
		}

		return $base;
	}
}

if (! function_exists('admin_cms_emergency_labels_en')) {
	/**
	 * Último recurso: mismas claves que cmp-admin-cms en en-EN.json.
	 *
	 * @return array<string, string>
	 */
	function admin_cms_emergency_labels_en(): array
	{
		return [
			'list_title' => 'CMS pages',
			'list_add_new' => 'Add page',
			'col_num' => '#',
			'col_page_title' => 'Page title',
			'col_page_text' => 'Summary',
			'col_link' => 'Link',
			'col_modified' => 'Last updated',
			'col_actions' => 'Actions',
			'form_add_title' => 'New CMS page',
			'form_edit_title' => 'Edit CMS page',
			'form_view_title' => 'View CMS page',
			'label_page_title' => 'Page title',
			'label_page_text' => 'Summary',
			'label_link' => 'Link (optional)',
			'label_content' => 'Content',
			'placeholder_page_title' => 'e.g. About us',
			'placeholder_page_text' => 'Short description or slug label',
			'placeholder_link' => 'e.g. /about-us',
			'placeholder_content' => 'Main page body',
			'btn_submit' => 'Save',
			'btn_cancel' => 'Cancel',
			'view_back' => 'Back to list',
			'view_edit' => 'Edit',
			'content_heading' => 'Content preview',
			'tooltip_view' => 'View',
			'tooltip_edit' => 'Edit',
			'tooltip_delete' => 'Delete',
			'delete_confirm_title' => 'Delete this page?',
			'delete_confirm_text' => 'This cannot be undone from here.',
			'delete_confirm_yes' => 'Yes, delete',
			'delete_confirm_no' => 'Cancel',
			'msg_created' => 'Page created.',
			'msg_updated' => 'Page updated.',
			'msg_deleted' => 'Page deleted.',
			'msg_validation_error' => 'Please check the form and try again.',
			'msg_not_found' => 'Page not found.',
			'datatable_length_menu' => 'Show _MENU_ entries',
			'datatable_search_placeholder' => 'Search',
			'datatable_all' => 'All',
			'datatable_zero_records' => 'No matching records found.',
			'datatable_info' => 'Showing _START_ to _END_ of _TOTAL_ entries',
			'datatable_info_empty' => 'Showing 0 to 0 of 0 entries',
			'datatable_info_filtered' => '(filtered from _MAX_ total entries)',
			'datatable_previous' => 'Previous',
			'datatable_next' => 'Next',
		];
	}
}

if (! function_exists('admin_lang_cmp_admin_cms_texts')) {
	/**
	 * cmp-admin-cms: en-EN como base + locale de sesión.
	 *
	 * @return array<string, string>
	 */
	function admin_lang_cmp_admin_cms_texts(): array
	{
		$emergency = admin_cms_emergency_labels_en();
		$fromEn = admin_lang_json_flat_section('en-EN.json', 'cmp-admin-cms');
		$defaults = array_merge($emergency, $fromEn);
		$file = admin_shell_lang_filename();
		if ($file === 'en-EN.json') {
			return $defaults;
		}
		$user = admin_lang_json_flat_section($file, 'cmp-admin-cms');

		return array_merge($defaults, $user);
	}
}

if (! function_exists('admin_stripe_settings_emergency_labels_en')) {
	/**
	 * Etiquetas EN de la pantalla Stripe settings (singleton form).
	 *
	 * @return array<string, string>
	 */
	function admin_stripe_settings_emergency_labels_en(): array
	{
		return [
			'page_title' => 'Stripe settings',
			'section_issuing_entity' => 'Issuing entity',
			'section_stripe_secret' => 'Stripe secret key',
			'section_stripe_publish' => 'Stripe publishable key',
			'label_entidad_emisora' => 'Entity name',
			'label_entidad_direccion' => 'Address',
			'label_entidad_pais' => 'Country',
			'label_entidad_estado' => 'State / region',
			'label_entidad_ciudad' => 'City',
			'label_stripe_secret' => 'Secret key (sk_…)',
			'label_stripe_publish' => 'Publishable key (pk_…)',
			'help_stripe_secret' => 'Stored in the stripe_secret table; used by the API to call Stripe.',
			'help_stripe_publish' => 'Stored in the stripe_publish table; legacy column used by the resolver fallback.',
			'btn_submit' => 'Save changes',
			'btn_cancel' => 'Cancel',
			'msg_saved' => 'Stripe settings saved.',
			'msg_validation_entidad' => 'Issuing entity is invalid (name is required and must not exceed 100 chars).',
			'msg_missing_entidad_table' => 'Issuing entity table is missing. Create the issuing_entity table first.',
			'msg_save_entidad_failed' => 'Issuing entity could not be saved.',
			'msg_save_secret_failed' => 'Stripe secret could not be saved.',
			'msg_save_publish_failed' => 'Stripe publishable key could not be saved.',
		];
	}
}

if (! function_exists('admin_lang_cmp_admin_stripe_settings_texts')) {
	function admin_lang_cmp_admin_stripe_settings_texts(): array
	{
		$emergency = admin_stripe_settings_emergency_labels_en();
		$fromEn = admin_lang_json_flat_section('en-EN.json', 'cmp-admin-stripe-settings');
		$defaults = array_merge($emergency, $fromEn);
		$file = admin_shell_lang_filename();
		if ($file === 'en-EN.json') {
			return $defaults;
		}
		$user = admin_lang_json_flat_section($file, 'cmp-admin-stripe-settings');

		return array_merge($defaults, $user);
	}
}

if (! function_exists('admin_stripe_settings_page_texts')) {
	/**
	 * @param array<string, string>|null $pageTextsFromController
	 *
	 * @return array<string, string>
	 */
	function admin_stripe_settings_page_texts(?array $pageTextsFromController = null): array
	{
		$base = admin_lang_cmp_admin_stripe_settings_texts();
		if (! is_array($pageTextsFromController) || $pageTextsFromController === []) {
			return $base;
		}
		foreach ($pageTextsFromController as $k => $v) {
			if (! is_string($k) || $k === '') {
				continue;
			}
			if (is_string($v) && trim($v) !== '') {
				$base[$k] = $v;
			} elseif (is_numeric($v)) {
				$base[$k] = (string) $v;
			}
		}

		return $base;
	}
}

if (! function_exists('admin_cms_page_texts')) {
	/**
	 * Textos pantallas CMS admin.
	 *
	 * @param array<string, string>|null $pageTextsFromController
	 *
	 * @return array<string, string>
	 */
	function admin_cms_page_texts(?array $pageTextsFromController = null): array
	{
		$base = admin_lang_cmp_admin_cms_texts();
		if (! is_array($pageTextsFromController) || $pageTextsFromController === []) {
			return $base;
		}
		foreach ($pageTextsFromController as $k => $v) {
			if (! is_string($k) || $k === '') {
				continue;
			}
			if (is_string($v) && trim($v) !== '') {
				$base[$k] = $v;
			} elseif (is_numeric($v)) {
				$base[$k] = (string) $v;
			}
		}

		return $base;
	}
}

if (! function_exists('admin_shell_lang_section')) {
	/**
	 * Textos del layout admin (navbar) desde api/lang/*.json → cmp-admin-shell.
	 *
	 * @return array<string, string>
	 */
	function admin_shell_lang_section(): array
	{
		return admin_lang_merged_section('cmp-admin-shell');
	}
}

if (! function_exists('admin_company_form_emergency_labels_en')) {
	/**
	 * @return array<string, string>
	 */
	function admin_company_form_emergency_labels_en(): array
	{
		return [
			'form_add_title' => 'Add new company',
			'form_edit_title' => 'Edit company',
			'label_company_category' => 'Company category',
			'label_company_name' => 'Company name',
			'placeholder_company_name' => 'Company name',
			'label_short_name' => 'Short name',
			'placeholder_short_name' => 'Short name',
			'label_language' => 'Language',
			'label_email' => 'Email',
			'placeholder_email' => 'Enter email',
			'label_password' => 'Password',
			'label_phone_number' => 'Phone number',
			'label_cell_number' => 'Cell number',
			'label_website' => 'Website',
			'label_total_price' => 'Total price',
			'label_sell_with_inventory' => 'Sell with inventory',
			'label_show_inventory' => 'Show inventory',
			'label_show_semaforo_stock' => 'Show stock traffic light',
			'label_image' => 'Image',
			'label_image_format' => 'Format',
			'label_image_formats' => 'jpg, jpeg, png, gif',
			'label_image_max_size' => 'Max upload size',
			'label_image_max_size_value' => '10 MB',
			'btn_select_image' => 'Select image',
			'btn_change_image' => 'Change',
			'btn_remove_image' => 'Remove',
			'section_company_contacts' => 'Company contacts',
			'label_contact_name' => 'Name',
			'label_contact_email' => 'Email',
			'label_contact_website' => 'Website',
			'label_contact_phone' => 'Phone',
			'label_contact_cellphone' => 'Cellphone',
			'btn_add_more' => 'Add more',
			'btn_submit' => 'Save',
			'btn_back' => 'Back',
			'option_yes' => 'Yes',
			'option_no' => 'No',
			'required_asterisk' => '*',
			'duplicate_email_exists' => 'This email already exists in the database',
			'duplicate_name_exists' => 'This company name already exists in the database',
			'duplicate_phone_exists' => 'This phone number already exists in the database',
			'company_created_success' => 'Company created successfully.',
			'email_already_registered' => 'This email is already registered with us.',
			'phone_already_registered' => 'This phone number is already registered with us.',
		];
	}
}

if (! function_exists('admin_lang_cmp_admin_company_texts')) {
	/**
	 * cmp-admin-company: en-EN como base + locale de sesión.
	 *
	 * @return array<string, string>
	 */
	function admin_lang_cmp_admin_company_texts(): array
	{
		$emergency = admin_company_form_emergency_labels_en();
		$fromEn = admin_lang_json_flat_section('en-EN.json', 'cmp-admin-company');
		$defaults = array_merge($emergency, $fromEn);
		$file = admin_shell_lang_filename();
		if ($file === 'en-EN.json') {
			return $defaults;
		}
		$user = admin_lang_json_flat_section($file, 'cmp-admin-company');

		return array_merge($defaults, $user);
	}
}

if (! function_exists('admin_company_page_texts')) {
	/**
	 * @param array<string, string>|null $pageTextsFromController
	 *
	 * @return array<string, string>
	 */
	function admin_company_page_texts(?array $pageTextsFromController = null): array
	{
		$base = admin_lang_cmp_admin_company_texts();
		if (! is_array($pageTextsFromController) || $pageTextsFromController === []) {
			return $base;
		}
		foreach ($pageTextsFromController as $k => $v) {
			if (! is_string($k) || $k === '') {
				continue;
			}
			if (is_string($v) && trim($v) !== '') {
				$base[$k] = $v;
			} elseif (is_numeric($v)) {
				$base[$k] = (string) $v;
			}
		}

		return $base;
	}
}

if (! function_exists('admin_apply_phone_mask')) {
	/**
	 * Aplica máscara de countries.phonemask (# = dígito).
	 */
	function admin_apply_phone_mask(string $phoneNumber, string $mask): string
	{
		$phoneNumber = trim($phoneNumber);
		$mask = trim($mask);
		if ($phoneNumber === '' || $mask === '') {
			return $phoneNumber;
		}
		$digits = preg_replace('/\D/', '', $phoneNumber);
		if ($digits === '') {
			return $phoneNumber;
		}
		$result = '';
		$digitIndex = 0;
		$maskLen = strlen($mask);
		$digitsLen = strlen($digits);
		for ($i = 0; $i < $maskLen && $digitIndex < $digitsLen; $i++) {
			if ($mask[$i] === '#') {
				$result .= $digits[$digitIndex++];
			} else {
				$result .= $mask[$i];
			}
		}
		if ($digitIndex < $digitsLen) {
			$result .= substr($digits, $digitIndex);
		}

		return $result;
	}
}

if (! function_exists('admin_strip_phone_dial_code')) {
	function admin_strip_phone_dial_code(string $fullPhone, int $dialCode): string
	{
		$fullPhone = trim($fullPhone);
		if ($fullPhone === '') {
			return '';
		}
		$digits = preg_replace('/\D/', '', $fullPhone);
		if ($dialCode > 0) {
			$dialStr = (string) $dialCode;
			$prefix = '+' . $dialStr;
			if (strpos($fullPhone, $prefix) === 0) {
				$rest = substr($fullPhone, strlen($prefix));

				return preg_replace('/\D/', '', $rest) ?? '';
			}
			if (strpos($digits, $dialStr) === 0) {
				return substr($digits, strlen($dialStr));
			}
		}

		return $digits;
	}
}

if (! function_exists('admin_build_country_phone_meta_map')) {
	/**
	 * @param list<object|array<string, mixed>> $countries
	 * @return array<int, array{phonecode: int, phonemask: string}>
	 */
	function admin_build_country_phone_meta_map(array $countries): array
	{
		$map = [];
		foreach ($countries as $country) {
			$o = is_array($country) ? (object) $country : $country;
			$id = (int) ($o->id ?? $o->country_id ?? 0);
			if ($id < 1) {
				continue;
			}
			$map[$id] = [
				'phonecode' => (int) ($o->phonecode ?? 0),
				'phonemask' => trim((string) ($o->phonemask ?? $o->phone_mask ?? '')),
			];
		}

		return $map;
	}
}

if (! function_exists('admin_format_company_phone_display')) {
	/**
	 * Formato de lectura: +{phonecode} {parte nacional con phonemask}.
	 *
	 * @param array<int, array{phonecode: int, phonemask: string}> $countryMetaById
	 */
	function admin_format_company_phone_display(string $storedPhone, $countryId, array $countryMetaById): string
	{
		$storedPhone = trim($storedPhone);
		if ($storedPhone === '') {
			return '';
		}
		$cid = (int) $countryId;
		$phonecode = 0;
		$phonemask = '';
		if ($cid > 0 && isset($countryMetaById[$cid])) {
			$phonecode = (int) $countryMetaById[$cid]['phonecode'];
			$phonemask = trim((string) $countryMetaById[$cid]['phonemask']);
		}
		if ($phonemask === '' && $phonecode === 1) {
			$phonemask = '(###) ###-####';
		}
		$nationalDigits = admin_strip_phone_dial_code($storedPhone, $phonecode);
		if ($phonemask !== '' && $nationalDigits !== '') {
			$nationalFormatted = admin_apply_phone_mask($nationalDigits, $phonemask);

			return $phonecode > 0
				? '+' . $phonecode . ' ' . $nationalFormatted
				: $nationalFormatted;
		}

		return $storedPhone;
	}
}

if (! function_exists('admin_company_whatsapp_use_column')) {
	/**
	 * Columna BIT(1) del flag "Use WhatsApp": use_whatsapp (actual) o whatsapp_instance (legado).
	 */
	function admin_company_whatsapp_use_column(): ?string
	{
		$cap = admin_capability_section('companies');
		if ($cap['hasUseWhatsapp'] ?? false) {
			return 'use_whatsapp';
		}
		if ($cap['hasWhatsappInstance'] ?? false) {
			return 'whatsapp_instance';
		}

		return null;
	}
}

if (! function_exists('admin_company_whatsapp_flag_is_on')) {
	/**
	 * Interpreta valor BIT(1), int, string o bool desde POST o fila de BD.
	 */
	function admin_company_whatsapp_flag_is_on($raw): bool
	{
		if (is_bool($raw)) {
			return $raw;
		}
		if (is_int($raw) || is_float($raw)) {
			return (int) $raw !== 0;
		}
		if (! is_string($raw)) {
			return false;
		}
		if ($raw === '' || $raw === "\x00") {
			return false;
		}
		if (ctype_digit($raw)) {
			return (int) $raw !== 0;
		}
		if (strlen($raw) === 1) {
			return ord($raw) > 0;
		}

		return in_array(strtolower(trim($raw)), ['1', 'true', 'on', 'yes'], true);
	}
}

if (! function_exists('admin_company_whatsapp_flag_from_post')) {
	/**
	 * Lee use_whatsapp del POST (hidden 0 + checkbox 1 pueden llegar como array).
	 */
	function admin_company_whatsapp_flag_from_post($CI): int
	{
		if (! is_object($CI) || ! method_exists($CI, 'input')) {
			return 0;
		}
		$raw = $CI->input->post('use_whatsapp');
		if (is_array($raw)) {
			foreach ($raw as $v) {
				if (admin_company_whatsapp_flag_is_on($v)) {
					return 1;
				}
			}

			return 0;
		}

		return admin_company_whatsapp_flag_is_on($raw) ? 1 : 0;
	}
}

if (! function_exists('admin_company_whatsapp_flag_from_row')) {
	/**
	 * Lee el flag desde objeto/array de compañía (use_whatsapp o whatsapp_instance).
	 *
	 * @param object|array<string, mixed> $row
	 */
	function admin_company_whatsapp_flag_from_row($row): bool
	{
		if (is_object($row)) {
			$raw = $row->use_whatsapp ?? ($row->whatsapp_instance ?? 0);
		} elseif (is_array($row)) {
			$raw = $row['use_whatsapp'] ?? ($row['whatsapp_instance'] ?? 0);
		} else {
			$raw = 0;
		}

		return admin_company_whatsapp_flag_is_on($raw);
	}
}
