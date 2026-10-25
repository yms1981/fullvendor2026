<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @property Langmodel $langmodel
 */
class HelperLanguage extends CI_Model {

    public ?int $company_id;
    public ?int $langId;
    public $company_info = array();

    function __construct()
	{
		parent::__construct();
        $this->company_id = $this->session->userdata('compId');
    }

    private function currentLangId(): int
    {
        $compId = (int) ($this->session->userdata('compId') ?? 0);
        if ($compId > 0 && function_exists('fv_resolve_company_language_id')) {
            return fv_resolve_company_language_id();
        }
        if (function_exists('fv_resolve_session_language_id')) {
            return fv_resolve_session_language_id();
        }
        $id = (int) ($this->session->userdata('language_id') ?? $this->session->userdata('languageid') ?? 1);

        return ($id >= 1 && $id <= 7) ? $id : 1;
    }

    private function langmodelInstance()
    {
        $CI = &get_instance();
        if (! isset($CI->langmodel)) {
            $CI->load->model('langmodel');
        }

        return $CI->langmodel;
    }

    public function catalog(){
        return $this->langmodelInstance()->getMdl('cmp-catalog', $this->currentLangId());
    }

    public function get_language_head(){
        return $this->langmodelInstance()->getMdl('comp-login-head', $this->currentLangId());
    }

    public function lang_terms(){
        return $this->langmodelInstance()->getMdl('cmp-terms', $this->currentLangId());
    }

    public function lang_perfil(){
        return $this->langmodelInstance()->getMdl('cmp-perfil', $this->currentLangId());
    }

    public function lang_botones(){
        return $this->langmodelInstance()->getMdl('cmp-botones', $this->currentLangId());
    }

    public function errors_products(){
        return $this->langmodelInstance()->getMdl('cmp-errores-products', $this->currentLangId());
    }

    public function lang_customer(){
        return $this->langmodelInstance()->getMdl('cmp-clientes', $this->currentLangId());
    }

    public function lang_products(){
        return $this->langmodelInstance()->getMdl('cmp-products', $this->currentLangId());
    }

    public function lang_enabled()
    {
        return $this->langmodelInstance()->getMdl('cmp-enabled', $this->currentLangId());
    }

    public function lang_langtermino(){
        return $this->langmodelInstance()->getMdl('errores_termino', $this->currentLangId());
    }

    public function lang_erroresunits(){
        return $this->langmodelInstance()->getMdl('errores_unit', $this->currentLangId());
    }

    public function lang_units(){
        return $this->langmodelInstance()->getMdl('unit-languaje', $this->currentLangId());
    }

    public function lang_groups(){
        return $this->langmodelInstance()->getMdl('cmp-group', $this->currentLangId());
    }

    public function lang_cart(){
        return $this->langmodelInstance()->getMdl('cmp-cart', $this->currentLangId());
    }

    public function lang_order(){
        return $this->langmodelInstance()->getMdl('cmp-orders', $this->currentLangId());
    }

    public function lang_categorias(){
        return $this->langmodelInstance()->getMdl('cmp-categorias', $this->currentLangId());
    }

    public function modal(){
        return $this->langmodelInstance()->getMdl('cmp-modal', $this->currentLangId());
    }

    public function errores_catalog(){
        return $this->langmodelInstance()->getMdl('errores_catalog', $this->currentLangId());
    }


    public function errores_productos(){
        return $this->langmodelInstance()->getMdl('errores_producto', $this->currentLangId());
    }

    public function status_active(){
        return $this->langmodelInstance()->getMdl('cmp-status', $this->currentLangId());
    }

    public function status(){
        return $this->langmodelInstance()->getMdl('cmp_status', $this->currentLangId());
    }

    public function option_basic(){
        return $this->langmodelInstance()->getMdl('cmp-option_basic', $this->currentLangId());
    }

    public function meses(){
        return $this->langmodelInstance()->getMdl('cmp-meses', $this->currentLangId());
    }

    public function modal_exportLayout(){
        return $this->langmodelInstance()->getMdl('cmp-modal-export-layout', $this->currentLangId());
    }

    public function inventory(){
        return $this->langmodelInstance()->getMdl('cmp-inventory', $this->currentLangId());
    }

    public function mensajes(){
        return $this->langmodelInstance()->getMdl('cm-mensajes', $this->currentLangId());
    }

    public function errors_import_images(){
        return $this->langmodelInstance()->getMdl('cm-import-images', $this->currentLangId());
    }

    public function currency(){
        return $this->langmodelInstance()->getMdl('cm-currency', $this->currentLangId());
    }

    public function dashboard(){
        return $this->langmodelInstance()->getMdl('cm-dashboard', $this->currentLangId());
    }

    public function user_validate(){
        return $this->langmodelInstance()->getMdl('cmp-user', $this->currentLangId());
    }

    public function tooltip(){
        return $this->langmodelInstance()->getMdl('cmp-toltip', $this->currentLangId());
    }

    public function profile_vendor(){
        return $this->langmodelInstance()->getMdl('cmp-profile-vendor', $this->currentLangId());
    }

    public function warehouse(){
        return $this->langmodelInstance()->getMdl('cmp-warehouse', $this->currentLangId());
    }

    public function login_seller() {
        return $this->langmodelInstance()->getMdl('comp-login-seller', $this->currentLangId());
    }


    public function lang_navigation(){
        return $this->langmodelInstance()->getMdl('company-navigation', $this->currentLangId());
    }

}
