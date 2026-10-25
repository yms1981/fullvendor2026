<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Mensajes traducidos para acciones CRUD compartidas (switches, toggles, etc.).
 */
trait CrudActionMessages
{
    protected function getCrudToolsLang(): array
    {
        if (method_exists($this, 'getConfigCrudLang')) {
            $lang = $this->getConfigCrudLang();
            if (is_array($lang) && !empty($lang)) {
                return $lang;
            }
        }

        if (method_exists($this, 'getLangJsonSection')) {
            $lang = $this->getLangJsonSection('config-crud-tools');
            if (is_array($lang)) {
                return $lang;
            }
        }

        return [];
    }

    protected function getCrudToolsMessage(string $key): string
    {
        $lang = $this->getCrudToolsLang();
        $msg = trim((string) ($lang[$key] ?? ''));

        return $msg;
    }
}
