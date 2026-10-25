<?php

namespace Tests\Feature\Http\Controllers;

use Tests\Feature\Http\ResourceFeaturesTestCase;

class InventoryControllerTest extends ResourceFeaturesTestCase
{
    protected $resourceRoute = 'inventory';

    protected function getIdCondArray($item)
    {
        return [['company_id', $item->company_id], ['product_id', $item->product_id]];
    }

    public function getModelIdForUrl($item)
    {
        return "company/{$item->company_id}/product/{$item->product_id}";
    }
}
