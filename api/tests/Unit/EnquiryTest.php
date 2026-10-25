<?php

namespace Tests\Unit;

use App\Models\Enquiry;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class EnquiryTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = Enquiry::class;

    public function testModify()
    {
        /** @var Enquiry $item */
        $item = $this->createModelInstance();
        $this->assertModifiesStringProperty($item, 'name');
        $this->assertModifiesStringProperty($item, 'email');
        $this->assertModifiesStringProperty($item, 'phone');
        $this->assertModifiesStringProperty($item, 'subject');
        $this->assertModifiesStringProperty($item, 'message');
        $this->assertModifiesNumberProperty($item, 'status', fake()->randomDigitNot($item->status));
    }
}
