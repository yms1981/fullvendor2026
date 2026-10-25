<?php

namespace Database\Seeders;

use App\Enums\AddressCustomerTypes;
use App\Models\Address;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\TermsOfSale;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $company1 = Company::where('name', 'Compania 1')->first();
        $company2 = Company::where('name', 'Compania 2')->first();

        $customerGroups = [
            $company1->company_id => CustomerGroup::where('company_id', $company1->company_id)->pluck('group_id', 'name')->toArray(),
            $company2->company_id => CustomerGroup::where('company_id', $company2->company_id)->pluck('group_id', 'name')->toArray(),
        ];

        $terms = [
            $company1->company_id => TermsOfSale::where('company_id', $company1->company_id)->pluck('term_id', 'name')->toArray(),
            $company2->company_id => TermsOfSale::where('company_id', $company2->company_id)->pluck('term_id', 'name')->toArray(),
        ];

        $sellers = [
            $company1->company_id => Vendor::where('company_id', $company1->company_id)->pluck('id')->toArray(),
            $company2->company_id => Vendor::where('company_id', $company2->company_id)->pluck('id')->toArray(),
        ];

        $customers = [
            // COMPANY 1
            [
                'company_id' => $company1->company_id,
                'name' => 'Customer1',
                'discount' => 10,
                'group' => 'Publico',
                'term' => 'Contado',
                'catalog_emails' => true,
                'seller_assign_catalog' => fn($s) => $s[2] ?? null,
                'sellers' => fn($s) => [$s[0] ?? null, $s[1] ?? null, $s[2] ?? null],
                'addresses' => [AddressCustomerTypes::Commercial, AddressCustomerTypes::Dispatch],
            ],
            [
                'company_id' => $company1->company_id,
                'name' => 'Customer3',
                'discount' => 20,
                'group' => 'Mayorista',
                'term' => 'Cuenta corriente',
                'catalog_emails' => true,
                'seller_assign_catalog' => fn($s) => $s[1] ?? null,
                'sellers' => fn($s) => [$s[3] ?? null],
                'addresses' => [],
            ],
            [
                'company_id' => $company1->company_id,
                'name' => 'Customer4',
                'discount' => 0,
                'group' => 'Distribuidor',
                'term' => '60 dias',
                'catalog_emails' => false,
                'seller_assign_catalog' => fn($s) => $s[0] ?? null, // vendedor1
                'sellers' => fn($s) => [$s[1] ?? null, $s[2] ?? null],
                'addresses' => [AddressCustomerTypes::Commercial],
            ],
            [
                'company_id' => $company1->company_id,
                'name' => 'Customer5',
                'discount' => 0,
                'group' => 'Exportacion',
                'term' => 'Cuenta corriente',
                'catalog_emails' => true,
                'seller_assign_catalog' => fn($s) => $s[2] ?? null,
                'sellers' => fn($s) => [$s[1] ?? null, $s[3] ?? null],
                'addresses' => [AddressCustomerTypes::Dispatch],
            ],
            [
                'company_id' => $company1->company_id,
                'name' => 'Customer6',
                'discount' => 0,
                'group' => 'Lista',
                'term' => '60 dias',
                'catalog_emails' => true,
                'seller_assign_catalog' => null,
                'sellers' => fn($s) => [$s[0] ?? null, $s[2] ?? null],
                'addresses' => [],
            ],
            [
                'company_id' => $company1->company_id,
                'name' => 'Customer7',
                'discount' => 0,
                'group' => 'Lista',
                'term' => '60 dias',
                'catalog_emails' => true,
                'seller_assign_catalog' => null,
                'sellers' => [],
                'addresses' => [],
            ],
            [
                'company_id' => $company1->company_id,
                'name' => 'Customer8',
                'discount' => 0,
                'group' => 'Lista',
                'term' => '60 dias',
                'catalog_emails' => true,
                'seller_assign_catalog' => null,
                'sellers' => [],
                'addresses' => [],
            ],
            [
                'company_id' => $company1->company_id,
                'name' => 'Customer9',
                'discount' => 0,
                'group' => 'Lista',
                'term' => '60 dias',
                'catalog_emails' => true,
                'seller_assign_catalog' => null,
                'sellers' => [],
                'addresses' => [],
            ],

            // COMPANY 2
            [
                'company_id' => $company2->company_id,
                'name' => 'Customer2',
                'discount' => 25,
                'group' => 'Lista',
                'term' => 'Contado',
                'catalog_emails' => true,
                'seller_assign_catalog' => fn($s) => $s[0] ?? null,
                'sellers' => fn($s) => [$s[0] ?? null, $s[1] ?? null],
                'addresses' => [AddressCustomerTypes::Commercial, AddressCustomerTypes::Dispatch],
            ],
            [
                'company_id' => $company2->company_id,
                'name' => 'Customer1',
                'discount' => 5,
                'group' => 'Mayorista',
                'term' => 'Cuenta corriente',
                'catalog_emails' => true,
                'seller_assign_catalog' => fn($s) => $s[2] ?? null,
                'sellers' => fn($s) => [$s[1] ?? null, $s[2] ?? null],
                'addresses' => [AddressCustomerTypes::Commercial],
            ],
            [
                'company_id' => $company2->company_id,
                'name' => 'Customer7',
                'discount' => 0,
                'group' => 'Distribuidor',
                'term' => '30 dias',
                'catalog_emails' => true,
                'seller_assign_catalog' => fn($s) => $s[3] ?? null,
                'sellers' => fn($s) => [$s[0] ?? null, $s[1] ?? null],
                'addresses' => [AddressCustomerTypes::Dispatch],
            ],
            [
                'company_id' => $company2->company_id,
                'name' => 'Customer8',
                'discount' => 0,
                'group' => 'Exportacion',
                'term' => '30 dias',
                'catalog_emails' => false,
                'seller_assign_catalog' => fn($s) => $s[4] ?? null,
                'sellers' => fn($s) => [$s[2] ?? null],
                'addresses' => [AddressCustomerTypes::Commercial, AddressCustomerTypes::Dispatch],
            ],
            [
                'company_id' => $company2->company_id,
                'name' => 'Customer9',
                'discount' => 0,
                'group' => 'Dealers',
                'term' => 'Contado',
                'catalog_emails' => true,
                'seller_assign_catalog' => null,
                'sellers' => fn($s) => [$s[3] ?? null, $s[5] ?? null],
                'addresses' => [],
            ],
        ];

        foreach ($customers as $data) {
            $companyId = $data['company_id'];

            $customer = Customer::create([
                'company_id' => $companyId,
                'name' => $data['name'],
                'business_name' => $data['name'],
                'discount' => $data['discount'],
                'group_id' => $customerGroups[$companyId][$data['group']],
                'term_id' => $terms[$companyId][$data['term']],
                'catalog_emails' => $data['catalog_emails'],
                'seller_assign_catalog' => is_callable($data['seller_assign_catalog'])
                    ? $data['seller_assign_catalog']($sellers[$companyId])
                    : $data['seller_assign_catalog'],
            ]);

            // Sellers
            $sellerIds = is_callable($data['sellers'])
                ? array_filter($data['sellers']($sellers[$companyId]))
                : [];
            $customer->sellers()->attach($sellerIds);

            // Addresses
            foreach ($data['addresses'] as $type) {
                $address = Address::factory()->create();
                $customer->addresses()->attach($address->id, ['type' => $type]);
            }
        }
    }
}
