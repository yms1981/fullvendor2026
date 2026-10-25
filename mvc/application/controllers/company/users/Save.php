<?php

class Save extends Company_Controller
{
    use IsCRUD;
    use IsAction;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('user', 'company/user/', 'users');
        $this->langArr = $this->getModuleLang('cmp-user');
    }

    public function run(): void
    {
        $raw = file_get_contents('php://input');
        $postData = json_decode($raw, true);
        if (! is_array($postData)) {
            $postData = [];
        }
        /* multipart/form-data deja php://input vacío: los datos van en $_POST */
        if ($postData === [] && ! empty($_POST)) {
            $postData = $_POST;
        }

        $postData['company_id'] = $this->getCompanyId();

        $response = $this->post("{$this->entityPath}", $postData);

        if ($this->hasError($response)) {
            $def = $this->langArr['user_error_save'] ?? 'Could not save user.';
            $payload = $this->unpackApiErrorResponse($response, $def);
            $jsonOut = ['message' => $payload['message'], 'errors' => $payload['errors']];
            if (! empty($payload['existing_user'])) {
                $jsonOut['existing_user'] = $payload['existing_user'];
            }
            if ($this->input->is_ajax_request()) {
                header('Content-Type: application/json');
                http_response_code(422);
                echo json_encode($jsonOut);
                exit;
            }
            $this->session->set_flashdata('user_form_validation_json', json_encode($jsonOut));
            $ref = $this->input->server('HTTP_REFERER');
            $base = rtrim((string) base_url(), '/');
            if ($ref && $base !== '' && strpos((string) $ref, $base) === 0) {
                redirect($ref);
                return;
            }
            redirect(company_url('users/add'));
            return;
        }

        $fallback = $this->langArr['user_saved'] ?? 'User added successfully';
        $createdLangId = $this->languageIdFromUserCreateResponse($response);
        $msg = ($createdLangId !== null && $createdLangId > 0)
            ? $this->getTranslationFromLangJsonByLanguageId($createdLangId, 'cmp-user', 'user_saved', $fallback)
            : $fallback;
        if ($this->input->is_ajax_request()) {
            $this->jsonSuccess($msg);
            return;
        }

        $this->session->set_flashdata('user_form_success_message', $msg);
        $redirect = company_url('users');
        $ref = $this->input->server('HTTP_REFERER');
        $base = rtrim((string) base_url(), '/');
        $successText = '';
        if ($ref && $base !== '' && strpos((string) $ref, $base) === 0
            && preg_match('/[?&]type=(manager|customer|seller|warehouse)\b/', (string) $ref, $m)) {
            $redirect = company_url('users?type=' . $m[1]);
            if ($m[1] === 'manager') {
                $successText = (string) ($this->langArr['tab_manager'] ?? '');
            }
        }
        if ($successText !== '') {
            $this->session->set_flashdata('user_form_success_text', $successText);
        }
        redirect($redirect);
    }

    /**
     * @param mixed $response Cuerpo JSON del POST user (modelo usuario).
     */
    private function languageIdFromUserCreateResponse($response): ?int
    {
        if (! is_array($response)) {
            return null;
        }
        if (isset($response['language_id'])) {
            return (int) $response['language_id'];
        }
        if (isset($response['data']['language_id'])) {
            return (int) $response['data']['language_id'];
        }

        return null;
    }

}
