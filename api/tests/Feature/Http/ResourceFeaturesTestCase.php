<?php

namespace Tests\Feature\Http;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Testing\Fluent\AssertableJson;

class ResourceFeaturesTestCase extends FeatureTestCase
{

    protected function getResourceModelFactory($count = 1, $resourceModel = null)
    {
        if (!isset($resourceModel)) {
            $resourceModel = Controller::getModelForRoute($this->resourceRoute);
        }

        $factory = $resourceModel::factory();
        if ($count > 1) {
            $factory = $factory->count($count);
        }
        return $factory;
    }

    protected function createResourceModelItems($count = 1, $attributes = [], $resourceModel = null)
    {
        return $this->getResourceModelFactory($count, $resourceModel)->create($attributes);
    }

    protected function makeResourceModelItems($count = 1, $attributes = [], $resourceModel = null)
    {
        return $this->getResourceModelFactory($count, $resourceModel)->make($attributes);
    }

    /**
     * @param Model $item
     * @return mixed
     */
    public function getModelIdForUrl($item)
    {
        if (method_exists($item, 'getSerializedId')) {
            return $item->getSerializedId();
        }

        $primaryKeyName = $item->getKeyName();
        return $item->$primaryKeyName;
    }

    /**
     * Defines the condition for the queryBuilder to find a model of self::$model class as an array of conditions
     * @param Model $item
     * @return array[]
     */
    protected function getIdCondArray($item)
    {
        $key = $item->getKeyName();
        $idCondArr = [];
        if (is_string($key)) {
            return [[$key, $item->$key]];
        }

        foreach ($key as $partialKey) {
            $idCondArr[] = [$partialKey, $item->$partialKey];
        }
        return $idCondArr;
    }

    /**
     * @param Collection $multipleAddItems
     * @return array
     */
    protected function prepareDataForMultipleAdd($multipleAddItems)
    {
        return ["items" => $multipleAddItems->toArray()];
    }

    /**
     * @param $uri
     * @param $data
     * @param int $expectedFailedStatus
     * @return void
     */
    protected function assertStoreFails($uri, $data, $expectedFailedStatus = -2)
    {
        $response = $this->post(
            $uri,
            $data
        );

        $response->assertStatus(200);
        $content = json_decode($response->getContent());
        self::assertEquals($expectedFailedStatus, $content->status);
    }


    protected function cleanUpModelTable()
    {
        Controller::getModelForRoute($this->resourceRoute)::query()->delete();
    }

    /**
     * @param string|\Eloquent $relatedModel
     * @param string|JsonResource $relatedResourceClass
     * @param string $relatedPath
     * @param string $relation
     * @return void
     */
    protected function assertGetRelatedOne($relatedModel, $relatedResourceClass, $relatedPath, $relation = null): void
    {
        /** @var \Eloquent $item */
        $item = $this->createResourceModelItems();
        if (!isset($relation)) {
            $relation = $relatedPath;
        }
        $relatedItems = $this->makeRelated($relatedModel, 1);
        $relatedAttributes = array_keys((new $relatedResourceClass($relatedItems->first()))->toArray(request()));
        $item->$relation()->save($relatedItems->first());
        $this->getJson(
            $this->getResourceUri() . '/' . $this->getModelIdForUrl($item) . '/' . $relatedPath
        )
            ->assertOk()
            ->assertJson(fn(AssertableJson $json) => $json->hasAll(
                $relatedAttributes
            )->etc()
            );
    }

    /**
     * @param string|\Eloquent $relatedModel
     * @param string|JsonResource $relatedResourceClass
     * @param string $relatedPath
     * @param array|\Closure $pivotValues
     * @return void
     */
    protected function assertAddRelated(
        string $relatedModel,
        string $relatedResourceClass,
        string $relatedPath,
        string $relation = null,
               $pivotValues = []
    ): void
    {
        if (!isset($relation)) {
            $relation = $relatedPath;
        }
        $relatedAmount = 5;

        $data = [];
        if (is_callable($pivotValues)) {
            $data = $pivotValues(0)[0];
        }

        $pivotValues = is_callable($pivotValues) ? $pivotValues($relatedAmount) : $pivotValues;

        $item = $this->createResourceModelItems();
        $relatedItems = $this->makeRelated($relatedModel, $relatedAmount);

        $newRelated = $relatedModel::factory()->create();
        $newRelatedResource = new $relatedResourceClass($newRelated);
        $newRelatedArr = $newRelatedResource->toArray(request());
        $relatedAttributes = array_keys($newRelatedArr);
        $item->$relation()->saveMany($relatedItems, $pivotValues);

        $keyName = $relatedModel::getModel()->getKeyName();

        $this->postJson(
            $this->getResourceUri() . '/' . $this->getModelIdForUrl($item) . "/{$relatedPath}/{$newRelated->$keyName}",
            $data
        )
            ->assertOk()
            ->assertJson(fn(AssertableJson $json) => $json->has(
                'data',
                $relatedAmount + 1,
                fn(AssertableJson $rel) => $rel->hasAll(
                    $relatedAttributes
                )->etc()
            )
            )->assertJsonFragment($newRelatedArr);
    }

    /**
     * @param string|Model $relatedModel
     * @param string $relatedResource
     * @param string $relatedPath
     * @param string|null $relation
     * @param array|\Closure $pivotValues
     * @return void
     */
    protected function assertRemoveRelated(
        string $relatedModel,
        string $relatedResource,
        string $relatedPath,
        string $relation = null,
               $pivotValues = []
    ): void
    {
        if (!isset($relation)) {
            $relation = $relatedPath;
        }
        $item = $this->createResourceModelItems();
        $relatedAmount = 5;
        $pivotValues = is_callable($pivotValues) ? $pivotValues($relatedAmount) : $pivotValues;
        $related = $this->makeRelated($relatedModel, $relatedAmount);
        $item->$relation()->saveMany($related, $pivotValues);

        $toRemove = $item->$relation->random();
        $toRemoveResource = new $relatedResource($toRemove);
        $toRemoveArray = $toRemoveResource->toArray(request());
        $relatedAttributes = array_keys($toRemoveArray);
        $keyName = $relatedModel::getModel()->getKeyName();

        $this->deleteJson(
            $this->getResourceUri() . '/' . $this->getModelIdForUrl($item) . "/{$relatedPath}/{$toRemove->$keyName}"
        )
            ->assertOk()
            ->assertJson(fn(AssertableJson $json) => $json->has(
                'data',
                $relatedAmount - 1,
                fn(AssertableJson $rel) => $rel->hasAll(
                    $relatedAttributes
                )->etc()
            )
            )->assertJsonMissing([$keyName => $toRemoveArray[$keyName]]);
    }

    public function test_index()
    {
        $this->createResourceModelItems(5);

        $index = $this->getJson($this->getResourceUri());

        $index->assertOk();
//        ->assertJson(fn(AssertableJson $josn) => $josn->has('data',
//            fn(AssertableJson $data) => $data->has()
//        ));

        $list = json_decode($index->getContent(), true)['data'];

        self::assertIsArray($list);
        self::assertGreaterThanOrEqual(5, count($list));

        foreach ($list as $listItem) {
            $resourceModel = Controller::getModelForRoute($this->resourceRoute);
            $lt = new $resourceModel();
            $lt->forceFill($listItem);
            foreach ($lt->toArray() as $key => $value) {
                self::assertArrayHasKey($key, $listItem);
                self::assertEquals($value, $listItem[$key]);
            }
        }
    }

    public function test_store()
    {
        $item = $this->makeResourceModelItems();
        $this->assertStoreSuccess($this->getResourceUri(), $item->toArray());
    }

    public function test_show()
    {
        /** @var Model $item */
        $item = $this->createResourceModelItems();
        $resourceClass = Controller::getResourceForRoute($this->resourceRoute);
        $resource = new $resourceClass($item);

        $response = $this->getJson($this->getResourceUri() . '/' . $this->getModelIdForUrl($item));
        $response
            ->assertOk()
            ->assertJson(
                fn(AssertableJson $data) => $data->hasAll(
                    array_keys($resource->toArray(request()))
                )->etc()
            );
    }

    public function test_update()
    {
        /** @var Model $item */
        $item = $this->createResourceModelItems();
        $fillable = $item->getFillable();
        $casts = $item->getCasts();
        $itemToModify = Controller::getModelForRoute($this->resourceRoute)::factory()->make();
        foreach ($fillable as $property) {
            $this->assertUpdate($property, $itemToModify->$property);
        }
    }

    public function test_destroy()
    {
        /** @var Model $item */
        $item = $this->createResourceModelItems();
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

    /**
     * @param \Eloquent|string $relatedModel
     * @param $amount
     * @return mixed
     */
    protected function makeRelated(\Eloquent|string $relatedModel, $amount = 5)
    {
        return $relatedModel::factory()->count($amount)->make();
    }


}
