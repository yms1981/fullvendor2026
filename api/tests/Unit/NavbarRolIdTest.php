<?php

namespace Tests\Unit;

use App\Models\NavbarRolId;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class NavbarRolIdTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = NavbarRolId::class;

    public function testModify()
    {
        /** @var NavbarRolId $item */
        $item = $this->createModelInstance();

        $this->assertModifiesNumberProperty($item, 'fhater_id', fake()->randomDigitNot($item->fhater_id));
    }
}
