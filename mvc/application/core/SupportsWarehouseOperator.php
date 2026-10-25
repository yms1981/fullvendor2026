<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Crear o actualizar el usuario operador (rol warehouse) vinculado a un depósito físico.
 */
trait SupportsWarehouseOperator
{
    /**
     * @param array<string, mixed> $postData
     * @return array{email: string, password: string, phone: string, user_id: int}
     */
    protected function pullOperatorFields(array &$postData): array
    {
        $operator = [
            'email' => trim((string) ($postData['operator_email'] ?? '')),
            'password' => trim((string) ($postData['operator_password'] ?? '')),
            'phone' => trim((string) ($postData['operator_phone'] ?? '')),
            'user_id' => (int) ($postData['operator_user_id'] ?? 0),
        ];
        unset(
            $postData['operator_email'],
            $postData['operator_password'],
            $postData['operator_phone'],
            $postData['operator_user_id']
        );

        return $operator;
    }

    protected function extractApiResourceId($response): int
    {
        if (is_object($response)) {
            if (isset($response->data->id)) {
                return (int) $response->data->id;
            }
            if (isset($response->id)) {
                return (int) $response->id;
            }
        }
        if (is_array($response)) {
            if (isset($response['data']['id'])) {
                return (int) $response['data']['id'];
            }
            if (isset($response['id'])) {
                return (int) $response['id'];
            }
        }

        return 0;
    }

    /**
     * @return string|null mensaje de error o null si ok / no aplica
     */
    protected function syncWarehouseOperatorUser(int $warehouseId, array $operator, int $companyId): ?string
    {
        if ($warehouseId < 1) {
            return null;
        }

        $userLang = $this->getModuleLang('cmp-user');

        if ($operator['user_id'] > 0) {
            if ($operator['password'] === '' && $operator['phone'] === '') {
                return null;
            }

            if ($operator['email'] === '') {
                return (string) ($userLang['email_is_required'] ?? ($this->langArr['warehouse_operator_error'] ?? ''));
            }

            $payload = [
                'name' => $operator['email'],
                'email' => $operator['email'],
            ];
            if ($operator['password'] !== '') {
                $payload['password'] = $operator['password'];
            }
            if ($operator['phone'] !== '') {
                $payload['phone'] = $operator['phone'];
            }

            $resp = $this->put('user/' . $operator['user_id'], $payload);

            return $this->hasError($resp)
                ? ($this->langArr['warehouse_operator_error'] ?? ($userLang['user_error_save'] ?? ''))
                : null;
        }

        if ($operator['email'] === '') {
            return null;
        }

        if ($operator['password'] === '') {
            return (string) ($userLang['password_is_required'] ?? '');
        }

        $payload = [
            'company_id' => $companyId,
            'role' => 'warehouse',
            'related_id' => $warehouseId,
            'email' => $operator['email'],
            'password' => $operator['password'],
            'name' => $operator['email'],
        ];
        if ($operator['phone'] !== '') {
            $payload['phone'] = $operator['phone'];
        }

        $resp = $this->post('user', $payload);

        return $this->hasError($resp)
            ? ($this->langArr['warehouse_operator_error'] ?? ($userLang['user_error_save'] ?? ''))
            : null;
    }
}
