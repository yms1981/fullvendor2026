<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @property CI_Input $input
 */
class Application_Controller extends MY_Controller
{
    protected $benchmarkmessages = [];
    /** @var string app name to force change on session app */
    protected $forceAppChange = null;
    protected $accessControlRole = null;

    /** @var array the cached variables persisted on session for the application for speedup */
    protected $persisted = [];

    /** @var array specific translations for the controller */
    public $langArr = null;

    /** @var array full translations for the application */
    public $langDict = null;

    protected bool $displayBenchmarks = false;

    /** @var array<string, array<string, mixed>> diccionario por idioma, vivo en el proceso PHP */
    protected static array $runtimeLangDictionary = [];

    public function __construct()
    {
        parent::__construct();
        $this->checkLoggedInOrLoginWithRedirect();
        if ($this->isParallelUploadAjaxRequest()) {
            $headerToken = $this->input->get_request_header('X-FV-Upload-Token', true);
            if (is_string($headerToken) && preg_match('/^\d+\|[^\s\r\n]+$/', trim($headerToken))) {
                $this->userToken = trim($headerToken);
            } elseif (method_exists($this, 'resolveImageUploadApiToken')) {
                $resolvedToken = $this->resolveImageUploadApiToken();
                if ($resolvedToken !== '') {
                    $this->userToken = $resolvedToken;
                }
            }
            if (empty($this->userToken) && method_exists($this, 'getUserToken')) {
                $this->getUserToken();
            }
            // Cachear company_id ANTES de soltar el lock de sesión (memcached).
            if (method_exists($this, 'getCompanyId')) {
                $cachedCompanyId = (int) $this->getCompanyId();
                if ($cachedCompanyId < 1 && method_exists($this, 'getUserData')) {
                    $cachedCompanyId = (int) ($this->getUserData('compId') ?? 0);
                }
                if ($cachedCompanyId > 0) {
                    $this->fvCachedUploadCompanyId = $cachedCompanyId;
                }
            }
            $this->releaseSessionLockIfActive();
        } else {
            $this->getDictionary();
        }
    }

    /**
     * Peticiones AJAX de subida/importación en paralelo: evitar getDictionary() y liberar
     * el lock de sesión (memcached) para que no devuelvan HTML de login/error por contención.
     */
    protected function isParallelUploadAjaxRequest(): bool
    {
        if (defined('FV_STATELESS_IMAGE_UPLOAD') && FV_STATELESS_IMAGE_UPLOAD) {
            return true;
        }
        if (!isset($this->input) || !is_object($this->input) || !method_exists($this->input, 'is_ajax_request')) {
            return false;
        }
        if (!$this->input->is_ajax_request()) {
            return false;
        }
        $class = strtolower((string) ($this->router->class ?? ''));
        $method = strtolower((string) ($this->router->method ?? ''));
        if ($method === 'uploadimage') {
            return true;
        }
        // Importación de imágenes: endpoints AJAX ligeros (evitar getDictionary + lock de sesión).
        if (in_array($class, ['importimages', 'images'], true)
            && in_array($method, ['skuindex', 'uploadcredentials', 'import'], true)
        ) {
            return true;
        }

        return false;
    }

    protected function releaseSessionLockIfActive(): void
    {
        if (function_exists('session_write_close') && session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }

    protected function hasAccess(): bool
    {
        return !isset($this->accessControlRole) || $this->getRole() === $this->accessControlRole;
    }

    protected function getMethodNameForDataForView($view)
    {
        // Validar que $view sea un string antes de procesarlo
        if (!is_string($view) || empty($view)) {
            return null;
        }
        
        $viewPathItems = explode('/', $view);
        foreach ($viewPathItems as $k => $viewPathItem)
        {
            $viewPathItems[$k] = ucfirst($viewPathItem);
        }
        $viewNameCamelCase = implode('', $viewPathItems);
        return "getDataFor{$viewNameCamelCase}View";
    }

    protected function getDataForView($view=null, $dataForView = [])
    {
        $this->benchmark->mark('application_get_data_for_view_start');
        $dataForView['main_content'] = $view;
        $dataForView['setting'] = $this->getPlatformSettings();

        $dataForView['lang_json'] = $this->getDictionary();
        $dataForView['commonTexts'] = $this->getCommonTexts();
        $dataForView['jsFiles'] = $this->jsFiles;

        /** @var $getDataForThisView string the method name to get the data for this view named getDataFor{ViewCamelCaseFromPath}View */
        $getDataForThisView = $this->getMethodNameForDataForView($view);
        if($getDataForThisView !== null && method_exists($this, $getDataForThisView))
            $dataForView = $this->$getDataForThisView($dataForView);

        $this->benchmark->mark('application_get_data_for_view_end');
        return $dataForView;
    }

    /**
     * Obtiene un valor persistido o lo calcula y persiste si no existe
     *
     * @param string $key Clave para persistir el valor
     * @param callable $callback Función que retorna el valor a persistir si no existe
     * @return mixed El valor persistido o calculado
     */
    protected function getPersistedOrCompute(string $key, callable $callback)
    {
        return $this->getPersisted($key) ??
            $this->persist($key, $callback());
    }

    protected function getPersisted($name, $whatToPersistIfNotPersistedClousure=null) {
        if (array_key_exists($name, $this->persisted)) {
            return $this->persisted[$name];
        }
        $persisted = $this->getUserData($name);
        if (! empty($persisted)) {
            $this->persisted[$name] = $persisted;

            return $persisted;
        }
        if (isset($whatToPersistIfNotPersistedClousure)) {
            if (is_callable($whatToPersistIfNotPersistedClousure)) {
                $persisted = $whatToPersistIfNotPersistedClousure();
            } else {
                $persisted = $whatToPersistIfNotPersistedClousure;
            }
            $this->persist($name, $persisted);
        }

        return $persisted;
    }

    protected function persist($name, $value)
    {
        $this->persisted[$name] = $value;
        $this->setUserData($name, $value);
        return $value;
    }

    /**
     * Queries the backend the full app dictionary and stores it in the session
     *
     * @return array|mixed|null
     */
    protected function getDictionary()
    {
        $this->ensureLangmodelLoaded();
        $this->benchmark->mark('before_lang');

        $companyId = $this->getCompanyId();
        $currentLang = $this->getDefaultLanguage($companyId);
        if (isset(self::$runtimeLangDictionary[$currentLang]) && is_array(self::$runtimeLangDictionary[$currentLang])) {
            $this->langDict = self::$runtimeLangDictionary[$currentLang];
            $this->benchmark->mark('after_lang');

            return $this->langDict;
        }

        $dict = $this->getDictionaryFromApi();
        self::$runtimeLangDictionary[$currentLang] = is_array($dict) ? $dict : [];
        $this->langDict = self::$runtimeLangDictionary[$currentLang];
        $this->dropSessionLangDictionary();
        $this->benchmark->mark('after_lang');

        return $this->langDict;
    }

    /**
     * El diccionario completo no debe vivir en la cookie de sesión: pesa cientos de KB
     * y se reescribía en cada pantalla.
     */
    protected function dropSessionLangDictionary(): void
    {
        if ($this->getUserData('langDictionary') === null && $this->getUserData('langDictionary_en') === null) {
            return;
        }
        $clear = [
            'langDictionary' => null,
            'langDictionary_lang' => null,
        ];
        foreach (['en', 'es', 'pt', 'it', 'ru', 'de', 'zh'] as $loc) {
            $clear['langDictionary_'.$loc] = null;
        }
        $this->setUserDataBatch($clear);
        if (function_exists('session_write_close') && session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }

    protected function getModuleLang($moduleLangName) {
        $mdl = str_replace('-', '_', $moduleLangName);
        $languageDictionary = $this->langDict;
        if (! is_array($languageDictionary) || $languageDictionary === []) {
            $languageDictionary = $this->getDictionary();
        }
        
        // Verificar que el diccionario existe y tiene el módulo
        if (is_array($languageDictionary) && isset($languageDictionary[$mdl]['lang'])) {
            $moduleLang = $languageDictionary[$mdl]['lang'];
            if (is_array($moduleLang) && !empty($moduleLang)) {
                return $moduleLang;
            }
        }
        
        // Fallback: cargar el módulo directamente desde la API para no dejar botones/etiquetas sin traducir
        if (!empty($this->langmodel)) {
            $langId = 1;
            $compId = (int) ($this->getUserData('compId') ?? 0);
            if ($compId > 0 && function_exists('fv_resolve_company_language_id')) {
                $langId = fv_resolve_company_language_id();
            } elseif (function_exists('fv_resolve_session_language_id')) {
                $langId = fv_resolve_session_language_id();
            } elseif ($this->getUserData('language_id')) {
                $langId = (int) $this->getUserData('language_id');
            }
            $loaded = $this->langmodel->getMdl($moduleLangName, $langId);
            if (is_array($loaded) && !empty($loaded)) {
                return $loaded;
            }
        }

        if (method_exists($this, 'getLangJsonSection')) {
            return $this->getLangJsonSection($moduleLangName);
        }

        return [];
    }

    /**
     * Gets the html to view all benchmark messages (it could be override to add messages)
     *
     * @return string
     */
    protected function getBenchmarkMessagesContent()
    {
        return $this->load->view(
            "benchmark",
            ['benchmarkmessages' => $this->benchmarkmessages],
            true
        );
    }

    public function showContent($contentView, $data = [], $layout = 'base')
    {
        $vars = $this->getDataForView($contentView, $data);

        $this->benchmark->mark('load-view-layout-start');
        $content = $this->load->view("layouts/$layout", $vars, true);
        $this->benchmark->mark('show-content-end');
        if($this->displayBenchmarks)
            $content .= $this->getBenchmarkMessagesContent();
        echo $content;
    }

    public function checkLoggedInOrLoginWithRedirect()
    {
        parent::checkLoggedInOrLoginWithRedirect();
        if (isset($this->forceAppChange)) {
            $this->setApplication($this->forceAppChange);
        }
        if (!$this->hasAccess()) {
            $this->redirectToApplication($this->getCompany());
        }
    }

    protected function getCommonTexts(): array
    {
        try {
            if (isset($this->HelperLanguage) && is_object($this->HelperLanguage)) {
                return $this->HelperLanguage->get_language_head();
            } else {
                // Si HelperLanguage no está disponible, cargarlo
                $this->load->model('HelperLanguage');
                if (isset($this->HelperLanguage) && is_object($this->HelperLanguage)) {
                    return $this->HelperLanguage->get_language_head();
                }
            }
        } catch (\Exception $e) {
            log_message('error', 'Error in getCommonTexts: ' . $e->getMessage());
        }
        // Devolver array vacío como fallback
        return [];
    }


    protected function addBenchmarkMessage($point1, $point2,$name =null)
    {
        if(!isset($name)) $name = "$point1 - $point2";
        if(isset($this->benchmark->marker[$point2],$this->benchmark->marker[$point1]))
            $this->benchmarkmessages[] = "$name: " . $this->benchmark->elapsed_time($point1, $point2);
    }

    protected function jsonSuccess(string $message, array $data = [], int $code = 200)
    {
        header('Content-Type: application/json');
        http_response_code($code);
        echo json_encode(array_merge(['message' => $message], $data));
        exit;
    }

    /**
     * Normaliza respuestas de error de la API (422, etc.) para JSON o flash.
     *
     * @return array{message: string, errors: array, existing_user: mixed}
     */
    protected function unpackApiErrorResponse(array $response, string $defaultMessage): array
    {
        $message = $defaultMessage;
        $errors = [];
        $existingUser = null;

        if (isset($response['message'])) {
            if (is_array($response['message'])) {
                $message = $response['message']['message'] ?? $defaultMessage;
                if (! is_string($message)) {
                    $message = $defaultMessage;
                }
                $errors = $response['message']['errors'] ?? [];
                $existingUser = $response['message']['existing_user'] ?? null;
            } else {
                $message = $response['message'];
            }
        } elseif (isset($response['error'])) {
            $message = $response['error'];
        }

        if (isset($response['errors']) && empty($errors)) {
            $errors = $response['errors'];
        }
        if ($existingUser === null && isset($response['existing_user'])) {
            $existingUser = $response['existing_user'];
        }

        return ['message' => $message, 'errors' => $errors, 'existing_user' => $existingUser];
    }

    protected function jsonError(array $response, string $defaultMessage = 'An error has occurred.', int $code = 400)
    {
        $payload = $this->unpackApiErrorResponse($response, $defaultMessage);
        header('Content-Type: application/json');
        http_response_code($code);
        $out = ['message' => $payload['message'], 'errors' => $payload['errors']];
        if (array_key_exists('existing_user', $payload) && $payload['existing_user'] !== null) {
            $out['existing_user'] = $payload['existing_user'];
        }
        echo json_encode($out);
        exit;
    }

    public function getCurrency($company=null) {
        $company = $company ?? $this->getCompany();
        try {
            $currency = $this->cachedApiValue('currency:'.$company, 300, function () use ($company) {
                return $this->getObjectFromApi("company/{$company}/currency");
            });
        }
        catch (\GuzzleHttp\Exception\GuzzleException $e) {
            $currencies = $this->getObjectFromApi("currency");
            if(is_array($currencies) && count($currencies) > 0)
                $currency = $currencies[0];
        }
        return $currency;
    }

}
