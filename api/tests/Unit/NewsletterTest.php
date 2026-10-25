<?php

namespace Tests\Unit;
use App\Models\Newsletter;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class NewsletterTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = Newsletter::class;

    public function testModify()
    {
        /** @var Newsletter $item */
        $item = $this->createModelInstance();
        $this->assertModifiesStringProperty($item,'email');
        $this->assertModifiesNumberProperty($item, 'status',!$item->status);
    }
}
