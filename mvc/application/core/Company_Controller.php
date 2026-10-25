<?php

class Company_Controller extends Application_Controller
{

    protected string $layout = 'company';

    /**
     * Tras autenticación, valida suscripción/plan y límites de companies en cada petición.
     * Si falla, redirige a /login (Auth) con el mensaje en flash.
     */
    public function checkLoggedInOrLoginWithRedirect()
    {
        parent::checkLoggedInOrLoginWithRedirect();

        if ($this->isDeferredAuthImageUploadAjaxRequest()) {
            return;
        }

        if (! $this->hasUserData('company') || ! $this->hasUserData('compId')) {
            return;
        }

        $companyId = (int) $this->getCompanyId();
        if ($companyId < 1) {
            return;
        }

        $this->syncCompanyPanelLanguageFromCompanyRecord();

        $langId = (int) ($this->getUserData('language_id') ?? 1);
        $this->load->library('company_plan_validator');
        $msgs = $this->company_plan_validator->loadLoginLangMessages($langId);
        $error = $this->cachedApiValue('planval:'.$companyId, 90, function () use ($companyId, $msgs) {
            return $this->company_plan_validator->validateForLogin($companyId, $msgs);
        });
        if ($error === '') {
            return;
        }

        $safe = nl2br(htmlspecialchars($error, ENT_QUOTES, 'UTF-8'));
        $alert = $this->load->view('auth/alert_message', ['msg' => $safe, 'alertType' => 'danger'], true);

        // Destruir sesión PRIMERO; si no, el flash se borra junto con todo lo demás.
        $this->session->sess_destroy();

        // Reabrir sesión nueva para guardar el flash.
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        $this->session->set_flashdata('msg', $alert);
        session_write_close();

        $loginTarget = base_url('login');
        $redirectto = $this->input->get('redirectto');
        if (empty($redirectto) && isset($_SERVER['HTTP_REFERER'])) {
            $referer = (string) $_SERVER['HTTP_REFERER'];
            if (strpos($referer, '/login') !== false && strpos($referer, 'redirectto=') !== false) {
                parse_str((string) parse_url($referer, PHP_URL_QUERY), $qs);
                if (! empty($qs['redirectto'])) {
                    $redirectto = $qs['redirectto'];
                }
            }
        }
        if (! empty($redirectto)) {
            $loginTarget .= '?' . http_build_query(['redirectto' => (string) $redirectto]);
        }

        redirect($loginTarget, 'location');
        exit;
    }

    /**
     * On company we expect to send on every request the company_id
     * @param $options
     * @param $paramsWhere
     * @return void
     */
    protected function injectParams(&$options,$paramsWhere='query') {
        $companyId = 0;
        if (method_exists($this, 'resolveImageUploadCompanyId')) {
            $companyId = (int) $this->resolveImageUploadCompanyId();
        }
        if ($companyId < 1 && isset($this->input) && is_object($this->input)) {
            $companyId = (int) ($this->input->post('fv_company_id') ?? 0);
        }
        if ($companyId < 1) {
            $companyId = (int) $this->getCompanyId();
        }
        if ($companyId < 1) {
            return;
        }
        if(isset($options['multipart'])) //multipart exception neither query nor json
        {
            $options['multipart'][] =
            [
                'name' => 'company_id',
                'contents' => $companyId
            ];
        }
        else
            $options[$paramsWhere]['company_id'] = $companyId;
    }

    /**
     * Bloque anidado de api/lang/{es-ES|en-EN}.json (p. ej. "cmp-user").
     * La fuente de verdad de cadenas es el JSON; no leer api/lang/{es|en}/cmp-*.php.
     *
     * @param string $section clave de primer nivel en el JSON (ej. cmp-user)
     * @return array<string, mixed>
     */
    protected function getLangJsonSection(string $section): array
    {
        return fv_lang_json_flat_section($this->resolveCompanyPanelLangId(), $section);
    }

    /**
     * Resuelve una cadena de api/lang/{locale}.json respetando el idioma de la empresa.
     * Orden: JSON local/API vía getLangJsonSection → caché del módulo → getModuleLang (langmdl).
     */
    protected function resolveModuleLangString(string $section, string $key, ?array $moduleLang = null): string
    {
        $fromJson = $this->getLangJsonSection($section);
        if (isset($fromJson[$key])) {
            $msg = trim((string) $fromJson[$key]);
            if ($msg !== '') {
                return $msg;
            }
        }

        if (is_array($moduleLang) && isset($moduleLang[$key])) {
            $msg = trim((string) $moduleLang[$key]);
            if ($msg !== '') {
                return $msg;
            }
        }

        $fresh = $this->getModuleLang($section);
        if (isset($fresh[$key])) {
            $msg = trim((string) $fresh[$key]);
            if ($msg !== '') {
                return $msg;
            }
        }

        $tools = $this->getLangJsonSection('config-crud-tools');
        if (isset($tools['message_error_generic'])) {
            $msg = trim((string) $tools['message_error_generic']);
            if ($msg !== '') {
                return $msg;
            }
        }

        return '';
    }

    protected function termsLang(string $key): string
    {
        $moduleLang = isset($this->langArr) && is_array($this->langArr) ? $this->langArr : null;

        return $this->resolveModuleLangString('cmp-terms', $key, $moduleLang);
    }

    protected function groupsLang(string $key): string
    {
        $moduleLang = isset($this->langArr) && is_array($this->langArr) ? $this->langArr : null;

        return $this->resolveModuleLangString('cmp-group', $key, $moduleLang);
    }

    /**
     * Panel company: JSON en api/lang/*.json como fuente principal de módulos cmp-*.
     */
    protected function getModuleLang($moduleLangName)
    {
        $fromJson = $this->getLangJsonSection($moduleLangName);
        if (empty($fromJson)) {
            $altKey = str_contains($moduleLangName, '_')
                ? str_replace('_', '-', $moduleLangName)
                : str_replace('-', '_', $moduleLangName);
            if ($altKey !== $moduleLangName) {
                $fromJson = $this->getLangJsonSection($altKey);
            }
        }
        if (! empty($fromJson)) {
            return $fromJson;
        }

        return parent::getModuleLang($moduleLangName);
    }

    /**
     * Idioma del panel: siempre el de la empresa (companies.language_id), no el del usuario.
     */
    protected function resolveCompanyPanelLangId(): int
    {
        if (function_exists('fv_resolve_company_language_id')) {
            $resolved = fv_resolve_company_language_id();
            if ($resolved >= 1 && $resolved <= 7) {
                return $resolved;
            }
        }

        return 1;
    }

    /**
     * Sincroniza language_id de sesión con companies.language_id (fuente de verdad del panel).
     */
    protected function syncCompanyPanelLanguageFromCompanyRecord(): void
    {
        $companyId = (int) $this->getCompanyId();
        if ($companyId < 1) {
            return;
        }

        $prevLangId = (int) ($this->getUserData('company_panel_language_id') ?? 0);
        $prevCompId = (int) ($this->getUserData('company_panel_language_comp_id') ?? 0);
        $syncedAt = (int) ($this->getUserData('company_panel_language_at') ?? 0);
        if ($prevCompId === $companyId && $prevLangId >= 1 && (time() - $syncedAt) < 600) {
            return;
        }
        $langId = $this->resolveCompanyLanguageIdFromApi($companyId);
        $this->persistCompanyPanelLanguage($companyId, $langId);

        if ($prevLangId !== $langId || $prevCompId !== $companyId) {
            $this->setUserData('langDictionary', null);
            foreach (['en', 'es', 'pt', 'it', 'ru', 'de', 'zh'] as $loc) {
                $this->setUserData("langDictionary_{$loc}", null);
            }
            if (function_exists('fv_language_id_to_locale')) {
                $this->setUserData('langDictionary_lang', fv_language_id_to_locale($langId));
            }
        }
    }

    /**
     * Archivo api/lang/*.json según language_id (alineado con LanguageSeeder).
     */
    protected function langJsonFilenameForLanguageId(int $languageId): string
    {
        static $map = [
            1 => 'en-EN.json',
            2 => 'es-ES.json',
            3 => 'pt-PT.json',
            4 => 'it-IT.json',
            5 => 'ru-RU.json',
            6 => 'de-DE.json',
            7 => 'zh-CN.json',
        ];

        return $map[$languageId] ?? 'en-EN.json';
    }

    /**
     * Una cadena concreta de api/lang/{locale}.json: $section.$key (p. ej. cmp-user.user_saved).
     */
    protected function getTranslationFromLangJsonByLanguageId(int $languageId, string $section, string $key, string $fallback = ''): string
    {
        $path = dirname(APPPATH, 2) . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $this->langJsonFilenameForLanguageId($languageId);
        if (! is_readable($path)) {
            return $fallback;
        }
        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            return $fallback;
        }
        $data = json_decode($raw, true);
        if (! is_array($data) || ! isset($data[$section][$key])) {
            return $fallback;
        }
        $value = $data[$section][$key];

        return is_string($value) && $value !== '' ? $value : $fallback;
    }

    /**
     * Uso actual vs límites del plan (GET company/{id}/plan-limits).
     *
     * @return array<string, array{current?: int, max?: int, can_create?: bool}>|null
     */
    protected function fetchCompanyPlanLimits(): ?array
    {
        $companyId = (int) $this->getCompanyId();
        if ($companyId < 1) {
            return null;
        }

        try {
            $resp = $this->getDataFromApi("company/{$companyId}/plan-limits", [], true);
        } catch (\Throwable $e) {
            log_message('error', 'fetchCompanyPlanLimits: ' . $e->getMessage());

            return null;
        }

        if ($this->hasError($resp) || ! is_array($resp)) {
            return null;
        }

        if (isset($resp['customers'])) {
            return $resp;
        }

        if (isset($resp['data']) && is_array($resp['data'])) {
            return $resp['data'];
        }

        return null;
    }

    /**
     * Bloque para aviso en pantallas de alta (pre-check MVC).
     *
     * @param array<string, array{current?: int, max?: int, can_create?: bool}>|null $limits
     * @param 'customers'|'orders_month'|'sellers' $metric
     * @return array{can_create: bool, message: string, current: int, max: int}
     */
    protected function buildPlanLimitBlock(?array $limits, string $metric, string $langSection): array
    {
        $default = ['can_create' => true, 'message' => '', 'current' => 0, 'max' => 0];
        if (! is_array($limits) || ! isset($limits[$metric]) || ! is_array($limits[$metric])) {
            return $default;
        }

        $row = $limits[$metric];
        $current = (int) ($row['current'] ?? 0);
        $max = (int) ($row['max'] ?? 0);
        $canCreate = (bool) ($row['can_create'] ?? true);

        if ($canCreate) {
            return ['can_create' => true, 'message' => '', 'current' => $current, 'max' => $max];
        }

        $langId = (int) ($this->getUserData('language_id') ?? 1);
        if (function_exists('fv_resolve_company_language_id')) {
            $langId = fv_resolve_company_language_id();
        }
        $template = '';
        if (is_array($this->langArr ?? null) && ! empty($this->langArr['plan_limit_at_capacity_banner'])) {
            $template = (string) $this->langArr['plan_limit_at_capacity_banner'];
        }
        if ($template === '') {
            $section = $this->getLangJsonSection($langSection);
            $template = (string) ($section['plan_limit_at_capacity_banner'] ?? '');
        }
        if ($template === '') {
            $template = $this->getTranslationFromLangJsonByLanguageId(
                $langId,
                $langSection,
                'plan_limit_at_capacity_banner',
                'Limit reached (%d of %d).'
            );
        }

        return [
            'can_create' => false,
            'message' => sprintf($template, $current, $max),
            'current' => $current,
            'max' => $max,
        ];
    }

    protected function getDataForView($view = null, $dataForView = [])
    {

        try {
            $this->benchmark->mark('company_showcontent_start');
            
            try {
                $dataForView['main_navigation'] = $this->getMainNavigation();
            } catch (\Exception $e) {
                log_message('error', 'Error in getMainNavigation: ' . $e->getMessage());
                $dataForView['main_navigation'] = [];
            }

            try {
                $dataForView['langdef'] = $this->getModuleLang('comp-login-head');
            } catch (\Exception $e) {
                log_message('error', 'Error getting langdef: ' . $e->getMessage());
                $dataForView['langdef'] = [];
            }
            
            $this->benchmark->mark('navigation');
            
            try {
                $dataForView['config_menu'] = $this->getConfigMenu();
            } catch (\Exception $e) {
                log_message('error', 'Error in getConfigMenu: ' . $e->getMessage());
                $dataForView['config_menu'] = [];
            }
            
            $this->benchmark->mark('configmenu');
            
            try {
                $companyId = $this->getCompanyId();
                if ($companyId && $companyId > 0) {
                    try {
                        $dataForView['company'] = $this->cachedApiValue('company:'.$companyId, 120, function () use ($companyId) {
                            return $this->getObjectFromApi('company/'.$companyId);
                        });
                    } catch (\Exception $e) {
                        log_message('error', 'Error getting company from API: ' . $e->getMessage());
                        $dataForView['company'] = null;
                    }
                } else {
                    $dataForView['company'] = null;
                }
            } catch (\Exception $e) {
                log_message('error', 'Error getting company ID: ' . $e->getMessage());
                $dataForView['company'] = null;
            }
            
            $this->benchmark->mark('company');
            
            try {
                $dataForView['user'] = $this->enrichSessionUserAvatar($this->getUser());
            } catch (\Exception $e) {
                log_message('error', 'Error getting user: ' . $e->getMessage());
                $dataForView['user'] = null;
            }

            $this->benchmark->mark('user');
            
            try {
                $companyId = $this->getCompanyId();
                
                // Obtener datos del API y verificar si están llegando
                if ($companyId) {
                    try {
                        $format_numeric = $this->cachedApiValue('fmtnum:'.$companyId, 300, function () use ($companyId) {
                            return $this->getObjectFromApi("company/{$companyId}/format-numeric");
                        });
                    } catch (\Exception $e) {
                        log_message('error', 'Error getting format-numeric from API: ' . $e->getMessage());
                        $format_numeric = null;
                    }
                } else {
                    $format_numeric = null;
                }
                
                // currency-symbol y currency-code devuelven strings directamente (no arrays)
                // getDataFromApi con associative=true devuelve el string directamente cuando el JSON es un string simple
                if ($companyId) {
                    try {
                        $format_currency = $this->cachedApiValue('cursym:'.$companyId, 300, function () use ($companyId) {
                            return $this->getRawFromApi("company/{$companyId}/currency-symbol", []);
                        });
                    } catch (\Exception $e) {
                        log_message('error', 'Error getting currency-symbol from API: ' . $e->getMessage());
                        $format_currency = null;
                    }
                    
                    try {
                        $code_currency = $this->cachedApiValue('curcode:'.$companyId, 300, function () use ($companyId) {
                            return $this->getRawFromApi("company/{$companyId}/currency-code", []);
                        });
                    } catch (\Exception $e) {
                        log_message('error', 'Error getting currency-code from API: ' . $e->getMessage());
                        $code_currency = null;
                    }
                } else {
                    $format_currency = null;
                    $code_currency = null;
                }
            } catch (\Exception $e) {
                log_message('error', 'Error getting company ID for format data: ' . $e->getMessage());
                $format_numeric = null;
                $format_currency = null;
                $code_currency = null;
            }
        
            $this->benchmark->mark('format-numeric');
            $dataForView['format_numeric'] = $format_numeric;
            $this->benchmark->mark('format-currency');
            $dataForView['format_currency'] = $format_currency;
            $this->benchmark->mark('code-currency');
            $dataForView['code_currency'] = $code_currency;

            // Formato de fecha de la empresa (Company Profile → format_date_id)
            $format_date = null;
            try {
                $companyForDate = $dataForView['company'] ?? null;
                $formatDateId = is_object($companyForDate) ? (int) ($companyForDate->format_date_id ?? 0) : 0;
                if ($formatDateId > 0) {
                    $format_date = $this->cachedApiValue('fmtdate:'.$formatDateId, 300, function () use ($formatDateId) {
                        return $this->getObjectFromApi("formatdate/{$formatDateId}");
                    });
                }
                if (! is_object($format_date) || (method_exists($this, 'hasError') && $this->hasError($format_date))) {
                    $format_date = $this->cachedApiValue('fmtdate:default', 300, function () {
                        return $this->getObjectFromApi('formatdate/default');
                    });
                }
            } catch (\Exception $e) {
                log_message('error', 'Error getting format_date from API: ' . $e->getMessage());
                $format_date = null;
            }
            if (! is_object($format_date) || (method_exists($this, 'hasError') && $this->hasError($format_date))) {
                $format_date = function_exists('fv_default_format_date_object')
                    ? fv_default_format_date_object()
                    : (object) ['format_php' => 'm/d/Y', 'format_js' => 'mm/dd/yy'];
            }
            if (function_exists('fv_normalize_format_date_object')) {
                $format_date = fv_normalize_format_date_object($format_date);
            }
            $dataForView['format_date'] = $format_date;
            // Disponible para helpers (fv_format_display_date) en la misma request.
            $this->fv_format_date = $format_date;
            if (isset($this->Helper) && is_object($this->Helper)) {
                $this->Helper->cached_format_date = $format_date;
                if (is_object($this->Helper->company_info ?? null) && isset($formatDateId) && $formatDateId > 0) {
                    $this->Helper->company_info->format_date_id = $formatDateId;
                }
            }
            
            // Obtener traducciones de toltip para el botón eliminar (si no se ha definido ya)
            if (!isset($dataForView['deleteLang'])) {
                try {
                    $deleteLang = method_exists($this, 'getLangJsonSection')
                        ? $this->getLangJsonSection('cmp-toltip')
                        : [];
                    if (empty($deleteLang)) {
                        $deleteLang = $this->getModuleLang('cmp-toltip');
                    }
                    $dataForView['deleteLang'] = is_array($deleteLang) ? $deleteLang : [];
                } catch (\Exception $e) {
                    log_message('error', 'Error getting deleteLang translations: ' . $e->getMessage());
                    $dataForView['deleteLang'] = method_exists($this, 'getLangJsonSection')
                        ? $this->getLangJsonSection('cmp-toltip')
                        : [];
                }
            }
            
            // Traducciones para alertas de validación según idioma configurado (en/es), expuestas en JSON en la vista
            if (!isset($dataForView['validationLang'])) {
                try {
                    $validationLang = method_exists($this, 'getLangJsonSection')
                        ? $this->getLangJsonSection('errores_unit')
                        : [];
                    if (empty($validationLang)) {
                        $locale = $this->getDefaultLanguage($this->getCompanyId());
                        $fromApi = $this->getObjectFromApi('langmdl/errores_unit', ['locale' => $locale]);
                        $validationLang = is_array($fromApi) ? $fromApi : (is_object($fromApi) ? (array) $fromApi : []);
                    }
                    $dataForView['validationLang'] = is_array($validationLang) ? $validationLang : [];
                } catch (\Exception $e) {
                    log_message('error', 'Error getting validationLang translations: ' . $e->getMessage());
                    $dataForView['validationLang'] = method_exists($this, 'getLangJsonSection')
                        ? $this->getLangJsonSection('errores_unit')
                        : [];
                }
            }
            
            $result = parent::getDataForView($view, $dataForView);
            $result['company_id'] = (int) $this->getCompanyId();
            $result['globalSearchLang'] = $this->getLangJsonSection('cmp-global-search');
            if (empty($result['page']) && function_exists('fv_company_nav_active_key')) {
                $activeNav = fv_company_nav_active_key();
                if ($activeNav !== null && $activeNav !== '') {
                    $result['page'] = $activeNav;
                }
            }
            $this->addJsFile(base_url('companyasset/company/global-search.js'));

            return $result;
        } catch (\Exception $e) {
            log_message('error', 'Fatal error in Company_Controller::getDataForView: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            // Devolver datos mínimos para que la vista pueda cargar
            $dataForView['main_navigation'] = $dataForView['main_navigation'] ?? [];
            $dataForView['config_menu'] = $dataForView['config_menu'] ?? [];
            $dataForView['company'] = $dataForView['company'] ?? null;
            $dataForView['user'] = $dataForView['user'] ?? null;
            $dataForView['company_id'] = (int) $this->getCompanyId();
            $dataForView['format_numeric'] = $dataForView['format_numeric'] ?? null;
            $dataForView['format_currency'] = $dataForView['format_currency'] ?? null;
            $dataForView['code_currency'] = $dataForView['code_currency'] ?? null;
            $dataForView['format_date'] = $dataForView['format_date']
                ?? (function_exists('fv_default_format_date_object') ? fv_default_format_date_object() : null);
            return parent::getDataForView($view, $dataForView);
        }
        
        
        
        
        return $result;
    }

    public function showContent($contentView, $data = [], $layout = 'company')
    {
        parent::showContent($contentView, $data, $layout);
    }

    protected function getConfigMenu()
    {
        $langId = fv_resolve_company_language_id();
        $langNav = fv_lang_json_flat_section($langId, 'company-navigation');

        return [
            'url' => '',
            'label' => $langNav['profile'] ?? 'Profile',
            'icon' => 'fa fa-cog fa-fw fa-lg',
            'subitems' => [
                'profile' => [
                    'url' => 'profile',
                    'label' => $langNav['profile'] ?? 'Profile',
                    'icon' => 'fa fa-cog fa-fw fa-lg',
                ],
                'import' => [
                    'url' => 'import/lists',
                    'label' => $langNav['import'] ?? 'Import',
                    'icon' => 'mdi mdi-import',
                ],
                'logs' => [
                    'url' => 'logs/lists',
                    'label' => $langNav['logs'] ?? 'Logs',
                    'icon' => 'mdi mdi-database',
                ],
            ]
        ];
    }

    /**
     * Creates the main navigation information for the company app
     * @return array
     */
    protected function getMainNavigation(): array
    {
        $langId = fv_resolve_company_language_id();
        $langNav = fv_lang_json_flat_section($langId, 'company-navigation');

        
        return [
            'dashboard' => [
                'url' => 'dashboard',
                'label' => $langNav['dashboard'] ?? 'Dashboard',
                'icon' => 'fa-solid fa-house',
            ],
            'settings' => [
                'url' => 'null',
                'label' => $langNav['settings'] ?? ($langNav['profile'] ?? 'Settings'),
                'icon' => 'fa-solid fa-gear',
                'subitems' => [
                    'units' => [
                        'url' => 'units/lists',
                        'label' => $langNav['nav_units'] ?? 'Units',
                        'icon' => 'fa-solid fa-ruler-combined',
                    ],
                    'payment_terms' => [
                        'url' => 'terms/lists',
                        'label' => $langNav['nav_payment_terms'] ?? 'Payment terms',
                        'icon' => 'fa-solid fa-file-invoice-dollar',
                    ],
                    'sellers_profile' => [
                        'url' => 'sellerprofile/lists',
                        'label' => $langNav['nav_sellers_profile'] ?? 'Sellers profile',
                        'icon' => 'fa-solid fa-id-card',
                    ],
                    'currency' => [
                        'url' => 'currencies/lists',
                        'label' => $langNav['nav_currency'] ?? 'Currency',
                        'icon' => 'fa-solid fa-coins',
                    ],
                    'customer_groups' => [
                        'url' => 'groups',
                        'label' => $langNav['nav_customer_groups'] ?? 'Customer groups',
                        'icon' => 'fa-solid fa-users',
                    ],
                    'users' => [
                        'url' => 'users',
                        'label' => $langNav['user'] ?? 'Users',
                        'icon' => 'fa-solid fa-user-lock',
                    ],
                    'warehouses' => [
                        'url' => 'warehouses',
                        'label' => $langNav['warehouse'] ?? 'Warehouse',
                        'icon' => 'fa-solid fa-house-user',
                    ],
                    'send_templates' => [
                        'url' => 'sendtemplate',
                        'label' => $langNav['nav_send_templates'] ?? 'Send templates',
                        'icon' => 'fa-solid fa-envelope-open-text',
                    ],
                ],
            ],
            'products' => [
                'url' => 'null',
                'label' => $langNav['products'] ?? 'Products',
                'icon' => 'fa-solid fa-box',
                'subitems' => [
                    'manage_products' => [
                        'url' => 'products',
                        'label' => $langNav['nav_manage_products'] ?? 'Manage Products',
                        'icon' => 'fa-solid fa-boxes-stacked',
                    ],
                    'product_categories' => [
                        'url' => 'categories',
                        'label' => $langNav['nav_categories'] ?? 'Categories',
                        'icon' => 'fa-solid fa-folder-tree',
                    ],
                    'product_inventory' => [
                        'url' => 'products/inventory',
                        'label' => $langNav['nav_product_inventory'] ?? 'Inventory',
                        'icon' => 'fa-solid fa-clipboard-list',
                    ],
                ],
            ],
            'customer' => [
                'url' => 'customers',
                'label' => $langNav['customer'] ?? 'Customers',
                'icon' => 'fa-solid fa-user-group',
            ],
            'order' => [
                'url' => 'null',
                'label' => $langNav['order'] ?? 'Orders',
                'icon' => 'fa-solid fa-cart-shopping',
                'subitems' => [
                    'order' => [
                        'url' => 'orders',
                        'label' => $langNav['order'] ?? 'Orders',
                        'icon' => 'fa-solid fa-paper-plane',
                    ],
                    'warehouseorders' => [
                        'url' => 'warehouse/orders',
                        'label' => $langNav['warehouseorders'] ?? 'Warehouse Orders',
                        'icon' => 'fa-solid fa-paste',
                    ],
                    'inventory' => [
                        'url' => 'inventory/order',
                        'label' => $langNav['inventory'] ?? 'Inventory',
                        'icon' => 'fa-solid fa-warehouse',
                    ],
                    'opencart' => [
                        'url' => 'cart/abandoned',
                        'label' => $langNav['opencart'] ?? 'Open Cart',
                        'icon' => 'fa-solid fa-cart-shopping',
                    ],
                ],
            ],
            'catalog' => [
                'url' => 'catalog',
                'label' => $langNav['catalog'] ?? 'Catalog',
                'icon' => 'fa-solid fa-table-cells',
            ],
        ];
    }

    /**
     * Normaliza la respuesta de la API de estados de orden y deduplica por cod_status.
     * Garantiza un único registro por cod_status (no repetir New, Pending, Rejected, etc.).
     *
     * @param mixed $raw Respuesta cruda de getObjectFromApi('statusorder/list')
     * @return array Lista de estados únicos por cod_status, ordenados por cod_status
     */
    /**
     * Extrae un array plano de ítems desde respuestas API (lista directa, paginada con data, o error).
     *
     * @param mixed $raw
     * @return array<int, mixed>
     */
    protected function extractApiListItems($raw): array
    {
        if ($this->hasError($raw)) {
            return [];
        }
        if (is_array($raw)) {
            if (isset($raw['data']) && is_array($raw['data'])) {
                return $raw['data'];
            }
            if (isset($raw['error'])) {
                return [];
            }

            return $this->isSequentialArray($raw) ? $raw : [];
        }
        if (is_object($raw) && isset($raw->data)) {
            $data = $raw->data;

            return is_array($data) ? $data : (is_iterable($data) ? iterator_to_array($data) : []);
        }
        if ($raw instanceof \Traversable) {
            return iterator_to_array($raw);
        }

        return [];
    }

    /**
     * @param array<mixed> $value
     */
    protected function isSequentialArray(array $value): bool
    {
        if ($value === []) {
            return true;
        }

        return array_keys($value) === range(0, count($value) - 1);
    }

    protected function normalizeAndDeduplicateOrderStatuses($raw)
    {
        if (is_array($raw) && isset($raw['error'])) {
            return [];
        }
        $list = [];
        if (is_array($raw) && !isset($raw['data'])) {
            $list = $raw;
        } elseif (is_object($raw) && isset($raw->data)) {
            $list = is_array($raw->data) ? $raw->data : (array) $raw->data;
        } elseif (is_array($raw)) {
            $list = $raw;
        } elseif (is_object($raw) && ($raw instanceof \Traversable || $raw instanceof \stdClass)) {
            $list = $raw instanceof \Traversable ? iterator_to_array($raw) : (array) $raw;
        }
        $byCodStatus = [];
        foreach ($list as $status) {
            $cod = is_object($status) ? ($status->cod_status ?? null) : ($status['cod_status'] ?? null);
            if ($cod === null && $cod !== 0 && $cod !== '0') {
                continue;
            }
            $key = (int) $cod;
            if (!array_key_exists($key, $byCodStatus)) {
                $byCodStatus[$key] = $status;
            }
        }
        ksort($byCodStatus, SORT_NUMERIC);
        return array_values($byCodStatus);
    }

    /**
     * UUID de imagen de producto para company_url('image/{uuid}').
     * Origen API: default_image.uuid, product_image, images[0], default_image_id.
     *
     * @param array<string, mixed>|object $product
     */
    protected function resolveProductImageUuid($product): string
    {
        $uuid = $this->resolveProductImageFromRelation($product);
        if ($uuid !== '') {
            return $uuid;
        }

        $productId = is_array($product)
            ? (int) ($product['pro_id'] ?? $product['product_id'] ?? 0)
            : (int) ($product->pro_id ?? $product->product_id ?? 0);
        if ($productId > 0) {
            return $this->resolveProductImageFromApi($productId);
        }

        return '';
    }

    /**
     * @param array<string, mixed>|object $product
     */
    protected function resolveProductImageFromRelation($product): string
    {
        $obj = is_array($product) ? (object) $product : $product;

        if (! empty($obj->product_image)) {
            $uuid = $this->normalizeImageUuid($obj->product_image);
            if ($uuid !== '') {
                return $uuid;
            }
        }

        if (! empty($obj->default_image_id)) {
            $uuid = $this->normalizeImageUuid($obj->default_image_id);
            if ($uuid !== '') {
                return $uuid;
            }
        }

        $img = $obj->default_image ?? null;
        if ($img) {
            $image = is_array($img) ? (object) $img : $img;
            $uuid = $image->uuid ?? $image->image_id ?? ($image->image->uuid ?? null);
            $uuid = $this->normalizeImageUuid($uuid);
            if ($uuid !== '') {
                return $uuid;
            }
        }

        $images = $obj->images ?? [];
        if (is_array($images) && count($images) > 0) {
            $first = $images[0];
            $image = is_array($first) ? (object) $first : $first;
            $uuid = $image->uuid ?? $image->image_id ?? ($image->image->uuid ?? null);
            $uuid = $this->normalizeImageUuid($uuid);
            if ($uuid !== '') {
                return $uuid;
            }
        }

        if (! empty($obj->Img)) {
            $uuid = $this->normalizeImageUuid($obj->Img);
            if ($uuid !== '') {
                return $uuid;
            }
        }

        return '';
    }

    /**
     * @param mixed $value
     */
    protected function normalizeImageUuid($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        if (is_object($value)) {
            $value = $value->uuid ?? $value->image_id ?? '';
        }
        $s = trim((string) $value);
        if ($s === '') {
            return '';
        }
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $s)) {
            return $s;
        }

        return '';
    }

    protected function resolveProductImageFromApi(int $productId): string
    {
        $product = $this->getObjectFromApi("product/{$productId}", ['relations' => ['default_image', 'images']]);
        if ($this->hasError($product) || ! $product) {
            return '';
        }
        $p = is_object($product) ? $product : (object) $product;
        if (isset($p->data)) {
            $p = is_object($p->data) ? $p->data : (object) $p->data;
        }

        return $this->resolveProductImageFromRelation($p);
    }

    /**
     * @param array<string, mixed>|object $raw
     * @return array<string, mixed>
     */
    protected function mapProductForOrderSearch($raw): array
    {
        $item = is_array($raw) ? $raw : (array) $raw;
        $uuid = company_product_image_uuid($raw);
        $publicUrl = company_product_image_url($uuid !== '' ? $uuid : null, 90);
        $sku = (string) ($item['sku'] ?? '');
        $name = (string) ($item['name'] ?? '');

        $item['Img'] = $uuid;
        // Legacy SQL devolvía `images` como URL lista; el modal y Vue leen images / imageurl / Img.
        $item['images'] = $publicUrl;
        $item['imageurl'] = $publicUrl;
        $item['completed_name'] = strtoupper(trim($sku . ' | ' . $name));

        return $item;
    }

    /**
     * Asegura que el usuario de sesión tenga su `image` (UUID) para el avatar del navbar.
     * La sesión de login a veces queda sin imagen o desactualizada tras subir avatar.
     *
     * @param mixed $user
     * @return mixed
     */
    protected function enrichSessionUserAvatar($user)
    {
        if ($user === null || $user === false || $user === '') {
            return $user;
        }

        $isArray = is_array($user);
        $isObject = is_object($user);
        if (! $isArray && ! $isObject) {
            return $user;
        }

        $userId = $isArray ? (int) ($user['id'] ?? 0) : (int) ($user->id ?? 0);
        if ($userId < 1) {
            return $user;
        }

        $currentImage = $isArray
            ? trim((string) ($user['image'] ?? ''))
            : trim((string) ($user->image ?? ''));

        $cache = $this->getUserData('user_avatar_image_cache');
        $cacheOk = is_array($cache)
            && (int) ($cache['user_id'] ?? 0) === $userId
            && (time() - (int) ($cache['ts'] ?? 0)) < 300;

        $image = $currentImage;
        if ($cacheOk) {
            $image = trim((string) ($cache['image'] ?? $currentImage));
        } else {
            try {
                $apiUser = $this->getObjectFromApi('user/' . $userId, [
                    'props' => ['id', 'image', 'name', 'email'],
                ]);
                if (! $this->hasError($apiUser) && is_object($apiUser)) {
                    $image = trim((string) ($apiUser->image ?? ''));
                    $this->setUserData('user_avatar_image_cache', [
                        'user_id' => $userId,
                        'image' => $image,
                        'ts' => time(),
                    ]);
                }
            } catch (\Throwable $e) {
                log_message('debug', 'enrichSessionUserAvatar: ' . $e->getMessage());
            }
        }

        if ($isArray) {
            $user['image'] = $image;
        } else {
            $user->image = $image;
        }

        if ($image !== $currentImage) {
            $this->setUser($user);
        }

        return $user;
    }

    /**
     * Invalida la caché del avatar de sesión (p. ej. tras actualizar la imagen del usuario logueado).
     */
    protected function clearSessionUserAvatarCache(): void
    {
        $this->setUserData('user_avatar_image_cache', null);
    }

    /**
     * Mensajes JSON traducidos para respuestas de error AJAX del panel company.
     */
    protected function jsonError(array $response, string $defaultMessage = '', int $code = 400)
    {
        if ($defaultMessage === '' || $defaultMessage === 'An error has occurred.') {
            $tools = $this->getLangJsonSection('config-crud-tools');
            $defaultMessage = (string) ($tools['message_error_generic'] ?? '');
            if ($defaultMessage === '') {
                $toltip = $this->getLangJsonSection('cmp-toltip');
                $defaultMessage = (string) ($toltip['error_occurred'] ?? '');
            }
        }

        parent::jsonError($response, $defaultMessage, $code);
    }
}
