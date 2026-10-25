<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Company;
use App\Models\Image;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Unit;
use Illuminate\Database\Seeder;


class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $company1 = Company::where('name', 'Compania 1')->first();
        $company2 = Company::where('name', 'Compania 2')->first();

        $units = [
            $company1->company_id => Unit::where('company_id', $company1->company_id)->pluck('unit_id', 'unit')->toArray(),
            $company2->company_id => Unit::where('company_id', $company2->company_id)->pluck('unit_id', 'unit')->toArray(),
        ];

        $categories = [
            $company1->company_id => Category::where('company_id', $company1->company_id)->pluck('cat_id', 'category_name')->toArray(),
            $company2->company_id => Category::where('company_id', $company2->company_id)->pluck('cat_id', 'category_name')->toArray(),
        ];

        $products = [
            // ================= COMPANY 1 =================
            [
                'company'    => $company1,
                'data' => [
                    'name' => 'Producto 1',
                    'sku' => 'ProdXXXA',
                    'stock' => 5,
                    'minimum_stock' => 2,
                    'sem_red_hasta' => 6,
                    'sem_amarillo_desde' => 7,
                    'sem_amarillo_hasta' => 15,
                    'sem_verde_desde' => 16,
                    'unit_id' => 'Unidad',
                    'status' => 1
                ],
                'categories' => ['Categoria 1', 'Categoria 2', 'Categoria 3'],
                'with_image' => true,
            ],
            [
                'company'    => $company1,
                'data' => [
                    'name' => 'Producto 2',
                    'sku' => 'ProdXXB',
                    'stock' => 20,
                    'sem_red_hasta' => 10,
                    'sem_amarillo_desde' => 11,
                    'sem_amarillo_hasta' => 20,
                    'sem_verde_desde' => 21,
                    'unit_id' => 'Kgs',
                    'status' => 1,
                    'notify_minimum_stock' => 0
                ],
                'categories' => ['Categoria 4', 'Categoria 3'],
                'with_image' => false,
            ],
            [
                'company'    => $company1,
                'data' => [
                    'name' => 'Producto 3',
                    'sku' => 'ProdYYYC',
                    'stock' => 30,
                    'sem_red_hasta' => 15,
                    'sem_amarillo_desde' => 11,
                    'sem_amarillo_hasta' => 25,
                    'sem_verde_desde' => 26,
                    'unit_id' => 'Unidad',
                    'status' => 1,
                    'notify_minimum_stock' => 0
                ],
                'categories' => ['Categoria 2', 'Categoria 3'],
                'with_image' => true,
            ],
            [
                'company'    => $company1,
                'data' => [
                    'name' => 'Producto 4',
                    'sku' => 'ProdYYD',
                    'stock' => 10,
                    'minimum_stock' => 3,
                    'unit_id' => 'Kgs',
                    'status' => 1,
                    'notify_minimum_stock' => 0
                ],
                'categories' => ['Categoria 1'],
                'with_image' => true,
            ],
            [
                'company'    => $company1,
                'data' => [
                    'name' => 'Producto 5',
                    'sku' => 'ProdZZZE',
                    'stock' => 0,
                    'unit_id' => 'Unidad',
                    'status' => 0,
                    'notify_minimum_stock' => 0
                ],
                'categories' => [],
                'with_image' => true,
            ],
            [
                'company'    => $company1,
                'data' => [
                    'name' => 'Producto 6',
                    'sku' => 'ProdCCCE SC',
                    'stock' => 4,
                    'minimum_stock' => 5,
                    'unit_id' => 'Cm',
                    'status' => 1,
                    'notify_minimum_stock' => 0
                ],
                'categories' => [],
                'with_image' => false,
            ],

            // ================= COMPANY 2 =================
            [
                'company'    => $company2,
                'data' => [
                    'name' => 'Producto 7',
                    'sku' => 'ProdXXXA',
                    'stock' => 2,
                    'minimum_stock' => 2,
                    'sem_red_hasta' => 2,
                    'sem_amarillo_desde' => 7,
                    'sem_amarillo_hasta' => 15,
                    'sem_verde_desde' => 16,
                    'unit_id' => 'Kgs',
                    'status' => 1
                ],
                'categories' => ['Categoria 5', 'Categoria 6', 'Categoria 7'],
                'with_image' => true,
            ],
            [
                'company'    => $company2,
                'data' => [
                    'name' => 'Producto 8',
                    'sku' => 'Prod-YYYG',
                    'stock' => 15,
                    'sem_red_hasta' => 10,
                    'sem_amarillo_desde' => 11,
                    'sem_amarillo_hasta' => 20,
                    'sem_verde_desde' => 21,
                    'unit_id' => 'Unidad',
                    'status' => 1,
                    'notify_minimum_stock' => 0
                ],
                'categories' => ['Categoria 8', 'Categoria 7', 'Categoria 5'],
                'with_image' => true,
            ],
            [
                'company'    => $company2,
                'data' => [
                    'name' => 'Producto 9',
                    'sku' => 'Prod-ZZZH',
                    'stock' => 45,
                    'sem_red_hasta' => 20,
                    'sem_amarillo_desde' => 21,
                    'sem_amarillo_hasta' => 25,
                    'sem_verde_desde' => 26,
                    'unit_id' => 'Kgs',
                    'status' => 1,
                    'notify_minimum_stock' => 0
                ],
                'categories' => ['Categoria 5'],
                'with_image' => true,
            ],
            [
                'company'    => $company2,
                'data' => [
                    'name' => 'Producto 10',
                    'sku' => 'Prod-ZZZI',
                    'stock' => 30,
                    'minimum_stock' => 3,
                    'unit_id' => 'Unidad',
                    'status' => 1,
                    'notify_minimum_stock' => 0
                ],
                'categories' => ['Categoria 7', 'Categoria 8'],
                'with_image' => false,
            ],
            [
                'company'    => $company2,
                'data' => [
                    'name' => 'Producto 11',
                    'sku' => 'Prod-YYYJ',
                    'stock' => 0,
                    'status' => 0,
                    'notify_minimum_stock' => 0
                ],
                'categories' => [],
                'with_image' => true,
            ],
            [
                'company'    => $company2,
                'data' => [
                    'name' => 'Producto 12',
                    'sku' => 'Prod-YYYJ A1',
                    'stock' => 0,
                    'sem_red_hasta' => 10,
                    'sem_amarillo_desde' => 11,
                    'sem_amarillo_hasta' => 21,
                    'sem_verde_desde' => 22,
                    'unit_id' => 'Cm',
                    'status' => 1
                ],
                'categories' => [],
                'with_image' => true,
            ],
        ];

        foreach ($products as $p) {
            $companyId = $p['company']->company_id;

            if (!empty($p['data']['unit_id'])) {
                $p['data']['unit_id'] = $units[$companyId][$p['data']['unit_id']] ?? null;
            }

            $product = Product::create(array_merge(
                $p['data'],
                [
                    'company_id' => $companyId,
                    'language_id' => 1,
                    'sale_price' => 100,
                    'fob_price' => 10,
                    'purchase_price' => 100,
                ]
            ));

            if (!empty($p['categories'])) {
                $categoryIds = array_map(fn($name) => $categories[$companyId][$name] ?? null, $p['categories']);
                $product->categories()->attach(array_filter($categoryIds));
            }

            if ($p['with_image']) {
                foreach (range(1, rand(1, 3)) as $i) {
                    $image = Image::factory()->create(['company_id' => $companyId]);

                    ProductImage::create([
                        'product_id' => $product->pro_id,
                        'image_id'   => $image->uuid,
                    ]);
                }
            }
        }
    }
}
