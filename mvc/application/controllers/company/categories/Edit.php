<?php
require_once APPPATH . 'controllers/company/categories/Form.php';

class Edit extends Form
{
    use IsAction;
    use HandlesImageUpload;
    use SupportsCategoryValidationMessages;

    public function run($categoryId=null)
    {
        // Si es POST, ejecutar la lógica de actualización
        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            // Obtener categoryId de la URL o del POST
            if (!$categoryId) {
                $categoryId = $this->input->post('cat_id');
            }
            if (!$categoryId) {
                // Intentar obtener de la URL
                $segments = $this->uri->segment_array();
                $editIndex = array_search('edit', $segments);
                if ($editIndex !== false && isset($segments[$editIndex + 1])) {
                    $categoryId = $segments[$editIndex + 1];
                }
            }
            
            if (!$categoryId) {
                $this->jsonError(
                    ['error' => 'missing_category_id'],
                    $this->langArr['category_save_error_generic'] ?? '',
                    422
                );
                return;
            }

            $existing = $this->getObjectFromApi("{$this->entityPath}/{$categoryId}", [
                'props' => ['cat_id', 'category_default'],
            ]);
            if (! $this->hasError($existing) && (int) ($existing->category_default ?? 0) === 1) {
                $this->jsonError(
                    ['error' => 'default_category'],
                    $this->langArr['error_edit'] ?? '',
                    400
                );
                return;
            }

            $postData = $this->input->post();
            $company = $this->Helper->get_company_info();
            
            // Preparar los datos del formulario
            $updateData = [
                'company_id' => $company->company_id,
                'category_name' => $postData['category_name'] ?? '',
                'category_status' => isset($postData['category_status']) ? (int)$postData['category_status'] : 1
            ];

            // Solo procesar la imagen si se subió un archivo nuevo
            $imageInputName = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $imageInputName = 'image';
            } elseif (isset($_FILES['category_image']) && $_FILES['category_image']['error'] === UPLOAD_ERR_OK) {
                $imageInputName = 'category_image';
            }

            // Solo llamar a updateImage si hay una nueva imagen (elimina la anterior y sube la nueva)
            if ($imageInputName) {
                log_message('debug', "Edit::run - Nueva imagen detectada: {$imageInputName}");
                log_message('debug', "Edit::run - Procesando imagen para categoría {$categoryId}");

                try {
                    $updateData = $this->prepareImageData($updateData, $imageInputName);

                    // Liberar sesión antes del HTTP a la API (puede tardar).
                    // Evita deadlock: workers Apache esperando el lock mientras Guzzle
                    // reentra por localhost/api → ProxyPass.
                    if (method_exists($this, 'releaseSessionLockIfActive')) {
                        $this->releaseSessionLockIfActive();
                    } elseif (function_exists('session_write_close') && session_status() === PHP_SESSION_ACTIVE) {
                        session_write_close();
                    }
                    
                    if (isset($updateData['image']) && $updateData['image'] instanceof \CURLFile) {
                        $imagePostData = ['image' => $updateData['image']];
                        log_message('debug', "Edit::run - Nueva imagen preparada para subir");
                        log_message('debug', "Edit::run - Llamando a category/{$categoryId}/updateImage");

                        // Llamar a updateImage (elimina la anterior y sube la nueva)
                        $imageResponse = $this->multipartPost(
                            "category/{$categoryId}/updateImage",
                            $imagePostData,
                            true
                        );

                        log_message('debug', "Edit::run - Respuesta API imagen: " . json_encode($imageResponse));

                        if (isset($imageResponse['error'])) {
                            log_message('error', "Edit::run - Error al procesar imagen: " . json_encode($imageResponse));
                            $this->jsonError(
                                $imageResponse,
                                $this->langArr['category_save_error_generic'] ?? ''
                            );
                            return;
                        }

                        log_message('debug', "Edit::run - Imagen procesada exitosamente");
                    } else {
                        log_message('warning', "Edit::run - No se pudo preparar la imagen como CURLFile");
                    }
                } catch (\GuzzleHttp\Exception\GuzzleException $e) {
                    log_message('error', "Edit::run - Error Guzzle al procesar imagen: " . $e->getMessage());
                    $this->jsonError(
                        ['error' => $e->getMessage()],
                        $this->langArr['category_save_error_generic'] ?? ''
                    );
                    return;
                } catch (\Exception $e) {
                    log_message('error', "Edit::run - Error general al procesar imagen: " . $e->getMessage());
                    $this->jsonError(
                        ['error' => $e->getMessage()],
                        $this->langArr['category_save_error_generic'] ?? ''
                    );
                    return;
                }
            } else {
                log_message('debug', "Edit::run - No hay nueva imagen, se mantendrá la imagen actual");
                if (method_exists($this, 'releaseSessionLockIfActive')) {
                    $this->releaseSessionLockIfActive();
                } elseif (function_exists('session_write_close') && session_status() === PHP_SESSION_ACTIVE) {
                    session_write_close();
                }
            }

            // La imagen se actualiza vía updateImage (multipart). El PUT solo acepta image como UUID string.
            unset($updateData['image']);

            // Actualizar la categoría usando la API
            try {
                $updateResponse = $this->put("{$this->entityPath}/{$categoryId}", $updateData);

                if ($this->hasError($updateResponse)) {
                    $name = trim((string) ($updateData['category_name'] ?? ''));
                    $updateResponse = $this->localizeCategoryApiErrorResponse(
                        is_array($updateResponse) ? $updateResponse : [],
                        $name
                    );
                    $defaultMsg = $this->formatCategoryNameExistsMessage($name);
                    $this->jsonError(
                        $updateResponse,
                        $defaultMsg !== '' ? $defaultMsg : ($this->langArr['category_exists'] ?? ''),
                        422
                    );
                    return;
                }

                $successMsg = $this->langArr['category_updated']
                    ?? $this->langArr['category_success']
                    ?? '';
                $this->jsonSuccess($successMsg);
            } catch (\Exception $e) {
                log_message('error', "Edit::run - Error al actualizar categoría: " . $e->getMessage());
                $this->jsonError(['error' => $e->getMessage()], 'Error al actualizar la categoría');
            }
            return;
        }

        // Si es GET, mostrar el formulario (lógica original)
        if (!$categoryId) {
            show_404();
            return;
        }

        $category = $this->getObjectFromApi("{$this->entityPath}/{$categoryId}", ['relations' => ['imageRecord']]);

        if ($this->hasError($category)) {
            if ($this->input->is_ajax_request()) {
                $this->output->set_status_header(404);
                $this->output->set_content_type('text/html', 'utf-8');
                echo '<div class="alert alert-danger">'
                    . htmlspecialchars((string) ($this->langArr['load_form_error'] ?? $this->langArr['category_save_error_generic'] ?? ''), ENT_QUOTES, 'UTF-8')
                    . '</div>';
                return;
            }
            show_404();
            return;
        }

        if ((int) ($category->category_default ?? 0) === 1) {
            if ($this->input->is_ajax_request()) {
                $this->output->set_status_header(403);
                $this->output->set_content_type('text/html', 'utf-8');
                echo '<div class="alert alert-warning">'
                    . htmlspecialchars((string) ($this->langArr['error_edit'] ?? ''), ENT_QUOTES, 'UTF-8')
                    . '</div>';
                return;
            }
            show_error($this->langArr['error_edit'] ?? '', 403);
            return;
        }

        // Tras leer token/sesión para la API, liberar lock para no bloquear otras pestañas/AJAX.
        if (function_exists('session_write_close') && session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $data['category'] = $category;
        
        // Preparar pageTexts con todas las traducciones necesarias del módulo
        $pageTexts = array_merge(
            $this->getLangJsonSection('cmp-toltip'),
            is_array($this->langArr) ? $this->langArr : []
        );
        
        // Asegurar que las claves necesarias para el modal estén presentes
        // Estas claves deben existir en api/lang/*.json (módulo de categorías)
        if (!isset($pageTexts['titulo_editar'])) {
            $pageTexts['titulo_editar'] = $pageTexts['edit'] ?? '';
        }
        if (!isset($pageTexts['boton_cancelar'])) {
            $pageTexts['boton_cancelar'] = $pageTexts['cancel'] ?? $pageTexts['cancelar'] ?? '';
        }
        if (!isset($pageTexts['boton_enviar'])) {
            $pageTexts['boton_enviar'] = $pageTexts['guardar'] ?? $pageTexts['save'] ?? $pageTexts['update'] ?? $pageTexts['actualizar'] ?? '';
        }
        // Mantener compatibilidad con btn_edit
        if (!isset($pageTexts['btn_edit'])) {
            $pageTexts['btn_edit'] = $pageTexts['boton_enviar'] ?? $pageTexts['guardar'] ?? $pageTexts['save'] ?? '';
        }
        
        $data['pageTexts'] = $pageTexts;

        // Solo permitir peticiones AJAX (desde el modal)
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        // Cargar la vista del formulario de edición sin el wrapper completo
        $formContent = $this->load->view('company/categorias/modals/edit_category_form', $data, true);
        if (! is_string($formContent) || trim($formContent) === '') {
            echo '<div class="alert alert-danger">'
                . htmlspecialchars((string) ($pageTexts['load_form_error'] ?? ''), ENT_QUOTES, 'UTF-8')
                . '</div>';
            return;
        }
        echo $formContent;
        return;
    }
}
