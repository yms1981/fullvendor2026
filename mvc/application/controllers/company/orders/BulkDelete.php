<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * API de eliminación masiva de órdenes.
 * POST order_ids[] → elimina vía API y devuelve JSON { success, deleted, failed, message }.
 */
class BulkDelete extends Company_Controller
{
    public function __construct()
    {
        parent::__construct();
        $langId = $this->getUserData('language_id');
        $this->langArr = $this->langmodel->getMdl('cmp-orders', $langId);
        if (!is_array($this->langArr)) {
            $this->langArr = $this->getModuleLang('cmp-orders');
        }
    }

    /**
     * POST order_ids[] → elimina cada orden con la API y responde JSON.
     */
    public function index()
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($this->input->method(true) !== 'POST') {
            $this->output->set_output(json_encode([
                'success' => false,
                'deleted' => 0,
                'failed' => 0,
                'message' => $this->langArr['alert_select_at_least_one'] ?? 'Seleccione al menos una orden.',
            ]));
            return;
        }

        $orderIds = $this->input->post('order_ids');
        if (!is_array($orderIds)) {
            $orderIds = $orderIds !== null && $orderIds !== '' ? [$orderIds] : [];
        }
        $orderIds = array_filter(array_map('intval', $orderIds));
        if (empty($orderIds)) {
            $this->output->set_output(json_encode([
                'success' => false,
                'deleted' => 0,
                'failed' => 0,
                'message' => $this->langArr['alert_select_at_least_one'] ?? 'Seleccione al menos una orden.',
            ]));
            return;
        }

        $deleted = 0;
        $failed = 0;
        foreach ($orderIds as $orderId) {
            if ($orderId <= 0) {
                continue;
            }
            $resp = $this->apiDelete("order/{$orderId}");
            if (!$this->hasError($resp)) {
                $deleted++;
            } else {
                $failed++;
            }
        }

        $message = '';
        if ($deleted > 0) {
            $message = $deleted === 1
                ? ($this->langArr['order_deleted_success'] ?? 'Orden eliminada correctamente.')
                : sprintf($this->langArr['orders_deleted_success'] ?? 'Se eliminaron %d orden(es) correctamente.', $deleted);
        }
        if ($failed > 0) {
            $msgErr = sprintf($this->langArr['orders_delete_error'] ?? 'No se pudieron eliminar %d orden(es).', $failed);
            $message = $message ? $message . ' ' . $msgErr : $msgErr;
        }
        if ($message === '' && $deleted === 0 && $failed > 0) {
            $message = $this->langArr['orders_delete_error'] ?? 'No se pudieron eliminar las órdenes.';
        }

        $this->output->set_output(json_encode([
            'success' => $deleted > 0,
            'deleted' => $deleted,
            'failed' => $failed,
            'message' => $message,
        ]));
    }
}
