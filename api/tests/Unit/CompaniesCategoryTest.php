<?php

namespace Tests\Unit;

use App\Models\CompaniesCategory;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class CompaniesCategoryTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = CompaniesCategory::class;

    public function testModify()
    {
        /** @var CompaniesCategory $item */
        $item = $this->createModelInstance();
        $this->assertModifiesNumberProperty($item,'category_status',!$item->category_status);
        $this->assertModifiesStringProperty($item,'category_name');
    }
}
