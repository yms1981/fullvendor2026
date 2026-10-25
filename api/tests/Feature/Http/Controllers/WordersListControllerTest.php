<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\Feature\Http\ResourceFeaturesTestCase;

class WordersListControllerTest extends ResourceFeaturesTestCase
{
    protected $resourceRoute = 'worderslist';
}
