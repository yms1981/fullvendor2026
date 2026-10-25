<?php

namespace Tests\Feature;

use App\Http\Kernel;
use Illuminate\Database\Eloquent\Model;

trait ResourceSoftDeletes
{
    public function test_restore()
    {
        /** @var Model $item */
        $item = $this->createResourceModelItems();
        $itemId = $this->getIdCondArray($item);
        $item->deleteQuietly();

        self::assertNotNull(Kernel::getModelForRoute($this->resourceRoute)::onlyTrashed()->find($itemId)->sole());

        $response = $this->putJson(
            $this->getResourceUri() . '/' . $this->getModelIdForUrl($item) . '/restore'
        );
        $response->assertOk();
        self::assertNotNull(Kernel::getModelForRoute($this->resourceRoute)::find($itemId)->sole());
    }
}
