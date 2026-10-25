<?php

namespace Tests\Unit;

use App\Models\CurrencyFormat;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class CurrencyFormatTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = CurrencyFormat::class;

    public function testModify()
    {
        /** @var CurrencyFormat $item */
        $item = $this->createModelInstance();
        $this->assertModifiesStringProperty($item, 'CurrencyName');
        $this->assertModifiesStringProperty($item, 'CurrencyCode');
        $this->assertModifiesStringProperty($item, 'CurrencyFormat');
        $this->assertModifiesStringProperty($item, 'currOutput');
        $this->assertModifiesStringProperty($item, 'CodeRegional');

        $this->assertModifiesNumberProperty($item, 'order',fake()->randomDigitNot($item->order));
    }
}
