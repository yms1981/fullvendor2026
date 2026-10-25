<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Verifica la contraseña del usuario logeado antes de permitir eliminar órdenes.
 * POST password → JSON { valid: true|false, message?: string }
 */
class VerifyDeletePassword extends Company_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->langArr = $this->getModuleLang('cmp-orders');
    }

    /**
     * POST password → verifica contra el usuario de sesión (companies).
     * Responde JSON: { valid: true|false, message?: string }
     */
    public function index()
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($this->input->method(true) !== 'POST') {
            $this->output->set_output(json_encode([
                'valid' => false,
                'message' => $this->langArr['invalid_request'] ?? 'Invalid request.',
            ]));
            return;
        }

        $password = $this->input->post('password');
        if ($password === null || $password === '') {
            $this->output->set_output(json_encode([
                'valid' => false,
                'message' => $this->langArr['delete_password_required'] ?? 'Debe ingresar su contraseña.',
            ]));
            return;
        }

        $email = $this->getUserData('email');
        if (empty($email)) {
            $this->output->set_output(json_encode([
                'valid' => false,
                'message' => $this->langArr['session_expired'] ?? 'Sesión no válida.',
            ]));
            return;
        }

        $user = $this->mymodel->get_by('companies', true, ['email' => $email, 'deleted_at' => null]);
        if (!$user || !isset($user->password)) {
            $this->output->set_output(json_encode([
                'valid' => false,
                'message' => $this->langArr['session_expired'] ?? 'Sesión no válida.',
            ]));
            return;
        }

        if (!password_verify($password, $user->password)) {
            $this->output->set_output(json_encode([
                'valid' => false,
                'message' => $this->langArr['delete_password_incorrect'] ?? 'Contraseña incorrecta.',
            ]));
            return;
        }

        $this->output->set_output(json_encode([
            'valid' => true,
            'message' => $this->langArr['delete_password_ok'] ?? 'Contraseña correcta.',
        ]));
    }
}
