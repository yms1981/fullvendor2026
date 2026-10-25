<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Añade o incrementa una línea de orden (flujo depósito / warehouse).
 *
 * Migración (2026): sustituye company/order/add_newItem (SQL en Order.php legacy).
 */
class AddOrderLine extends Company_Controller
{
    use IsAction;

    /** @var array<string, string> */
    protected $langArr = [];

    public function __construct()
    {
        parent::__construct();
        $this->langArr = $this->getModuleLang('cmp-orders');
    }

    public function run()
    {
        $orderId = (int) $this->input->post('order_id');
        $productId = (int) $this->input->post('product_id');
        $qty = (float) str_replace([',', ' '], ['.', ''], (string) $this->input->post('qty'));
        $redirectWarehouse = (bool) $this->input->post('redirect_warehouse');

        if ($orderId < 1 || $productId < 1 || $qty <= 0) {
            $this->flashAddLineResult(false, $orderId, $redirectWarehouse);

            return;
        }

        $product = $this->getObjectFromApi("product/{$productId}");
        if ($this->hasError($product) || ! is_object($product)) {
            $this->flashAddLineResult(false, $orderId, $redirectWarehouse);

            return;
        }

        $salePrice = (float) ($product->sale_price ?? 0);
        $existing = $this->getObjectFromApi("order/{$orderId}/product/{$productId}");
        $ok = false;

        if (! $this->hasError($existing) && is_object($existing) && ! empty($existing->detail_id)) {
            $detailId = (int) $existing->detail_id;
            $newQty = (float) ($existing->qty ?? 0) + $qty;
            $updated = $this->put("orderdetail/{$detailId}", [
                'qty' => $newQty,
                'sale_price' => (float) ($existing->sale_price ?? $salePrice),
            ]);
            $ok = ! $this->hasError($updated);
        } else {
            $created = $this->post('orderdetail', [
                'order_id' => $orderId,
                'product_id' => $productId,
                'qty' => $qty,
                'discount_type' => 1,
                'sale_price' => $salePrice,
                'fob_price' => (float) ($product->fob_price ?? 0),
                'purchase_price' => (float) ($product->purchase_price ?? 0),
            ]);
            $ok = ! $this->hasError($created);
        }

        $this->flashAddLineResult($ok, $orderId, $redirectWarehouse);
    }

    private function flashAddLineResult(bool $ok, int $orderId, bool $redirectWarehouse): void
    {
        if ($ok) {
            $msg = $this->langArr['item_added_success'] ?? $this->langArr['order_saved'] ?? 'OK';
            $this->session->set_flashdata('msg', $msg);
        } else {
            $msg = $this->langArr['error_orden'] ?? 'Error';
            $this->session->set_flashdata('msg', $msg);
        }

        if ($redirectWarehouse && $orderId > 0) {
            redirect(company_url('warehouse/warehouse_orderview/' . $orderId), 'refresh');

            return;
        }

        if ($orderId > 0) {
            redirect(company_url('orders/view/' . $orderId), 'refresh');

            return;
        }

        redirect(company_url('orders'), 'refresh');
    }
}
