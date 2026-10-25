<?php

namespace Tests\Unit;

use App\Models\Category;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class CategoryTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = Category::class;

    public function testModify()
    {
        /** @var Category $item */
        $item = $this->createModelInstance();
        $this->assertModifiesStringProperty($item, 'category_name');

        $this->assertModifiesNumberProperty($item, 'category_status',!$item->category_status);
        $this->assertModifiesNumberProperty($item, 'category_default',!$item->category_default);
    }
}
