<?php

namespace Tests\Unit;

use App\Models\CompanyStatusOrder;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class CompanyStatusOrderTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = CompanyStatusOrder::class;

    public function testModify()
    {
        /** @var CompanyStatusOrder $item */
        $item = $this->createModelInstance();
        $this->assertModifiesStringProperty($item,'name');
        $this->assertModifiesStringProperty($item,'email',fake()->safeEmail);
        $this->assertModifiesNumberProperty($item,'cod_status',!$item->cod_status);
    }
}
