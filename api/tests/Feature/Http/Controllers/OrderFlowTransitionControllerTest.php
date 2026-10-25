<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\Feature\Http\ResourceFeaturesTestCase;
use Tests\TestCase;

class OrderFlowTransitionControllerTest extends ResourceFeaturesTestCase
{
    protected $resourceRoute = 'orderflowtransitions';
    public function test_update()
    {
        parent::test_update();
    }
}
