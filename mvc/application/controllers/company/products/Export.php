<?php

class Export extends Company_Controller
{
    use IsCRUD;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('product', 'company/product/', 'products');
    }

    public function index()
    {
        $params = $this->getParamsForBulkItems((object) $this->input->post());
        $params['type_file'] = $this->input->post('type_file');
        $params['delimiter'] = $this->input->post('separator');

        $export = $this->apiRawRequest("{$this->entityPath}/export", $params, 'POST', [
            'headers' => ['Accept' => '*/*'],
        ]);

        if ($this->hasError($export)) {
            $this->jsonError($export, 'Error exporting');
            return;
        }

        if (!is_object($export) || !method_exists($export, 'getHeader')) {
            $this->jsonError(['error' => 'export_failed'], 'Error exporting');
            return;
        }

        $disposition = $export->getHeader('Content-Disposition');
        if (!empty($disposition)) {
            header('Content-Disposition: ' . $disposition[0]);
        }

        $contentType = $export->getHeader('Content-Type');
        if (!empty($contentType)) {
            header('Content-Type: ' . $contentType[0]);
        }

        $contentLength = $export->getHeader('Content-Length');
        if (!empty($contentLength)) {
            header('Content-Length: ' . $contentLength[0]);
        }

        echo $export->getBody()->getContents();
        exit;
    }
}
