<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\TermsOfSale;
use Illuminate\Database\Seeder;

class TermsOfSaleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company1 = Company::where('name', 'Compania 1')->first();
        $company2 = Company::where('name', 'Compania 2')->first();
        $company3 = Company::where('name', 'Compania 3')->first();

        $default1 = TermsOfSale::create(['company_id' => $company1->company_id, 'name' => 'Cuenta corriente']);
        TermsOfSale::insert([
            ['company_id' => $company1->company_id, 'name' => 'Contado'],
            ['company_id' => $company1->company_id, 'name' => '60 dias'],
        ]);
        $company1->update(['default_term_id' => $default1->term_id]);

        $default2 = TermsOfSale::create(['company_id' => $company2->company_id, 'name' => '30 dias']);
        TermsOfSale::insert([
            ['company_id' => $company2->company_id, 'name' => 'Contado'],
            ['company_id' => $company2->company_id, 'name' => 'Cuenta corriente'],
        ]);
        $company2->update(['default_term_id' => $default2->term_id]);

        $default3 = TermsOfSale::create(['company_id' => $company3->company_id, 'name' => 'Cash']);
        TermsOfSale::insert([
            ['company_id' => $company3->company_id, 'name' => 'Check 30 days'],
            ['company_id' => $company3->company_id, 'name' => '60 dias'],
        ]);
        $company3->update(['default_term_id' => $default3->term_id]);
    }
}
