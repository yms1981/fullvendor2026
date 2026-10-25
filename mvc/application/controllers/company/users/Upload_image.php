<?php

/**
 * Sube la imagen de usuario a MinIO vía API images (mismo flujo que productos/categorías).
 */
class Upload_image extends Company_Controller
{
    use IsAction;

    public function run(): void
    {
        if (empty($_FILES['contents']['tmp_name']) && empty($_FILES['images']['tmp_name'])) {
            $this->output->set_status_header(400);
            $this->output->set_content_type('application/json')->set_output(json_encode([
                'message' => 'No file',
            ]));

            return;
        }

        $file = ! empty($_FILES['contents']['tmp_name']) ? $_FILES['contents'] : $_FILES['images'];
        $tmpName = $file['tmp_name'];
        $origName = $file['name'] ?? 'avatar.jpg';
        // La API normaliza el binario (JPEG + tamaño); el nombre debe coincidir.
        $safeName = 'user_' . bin2hex(random_bytes(8)) . '.jpg';

        $companyId = (int) $this->getCompanyId();

        $multipart = [
            [
                'name' => 'contents',
                'contents' => fopen($tmpName, 'r'),
                'filename' => $origName,
            ],
            [
                'name' => 'name',
                'contents' => $safeName,
            ],
            [
                'name' => 'company_id',
                'contents' => (string) $companyId,
            ],
            [
                'name' => 'type',
                'contents' => 'user',
            ],
        ];

        try {
            $image = $this->apiRequest('images', 'POST', ['multipart' => $multipart], true);
        } catch (\Throwable $e) {
            log_message('error', 'users/Upload_image: ' . $e->getMessage());
            $this->output->set_status_header(500);
            $this->output->set_content_type('application/json')->set_output(json_encode([
                'message' => $e->getMessage(),
            ]));

            return;
        }

        if (is_array($image) && ! empty($image['error'])) {
            $this->output->set_status_header(502);
            $this->output->set_content_type('application/json')->set_output(json_encode($image));

            return;
        }

        $uuid = is_array($image) ? ($image['uuid'] ?? null) : (is_object($image) ? ($image->uuid ?? null) : null);
        $extension = is_array($image) ? ($image['extension'] ?? 'jpg') : (is_object($image) ? ($image->extension ?? 'jpg') : 'jpg');
        $extension = strtolower(ltrim((string) $extension, '.'));
        if ($extension === '' || $extension === 'jpeg') {
            $extension = 'jpg';
        }

        $this->output->set_content_type('application/json')->set_output(json_encode([
            'uuid' => $uuid,
            'name' => is_array($image) ? ($image['name'] ?? null) : (is_object($image) ? ($image->name ?? null) : null),
            'extension' => $extension,
            'mime_type' => is_array($image) ? ($image['mime_type'] ?? 'image/jpeg') : (is_object($image) ? ($image->mime_type ?? 'image/jpeg') : 'image/jpeg'),
            'size' => is_array($image) ? ($image['size'] ?? null) : (is_object($image) ? ($image->size ?? null) : null),
        ]));
    }
}
