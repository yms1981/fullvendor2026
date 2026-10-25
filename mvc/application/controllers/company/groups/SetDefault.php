<?php

class SetDefault extends Company_Controller
{
    use IsCRUD;
    use IsAction;
    use CrudSetDefaultAction;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('customergroup', 'company/group/', 'groups');
        $this->langArr = $this->getModuleLang('cmp-group');
    }

    protected function getSetDefaultEntityProps(): ?array
    {
        return ['group_id', 'name', 'percentage_on_price', 'percent_price_amount', 'group_status', 'company_id'];
    }

    protected function buildSetDefaultUpdatePayload(object $entity, $itemId): array
    {
        $percentage = $entity->percentage_on_price ?? null;
        $normalized = is_scalar($percentage) ? strtolower(trim((string) $percentage)) : '';
        if ($percentage === null || $normalized === '' || $normalized === '0' || $normalized === 'nothing') {
            $percentage = null;
        }

        return [
            'name' => trim((string) ($entity->name ?? '')),
            'percentage_on_price' => $percentage,
            'percent_price_amount' => $entity->percent_price_amount ?? 0,
            'default' => true,
        ];
    }

    protected function getDedicatedSetDefaultApiPath($itemId): ?string
    {
        return "{$this->entityPath}/{$itemId}/setDefault";
    }

    protected function getItemIdField(): string
    {
        return 'group_id';
    }

    protected function getStatusField(): string
    {
        return 'group_status';
    }
}
