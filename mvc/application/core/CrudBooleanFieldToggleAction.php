<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Acción compartida para alternar campos booleanos desde la grilla.
 */
trait CrudBooleanFieldToggleAction
{
    use CrudActionMessages;

    /**
     * @return array<int, string>
     */
    abstract protected function getToggleableBooleanFields(): array;

    /**
     * @return string
     */
    abstract protected function getBooleanSwitchFieldTemplate(): string;

    protected function getBooleanFieldToggleSuccessMessage(): string
    {
        $lang = is_array($this->langArr ?? null) ? $this->langArr : [];
        $msg = trim((string) ($lang['boolean_field_switch_success'] ?? ''));
        if ($msg !== '') {
            return $msg;
        }

        $msg = $this->getCrudToolsMessage('boolean_field_switch_success');
        if ($msg !== '') {
            return $msg;
        }

        return $this->getCrudToolsMessage('message_ok');
    }

    protected function getBooleanFieldToggleErrorMessage(): string
    {
        $lang = is_array($this->langArr ?? null) ? $this->langArr : [];
        $msg = trim((string) ($lang['boolean_field_switch_error'] ?? ''));
        if ($msg !== '') {
            return $msg;
        }

        $msg = $this->getCrudToolsMessage('boolean_field_switch_error');
        if ($msg !== '') {
            return $msg;
        }

        return $this->getCrudToolsMessage('message_error_generic');
    }

    protected function renderBooleanSwitchFieldHtml(object $listItem, string $fieldName): string
    {
        $itemIdField = method_exists($this, 'getItemIdField') ? $this->getItemIdField() : 'id';
        $fieldValue = $listItem->{$fieldName} ?? 0;

        return $this->load->view(
            $this->getBooleanSwitchFieldTemplate(),
            [
                'listItem' => $listItem,
                'value' => $fieldValue,
                'fieldName' => $fieldName,
                'itemIdField' => $itemIdField,
                'controllerUrl' => $this->paginationUrl(),
                'pageTexts' => method_exists($this, 'getPageTexts') ? $this->getPageTexts() : [],
            ],
            true
        );
    }

    /**
     * @param mixed $payload
     */
    protected function unwrapApiRecord($payload): object
    {
        if (is_array($payload)) {
            $payload = (object) $payload;
        }
        if (!is_object($payload)) {
            return new \stdClass();
        }
        if (isset($payload->data)) {
            $data = $payload->data;
            if (is_object($data)) {
                return $data;
            }
            if (is_array($data)) {
                return (object) $data;
            }
        }

        return $payload;
    }

    public function runBooleanFieldToggle($itemId, $fieldName = null): void
    {
        $fieldName = trim((string) $fieldName);
        $allowedFields = $this->getToggleableBooleanFields();
        $itemId = (int) $itemId;

        if ($itemId <= 0) {
            $notFound = $this->getCrudToolsMessage('message_not_found');
            $this->jsonError(['message' => $notFound], $notFound, 404);
            return;
        }

        if ($fieldName === '' || !in_array($fieldName, $allowedFields, true)) {
            $invalid = $this->getCrudToolsMessage('message_invalid_field');
            $this->jsonError(['message' => $invalid], $invalid, 400);
            return;
        }

        $companyId = (int) $this->getCompanyId();
        $entity = $this->getObjectFromApi("{$this->entityPath}/{$itemId}", [
            'company' => $companyId,
        ]);
        if ($this->hasError($entity)) {
            $notFound = $this->getCrudToolsMessage('message_not_found');
            $payload = is_array($entity) ? $entity : ['message' => $notFound];
            $this->jsonError($payload, $notFound, 404);
            return;
        }

        $entity = $this->unwrapApiRecord($entity);
        if (isset($entity->company_id) && (int) $entity->company_id !== $companyId) {
            $notFound = $this->getCrudToolsMessage('message_not_found');
            $this->jsonError(['message' => $notFound], $notFound, 404);
            return;
        }

        $currentValue = !empty($entity->{$fieldName}) && ((int) $entity->{$fieldName} === 1);
        $newValue = $currentValue ? 0 : 1;

        $payload = [
            $fieldName => $newValue,
            'company_id' => $companyId,
        ];

        if (isset($entity->profile_name) && trim((string) $entity->profile_name) !== '') {
            $payload['profile_name'] = $entity->profile_name;
        }

        $response = $this->put("{$this->entityPath}/{$itemId}", $payload);
        if ($this->hasError($response)) {
            $this->jsonError($response, $this->getBooleanFieldToggleErrorMessage(), 422);
            return;
        }

        $updated = $this->unwrapApiRecord($response);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'html' => $this->renderBooleanSwitchFieldHtml($updated, $fieldName),
            'message' => $this->getBooleanFieldToggleSuccessMessage(),
            'itemId' => (string) $itemId,
            'fieldName' => $fieldName,
            'value' => $newValue,
        ]);
    }

    /** @deprecated Use runBooleanFieldToggle() */
    public function run($itemId, $fieldName = null): void
    {
        $this->runBooleanFieldToggle($itemId, $fieldName);
    }
}
