<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Facades\Excel;

class MobileProductSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('name', 'Compania 3')->first();

        if (!$company) {
            $this->command->error('No se encontró la compañía.');
            return;
        }

        $units = Unit::where('company_id', $company->company_id)
            ->pluck('unit_id', 'unit')
            ->toArray();

        $categories = Category::where('company_id', $company->company_id)
            ->pluck('cat_id', 'category_name')
            ->toArray();

        $path = database_path('seeders/data/products.csv');

        if (!File::exists($path)) {
            $this->command->error("El archivo CSV no existe en: $path");
            return;
        }

        $rows = Excel::toCollection(null, $path)->first();
        $headers = $rows->shift();

        foreach ($rows as $row) {
            $record = collect($headers)->combine($row)->map(fn($v) => trim($v))->toArray();

            $unitId = $units[$record['unidad medida']] ?? null;

            $categoryNames = explode(',', $record['categoria']);
            $categoryIds = [];
            foreach ($categoryNames as $catName) {
                $catName = trim($catName);
                if (isset($categories[$catName])) {
                    $categoryIds[] = $categories[$catName];
                }
            }

            $data = [
                'language_id' => $company->language_id,
                'company_id'     => $company->company_id,
                'sku'            => $record['SKU'] ?? null,
                'name'           => $record['name'] ?? null,
                'stock'          => (int) ($record['stock'] ?? 0),
                'sale_price'     => (float) ($record['sale_price'] ?? 0),
                'purchase_price' => (float) ($record['purchase_price'] ?? 0),
                'fob_price'      => (float) ($record['fob_price'] ?? 0),
                'minimum_stock'   => (int) ($record['cantidad minima'] ?? 0),
                'status'         => (bool) ($record['Activo'] ?? false),
                'unit_id'        => $unitId,
            ];

            $product = Product::updateOrCreate(
                ['sku' => $data['sku'], 'company_id' => $company->company_id],
                $data
            );

            if (!empty($categoryIds) && method_exists($product, 'categories')) {
                $product->categories()->sync($categoryIds);
            }
        }
    }
}
