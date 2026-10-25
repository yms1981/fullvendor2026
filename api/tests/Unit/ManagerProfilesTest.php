<?php

namespace Tests\Unit;
use App\Models\ManagerProfiles;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class ManagerProfilesTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = ManagerProfiles::class;

    public function testModify()
    {
        /** @var ManagerProfiles $item */
        $item = $this->createModelInstance();
        $this->assertModifiesStringProperty($item, 'name');

        $this->assertModifiesNumberProperty($item, 'is_active',!$item->is_active);
    }
}
