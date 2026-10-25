<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class SendCatalog extends Company_Controller
{
    public function index()
    {
        // Usar raw_input_stream de CI: php://input solo se puede leer bien una vez; un segundo file_get_contents queda vacío.
        $raw = $this->input->raw_input_stream;
        $postData = [];
        if (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $postData = $decoded;
            }
        }

        $response = $this->post("customer/bulkSendCatalog", $postData);

        if ($this->hasError($response)){
            $this->jsonError($response, 'Error sending catalog.');
        }

        $this->jsonSuccess($response['message']);
    }
}
