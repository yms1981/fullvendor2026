<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Mensajes de validación de categorías según idioma de la empresa (JSON).
 */
trait SupportsCategoryValidationMessages
{
    protected function getCategoryLangSection(): array
    {
        if (method_exists($this, 'getLangJsonSection')) {
            $fromJson = $this->getLangJsonSection('cmp-categorias');
            if (! empty($fromJson)) {
                return $fromJson;
            }
            $fromJson = $this->getLangJsonSection('cmp_categorias');
            if (! empty($fromJson)) {
                return $fromJson;
            }
        }

        return is_array($this->langArr ?? null) ? $this->langArr : [];
    }

    protected function formatCategoryNameExistsMessage(string $name): string
    {
        $name = trim($name);
        $lang = $this->getCategoryLangSection();

        $template = (string) ($lang['category_name_already_exists'] ?? '');
        if ($name !== '' && $template !== '' && str_contains($template, '%')) {
            return sprintf($template, $name);
        }

        if ($template !== '') {
            return $template;
        }

        return (string) ($lang['category_exists'] ?? '');
    }

    /**
     * @param array{message?: mixed, errors?: array} $payload
     */
    protected function isCategoryNameDuplicateApiError(array $payload): bool
    {
        $parts = [strtolower((string) ($payload['message'] ?? ''))];
        foreach ($payload['errors'] ?? [] as $fieldErrors) {
            foreach ((array) $fieldErrors as $err) {
                $parts[] = strtolower((string) $err);
            }
        }

        $haystack = implode(' ', $parts);

        if (isset($payload['errors']['category_name'])) {
            return true;
        }

        return str_contains($haystack, 'already exists')
            || str_contains($haystack, 'ya existe')
            || str_contains($haystack, 'ya ha sido tomado')
            || str_contains($haystack, 'has been taken')
            || str_contains($haystack, 'category name')
            || str_contains($haystack, 'nombre de categoria')
            || str_contains($haystack, 'nombre de categoría')
            || str_contains($haystack, 'categoria');
    }

    protected function localizeCategoryApiErrorResponse(array $response, string $name): array
    {
        $payload = $this->unpackApiErrorResponse($response, '');
        if (! $this->isCategoryNameDuplicateApiError($payload)) {
            return $response;
        }

        $localized = $this->formatCategoryNameExistsMessage($name);
        if ($localized === '') {
            return $response;
        }

        if (isset($response['message']) && is_array($response['message'])) {
            $response['message']['message'] = $localized;
            if (! isset($response['message']['errors']) || ! is_array($response['message']['errors'])) {
                $response['message']['errors'] = [];
            }
            $response['message']['errors']['category_name'] = [$localized];
        } else {
            $response['message'] = $localized;
        }

        $response['errors'] = array_merge(
            is_array($response['errors'] ?? null) ? $response['errors'] : [],
            ['category_name' => [$localized]]
        );

        return $response;
    }
}
