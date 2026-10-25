<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Vendor;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SellerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company1 = Company::where('name', 'Compania 1')->first();
        $company2 = Company::where('name', 'Compania 2')->first();
        $company3 = Company::where('name', 'Compania 3')->first();

        $company1SellersAmmount = 11;
        $default1 = Vendor::create(['company_id' => $company1->company_id]);
        $insertData = [];
        for ($i = 1; $i < $company1SellersAmmount; $i++) {
            $insertData[] = ['company_id' => $company1->company_id];
        }
        Vendor::insert($insertData);
        $company1->update(['default_seller_id' => $default1->id]);

        $company2SellersAmmount = 6;
        $default2 = Vendor::create(['company_id' => $company2->company_id]);
        $insertData = [];
        for ($i = 1; $i < $company2SellersAmmount; $i++) {
            $insertData[] = ['company_id' => $company2->company_id];
        }
        Vendor::insert($insertData);
        $company2->update(['default_seller_id' => $default2->id]);

        $default3 = Vendor::create(['company_id' => $company3->company_id]);
        $company3->update(['default_seller_id' => $default3->id]);

    }
}
