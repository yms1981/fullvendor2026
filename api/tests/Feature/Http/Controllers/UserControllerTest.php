<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\CompanyUserTypes;
use App\Models\Company;
use App\Models\Customer;
use App\Models\SellerProfiles;
use App\Models\Vendor;
use App\Models\Warehouse;
use Tests\Feature\Http\ResourceFeaturesTestCase;

class UserControllerTest extends ResourceFeaturesTestCase
{
    protected $resourceRoute = 'user';

    public function test_store()
    {
        $item = $this->makeResourceModelItems()->toArray();

        $company = Company::factory()->create();
        $item['company_id'] = $company->company_id;

        $role = fake()->randomElement(CompanyUserTypes::all());
        $item['role'] = $role;

        $item = match ($role) {
            CompanyUserTypes::Customer->value => array_merge($item, [
                'related_id' => Customer::factory()->create()->customer_id,
            ]),
            CompanyUserTypes::Manager->value => $item,
            CompanyUserTypes::Warehouse->value => array_merge($item, [
                'related_id' => Warehouse::factory()->create()->id,
            ]),
            CompanyUserTypes::Seller->value => array_merge($item, [
                'seller' => array_merge(Vendor::factory()->make()->toArray(),
                    ['default' => fake()->boolean()]
                ),
                'profile_id' => SellerProfiles::factory()->create()->profile_id,
            ]),
            default => $item,
        };

        $this->assertStoreSuccess($this->getResourceUri(), $item);
    }
}
