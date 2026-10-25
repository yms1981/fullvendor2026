<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Logs extends MY_Controller
{

	public function __construct()
	{
		parent::__construct();
		$this->checkLoggedInOrLoginWithRedirect();
	}

	/**
	 * @return array<int, object>
	 */
	private function fetchAdminLogs(string $orderDirection = 'DESC'): array
	{
		$resp = $this->getDataFromApi('log', [
			'all' => true,
			'orderProperty' => 'log_id',
			'orderDirection' => $orderDirection,
		], true);

		$rows = $this->extractApiListRows($resp);

		$emailMap = $this->sellerEmailMapByUserId();
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
	 * @return array<int, string>
	 */
	private function sellerEmailMapByUserId(): array
	{
		$resp = $this->getDataFromApi('vendor', [
			'all' => true,
			'relations' => ['user'],
			'props' => ['user_id', 'id'],
		], true);

		$rows = $this->extractApiListRows($resp);

		$map = [];
		foreach ($rows as $row) {
			$v = is_object($row) ? $row : (object) $row;
			$userId = (int) ($v->user_id ?? 0);
			if ($userId < 1) {
				continue;
			}
			$user = $v->user ?? null;
			$email = is_object($user) ? trim((string) ($user->email ?? '')) : '';
			if ($email !== '') {
				$map[$userId] = $email;
			}
		}

		return $map;
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
	 * Réplica la lógica SQL de Commonmodel::fetchtotal_logsdata.
	 *
	 * @param array<int, object> $logs
	 * @return array<int, object>
	 */
	private function applyAdminLogFilters(array $logs, string $fromdate, string $todate, string $logtype): array
	{
		$fromdate = trim($fromdate);
		$todate = trim($todate);
		$logtype = trim($logtype);

		$filtered = array_filter($logs, static function ($log) use ($fromdate, $todate, $logtype) {
			$ts = strtotime((string) ($log->created_on ?? ''));
			if ($ts === false) {
				return false;
			}
			$d = date('Y-m-d', $ts);
			if ($fromdate !== '' && $d < $fromdate) {
				return false;
			}
			if ($todate !== '' && $d > $todate) {
				return false;
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

		return $ts ? date('d-M-Y H:i A', $ts) : '';
	}

	/**
	 * Misma salida HTML que Commonmodel::fetchtotal_logsdata.
	 *
	 * @param array<int, object> $logs
	 */
	private function buildFilterSearchHtml(array $logs, string $emptyMessage = 'No data available'): string
	{
		if ($logs === []) {
			$msg = htmlspecialchars($emptyMessage, ENT_QUOTES, 'UTF-8');

			return '<tr class="text-center"><td colspan="5">'.$msg.'</td></tr>';
		}

		$output = '';
		$j = 0;
		foreach ($logs as $log) {
			$j++;
			$output .= '<tr>
				<td>'.$j.'</td>
				<td>'.htmlspecialchars($this->formatLogDateTime($log->created_on ?? null), ENT_QUOTES, 'UTF-8').'</td>
				<td>'.htmlspecialchars((string) ($log->seller_email ?? ''), ENT_QUOTES, 'UTF-8').'</td>
				<td>'.htmlspecialchars((string) ($log->log_type ?? ''), ENT_QUOTES, 'UTF-8').'</td>
				<td>'.htmlspecialchars((string) ($log->notes ?? ''), ENT_QUOTES, 'UTF-8').'</td>
				</tr>';
		}

		return $output;
	}

	public function lists()
	{
		$data = array(
			'title' => admin_lang_string('cmp-admin-logs', 'list_page_title', 'Log history'),
			'page' => 'logs',
			'subpage' => 'loglists',
			'logsListLang' => admin_lang_merged_section('cmp-admin-logs'),
		);

		$data['list'] = $this->fetchAdminLogs('DESC');

		$this->load->view('admin/header', $data);
		$this->load->view('admin/log_history_list');
		$this->load->view('admin/footer');
	}

	public function filterSearch()
	{
		$fromdate = (string) $this->input->post('fromdate');
		$todate = (string) $this->input->post('todate');
		$logtype = (string) $this->input->post('logtype');
		if ($logtype === 'all') {
			$logtype = '';
		}

		$logs = $this->applyAdminLogFilters(
			$this->fetchAdminLogs('ASC'),
			$fromdate,
			$todate,
			$logtype
		);

		$this->output->set_content_type('application/json', 'utf-8');
		echo json_encode([
			'log_list' => $this->buildFilterSearchHtml(
				$logs,
				admin_lang_string('cmp-admin-logs', 'empty_no_data', 'No data available')
			),
		]);
	}

}
