<?php

namespace Tests\Unit;

use App\Models\OrderFlow;
use PHPUnit\Framework\TestCase;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class OrderFlowTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = OrderFlow::class;

    public function testModify()
    {
        /** @var OrderFlow $item */
        $item = $this->createModelInstance();

        $this->assertModifiesStringProperty($item, 'name');
    }
}
