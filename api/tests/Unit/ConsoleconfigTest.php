<?php

namespace Tests\Unit;

use App\Models\Consoleconfig;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class ConsoleconfigTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = Consoleconfig::class;

    public function testModify()
    {
        /** @var Consoleconfig $item */
        $item = $this->createModelInstance();

        $this->assertModifiesStringProperty($item, 'username');
        $this->assertModifiesStringProperty($item, 'password');
        $this->assertModifiesStringProperty($item, 'email');
        $this->assertModifiesStringProperty($item, 'address');
        $this->assertModifiesStringProperty($item, 'telephone');
        $this->assertModifiesStringProperty($item, 'app');
        $this->assertModifiesStringProperty($item, 'rol');
        $this->assertModifiesStringProperty($item, 'token');
    }
}
