<?php

namespace Tests\Unit;

use App\Models\CatalogSentMail;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class CatalogSentMailTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = CatalogSentMail::class;

    public function testModify()
    {
        /** @var CatalogSentMail $item */
        $item = $this->createModelInstance();
        $this->assertModifiesNumberProperty($item,'hide_price',!$item->hide_price);

        $this->assertModifiesStringProperty($item,'catalog_text');
        $this->assertModifiesStringProperty($item,'catalog_send_type');
        $this->assertModifiesStringProperty($item,'customer_emails');


        $this->assertModifiesDateProperty($item,'send_date');
    }
}
