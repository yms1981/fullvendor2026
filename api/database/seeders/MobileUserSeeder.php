<?php

namespace Database\Seeders;

use App\Enums\CompanyUserTypes;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerUser;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;

class MobileUserSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('name', 'Compania 3')->first();

        if (!$company) {
            $this->command->error('No se encontró la compañía.');
            return;
        }

        $this->createInternalUsers($company);
        $this->createCustomerUsers($company);
    }


    public function createCustomerUsers(Company $company): void
    {
        $customers = Customer::where('company_id', $company->company_id)
            ->pluck('customer_id', 'business_name')
            ->toArray();

        $path = database_path('seeders/data/customerUsers.csv');

        if (!File::exists($path)) {
            $this->command->error("El archivo CSV no existe en: $path");
            return;
        }

        $rows = Excel::toCollection(null, $path)->first();
        $headers = $rows->shift();

        foreach ($rows as $row) {
            $record = collect($headers)->combine($row)->map(fn($v) => trim($v))->toArray();

            $customerName = $record['Usuario'] ?? null;
            $email = $record['Email'] ?? null;

            if (!$customerName || !$email) {
                $this->command->warn("⚠️ Fila incompleta, omitida (nombre o email vacío).");
                continue;
            }

            $customerId = $customers[$customerName] ?? null;

            if (!$customerId) {
                $this->command->warn("⚠️ Cliente '{$customerName}' no encontrado en la compañía, se omite.");
                continue;
            }

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $customerName,
                    'password' => Hash::make('123456'),
                ]
            );

            CustomerUser::firstOrCreate([
                'customer_id' => $customerId,
                'user_id' => $user->id,
            ]);

            $company->users()->syncWithoutDetaching([
                $user->id => ['type' => CompanyUserTypes::Customer->value],
            ]);
        }
    }

    public function createInternalUsers(Company $company): void
    {
        $internalUsers = [
            ['name' => 'bn', 'role' => CompanyUserTypes::Manager->value, 'email' => 'bn@fullvendor.com'],
            ['name' => 'korachick', 'role' => CompanyUserTypes::Seller->value, 'email' => 'yms@fullvendor.com'],
            ['name' => 'Yohandris', 'role' => CompanyUserTypes::Warehouse->value, 'email' => 'yfv@fullvendor.com'],
        ];

        foreach ($internalUsers as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('123456'),
                ]
            );

            $role = $data['role'];

            if ($role === CompanyUserTypes::Seller->value) {
                $seller = Vendor::where('company_id', $company->company_id)->first();

                if ($seller) {
                    $seller->update(['user_id' => $user->id]);
                } else {
                    $this->command->warn("⚠️ No se encontró perfil de vendedor para la compañía.");
                }
            }

            if ($role === CompanyUserTypes::Warehouse->value) {
                $warehouse = Warehouse::where('company_id', $company->company_id)->first();

                if ($warehouse) {
                    $warehouse->users()->syncWithoutDetaching([$user->id => ['company_id' => $company->company_id],]);
                } else {
                    $this->command->warn("⚠️ No se encontró warehouse para la compañía.");
                }
            }

            $company->users()->syncWithoutDetaching([$user->id => ['type' => $role]]);
        }
    }
}
