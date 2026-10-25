<?php

class Update extends Company_Controller
{
    use IsAction;
    use IsCRUD;
    protected $itemId;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('user', 'company/user/', 'users');
        $this->langArr = $this->getModuleLang('cmp-user');
    }

    function run($userId=null)
    {
        $raw = file_get_contents('php://input');
        $postData = json_decode($raw, true);
        if (! is_array($postData)) {
            $postData = [];
        }
        if ($postData === [] && ! empty($_POST)) {
            $postData = $_POST;
        }

        if (isset($postData['password']) && trim((string) $postData['password']) === '') {
            unset($postData['password']);
        }

        $response = $this->put("{$this->entityPath}/{$userId}", $postData);

        if ($this->hasError($response)) {
            $def = $this->langArr['user_error_save'] ?? 'Could not update user.';
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
            redirect(company_url('users'));
            return;
        }

        // Si se actualiza el usuario logueado (p. ej. avatar), refrescar sesión del navbar.
        $sessionUser = $this->getUser();
        $sessionUserId = 0;
        if (is_array($sessionUser)) {
            $sessionUserId = (int) ($sessionUser['id'] ?? 0);
        } elseif (is_object($sessionUser)) {
            $sessionUserId = (int) ($sessionUser->id ?? 0);
        }
        if ($sessionUserId > 0 && (int) $userId === $sessionUserId) {
            $this->clearSessionUserAvatarCache();
            if (isset($postData['image'])) {
                $newImage = trim((string) $postData['image']);
                if (is_array($sessionUser)) {
                    $sessionUser['image'] = $newImage;
                } else {
                    $sessionUser->image = $newImage;
                }
                $this->setUser($sessionUser);
            } else {
                $this->enrichSessionUserAvatar($sessionUser);
            }
        }

        $msg = $this->langArr['user_updated'] ?? 'User updated successfully.';
        if ($this->input->is_ajax_request()) {
            $this->jsonSuccess($msg);
            return;
        }

        $this->session->set_flashdata('user_form_success_message', $msg);
        $ref = $this->input->server('HTTP_REFERER');
        $base = rtrim((string) base_url(), '/');
        if ($ref && $base !== '' && strpos((string) $ref, $base) === 0) {
            redirect($ref);
            return;
        }
        redirect(company_url('users'));
    }
}
