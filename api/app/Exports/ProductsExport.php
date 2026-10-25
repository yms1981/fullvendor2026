<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsExport implements FromCollection, WithHeadings, WithMapping
{
    /** @var Collection */
    protected $products;

    public function __construct($products)
    {
        $this->products = $products;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->products;
    }

    public function headings(): array
    {
        return [
            'Product Name',
            'Category',
            'SKU',
            'Barcode',
            'Unit Type',
            'Sale Price',
            'FOB Price',
            'Purchase Price',
            'Stock',
            'MOQ',
            'Notify Minimum Stock',
            'Tags',
            'Locations',
            'Descriptions',
            'Status',
        ];
    }

    /**
     * @param mixed $product
     * @return array<int, mixed>
     */
    public function map($product): array
    {
        $row = is_array($product) ? (object) $product : $product;

        return [
            $this->sanitizeExportText($row->name ?? ''),
            $this->sanitizeExportText($row->categories ?? ''),
            $this->sanitizeExportText($row->sku ?? ''),
            $this->sanitizeExportText($row->barcode ?? ''),
            $this->sanitizeExportText($row->unit_type ?? ''),
            $this->formatExportNumber($row->sale_price ?? null),
            $this->formatExportNumber($row->fob_price ?? null),
            $this->formatExportNumber($row->purchase_price ?? null),
            $row->stock ?? '',
            $this->formatExportNumber($row->minimum_stock ?? null),
            $row->notify_minimum_stock ?? '',
            $this->sanitizeExportText($row->tags ?? '', true),
            $this->sanitizeExportText($row->locations ?? ''),
            $this->sanitizeExportText($row->descriptions ?? ''),
            $row->status ?? '',
        ];
    }

    private function sanitizeExportText($value, bool $tagsOnly = false): string
    {
        $text = (string) $value;
        if ($tagsOnly) {
            $text = str_replace(';', ' ', $text);
        } else {
            $text = str_replace([',', ';'], ' ', $text);
        }

        return trim($text, ';, ');
    }

    /**
     * @param mixed $value
     * @return string|mixed
     */
    private function formatExportNumber($value)
    {
        if ($value === null || $value === '') {
            return '';
        }
        if (!is_numeric($value)) {
            return $value;
        }

        return number_format((float) $value, 2, '.', '');
    }
}
