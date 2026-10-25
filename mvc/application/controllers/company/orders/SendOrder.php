<?php

/**
 * Envío de orden por correo al cliente.
 *
 * Delega en POST /api/v2/order/sendOrder (OrderSendEmailService en Laravel).
 */
class SendOrder extends Company_Controller
{
    use IsCRUD;
    use IsAction;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('order', 'company/order/', 'orders');
        $this->langArr = $this->getModuleLang('cmp-orders');
    }

    public function run()
    {
        $data = $this->input->post();

        $response = $this->post("{$this->entityPath}/sendOrder", $data);

        if ($this->hasError($response)) {
            $this->jsonError($response, $this->langArr['email_send_failed'] ?? 'Error sending order.');
        }

        $message = is_array($response) && isset($response['message']) && is_string($response['message'])
            ? $response['message']
            : ($this->langArr['order_enviada'] ?? 'Order sent.');

        $this->jsonSuccess($message);
    }

}
