<?php

namespace Tests\Unit;

use App\Models\City;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class CityTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = City::class;

    public function testModify()
    {
        /** @var City $item */
        $item = $this->createModelInstance();
        $this->assertModifiesStringProperty($item, 'name');
    }
}
