<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CategoriesExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $categories;

    public function __construct($categories)
    {
        $this->category = $categories;
    }

    public function collection()
    {
        return $this->category->map(function ($category) {

            return [
                $category->cat_id,
                $category->category_name,
                $category->category_created_at,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Category ID',
            'Category Name',
            'Created Date',
        ];
    }
}
