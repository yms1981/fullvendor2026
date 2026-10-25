<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class GeneratePdf extends Company_Controller
{
    public function index()
    {
        $orderId = $this->input->post('order_id');
        $includeLocations = $this->input->post('include_locations');
        $withNotes = $this->input->post('notas') === 'con';
        $deliveryNotes = $this->input->post('delivery_notes');

        if (!$orderId) {
            show_404();
        }

        $payload = [
            'include_locations' => (bool) $includeLocations,
            'delivery_notes' => $withNotes ? $deliveryNotes : null,
        ];

        // PDF con imágenes puede superar el timeout Guzzle por defecto (30s).
        $response = $this->apiRawRequest("order/{$orderId}/generatePdf", $payload, 'POST', [
            'timeout' => 180.0,
            'connect_timeout' => 20.0,
        ]);

        if ($this->hasError($response)) {
            $this->jsonError(is_array($response) ? $response : ['error' => true, 'message' => 'Error generating PDF'], 'Error generating PDF');
            return;
        }

        if (!is_object($response) || !method_exists($response, 'getHeaderLine')) {
            $this->jsonError(['error' => true, 'message' => 'Invalid PDF response'], 'Error generating PDF');
            return;
        }

        $disposition = $response->getHeaderLine('Content-Disposition');
        if ($disposition === '') {
            $disposition = 'inline';
        }
        if (stripos($disposition, 'attachment') !== false) {
            $disposition = 'inline' . (preg_match('/filename="([^"]+)"/', $disposition, $m) ? '; filename="' . $m[1] . '"' : '');
        }

        $contentType = $response->getHeaderLine('Content-Type');
        if ($contentType === '') {
            $contentType = 'application/pdf';
        }

        $body = $response->getBody()->getContents();
        $contentLength = $response->getHeaderLine('Content-Length');
        if ($contentLength === '' && is_string($body)) {
            $contentLength = (string) strlen($body);
        }

        header('Content-Disposition: ' . $disposition);
        header('Content-Type: ' . $contentType);
        if ($contentLength !== '') {
            header('Content-Length: ' . $contentLength);
        }
        $acceptRanges = $response->getHeaderLine('Accept-Ranges');
        if ($acceptRanges !== '') {
            header('Accept-Ranges: ' . $acceptRanges);
        }

        echo $body;
    }
}
