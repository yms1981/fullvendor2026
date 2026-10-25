<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductInventoryExport implements FromCollection, WithHeadings, WithMapping
{
    /** @var Collection<int, object> */
    protected Collection $rows;

    /** @var list<string> */
    protected array $headings;

    protected bool $includeRealInventory;

    /**
     * @param iterable<int, object|array<string, mixed>> $rows
     * @param list<string> $headings
     */
    public function __construct(iterable $rows, array $headings, bool $includeRealInventory = false)
    {
        $this->rows = collect($rows);
        $this->headings = $headings;
        $this->includeRealInventory = $includeRealInventory;
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    /**
     * @param mixed $row
     * @return list<string|float>
     */
    public function map($row): array
    {
        $item = is_array($row) ? (object) $row : $row;

        $mapped = [
            (string) ($item->sku ?? ''),
            (string) ($item->name ?? ''),
            $this->formatNumber($item->sale_price ?? null),
            $this->formatNumber($item->inventory_qty ?? null),
        ];

        if ($this->includeRealInventory) {
            $mapped[] = '';
        }

        $mapped[] = (string) ($item->categories ?? '');

        return $mapped;
    }

    /**
     * @param mixed $value
     */
    private function formatNumber($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        if (! is_numeric($value)) {
            return (string) $value;
        }

        return number_format((float) $value, 2, '.', '');
    }
}
