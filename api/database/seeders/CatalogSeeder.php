<?php

namespace Database\Seeders;

use App\Models\Catalog;
use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $company1 = Company::where('name', 'Compania 1')->first();
        $company2 = Company::where('name', 'Compania 2')->first();

        $categories = [
            $company1->company_id => Category::where('company_id', $company1->company_id)
                ->where('category_status', 1)
                ->pluck('cat_id', 'category_name')
                ->toArray(),
            $company2->company_id => Category::where('company_id', $company2->company_id)
                ->where('category_status', 1)
                ->pluck('cat_id', 'category_name')
                ->toArray(),
        ];

        $products = [
            $company1->company_id => Product::where('company_id', $company1->company_id)
                ->where('status', 1)
                ->with('categories')
                ->get(),
            $company2->company_id => Product::where('company_id', $company2->company_id)
                ->where('status', 1)
                ->with('categories')
                ->get(),
        ];

        $catalogs = [
            // --- COMPANY 1 ---
            [
                'company_id' => $company1->company_id,
                'catalog_name' => 'Fullcatalogo',
                'status' => 1,
                'category_names' => fn($c) => array_keys($c),
                'product_filter' => fn($p) => $p->categories->isEmpty(),
                'default_catalog' => 1
            ],
            [
                'company_id' => $company1->company_id,
                'catalog_name' => 'Catalogo1',
                'status' => 1,
                'category_names' => ['Categoria 1', 'Categoria 3', 'Categoria 4'],
                'product_filter' => fn($p) =>
                $p->categories->pluck('category_name')->intersect(['Categoria 1', 'Categoria 3', 'Categoria 4'])->isNotEmpty(),
            ],
            [
                'company_id' => $company1->company_id,
                'catalog_name' => 'Catalogo2',
                'status' => 1,
                'category_names' => ['Categoria 1', 'Categoria 2'],
                'product_filter' => fn($p) =>
                $p->categories->pluck('category_name')->intersect(['Categoria 1', 'Categoria 2'])->isNotEmpty(),
                'extra_skus' => ['ProdZZZE', 'ProdCCCE SC'],
            ],

            // --- COMPANY 2 ---
            [
                'company_id' => $company2->company_id,
                'catalog_name' => 'Fullcatalogo',
                'status' => 1,
                'category_names' => fn($c) => array_keys($c),
                'product_filter' => fn($p) => $p->categories->isEmpty(),
                'default_catalog' => 1
            ],
            [
                'company_id' => $company2->company_id,
                'catalog_name' => 'Catalogo1',
                'status' => 1,
                'category_names' => ['Categoria 6', 'Categoria 8'],
                'product_filter' => fn($p) =>
                $p->categories->pluck('category_name')->intersect(['Categoria 6', 'Categoria 8'])->isNotEmpty(),
            ],
            [
                'company_id' => $company2->company_id,
                'catalog_name' => 'Catalogo3',
                'status' => 1,
                'category_names' => ['Categoria 5', 'Categoria 7'],
                'product_filter' => fn($p) =>
                $p->categories->pluck('category_name')->intersect(['Categoria 5', 'Categoria 7'])->isNotEmpty(),
                'extra_skus' => ['Prod-YYYJ', 'Prod-YYYJ A1'],
            ],
        ];

        foreach ($catalogs as $data) {
            $companyId = $data['company_id'];

            $categoryNames = is_callable($data['category_names'])
                ? $data['category_names']($categories[$companyId])
                : $data['category_names'];

            $categoryIds = array_filter(
                array_map(fn($name) => $categories[$companyId][$name] ?? null, $categoryNames)
            );

            $filteredProducts = $products[$companyId]
                ->filter($data['product_filter'])
                ->pluck('pro_id')
                ->toArray();

            $extraSkus = $data['extra_skus'] ?? [];
            if (!empty($extraSkus)) {
                $extraProducts = Product::where('company_id', $companyId)
                    ->whereIn('sku', $extraSkus)
                    ->pluck('pro_id')
                    ->toArray();

                $filteredProducts = array_unique(array_merge($filteredProducts, $extraProducts));
            }

            $catalog = Catalog::create([
                'company_id' => $companyId,
                'catalog_name' => $data['catalog_name'],
                'status' => $data['status'],
                'default_catalog' => $data['default_catalog'] ?? 0,
                'cat_id' => $data['catalog_name']
            ]);

            if (!empty($categoryIds)) {
                $attachData = [];
                $order = 1;
                foreach ($categoryIds as $catId) {
                    $attachData[$catId] = ['order' => $order++];
                }

                $catalog->categories()->attach($attachData);
            }

            if (!empty($filteredProducts)) {
                $catalog->products()->attach($filteredProducts);
            }
        }
    }
}
