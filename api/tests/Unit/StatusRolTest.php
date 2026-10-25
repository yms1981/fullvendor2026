<?php

namespace Tests\Unit;
use App\Models\StatusRol;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class StatusRolTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = StatusRol::class;

    public function testModify()
    {
        /** @var StatusRol $item */
        $item = $this->createModelInstance();
        $this->assertModifiesNumberProperty($item, 'id_status', fake()->randomDigitNot($item->id_status));
    }
}
