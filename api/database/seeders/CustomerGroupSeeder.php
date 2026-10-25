<?php

namespace Database\Seeders;

use App\Enums\CustomerGroupTypes;
use App\Models\Company;
use App\Models\CustomerGroup;
use Illuminate\Database\Seeder;

class CustomerGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company1 = Company::where('name', 'Compania 1')->first();
        $company2 = Company::where('name', 'Compania 2')->first();
        $company3 = Company::where('name', 'Compania 3')->first();

        $default1 = CustomerGroup::create(['company_id' => $company1->company_id, 'name' => 'Publico', 'percentage_on_price' => CustomerGroupTypes::Increase, 'percent_price_amount' => 25]);
        CustomerGroup::insert([
            ['company_id' => $company1->company_id, 'name' => 'Lista', 'percentage_on_price' => null, 'percent_price_amount' => null],
            ['company_id' => $company1->company_id, 'name' => 'Mayorista', 'percentage_on_price' => CustomerGroupTypes::Decrease, 'percent_price_amount' => 15],
            ['company_id' => $company1->company_id, 'name' => 'Distribuidor', 'percentage_on_price' => CustomerGroupTypes::Decrease, 'percent_price_amount' => 30],
            ['company_id' => $company1->company_id, 'name' => 'Exportacion', 'percentage_on_price' => CustomerGroupTypes::Increase, 'percent_price_amount' => 40]
        ]);
        $company1->update(['default_customer_group_id' => $default1->group_id]);

        $default2 = CustomerGroup::create(['company_id' => $company2->company_id, 'name' => 'Mayorista', 'percentage_on_price' => CustomerGroupTypes::Increase, 'percent_price_amount' => 25]);
        CustomerGroup::insert([
            ['company_id' => $company2->company_id, 'name' => 'Lista', 'percentage_on_price' => null, 'percent_price_amount' => null],
            ['company_id' => $company2->company_id, 'name' => 'Distribuidor', 'percentage_on_price' => CustomerGroupTypes::Increase, 'percent_price_amount' => 35],
            ['company_id' => $company2->company_id, 'name' => 'Exportacion', 'percentage_on_price' => CustomerGroupTypes::Decrease, 'percent_price_amount' => 45],
            ['company_id' => $company2->company_id, 'name' => 'Dealers', 'percentage_on_price' => CustomerGroupTypes::Decrease, 'percent_price_amount' => 30]
        ]);
        $company2->update(['default_customer_group_id' => $default2->group_id]);

        $default3 = CustomerGroup::create(['company_id' => $company3->company_id, 'name' => 'Retailer']);
        CustomerGroup::insert([
            ['company_id' => $company3->company_id, 'name' => 'Yiwu', 'percentage_on_price' => CustomerGroupTypes::Decrease, 'percent_price_amount' => 70],
            ['company_id' => $company3->company_id, 'name' => 'Fullvendor Group', 'percentage_on_price' => null, 'percent_price_amount' => null],
        ]);
        $company3->update(['default_customer_group_id' => $default3->group_id]);
    }
}
