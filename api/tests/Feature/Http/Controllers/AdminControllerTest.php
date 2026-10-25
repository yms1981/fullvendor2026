<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Support\Facades\Schema;
use Tests\Feature\Http\ResourceFeaturesTestCase;

class AdminControllerTest extends ResourceFeaturesTestCase
{
    protected $resourceRoute = 'admin';

    protected function createResourceModelItems($count = 1, $attributes = [], $resourceModel = null)
    {
        $attributes = [...$attributes, 'status'=>1,'deleted_at'=>null];
        return parent::createResourceModelItems(
            $count,
            $attributes,
            $resourceModel
        );
    }

}
