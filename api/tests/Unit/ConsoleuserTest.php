<?php

namespace Tests\Unit;

use App\Models\Consoleuser;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class ConsoleuserTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = Consoleuser::class;

    public function testModify()
    {
        /** @var Consoleuser $item */
        $item = $this->createModelInstance();
        $this->assertModifiesStringProperty($item, 'email');
        $this->assertModifiesStringProperty($item, 'password');
        $this->assertModifiesStringProperty($item, 'rol');
    }
}
