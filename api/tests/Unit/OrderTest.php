<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\StatusOrder;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class OrderTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = Order::class;

    public function testModify()
    {
        /** @var Order $item */
        $item = $this->createModelInstance();

        $this->assertModifiesStringProperty($item, 'transaction_id');
        $this->assertModifiesStringProperty($item, 'approved_by');
        $this->assertModifiesStringProperty($item, 'token_pass');

        $this->assertModifiesNumberProperty($item, 'discount_type', fake()->randomDigitNot($item->discount_type));
        $this->assertModifiesNumberProperty($item, 'payment_method', fake()->randomDigitNot($item->payment_method));
        $this->assertModifiesNumberProperty($item, 'payment_status', fake()->randomDigitNot($item->payment_status));
        $this->assertModifiesNumberProperty($item, 'delivery_status', fake()->randomDigitNot($item->delivery_status));
        $this->assertModifiesNumberProperty($item, 'id_kor', fake()->randomDigitNot($item->id_kor));
        $this->assertModifiesNumberProperty($item, 'discount_type_since_movil', fake()->randomDigitNot($item->discount_type_since_movil));
        $this->assertModifiesNumberProperty($item, 'update_bybackend', fake()->randomDigitNot($item->update_bybackend));
        $this->assertModifiesNumberProperty($item, 'leido', fake()->randomDigitNot($item->leido));
        $this->assertModifiesNumberProperty($item, 'proforma', fake()->randomDigitNot($item->proforma));

        $this->assertModifiesNumberProperty($item, 'amount', fake()->randomFloat(config('app.money_decimal_precision')));
        $this->assertModifiesNumberProperty($item, 'discount_a', fake()->randomFloat(config('app.money_decimal_precision')));
        $this->assertModifiesNumberProperty($item, 'total_amount', fake()->randomFloat(config('app.money_decimal_precision')));
        $this->assertModifiesNumberProperty($item, 'amount_delivered', fake()->randomFloat(config('app.money_decimal_precision')));
        $this->assertModifiesNumberProperty($item, 'discount_delivered', fake()->randomFloat(config('app.money_decimal_precision')));
        $this->assertModifiesNumberProperty($item, 'total_delivered', fake()->randomFloat(config('app.money_decimal_precision')));
        $this->assertModifiesNumberProperty($item, 'discount', fake()->randomFloat(config('app.money_decimal_precision')));
        $this->assertModifiesNumberProperty($item, 'discount_movil', fake()->randomFloat(2));

       $this->assertModifiesDateProperty($item, 'date_confirmed');
       $this->assertModifiesDateProperty($item, 'send_email_order');
       $this->assertModifiesDateProperty($item, 'date_rejected_email');
       $this->assertModifiesDateProperty($item, 'date_expired_email');
       $this->assertModifiesDateProperty($item, 'virified_byportal');
    }
}
