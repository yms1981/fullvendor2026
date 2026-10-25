<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\Vendor;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;
use Tests\ModelWithRelations;

class VendorTest extends ModelBaseTestCase implements ModelWithModificableProperties, ModelWithRelations
{
    protected $model = Vendor::class;

    public function testRelationships()
    {
        //customers
        /** @var Vendor $item */
        $item = $this->createModelInstance();
        $item->customers()->saveMany(Customer::factory()->count(3)->make());
        $this->assertBelongsToMany($item->customers(), Customer::class );

    }

    public function testModify()
    {
        /** @var Vendor $item */
        $item = $this->createModelInstance();
        $this->assertModifiesStringProperty($item, 'fax');
        $this->assertModifiesStringProperty($item, 'phone_number');
        $this->assertModifiesStringProperty($item, 'cell_number');

        $this->assertModifiesNumberProperty($item, 'status', !$item->status);
        $this->assertModifiesNumberProperty($item, 'all_customers', !$item->all_customers);
        $this->assertModifiesNumberProperty($item, 'proforma', !$item->proforma);
    }
}
