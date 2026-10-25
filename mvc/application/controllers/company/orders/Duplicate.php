<?php

class Duplicate extends Company_Controller
{
    use IsCRUD;
    use IsAction;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('order', 'company/order/', 'orders');
        $this->langArr = $this->getModuleLang('cmp-orders');
    }

    public function run($orderId = null)
    {
        $customer_id = $this->input->get('customer_id');
        if ($customer_id === null || $customer_id === '') {
            $this->flashMessage('["' . ($this->langArr['modal_duplicate_select_customer'] ?? 'Seleccione un cliente') . '", "warning", "#F8BB86"]');
            redirect(company_url("orders/view/{$orderId}"));
            return;
        }

        $response = $this->getObjectFromApi("{$this->entityPath}/{$orderId}/full");
        if ($this->hasError($response) || !$response) {
            $this->jsonError($response ?: ['message' => 'Order not found'], 'Error duplicating order.');
            return;
        }

        $order = is_object($response) && isset($response->data) ? $response->data : $response;
        if (is_array($order)) {
            $order = (object) json_decode(json_encode($order), false);
        }

        $details = $this->buildDetailsFromOrder($order);
        $internalNotes = $this->extractInternalNotes($order);

        $payload = [
            'customer_id' => $customer_id,
            'tipo_d'       => $order->tipo_d ?? 'D',
            'details'      => $details,
            'notes'        => $internalNotes,
        ];

        $encoded = urlencode(json_encode($payload));
        redirect(company_url("orders/add?duplicated={$encoded}"));
    }

    /**
     * Construye el array 'details' esperado por la vista order_add a partir de order_details de la API.
     */
    protected function buildDetailsFromOrder($order)
    {
        $details = [];
        $orderDetails = $order->order_details ?? $order->orderDetails ?? [];
        if (!is_array($orderDetails)) {
            $orderDetails = [];
        }

        foreach ($orderDetails as $d) {
            $detail = is_object($d) ? $d : (object) $d;
            $product = $detail->product ?? null;
            $productId = $detail->product_id ?? null;
            if (!$productId && $product) {
                $productId = $product->pro_id ?? $product->product_id ?? null;
            }
            if (!$productId) {
                continue;
            }

            $name = '';
            $sku = '';
            $image = '';
            if ($product) {
                $name = $product->name ?? $product->product_name ?? '';
                $sku = $product->sku ?? '';
                $image = $this->resolveProductImageUuid($product);
            }

            $details[] = [
                'id'          => $detail->detail_id ?? '',
                'product_id'  => $productId,
                'name'        => $name,
                'sku'         => $sku,
                'qty'         => (float) ($detail->qty ?? 0),
                'sale_price'  => (float) ($detail->sale_price ?? 0),
                'comments'    => $detail->comments ?? $detail->comment ?? '',
                'image'       => $image,
                'fixed_price' => !empty($detail->fixed_price) || !empty($detail->fixed_pricedate),
            ];
        }

        return $details;
    }

    /**
     * Extrae las notas internas de la orden (notes de tipo internal).
     */
    protected function extractInternalNotes($order)
    {
        $notes = $order->notes ?? [];
        if (!is_array($notes)) {
            return '';
        }
        foreach ($notes as $note) {
            $n = is_object($note) ? $note : (object) $note;
            if (isset($n->type) && (string) $n->type === 'internal' && isset($n->note)) {
                return trim((string) $n->note);
            }
        }
        return $order->internal_notes ?? $order->internal_comments ?? '';
    }
}
