<?php

namespace Tests\Unit;

use App\Models\StatusCart;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class StatusCartTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = StatusCart::class;

    public function testModify()
    {
        /** @var StatusCart $item */
        $item = $this->createModelInstance();
        $this->assertModifiesStringProperty($item, 'name_spa');
        $this->assertModifiesStringProperty($item, 'name_eng');
        $this->assertModifiesStringProperty($item, 'color');
        $this->assertModifiesStringProperty($item, 'cod');
        $this->assertModifiesStringProperty($item, 'class');
        $this->assertModifiesStringProperty($item, 'selected_default');

        $this->assertModifiesNumberProperty($item, 'allow_toremove', !$item->allow_toremove);
        $this->assertModifiesNumberProperty($item, 'multiple_cart', !$item->multiple_cart);
        $this->assertModifiesNumberProperty($item, 'nosubmit_order', !$item->nosubmit_order);
    }
}
