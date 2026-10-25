<?php

namespace Tests\Unit;

use App\Models\AppUpdate;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class AppUpdateTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = AppUpdate::class;

    public function testModify()
    {
        /** @var AppUpdate $item */
        $item = $this->createModelInstance();
        $this->assertModifiesDateProperty($item, 'fecha');
        $this->assertModifiesNumberProperty($item, 'updatenumber',fake()->randomDigitNot($item->updatenumber));

    }
}
