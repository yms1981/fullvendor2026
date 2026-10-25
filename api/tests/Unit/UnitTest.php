<?php

namespace Tests\Unit;

use App\Models\Unit;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class UnitTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = Unit::class;

    public function testModify()
    {
        /** @var Unit $item */
        $item = $this->createModelInstance();
        $this->assertModifiesStringProperty($item, 'formato');
        $this->assertModifiesStringProperty($item, 'description');
        $this->assertModifiesStringProperty($item, 'unit');

        $this->assertModifiesNumberProperty($item, 'status', fake()->randomDigitNot($item->status));
        $this->assertModifiesNumberProperty($item, 'decimales', fake()->randomDigitNot($item->decimales));
    }
}
