<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\TermsOfSale;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class CompanyTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = Company::class;

    public function testRelationships()
    {
        /** @var Company $item */
        $term = TermsOfSale::factory()->create();
        $item = $this->createModelInstance();
        $item->defaultTermOfSale()->associate($term)->save();
        $item->terms_of_sales()->saveMany(TermsOfSale::factory()->count(5)->make());
        $this->assertHasMany($item->terms_of_sales(), TermsOfSale::class );

        $this->assertBelongsTo($item->defaultTermOfSale(), TermsOfSale::class);
    }

    public function testModify()
    {
        /** @var Company $item */
        $item = $this->createModelInstance();
        $this->assertModifiesStringProperty($item, 'name');
        $this->assertModifiesStringProperty($item, 'short_name');
        $this->assertModifiesStringProperty($item, 'email');
        $this->assertModifiesStringProperty($item, 'password');
        $this->assertModifiesStringProperty($item, 'phone_number');
        $this->assertModifiesStringProperty($item, 'cell_number');
        $this->assertModifiesStringProperty($item, 'fax');
        $this->assertModifiesStringProperty($item, 'image');
        $this->assertModifiesStringProperty($item, 'tax_condition');
        $this->assertModifiesStringProperty($item, 'tax_id');
        $this->assertModifiesStringProperty($item, 'website');
        $this->assertModifiesStringProperty($item, 'slug_url');
        $this->assertModifiesStringProperty($item, 'token');
        $this->assertModifiesStringProperty($item, 'sellers_ids');

        $this->assertModifiesNumberProperty($item, 'show_inventory',fake()->randomDigitNot($item->show_inventory));
        $this->assertModifiesNumberProperty($item, 'show_semaforo_stock', fake()->randomDigitNot($item->show_semaforo_stock));
        $this->assertModifiesNumberProperty($item, 'sell_with_inventory', fake()->randomDigitNot($item->sell_with_inventory));
        $this->assertModifiesNumberProperty($item, 'catalogsend_time', fake()->randomDigitNot($item->catalogsend_time));
        $this->assertModifiesNumberProperty($item, 'ordersend_time', fake()->randomDigitNot($item->ordersend_time));
        $this->assertModifiesNumberProperty($item, 'total_price', fake()->randomDigitNot($item->total_price));
        $this->assertModifiesNumberProperty($item, 'max_number_of_vendors', fake()->randomDigitNot($item->max_number_of_vendors));
        $this->assertModifiesNumberProperty($item, 'max_number_of_orders', fake()->randomDigitNot($item->max_number_of_orders));
        $this->assertModifiesNumberProperty($item, 'max_number_of_clients', fake()->randomDigitNot($item->max_number_of_clients));
    }
}
