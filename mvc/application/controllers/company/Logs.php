<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Historial de registros (logs) del panel company.
 * Debe usar Company_Controller + showContent para cargar CSS/layout.
 */
class Logs extends Company_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->checkLoggedInOrLoginWithRedirect();
        $this->load->model('langmodel');
    }

    /**
     * @return array<int, object>
     */
    private function fetchCompanyLogs(array $filters = [], string $orderDirection = 'DESC'): array
    {
        $compId = (int) $this->getCompany();
        if ($compId < 1) {
            return [];
        }

        $params = array_merge([
            'company' => $compId,
            'all' => true,
            'orderProperty' => 'log_id',
            'orderDirection' => $orderDirection,
        ], $filters);

        $resp = $this->getDataFromApi('log', $params, false);
        $rows = [];
        if (is_object($resp) && isset($resp->data) && is_array($resp->data)) {
            $rows = $resp->data;
        } elseif (is_array($resp) && isset($resp['data']) && is_array($resp['data'])) {
            $rows = $resp['data'];
        } elseif (is_array($resp)) {
            $rows = $resp;
        }

        $userIds = [];
        foreach ($rows as $row) {
            $r = is_object($row) ? $row : (object) $row;
            $uid = (int) ($r->user_id ?? 0);
            if ($uid > 0) {
                $userIds[$uid] = $uid;
            }
        }
        $emailMap = $this->userEmailMapByUserIds(array_values($userIds));
        $out = [];
        foreach ($rows as $row) {
            $out[] = $this->normalizeLogRow($row, $emailMap);
        }

        if (strtoupper($orderDirection) === 'ASC') {
            usort($out, static fn ($a, $b) => ((int) ($a->log_id ?? 0)) <=> ((int) ($b->log_id ?? 0)));
        } else {
            usort($out, static fn ($a, $b) => ((int) ($b->log_id ?? 0)) <=> ((int) ($a->log_id ?? 0)));
        }

        return $out;
    }

    /**
     * @param array<int, int> $userIds
     * @return array<int, string>
     */
    private function userEmailMapByUserIds(array $userIds): array
    {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        if ($userIds === []) {
            return [];
        }

        if (! isset($this->db) || $this->db === null) {
            $this->load->database();
        }

        $rows = $this->db
            ->select('id, email')
            ->where_in('id', $userIds)
            ->get('users')
            ->result();

        $map = [];
        foreach ($rows as $row) {
            $id = (int) ($row->id ?? 0);
            $email = trim((string) ($row->email ?? ''));
            if ($id > 0 && $email !== '') {
                $map[$id] = $email;
            }
        }

        return $map;
    }

    private function labelForLogType(string $logType, array $lang): string
    {
        $logType = trim($logType);
        if ($logType === 'login') {
            return (string) ($lang['log_type_login'] ?? $logType);
        }
        if ($logType === 'sync') {
            return (string) ($lang['log_type_sync'] ?? $logType);
        }

        return $logType;
    }

    /**
     * @param array<int, string> $emailMap
     */
    private function normalizeLogRow($row, array $emailMap): object
    {
        $o = is_object($row) ? $row : (object) $row;
        $userId = (int) ($o->user_id ?? 0);
        $o->seller_email = $emailMap[$userId] ?? '';
        if (! isset($o->created_on) || $o->created_on === null || $o->created_on === '') {
            $o->created_on = (string) ($o->created_at ?? '');
        }

        return $o;
    }

    /**
     * @param array<int, object> $logs
     * @return array<int, object>
     */
    private function applyLegacyLogFilters(array $logs, string $fromdate, string $todate, string $logtype): array
    {
        $fromdate = trim($fromdate);
        $todate = trim($todate);
        $logtype = trim($logtype);
        if ($logtype === 'all') {
            $logtype = '';
        }

        $filtered = array_filter($logs, static function ($log) use ($fromdate, $todate, $logtype) {
            $ts = strtotime((string) ($log->created_on ?? ''));
            if ($ts === false) {
                return false;
            }
            $d = date('Y-m-d', $ts);
            if ($fromdate !== '' && $todate !== '') {
                if ($d < $fromdate || $d > $todate) {
                    return false;
                }
            } else {
                if ($fromdate !== '' && $d !== $fromdate) {
                    return false;
                }
                if ($todate !== '' && $d !== $todate) {
                    return false;
                }
            }
            if ($logtype !== '' && ($log->log_type ?? '') !== $logtype) {
                return false;
            }

            return true;
        });

        $out = array_values($filtered);
        usort($out, static fn ($a, $b) => ((int) ($a->log_id ?? 0)) <=> ((int) ($b->log_id ?? 0)));

        return $out;
    }

    private function formatLogDateTime($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        $ts = strtotime((string) $value);

        return $ts ? fv_format_display_date($ts, fv_resolve_display_date_format() . ' H:i A') : '';
    }

    /**
     * @param array<int, object> $logs
     */
    private function buildFilterSearchHtml(array $logs): string
    {
        $lang = $this->getLangJsonSection('cmp-admin-logs');
        $empty = htmlspecialchars((string) ($lang['empty_no_data'] ?? 'No data available'), ENT_QUOTES, 'UTF-8');

        if ($logs === []) {
            return '<tr class="text-center"><td colspan="5">' . $empty . '</td></tr>';
        }

        $output = '';
        $j = 0;
        foreach ($logs as $log) {
            $j++;
            $typeLabel = $this->labelForLogType((string) ($log->log_type ?? ''), $lang);
            $output .= '<tr>'
                . '<td>' . $j . '</td>'
                . '<td>' . htmlspecialchars($this->formatLogDateTime($log->created_on ?? null), ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td>' . htmlspecialchars((string) ($log->seller_email ?? ''), ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td>' . htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td>' . htmlspecialchars((string) ($log->notes ?? ''), ENT_QUOTES, 'UTF-8') . '</td>'
                . '</tr>';
        }

        return $output;
    }

    public function lists()
    {
        $logsLang = $this->getLangJsonSection('cmp-admin-logs');
        $title = (string) ($logsLang['list_page_title'] ?? 'Log history');

        $data = [
            'title' => $title,
            'page' => 'logs',
            'subpage' => 'loglists',
            'logsListLang' => $logsLang,
            'list' => $this->fetchCompanyLogs([], 'DESC'),
            'langdef' => $this->getCommonTexts(),
        ];

        $this->showContent('company/log_history_list', $data);
    }

    public function filterSearch()
    {
        $fromdate = (string) $this->input->post('fromdate');
        $todate = (string) $this->input->post('todate');
        $logtype = (string) $this->input->post('logtype');

        $logs = $this->applyLegacyLogFilters(
            $this->fetchCompanyLogs([], 'ASC'),
            $fromdate,
            $todate,
            $logtype
        );

        $this->output->set_content_type('application/json', 'utf-8');
        echo json_encode([
            'log_list' => $this->buildFilterSearchHtml($logs),
        ]);
    }
}
