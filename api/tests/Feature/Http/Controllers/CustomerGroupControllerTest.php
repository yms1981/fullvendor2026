<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\Feature\Http\ResourceFeaturesTestCase;

class CustomerGroupControllerTest extends ResourceFeaturesTestCase
{
    protected $resourceRoute = 'customergroup';

    public function test_update()
    {
        /** @var Model $item */
        $item = $this->createResourceModelItems();
        $fillable = $item->getFillable();

        $excluded = ['group_status'];
        $fillableToTest = array_diff($fillable, $excluded);

        $itemToModify = Controller::getModelForRoute($this->resourceRoute)::factory()->make();
        foreach ($fillableToTest as $property) {
            $this->assertUpdate($property, $itemToModify->$property);
        }
    }
}
