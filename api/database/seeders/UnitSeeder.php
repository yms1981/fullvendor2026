<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Unit;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company1 = Company::where('name', 'Compania 1')->first();
        $company2 = Company::where('name', 'Compania 2')->first();
        $company3 = Company::where('name', 'Compania 3')->first();

        $default1 = Unit::create(['company_id' => $company1->company_id, 'unit' => 'Unidad', 'formato' => 'entero', 'description' => 'Unidad']);
        Unit::insert([
            ['company_id' => $company1->company_id, 'unit' => 'Cm', 'formato' => 'decimal', 'decimales' => 2, 'description' => 'Centimetros'],
            ['company_id' => $company1->company_id, 'unit' => 'Kgs', 'formato' => 'decimal', 'decimales' => 2, 'description' => 'Kilogramos']
        ]);
        $company1->update(['default_unit_id' => $default1->unit_id]);

        $default2 = Unit::create(['company_id' => $company2->company_id, 'unit' => 'Cm', 'formato' => 'decimal', 'decimales' => 2, 'description' => 'Centimetros']);;
        Unit::insert([
            ['company_id' => $company2->company_id, 'unit' => 'Unidad', 'formato' => 'entero', 'decimales' => null, 'description' => 'Unidad'],
            ['company_id' => $company2->company_id, 'unit' => 'Kgs', 'formato' => 'decimal', 'decimales' => 2, 'description' => 'Kilogramos']
        ]);
        $company2->update(['default_unit_id' => $default2->unit_id]);

        $default3 = Unit::create(['company_id' => $company3->company_id, 'unit' => 'Unit', 'description' => 'Unit']);
        Unit::insert([
            ['company_id' => $company3->company_id, 'unit' => 'Box', 'description' => 'Box'],
            ['company_id' => $company3->company_id, 'unit' => 'Set', 'description' => 'Set'],
            ['company_id' => $company3->company_id, 'unit' => 'Case', 'description' => 'Case'],
            ['company_id' => $company3->company_id, 'unit' => 'Pc(s)', 'description' => 'Pc(s)'],
        ]);
        $company2->update(['default_unit_id' => $default3->unit_id]);
    }
}
