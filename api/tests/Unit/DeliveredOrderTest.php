<?php

namespace Tests\Unit;

use App\Models\DeliveredOrder;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class DeliveredOrderTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = DeliveredOrder::class;

    public function testModify()
    {
        /** @var DeliveredOrder $item */
        $item = $this->createModelInstance();
        $this->assertModifiesNumberProperty($item, 'delivered_quantity', fake()->randomFloat(config('app.money_decimal_precision')));
        $this->assertModifiesNumberProperty($item, 'delivered_pack', fake()->randomDigitNot($item->delivered_pack));
        $this->assertModifiesDateProperty($item, 'delivered_date', fake()->dateTime()->format('Y-m-d H:i:s'));
    }
}
