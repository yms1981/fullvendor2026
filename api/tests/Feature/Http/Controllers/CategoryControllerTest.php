<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Tests\Feature\Http\ResourceFeaturesTestCase;

class CategoryControllerTest extends ResourceFeaturesTestCase
{
    protected $resourceRoute = 'category';

    public function test_disable_non_default_category_without_products(): void
    {
        $item = $this->createResourceModelItems(1, [
            'category_default' => 0,
            'category_status' => 1,
        ]);

        $response = $this->putJson(
            $this->getResourceUri() . '/' . $this->getModelIdForUrl($item) . '/disable'
        );

        $response->assertOk();
        $response->assertJsonFragment(['category_status' => 0]);
    }

    public function test_disable_rejects_default_category(): void
    {
        $item = $this->createResourceModelItems(1, [
            'category_default' => 1,
            'category_status' => 1,
        ]);

        $response = $this->putJson(
            $this->getResourceUri() . '/' . $this->getModelIdForUrl($item) . '/disable'
        );

        $response->assertStatus(400);
        $response->assertJsonFragment([
            'message' => 'Cannot change the status of the default category',
        ]);
    }

    public function test_disable_rejects_category_with_products(): void
    {
        $item = $this->createResourceModelItems(1, [
            'category_default' => 0,
            'category_status' => 1,
        ]);

        $product = Product::factory()->create([
            'company_id' => $item->company_id,
            'category_id' => $item->cat_id,
        ]);
        $item->products()->attach($product->pro_id);

        $response = $this->putJson(
            $this->getResourceUri() . '/' . $this->getModelIdForUrl($item) . '/disable'
        );

        $response->assertStatus(400);
        $response->assertJsonFragment([
            'message' => 'Cannot deactivate a category with associated products',
        ]);
    }

    public function test_update()
    {
        /** @var Model $item */
        $item = $this->createResourceModelItems();
        $fillable = $item->getFillable();

        $excluded = ['category_default','image'];
        $fillableToTest = array_diff($fillable, $excluded);

        $itemToModify = Controller::getModelForRoute($this->resourceRoute)::factory()->make();
        foreach ($fillableToTest as $property) {
            $this->assertUpdate($property, $itemToModify->$property);
        }
    }

    public function assertUpdate($key, $value, $item = null)
    {
        if (!isset($item)) {
            $item = $this->createResourceModelItems(1, ['category_default' => false]);
        }
        $item->$key = $value;
        $resourceClass = Controller::getResourceForRoute($this->resourceRoute);
        /** @var JsonResource $resource */
        $resource = new $resourceClass($item);
        $data = $resource->toArray(request());

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

    public function test_destroy()
    {
        /** @var Model $item */
        $item = $this->createResourceModelItems(1,['category_default' => false]);
        $resourceClass = Controller::getResourceForRoute($this->resourceRoute);
        /** @var JsonResource $resource */
        $resource = new $resourceClass($item);
        $data = $resource->toArray(request());
        $response = $this->deleteJson(
            $this->getResourceUri() . '/' . $this->getModelIdForUrl($item),
            $data
        );
        $response->assertNoContent();

        $erased = Controller::getModelForRoute($this->resourceRoute)::query()->where($this->getIdCondArray($item))->count();
        self::assertEquals(0, $erased);
    }
}
