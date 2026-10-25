<?php

trait HandlesImageUpload
{
    protected function prepareImageData(array $postData, $inputName): array
    {
        if (isset($_FILES[$inputName]) && $_FILES[$inputName]['error'] === UPLOAD_ERR_OK) {
            $filePath = $_FILES[$inputName]['tmp_name'];
            $fileName = $_FILES[$inputName]['name'];
            $mimeType = $_FILES[$inputName]['type'];

            $postData['image'] = new CURLFile($filePath, $mimeType, $fileName);
        }

        return $postData;
    }
}
