<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\TermsOfSale;
use App\Models\Vendor;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;
use Tests\ModelWithRelations;

class CustomerTest extends ModelBaseTestCase implements ModelWithModificableProperties, ModelWithRelations
{
    protected $model = Customer::class;

    public function testRelationships()
    {
        /** @var Customer $item */
        $item = $this->createModelInstance();
        $item->sellers()->saveMany(Vendor::factory()->count(5)->make());
        $this->assertBelongsToMany($item->sellers(), Vendor::class );

        $this->assertBelongsTo($item->terms_of_sale(), TermsOfSale::class);
    }


    public function testModify()
    {
        /** @var Customer $item */
        $item = $this->createModelInstance();
        $this->assertModifiesStringProperty($item, 'name');
        $this->assertModifiesStringProperty($item, 'business_name');
        $this->assertModifiesStringProperty($item, 'tax_id');
        $this->assertModifiesStringProperty($item, 'email');
        $this->assertModifiesStringProperty($item, 'phone');
        $this->assertModifiesStringProperty($item, 'cell_phone');
        $this->assertModifiesStringProperty($item, 'notes');
        $this->assertModifiesStringProperty($item, 'dispatch_delivery_address');
        $this->assertModifiesStringProperty($item, 'dispatch_shipping_notes');
        $this->assertModifiesStringProperty($item, 'cust_id_kor');
        $this->assertModifiesStringProperty($item, 'password');

        $this->assertModifiesNumberProperty($item, 'catalog_emails', fake()->randomDigitNot($item->catalog_emails));
        $this->assertModifiesNumberProperty($item, 'customer_status', fake()->randomDigitNot($item->customer_status));
        $this->assertModifiesNumberProperty($item, 'id_kor', fake()->randomDigitNot($item->id_kor));
        $this->assertModifiesNumberProperty($item, 'seller_assign_catalog', Vendor::factory()->create()->id);
        $this->assertModifiesNumberProperty($item, 'discount', fake()->randomDigitNot($item->discount));
        $this->assertModifiesNumberProperty($item, 'balance', fake()->randomFloat(config('app.money_decimal_precision')));
        $this->assertModifiesNumberProperty($item, 'sales', fake()->randomDigitNot($item->sales));
    }
}
