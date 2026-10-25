<?php

namespace Tests\Unit;

use App\Models\Gpstracker;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class GpstrackerTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = Gpstracker::class;

    public function testModify()
    {
        /** @var Gpstracker $item */
        $item = $this->createModelInstance();
        $this->assertModifiesStringProperty($item, 'address');
        $this->assertModifiesStringProperty($item, 'deviceinfo');
        $this->assertModifiesNumberProperty($item, 'login', fake()->randomDigitNot($item->login));
        $this->assertModifiesNumberProperty($item, 'latitude', fake()->randomDigitNot($item->latitude));
        $this->assertModifiesNumberProperty($item, 'longitude', fake()->randomDigitNot($item->longitude));
        $this->assertModifiesNumberProperty($item, 'company_id', fake()->randomDigitNot($item->company_id));
        $this->assertModifiesNumberProperty($item, 'user_id', fake()->randomDigitNot($item->user_id));
    }
}
