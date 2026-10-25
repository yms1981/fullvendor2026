<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Mensajes de validación de grupos de clientes según idioma de la empresa (JSON).
 */
trait SupportsCustomerGroupValidationMessages
{
    protected function formatCustomerGroupNameExistsMessage(string $name): string
    {
        $name = trim($name);
        $lang = method_exists($this, 'getLangJsonSection')
            ? $this->getLangJsonSection('cmp-group')
            : (is_array($this->langArr ?? null) ? $this->langArr : []);

        $template = (string) ($lang['group_name_already_exists'] ?? '');
        if ($name !== '' && $template !== '' && str_contains($template, '%')) {
            return sprintf($template, $name);
        }

        if ($template !== '') {
            return $template;
        }

        return (string) ($lang['group_existente'] ?? '');
    }

    /**
     * @param array{message?: mixed, errors?: array} $payload
     */
    protected function isCustomerGroupNameDuplicateApiError(array $payload): bool
    {
        $parts = [strtolower((string) ($payload['message'] ?? ''))];
        foreach ($payload['errors'] ?? [] as $fieldErrors) {
            foreach ((array) $fieldErrors as $err) {
                $parts[] = strtolower((string) $err);
            }
        }

        $haystack = implode(' ', $parts);

        if (isset($payload['errors']['name'])) {
            return true;
        }

        return str_contains($haystack, 'already exists')
            || str_contains($haystack, 'ya existe')
            || str_contains($haystack, 'group name')
            || str_contains($haystack, 'nombre del grupo')
            || str_contains($haystack, 'grupo de cliente');
    }

    protected function localizeCustomerGroupApiErrorResponse(array $response, string $name): array
    {
        $payload = $this->unpackApiErrorResponse($response, '');
        if (! $this->isCustomerGroupNameDuplicateApiError($payload)) {
            return $response;
        }

        $localized = $this->formatCustomerGroupNameExistsMessage($name);
        if ($localized === '') {
            return $response;
        }

        if (isset($response['message']) && is_array($response['message'])) {
            $response['message']['message'] = $localized;
            if (! isset($response['message']['errors']) || ! is_array($response['message']['errors'])) {
                $response['message']['errors'] = [];
            }
            $response['message']['errors']['name'] = [$localized];
        } else {
            $response['message'] = $localized;
        }

        $response['errors'] = array_merge(
            is_array($response['errors'] ?? null) ? $response['errors'] : [],
            ['name' => [$localized]]
        );

        return $response;
    }
}
