<?php

namespace Tests\Unit;

use App\Models\DictionaryGp;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class DictionaryGpTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = DictionaryGp::class;

    public function testModify()
    {
        /** @var DictionaryGp $item */
        $item = $this->createModelInstance();
        $this->assertModifiesStringProperty($item, 'eng');
        $this->assertModifiesStringProperty($item, 'esp');
        $this->assertModifiesStringProperty($item, 'arg');
    }
}
