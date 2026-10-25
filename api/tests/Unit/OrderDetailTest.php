<?php

namespace Tests\Unit;

use App\Models\OrderDetail;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class OrderDetailTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = OrderDetail::class;

    public function testModify()
    {
        /** @var OrderDetail $item */
        $item = $this->createModelInstance();
        $this->assertModifiesStringProperty($item, 'groupcustomer');
        $this->assertModifiesStringProperty($item, 'tipolista');
        $this->assertModifiesStringProperty($item, 'comment');

        $this->assertModifiesNumberProperty($item, 'perc_price', fake()->randomFloat(config('app.money_decimal_precision')));
        $this->assertModifiesNumberProperty($item, 'salesp', fake()->randomFloat(config('app.money_decimal_precision')));
        $this->assertModifiesNumberProperty($item, 'impprice', fake()->randomFloat(config('app.money_decimal_precision')));
        $this->assertModifiesNumberProperty($item, 'totalprice', fake()->randomFloat(config('app.money_decimal_precision')));
        $this->assertModifiesNumberProperty($item, 'import_price', fake()->randomFloat(config('app.money_decimal_precision')));
        $this->assertModifiesNumberProperty($item, 'pack', fake()->randomFloat(config('app.money_decimal_precision')));
        $this->assertModifiesNumberProperty($item, 'delivered_pack', fake()->randomFloat(config('app.money_decimal_precision')));
        $this->assertModifiesNumberProperty($item, 'total', fake()->randomFloat(config('app.money_decimal_precision')));
        $this->assertModifiesNumberProperty($item, 'discountt', fake()->randomFloat(config('app.money_decimal_precision')));
        $this->assertModifiesNumberProperty($item, 'amount', fake()->randomFloat(config('app.money_decimal_precision')));
        $this->assertModifiesNumberProperty($item, 'total_delivered', fake()->randomFloat(config('app.money_decimal_precision')));
        $this->assertModifiesNumberProperty($item, 'discount_delivered', fake()->randomFloat(config('app.money_decimal_precision')));
        $this->assertModifiesNumberProperty($item, 'amount_delivered', fake()->randomFloat(config('app.money_decimal_precision')));
        $this->assertModifiesNumberProperty($item, 'total_amount', fake()->randomFloat(config('app.money_decimal_precision')));
        $this->assertModifiesNumberProperty($item, 'discount_amount', fake()->randomFloat(config('app.money_decimal_precision')));
        $this->assertModifiesNumberProperty($item, 'amount_sales', fake()->randomFloat(config('app.money_decimal_precision')));
        $this->assertModifiesNumberProperty($item, 'purchase_price', fake()->randomFloat(config('app.money_decimal_precision')));
        $this->assertModifiesNumberProperty($item, 'sale_price_app', fake()->randomFloat(config('app.money_decimal_precision')));
        $this->assertModifiesNumberProperty($item, 'fob_price', fake()->randomFloat(config('app.money_decimal_precision')));
        $this->assertModifiesNumberProperty($item, 'sale_price', fake()->randomFloat(config('app.money_decimal_precision')));
        $this->assertModifiesNumberProperty($item, 'discount', fake()->randomFloat(config('app.money_decimal_precision')));
        $this->assertModifiesNumberProperty($item, 'qty', fake()->randomFloat(config('app.money_decimal_precision')));
        $this->assertModifiesNumberProperty($item, 'delivered_quantity', fake()->randomFloat(config('app.money_decimal_precision')));

        $this->assertModifiesDateProperty($item, 'fixed_pricedate');

    }
}
