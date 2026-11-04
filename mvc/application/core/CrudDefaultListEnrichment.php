<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Marca isDefault en filas de listados CRUD según el default_*_id de la empresa.
 */
trait CrudDefaultListEnrichment
{
    /**
     * @return array{companyField: string, itemField: string}|null
     */
    protected function resolveCrudCompanyDefaultMapping(): ?array
    {
        $entity = trim((string) ($this->entityPath ?? ''));
        $mappings = [
            'unit' => ['companyField' => 'default_unit_id', 'itemField' => 'unit_id'],
            'termsofsale' => ['companyField' => 'default_term_id', 'itemField' => 'term_id'],
            'customergroup' => ['companyField' => 'default_customer_group_id', 'itemField' => 'group_id'],
            'vendor' => ['companyField' => 'default_seller_id', 'itemField' => 'id'],
        ];

        if (isset($mappings[$entity])) {
            return $mappings[$entity];
        }

        if (method_exists($this, 'getItemIdField')) {
            $itemField = $this->getItemIdField();
            foreach ($mappings as $mapping) {
                if ($mapping['itemField'] === $itemField) {
                    return $mapping;
                }
            }
        }

        return null;
    }

    /**
     * Ruta MVC relativa a company_url() para marcar por defecto desde la grilla.
     * null = solo indicador visual (sin switch).
     */
    protected function resolveCrudSetDefaultMvcPath(): ?string
    {
        $entity = trim((string) ($this->entityPath ?? ''));
        $paths = [
            'unit' => 'units/setDefault',
            'termsofsale' => 'terms/setDefault',
            'customergroup' => 'groups/setDefault',
        ];

        return $paths[$entity] ?? null;
    }

    /**
     * @param int|string $itemId
     */
    protected function buildCrudSetDefaultUrl($itemId): string
    {
        $path = $this->resolveCrudSetDefaultMvcPath();
        if ($path === null || $itemId === '' || $itemId === null) {
            return '';
        }

        return company_url(rtrim($path, '/') . '/' . $itemId);
    }

    protected function listHasDefaultColumn(): bool
    {
        if (method_exists($this, 'getFieldTemplates')) {
            $templates = $this->getFieldTemplates();
            if (isset($templates['isDefault']) && $templates['isDefault'] === 'components/crud/default_field') {
                return true;
            }
        }

        if (method_exists($this, 'getListItemFields')) {
            $fields = $this->getListItemFields([]);
            if (is_array($fields) && in_array('isDefault', $fields, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param object|null $paginatedList
     * @return object|null
     */
    protected function enrichCrudListDefaultFlags($paginatedList)
    {
        if (!$this->listHasDefaultColumn()) {
            return $paginatedList;
        }
        if (!is_object($paginatedList) || !isset($paginatedList->data) || !is_array($paginatedList->data)) {
            return $paginatedList;
        }

        $mapping = $this->resolveCrudCompanyDefaultMapping();
        if ($mapping === null) {
            return $paginatedList;
        }

        $companyId = method_exists($this, 'getCompanyId') ? (int) $this->getCompanyId() : 0;
        if ($companyId <= 0) {
            return $paginatedList;
        }

        $companyField = $mapping['companyField'];
        $itemField = $mapping['itemField'];

        $company = $this->getObjectFromApi('company/' . $companyId, [
            'props' => ['company_id', $companyField],
        ]);
        if (method_exists($this, 'hasError') && $this->hasError($company)) {
            return $paginatedList;
        }

        $defaultId = (int) (is_object($company) ? ($company->{$companyField} ?? 0) : 0);
        $setDefaultPath = $this->resolveCrudSetDefaultMvcPath();

        foreach ($paginatedList->data as $index => $item) {
            if (is_array($item)) {
                $item = (object) $item;
            }
            if (!is_object($item)) {
                continue;
            }

            $itemId = (int) ($item->{$itemField} ?? 0);
            $isDefault = $itemId > 0 && $defaultId > 0 && $itemId === $defaultId;
            $item->isDefault = $isDefault;
            $item->is_default = $isDefault;
            if ($setDefaultPath !== null && $itemId > 0) {
                $item->setDefaultUrl = $this->buildCrudSetDefaultUrl($itemId);
            }
            $paginatedList->data[$index] = $item;
        }

        return $paginatedList;
    }
}
