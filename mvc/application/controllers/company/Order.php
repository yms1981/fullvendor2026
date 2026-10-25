<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Puente legacy: /company/order/* → flujo canónico /company/orders/* (API).
 *
 * Sustituye el controlador monolítico (~2500 líneas SQL). GET redirige; POST reenvía
 * al controlador canónico equivalente para no romper enlaces antiguos.
 */
class Order extends Company_Controller
{
    /** @var array<string, string> segmento legacy → ruta relativa bajo company/orders */
    private const GET_REDIRECTS = [
        'lists' => 'orders',
        'order_details' => 'orders/view',
        'view' => 'orders/view',
        'edit' => 'orders/edit',
        'add' => 'orders/add',
        'new_credit_note' => 'orders/add',
        'combine_order' => 'orders',
        'editorder' => 'orders',
        'downloadOrderCSV' => 'orders/CSVExport',
        'approveStatus' => 'orders/approve',
        'order_duplicate' => 'orders/duplicate',
    ];

    /** @var array<string, array{0: string, 1: string}> legacy method → [controller, method] */
    private const POST_FORWARDS = [
        'enviar_orden_email' => ['SendOrder', 'run'],
        'save' => ['Save', 'run'],
        'update' => ['Update', 'run'],
        'cambiar_status' => ['ChangeStatus', 'run'],
        'change_statusOrder' => ['ChangeStatus', 'run'],
        'change_status' => ['ChangeStatus', 'run'],
        'get_historial' => ['GetHistorial', 'run'],
        'add_newItem' => ['AddOrderLine', 'run'],
        'delete_orderOne' => ['DeleteOrderOne', 'run'],
        'getquickprice' => ['UpdateOrderLine', 'run'],
        'price_update' => ['UpdateOrderLine', 'run'],
        'assigned_warehouse' => ['AssignWarehouse', 'run'],
        'generar_order_catalog' => ['GenerateCatalogOrder', 'run'],
        'obtener_formulario' => ['ProductDetails', 'run'],
        'combine_orderSuccess' => ['CombineOrders', 'index'],
        'validar_ordenescondescuento' => ['CombinePreview', 'index'],
        'approveStatus' => ['Approve', 'run'],
        'order_duplicate' => ['Duplicate', 'run'],
        'getCustomer' => ['GetCustomer', 'run'],
        'print_order' => ['GeneratePdf', 'index'],
        'buscar_productoSelect2' => ['SearchProductSelect2', 'index'],
    ];

    public function _remap($method, array $params = [])
    {
        if ($method === 'index' || $method === '') {
            redirect(company_url('orders'));
            return;
        }

        if ($this->input->method(true) === 'POST') {
            $this->forwardPost($method, $params);
            return;
        }

        if (isset(self::GET_REDIRECTS[$method])) {
            $base = self::GET_REDIRECTS[$method];
            $target = company_url($base . (empty($params) ? '' : '/' . implode('/', $params)));
            redirect($target);
            return;
        }

        redirect(company_url('orders'));
    }

    /**
     * @param array<int, string> $params
     */
    private function forwardPost(string $method, array $params): void
    {
        if (isset(self::POST_FORWARDS[$method])) {
            [$controller, $action] = self::POST_FORWARDS[$method];
            $this->invokeOrdersController($controller, $action, $params);
            return;
        }

        if (in_array($method, ['add_newItem', 'delete_orderOne', 'approveStatus', 'order_duplicate', 'update'], true) && ! empty($params[0])) {
            $_POST['order_id'] = $_POST['order_id'] ?? $params[0];
            $map = [
                'add_newItem' => ['AddOrderLine', 'run'],
                'delete_orderOne' => ['DeleteOrderOne', 'run'],
                'approveStatus' => ['Approve', 'run'],
                'order_duplicate' => ['Duplicate', 'run'],
                'update' => ['Update', 'run'],
            ];
            [$controller, $action] = $map[$method];
            $this->invokeOrdersController($controller, $action, $params);
            return;
        }

        show_error('Legacy order endpoint deprecated. Use /company/orders.', 410);
    }

    /**
     * @param array<int, string> $params
     */
    private function invokeOrdersController(string $controller, string $action, array $params = []): void
    {
        if ($controller === 'SearchProductSelect2') {
            require_once APPPATH . 'controllers/company/products/SearchProductSelect2.php';
            $instance = new SearchProductSelect2();
        } else {
            require_once APPPATH . 'controllers/company/orders/' . $controller . '.php';
            $instance = new $controller();
        }

        if (! empty($params) && method_exists($instance, $action)) {
            call_user_func_array([$instance, $action], $params);
            return;
        }

        if (method_exists($instance, $action)) {
            $instance->$action();
            return;
        }

        if (method_exists($instance, 'run')) {
            $instance->run(...$params);
        }
    }
}
