<?php

namespace Tests\Unit;

use App\Models\ChangesApp;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class ChangesAppTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = ChangesApp::class;

    public function testModify()
    {
        /** @var ChangesApp $item */
        $item = $this->createModelInstance();
        $this->assertModifiesNumberProperty($item,'read',!$item->read);

        $this->assertModifiesStringProperty($item,'email');
    }
}
