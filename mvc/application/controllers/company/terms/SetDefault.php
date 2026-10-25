<?php

class SetDefault extends Company_Controller
{
    use IsCRUD;
    use IsAction;
    use CrudSetDefaultAction;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('termsofsale', 'company/term/', 'terms');
        $this->langArr = $this->getModuleLang('cmp-terms');
    }

    protected function getSetDefaultEntityProps(): ?array
    {
        return ['term_id', 'name', 'term_status', 'company_id'];
    }

    protected function buildSetDefaultUpdatePayload(object $entity, $itemId): array
    {
        return [
            'term_id' => (int) ($itemId ?: ($entity->term_id ?? 0)),
            'name' => trim((string) ($entity->name ?? '')),
            'default' => true,
        ];
    }

    protected function getDedicatedSetDefaultApiPath($itemId): ?string
    {
        return "{$this->entityPath}/{$itemId}/setDefault";
    }

    protected function getItemIdField(): string
    {
        return 'term_id';
    }

    protected function getStatusField(): string
    {
        return 'term_status';
    }
}
