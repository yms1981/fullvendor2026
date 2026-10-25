<?php

namespace Tests\Unit;

use App\Models\Gpslog;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class GpslogTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = Gpslog::class;

    public function testModify()
    {
        /** @var Gpslog $item */
        $item = $this->createModelInstance();
        $this->assertModifiesNumberProperty($item, 'login', fake()->randomDigitNot($item->login));
        $this->assertModifiesStringProperty($item, 'infotext');
    }
}
