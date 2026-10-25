<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Tests\Feature\Http\ResourceFeaturesTestCase;

class CustomerControllerTest extends ResourceFeaturesTestCase
{
    protected $resourceRoute = 'customer';

    protected function createResourceModelItems($count = 1, $attributes = [], $resourceModel = null)
    {
        $attributes += ['customer_status'=>1];
        return parent::createResourceModelItems(
            $count,
            $attributes,
            $resourceModel
        );
    }

    public function test_store()
    {
        $item = $this->makeResourceModelItems();
        $item = $item->toArray();
        $item['sellers'] = Vendor::factory()->count(2)->create()->pluck('id');
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
        $data['sellers'] = Vendor::factory()->count(2)->create()->pluck('id'); //improve?

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
        }
    }
}
