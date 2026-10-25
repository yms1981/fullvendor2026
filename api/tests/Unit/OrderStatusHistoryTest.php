<?php

namespace Tests\Unit;
use App\Models\OrderStatusHistory;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class OrderStatusHistoryTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = OrderStatusHistory::class;

    public function testModify()
    {
        /** @var OrderStatusHistory $item */
        $item = $this->createModelInstance();
        $this->assertModifiesNumberProperty($item,'cod_status',fake()->randomNumber());
    }
}
