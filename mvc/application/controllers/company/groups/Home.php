<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Home extends Company_Controller
{

    use IsCRUD;
    use ConfigCrudListTools {
        getConfigCrudStatusSwitchConfig as protected getBaseConfigCrudStatusSwitchConfig;
    }

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('customergroup', 'company/group/', 'groups');
        $this->langArr = $this->getModuleLang('cmp-group');
    }

    /* List */
    public function index()
    {
        $indexJsFiles = [
            'companyasset/company/enable-disable.js',
            'companyasset/company/common/validation-alert.js',
            'companyasset/company/common/delete-alert.js',
            'companyasset/company/groups/modal_add.js',
            'companyasset/company/groups/modal_edit.js',
        ];

        foreach ($indexJsFiles as $indexJsFile) {
            $this->addJsFile(base_url($indexJsFile));
        }

        $this->addModal('company/groups/modals/add_group');
        $this->addModal('company/groups/modals/edit_group');
        $this->list();
    }

    protected function getExtraListViewData($paginatedList, $contextualButtonBar = null): array
    {
        return array_merge(
            $this->getConfigCrudDatagridViewData(),
            [
                'listCenterAllDataColumns' => false,
            ]
        );
    }

    protected function getConfigCrudStatusSwitchConfig(): array
    {
        $config = $this->getBaseConfigCrudStatusSwitchConfig();

        $config['messages']['enabled'] = $this->groupsLang('group_enabled_success');
        $config['messages']['disabled'] = $this->groupsLang('group_disabled_success');

        return $config;
    }

    protected function initListButtonBar()
    {
        $this->listButtonBar = new ButtonBar();
        $this->listButtonBar->addModalButton(
            $this->langArr['btn_add_group'] ?? 'Add Group',
            "#modal_add_group",
            'company/groups/modals/add_group',
            'mdi mdi-plus-circle'
        );
        $this->appendConfigCrudListToolbar('groups', 'groupscsv.csv');
    }

    protected function initItemActions()
    {
        $langtoltip = $this->getModuleLang('cmp-toltip');
        $this->addItemAction('edit', $langtoltip['edit'], company_url('groups/edit/'), 'mdi mdi-pencil');
        $this->addItemAction('delete', $langtoltip['delete'], company_url('groups/delete/'), 'mdi mdi-delete', 'btn-warning');
    }

    protected function getPageTexts($pageContentObject = null): array
    {
        $langToggleStatusButton = $this->getModuleLang('cmp-enabled');
        $langArr = is_array($this->langArr) ? $this->langArr : [];
        $texts = [
                'company_total_items_label' => $langArr['total_groups'] ?? 'Total groups',
                'cantidad_productos' => $langArr['total_groups_found'] ?? 'Total groups found',
                'entityNamePlural' => $langArr['groups'] ?? 'Groups',
                'entityName' => $langArr['group'] ?? 'Group',
                'fields' => [
                    'name' => $langArr['name'] ?? 'Name',
                    'percentage_on_price' => $langArr['on_price'] ?? '% Price',
                    'percent_price_amount' => $langArr['%'] ?? '%',
                    'isDefault' => $langArr['defecto'] ?? ($langArr['default'] ?? 'By Default'),
                    'created' => $langArr['created'] ?? 'Creation Date',
                ],
                'colmn_status' => $langArr['status'] ?? 'Status',
                'colmn_action' => $langArr['actions'] ?? 'Actions',
                'option_enable' => $langToggleStatusButton['option_enable'] ?? '',
                'option_disable' => $langToggleStatusButton['option_disable'] ?? '',
                'loader-enable' => $langToggleStatusButton['loader-enabling'] ?? '',
                'loader-disable' => $langToggleStatusButton['loader-disabling'] ?? '',
                'btn_filter' => $langArr['btn_filter'] ?? 'Filter',
                'seleccionar_por' => $langArr['seleccionar_por'] ?? 'Filter by',
                'search' => $langArr['search'] ?? 'Search',
                'search_placeholder' => $langArr['search_placeholder'] ?? 'Search by name',
            ] + $langArr;
        $texts['pageTitle'] = $this->resolveConfigCrudPageTitle($langArr, 'pageTitle', 'list_page_title', 'list_of_group');
        return $texts;
    }

    protected function getFormFilterView(): string
    {
        return 'company/groups/group_filter';
    }

    protected function getConfigCrudInlineEditableFields(): array
    {
        return ['name', 'percent_price_amount'];
    }

    protected function getListItemFields($list)
    {
        return [
            'name',
            'percentage_on_price',
            'percent_price_amount',
            'isDefault',
            'created'
        ];
    }

    protected function getSortableFields($list)
    {
        return [
            'group_id'
        ];
    }

    protected function getFieldTemplates(): array
    {
        return [
            'isDefault' => 'components/crud/default_field',
            'created' => 'components/crud/created_field',
            'percentage_on_price' => 'company/groups/list/percentage_on_price_field',
        ];
    }

    protected function getActionButtonTemplates(): array
    {
        return [
            'edit' => 'company/groups/list/edit_action_button',
            'delete' => 'company/groups/list/delete_action_button'
        ];
    }

    protected function getItemIdField(): string
    {
        return 'group_id';
    }

    protected function getStatusField(): string
    {
        return 'group_status';
    }

    protected function getFilterData(): array
    {
        $searchTerm = $this->input->get('prsearch');
        $filter = [
            'company' => $this->getCompanyId(),
            'props' => ['group_id', 'name', 'percentage_on_price', 'percent_price_amount', 'created_at', 'group_status'],
        ];
        if (!empty($searchTerm)) {
            $filter['terms'] = array_values(array_filter(explode(' ', trim($searchTerm))));
        }
        return $filter;
    }

    /**
     * Habilitar un grupo desde la grilla (/company/groups/enable/{id}).
     */
    public function enable($groupId = null): void
    {
        if (!$groupId) {
            show_404();
            return;
        }

        $groupId = (int) $groupId;
        if ($groupId <= 0) {
            $message = $this->groupsLang('no_puede_desactivar');
            $this->jsonError(['message' => $message], $message, 404);
            return;
        }

        $this->changeGroupStatusViaApi($groupId, 'enable');
    }

    /**
     * Deshabilitar un grupo desde la grilla (/company/groups/disable/{id}).
     */
    public function disable($groupId = null): void
    {
        if (!$groupId) {
            show_404();
            return;
        }

        $groupId = (int) $groupId;
        if ($groupId <= 0) {
            $message = $this->groupsLang('no_puede_desactivar');
            $this->jsonError(['message' => $message], $message, 404);
            return;
        }

        $this->changeGroupStatusViaApi($groupId, 'disable');
    }

    protected function changeGroupStatusViaApi(int $groupId, string $action): void
    {
        $updated = $this->put("{$this->entityPath}/{$groupId}/{$action}");
        $apiResponse = $updated;

        if ($this->hasError($updated)) {
            if ($action === 'disable') {
                $payload = (is_array($updated) && isset($updated['message']) && is_array($updated['message']))
                    ? $updated['message']
                    : [];
                $messageKey = isset($payload['message_key']) ? (string) $payload['message_key'] : '';
                if ($messageKey === 'group_has_customers_cannot_disable') {
                    $message = $this->formatGroupBlockedByCustomersMessage(
                        $payload,
                        'group_has_customers_cannot_disable',
                        'Customer group has %d assigned customer(s) (%s) and cannot be disabled.'
                    );
                    $this->jsonError(['message' => $message], $message, 400);
                    return;
                }

                $rawMessage = $this->extractGroupDisableErrorMessage($updated);
                $message = $this->resolveGroupDisableErrorMessage($rawMessage);
                $this->jsonError(['message' => $message], $message, 400);
                return;
            }

            $unpacked = $this->unpackApiErrorResponse(
                is_array($updated) ? $updated : [],
                $this->groupsLang('no_puede_desactivar')
            );
            $this->jsonError(['message' => $unpacked['message']], $unpacked['message'], 400);
            return;
        }

        $updated = $this->normalizeGroupStatusResponse($updated, $action, $groupId);
        if ($updated === null) {
            log_message('error', 'groups/Home::changeGroupStatusViaApi - Respuesta inválida: ' . json_encode($apiResponse));
            $message = $this->groupsLang('no_puede_desactivar');
            $this->jsonError(['message' => $message], $message, 500);
            return;
        }

        $message = $this->resolveGroupStatusSuccessMessage($action);

        $html = $this->load->view(
            'components/crud/enable-disable_button',
            [
                'listItem' => (object) $updated,
                'itemIdField' => 'group_id',
                'itemStatusField' => 'group_status',
                'controllerUrl' => $this->paginationUrl(),
                'pageTexts' => $this->getModuleLang('cmp-enabled'),
            ],
            true
        );

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'html' => $html,
            'message' => $message,
        ]);
        exit;
    }

    protected function resolveGroupStatusSuccessMessage(string $action): string
    {
        $key = $action === 'enable' ? 'group_enabled_success' : 'group_disabled_success';
        $message = $this->groupsLang($key);
        if ($message !== '') {
            return $message;
        }

        $lang = is_array($this->langArr) ? $this->langArr : [];

        return trim((string) ($lang[$key] ?? ''));
    }

    /**
     * @param mixed $updated
     * @return array<string, mixed>|null
     */
    private function normalizeGroupStatusResponse($updated, string $action, int $groupId): ?array
    {
        if (is_object($updated)) {
            if (isset($updated->data)) {
                $updated = $updated->data;
            }
            $updated = (array) $updated;
        }

        if (!is_array($updated)) {
            $updated = [];
        }

        if (isset($updated['data']) && is_array($updated['data'])) {
            $updated = $updated['data'];
        }

        if (!isset($updated['group_id']) && $groupId > 0) {
            $updated['group_id'] = $groupId;
        }

        if (!isset($updated['group_id'])) {
            return null;
        }

        if (!isset($updated['group_status'])) {
            $updated['group_status'] = $action === 'enable' ? 1 : 0;
        } else {
            $updated['group_status'] = ($updated['group_status'] == 1 || $updated['group_status'] === true || $updated['group_status'] === '1') ? 1 : 0;
        }

        return $updated;
    }

    /**
     * @param mixed $updated
     */
    private function extractGroupDisableErrorMessage($updated): string
    {
        if (!is_array($updated) || !isset($updated['message'])) {
            return '';
        }

        $msg = $updated['message'];
        if (is_object($msg) && isset($msg->message)) {
            return is_string($msg->message) ? $msg->message : '';
        }
        if (is_array($msg) && isset($msg['message'])) {
            return is_string($msg['message']) ? $msg['message'] : '';
        }

        return is_string($msg) ? $msg : '';
    }

    private function resolveGroupDisableErrorMessage(string $rawMessage): string
    {
        $raw = $rawMessage;
        if (stripos($raw, 'Default customer group') !== false || (stripos($raw, 'default') !== false && stripos($raw, 'cannot be disabled') !== false)) {
            return $this->groupsLang('defecto_nopuede_desactivar');
        }
        if (stripos($raw, 'assigned customers') !== false || stripos($raw, 'clientes asignados') !== false) {
            return $this->groupsLang('group_has_customers_cannot_disable');
        }

        return $raw !== '' ? $raw : $this->groupsLang('no_puede_desactivar');
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function formatGroupBlockedByCustomersMessage(array $payload, string $messageKey, string $defaultTemplate): string
    {
        $count = (int) ($payload['customers_count'] ?? 0);
        $names = is_array($payload['customers_names'] ?? null) ? $payload['customers_names'] : [];
        $overflow = (int) ($payload['customers_overflow'] ?? 0);

        $template = $this->langArr[$messageKey]
            ?? (isset($payload['message']) && is_string($payload['message']) && $payload['message'] !== ''
                ? $payload['message']
                : $defaultTemplate);

        $andMoreTemplate = $this->langArr['group_customers_and_more'] ?? 'and %d more';

        $namesList = implode(', ', array_map('strval', $names));
        if ($overflow > 0) {
            $suffix = (strpos($andMoreTemplate, '%') !== false)
                ? sprintf($andMoreTemplate, $overflow)
                : $andMoreTemplate . ' ' . $overflow;
            $namesList = $namesList !== '' ? ($namesList . ' ' . $suffix) : $suffix;
        }

        if (substr_count($template, '%') >= 2 && strpos($template, '%s') !== false) {
            return sprintf($template, $count, $namesList);
        }
        if (strpos($template, '%') !== false) {
            return sprintf($template, $count);
        }

        return $template;
    }
}
