<?php

namespace Tests\Unit;

use App\Models\FormatNumeric;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class FormatNumericTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = FormatNumeric::class;

    public function testModify()
    {
        /** @var FormatNumeric $item */
        $item = $this->createModelInstance();
        $this->assertModifiesStringProperty($item, 'numeric_format');
        $this->assertModifiesStringProperty($item, 'radixPoint');
        $this->assertModifiesStringProperty($item, 'groupSeparator');
        $this->assertModifiesStringProperty($item, 'code_regional');

        $this->assertModifiesNumberProperty($item, 'digits', fake()->randomDigitNot($item->digits));
        $this->assertModifiesNumberProperty($item, 'default', fake()->randomDigitNot($item->default));
    }
}
