<?php

namespace Tests\Unit;

use App\Models\CompanyContact;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class CompanyContactTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = CompanyContact::class;

    public function testModify()
    {
        /** @var CompanyContact $item */
        $item = $this->createModelInstance();
        $this->assertModifiesStringProperty($item,'name');
        $this->assertModifiesStringProperty($item,'email',fake()->safeEmail);
        $this->assertModifiesStringProperty($item,'website',fake()->url);
        $this->assertModifiesStringProperty($item,'phone',fake()->phoneNumber);
        $this->assertModifiesStringProperty($item,'cell_phone',fake()->phoneNumber);
    }
}
