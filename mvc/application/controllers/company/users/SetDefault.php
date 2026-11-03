<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once APPPATH . 'controllers/company/users/Form.php';

class SetDefault extends Form
{
    use IsAction;
    use CrudSetDefaultAction;

    protected function getItemIdField(): string
    {
        return 'id';
    }

    protected function getPageTexts($pageContentObject = null): array
    {
        $lang = is_array($this->langArr ?? null) ? $this->langArr : [];
        $json = $this->getLangJsonSection('cmp-user');
        if (is_array($json)) {
            $lang = $json + $lang;
        }

        return [
            'fields' => [
                'isDefault' => $lang['defecto'] ?? ($lang['default'] ?? ''),
            ],
            'default' => $lang['default'] ?? '',
            'defecto' => $lang['defecto'] ?? '',
            'default_indicator_label' => $lang['default_indicator_label'] ?? ($lang['defecto'] ?? ''),
            'default_switch_set_success' => $lang['default_switch_set_success'] ?? '',
            'default_switch_error' => $lang['default_switch_error'] ?? '',
        ] + $lang;
    }

    protected function getDedicatedSetDefaultApiPath($itemId): ?string
    {
        $companyId = (int) $this->getCompanyId();
        if ($companyId < 1) {
            return null;
        }

        $seller = $this->getObjectFromApi("user/{$itemId}/company/{$companyId}/seller");
        if ($this->hasError($seller) || ! is_object($seller)) {
            return null;
        }

        $sellerId = (int) ($seller->id ?? 0);
        if ($sellerId < 1) {
            return null;
        }

        return "vendor/{$sellerId}/setDefault";
    }

    protected function buildSetDefaultUpdatePayload(object $entity, $itemId): array
    {
        return [
            'company_id' => (int) $this->getCompanyId(),
            'seller' => [
                'default' => 1,
            ],
        ];
    }

    protected function resolveUserIdForDefault(object $listItem, $fallbackId = null): int
    {
        $userId = (int) ($listItem->user_id ?? 0);
        if ($userId < 1) {
            $userId = (int) ($listItem->id ?? 0);
        }
        if ($userId < 1 && $fallbackId !== null) {
            $userId = (int) $fallbackId;
        }

        return $userId;
    }

    protected function renderDefaultFieldHtml(object $listItem): string
    {
        $userId = $this->resolveUserIdForDefault($listItem);
        $listItem->id = $userId > 0 ? $userId : ($listItem->id ?? 0);
        $listItem->isDefault = true;
        $listItem->is_default = true;
        if ($userId > 0) {
            $listItem->setDefaultUrl = company_url('users/setDefault/' . $userId);
        }

        $itemIdField = $this->getItemIdField();

        return $this->load->view(
            'components/crud/default_field',
            [
                'listItem' => $listItem,
                'itemIdField' => $itemIdField,
                'controllerUrl' => $this->paginationUrl(),
                'pageTexts' => $this->getPageTexts(),
            ],
            true
        );
    }

    protected function isDefaultRecord(object $entity): bool
    {
        if (!empty($entity->isDefault) || !empty($entity->is_default)) {
            return true;
        }

        $companyId = (int) $this->getCompanyId();
        $userId = $this->resolveUserIdForDefault($entity);
        if ($companyId < 1 || $userId < 1) {
            return false;
        }

        $seller = $this->getObjectFromApi("user/{$userId}/company/{$companyId}/seller");
        if ($this->hasError($seller) || ! is_object($seller)) {
            return false;
        }

        $sellerId = (int) ($seller->id ?? 0);
        $company = $this->getObjectFromApi('company/' . $companyId, [
            'props' => ['company_id', 'default_seller_id'],
        ]);
        if ($this->hasError($company) || ! is_object($company)) {
            return false;
        }

        return $sellerId > 0 && (int) ($company->default_seller_id ?? 0) === $sellerId;
    }

    protected function isInactiveDefaultCandidate(object $entity): bool
    {
        return false;
    }

    protected function isValidSetDefaultRecord(object $record, $itemId): bool
    {
        if ($this->isDefaultRecord($record)) {
            return true;
        }

        // Respuesta de vendor/setDefault: seller id + user_id.
        return isset($record->user_id) || isset($record->id);
    }
}
