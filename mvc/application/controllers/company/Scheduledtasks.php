<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tareas programadas de sync ERP (pull).
 */
class Scheduledtasks extends Company_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->checkLoggedInOrLoginWithRedirect();
    }

    public function index()
    {
        $companyId = (int) $this->getCompanyId();
        $lang = $this->getLangJsonSection('cmp-scheduled-tasks');
        $nav = $this->getLangJsonSection('company-navigation');

        $payload = $this->getObjectFromApi("company/{$companyId}/scheduled-tasks");
        if ($this->hasError($payload)) {
            $tasks = [];
            $erpEnabled = false;
            $erpUrl = '';
            $frequencies = [
                'manual', 'every_15_min',
                'every_1_hour', 'every_2_hours', 'every_3_hours',
                'every_4_hours', 'every_5_hours', 'every_6_hours',
                'daily',
            ];
        } else {
            $tasks = is_array($payload->data ?? null) ? $payload->data : [];
            $erpEnabled = !empty($payload->erp_enabled);
            $erpUrl = (string) ($payload->erp_url ?? '');
            $frequencies = is_array($payload->frequencies ?? null)
                ? $payload->frequencies
                : [
                    'manual', 'every_15_min',
                    'every_1_hour', 'every_2_hours', 'every_3_hours',
                    'every_4_hours', 'every_5_hours', 'every_6_hours',
                    'daily',
                ];
        }

        $logsPayload = $this->getObjectFromApi("company/{$companyId}/scheduled-tasks/logs", [
            'per_page' => 50,
            'page' => 1,
        ]);
        $logs = [];
        if (!$this->hasError($logsPayload) && is_object($logsPayload) && is_array($logsPayload->data ?? null)) {
            $logs = $logsPayload->data;
        }

        $queuePayload = $this->getObjectFromApi("company/{$companyId}/scheduled-tasks/image-queue", [
            'recent' => 40,
        ]);
        $imageQueue = [
            'total' => 0,
            'pending' => 0,
            'processing' => 0,
            'downloaded' => 0,
            'failed' => 0,
            'skipped' => 0,
            'percent' => 0,
            'active' => false,
            'recent' => [],
        ];
        if (!$this->hasError($queuePayload) && is_object($queuePayload) && is_object($queuePayload->data ?? null)) {
            $d = $queuePayload->data;
            $imageQueue = [
                'total' => (int) ($d->total ?? 0),
                'pending' => (int) ($d->pending ?? 0),
                'processing' => (int) ($d->processing ?? 0),
                'downloaded' => (int) ($d->downloaded ?? 0),
                'failed' => (int) ($d->failed ?? 0),
                'skipped' => (int) ($d->skipped ?? 0),
                'percent' => (float) ($d->percent ?? 0),
                'active' => !empty($d->active),
                'recent' => is_array($d->recent ?? null) ? $d->recent : [],
            ];
        }

        $this->addJsFile(function_exists('dated_file_url')
            ? dated_file_url('companyasset/company/scheduled-tasks.js')
            : base_url('companyasset/company/scheduled-tasks.js'));

        $this->showContent('company/scheduled_tasks/index', [
            'title' => $lang['page_title'] ?? ($nav['nav_scheduled_tasks'] ?? ''),
            'pageTexts' => $lang,
            'tasks' => $tasks,
            'logs' => $logs,
            'imageQueue' => $imageQueue,
            'erpEnabled' => $erpEnabled,
            'erpUrl' => $erpUrl,
            'frequencies' => $frequencies,
            'companyId' => $companyId,
            'updateUrlBase' => company_url('scheduledtasks/update/'),
            'runUrlBase' => company_url('scheduledtasks/run/'),
            'logsUrl' => company_url('scheduledtasks/logs'),
            'imageQueueUrl' => company_url('scheduledtasks/imageQueue'),
        ]);
    }

    public function imageQueue()
    {
        $companyId = (int) $this->getCompanyId();
        $params = [
            'recent' => max(1, min(100, (int) ($this->input->get('recent') ?: 40))),
        ];
        $response = $this->getObjectFromApi("company/{$companyId}/scheduled-tasks/image-queue", $params);
        if ($this->hasError($response)) {
            $msg = is_array($response) ? ($response['message'] ?? 'Error') : 'Error';
            $this->jsonError(is_array($response) ? $response : ['message' => $msg], (string) $msg, 422);
            return;
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $response->data ?? null,
        ]);
    }

    public function logs()
    {
        $companyId = (int) $this->getCompanyId();
        $params = [
            'per_page' => max(1, min(100, (int) ($this->input->get('per_page') ?: 50))),
            'page' => max(1, (int) ($this->input->get('page') ?: 1)),
        ];
        $taskType = trim((string) $this->input->get('task_type'));
        $status = trim((string) $this->input->get('status'));
        if ($taskType !== '') {
            $params['task_type'] = $taskType;
        }
        if ($status !== '') {
            $params['status'] = $status;
        }

        $response = $this->getObjectFromApi("company/{$companyId}/scheduled-tasks/logs", $params);
        if ($this->hasError($response)) {
            $msg = is_array($response) ? ($response['message'] ?? 'Error') : 'Error';
            $this->jsonError(is_array($response) ? $response : ['message' => $msg], (string) $msg, 422);
            return;
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $response->data ?? [],
            'meta' => $response->meta ?? null,
        ]);
    }

    public function update($taskType = null)
    {
        $companyId = (int) $this->getCompanyId();
        $taskType = trim((string) $taskType);
        if ($taskType === '') {
            $this->jsonError(['message' => 'Invalid task'], 'Invalid task', 422);
            return;
        }

        $payload = [
            'enabled' => false,
            'frequency' => 'manual',
        ];

        $raw = file_get_contents('php://input');
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                if (array_key_exists('enabled', $decoded)) {
                    $payload['enabled'] = filter_var($decoded['enabled'], FILTER_VALIDATE_BOOLEAN);
                }
                if (array_key_exists('frequency', $decoded)) {
                    $payload['frequency'] = trim((string) $decoded['frequency']);
                }
            }
        } else {
            $enabledRaw = $this->input->post('enabled');
            $payload['enabled'] = $enabledRaw === '1' || $enabledRaw === 'true' || $enabledRaw === true || $enabledRaw === 1;
            $payload['frequency'] = trim((string) $this->input->post('frequency'));
        }

        $response = $this->put("company/{$companyId}/scheduled-tasks/{$taskType}", $payload);
        if ($this->hasError($response)) {
            $msg = is_array($response) ? ($response['message'] ?? 'Error') : 'Error';
            $this->jsonError(is_array($response) ? $response : ['message' => $msg], (string) $msg, 422);
            return;
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $response->data ?? $response]);
    }

    public function run($taskType = null)
    {
        $companyId = (int) $this->getCompanyId();
        $taskType = trim((string) $taskType);
        if ($taskType === '') {
            $this->jsonError(['message' => 'Invalid task'], 'Invalid task', 422);
            return;
        }

        $response = $this->post("company/{$companyId}/scheduled-tasks/{$taskType}/run", []);
        if ($this->hasError($response)) {
            $msg = $this->extractScheduledTaskRunMessage($response);
            $data = null;
            $result = null;
            if (is_array($response)) {
                $payload = $response['message'] ?? $response;
                if (is_array($payload)) {
                    $data = $payload['data'] ?? null;
                    $result = $payload['result'] ?? null;
                }
            }
            header('Content-Type: application/json');
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => $msg,
                'data' => $data,
                'result' => $result,
            ]);
            return;
        }

        $resultMessage = '';
        if (is_object($response) && is_object($response->result ?? null)) {
            $resultMessage = (string) ($response->result->message ?? '');
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $response->data ?? null,
            'result' => $response->result ?? null,
            'message' => $resultMessage,
        ]);
    }

    /**
     * @param mixed $response
     */
    private function extractScheduledTaskRunMessage($response): string
    {
        if (method_exists($this, 'resolveApiErrorMessage') && is_array($response)) {
            $resolved = trim($this->resolveApiErrorMessage($response));
            if ($resolved !== '' && $resolved !== 'Array') {
                // Prefer nested result.message from Laravel 422 payload.
                $payload = $response['message'] ?? null;
                if (is_array($payload)) {
                    $fromResult = $payload['result']['message'] ?? null;
                    if (is_string($fromResult) && trim($fromResult) !== '') {
                        return $this->simplifyErpErrorMessage(trim($fromResult));
                    }
                    $fromData = $payload['data']['last_message'] ?? null;
                    if (is_string($fromData) && trim($fromData) !== '') {
                        return $this->simplifyErpErrorMessage(trim($fromData));
                    }
                }

                return $this->simplifyErpErrorMessage($resolved);
            }
        }

        if (is_array($response)) {
            $payload = $response['message'] ?? $response;
            if (is_array($payload)) {
                foreach ([
                    $payload['result']['message'] ?? null,
                    $payload['data']['last_message'] ?? null,
                    $payload['message'] ?? null,
                ] as $candidate) {
                    if (is_string($candidate) && trim($candidate) !== '') {
                        return $this->simplifyErpErrorMessage(trim($candidate));
                    }
                }
            }
            if (is_string($response['message'] ?? null) && trim((string) $response['message']) !== '') {
                return $this->simplifyErpErrorMessage(trim((string) $response['message']));
            }
        }

        $lang = $this->getLangJsonSection('cmp-scheduled-tasks');

        return (string) ($lang['error'] ?? 'Error');
    }

    private function simplifyErpErrorMessage(string $message): string
    {
        // "ERP HTTP 503: {\"message\":\"Actualización…\"}" → texto legible
        if (preg_match('/ERP HTTP\s+(\d+):\s*(\{.*\})\s*$/s', $message, $m)) {
            $decoded = json_decode($m[2], true);
            if (is_array($decoded) && is_string($decoded['message'] ?? null) && trim($decoded['message']) !== '') {
                $inner = trim((string) $decoded['message']);
                // Acortar SQLSTATE ruidoso
                if (preg_match('/^(Actualización del sistema incompleta:\s*)/u', $inner, $prefix)) {
                    if (stripos($inner, "Table 'users' already exists") !== false
                        || stripos($inner, 'Base table or view already exists') !== false) {
                        $lang = $this->getLangJsonSection('cmp-scheduled-tasks');

                        return (string) ($lang['error_erp_schema_conflict'] ?? $inner);
                    }
                }

                return $inner;
            }

            $lang = $this->getLangJsonSection('cmp-scheduled-tasks');
            $tpl = (string) ($lang['error_erp_http'] ?? 'ERP HTTP :status');

            return str_replace(':status', $m[1], $tpl);
        }

        if (strlen($message) > 400) {
            return rtrim(mb_substr($message, 0, 400)).'…';
        }

        return $message;
    }
}
