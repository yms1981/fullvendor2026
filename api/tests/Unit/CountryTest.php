<?php

namespace Tests\Unit;

use App\Models\Country;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class CountryTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = Country::class;

    public function testModify()
    {
        /** @var Country $item */
        $item = $this->createModelInstance();
        $this->assertModifiesStringProperty($item, 'sortname',fake()->countryCode());
        $this->assertModifiesStringProperty($item, 'name');
        $this->assertModifiesStringProperty($item, 'time_zone',fake()->timezone);

        $this->assertModifiesNumberProperty($item, 'phonecode',fake()->randomDigitNot($item->phonecode));
    }
}
