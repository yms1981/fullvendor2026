<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\Feature\Http\ResourceFeaturesTestCase;
use Tests\TestCase;

class OrderFlowControllerTest extends ResourceFeaturesTestCase
{
    protected $resourceRoute = 'orderflows';

    public function test_update()
    {
        parent::test_update();
    }
}
