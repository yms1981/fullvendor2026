<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Punto de entrada de la plataforma Admin.
 * URL: /admin y /admin/
 * Redirige a login si no hay sesión de admin, o al dashboard si ya está logueado.
 */
class Admin extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Entrada principal: /admin
     * Con sesión admin -> dashboard; sin sesión -> login.
     */
    public function index()
    {
        if ($this->isAdminLoggedIn()) {
            redirect(admin_url('dashboard'), 'refresh');
            return;
        }
        redirect(admin_url('login'), 'refresh');
    }

    /**
     * Comprueba si hay sesión de administrador (admin).
     */
    private function isAdminLoggedIn(): bool
    {
        return $this->hasUserData('userId') && $this->hasUserData('admin');
    }
}
