<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Company;
use App\Models\Image;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $company1 = Company::where('name', 'Compania 1')->first();
        $company2 = Company::where('name', 'Compania 2')->first();
        $company3 = Company::where('name', 'Compania 3')->first();

        $categoriesData = [
            $company1->company_id => [
                ['name' => 'Categoria 1', 'status' => 1, 'with_image' => true],
                ['name' => 'Categoria 2', 'status' => 1, 'with_image' => false],
                ['name' => 'Categoria 3', 'status' => 0, 'with_image' => true],
                ['name' => 'Categoria 4', 'status' => 1, 'with_image' => true],
            ],
            $company2->company_id => [
                ['name' => 'Categoria 5', 'status' => 1, 'with_image' => true],
                ['name' => 'Categoria 6', 'status' => 1, 'with_image' => true],
                ['name' => 'Categoria 7', 'status' => 1, 'with_image' => false],
                ['name' => 'Categoria 8', 'status' => 0, 'with_image' => true],
            ],
            $company3->company_id => [
                ['name' => 'Appliances & Others', 'status' => 1, 'with_image' => true],
                ['name' => 'Bead', 'status' => 1, 'with_image' => true],
                ['name' => 'Candles', 'status' => 0, 'with_image' => true],
                ['name' => 'Cell Accesories', 'status' => 0, 'with_image' => true],
                ['name' => 'Ceramic', 'status' => 1, 'with_image' => true],
                ['name' => 'Coffee Warmer', 'status' => 1, 'with_image' => true],
                ['name' => 'Cookware', 'status' => 1, 'with_image' => true],
                ['name' => 'Decoration', 'status' => 1, 'with_image' => true],
                ['name' => 'Dollar Products', 'status' => 1, 'with_image' => true],
                ['name' => 'Electric Pallets', 'status' => 1, 'with_image' => true],
                ['name' => 'Eyewear', 'status' => 0, 'with_image' => true],
                ['name' => 'Flower', 'status' => 1, 'with_image' => true],
                ['name' => 'Fry pan', 'status' => 1, 'with_image' => true],
                ['name' => 'Gadgets & Utensils & Cleaning Products', 'status' => 1, 'with_image' => true],
                ['name' => 'Glassware', 'status' => 1, 'with_image' => true],
                ['name' => 'Home Deco (Carpet & Rug & Drapery)', 'status' => 1, 'with_image' => true],
                ['name' => 'Hooka', 'status' => 1, 'with_image' => true],
                ['name' => 'Incense Burner', 'status' => 1, 'with_image' => true],
                ['name' => 'Kettle', 'status' => 1, 'with_image' => true],
                ['name' => 'Kitchen Products', 'status' => 0, 'with_image' => true],
                ['name' => 'Linen & Shoes and Others', 'status' => 1, 'with_image' => true],
                ['name' => 'Medical Supplies', 'status' => 0, 'with_image' => true],
                ['name' => 'MIDDLE EAST PRODUCTS', 'status' => 0, 'with_image' => true],
                ['name' => 'Muslim Pictures and Deco', 'status' => 1, 'with_image' => true],
                ['name' => 'New release', 'status' => 0, 'with_image' => true],
                ['name' => 'Organizer', 'status' => 1, 'with_image' => true],
                ['name' => 'Oriental Authentic Food & Drink', 'status' => 1, 'with_image' => true],
                ['name' => 'Porcelain', 'status' => 1, 'with_image' => true],
                ['name' => 'Ramadan 2023', 'status' => 1, 'with_image' => true],
                ['name' => 'Rug', 'status' => 0, 'with_image' => true],
                ['name' => 'Services', 'status' => 0, 'with_image' => true],
                ['name' => 'Shoes and Others', 'status' => 0, 'with_image' => true],
                ['name' => 'Silverware', 'status' => 1, 'with_image' => true],
                ['name' => 'Storack', 'status' => 0, 'with_image' => true],
                ['name' => 'Thermos', 'status' => 1, 'with_image' => true],
                ['name' => 'Travel Needs', 'status' => 1, 'with_image' => true],
                ['name' => 'Trays', 'status' => 1, 'with_image' => true],
                ['name' => 'Turkish Collection', 'status' => 1, 'with_image' => true],
                ['name' => 'Vitamin', 'status' => 0, 'with_image' => true],
                ['name' => 'Without Category', 'status' => 1, 'with_image' => true],
                ['name' => 'categoria def', 'status' => 0, 'with_image' => true],
                ['name' => 'Ramadan', 'status' => 1, 'with_image' => true],
                ['name' => 'Mexican Products', 'status' => 1, 'with_image' => true],
                ['name' => 'Promotion', 'status' => 1, 'with_image' => true],
            ],
        ];

        foreach ($categoriesData as $companyId => $categories) {
            foreach ($categories as $cat) {
                $imageUuid = null;

                if ($cat['with_image']) {
                    $image = Image::factory()->create(['company_id' => $companyId]);
                    $imageUuid = $image->uuid;
                }

                Category::create([
                    'category_name'   => $cat['name'],
                    'company_id'      => $companyId,
                    'category_status' => $cat['status'],
                    'image'          => $imageUuid,
                ]);
            }
        }
    }
}

