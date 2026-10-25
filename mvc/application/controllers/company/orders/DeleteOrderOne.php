<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Elimina una línea de detalle de orden.
 *
 * Migración (2026): sustituye company/order/delete_orderOne (SQL en Order.php legacy).
 * Usa DELETE /api/v2/orderdetail/{id} y redirige al detalle del CRUD canónico orders/view.
 */
class DeleteOrderOne extends Company_Controller
{
    use IsAction;

    /** @var array<string, string> */
    protected $langArr = [];

    public function __construct()
    {
        parent::__construct();
        $this->langArr = $this->getModuleLang('cmp-orders');
    }

    public function run($orderDetailId = null)
    {
        $detailId = (int) ($orderDetailId ?? 0);
        if ($detailId < 1) {
            show_404();
        }

        $detail = $this->getObjectFromApi("orderdetail/{$detailId}");
        $orderId = 0;
        if (!$this->hasError($detail) && $detail) {
            $orderId = (int) ($detail->order_id ?? 0);
        }

        $response = $this->apiDelete("orderdetail/{$detailId}");
        if ($this->hasError($response)) {
            $msg = '["Error", "error", "#DD6B55"]';
            $this->flashMessage($msg);
        } else {
            $msg = '["' . ($this->langArr['eliminado_exito'] ?? 'Deleted') . '", "success", "#A5DC86"]';
            $this->flashMessage($msg);
        }

        if ($orderId > 0) {
            redirect(company_url('orders/view/' . $orderId), 'refresh');
        }

        redirect(company_url('orders'), 'refresh');
    }
}
