<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PriceListExport implements FromArray, WithHeadings
{
    protected $products;

    public function __construct(array $products)
    {
        $this->products = $products;
    }

    public function array(): array
    {
        return $this->products;
    }

    public function headings(): array
    {
        //utilizar diccionario para no tener los headers hardcodeados
        return ['SKU', 'Nombre', 'Categorías', 'Precio'];
    }
}
