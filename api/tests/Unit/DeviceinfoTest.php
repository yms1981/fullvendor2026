<?php

namespace Tests\Unit;

use App\Models\Deviceinfo;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class DeviceinfoTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = Deviceinfo::class;

    public function testModify()
    {
        /** @var Deviceinfo $item */
        $item = $this->createModelInstance();
        $this->assertModifiesStringProperty($item, 'so');
        $this->assertModifiesStringProperty($item, 'version');
        $this->assertModifiesStringProperty($item, 'versionname');
    }
}
