<?php

namespace Tests\Unit;

use App\Models\Cm;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class CmTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = Cm::class;

    public function testModify()
    {
        /** @var Cm $item */
        $item = $this->createModelInstance();
        $this->assertModifiesStringProperty($item, 'pageTitle');
        $this->assertModifiesStringProperty($item, 'pageText');
        $this->assertModifiesStringProperty($item, 'link');
        $this->assertModifiesStringProperty($item, 'content');
    }
}
