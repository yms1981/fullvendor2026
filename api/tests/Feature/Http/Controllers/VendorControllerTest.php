<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Models\SellerProfiles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\Feature\Http\ResourceFeaturesTestCase;

class VendorControllerTest extends ResourceFeaturesTestCase
{
    protected $resourceRoute = 'vendor';

    function test_customers()
    {
        $relatedAmount = 50;
        $per_page = 20;

        if (!isset($relation)) {
            $relation = 'customers';
        }
        /** @var \Eloquent $item */
        $item = $this->createResourceModelItems();
        $relatedItems = $this->makeRelated(Customer::class, $relatedAmount);
        $relatedAttributes = array_keys((new CustomerResource($relatedItems->first()))->toArray(request()));
        $relatedItems->each(function ($rel) use ($item) {
            $rel->company_id = $item->company_id;
            $rel->customer_status = true;
        });
        $item->$relation()->saveMany($relatedItems, []);
        $this->getJson(
            $this->getResourceUri() . '/' . $this->getModelIdForUrl($item) . '/customers',
            ['per_page' => $per_page]
        )
            ->assertOk()
            ->assertJson(fn(AssertableJson $json) => $json->has(
                'data',
                $per_page,
                fn(AssertableJson $rel) => $rel->hasAll(
                    $relatedAttributes
                )->etc()
            )
                ->where('per_page',$per_page)
                ->where('current_page',1)
                ->where('last_page',(int)ceil($relatedAmount / $per_page))
                ->where('from',1)
                ->where('to',$per_page)
                ->where('total',$relatedAmount)
                ->etc()
            );

    }

    public function test_store()
    {
        $item = $this->makeResourceModelItems()->toArray();

        $item['profile_id'] = SellerProfiles::factory()->create()->profile_id;
        $item['default'] = fake()->boolean();

        $this->assertStoreSuccess($this->getResourceUri(), $item);
    }

    public function assertUpdate($key, $value, $item = null)
    {
        if (!isset($item)) {
            $item = $this->createResourceModelItems();
        }
        $item->$key = $value;
        $resourceClass = Controller::getResourceForRoute($this->resourceRoute);
        /** @var JsonResource $resource */
        $resource = new $resourceClass($item);
        $data = $resource->toArray(request());

        $data['profile_id'] = SellerProfiles::factory()->create()->profile_id;
        $data['default'] = false;

        $response = $this->putJson(
            $this->getResourceUri() . '/' . $this->getModelIdForUrl($item),
            $data
        );

        $response
            ->assertOk();
        $isKeyHidden = Arr::first($item->getHidden(), fn($hidden) => $hidden === $key);
        if (!$isKeyHidden) {
            $response->assertJsonFragment([$key => $data[$key]]);
        }

        /** @var Model $updatedModel */
        $updatedModel = Controller::getModelForRoute($this->resourceRoute)::where($this->getIdCondArray($item))->sole();
        /** @var JsonResource $updatedResource */
        $updatedResource = new $resourceClass($updatedModel);
        $updatedData = $updatedResource->toArray(request());
        if (!$isKeyHidden) {
            $expected = is_array($data[$key]) ? json_encode($data[$key]) : $data[$key];
            $actual = is_array($updatedData[$key]) ? json_encode($updatedData[$key]) : $updatedData[$key];
            self::assertEquals("$key: $expected", "$key: $actual");
        } //aca tmb
    }
}
