<?php

namespace Tests\Unit;

use App\Models\Currency;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class CurrencyTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = Currency::class;

    public function testModify()
    {
        /** @var Currency $item */
        $item = $this->createModelInstance();
        $this->assertModifiesStringProperty($item, 'name');
        $this->assertModifiesStringProperty($item, 'code', fake()->currencyCode);
        $uniqueLetter = fake()->randomLetter();
        while ($uniqueLetter === $item->symbol) {
            $uniqueLetter = fake()->randomLetter();
        }
        $this->assertModifiesStringProperty($item, 'symbol', $uniqueLetter);

//        $this->assertModifiesNumberProperty($item, 'tags');
    }
}
