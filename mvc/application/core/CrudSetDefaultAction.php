<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Acción compartida para marcar un ítem como valor por defecto desde la grilla.
 */
trait CrudSetDefaultAction
{
    use CrudActionMessages;

    /**
     * Campos a solicitar al API antes del PUT de respaldo (como en el modal de edición).
     *
     * @return list<string>|null null = todos los campos (*)
     */
    protected function getSetDefaultEntityProps(): ?array
    {
        return null;
    }

    /**
     * @return array<string, mixed>
     */
    abstract protected function buildSetDefaultUpdatePayload(object $entity, $itemId): array;

    protected function getSetDefaultSuccessMessage(): string
    {
        $lang = is_array($this->langArr ?? null) ? $this->langArr : [];
        $msg = trim((string) ($lang['default_switch_set_success'] ?? ''));
        if ($msg !== '') {
            return $msg;
        }

        $msg = $this->getCrudToolsMessage('default_switch_set_success');
        if ($msg !== '') {
            return $msg;
        }

        return $this->getCrudToolsMessage('message_ok');
    }

    protected function getSetDefaultErrorMessage(): string
    {
        $lang = is_array($this->langArr ?? null) ? $this->langArr : [];
        $msg = trim((string) ($lang['default_switch_error'] ?? ''));
        if ($msg !== '') {
            return $msg;
        }

        $msg = $this->getCrudToolsMessage('default_switch_error');
        if ($msg !== '') {
            return $msg;
        }

        return $this->getCrudToolsMessage('message_error_generic');
    }

    protected function renderDefaultFieldHtml(object $listItem): string
    {
        $itemIdField = method_exists($this, 'getItemIdField') ? $this->getItemIdField() : 'id';

        return $this->load->view(
            'components/crud/default_field',
            [
                'listItem' => $listItem,
                'itemIdField' => $itemIdField,
                'controllerUrl' => $this->paginationUrl(),
                'pageTexts' => method_exists($this, 'getPageTexts') ? $this->getPageTexts() : [],
            ],
            true
        );
    }

    /**
     * Normaliza respuestas API envueltas en { data: ... }.
     *
     * @param mixed $record
     */
    protected function normalizeCrudApiRecord($record): object
    {
        if (is_array($record)) {
            $record = (object) $record;
        }

        $itemIdField = method_exists($this, 'getItemIdField') ? $this->getItemIdField() : 'id';

        while (is_object($record) && isset($record->data)) {
            $hasIdentity = isset($record->{$itemIdField}) && $record->{$itemIdField} !== ''
                || isset($record->id) && $record->id !== '';
            if ($hasIdentity) {
                break;
            }

            $inner = $record->data;
            if (is_object($inner)) {
                $record = $inner;
            } elseif (is_array($inner)) {
                $record = (object) $inner;
            } else {
                break;
            }
        }

        return (object) $record;
    }

    /**
     * @param mixed $itemId
     * @return object|array<string, mixed>
     */
    protected function loadEntityForSetDefault($itemId)
    {
        $query = [];
        $props = $this->getSetDefaultEntityProps();
        if (is_array($props) && $props !== []) {
            $query['props'] = $props;
        }

        $entity = $this->getObjectFromApi("{$this->entityPath}/{$itemId}", $query);
        if ($this->hasError($entity)) {
            return $entity;
        }

        return $this->normalizeCrudApiRecord($entity);
    }

    /**
     * Ruta API dedicada para marcar por defecto (p. ej. unit/{id}/setDefault).
     * null = usar PUT completo del recurso.
     */
    protected function getDedicatedSetDefaultApiPath($itemId): ?string
    {
        return null;
    }

    protected function resolveSetDefaultErrorMessage($response): string
    {
        if (method_exists($this, 'resolveApiErrorMessage') && is_array($response)) {
            $message = trim((string) $this->resolveApiErrorMessage($response));
            if ($message !== '') {
                return $message;
            }
        }

        return $this->getSetDefaultErrorMessage();
    }

    /**
     * @return array<string, mixed>|object|null
     */
    protected function invokeSetDefaultApi(object $entity, $itemId)
    {
        $companyId = $this->getCompanyId();
        $dedicatedPath = $this->getDedicatedSetDefaultApiPath($itemId);

        if ($dedicatedPath !== null) {
            $response = $this->put($dedicatedPath, [
                'company_id' => $companyId,
            ]);
            if (!$this->hasError($response)) {
                return $response;
            }
            log_message('error', 'CrudSetDefaultAction dedicated PUT failed for '
                . $this->entityPath . '/' . $itemId . ': ' . json_encode($response));
        }

        $entityForPayload = $this->loadEntityForSetDefault($itemId);
        if (!$this->hasError($entityForPayload)) {
            $entity = $entityForPayload;
        }

        $payload = $this->buildSetDefaultUpdatePayload($entity, $itemId);
        $payload['company_id'] = $companyId;
        if (!array_key_exists('default', $payload)) {
            $payload['default'] = true;
        }

        $response = $this->put("{$this->entityPath}/{$itemId}", $payload);
        if (!$this->hasError($response)) {
            return $response;
        }

        log_message('error', 'CrudSetDefaultAction resource PUT failed for '
            . $this->entityPath . '/' . $itemId . ' payload=' . json_encode($payload)
            . ' response=' . json_encode($response));

        return $response;
    }

    protected function isInactiveDefaultCandidate(object $entity): bool
    {
        $statusField = method_exists($this, 'getStatusField') ? $this->getStatusField() : 'status';
        if (!isset($entity->{$statusField})) {
            return false;
        }

        return !$entity->{$statusField};
    }

    protected function getInactiveDefaultErrorMessage(): string
    {
        $lang = is_array($this->langArr ?? null) ? $this->langArr : [];
        $msg = trim((string) ($lang['default_switch_inactive_error'] ?? ''));
        if ($msg !== '') {
            return $msg;
        }

        return $this->getSetDefaultErrorMessage();
    }

    public function run($itemId)
    {
        $entity = $this->loadEntityForSetDefault($itemId);
        if ($this->hasError($entity)) {
            $notFound = $this->getCrudToolsMessage('message_not_found');
            $payload = is_array($entity) ? $entity : ['message' => $notFound];
            $this->jsonError($payload, $notFound, 404);
            return;
        }

        if ($this->isDefaultRecord($entity)) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'html' => $this->renderDefaultFieldHtml($entity),
                'message' => $this->getSetDefaultSuccessMessage(),
                'alreadyDefault' => true,
            ]);
            return;
        }

        if ($this->isInactiveDefaultCandidate($entity)) {
            $this->jsonError(
                ['message' => $this->getInactiveDefaultErrorMessage()],
                $this->getInactiveDefaultErrorMessage(),
                422
            );
            return;
        }

        $response = $this->invokeSetDefaultApi($entity, $itemId);

        if ($this->hasError($response)) {
            $this->jsonError($response, $this->resolveSetDefaultErrorMessage($response), 422);
            return;
        }

        $updated = $this->normalizeCrudApiRecord($response);
        if (!$this->isValidSetDefaultRecord($updated, $itemId)) {
            $updated = clone $entity;
            $updated->isDefault = true;
            $updated->is_default = true;
        }
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'html' => $this->renderDefaultFieldHtml($updated),
            'message' => $this->getSetDefaultSuccessMessage(),
            'itemId' => (string) $itemId,
        ]);
    }

    protected function isDefaultRecord(object $entity): bool
    {
        return !empty($entity->isDefault) || !empty($entity->is_default);
    }

    protected function isValidSetDefaultRecord(object $record, $itemId): bool
    {
        if ($this->isDefaultRecord($record)) {
            return true;
        }

        $itemIdField = method_exists($this, 'getItemIdField') ? $this->getItemIdField() : 'id';
        $recordId = $record->{$itemIdField} ?? null;
        if ($recordId === null || $recordId === '') {
            $recordId = $record->id ?? null;
        }

        return $recordId !== null && $recordId !== '' && (string) $recordId === (string) $itemId;
    }
}
