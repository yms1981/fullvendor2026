<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\TermsOfSale;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Tests\Feature\Http\ResourceFeaturesTestCase;

class TermsOfSaleControllerTest extends ResourceFeaturesTestCase
{
    protected $resourceRoute = 'termsofsale';

    public function test_store()
    {
        $item = $this->makeResourceModelItems();
        $item = $item->toArray();
        $item['default'] = false;
        $this->assertStoreSuccess($this->getResourceUri(), $item);
    }

    public function test_update()
    {
        /** @var TermsOfSale $item */
        $item = $this->createResourceModelItems();
        $fillable = $item->getFillable();
        $fillable = array_values(array_diff($fillable, ["term_status"])); //State changes are made from "enable" and "disable"
        $itemToModify = Controller::getModelForRoute($this->resourceRoute)::factory()->make();
        foreach ($fillable as $property) {
            $this->assertUpdate($property, $itemToModify->$property);
        }
    }

    public function test_destroy()
    {
        /** @var TermsOfSale $item */
        $item = $this->createResourceModelItems();
        $resourceClass = Controller::getResourceForRoute($this->resourceRoute);
        /** @var JsonResource $resource */
        $resource = new $resourceClass($item);
        $data = $resource->toArray(request());
        $data['company'] = $item->company_id;
        $response = $this->deleteJson(
            $this->getResourceUri() . '/' . $this->getModelIdForUrl($item),
            $data
        );
        $response->assertNoContent();

        /** @var TermsOfSale $erased */
        $erased = Controller::getModelForRoute($this->resourceRoute)::query()->where($this->getIdCondArray($item))->count();
        self::assertEquals(0, $erased);
    }

    function test_enable()
    {
        $this->changeStatus('/enable',1);
    }

    function test_disable()
    {
        $this->changeStatus('/disable',0);
    }

    function test_default()
    {
        $item = $this->createResourceModelItems();
        $company = Company::factory(['default_term_id' => $item->term_id])->create();

        $response = $this->getJson(
            $this->getResourceUri() . '/company/' . $this->getModelIdForUrl($company)
        );

        $response->assertOk();
        $response->assertJsonFragment(['term_id' => $item->term_id]);
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

        /** @var TermsOfSale $updatedModel */
        $updatedModel = Controller::getModelForRoute($this->resourceRoute)::where($this->getIdCondArray($item))->sole();
        /** @var JsonResource $updatedResource */
        $updatedResource = new $resourceClass($updatedModel);
        $updatedData = $updatedResource->toArray(request());
        if (!$isKeyHidden) {
            self::assertEquals("$key: " . $data[$key], "$key: " . $updatedData[$key]);
        }
    }

    protected function changeStatus(string $endpoint,int $expectedState): void
    {
        $key = 'term_status';
        $item = $this->createResourceModelItems(1,[$key => !$expectedState]);

        $response = $this->putJson(
            $this->getResourceUri() . '/' . $this->getModelIdForUrl($item).$endpoint
        );
        $response
            ->assertOk();

        $response->assertJsonFragment([$key => $expectedState]);
    }
}
