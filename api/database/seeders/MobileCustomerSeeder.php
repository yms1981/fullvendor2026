<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\TermsOfSale;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Facades\Excel;

class MobileCustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = Company::where('name', 'Compania 3')->first();

        if (!$company) {
            $this->command->error('No se encontró la compañía.');
            return;
        }

        $customerGroups = CustomerGroup::where('company_id', $company->company_id)
            ->pluck('group_id', 'name')
            ->toArray();

        $terms = TermsOfSale::where('company_id', $company->company_id)
            ->pluck('term_id', 'name')
            ->toArray();

        $path = database_path('seeders/data/customers.csv');

        if (!File::exists($path)) {
            $this->command->error("El archivo CSV no existe en: $path");
            return;
        }

        $rows = Excel::toCollection(null, $path)->first();
        $headers = $rows->shift();

        foreach ($rows as $row) {
            $record = collect($headers)->combine($row)->map(fn($v) => trim($v))->toArray();

            $groupId = $customerGroups[$record['Grupo']] ?? null;
            $termId  = $terms[$record['Termino venta']] ?? null;

            $data = [
                'company_id'     => $company->company_id,
                'name'           => $record['nombre contacto'] ?? null,
                'business_name'  => $record['nombre compania'] ?? null,
                'email'          => $record['mail'] ?? null,
                'group_id'       => $groupId,
                'term_id'        => $termId,
                'customer_status'         => (bool) ($record['Estado'] ?? false),
            ];

            Customer::create($data);
        }
    }
}
