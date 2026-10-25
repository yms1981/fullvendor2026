<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\CompanyUserTypes;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class CatalogCartControllerTest extends OrderControllerTest
{
    protected $resourceRoute = 'catalogcart';

    public function test_store()
    {
        $user = User::whereHas('companies')->first();

        if (!$user) {
            $company = Company::first();
            $user = User::factory()->create();
            $user->companies()->attach($company->company_id, [
                'type' => CompanyUserTypes::Manager
            ]);
        }

        Sanctum::actingAs($user);

        $company = $user->companies()
            ->wherePivot('type', 'manager')
            ->first();

        $customer = Customer::where('company_id', $company->company_id)->first() ?? Customer::factory()->create(['company_id' => $company->company_id,]);

        /** @var Order $item */
        $item = $this->makeResourceModelItems(1,['customer_id' => $customer->customer_id, 'company_id' => $customer->company_id]);
        $order = $item->toArray();
        $order['seller_id'] = $customer->sellers->first()->id ?? null;
        $this->assertStoreSuccess(
            $this->getResourceUri(),
            [
                'customer_id' => $item->customer_id,
                'order' => $order,
                'details' => $item->order_details()
            ]
        );
    }
}
