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
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        /** @var Company $company1 */
        $company1 = Company::where('name', 'Compania 1')->first();
        /** @var Company $company2 */
        $company2 = Company::where('name', 'Compania 2')->first();

        $vendors = [
            $company1->company_id => Vendor::where('company_id', $company1->company_id)->get(),
            $company2->company_id => Vendor::where('company_id', $company2->company_id)->get(),
        ];

        $customers = [
            $company1->company_id => Customer::where('company_id', $company1->company_id)->get(),
            $company2->company_id => Customer::where('company_id', $company2->company_id)->get(),
        ];

        $warehouses = [
            $company1->company_id => Warehouse::where('company_id', $company1->company_id)->get(),
            $company2->company_id => Warehouse::where('company_id', $company2->company_id)->get(),
        ];

        $roleCustomer  = CompanyUserTypes::Customer->value;
        $roleSeller    = CompanyUserTypes::Seller->value;
        $roleManager   = CompanyUserTypes::Manager->value;
        $roleWarehouse = CompanyUserTypes::Warehouse->value;

        // Definición de usuarios con roles
        $usersData = [
            $company1->company_id => [
                ['name' => 'war', 'roles' => [$roleWarehouse], 'warehouse' => 0],
                ['name' => 'cus', 'roles' => [$roleManager,$roleCustomer], 'customer' => 0],
                ['name' => 'war-cus3', 'roles' => [$roleWarehouse,$roleCustomer], 'warehouse' => 0, 'customer' => 1],
                ['name' => 'man', 'roles' => [$roleManager]],
                ['name' => 'man-war', 'roles' => [$roleManager,$roleWarehouse], 'warehouse' => 0],
                ['name' => 'man-cus4', 'roles' => [$roleManager,$roleCustomer], 'customer' => 2],
                ['name' => 'man-cus5-war', 'roles' => [$roleManager,$roleCustomer,$roleWarehouse], 'warehouse' => 0, 'customer' =>3],
                ['name' => 'sel1', 'roles' => [$roleSeller], 'vendor' => 0],
                ['name' => 'sel3', 'roles' => [$roleSeller], 'vendor' => 1],
                ['name' => 'sel4', 'roles' => [$roleSeller], 'vendor' => 2],
                ['name' => 'sel7', 'roles' => [$roleSeller], 'vendor' => 3],
                ['name' => 'sel-war', 'roles' => [$roleSeller,$roleWarehouse], 'vendor' => 4, 'warehouse' => 0],
                ['name' => 'sel-cus6', 'roles' => [$roleSeller,$roleCustomer], 'vendor' => 5, 'customer' => 4],
                ['name' => 'sel-cus7-war', 'roles' => [$roleSeller,$roleCustomer,$roleWarehouse], 'vendor' => 6, 'warehouse' => 0, 'customer' => 5],
                ['name' => 'sel-man', 'roles' => [$roleSeller,$roleManager], 'vendor' => 7],
                ['name' => 'sel-man-war', 'roles' => [$roleSeller,$roleManager,$roleWarehouse], 'vendor' => 8, 'warehouse' => 0],
                ['name' => 'sel-man-cus8', 'roles' => [$roleSeller,$roleManager,$roleCustomer], 'vendor' => 9, 'customer' => 6],
                ['name' => 'sel-man-cus9-war', 'roles' => [$roleSeller,$roleManager,$roleCustomer,$roleWarehouse], 'vendor' => 10, 'warehouse' => 0, 'customer' => 7],
            ],
            $company2->company_id => [
                ['name' => 'war', 'roles' => [$roleWarehouse], 'warehouse' => 0],
                ['name' => 'cus2', 'roles' => [$roleCustomer], 'customer' => 0],
                ['name' => 'man2', 'roles' => [$roleManager]],
                ['name' => 'sel2', 'roles' => [$roleSeller], 'vendor' => 0],
                ['name' => 'sel5', 'roles' => [$roleSeller], 'vendor' => 1],
                ['name' => 'sel6', 'roles' => [$roleSeller], 'vendor' => 2],
                ['name' => 'cus', 'roles' => [$roleCustomer], 'customer' => 1, 'email' => 'cus@compania1.com'],
                ['name' => 'man1', 'roles' => [$roleManager]],
                ['name' => 'sel3', 'roles' => [$roleSeller], 'vendor' => 3, 'email' => 'sel3@compania1.com'],
                ['name' => 'sel1', 'roles' => [$roleCustomer], 'customer' => 2, 'email' => 'sel1@compania1.com'],
            ]
        ];

        foreach ($usersData as $companyId => $users) {
            $company = $companyId === $company1->company_id ? $company1 : $company2;
            $companyDomain = str_replace(' ','',Str::lower($company->name)) .".com";
            foreach ($users as $data) {
                $email = $data['email'] ?? "{$data['name']}@$companyDomain";

                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => $data['name'],
                        'password' => bcrypt('123456'),
                    ]
                );

                // seller
                if (isset($data['vendor'])) {
                    $vendors[$companyId][$data['vendor']]->update(['user_id' => $user->id]);
                }

                // customer
                if (isset($data['customer'])) {
                    CustomerUser::create([
                        'customer_id' => $customers[$companyId][$data['customer']]->customer_id,
                        'user_id' => $user->id,
                    ]);
                }

                // warehouse
                if (isset($data['warehouse'])) {
                    $warehouses[$companyId][$data['warehouse']]->users()->attach($user->id, ['company_id' => $companyId]);
                }

                // company_user roles
                foreach ($data['roles'] as $role) {
                    $company->users()->attach($user->id, ['type' => $role]);
                }
            }
        }

    }
}
