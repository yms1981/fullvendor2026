<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\Customer;
use App\Models\TermsOfSale;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class TermsOfSaleTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = TermsOfSale::class;

    public function testRelationships()
    {
        /** @var TermsOfSale $item */
        $item = $this->createModelInstance();
        $item->customers()->saveMany(Customer::factory()->count(5)->make());
        $this->assertHasMany($item->customers(), Customer::class );

        $this->assertBelongsTo($item->company(), Company::class);
    }

    public function testModify()
    {
        /** @var TermsOfSale $item */
        $item = $this->createModelInstance();
        $this->assertModifiesStringProperty($item, 'name');
        $this->assertModifiesNumberProperty($item, 'term_status', !$item->term_status);
    }
}
