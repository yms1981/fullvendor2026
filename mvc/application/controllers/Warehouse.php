<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * El controlador de compañía está en application/controllers/company/Warehouse.php.
 * Este fichero solo redirige peticiones antiguas /warehouse/* (sin prefijo company/)
 * hacia company/warehouse/*.
 */
class Warehouse extends CI_Controller
{
    /**
     * CodeIgniter 3: si existe _remap, se usa en lugar del método pedido por la URI.
     *
     * @param string $method
     * @param list<string> $params
     */
    public function _remap($method, $params = [])
    {
        $base = rtrim((string) $this->config->item('company_url'), '/');
        if ($method === 'index' && $params === []) {
            redirect($base . '/warehouse/lists', 'location', 302);
            return;
        }
        $tail = $method;
        if ($params !== []) {
            $tail .= '/' . implode('/', $params);
        }
        redirect($base . '/warehouse/' . $tail, 'location', 302);
    }
}
