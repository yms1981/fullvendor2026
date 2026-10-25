<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\Feature\Http\ResourceFeaturesTestCase;

class ProductControllerTest extends ResourceFeaturesTestCase
{
    protected $resourceRoute = 'product';

    public function test_store()
    {
        $item = $this->makeResourceModelItems();
        $this->getCategories($item);
        $this->assertStoreSuccess($this->getResourceUri(), $item->toArray());
    }

    private function getCategories($item)
    {
        $companyId = $item->company_id;

        $categories = Category::where('company_id', $companyId)->pluck('cat_id');

        if ($categories->isEmpty()) {
            $categories = collect([
                Category::factory()->create(['company_id' => $companyId])->cat_id,
            ]);
        }

        $categories->random(rand(1, $categories->count()))->all();

        $item->category_id = $categories;
    }
}
