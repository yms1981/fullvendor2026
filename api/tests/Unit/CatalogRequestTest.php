<?php

namespace Tests\Unit;

use App\Models\CatalogRequest;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class CatalogRequestTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = CatalogRequest::class;

    public function testModify()
    {
        /** @var CatalogRequest $item */
        $item = $this->createModelInstance();

        $this->assertModifiesNumberProperty($item,'status',$item->status+3);

        $this->assertModifiesStringProperty($item,'descriptions');



    }
}
