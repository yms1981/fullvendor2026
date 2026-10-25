<?php

namespace Tests\Feature\Http;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\User;
use Database\Seeders\Test\DatabaseResourceFeatureSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Tests\RefreshDatabase;
use Tests\TestCase;

class FeatureTestCase extends TestCase
{
    use RefreshDatabase;
    protected $resourceRoute;
    protected $seeder = DatabaseResourceFeatureSeeder::class;
    protected $requestHeaders = [];
    protected $apiVersion = "v2";

    /**
     * @param $key
     * @param $value
     * @param Model $item
     * @return void
     */
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
        //$data[$key] = $value;

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

    /**
     * @param string|\Eloquent $relatedModel
     * @param string|JsonResource $relatedResourceClass
     * @param string $relatedPath
     * @param string $relation
     * @param \Closure|array $pivotValues
     * @return void
     */
    protected function assertListRelated(
        $relatedModel,
        $relatedResourceClass,
        $relatedPath,
        $relation = null,
        $pivotValues = []
    ): void
    {
        $relatedAmount = 5;
        $pivotValues = is_callable($pivotValues) ? $pivotValues($relatedAmount) : $pivotValues;
        if (!isset($relation)) {
            $relation = $relatedPath;
        }
        /** @var \Eloquent $item */
        $item = $this->createResourceModelItems();
        $relatedItems = $this->makeRelated($relatedModel, $relatedAmount);
        $relatedAttributes = array_keys((new $relatedResourceClass($relatedItems->first()))->toArray(request()));
        $item->$relation()->saveMany($relatedItems, $pivotValues);
        $this->getJson(
            $this->getResourceUri() . '/' . $this->getModelIdForUrl($item) . '/' . $relatedPath
        )
            ->assertOk()
            ->assertJson(fn(AssertableJson $json) => $json->has(
                'data',
                $relatedAmount,
                fn(AssertableJson $rel) => $rel->hasAll(
                    $relatedAttributes
                )->etc()
            )->etc()
            );
    }

    protected function setUp(): void
    {
        TestCase::setUp();
        if (Schema::hasColumn('admin', 'name')) {
            \Artisan::call(
                'migrate',
                [
                    '--path' => 'database/migrations/structure',
                    '--force' => true
                ]);
        }
        $user = User::query()->where('email', 'admin@fullvendor.com')->first();
        if (!isset($user))
            $user = User::factory()->create(['email' => 'admin@fullvendor.com', 'password' => 'password']);

        //allow the user to do everything because is admin
        Admin::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user, ["*"]);
    }

    /**
     * @param $uri
     * @param $data
     * @return void
     */
    protected function assertStoreSuccess($uri, $data)
    {
        $response = $this->postJson(
            $uri,
            $data
        );

        $response->assertCreated();
    }

    protected function getResourceUri($resource = null, $apiVersion = null)
    {
        if (!isset($resource)) {
            $resource = $this->resourceRoute;
        }
        if (!isset($apiVersion)) {
            $apiVersion = $this->apiVersion;
        }

        return "/api/{$apiVersion}/{$resource}";
    }
}
