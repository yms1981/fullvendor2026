<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\CompanyUserTypes;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\structure\StatusOrdersTableSeeder;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Http\ResourceFeaturesTestCase;

class OrderControllerTest extends ResourceFeaturesTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(StatusOrdersTableSeeder::class);

    }

    protected $resourceRoute = 'order';

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
        $item = $item->toArray();
        $item['seller_id'] = $customer->sellers->first()->id ?? $company->defaultSeller->id ?? Vendor::first()->id;

        $product = Product::where('stock', '>', 0)->first();

        $item['products'][] =
            [
                'pro_id' => $product->pro_id,
                'qty' => fake()->numberBetween(1, $product->stock),
                'price' => fake()->randomFloat(2, 0, 9999999),
                'fixed_price' => fake()->boolean(),
                'comment' => fake()->optional()->sentence,
            ];

        $this->assertStoreSuccess(
            $this->getResourceUri(),
            $item
        );
    }
}
