<?php

namespace Tests\Unit;

use App\Models\SellerProfiles;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class SellerProfilesTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = SellerProfiles::class;

    public function testModify()
    {
        /** @var SellerProfiles $item */
        $item = $this->createModelInstance();
        $this->assertModifiesStringProperty($item, 'order_disc_price');
        $this->assertModifiesStringProperty($item, 'order_net_disc_price');
        $this->assertModifiesStringProperty($item, 'profile_name');

        $this->assertModifiesNumberProperty($item, 'order_discount', !$item->order_discount);
        $this->assertModifiesNumberProperty($item, 'order_net_discount', !$item->order_net_discount);
        $this->assertModifiesNumberProperty($item, 'can_change_price', !$item->can_change_price);
        $this->assertModifiesNumberProperty($item, 'can_send_catalog', !$item->can_send_catalog);
        $this->assertModifiesNumberProperty($item, 'can_create_customer', !$item->can_create_customer);
        $this->assertModifiesNumberProperty($item, 'default', !$item->default);
        $this->assertModifiesNumberProperty($item, 'status', !$item->status);
    }
}
