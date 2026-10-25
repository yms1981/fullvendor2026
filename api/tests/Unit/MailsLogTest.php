<?php

namespace Tests\Unit;

use App\Models\MailsLog;
use Tests\ModelBaseTestCase;
use Tests\ModelWithModificableProperties;

class MailsLogTest extends ModelBaseTestCase implements ModelWithModificableProperties
{
    protected $model = MailsLog::class;

    public function testModify()
    {
        /** @var MailsLog $item */
        $item = $this->createModelInstance();
        $this->assertModifiesStringProperty($item, 'customers');
        $this->assertModifiesStringProperty($item, 'subject');
        $this->assertModifiesStringProperty($item, 'content');

        $this->assertModifiesDateProperty($item, 'fecha');
    }
}
