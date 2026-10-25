<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Delete extends Company_Controller
{
    use IsCRUD;
    use IsAction;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('user', 'company/user/', 'users');
        $dbLang = $this->getModuleLang('cmp-user');
        $fileLang = $this->getLangJsonSection('cmp-user');
        $this->langArr = array_merge($fileLang, is_array($dbLang) ? $dbLang : []);
    }

    public function run($user_id)
    {
        $msg = $this->langArr['user_delete_not_allowed'] ?? $this->langArr['delete_not_allowed'] ?? '';
        $this->jsonError(['message' => $msg], $msg !== '' ? $msg : 'Forbidden', 403);
    }
}
