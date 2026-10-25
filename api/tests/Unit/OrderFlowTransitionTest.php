<?php

namespace Tests\Unit;

use App\Models\OrderFlowTransition;
use PHPUnit\Framework\TestCase;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class OrderFlowTransitionTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = OrderFlowTransition::class;

    public function testModify()
    {
        /** @var OrderFlowTransition $item */
        $item = $this->createModelInstance();
        $this->assertModifiesStringProperty($item, 'name');
    }
}
