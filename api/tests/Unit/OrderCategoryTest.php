<?php

namespace Tests\Unit;
use App\Models\OrderCategory;

use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class OrderCategoryTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = OrderCategory::class;

    public function testModify()
    {
        /** @var OrderCategory $item */
        $item = $this->createModelInstance();
        $this->assertModifiesNumberProperty($item, 'order', fake()->randomDigitNot($item->order));
    }
}
