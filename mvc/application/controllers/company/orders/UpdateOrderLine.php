<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Actualiza cantidad/precio de una línea de orden (AJAX qtyonedit).
 *
 * Migración (2026): sustituye company/order/getquickprice (SQL directo en Order.php legacy).
 * Usa PUT/DELETE sobre /api/v2/orderdetail/{id} del flujo canónico /company/orders.
 */
class UpdateOrderLine extends Company_Controller
{
    use IsAction;

    public function run()
    {
        $detailId = (int) $this->input->post('detailId');
        $qty = $this->input->post('qqty');
        $salePrice = $this->input->post('qsaleprc');

        if ($detailId < 1) {
            echo json_encode([]);
            return;
        }

        $qtyFloat = (float) $qty;

        if ($qtyFloat == 0.0) {
            $this->apiDelete("orderdetail/{$detailId}");
            echo json_encode(['msg' => 0]);
            return;
        }

        $payload = [
            'qty' => $qtyFloat,
            'sale_price' => (float) $salePrice,
        ];

        $updated = $this->put("orderdetail/{$detailId}", $payload);
        if ($this->hasError($updated)) {
            echo json_encode([]);
            return;
        }

        $row = is_object($updated) ? $updated : (object) ($updated['data'] ?? $updated);
        $lineQty = (float) ($row->qty ?? $qtyFloat);
        $linePrice = (float) ($row->sale_price ?? $salePrice);
        $total = $lineQty * $linePrice;

        echo json_encode([
            'usaleprice' => $linePrice,
            'uqty' => $lineQty,
            'utprice' => $total,
        ]);
    }
}
