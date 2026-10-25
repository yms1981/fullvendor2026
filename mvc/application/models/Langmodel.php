<?php

use GuzzleHttp\Client;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property Client $http
 * @property Helper $Helper
 */
class Langmodel extends MY_Model {


    public $company = array();

    function __construct()
	{
		parent::__construct();
        $this->load->model('CompanyModel');
		//$this->company = $this->Helper->get_company_info();
    }

    private function companyModel()
    {
        $CI = &get_instance();
        if (! isset($CI->CompanyModel)) {
            $CI->load->model('CompanyModel');
        }

        return $CI->CompanyModel;
    }

    public function get_langsinceLogin($anymdl = null, $lang_id = null) {
        $CI = &get_instance();
        if ($lang_id !== null && $lang_id !== '') {
            $langId = (int) $lang_id;
        } elseif ((int) ($CI->session->userdata('compId') ?? 0) > 0 && function_exists('fv_resolve_company_language_id')) {
            $langId = fv_resolve_company_language_id();
        } else {
            $langId = fv_resolve_session_language_id();
        }
        $locale = fv_language_id_to_locale($langId);

        return $this->getDataFromApi("langmdl/{$anymdl}", ['locale' => $locale]);
    }

    public function getMdl($anymdl = null, $langid = null){

        $CI = &get_instance();
        $companyId = is_object($CI) && method_exists($CI, 'session')
            ? $CI->session->userdata('compId')
            : null;

        if ($langid !== null && $langid !== '') {
            $langId = (int) $langid;
        } else {
            $compId = is_object($CI) && method_exists($CI, 'session')
                ? (int) ($CI->session->userdata('compId') ?? 0)
                : 0;
            if ($compId > 0 && function_exists('fv_resolve_company_language_id')) {
                $langId = fv_resolve_company_language_id();
            } else {
                $langId = fv_resolve_session_language_id();
            }
        }
        $locale = fv_language_id_to_locale($langId);

        if (is_string($anymdl) && $anymdl !== '' && function_exists('fv_lang_json_flat_section')) {
            $fromJson = fv_lang_json_flat_section($langId, $anymdl);
            if ($fromJson !== []) {
                return $fromJson;
            }
        }

        // Pasar company_id y locale en la query string para que TranslationController lo use
        $params = ['locale' => $locale];
        if ($companyId) {
            $params['company_id'] = $companyId;
        }
        
        return $this->getDataFromApi("langmdl/{$anymdl}", $params);
    }

    public function get_lang($anymdl = null, $id_company = null, $language_id = null){

        if ($language_id !== null && $language_id !== '') {
            $locale = fv_language_id_to_locale((int) $language_id);
        } else {
            $company = $this->companyModel()->getCompany($id_company);

            if ($company && isset($company->language_id)) {
                $locale = fv_language_id_to_locale((int) $company->language_id);
            } else {
                $CI = &get_instance();
                $compId = is_object($CI) && method_exists($CI, 'session')
                    ? (int) ($CI->session->userdata('compId') ?? 0)
                    : 0;
                if ($compId > 0 && function_exists('fv_resolve_company_language_id')) {
                    $locale = fv_language_id_to_locale(fv_resolve_company_language_id());
                } else {
                    $locale = fv_language_id_to_locale(fv_resolve_session_language_id());
                }
            }
        }

        return $this->getDataFromApi("langmdl/{$anymdl}", ['company_id' => $id_company, 'locale' => $locale]);
    }
}
