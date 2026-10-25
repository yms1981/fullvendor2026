<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TestingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            CurrencySeeder::class,
            LanguageSeeder::class,
            CompanySeeder::class,
            TermsOfSaleSeeder::class,
            UnitSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
            MobileProductSeeder::class,
            CatalogSeeder::class,
            CustomerGroupSeeder::class,
            SellerSeeder::class,
            CustomerSeeder::class,
            MobileCustomerSeeder::class,
            WarehouseSeeder::class,
            UserSeeder::class,
            MobileUserSeeder::class,
        ]);
    }
}
