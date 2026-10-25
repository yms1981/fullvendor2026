<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @property Usermodel $usermodel
 * @property CI_Encryption $encryption
 * @property CI_Form_validation $form_validation
 * @property CI_Input $input
 * @property Mymodel $mymodel
 * @property Langmodel $langmodel
 */
class Login extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('usermodel');
        $this->load->model('mymodel');
        $this->load->model('langmodel');
    }

    /**
     * @param $username
     * @return bool
     */
    protected function userExists($username)
    {
        return $this->usermodel->count('companies', "email = '" . $username . "' and deleted_at is null") == 1;
    }

    /**
     * @param $username
     * @param $password
     * @return Usermodel|false

     */
    protected function verifyUser($username, $password){
        $user = $this->usermodel->get_by('companies', true, "email = '" . $username . "' and deleted_at is null");
        if(password_verify($password, @$user->password))
            return $user;
        return false;
    }

    /**
     * @param $languageid
     * @return int
     */
    protected function getLanguageId(): int
    {
        $langid = 1;
        if ($this->hasUserData('languageid')) {
            $langid = $this->getUserData('languageid');
        }
        return $langid;
    }

    /**
     * @param $username
     * @param $password
     * @param $langid
     * @return mixed|string
     */
    protected function login($username, $password, $langid)
    {
        $msgs = $this->langmodel->get_langsinceLogin('login-languaje', $langid);
        $msgs = is_array($msgs) ? $msgs : (array) $msgs;

        if (! $this->userExists($username)) {
            return $msgs['username'] ?? '';
        }

        $user = $this->verifyUser($username, $password);
        if (! $user) {
            return $msgs['password'] ?? '';
        }

        if ((int) ($user->status ?? 0) === 0) {
            return $msgs['status'] ?? ($msgs['password'] ?? '');
        }

        $companyId = (int) (($user->createdby_comp_id ?? 0) == 0
            ? ($user->company_id ?? 0)
            : $user->createdby_comp_id);

        $this->load->library('company_plan_validator');
        $planMsgs = $this->company_plan_validator->loadLoginLangMessages((int) $langid);
        $msgs = array_merge($msgs, $planMsgs);

        $validation = $this->company_plan_validator->validateForLogin($companyId, $msgs);
        if ($validation !== '') {
            return $validation;
        }

        $this->setSessionData($user, $password);

        return "Login ";
    }

    /**
     * @return boolean
     */
    protected function isLoggedIn()
    {
        return $this->hasUserData('compId') && $this->hasUserData('company');
    }

    /**
     * Gets the Logout html message
     * @param $langId
     * @return object|string
     */
    protected function getLogoutMessageHtml($langId)
    {
        if (isset($langId) && $langId == 1) {
            $msg = "You have successfully logout!";
        } else {
            $msg = "¡Ha finalizado la sesión correctamente!";
        }
        return $this->getSuccessAlert($msg);
    }


    /**
     * @param Usermodel $user
     * @param $password
     * @return void
     */
    protected function setSessionData($user=null, $password=null): void
    {
        $company = $this->mymodel->get_by('companies', true, 'company_id =' . $user->company_id);

        $this->setUserData('company', '1');
        $this->setUserData('language_id', $company->language_id);
        $this->setUserData('user_logueado', $user->company_id);
        $this->setUserData('currency', $company->currency);

        if ($user->createdby_comp_id == 0) {
            $this->setUserData('compId', $user->company_id);
        } else {
            $this->setUserData('compId', $user->createdby_comp_id);
        }
        $this->setUserData('email', $user->email);
        parent::setSessionData($user, $password);
    }


    /**
     * @param $langid
     * @return void
     */
    protected function addFormValidationRules($langid): void
    {
        $labels = ['username' => 'Email', 'password' => 'Password'];
        $errors = ['username' => null, 'password' => null];

        if ($langid != 1) {
            $labels['username'] = 'Correo';
            $errors['username'] = array('required' => 'El campo  %s es requerido');

            $labels['password'] = 'Clave';
            $errors['password'] = array('required' => 'El campo  %s es requerido');
        }
        $this->form_validation->set_rules('username', $labels['username'], 'trim|required', $errors['username']);
        $this->form_validation->set_rules('password', $labels['password'], 'trim|required', $errors['password']);
    }


    /**
     * @param string $msg
     * @return string
     */
    protected function getFormErrorMessageHtml(): string
    {
        $msg = '';

        if (form_error('username')) {
            $msg .= strip_tags(form_error('username'));
        }

        if (form_error('password')) {
            $msg .= strip_tags(form_error('password'));
        }
        return $this->getDangerAlert($msg);
    }

    /**
     * @param string $message
     * @return string
     */
    protected function getDangerAlert(string $message): string
    {
        $safe = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));

        return $this->load->view('company/login/alert', ['msg' => $safe, 'type' => 'danger'], true);
    }

    /**
     * Revalida plan/límites para sesión company ya abierta; destruye sesión y redirige a /login si falla.
     */
    protected function enforceCompanyPlanForSession(int $companyId, int $langId): void
    {
        if ($companyId < 1) {
            return;
        }
        $this->load->library('company_plan_validator');
        $msgs = $this->company_plan_validator->loadLoginLangMessages($langId);
        $error = $this->company_plan_validator->validateForLogin($companyId, $msgs);
        if ($error === '') {
            return;
        }

        $safe = nl2br(htmlspecialchars($error, ENT_QUOTES, 'UTF-8'));
        $alert = $this->load->view('auth/alert_message', ['msg' => $safe, 'alertType' => 'danger'], true);

        $this->session->sess_destroy();
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        $this->session->set_flashdata('msg', $alert);
        session_write_close();

        redirect(base_url('login'), 'location');
        exit;
    }

    /**
     * @param string $message
     * @return object|string
     */
    protected function getSuccessAlert(string $message)
    {
        return $this->load->view('company/login/alert', ['msg' => $message, 'type' => 'success']);
    }

    /**
     * @return void
     */
    protected function moveForward(): void
    {
        $this->flash('loader', true);
        if ($this->input->get('redirectto')) {
            $url = urldecode($this->input->get('redirectto'));
        } else {
            $url = company_url('dashboard');
        }
        redirect($url, 'refresh');
    }

    /**
     * Translates a string using locale
     * @param $msg
     * @return string
     */
    public function traducir($msg)
    {
        $language = "es_ES";
        putenv("LC_ALL=$language");
        setlocale(LC_ALL, $language);
        bindtextdomain("messages", "./locale");
        textdomain("messages");

        return _($msg);
    }

    /**
     * /company/login está deprecado: la URL oficial del login empresa/admin es /login
     * (controlador Auth, vista auth/login.php con selector de idioma, traducciones
     * desde api/lang/*.json y validación Fortify). Cualquier acceso aquí (incluida
     * sesión ya iniciada y POSTs históricos) se reenvía a /login conservando
     * ?redirectto= y ?lang= si vinieran en la URL.
     *
     * @return void
     */
    public function index()
    {
        $query = [];
        $redirectto = $this->input->get('redirectto');
        if (! empty($redirectto)) {
            $query['redirectto'] = (string) $redirectto;
        }
        $lang = $this->input->get('lang');
        if (! empty($lang)) {
            $query['lang'] = (string) $lang;
        }
        $target = base_url('login') . ($query !== [] ? '?' . http_build_query($query) : '');
        redirect($target, 'location');
        exit;
    }

    /**
     * Clears session data (logs out)
     * @return void
     */
    public function logout()
    {
        $msg = $this->getLogoutMessageHtml($this->getUserData('language_id'));

        $this->session->sess_destroy();

        $this->flashMessage($msg);
        redirect(company_url('login'), 'refresh');
    }
}
