<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class ExportImages extends Company_Controller
{
    use IsCRUD;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('product', 'company/product/', 'products');
        $this->langArr = $this->getModuleLang('cmp-products');
    }

    public function index()
    {
        $post = (object) $this->input->post();
        // getBulkItems solo trata "todos" si viene input_todos/all; el JS manda products=todos.
        if ((string) ($post->products ?? '') === 'todos') {
            $post->input_todos = 'todos';
        }
        $params = $this->getParamsForBulkItems($post);

        @set_time_limit(300);

        $export = $this->apiRawRequest("{$this->entityPath}/export-images", $params, 'POST', [
            'headers' => ['Accept' => '*/*'],
            'timeout' => 300.0,
            'connect_timeout' => 30.0,
        ]);

        if ($this->hasError($export)) {
            $errorCode = $this->extractExportImagesErrorCode($export);
            $message = $this->resolveExportImagesErrorMessage($errorCode);
            $this->jsonError(
                ['error' => $errorCode !== '' ? $errorCode : 'product_images_export_failed'],
                $message
            );

            return;
        }

        if (! is_object($export) || ! method_exists($export, 'getHeader')) {
            $this->jsonError(
                ['error' => 'export_failed'],
                $this->resolveExportImagesErrorMessage('product_images_export_failed')
            );

            return;
        }

        $disposition = $export->getHeader('Content-Disposition');
        if (! empty($disposition)) {
            header('Content-Disposition: '.$disposition[0]);
        } else {
            header('Content-Disposition: attachment; filename="product_images_'.date('Ymd_His').'.zip"');
        }

        $contentType = $export->getHeader('Content-Type');
        if (! empty($contentType)) {
            header('Content-Type: '.$contentType[0]);
        } else {
            header('Content-Type: application/zip');
        }

        $contentLength = $export->getHeader('Content-Length');
        if (! empty($contentLength)) {
            header('Content-Length: '.$contentLength[0]);
        }

        echo $export->getBody()->getContents();
        exit;
    }

    /**
     * @param mixed $export
     */
    private function extractExportImagesErrorCode($export): string
    {
        if (! is_array($export)) {
            return 'product_images_export_failed';
        }

        $payload = $export;
        if (isset($export['message'])) {
            if (is_string($export['message'])) {
                $decoded = json_decode($export['message'], true);
                if (is_array($decoded)) {
                    $payload = $decoded;
                }
            } elseif (is_array($export['message'])) {
                $payload = $export['message'];
            }
        }

        $code = (string) ($payload['error'] ?? '');
        if ($code === '' || in_array($code, ['Client error', 'Server error', 'Request error', 'Unknown error'], true)) {
            return 'product_images_export_failed';
        }

        return $code;
    }

    private function resolveExportImagesErrorMessage(string $errorCode): string
    {
        $lang = is_array($this->langArr) ? $this->langArr : [];
        $map = [
            'product_images_export_no_selection' => 'export_images_no_selection',
            'product_images_export_empty' => 'export_images_empty',
            'product_images_export_too_many' => 'export_images_too_many',
            'product_images_export_failed' => 'export_images_failed',
            'zip_extension_missing' => 'export_images_failed',
            'zip_open_failed' => 'export_images_failed',
            'temp_file_failed' => 'export_images_failed',
        ];
        $key = $map[$errorCode] ?? 'export_images_failed';

        return (string) ($lang[$key] ?? $lang['export_images_failed'] ?? '');
    }
}
