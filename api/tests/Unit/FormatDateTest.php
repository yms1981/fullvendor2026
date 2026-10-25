<?php

namespace Tests\Unit;

use App\Models\FormatDate;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class FormatDateTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = FormatDate::class;

    public function testModify()
    {
        /** @var FormatDate $item */
        $item = $this->createModelInstance();
        $this->assertModifiesStringProperty($item, 'format_php');
        $this->assertModifiesStringProperty($item, 'format_js');
        $this->assertModifiesStringProperty($item, 'date_firstmonth');
        $this->assertModifiesStringProperty($item, 'default');
        $this->assertModifiesStringProperty($item, 'month_year');
    }
}
