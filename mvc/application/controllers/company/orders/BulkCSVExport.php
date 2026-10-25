<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class bulkCSVExport extends Company_Controller
{
    public function index()
    {
        $postData = json_decode(file_get_contents('php://input'), true);
        $postData['company_id'] = $this->getCompanyId();

        $export = $this->apiRawRequest("order/bulkExport", $postData, 'POST');

        if ($this->hasError($export)){
            $this->jsonError($export, 'Error exporting csv.');
        }

        header('Content-Disposition: ' . $export->getHeader('Content-Disposition')[0]);
        header('Content-Type: ' . $export->getHeader('Content-Type')[0]);
        header('Content-Length: ' . $export->getHeader('Content-Length')[0]);
        header('Accept-Ranges: ' . $export->getHeader('Accept-Ranges')[0]);
        echo $export->getBody()->getContents();
    }
}
