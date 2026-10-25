<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Login extends MY_Controller
{

	public function __construct()
	{
		parent::__construct();
		$this->load->model('langmodel');
		$this->load->library('form_validation');
	}

	public function validate_credentials ($request, $extras = false, $password = true) {

		$request = $this->Helper->cast($request);
		$request->langId = $request->langId == 'en'? 1 /*español*/  : 2 /*english*/;
		$array_msgerror = array();
		$lang_validate = $this->langmodel->get_langsinceLogin('validate-user', $request->langId);

		if (!isset($request->username) && $request->username == '' || $request->username == null)
			array_push($array_msgerror,[ "name"=>"username" , "msg" => $lang_validate['user_is_required']] );

		if ($password) {
			if (!isset($request->password) && $request->password == '' || $request->password == null)
				array_push($array_msgerror,[ "name"=>"password" , "msg" => $lang_validate['password_is_required']]);
		}

		if (isset($request->customer)){
			if ($request->customer == '' || $request->customer == null) {
				array_push($array_msgerror,[ "name"=>"customer" , "msg" => $lang_validate['customer_is_required']]);
			}
		}

		if ($extras) {

			$field_required = false;
			if (!isset($request->password_repeat) && $request->password_repeat == '' || $request->password_repeat == null){
				$field_required = true;
				array_push($array_msgerror,[ "name"=>"password_repeat" , "msg" => $lang_validate['passwordrepeat_is_required']]);
			}

			if ($field_required == false) {
				if ($request->password !== $request->password_repeat)
					array_push($array_msgerror,[ "name"=>"password_repeat" , "msg" => $lang_validate['password_is_not_same']]);
			}


		}

		return $array_msgerror;
	}

	public function validations_extras($request , $lang_validate,$company = null, $customer = null){

		if (isset($request['company'])) {

			$company = $this->marketplaceFetchCompany((int) $request['company']);
			if (!$company) return ['errorvalidation'=>true,'errors'=>[
				[ "name"=>"company" , "msg" => $lang_validate['noexist_company']]
			]];
		}

		if (isset($request['customer'])) {
			$customer = $this->marketplaceFetchCustomer((int) $request['company'], (int) $request['customer']);
			if (!$customer) return ['errorvalidation'=>true,'errors'=>[
				[ "name"=>"username" , "msg" => $lang_validate['noexist_customer']]
			]];
		}

		return array();

	}

	public function forgot_password () {

		if ($this->hasUserData('marketplace_user') && $this->hasUserData('marketplace_customer_id')){
			return redirect(marketplace_url(),'refresh');
		}


		$this->removeUserData('marketplace_user');
		$this->removeUserData('marketplace_customer_id');

		$data = array('page' => 'login');
		$request = $this->input->get();
		$langid = isset($request['len']) && $request['len'] == 'en'? 1 /*english*/  : 2 /*español*/;
		$langid = !isset($request['len']) ? 1 : $langid;

		$lang_loginseller = $this->langmodel->get_langsinceLogin('comp-login-seller', $langid);


		$data['msg'] = $this->getFlashMessage();


		$data['settings'] = $this->getPlatformSettings();
		$data['langdef'] = $this->langmodel->get_langsinceLogin('company-login', $langid);
		$data['lang_validate'] = $this->langmodel->get_langsinceLogin('validate-user', $langid);
		$data['langid'] = $langid;
		$data['language'] = isset($request['len'])?$request['len']:'en';
		$data['lang_login'] = $lang_loginseller;
		$this->load->view('marketplace/forgot_password', $data);
	}

	public function register(){

		$request = $this->input->post();
		$lang_validate = $this->langmodel->get_langsinceLogin('validate-user', $request['langId']);

		$company = null; $customer = null;
		$repeat_customer = true;

		if (!isset($request['username']) && $request['username'] == '' || $request['username'] == null)
			return $this->Helper->response_json([ 'errorvalidation'=>true,'errors'=> [ ["name"=>"username" , "msg" => $lang_validate['user_is_required']]] ]);

		$result = $this->marketplaceLookupCustomers($request['username']);

		if (count($result) > 1){


			if (count($validaciones = $this->validate_credentials($request, true))>0) {
				return $this->Helper->response_json(['errorvalidation'=>true,'errors'=>$validaciones,"result" => $result ]);
			}

			if (count($validaciones = $this->validations_extras($request, $lang_validate, $company, $customer)) >= 1){
				return $this->Helper->response_json($validaciones);
			}

			if (isset($request['company'])) $company = $this->marketplaceFetchCompany((int) $request['company']);
			if (isset($request['customer'])) $customer = $this->marketplaceFetchCustomer((int) $request['company'], (int) $request['customer']);

		}

		// validar si el usuario es correcto y si el correo no se repite
		if (count($result) == 1){
			$company = $this->marketplaceFetchCompany((int) $result[0]->company_id);
			$repeat_customer = false;
		}

		$customer = count($result) == 1? $customer = $result[0]: $customer;

		if ($company && $customer) {

			$resutl_valid = $this->valid_companyAndcustomer($request,$lang_validate,null,$repeat_customer);
			//return  $this->Helper->response_json($resutl_valid);

			if (isset($resutl_valid['success']) && $resutl_valid['success'] = true){

				// procedemos a registrar
				$this->Helper->show_sessionmsg($lang_validate['register_success'], 'success');
				$this->marketplaceRegisterPassword((int) $company->company_id, (int) $customer->customer_id, (string) $request['password']);
				return $this->Helper->response_json(["success" => true, "login_url" => marketplace_url(), "repeat_customer" => $repeat_customer]);

			}

			// mostramos el error
			return  $this->Helper->response_json($resutl_valid);

		}

		$uniqueCompanyIds = $this->uniqueCompanyIds($result)[0];
		$customers_list = $this->getonly_customers($result);
		$newArray = $this->uniqueCompanyIds($result)[1];

		// no existe el customer
		if (count($customers_list)==0){
			return $this->Helper->response_json(['errorvalidation'=> true, "errors" => [
				[ "name"=>"username" , "msg" => $lang_validate['noexist_email']]
			]]);
		}

		// debe elegir a que empresa loguearse
		if (count($customers_list)>1){
			return $this->Helper->response_json([
				"success" => false,
				"select_customer"=>true,
				"select_company"=>true,
				"error" => "debe elegir a que empresa loguearse",
				"customers" => $customers_list,
				"companies" => $newArray,
				"repeat_customer" => $repeat_customer
			]);
		}


		return $this->Helper->response_json(["success" => false, "msg" => "error inesperado"]);

	}

	public function function_forgotpasswod(){

		$request = $this->input->post();
		$lang_validate = $this->langmodel->get_langsinceLogin('validate-user', $request['langId']);
		$repeat_customer = true;

		if (!isset($request['username']) && $request['username'] == '' || $request['username'] == null)
			return $this->Helper->response_json([ 'errorvalidation'=>true,'errors'=> [ ["name"=>"username" , "msg" => $lang_validate['user_is_required']]] ]);

		// validar si el usuario es correcto
		$company = null; $customer = null;
		$result = $this->marketplaceLookupCustomers($request['username']);

		if (count($result) > 1){

			if (count($validaciones = $this->validate_credentials($request, false,false))>0) {
				return $this->Helper->response_json(['errorvalidation'=>true,'errors'=>$validaciones]);
			}

			if (isset($request['company'])) $company = $this->marketplaceFetchCompany((int) $request['company']);
			if (isset($request['customer'])) $customer = $this->marketplaceFetchCustomer((int) $request['company'], (int) $request['customer']);

		}

		// validar si el usuario es correcto y si el correo no se repite
		if (count($result) == 1){
			$company = $this->marketplaceFetchCompany((int) $result[0]->company_id);
			$repeat_customer = false;
		}

		$customer = count($result) == 1? $customer = $result[0]: $customer;

		if ($company && $customer) {
			$resutl_valid = $this->valid_companyAndcustomer($request, $lang_validate);

			if (isset($resutl_valid['success']) && $resutl_valid['success'] = true){

				// if (!isset($request['g-recaptcha-response']) || $request['g-recaptcha-response'] == null || $request['g-recaptcha-response'] == null){

				// 	return $this->Helper->response_json(['errorvalidation'=>true,'errors'=>[
				// 		[
				// 		  "name"=>"not_robot",
				// 		  "msg" => $lang_validate['validate_captcha']
				// 		],
				// 		"repeat_customer" => $repeat_customer
				// 	]]);
				// }

				// procedemos a cambiar la contraseña
				$this->Helper->show_sessionmsg($lang_validate['email_send_success'], 'success');

				$new_password = $this->create_password();
				$plain = $this->marketplaceResetPassword((int) $company->company_id, (int) $customer->customer_id);
				if ($plain !== null) {
					$new_password = $plain;
				}

				$this->send_emailForgotPassword($lang_validate, $company, $customer, $new_password);
				return $this->Helper->response_json(["success" => true, "login_url" => marketplace_url(), "repeat_customer" => $repeat_customer, 'customer'=>$customer]);

			}
			// mostramos el error
			return  $this->Helper->response_json($resutl_valid);
		}


		$uniqueCompanyIds = $this->uniqueCompanyIds($result)[0];
		$customers_list = $this->getonly_customers($result);
		$newArray = $this->uniqueCompanyIds($result)[1];

		// no existe el customer
		if (count($customers_list)==0){
			return $this->Helper->response_json(['errorvalidation'=> true, "errors" => [
				[ "name"=>"username" , "msg" => $lang_validate['noexist_email']]
			]]);
		}

		// debe elegir a que empresa loguearse
		if (count($customers_list)>1){
			return $this->Helper->response_json([
				"success" => false,
				"select_customer"=>true,
				"select_company"=>true,
				"error" => "debe elegir a que empresa loguearse",
				"customers" => $customers_list,
				"companies" => $newArray,
				"repeat_customer" => $repeat_customer
			]);
		}


		return $this->Helper->response_json(["success" => false, "msg" => "error inesperado"]);

	}

	public function send_emailForgotPassword($lang_validate, $company, $customer, $new_password){

		$email_message = array(
			"lang_validate" => $lang_validate,
			"company"=> $company,
			"customer"=> $customer,
			"new_password" => $new_password,
		);
		$html = $this->load->view('marketplace/forgot_password_message_template', $email_message, TRUE);
        FullvendorEmailHelper::sendMailFromNoReply($customer->email,$lang_validate["restablecer_contraseña"],$html);
	}

	public function valid_companyAndcustomer ($request, $lang_validate, $url_redirect = null, $repeat_customer = true) {

		$url_redirect = $url_redirect ? $url_redirect : marketplace_url();

		// Validando Captcha
		if (!isset($request['g-recaptcha-response']) || $request['g-recaptcha-response'] == null || $request['g-recaptcha-response'] == null){

			return ['errorvalidation'=>true,'errors'=>[
				[
					"name" => "not_robot",
					"msg" => $lang_validate['validate_captcha'],// $lang_validate['noexist_customer']
					"repeat_customer" => $repeat_customer,
				]
			]];
		}


		return ["success" => true, "login_url" => $url_redirect, "repeat_customer" => $repeat_customer];


	}

	public function create_password($length = 6){
        // Caracteres posibles para la contraseña
        $caracteres = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";

        // Obtener la longitud total de los caracteres
        $total_caracteres = strlen($caracteres);

        // Inicializar la contraseña como una cadena vacía
        $password = "";

        // Generar la contraseña aleatoria
        for ($i = 0; $i < $length; $i++) {
            // Obtener un carácter aleatorio del conjunto de caracteres
            $caracter_aleatorio = $caracteres[rand(0, $total_caracteres - 1)];

            // Agregar el carácter a la contraseña
            $password .= $caracter_aleatorio;
        }

        // Devolver la contraseña generada
        return $password;
    }


	public function uniqueCompanyIds($result) {

		$uniqueCompanyIds = [];
		$newArray = [];
		foreach ($result as $key => $value) {

			$companyId = $value->company_id;
			// Verificar si 'company_id' ya ha sido agregado al nuevo array
			if (!in_array($companyId, $uniqueCompanyIds)) {
				$_logoUrl = company_logo_url((string) ($value->image ?? ''));
				$newArray[] = ['company_name' => $value->company_name,
								'company_id' => $value->company_id,
								'image' => $_logoUrl !== '' ? $_logoUrl : base_url('uploads/noimg.png')
							];

				$uniqueCompanyIds[] = $companyId; // Agregar 'company_id' a la lista de valores únicos
			}

		}

		return [$uniqueCompanyIds, $newArray];
	}

	public function getonly_customers ($result) {

		$customers_list = [];
		foreach ($result as $key => $value) {

			array_push($customers_list, [
				"name" => $value->name,
				"id" => $value->customer_id,
				'business_name' => $value->business_name,
				"company_image" => (company_logo_url((string) ($value->image ?? '')) ?: base_url('uploads/noimg.png')),
				"company_name" => $value->company_name,
				"id_comp" => $value->company_id,
				"lang" => $value->lang
			]);
		}

		return $customers_list;
	}

	public function register_customer () {

		if ($this->hasUserData('marketplace_user') && $this->hasUserData('marketplace_customer_id')){
			return redirect(marketplace_url(),'refresh');
		}

		$this->removeUserData('marketplace_user');
		$this->removeUserData('marketplace_customer_id');

		$data = array('page' => 'login');
		$request = $this->input->get();
		$langid = isset($request['len']) && $request['len'] == 'en'? 1 /*english*/  : 2 /*español*/;
		$langid = !isset($request['len']) ? 1 : $langid;

		$lang_loginseller = $this->langmodel->get_langsinceLogin('comp-login-seller', $langid);


		$data['msg'] = $this->getFlashMessage();


		$data['settings'] = $this->getPlatformSettings();
		$data['langdef'] = $this->langmodel->get_langsinceLogin('company-login', $langid);
		$data['lang_validate'] = $this->langmodel->get_langsinceLogin('validate-user', $langid);
		$data['langid'] = $langid;
		$data['language'] = isset($request['len'])?$request['len']:'en';
		$data['lang_login'] = $lang_loginseller;

		$this->load->view('marketplace/register', $data);

	}

	public function sign_in(){

		$request = $this->input->post();
		$lang_validate = $this->langmodel->get_langsinceLogin('validate-user', $request['langId']);

		if (!isset($request['username']) && $request['username'] == '' || $request['username'] == null)
			return $this->Helper->response_json([ 'errorvalidation'=>true,'errors'=> [ ["name"=>"username" , "msg" => $lang_validate['user_is_required']]] ]);


		$this->removeUserData('marketplace_user');
		$this->removeUserData('marketplace_customer_id');

		// si viene el company
		$company = null;
		$customer = null;

		// validar si el usuario es correcto
		$result = $this->marketplaceLookupCustomers($request['username']);
		$repeat_customer = true;

		if (count($result) > 1){

			// validaciones generales
			if (count($validaciones = $this->validate_credentials($request))>0) {
				return $this->Helper->response_json(['errorvalidation'=>true,'errors'=>$validaciones]);
			}

			if (isset($request['company'])) {

				$company = $this->marketplaceFetchCompany((int) $request['company']);
				if (!$company) return $this->Helper->response_json(['errorvalidation'=>true,'errors'=>[
					["name"=>"company" , "msg" => $lang_validate['noexist_company'], "result" => $result]
				]]);

				if(!$company->status) return $this->Helper->response_json(['errorvalidation'=>true,'errors'=>[
					["name"=>"username" , "msg" => "Company is not enabled"]
				]]);
			}

			if (isset($request['customer'])) {
				// valida password y username
				$customer = $this->marketplaceFetchCustomer((int) $request['company'], (int) $request['customer']);
				if (!$customer) return $this->Helper->response_json(['errorvalidation'=>true,'errors'=>[
					[ "name"=>"username" , "msg" => $lang_validate['noexist_customer']]
				]]);

				if(!$customer->customer_status) return $this->Helper->response_json(['errorvalidation'=>true,'errors'=>[
					[ "name"=>"username" , "msg" => "Customer is not enabled"]
				]]);
			}
		}

		if (count($result) == 1){
			$company = $this->marketplaceFetchCompany((int) $result[0]->company_id);
			$repeat_customer = false;
		}

		$customer = count($result) == 1? $customer = $result[0]: $customer;

		if (($company && $customer)) {

			if (!isset($request['g-recaptcha-response']) || $request['g-recaptcha-response'] == null || $request['g-recaptcha-response'] == null){
				return $this->Helper->response_json(['errorvalidation'=>true,'errors'=>
					[
						[
						  "name"=>"not_robot",
						  "msg" => $lang_validate['validate_captcha']
						],
					],
					"repeat_customer" => $repeat_customer,
				]);
			}

			$signIn = $this->marketplaceSignIn($request);
			if ($signIn === null) {
				return $this->Helper->response_json(["success" => false, 'errorvalidation'=> true, "errors" => [
					["name"=>"password", "msg" => $lang_validate['password_incorrect']],
				], "repeat_customer" => $repeat_customer]);
			}

			if (($signIn['result'] ?? '') === 'invalid_password') {
				return $this->Helper->response_json(["success" => false, 'errorvalidation'=> true, "errors" => [
					["name"=>"password", "msg" => $lang_validate['password_incorrect']],
				], "repeat_customer" => $repeat_customer]);
			}

			if (($signIn['result'] ?? '') === 'company_disabled') {
				return $this->Helper->response_json(['errorvalidation'=>true,'errors'=>[
					["name"=>"username" , "msg" => "Company is not enabled"]
				]]);
			}

			if (($signIn['result'] ?? '') === 'success') {
				$this->flash('loader', true);
				$this->setUserData('marketplace_user', (int) $signIn['company_id']);
				$this->setUserData('marketplace_customer_id', (int) $signIn['customer_id']);
				$this->setUserData('lang_marketplace', (int) $signIn['language_id']);
				$this->setUserData('session_cart', (string) $signIn['session_token']);

				return $this->Helper->response_json(["success" => true, "login_url" => marketplace_url('shop'), "repeat_customer" => $repeat_customer]);
			}
		}

		$customers_list = [];
		$newArray = [];
		$uniqueCompanyIds = [];

		foreach ($result as $key => $value) {
			$companyId = $value->company_id;
			// Verificar si 'company_id' ya ha sido agregado al nuevo array
			if (!in_array($companyId, $uniqueCompanyIds)) {
				$_logoUrl2 = company_logo_url((string) ($value->image ?? ''));
				$_smallUrl = ! empty($value->small_image) ? company_logo_url((string) $value->small_image) : '';
				$newArray[] = ['company_name' => $value->company_name,
								'company_id' => $value->company_id,
								'image' => $_logoUrl2 !== '' ? $_logoUrl2 : base_url('uploads/noimg.png'),
                                'small_image' => $_smallUrl !== '' ? $_smallUrl : null
							];

				$uniqueCompanyIds[] = $companyId; // Agregar 'company_id' a la lista de valores únicos
			}
		}

		foreach ($result as $key => $value) {

			array_push($customers_list, [
				"name" => $value->name,
				"id" => $value->customer_id,
				'business_name' => $value->business_name,
				"company_image" => (company_logo_url((string) ($value->image ?? '')) ?: base_url('uploads/noimg.png')),
				"company_name" => $value->company_name,
				"id_comp" => $value->company_id,
				"lang" => $value->lang,

			]);
		}

		// no existe el customer
		if (count($customers_list)==0){
			return $this->Helper->response_json(['errorvalidation'=> true, "errors" => [
				[ "name"=>"username" , "msg" => $lang_validate['noexist_email']]
			]]);
		}

		// debe elegir a que empresa loguearse
		if (count($customers_list)>1){

			usort($newArray, function($a, $b) {
				return strcmp($a['company_name'], $b['company_name']);
			});

			usort($customers_list, function($a, $b) {
				return strcmp($a['name'], $b['name']);
			});

			return $this->Helper->response_json([
				"success" => false,
				"select_customer"=>true,
				"select_company"=>true,
				"error" => "debe elegir a que empresa loguearse",
				"customers" => $customers_list,
				"companies" => $newArray,
				"repeat_customer" => $repeat_customer
			]);
		}


		return $this->Helper->response_json(["success" => false, "msg" => "error inesperado"]);
	}

	public function get_validationUser(){


	}


	public function validate_captcha($recaptchaToken){

		$secretKey = '6Lcnp2QpAAAAAJto8dAK8lRZnX4CVv6gyKhxeEYh';

        $url = 'https://www.google.com/recaptcha/api/siteverify';// Verificar el token con la API de reCAPTCHA

		$data = array(
            'secret' => $secretKey,
            'response' => $recaptchaToken,
        );

		$options = array(
            'http' => array(
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data),
            ),
        );

		$context = stream_context_create($options);
		$result = file_get_contents($url, false, $context);
		$jsonResult = json_decode($result, true);

		if ($jsonResult['success']) return true;

		return false;
	}



	public function index(){

		if ($this->hasUserData('marketplace_user') && $this->hasUserData('marketplace_customer_id')){
			return redirect(marketplace_url(),'refresh');
		}

		$this->removeUserData('marketplace_user');
		$this->removeUserData('marketplace_customer_id');

		$data = array('page' => 'login');
		$request = $this->input->get();
		$locale = strtolower(substr(trim((string) ($request['len'] ?? 'es')), 0, 2));
		if ($locale === '') {
			$locale = 'es';
		}
		$langid = function_exists('fv_locale_to_language_id')
			? fv_locale_to_language_id($locale)
			: (($locale === 'en') ? 1 : 2);

		$lang_loginseller = $this->langmodel->get_langsinceLogin('comp-login-seller', $langid);

		$data['msg'] = $this->getFlashMessage();

		$data['settings'] = $this->getPlatformSettings();
		$data['langdef'] = $this->langmodel->get_langsinceLogin('company-login', $langid);
		$data['lang_validate'] = $this->langmodel->get_langsinceLogin('validate-user', $langid);
		$data['langid'] = $langid;
		$data['language'] = $locale;
		$data['landingLocale'] = function_exists('fv_language_id_to_locale')
			? fv_language_id_to_locale($langid)
			: $locale;
		$data['mp'] = function_exists('fv_lang_json_flat_section')
			? fv_lang_json_flat_section($langid, 'public-marketplace')
			: [];
		if (! is_array($data['mp'])) {
			$data['mp'] = [];
		}
		$data['lang_login'] = $lang_loginseller;
		$this->load->view('marketplace/login', $data);
	}

	/**
	 * @return list<object>
	 */
	private function marketplaceLookupCustomers(string $email): array
	{
		$email = trim($email);
		if ($email === '') {
			return [];
		}
		$resp = $this->post('marketplace/customers/lookup', ['email' => $email], true);
		if (! is_array($resp) || $this->hasError($resp) || ! isset($resp['data']['customers']) || ! is_array($resp['data']['customers'])) {
			return [];
		}

		return $this->marketplaceRowsToObjects($resp['data']['customers']);
	}

	private function marketplaceFetchCompany(int $companyId): ?object
	{
		if ($companyId < 1) {
			return null;
		}
		$resp = $this->getDataFromApi('marketplace/customers/company/' . $companyId, [], true);
		if (! is_array($resp) || $this->hasError($resp) || ! isset($resp['data'])) {
			return null;
		}

		return is_object($resp['data']) ? $resp['data'] : (object) $resp['data'];
	}

	private function marketplaceFetchCustomer(int $companyId, int $customerId): ?object
	{
		if ($companyId < 1 || $customerId < 1) {
			return null;
		}
		$resp = $this->getDataFromApi('marketplace/customers/company/' . $companyId . '/customer/' . $customerId, [], true);
		if (! is_array($resp) || $this->hasError($resp) || ! isset($resp['data'])) {
			return null;
		}

		return is_object($resp['data']) ? $resp['data'] : (object) $resp['data'];
	}

	/**
	 * @param array<string, mixed> $request
	 * @return array<string, mixed>|null
	 */
	private function marketplaceSignIn(array $request): ?array
	{
		$payload = [
			'username' => (string) ($request['username'] ?? ''),
			'password' => (string) ($request['password'] ?? ''),
		];
		if (isset($request['company']) && $request['company'] !== '') {
			$payload['company'] = (int) $request['company'];
		}
		if (isset($request['customer']) && $request['customer'] !== '') {
			$payload['customer'] = (int) $request['customer'];
		}

		$resp = $this->post('marketplace/customers/sign-in', $payload, true);
		if (! is_array($resp) || $this->hasError($resp) || ! isset($resp['data']) || ! is_array($resp['data'])) {
			return null;
		}

		return $resp['data'];
	}

	private function marketplaceRegisterPassword(int $companyId, int $customerId, string $plainPassword): bool
	{
		$resp = $this->post('marketplace/customers/register-password', [
			'company' => $companyId,
			'customer' => $customerId,
			'password' => $plainPassword,
		], true);

		return is_array($resp) && ! $this->hasError($resp);
	}

	private function marketplaceResetPassword(int $companyId, int $customerId): ?string
	{
		$resp = $this->post('marketplace/customers/reset-password', [
			'company' => $companyId,
			'customer' => $customerId,
		], true);
		if (! is_array($resp) || $this->hasError($resp) || ! isset($resp['data']['password'])) {
			return null;
		}

		return (string) $resp['data']['password'];
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

	public function complogin($username, $password, $langid){

		$msgs = $this->langmodel->get_langsinceLogin('login-languaje', $langid);
		$msg = is_array($msgs) ? ($msgs['username'] ?? '') : '';

		$result = $this->marketplaceLookupCustomers((string) $username);
		if (count($result) !== 1) {
			return $msg;
		}

		$row = $result[0];
		$signIn = $this->marketplaceSignIn([
			'username' => $username,
			'password' => $password,
			'company' => (int) $row->company_id,
			'customer' => (int) $row->customer_id,
		]);

		if ($signIn === null || ($signIn['result'] ?? '') === 'invalid_password') {
			return is_array($msgs) ? ($msgs['password'] ?? $msg) : $msg;
		}

		if (($signIn['result'] ?? '') !== 'success') {
			return is_array($msgs) ? ($msgs['status'] ?? $msg) : $msg;
		}

		$this->setUserData('marketplace_user', (int) $signIn['company_id']);
		$this->setUserData('marketplace_customer_id', (int) $signIn['customer_id']);
		$this->setUserData('lang_marketplace', (int) $signIn['language_id']);
		$this->setUserData('session_cart', (string) ($signIn['session_token'] ?? ''));

		return "";
	}

	public function alert_msg($msg){

		$alert = '<div class="alert alert-danger alert-dismissable"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a><strong><i class="fa fa-check-circle-o"></i></strong>%s</div>';
		return sprintf($alert, $msg,$msg);
	}


	public function changelanguage(){

		$langid = $this->input->post('language_id');
		$usertype = $this->input->post('usertype');
		$_SESSION['language_id'] = $langid;
		//$_SESSION['usertype'] = $usertype;

		if (count($_POST)>0){
			redirect(marketplace_url('login'));
			return;
		}

		$langdef = $this->langmodel->getMdl('company-login', $langid);
		$data['langdef'] = $langdef;
		$data['langid'] = $langid;
		//$data['settings'] = $this->mymodel->get('settings', true, 'settingId', '1');
		$this->load->view('marketplace/login', $data);
	}






}
