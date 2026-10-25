<?php

namespace Tests\Unit;

use App\Models\Navbar;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class NavbarTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = Navbar::class;

    public function testModify()
    {
        /** @var Navbar $item */
        $item = $this->createModelInstance();
        $this->assertModifiesStringProperty($item, 'url_nav');
        $this->assertModifiesStringProperty($item, 'identify');
        $this->assertModifiesStringProperty($item, 'name_eng');
        $this->assertModifiesStringProperty($item, 'name_spa');
        $this->assertModifiesStringProperty($item, 'icon');

        $this->assertModifiesNumberProperty($item, 'father_id', fake()->randomDigitNot($item->father_id));
        $this->assertModifiesNumberProperty($item, 'order', fake()->randomDigitNot($item->order));
        $this->assertModifiesNumberProperty($item, 'config', fake()->randomDigitNot($item->config));
        $this->assertModifiesNumberProperty($item, 'is_active_to_rol', fake()->randomDigitNot($item->is_active_to_rol));
    }
}
