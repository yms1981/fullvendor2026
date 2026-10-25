<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Company;
use App\Models\Warehouse;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company1 = Company::where('name', 'Compania 1')->first();
        $company2 = Company::where('name', 'Compania 2')->first();
        $company3 = Company::where('name', 'Compania 3')->first();

        $address_W1 = Address::factory()->create();
        $address_W2 = Address::factory()->create();
        $address_W3 = Address::factory()->create();

        Warehouse::insert([
            //company1
            ['name' => 'Warehouse 1', 'company_id' => $company1->company_id, 'address_id' => $address_W1->id],
            //company2
            ['name' => 'Warehouse 2', 'company_id' => $company2->company_id, 'address_id' => $address_W2->id],
            //company3
            ['name' => 'Warehouse 3', 'company_id' => $company3->company_id, 'address_id' => $address_W3->id],
        ]);
    }
}
