<?php

/**
 * @property CI_Session $session
 */
trait HasAuth
{

    protected function getUserId()
    {
        return $this->getUserData('user')['id'];
    }

    protected function getRole()
    {
        return $this->getUserData('role');
    }

    protected function getCompanyId()
    {
        $compId = $this->getCompany();
        $resolved = $this->resolveCompanyIdFromSelection($compId);
        if ($resolved > 0) {
            return $resolved;
        }

        // Fallback: única empresa en app_access (login sin selectCompany explícito).
        $application = $this->getApplication();
        if ($application === null || $application === '') {
            $application = 'company';
        }
        $companies = $this->getCompanies($application);
        if (count($companies) === 1) {
            $this->setCompany($companies[0]);
            if (! $this->hasUserData('company')) {
                $this->setUserData('company', '1');
            }

            return $this->resolveCompanyIdFromSelection($this->getCompany());
        }

        return 0;
    }

    /**
     * @param array<string,mixed>|object|int|string|null $selection
     */
    protected function resolveCompanyIdFromSelection($selection): int
    {
        if (is_array($selection)) {
            return (int) ($selection['company_id'] ?? 0);
        }
        if (is_object($selection)) {
            return (int) ($selection->company_id ?? 0);
        }
        if ($selection === null || $selection === '') {
            return 0;
        }

        return (int) $selection;
    }

    /**
     * Tras login: compId + role deben quedar en sesión antes del redirect al panel company.
     *
     * @param array<string,mixed>|object|int|string|null $selectedCompany
     */
    protected function ensureCompanySessionFromSelection($selectedCompany): void
    {
        $existing = (int) ($this->getUserData('compId') ?? 0);
        if ($existing > 0) {
            return;
        }

        if (is_array($selectedCompany) || is_object($selectedCompany)) {
            $this->setCompany($selectedCompany);

            return;
        }

        $companyId = (int) $selectedCompany;
        if ($companyId < 1) {
            return;
        }

        $application = $this->getApplication();
        $companies = $application !== null && $application !== ''
            ? $this->getCompanies($application)
            : [];
        foreach ($companies as $companyRow) {
            if ($this->resolveCompanyIdFromSelection($companyRow) === $companyId) {
                $this->setCompany($companyRow);

                return;
            }
        }

        $this->setUserData('compId', $companyId);
    }

    protected function getCustomerId()
    {
        $role = $this->getRole();

        if ($role === 'customer')
            return $this->getUserData('marketplace_customer_id');

        return $this->getUserData('customer_id');
    }

    protected function setCustomerId($customerId): void
    {
        $this->setUserData('customer_id', $customerId);
    }

    protected function getSellerId() {
        return $this->getUserData('sellerId');
    }

    protected function isSellerSetted(): bool
    {
        return $this->hasUserData('sellerId');
    }

    /**
     * Determines if the session data of a logdein user
     * @return bool
     */
    protected function loggedIn():bool {
        return $this->hasUserData('user');
    }

    protected function doLogOut() {
        $this->removeUserData('userId');
        $this->removeUserData('admin');
        $this->removeUserData('user');
        $this->removeUserData('token');
        $this->removeUserData('company');
        $this->removeUserData('compId');
        $this->removeUserData('application');
        $this->removeUserData('company_panel_language_id');
        $this->removeUserData('company_panel_language_comp_id');
        if (isset($this->userToken)) {
            $this->userToken = null;
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            $this->session->sess_destroy();
        }
    }

    protected function getEntityValue($entityData, $key, $default = null) {
        if (is_array($entityData) && array_key_exists($key, $entityData)) {
            return $entityData[$key];
        }
        if (is_object($entityData) && isset($entityData->$key)) {
            return $entityData->$key;
        }
        return $default;
    }

    protected function setUser($userData)
    {
        $this->setUserData('user', $userData);
        //$this->setUserData('userId', $userData['id']);

    }

    protected function getUser()
    {
        return $this->getUserData('user');
        //$this->setUserData('userId', $userData['id']);

    }

    protected function setUserAppAccess($userAppAccess) {
        $this->setUserData('app_access', $userAppAccess);
    }

    protected function getUserAppAccess() {
        return $this->getUserData('app_access');
    }


    public function setApplication($application)
    {
        $this->setUserData('application', $application);
    }

    public function getApplication()
    {
        $app = $this->getUserData('application');
        $apps = $this->getApps();
        if (($app === null || $app === '') && count($apps) === 1) {
            $app = current($apps);
            $this->setApplication($app);
        }

        return $app;
    }

    /**
     * Gets the apps that current user has access vía any role
     * @return array
     */
    protected function getApps(): array
    {
        $apps = [];
        $appAccess = $this->getUserAppAccess();
        if (is_array($appAccess) || is_object($appAccess)) {
            foreach ($appAccess as $app => $accessCompanies) {
                if (is_array($accessCompanies) && isset($accessCompanies['access']) && $accessCompanies['access']) {
                    $apps[] = $app;
                }
            }
        }
        return $apps;
    }

    protected function setCompany($companyUser): void
    {
        $role = $this->getEntityValue($companyUser, 'roles');
        $roleType = $this->getEntityValue($role, 'type');
        $abilities = $this->getEntityValue($role, 'abilities');

        // Batch: setUserData() hace session_write_close() por clave y con memcached
        // se puede perder compId entre escrituras.
        $this->setUserDataBatch([
            'compId' => (int) $this->getEntityValue($companyUser, 'company_id'),
            'role' => $roleType,
            'abilities' => $abilities,
            'company' => '1',
        ]);
        // Una sola persistencia a memcached con todas las claves.
        session_write_close();
    }

    protected function getCompany() {
        $compId = $this->getUserData('compId');
        if (!isset($compId)) {
            $companies = $this->getCompanies($this->getApplication());
            if (count($companies) == 1) {
                $compId = $companies[0];
                $this->setCompany($compId);
            }
        }
        return $compId;
    }

    protected function getCompanies($application) {
        $appAccess = $this->getUserAppAccess();
        return $appAccess[$application]['companies'] ?? [];
    }

    protected function registerAdmin(): void
    {
        $appAccess = $this->getUserAppAccess();
        if (is_array($appAccess) && array_key_exists('admin', $appAccess) && empty($appAccess['admin']['access'])) {
            return;
        }

        $userId = (int) $this->getUserId();
        if ($userId < 1) {
            return;
        }

        $this->setUserData('userId', $userId);
        $this->setUserData('admin', '1');
    }

    /**
     * language_id de companies (1=en … 7=zh) vía API.
     */
    protected function resolveCompanyLanguageIdFromApi(int $companyId): int
    {
        if ($companyId < 1) {
            return 1;
        }
        try {
            $company = $this->getObjectFromApi("company/{$companyId}");
            if (is_object($company) && isset($company->language_id)) {
                $id = (int) $company->language_id;
                if ($id >= 1 && $id <= 7) {
                    return $id;
                }
            }
            if (is_array($company) && isset($company['language_id'])) {
                $id = (int) $company['language_id'];
                if ($id >= 1 && $id <= 7) {
                    return $id;
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'resolveCompanyLanguageIdFromApi: ' . $e->getMessage());
        }

        return 1;
    }

    /**
     * Idioma del panel company: companies.language_id en sesión MVC.
     */
    protected function persistCompanyPanelLanguage(int $companyId, int $languageId): void
    {
        if ($languageId < 1 || $languageId > 7) {
            $languageId = 1;
        }
        $this->setUserDataBatch([
            'company_panel_language_id' => $languageId,
            'company_panel_language_comp_id' => $companyId,
            'company_panel_language_at' => time(),
            'language_id' => $languageId,
            'languageid' => $languageId,
        ]);
    }

    protected function registerManager($userLang,$userId): void {
        $this->setUserDataBatch([
            'company' => '1',
            'language_id' => $userLang,
            'user_logueado' => $userId,
        ]);
    }

    protected function registerSeller($userLang,$userId,$companyId): void
    {
        $seller = $this->getObjectFromApi("user/{$userId}/company/{$companyId}/seller");
        $this->setUserData('company_seller', $userId);
        $this->setUserData('sellerId', $seller->id);
        $this->setUserData('lang_seller', $userLang);
    }
    protected function registerCustomer($userLang,$userId, $companyId): void
    {
        $customer = $this->getObjectFromApi("user/{$userId}/company/{$companyId}/customer")->customer_id ?? null;

        $this->setUserData('marketplace_user', $userId);
        $this->setUserData('marketplace_customer_id', $customer);
        $this->setUserData('lang_marketplace', $userLang);

    }

    /**
     * Prepara sesión y URL de destino tras login (sin redirigir).
     *
     * @return array{sessionReady: bool, url: string|null, langId: int}
     */
    protected function prepareApplicationRedirect($selectedCompany, $userLang = 1): array
    {
        $result = [
            'sessionReady' => false,
            'url' => null,
            'langId' => (int) $userLang,
        ];

        if (! $this->loggedIn()) {
            return $result;
        }

        $application = $this->getApplication();
        if ($application === null || $application === '') {
            return $result;
        }

        $redirectto = $this->input->get('redirectto');
        if ($redirectto === null || $redirectto === '') {
            $redirectto = $this->input->post('redirectto');
        }
        $redirectUrl = null;
        $sessionReady = false;
        $resolvedLangId = (int) $userLang;
        $userId = $this->getUserId();
        $companyId = $this->resolveCompanyIdFromSelection($selectedCompany);

        if ($application === 'admin') {
            $this->registerAdmin();
            $redirectUrl = admin_url('dashboard');
            $sessionReady = true;
        } elseif ($companyId > 0) {
            if ($application === 'company') {
                $this->ensureCompanySessionFromSelection($selectedCompany);
                $companyLangId = $this->resolveCompanyLanguageIdFromApi($companyId);
                $this->persistCompanyPanelLanguage($companyId, $companyLangId);
                $this->registerManager($companyLangId, $userId);
                $this->recordCompanyPanelLoginLog($companyId, (int) $userId, $companyLangId);
                $redirectUrl = company_url('dashboard');
                $resolvedLangId = $companyLangId;
                $sessionReady = true;
            } elseif ($application === 'salesman') {
                $this->registerSeller($userLang, $userId, $companyId);
                $redirectUrl = seller_url();
                $sessionReady = true;
            } elseif ($application === 'marketplace') {
                $this->registerCustomer($userLang, $userId, $companyId);
                $CI = &get_instance();
                $CI->load->library('customer_portal_session');
                $companyIdInt = (int) $companyId;
                $portalOk = $companyIdInt > 0 && $CI->customer_portal_session->establish_for_auth_user(
                    (int) $userId,
                    $companyIdInt,
                    (int) $userLang
                );
                $redirectUrl = $portalOk ? site_url('customer/catalog') : marketplace_url('catalog');
                $sessionReady = true;
            }
        }

        if (! $sessionReady) {
            return $result;
        }

        if (! empty($redirectto)) {
            $decoded = urldecode((string) $redirectto);
            if ($this->getApplicationFromUrl($decoded) === $application) {
                $fallback = $redirectUrl ?? company_url('dashboard');
                if (method_exists($this, 'normalizeInternalRedirectUrl')) {
                    $redirectUrl = $this->normalizeInternalRedirectUrl($decoded, $fallback);
                } else {
                    $redirectUrl = $decoded;
                }
            }
        }

        $result['sessionReady'] = true;
        $result['url'] = $redirectUrl ?? company_url('dashboard');
        $result['langId'] = $resolvedLangId;

        return $result;
    }

    protected function redirectToApplication($selectedCompany, $userLang = 1): void
    {
        $result = $this->prepareApplicationRedirect($selectedCompany, $userLang);
        if (! $result['sessionReady'] || $result['url'] === null) {
            return;
        }

        redirect($result['url'], 'refresh');
        exit;
    }

    /**
     * Registra un inicio de sesión en el panel company (tabla logs).
     * No debe interrumpir el login si falla la escritura.
     */
    protected function recordCompanyPanelLoginLog(int $companyId, int $userId, int $langId = 2): void
    {
        if ($companyId < 1 || $userId < 1) {
            return;
        }

        $dbConfig = APPPATH.'config/'.ENVIRONMENT.'/database.php';
        if (! is_file($dbConfig)) {
            $dbConfig = APPPATH.'config/database.php';
        }
        if (! is_file($dbConfig)) {
            return;
        }

        try {
            $CI = &get_instance();
            if (! isset($CI->db) || $CI->db === null) {
                $CI->load->database();
            }

            $notes = 'Company panel login';
            if (function_exists('fv_lang_json_flat_section')) {
                $lang = fv_lang_json_flat_section($langId, 'cmp-admin-logs');
                $fromLang = trim((string) ($lang['note_login'] ?? ''));
                if ($fromLang !== '') {
                    $notes = $fromLang;
                }
            }

            $now = date('Y-m-d H:i:s');
            $CI->db->insert('logs', [
                'user_id' => $userId,
                'company_id' => $companyId,
                'log_type' => 'login',
                'notes' => $notes,
                'created_on' => $now,
                'updated_at' => $now,
            ]);
        } catch (Throwable $e) {
            if (function_exists('log_message')) {
                log_message('error', 'recordCompanyPanelLoginLog: ' . $e->getMessage());
            }
        }
    }

}
