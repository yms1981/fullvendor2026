<?php

class SetDefault extends Company_Controller
{
    use IsCRUD;
    use IsAction;
    use CrudSetDefaultAction;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('unit', 'company/unit/', 'units');
        $this->langArr = $this->getModuleLang('unit-languaje');
    }

    protected function getDedicatedSetDefaultApiPath($itemId): ?string
    {
        return "{$this->entityPath}/{$itemId}/setDefault";
    }

    protected function getItemIdField(): string
    {
        return 'unit_id';
    }

    protected function getStatusField(): string
    {
        return 'status';
    }

    protected function getPageTexts($pageContentObject = null): array
    {
        $lang = is_array($this->langArr ?? null) ? $this->langArr : [];

        return [
            'fields' => [
                'isDefault' => $lang['default-column'] ?? '',
            ],
            'default-column' => $lang['default-column'] ?? '',
        ];
    }

    protected function getSetDefaultEntityProps(): ?array
    {
        return ['unit_id', 'unit', 'description', 'formato', 'decimales', 'status', 'company_id'];
    }

    protected function buildSetDefaultUpdatePayload(object $entity, $itemId): array
    {
        $unitId = (int) ($itemId ?: ($entity->unit_id ?? 0));

        $unitName = trim((string) ($entity->unit ?? ''));
        if ($unitName === '' && $unitId > 0) {
            $unitName = 'unit-' . $unitId;
        }

        $description = trim((string) ($entity->description ?? ''));
        if ($description === '') {
            $description = $unitName;
        }
        if ($description === '') {
            $description = '-';
        }

        $formato = (string) ($entity->formato ?? 'entero');
        if (!in_array($formato, ['entero', 'decimal'], true)) {
            $formato = 'entero';
        }

        $payload = [
            'unit_id' => $unitId,
            'unit' => $unitName,
            'description' => mb_substr($description, 0, 100),
            'formato' => $formato,
            'default' => true,
        ];

        if (isset($entity->decimales) && $entity->decimales !== '' && $entity->decimales !== null) {
            $payload['decimales'] = (int) $entity->decimales;
        }

        return $payload;
    }
}
