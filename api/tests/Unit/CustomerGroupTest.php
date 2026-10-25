<?php

namespace Tests\Unit;

use App\Enums\CustomerGroupTypes;
use App\Models\CustomerGroup;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class CustomerGroupTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = CustomerGroup::class;

    public function testModify()
    {
        /** @var CustomerGroup $item */
        $item = $this->createModelInstance();
        $this->assertModifiesStringProperty($item, 'name');
        $this->assertModifyEnumProperty($item, 'percentage_on_price', CustomerGroupTypes::all());
        $this->assertModifiesNumberProperty($item, 'percent_price_amount',fake()->randomFloat(config('app.money_decimal_precision')));
        $this->assertModifiesNumberProperty($item, 'group_status', fake()->randomDigitNot($item->group_status));
    }
}
