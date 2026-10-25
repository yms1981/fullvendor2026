<?php

namespace Tests\Unit;
use App\Models\State;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class StateTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = State::class;

    public function testModify()
    {
        /** @var State $item */
        $item = $this->createModelInstance();
        $this->assertModifiesStringProperty($item,'name');
    }
}
