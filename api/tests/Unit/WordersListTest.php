<?php

namespace Tests\Unit;
use App\Models\WordersList;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class WordersListTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = WordersList::class;

    public function testModify()
    {
        /** @var WordersList $item */
        $item = $this->createModelInstance();
        //TODO: implment modifiable asserts
        $this->assertModifiesStringProperty($item, 'transaction_id');
        $this->assertModifiesStringProperty($item, 'approved_by');
        $this->assertModifiesStringProperty($item, 'token_pass');

        $this->assertModifiesNumberProperty($item, 'discount_type', fake()->randomDigitNot($item->discount_type));
        $this->assertModifiesNumberProperty($item, 'order_status', fake()->randomDigitNot($item->order_status));
        $this->assertModifiesNumberProperty($item, 'payment_method', fake()->randomDigitNot($item->payment_method));
        $this->assertModifiesNumberProperty($item, 'payment_status', fake()->randomDigitNot($item->payment_status));
        $this->assertModifiesNumberProperty($item, 'delivery_status', fake()->randomDigitNot($item->delivery_status));

        $this->assertModifiesNumberProperty($item, 'amount', fake()->randomFloat(config('app.money_decimal_precision')));
        $this->assertModifiesNumberProperty($item, 'discount_a', fake()->randomFloat(config('app.money_decimal_precision')));
        $this->assertModifiesNumberProperty($item, 'total_amount', fake()->randomFloat(config('app.money_decimal_precision')));
        $this->assertModifiesNumberProperty($item, 'amount_delivered', fake()->randomFloat(config('app.money_decimal_precision')));
        $this->assertModifiesNumberProperty($item, 'discount_delivered', fake()->randomFloat(config('app.money_decimal_precision')));
        $this->assertModifiesNumberProperty($item, 'total_delivered', fake()->randomFloat(config('app.money_decimal_precision')));
        $this->assertModifiesNumberProperty($item, 'discount', fake()->randomFloat(config('app.money_decimal_precision')));

        $this->assertModifiesDateProperty($item, 'date_confirmed');
        $this->assertModifiesDateProperty($item, 'send_email_order');
        $this->assertModifiesDateProperty($item, 'date_rejected_email');
        $this->assertModifiesDateProperty($item, 'date_expired_email');
    }
}
